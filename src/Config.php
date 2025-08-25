<?php
namespace App;

use Dotenv\Dotenv;

use function GuzzleHttp\debug_resource;

class Config {
    private array $data;

    public function __construct() {        
        $this->data = [
            'api_key'           => $_ENV['API_KEY'] ?? '',
            'api_secret'        => $_ENV['API_SECRET'] ?? '',
            'allowed_origins'   => $_ENV['ALLOWED_ORIGINS'] ?? '',
            'api_base_url'      => $_ENV['API_BASE_URL'] ?? '',
            'token_endpoint'    => $_ENV['TOKEN_ENDPOINT'] ?? '',
            'query_endpoint'    => $_ENV['QUERY_ENDPOINT'] ?? '',
            'bibs_endpoint'     => $_ENV['BIBS_ENDPOINT'] ?? '',
            'items_endpoint'    => $_ENV['ITEMS_ENDPOINT'] ?? '',
            'query_parameters' => [
                'offset' => (int)($_ENV['QUERY_OFFSET'] ?? 0),
                'limit' => (int)($_ENV['QUERY_LIMIT'] ?? 10)],
            'active'            => filter_var($_ENV['ACTIVE'] ?? false, FILTER_VALIDATE_BOOL),
            'log_level'         => $_ENV['LOG_LEVEL'] ?? 'debug',
            'log_destination'   => $_ENV['LOG_DESTINATION'] ?? __DIR__ . '/../logs/app.log'
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

    public function getLogLevel(): string {
        return strtolower($this->get('log_level', 'debug'));
    }

    public function getLogDestination(): string {
        return strtolower($this->get('log_destination'));
    }

    public function getActive(): bool {
        return $this->get('active');
    }
}
