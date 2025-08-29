<?php
declare(strict_types=1);

namespace App;

use App\HttpClientInterface;
use App\Config;

class SierraApiClient {
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $tokenEndpoint;
    private string $queryEndpoint;
    private array $queryParameters;
    private array $queryFields;
    private string $itemFields;

    private ?string $token = null;
    private ?int $expiresAt;
    private HttpClientInterface $httpClient;

    public function __construct(Config $config, HttpClientInterface $httpClient) {
        $this->baseUrl = rtrim($config->getApiBaseUrl(), '/');
        $this->apiKey = $config->getApiKey();
        $this->apiSecret = $config->getApiSecret();
        $this->tokenEndpoint = $config->getTokenEndpoint();
        $this->queryEndpoint = $config->getQueryEndpoint();
        $this->queryParameters = $config->getQueryParameters();
        $this->queryFields = $config->getQueryFields();
        $this->itemFields = $config->getItemFields();
        $this->httpClient = $httpClient;
    }

    private function getToken(): string {
        if ($this->token !== null && $this->expiresAt !== null && time() < ($this->expiresAt - 10)) {
            return $this->token;
        }

        $this->authenticate();

        if ($this->token === null) {
            throw new \RuntimeException("Kunde inte hämta en giltig API-token.");
        }

        return $this->token;
    }

    private function authenticate(): void {
        $url = $this->baseUrl . $this->tokenEndpoint;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiSecret}")
        ];
        $body = "grant_type=client_credentials";

        $response = $this->httpClient->request($url, 'POST', $headers, $body);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Autentisering misslyckades: HTTP {$response['status']} - {$response['error']}");
        }

        $data = json_decode($response['response'], true);
        if (!isset($data['access_token']) || !isset($data['expires_in'])) {
            throw new \RuntimeException("Token saknas i autentiseringssvaret.");
        }
        $this->token = $data['access_token'];
        $this->expiresAt = time() + (int)$data['expires_in'];
    }

    public function queryBibs(array $identifiers): ?array {
        $token = $this->getToken();
        $query = $this->buildCombinedQuery($identifiers);

        if ($query === null) {
            return null;
        }

        $fields = "id,items{" . $this->itemFields . "}";
        $params = http_build_query([
            'limit' => $this->queryParameters['limit'],
            'offset' => $this->queryParameters['offset'],
            'fields' => $fields,
        ]);

        $url = $this->baseUrl . $this->queryEndpoint . '?' . $params;
        $jsonQuery = json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $response = $this->httpClient->request($url, 'POST', $headers, $jsonQuery);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Sökning misslyckades: HTTP {$response['status']} - {$response['error']}");
        }

        return $this->extractItemsFromBibsResponse($response['response']);
    }

    private function buildCombinedQuery(array $identifiers): ?array {
        $queryParts = [];
        $fields = $this->queryFields;
        
        // Hitta den mest prioriterade söknyckeln (Libris.kb ID, ISBN, ISSN)
        $priorityKey = $this->findFirstAvailableKey($fields, $identifiers, ['bib_id', 'isbn', 'issn']);
        
        if ($priorityKey !== null) {
            $field = $fields[$priorityKey];
            $queryParts[] = $this->makeFieldQuery(
                ['type' => 'bib'],
                [$field['type'] => $field['value']],
                $identifiers[$priorityKey]
            );
        }

        // Lägg till Onr som ett "eller"-alternativ om det finns
        if (!empty($identifiers['onr']) && isset($fields['onr'])) {
            if (!empty($queryParts)) {
                $queryParts[] = 'or';
            }
            $field = $fields['onr'];
            $queryParts[] = $this->makeFieldQuery(
                ['type' => 'bib'],
                [$field['type'] => $field['value']],
                $identifiers['onr']
            );
        }

        return empty($queryParts) ? null : ['queries' => $queryParts];
    }
    
    private function extractItemsFromBibsResponse(string $json): ?array {
        $data = json_decode($json, true);
        if (!isset($data['entries']) || !is_array($data['entries'])) {
            return null;
        }

        $allItems = [];
        foreach ($data['entries'] as $entry) {
            if (isset($entry['items']) && is_array($entry['items'])) {
                $allItems = array_merge($allItems, $entry['items']);
            }
        }
        return $allItems ?: null;
    }

    private function makeFieldQuery(array $record, array $fieldKey, string $value): array {
        return [
            'target' => [
                'record' => $record,
                'field' => $fieldKey
            ],
            'expr' => [
                'op' => 'equals',
                'operands' => [$value]
            ]
        ];
    }

    private function findFirstAvailableKey(array $fields, array $identifiers, array $preferredKeys): ?string {
        foreach ($preferredKeys as $key) {
            if (isset($fields[$key]) && !empty($identifiers[$key])) {
                return $key;
            }
        }
        return null;
    }
}
