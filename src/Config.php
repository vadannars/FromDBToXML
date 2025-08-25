<?php
namespace App;

use Dotenv\Dotenv;

use function GuzzleHttp\debug_resource;

class Config {
    private array $data;

    public function __construct($envPath) {        
        $dotenv = Dotenv::createImmutable($envPath);
        $dotenv->safeLoad();

        foreach ($_ENV as $key => $value) {
            if (!empty($key)) {
                putenv("{$key}={$value}");
            }
        }
        
        $this->data = [
            'api_key'           => getenv('API_KEY') ?? '',
            'api_secret'        => getenv('API_SECRET') ?? '',
            'allowed_origins'   => getenv('ALLOWED_ORIGINS') ?? '',
            'api_base_url'      => getenv('API_BASE_URL') ?? '',
            'token_endpoint'    => getenv('TOKEN_ENDPOINT') ?? '',
            'query_endpoint'    => getenv('QUERY_ENDPOINT') ?? '',
            'bibs_endpoint'     => getenv('BIBS_ENDPOINT') ?? '',
            'items_endpoint'    => getenv('ITEMS_ENDPOINT') ?? '',
            'query_parameters' => [
                'offset' => (int)(getenv('QUERY_OFFSET') ?? 0),
                'limit' => (int)(getenv('QUERY_LIMIT') ?? 10)],
            'active'            => filter_var(getenv('ACTIVE') ?? false, FILTER_VALIDATE_BOOL),
            'log_level'         => getenv('LOG_LEVEL') ?? 'debug',
            'log_destination'   => getenv('LOG_DESTINATION') ?? __DIR__ . '/../logs/app.log'
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
