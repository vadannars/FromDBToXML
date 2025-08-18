<?php
declare(strict_types=1);

namespace App;

use App\HttpClientInterface;

class SierraApiClient {
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private ?string $token = null;
    private ?int $expiresAt;

    private HttpClientInterface $httpClient;

    private const ITEM_FIELDS = 'location,callNumber,status';

    public function __construct(string $baseUrl, string $apiKey, string $apiSecret, HttpClientInterface $httpClient) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->httpClient = $httpClient;
    }

    /**
     * Hämtar en giltig token. Om den befintliga token är ogiltig eller saknas, hämtas en ny.
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getToken(): string {
        // Kontrollera om en giltig token redan finns.
        // Vi tar bort 10 sekunder från utgångstiden för att vara på den säkra sidan.
        if ($this->token !== null && $this->expiresAt !== null && time() < ($this->expiresAt - 10)) {
            return $this->token;
        }

        // Om token saknas eller har gått ut, hämta en ny.
        $this->authenticate();

        if ($this->token === null) {
            throw new \RuntimeException("Kunde inte hämta en giltig API-token.");
        }

        return $this->token;
    }

    /**
     * Autentisera och hämta token.
     *
     * @throws \RuntimeException
     */
    private function authenticate(): void {
        $url = $this->baseUrl . '/token';
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

    /**
     * Skickar JSON-query.
     * 
     * @throws \RuntimeException
     */
    public function queryBibs(array $identifiers, int $limit = 10, int $offset = 0): ?array {
        $token = $this->getToken();

        $query = $this->buildCombinedQuery($identifiers);
        if ($query === null) {
            return null;
        }

        $url = $this->baseUrl . '/bibs/query?limit=' . $limit . '&offset=' . $offset;
        $jsonQuery = json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $response = $this->httpClient->request($url, 'POST', $headers, $jsonQuery);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Sökning misslyckades: HTTP {$response['status']}");
        }

        return $this->extractBibIdsFromResponse($response['response']);
    }

    /**
     * Hämtar exemplar för en bib-post.
     * 
     * @throws \RuntimeException
     */
    public function fetchItems(string $bibId): ?array {
        $token = $this->getToken();

        $params = http_build_query([
            'fields' => self::ITEM_FIELDS,
            'deleted' => 'false',
            'suppressed' => 'false',
            'bibIds' => $bibId
        ]);

        $url = $this->baseUrl . '/items/?' . $params;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ];

        $response = $this->httpClient->request($url, 'GET', $headers);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Kunde inte hämta items: HTTP {$response['status']}");
        }

        $data = json_decode($response['response'], true);
        return $data['entries'] ?? null;
    }

    /**
     * Bygg sökfråga utifrån tillgängliga identifierare.
     *
     * @param array<string, string|null> $identifiers
     * @return array<string, mixed>|null
     */

    private function buildCombinedQuery(array $identifiers): ?array {
        $queryParts = [];

        // Gemensam del i alla queries
        $record = ['type' => 'bib'];

        /**
         * Array med alla potentiella fält.
         * @var array<string, array{tag?: string, marcTag?: string, value: ?string}>
         */
        $fields = [
            'bib_id' => ['tag' => 'j',        'value' => $identifiers['bib_id'] ?? null],
            'issn'   => ['marcTag' => '022',  'value' => $identifiers['issn'] ?? null],
            'isbn'   => ['tag' => 'i',        'value' => $identifiers['isbn'] ?? null],
            'onr'    => ['marcTag' => '035',  'value' => $identifiers['onr'] ?? null],
        ];

        if (!empty($fields['bib_id']['value'])) {
            $queryParts[] = $this->makeFieldQuery(
                $record,
                ['tag' => $fields['bib_id']['tag']],
                $fields['bib_id']['value']
            );
        }

        $priorityField = $this->pickFirstAvailable($fields, ['issn', 'isbn']);
        if ($priorityField !== null) {
            if (!empty($queryParts)) {
                $queryParts[] = 'or';
            }

            $fieldKey = isset($priorityField['tag'])
                ? ['tag' => $priorityField['tag']]
                : ['marcTag' => $priorityField['marcTag']];

            $queryParts[] = $this->makeFieldQuery($record, $fieldKey, $priorityField['value']);
        }

        if (!empty($fields['onr']['value'])) {
            if (!empty($queryParts)) {
                $queryParts[] = 'or';
            }

            $queryParts[] = $this->makeFieldQuery(
                $record,
                ['marcTag' => $fields['onr']['marcTag']],
                $fields['onr']['value']
            );
        }

        return empty($queryParts) ? null : ['queries' => $queryParts];
    }

    private function makeFieldQuery(array $record, array $fieldKey, string $value): array {
        return [
            'target' => [
                'record' => $record,
                'field'  => $fieldKey
            ],
            'expr' => [
                'op' => 'equals',
                'operands' => [$value, '']
            ]
        ];
    }

    // Hjälpfunktion för att välja första matchande fält från prioriterad lista
    /**
     * @param array<string, array{value: ?string}> $fields
     * @param string[] $preferredKeys
     * @return array{tag?: string, marcTag?: string, value: string}|null
     */
    private function pickFirstAvailable(array $fields, array $preferredKeys): ?array {
        foreach ($preferredKeys as $key) {
            if (!empty($fields[$key]['value'])) {
                return $fields[$key];
            }
        }
        return null;
    }

    private function extractBibIdsFromResponse(string $json): ?array {
        $data = json_decode($json, true);
        if (!isset($data['entries']) || !is_array($data['entries'])) {
            return null;
        }

        $ids = [];
        foreach ($data['entries'] as $entry) {
            $id = $this->extractBibIdFromLink($entry['link'] ?? '');
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
