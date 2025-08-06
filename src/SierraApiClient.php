<?php
namespace App;

class SierraApiClient {
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private ?string $token = null;

    public function __construct(string $baseUrl, string $apiKey, string $apiSecret) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Autentisera och hämta token.
     *
     * @throws \RuntimeException
     */
    public function authenticate(): void {
        $url = $this->baseUrl . '/token';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiSecret}")
        ];
        $body = "grant_type=client_credentials";

        $response = $this->makeHttpRequest($url, 'POST', $headers, $body);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Autentisering misslyckades: HTTP {$response['status']} - {$response['error']}");
        }

        $data = json_decode($response['response'], true);
        if (!isset($data['access_token'])) {
            throw new \RuntimeException("Token saknas i autentiseringssvaret.");
        }
        $this->token = $data['access_token'];
    }

    /**
     * @throws \RuntimeException
     */
    public function queryBibs(array $identifiers, int $limit = 10, int $offset = 0): ?array {
        if (!$this->token) {
            throw new \RuntimeException("Saknar giltig token, autentisera först.");
        }

        $query = $this->buildCombinedQuery($identifiers);
        if ($query === null) {
            return null;
        }

        $url = $this->baseUrl . '/bibs/query?limit=' . $limit . '&offset=' . $offset;
        $jsonQuery = json_encode($query, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $response = $this->makeHttpRequest($url, 'POST', $headers, $jsonQuery);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Sökning misslyckades: HTTP {$response['status']}");
        }

        return $this->extractBibIdsFromResponse($response['response']);
    }

    /**
     * @throws \RuntimeException
     */
    public function fetchItems(string $bibId): ?array {
        if (!$this->token) {
            throw new \RuntimeException("Saknar giltig token, autentisera först.");
        }

        $params = http_build_query([
            'fields' => 'location,callNumber,status',
            'deleted' => 'false',
            'suppressed' => 'false',
            'bibIds' => $bibId
        ]);

        $url = $this->baseUrl . '/items/?' . $params;
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ];

        $response = $this->makeHttpRequest($url, 'GET', $headers);

        if ($response['status'] !== 200) {
            throw new \RuntimeException("Kunde inte hämta items: HTTP {$response['status']}");
        }

        $data = json_decode($response['response'], true);
        return $data['entries'] ?? null;
    }

    private function buildCombinedQuery(array $identifiers): ?array {
        $fields = [
            'bib_id' => ['tag' => 'j',        'value' => $identifiers['bib_id'] ?? null],
            'isbn'   => ['tag' => 'i',        'value' => $identifiers['isbn'] ?? null],
            'onr'    => ['marcTag' => '035',  'value' => $identifiers['onr'] ?? null]
        ];

        $queryParts = [];
        foreach ($fields as $field) {
            if (empty($field['value'])) continue;

            if (!empty($queryParts)) {
                $queryParts[] = 'or';
            }

            $targetField = isset($field['tag']) ? ['tag' => $field['tag']] : ['marcTag' => $field['marcTag']];

            $queryParts[] = [
                'target' => [
                    'record' => ['type' => 'bib'],
                    'field'  => $targetField
                ],
                'expr' => [
                    'op' => 'equals',
                    'operands' => [$field['value'], '']
                ]
            ];
        }

        return empty($queryParts) ? null : ['queries' => $queryParts];
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

    /**
     * Utför HTTP-anrop med cURL
     *
     * @return array ['status' => int, 'response' => string, 'error' => string]
     */
    private function makeHttpRequest(string $url, string $method = 'GET', array $headers = [], $body = null): array {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && $body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            $formattedHeaders = [];
            foreach ($headers as $key => $value) {
                $formattedHeaders[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'response' => $response, 'error' => $error];
    }
}
