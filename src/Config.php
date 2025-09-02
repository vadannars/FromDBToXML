<?php
namespace App;

use Dotenv\Dotenv;

class Config {
    private array $data;

    public function __construct($envPath) {
        $dotenv = Dotenv::createImmutable($envPath);
        $dotenv->safeLoad();
        
        $this->data = [
            'api_key'           => $_ENV['API_KEY'] ?? '',
            'api_secret'        => $_ENV['API_SECRET'] ?? '',
            'allowed_origins'   => $_ENV['ALLOWED_ORIGINS'] ?? '',
            'api_base_url'      => $_ENV['API_BASE_URL'] ?? '',
            'token_endpoint'    => $_ENV['TOKEN_ENDPOINT'] ?? '',
            'query_endpoint'    => $_ENV['QUERY_ENDPOINT'] ?? '',
            'items_endpoint'    => $_ENV['ITEMS_ENDPOINT'] ?? '',
            'query_parameters'  => [
                'offset'    => (int)($_ENV['QUERY_OFFSET'] ?? 0),
                'limit'     => (int)($_ENV['QUERY_LIMIT'] ?? 10)
            ],
            'query_fields' => [
                'bib_id'    => $this->parseFieldString($_ENV['QUERY_LIBRIS_ID'] ?? null),
                'isbn'      => $this->parseFieldString($_ENV['QUERY_ISBN'] ?? null),
                'issn'      => $this->parseFieldString($_ENV['QUERY_ISSN'] ?? null),
                'onr'       => $this->parseFieldString($_ENV['QUERY_ONR'] ?? null)
            ],
            'item_fields'       => $_ENV['ITEM_FIELDS'] ?? 'location,callNumber,status',
            'active'            => filter_var($_ENV['ACTIVE'] ?? false, FILTER_VALIDATE_BOOL),
            'log_level'         => $_ENV['LOG_LEVEL'] ?? 'debug',
            'log_destination'   => $_ENV['LOG_DESTINATION'] ?? __DIR__ . '/../logs/app.log'
        ];
    }
    
    /**
     * Parsar en sträng från .env-filen (t.ex. "tag:j") till en array.
     *
     * @param string|null $fieldString
     * @return array<string, string>|null
     */
    private function parseFieldString(?string $fieldString): ?array {
        if (empty($fieldString)) {
            return null;
        }
        $parts = explode(':', $fieldString, 2);
        if (count($parts) !== 2) {
            return null;
        }
        return [
            'type' => $parts[0],
            'value' => $parts[1]
        ];
    }

    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function getAllowedOrigins(): string {
        return $this->get('allowed_origins', '');
    }

    public function getApiBaseUrl(): string {
        return rtrim($this->get('api_base_url'), '/');
    }

    public function getApiKey(): string {
        return $this->get('api_key');
    }

    public function getApiSecret(): string {
        return $this->get('api_secret');
    }

    public function getTokenEndpoint(): string {
        return $this->get('token_endpoint');
    }

    public function getQueryEndpoint(): string {
        return $this->get('query_endpoint');
    }

    public function getItemsEndpoint(): string {
        return $this->get('items_endpoint');
    }

    public function getItemFields(): string {
        return $this->get('item_fields');
    }

    public function getLogLevel(): string {
        return strtolower($this->get('log_level', 'debug'));
    }

    public function getLogDestination(): string {
        return $this->get('log_destination');
    }
    
    public function getActive(): bool {
        return $this->get('active');
    }

    public function getQueryParameters(): array {
        return $this->get('query_parameters', []);
    }
    
    public function getQueryFields(): array {
        return $this->get('query_fields', []);
    }
}