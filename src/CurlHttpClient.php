<?php
declare(strict_types=1);

namespace App;

class CurlHttpClient implements HttpClientInterface
{
    /**
     * Utför HTTP-anrop med cURL
     *
     * @return array ['status' => int, 'response' => string, 'error' => string]
     */
    public function request(string $url, string $method = 'GET', array $headers = [], $body = null): array
    {
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