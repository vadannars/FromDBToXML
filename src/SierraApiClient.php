<?php

declare(strict_types=1);

namespace App;

use App\HttpClientInterface;
use App\Config;
use Monolog\Logger;

/**
 * Klient för att interager med Sierra API:et.
 *
 * Hanterar autentisering, sökningar efter bibliografiska poster (bibs)
 * och hämtning av exemplar (items) baserat på olika identifierare.
 */
class SierraApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $tokenEndpoint;
    private string $queryEndpoint;
    private string $itemsEndpoint;
    /**
     * @var array<string, int>
     */
    private array $queryParameters;
    /**
     * @var array<string, array<string, string>|null>
     */
    private array $queryFields;
    private string $itemFields;

    private ?string $token = null;
    private ?int $expiresAt = null;
    private HttpClientInterface $httpClient;
    private Logger $logger;

    /**
     * Skapar en ny instans av SierraApiClient.
     *
     * @param Config              $config     En konfigurationsobjekt med API-uppgifter.
     * @param HttpClientInterface $httpClient En HTTP-klientimplementering (t.ex. GuzzleHttpClient).
     * @param Logger              $logger     En loggerinstans
     *                                        för loggning.
     */
    public function __construct(Config $config, HttpClientInterface $httpClient, Logger $logger)
    {
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
        $this->logger = $logger;
    }

    /**
     * Hämtar en API-token och hanterar caching för att undvika onödiga autentiseringar.
     *
     * @return string Den giltiga API-token.
     * @throws \RuntimeException Om autentiseringen misslyckas.
     */
    private function getToken(): string
    {
        if ($this->token !== null && $this->expiresAt !== null && time() < ($this->expiresAt - 10)) {
            $this->logger->info('Använder cachad API-token.');
            return $this->token;
        }
        $this->logger->info('Hämtar ny API-token.');
        $this->authenticate();

        if ($this->token === null) {
            $this->logger->error('Kunde inte hämta en giltig API-token.');
            throw new \RuntimeException("Kunde inte hämta en giltig API-token.");
        }

        return $this->token;
    }

    /**
     * Autentiserar mot Sierra API:et för att hämta en ny access-token.
     *
     * @throws \RuntimeException Om autentiseringen misslyckas.
     */
    private function authenticate(): void
    {
        $url = $this->baseUrl . $this->tokenEndpoint;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiSecret}")
        ];
        $body = "grant_type=client_credentials";

        try {
            $response = $this->httpClient->request($url, 'POST', $headers, $body);
        } catch (\Exception $e) {
            $this->logger->error('HTTP-förfrågan för autentisering misslyckades.', ['error' => $e->getMessage()]);
            throw new \RuntimeException("HTTP-förfrågan för autentisering misslyckades.", 0, $e);
        }

        $decodedResponse = json_decode($response['response'], true);

        if ($response['status'] !== 200) {
            $errorMessage = $response['error'] ?: 'Okänd felorsak vid autentisering';
            $this->logger->error(
                'Autentisering misslyckades.',
                ['status' => $response['status'],
                                'error' => $errorMessage]
            );
            throw new \RuntimeException("Autentisering misslyckades: HTTP {$response['status']} - {$errorMessage}");
        }

        if (!is_array($decodedResponse) || !isset($decodedResponse['access_token']) ||
            !is_string($decodedResponse['access_token']) ||
            !isset($decodedResponse['expires_in']) ||
            !is_int($decodedResponse['expires_in'])
        ) {
            $responseBodyContent = $response['response'];
            $this->logger->error(
                'Ogiltigt eller ofullständigt svar från autentiseringen.',
                ['response' => $responseBodyContent]
            );
            throw new \RuntimeException("Ogiltigt eller ofullständigt svar från autentiseringen.");
        }

        $this->token = $decodedResponse['access_token'];
        $this->expiresAt = time() + $decodedResponse['expires_in'];
        $this->logger->info('Autentisering lyckades.', ['expires_in' => $decodedResponse['expires_in']]);
    }

    /**
     * Hämtar exemplar från Sierra API:et baserat på en eller flera identifierare.
     *
     * @param  array<string, string|null> $identifiers En associativ array av identifierare (t.ex. 'bib_id', 'isbn').
     * @return array<array<string, mixed>>|null En array av exemplarinformation, eller null om inga hittades.
     * @throws \RuntimeException Om API-förfrågan misslyckas.
     */
    public function getItemsForIdentifiers(array $identifiers): ?array
    {
        $token = $this->getToken();
        $bibQuery = $this->buildCombinedQuery($identifiers);

        if ($bibQuery === null) {
            $this->logger->warning('Inga giltiga identifierare hittades för sökning.');
            return null;
        }

        $this->logger->debug('Bygger JSON-query för bibs-sökning.', ['query' => $bibQuery]);

        $bibParams = http_build_query(
            [
            'limit' => $this->queryParameters['limit'],
            'offset' => $this->queryParameters['offset']
            ]
        );

        $bibUrl = $this->baseUrl . $this->queryEndpoint . '?' . $bibParams;
        $jsonQuery = json_encode($bibQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        try {
            $bibResponse = $this->httpClient->request($bibUrl, 'POST', $headers, $jsonQuery);
        } catch (\Exception $e) {
            $this->logger->error(
                'HTTP-förfrågan för bibs-sökning misslyckades.',
                ['error' => $e->getMessage()]
            );
            throw new \RuntimeException("HTTP-förfrågan för bibs-sökning misslyckades.", 0, $e);
        }

        $decodedBibResponse = json_decode($bibResponse['response'], true);

        if ($bibResponse['status'] !== 200) {
            $errorMessage = $bibResponse['error'] ?: 'Okänd felorsak vid bibs-sökning';
            $this->logger->error(
                'Bibs-sökning misslyckades.',
                ['status' => $bibResponse['status'],
                                'error' => $errorMessage]
            );
            throw new \RuntimeException("Sökning misslyckades: HTTP {$bibResponse['status']} - {$errorMessage}");
        }

        if (!is_array($decodedBibResponse)) {
            $this->logger->error(
                'Ogiltigt JSON-svar från bibs-sökningen.',
                ['response' => $bibResponse['response']]
            );
            throw new \RuntimeException("Ogiltigt JSON-svar från bibs-sökningen.");
        }
        /**
        * @var array<string, mixed> $bibData
        */
        $bibData = $decodedBibResponse;

        $bibIds = $this->extractBibIdsFromResponse($bibData);

        if (empty($bibIds)) {
            $this->logger->info('Inga bibs hittades för de angivna identifierarna.');
            return null;
        }

        $this->logger->info('Hittade bibs, hämtar exemplar.', ['bibIds' => $bibIds]);

        $itemParams = http_build_query(
            [
            'fields' => $this->itemFields,
            'bibIds' => implode(',', $bibIds)
            ]
        );

        $itemsUrl = $this->baseUrl . $this->itemsEndpoint . '?' . $itemParams;
        try {
            $itemsResponse = $this->httpClient->request($itemsUrl, 'GET', $headers);
        } catch (\Exception $e) {
            $this->logger->error(
                'HTTP-förfrågan för items-hämtning misslyckades.',
                ['error' => $e->getMessage()]
            );
            throw new \RuntimeException("HTTP-förfrågan för items-hämtning misslyckades.", 0, $e);
        }

        $decodedItemsResponse = json_decode($itemsResponse['response'], true);

        if ($itemsResponse['status'] !== 200) {
            $errorMessage = $itemsResponse['error'] ?: 'Okänd felorsak vid items-hämtning';
            $this->logger->error(
                'Items-hämtning misslyckades.',
                ['status' => $itemsResponse['status'],
                                'error' => $errorMessage]
            );
            throw new \RuntimeException("Kunde inte hämta exemplar: HTTP {$itemsResponse['status']} - {$errorMessage}");
        }

        if (!is_array($decodedItemsResponse)) {
            $this->logger->error(
                'Ogiltigt JSON-svar från items-sökningen.',
                ['response' => $itemsResponse['response']]
            );
            throw new \RuntimeException("Ogiltigt JSON-svar från items-sökningen.");
        }
        /**
        * @var array<string, mixed> $itemsData
        */
        $itemsData = $decodedItemsResponse;

        $entries = $itemsData['entries'] ?? null;

        if (!is_array($entries)) {
            $this->logger->info('Inga exemplar hittades i items-svaret.');
            return null;
        }

        $this->logger->info('Hämtade exemplar framgångsrikt.', ['item_count' => count($entries)]);

        /**
        * @var array<array<string, mixed>> $sanitizedEntries
        */
        $sanitizedEntries = [];
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $sanitizedEntry = [];
                foreach ($entry as $key => $value) {
                    if (is_string($key)) {
                        $sanitizedEntry[$key] = $value;
                    }
                }
                $sanitizedEntries[] = $sanitizedEntry;
            }
        }

        return $sanitizedEntries;
    }

    /**
     * Bygger den kombinerade JSON-queryn för att söka efter bibliografiska poster.
     *
     * @param  array<string, string|null> $identifiers En array av identifierare.
     * @return array<string, mixed>|null Den färdiga JSON-query-arrayen, eller null om inga giltiga identifierare finns.
     */
    private function buildCombinedQuery(array $identifiers): ?array
    {
        /**
        * @var array<mixed> $queryParts
        */
        $queryParts = [];
        $record = ['type' => 'bib'];
        $fields = $this->queryFields;

        $priorityKey = $this->findFirstAvailableKey($fields, $identifiers, ['bib_id', 'isbn', 'issn']);

        if ($priorityKey !== null) {
            $field = $fields[$priorityKey];
            $identifierValue = $identifiers[$priorityKey];
            if ($field !== null && $identifierValue !== null) {
                $queryParts[] = $this->makeFieldQuery(
                    $record,
                    $field,
                    $identifierValue
                );
            }
        }

        if (isset($identifiers['onr']) && $identifiers['onr'] !== '') {
            $onrField = $fields['onr'] ?? null;
            $onrValue = $identifiers['onr'];
            if ($onrField !== null) {
                if (!empty($queryParts)) {
                    $queryParts[] = 'or';
                }
                $queryParts[] = $this->makeFieldQuery(
                    $record,
                    $onrField,
                    $onrValue
                );
            }
        }

        if (empty($queryParts)) {
            $this->logger->warning('Kunde inte bygga query, inga giltiga fält hittades.');
            return null;
        }

        /**
        * @var array<string, mixed> $finalQuery
        */
        $finalQuery = ['queries' => $queryParts];
        return $finalQuery;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, string> $fieldKey
     * @param  string                $value
     * @return array<string, mixed>
     */
    private function makeFieldQuery(array $record, array $fieldKey, string $value): array
    {
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
     * @param  array<string, array<string, string>|null> $fields
     * @param  array<string, string|null>                $identifiers
     * @param  array<int, string>                        $preferredKeys
     * @return string|null
     */
    private function findFirstAvailableKey(array $fields, array $identifiers, array $preferredKeys): ?string
    {
        foreach ($preferredKeys as $key) {
            if (isset($fields[$key]) && isset($identifiers[$key]) && $identifiers[$key] !== '') {
                return $key;
            }
        }
        return null;
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<int, string>|null
     */
    private function extractBibIdsFromResponse(array $data): ?array
    {
        if (!isset($data['entries']) || !is_array($data['entries'])) {
            return null;
        }

        $ids = [];
        /**
        * @var mixed $entry
        */
        foreach ($data['entries'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $link = $entry['link'] ?? null;
            if (is_string($link)) {
                $id = $this->extractBibIdFromLink($link);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }

        return $ids ?: null;
    }

    /**
     * Extraherar biblioteks-ID från en länksträng.
     *
     * @param  string $link Länken som innehåller biblioteks-ID.
     * @return string|null Det extraherade biblioteks-ID:t, eller null om det inte kunde extraheras.
     */
    private function extractBibIdFromLink(string $link): ?string
    {
        // basename() returnerar en sträng om $link är en sträng.
        // Vi kollar om resultatet är en tom sträng.
        $id = basename($link);
        if ($id === '') {
            return null;
        }
        return $id;
    }
}
