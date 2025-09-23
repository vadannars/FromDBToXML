<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * En HTTP-klientimplementering som använder GuzzleHttp.
 *
 * Denna klass ansluter till det externa API:et och hanterar
 * förfrågningar och svar på ett robust sätt, inklusive felhantering.
 */
class GuzzleHttpClient implements HttpClientInterface
{
    private Client $guzzleClient;

    /**
     * Skapar en ny instans av GuzzleHttpClient.
     */
    public function __construct()
    {
        $this->guzzleClient = new Client();
    }

    /**
     * Utför ett HTTP-anrop med Guzzle.
     *
     * @param  string                $url     Den
     *                                        fullständiga
     *                                        URL:en.
     * @param  string                $method  HTTP-metoden (t.ex. 'GET', 'POST').
     * @param  array<string, string> $headers En array med HTTP-headers.
     * @param  mixed                 $body    Innehållet i förfrågan (för POST,
     *                                        PUT, etc.).
     * @return array<string, int|string> En associativ array som innehåller 'status', 'response' och 'error'.
     */
    public function request(string $url, string $method = 'GET', array $headers = [], $body = null): array
    {
        $options = [
            'headers' => $headers,
        ];

        if ($body !== null) {
            $options['body'] = $body;
        }

        try {
            $response = $this->guzzleClient->request($method, $url, $options);

            return [
                'status' => $response->getStatusCode(),
                'response' => $response->getBody()->getContents(),
                'error' => ''
            ];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;
            $responseBody = $response ? $response->getBody()->getContents() : '';
            return [
                'status' => $statusCode,
                'response' => $responseBody,
                'error' => $e->getMessage()
            ];
        }
    }
}
