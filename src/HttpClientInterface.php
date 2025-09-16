<?php

declare(strict_types=1);

namespace App;

interface HttpClientInterface
{
    /**
     * Utför ett HTTP-anrop.
     *
     * @param  string                $url     Den
     *                                        fullständiga
     *                                        URL:en.
     * @param  string                $method  HTTP-metoden (t.ex. 'GET', 'POST').
     * @param  array<string, string> $headers En array med HTTP-headers.
     * @param  mixed                 $body    Innehållet i förfrågan (för POST,
     *                                        PUT, etc.).
     * @return array{status: int, response: string, error: string|null} Innehåller 'status', 'response' och 'error'.
     */
    public function request(string $url, string $method = 'GET', array $headers = [], $body = null): array;
}
