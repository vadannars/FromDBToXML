<?php
declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GuzzleHttpClient implements HttpClientInterface
{
    private Client $guzzleClient;

    public function __construct()
    {
        // Skapa en instans av Guzzle Client
        $this->guzzleClient = new Client();
    }

    public function request(string $url, string $method = 'GET', array $headers = [], $body = null): array
    {
        $options = [
            'headers' => $headers,
        ];

        // Guzzle hanterar body-data på olika sätt beroende på metod och Content-Type
        if ($body !== null) {
            if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
                $options['json'] = json_decode($body);
            } else {
                $options['body'] = $body;
            }
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
            if ($response) {
                return [
                    'status' => $response->getStatusCode(),
                    'response' => $response->getBody()->getContents(),
                    'error' => $e->getMessage()
                ];
            }
            
            return [
                'status' => 0,
                'response' => '',
                'error' => $e->getMessage()
            ];
        }
    }
}