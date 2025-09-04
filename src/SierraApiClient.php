<?php
declare(strict_types=1);

namespace App;

use App\HttpClientInterface;
use App\Config;

/**
 * Klient för att interagera med Sierra API:et.
 *
 * Hanterar autentisering, sökningar efter bibliografiska poster (bibs)
 * och hämtning av exemplar (items) baserat på olika identifierare.
 */
class SierraApiClient {
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $tokenEndpoint;
    private string $queryEndpoint;
    private string $itemsEndpoint;
    private array $queryParameters;
    private array $queryFields;
    private string $itemFields;

    private ?string $token = null;
    private ?int $expiresAt;
    private HttpClientInterface $httpClient;

    /**
     * Skapar en ny instans av SierraApiClient.
     *
     * @param Config $config En konfigurationsobjekt med API-uppgifter.
     * @param HttpClientInterface $httpClient En HTTP-klientimplementering (t.ex. GuzzleHttpClient).
     */
    public function __construct(Config $config, HttpClientInterface $httpClient) {
        $this->baseUrl = rtrim($config->getApiBaseUrl(), '/');
        $this->apiKey = $config->getApiKey();
        $this->apiSecret = $config->getApiSecret();
        $this->tokenEndpoint = $config->getTokenEndpoint();
        $this->queryEndpoint = $config->getQueryEndpoint();
        $this->itemsEndpoint = $config->getItemsEndpoint();

        /** @var array<string, int> $queryParameters */
        $queryParameters = $config->getQueryParameters();
        $this->queryParameters = $queryParameters;

        /** @var array<string, array<string, string>|null> $queryFields */
        $queryFields = $config->getQueryFields();
        $this->queryFields = $queryFields;
        $this->itemFields = $config->getItemFields();
        $this->httpClient = $httpClient;
    }

    /**
     * Hämtar en API-token och hanterar caching för att undvika onödiga autentiseringar.
     *
     * @return string Den giltiga API-token.
     * @throws \RuntimeException Om autentiseringen misslyckas.
     */
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

    /**
     * Autentiserar mot Sierra API:et för att hämta en ny access-token.
     *
     * @throws \RuntimeException Om autentiseringen misslyckas.
     */
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
        if (!is_array($data) || !isset($data['access_token']) || !isset($data['expires_in'])) {
            throw new \RuntimeException("Token saknas i autentiseringssvaret.");
        }
        $this->token = (string) $data['access_token'];
        $this->expiresAt = time() + (int)$data['expires_in'];
    }

    /**
     * Hämtar exemplar från Sierra API:et baserat på en eller flera identifierare.
     *
     * @param array<string, string|null> $identifiers En associativ array av identifierare (t.ex. 'bib_id', 'isbn').
     * @return array<array<string, mixed>>|null En array av exemplarinformation, eller null om inga hittades.
     * @throws \RuntimeException Om API-förfrågan misslyckas.
     */
    public function getItemsForIdentifiers(array $identifiers): ?array {
        $token = $this->getToken();
        $bibQuery = $this->buildCombinedQuery($identifiers);

        if ($bibQuery === null) {
            return null;
        }

        $bibParams = http_build_query([
            'limit' => $this->queryParameters['limit'],
            'offset' => $this->queryParameters['offset']
        ]);

        $bibUrl = $this->baseUrl . $this->queryEndpoint . '?' . $bibParams;
        $jsonQuery = json_encode($bibQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $bibResponse = $this->httpClient->request($bibUrl, 'POST', $headers, $jsonQuery);

        if ($bibResponse['status'] !== 200) {
            throw new \RuntimeException("Sökning misslyckades: HTTP {$bibResponse['status']} - {$bibResponse['error']}");
        }

        $bibData = json_decode($bibResponse['response'], true);
        if (!is_array($bibData)) {
            throw new \RuntimeException("Ogiltigt JSON-svar från bibs-sökningen.");
        }
        $bibIds = $this->extractBibIdsFromResponse($bibData);

        if (empty($bibIds)) {
            return null;
        }

        $itemParams = http_build_query([
            'fields' => $this->itemFields,
            'bibIds' => implode(',', $bibIds)]);
        
        $itemsUrl = $this->baseUrl . $this->itemsEndpoint . '?' . $itemParams;
        $itemsResponse = $this->httpClient->request($itemsUrl, 'GET', $headers);

        if ($itemsResponse['status'] !== 200) {
            throw new \RuntimeException("Kunde inte hämta exemplar: HTTP {$itemsResponse['status']} - {$itemsResponse['error']}");
        }

        $itemsData = json_decode($itemsResponse['response'], true);
        if (!is_array($itemsData)) {
            throw new \RuntimeException("Ogiltigt JSON-svar från items-sökningen.");
        }
        return $itemsData['entries'] ?? null;
    }

    /**
     * Bygger den kombinerade JSON-queryn för att söka efter bibliografiska poster.
     *
     * @param array<string, string|null> $identifiers An array of identifiers.
     * @return array|null Den färdiga JSON-query-arrayen, eller null om inga giltiga identifierare finns.
     */
    private function buildCombinedQuery(array $identifiers): ?array {
        $queryParts = [];
        $record = ['type' => 'bib'];
        $fields = $this->queryFields;
        
        // Hitta den mest prioriterade söknyckeln (Libris.kb ID, ISBN, ISSN)
        $priorityKey = $this->findFirstAvailableKey($fields, $identifiers, ['bib_id', 'isbn', 'issn']);
        
        if ($priorityKey !== null) {
            $field = $fields[$priorityKey];
            $queryParts[] = $this->makeFieldQuery(
                $record,
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
                $record,
                [$field['type'] => $field['value']],
                $identifiers['onr']
            );
        }

        return empty($queryParts) ? null : ['queries' => $queryParts];
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

    /**
     * @param array<string, array<string, string>|null> $fields
     * @param array<string, string|null> $identifiers
     * @param array<int, string> $preferredKeys
     * @return string|null
     */
    private function findFirstAvailableKey(array $fields, array $identifiers, array $preferredKeys): ?string {
        foreach ($preferredKeys as $key) {
            if (isset($fields[$key]) && !empty($identifiers[$key])) {
                return (string) $key;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>|null
     */
    private function extractBibIdsFromResponse(array $data): ?array {
        if (!isset($data['entries']) || !is_array($data['entries'])) {
            return null;
        }
        
        $ids = [];
        /** @var mixed $entry */
        foreach ($data['entries'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $link = (string) ($entry['link'] ?? '');
            $id = $this->extractBibIdFromLink($link);
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        
        return $ids ?: null;
    }

    private function extractBibIdFromLink(string $link): ?string {
        $id = basename($link);
        return $id !== '' ? $id : null;
    }
}
