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
    /** @var array<string, int> */
    private array $queryParameters;
    /** @var array<string, array<string, string>|null> */
    private array $queryFields;
    private string $itemFields;

    private ?string $token = null;
    private ?int $expiresAt = null;
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

        $this->queryParameters = $config->getQueryParameters();
        $this->queryFields = $config->getQueryFields();
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

        /** @var mixed $decodedResponse */
        $decodedResponse = json_decode($response['response'], true);
        
        if ($response['status'] !== 200) {
            // Säkerställ att $response['error'] är en sträng
            $errorMessage = is_string($response['error']) ? $response['error'] : 'Okänd felorsak';
            throw new \RuntimeException("Autentisering misslyckades: HTTP {$response['status']} - {$errorMessage}");
        }

        if (!is_array($decodedResponse)) {
            throw new \RuntimeException("Ogiltigt JSON-svar från autentiseringen.");
        }

        /** @var array<string, mixed> $data */
        $data = $decodedResponse;
        
        if (!array_key_exists('access_token', $data) || !is_string($data['access_token']) ||
            !array_key_exists('expires_in', $data) || !is_int($data['expires_in'])) {
            throw new \RuntimeException("Token eller utgångsdatum saknas i autentiseringssvaret.");
        }
        
        $this->token = $data['access_token'];
        $this->expiresAt = time() + $data['expires_in'];
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
        
        /** @var mixed $decodedBibResponse */
        $decodedBibResponse = json_decode($bibResponse['response'], true);

        if ($bibResponse['status'] !== 200) {
            $errorMessage = is_string($bibResponse['error']) ? $bibResponse['error'] : 'Okänd felorsak';
            throw new \RuntimeException("Sökning misslyckades: HTTP {$bibResponse['status']} - {$errorMessage}");
        }

        if (!is_array($decodedBibResponse)) {
            throw new \RuntimeException("Ogiltigt JSON-svar från bibs-sökningen.");
        }
        /** @var array<string, mixed> $bibData */
        $bibData = $decodedBibResponse;
        
        $bibIds = $this->extractBibIdsFromResponse($bibData);

        if (empty($bibIds)) {
            return null;
        }

        $itemParams = http_build_query([
            'fields' => $this->itemFields,
            'bibIds' => implode(',', $bibIds)]);
        
        $itemsUrl = $this->baseUrl . $this->itemsEndpoint . '?' . $itemParams;
        $itemsResponse = $this->httpClient->request($itemsUrl, 'GET', $headers);
        
        /** @var mixed $decodedItemsResponse */
        $decodedItemsResponse = json_decode($itemsResponse['response'], true);
        
        if ($itemsResponse['status'] !== 200) {
            $errorMessage = is_string($itemsResponse['error']) ? $itemsResponse['error'] : 'Okänd felorsak';
            throw new \RuntimeException("Kunde inte hämta exemplar: HTTP {$itemsResponse['status']} - {$errorMessage}");
        }
        
        if (!is_array($decodedItemsResponse)) {
            throw new \RuntimeException("Ogiltigt JSON-svar från items-sökningen.");
        }

        /** @var array<string, mixed> $itemsData */
        $itemsData = $decodedItemsResponse;

        if (!array_key_exists('entries', $itemsData) || !is_array($itemsData['entries'])) {
            return null;
        }
        
        /** @var array<array<string, mixed>> $entries */
        $entries = [];
        foreach ($itemsData['entries'] as $entry) {
            if (is_array($entry)) {
                $sanitizedEntry = [];
                foreach ($entry as $key => $value) {
                    if (is_string($key)) {
                        $sanitizedEntry[$key] = $value;
                    }
                }
                $entries[] = $sanitizedEntry;
            }
        }
        
        return $entries;
    }

    /**
     * Bygger den kombinerade JSON-queryn för att söka efter bibliografiska poster.
     *
     * @param array<string, string|null> $identifiers En array av identifierare.
     * @return array<string, mixed>|null Den färdiga JSON-query-arrayen, eller null om inga giltiga identifierare finns.
     */
    private function buildCombinedQuery(array $identifiers): ?array {
        /** @var array<mixed> $queryParts */
        $queryParts = [];
        $record = ['type' => 'bib'];
        $fields = $this->queryFields;
        
        $priorityKey = $this->findFirstAvailableKey($fields, $identifiers, ['bib_id', 'isbn', 'issn']);
        
        if ($priorityKey !== null) {
            $field = $fields[$priorityKey];
            if ($field !== null && isset($identifiers[$priorityKey])) {
                $queryParts[] = $this->makeFieldQuery(
                    $record,
                    ['tag' => $field['value']],
                    (string) $identifiers[$priorityKey]
                );
            }
        }

        if (array_key_exists('onr', $identifiers) && !empty($identifiers['onr']) && array_key_exists('onr', $fields) && $fields['onr'] !== null) {
            if (!empty($queryParts)) {
                $queryParts[] = 'or';
            }
            $queryParts[] = $this->makeFieldQuery(
                $record,
                ['marcTag' => '035'],
                (string) $identifiers['onr']
            );
        }

        // Säkerställ att returtypen är korrekt specificerad för PHPStan
        if (empty($queryParts)) {
            return null;
        }

        /** @var array<string, mixed> $finalQuery */
        $finalQuery = ['queries' => $queryParts];
        return $finalQuery;
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, string> $fieldKey
     * @param string $value
     * @return array<string, mixed>
     */
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
            // Säkerställ att nyckeln finns och har ett värde i både $fields och $identifiers.
            if (isset($fields[$key]) && $fields[$key] !== null && isset($identifiers[$key]) && !empty($identifiers[$key])) {
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
            // Säkerställ att 'link' nyckeln finns och är en sträng.
            $link = isset($entry['link']) && is_string($entry['link']) ? $entry['link'] : '';
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