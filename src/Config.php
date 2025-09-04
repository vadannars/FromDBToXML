<?php
declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

/**
 * Hanterar applikationens konfiguration genom att läsa miljövariabler från en .env-fil.
 *
 * Denna klass ger en strukturerad åtkomst till konfigurationsvärden och ser till att
 * de har rätt datatyp.
 */
class Config {
    /** @var array<string, mixed> */
    private array $data;

    /**
     * Skapar en ny instans av Config och laddar miljövariabler.
     *
     * @param string $envPath Sökvägen till mappen där .env-filen finns.
     */
    public function __construct(string $envPath) {
        $dotenv = Dotenv::createImmutable($envPath);
        $dotenv->safeLoad();
        
        $this->data = [
            'api_key'           => isset($_ENV['API_KEY']) ? (string) $_ENV['API_KEY'] : '',
            'api_secret'        => isset($_ENV['API_SECRET']) ? (string) $_ENV['API_SECRET'] : '',
            'allowed_origins'   => isset($_ENV['ALLOWED_ORIGINS']) ? (string) $_ENV['ALLOWED_ORIGINS'] : '',
            'api_base_url'      => isset($_ENV['API_BASE_URL']) ? (string) $_ENV['API_BASE_URL'] : '',
            'token_endpoint'    => isset($_ENV['TOKEN_ENDPOINT']) ? (string) $_ENV['TOKEN_ENDPOINT'] : '',
            'query_endpoint'    => isset($_ENV['QUERY_ENDPOINT']) ? (string) $_ENV['QUERY_ENDPOINT'] : '',
            'items_endpoint'    => isset($_ENV['ITEMS_ENDPOINT']) ? (string) $_ENV['ITEMS_ENDPOINT'] : '',
            'query_parameters' => [
                'offset'    => isset($_ENV['QUERY_OFFSET']) ? (int) $_ENV['QUERY_OFFSET'] : 0,
                'limit'     => isset($_ENV['QUERY_LIMIT']) ? (int) $_ENV['QUERY_LIMIT'] : 10
            ],
            'query_fields' => [
                'bib_id'    => $this->parseFieldString(isset($_ENV['QUERY_LIBRIS_ID']) ? (string) $_ENV['QUERY_LIBRIS_ID'] : null),
                'isbn'      => $this->parseFieldString(isset($_ENV['QUERY_ISBN']) ? (string) $_ENV['QUERY_ISBN'] : null),
                'issn'      => $this->parseFieldString(isset($_ENV['QUERY_ISSN']) ? (string) $_ENV['QUERY_ISSN'] : null),
                'onr'       => $this->parseFieldString(isset($_ENV['QUERY_ONR']) ? (string) $_ENV['QUERY_ONR'] : null)
            ],
            'item_fields'       => isset($_ENV['ITEM_FIELDS']) ? (string) $_ENV['ITEM_FIELDS'] : 'location,callNumber,status',
            'active'            => isset($_ENV['ACTIVE']) ? filter_var($_ENV['ACTIVE'], FILTER_VALIDATE_BOOL) : false,
            'log_level'         => isset($_ENV['LOG_LEVEL']) ? (string) $_ENV['LOG_LEVEL'] : 'debug',
            'log_destination'   => isset($_ENV['LOG_DESTINATION']) ? (string) $_ENV['LOG_DESTINATION'] : __DIR__ . '/../logs/app.log'
        ];
    }
    
    /**
     * Parsar en sträng från .env-filen (t.ex. "tag:j") till en array.
     *
     * @param string|null $fieldString Strängen som ska parsas.
     * @return array<string, string>|null En associativ array med 'type' och 'value', eller null om strängen är ogiltig.
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
            'type' => (string) $parts[0],
            'value' => (string) $parts[1]
        ];
    }
    
    /**
     * Hämtar ett konfigurationsvärde.
     *
     * @param string $key Nyckeln för konfigurationsvärdet.
     * @param mixed|null $default Standardvärde om nyckeln inte finns.
     * @return mixed Det hämtade värdet.
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    public function getAllowedOrigins(): string {
        return (string) $this->get('allowed_origins', '');
    }
    
    public function getApiBaseUrl(): string {
        return rtrim((string) $this->get('api_base_url'), '/');
    }
    public function getApiKey(): string {
        return (string) $this->get('api_key');
    }
    public function getApiSecret(): string {
        return (string) $this->get('api_secret');
    }
    public function getTokenEndpoint(): string {
        return (string) $this->get('token_endpoint');
    }
    public function getQueryEndpoint(): string {
        return (string) $this->get('query_endpoint');
    }
    public function getItemsEndpoint(): string {
        return (string) $this->get('items_endpoint');
    }
    public function getItemFields(): string {
        return (string) $this->get('item_fields');
    }
    public function getLogLevel(): string {
        return strtolower((string) $this->get('log_level', 'debug'));
    }
    public function getLogDestination(): string {
        return (string) $this->get('log_destination');
    }
    public function getActive(): bool {
        return (bool) $this->get('active');
    }
    
    /**
     * @return array<string, int>
     */
    public function getQueryParameters(): array {
        return (array) $this->get('query_parameters', []);
    }
    
    /**
     * @return array<string, array<string, string>|null>
     */
    public function getQueryFields(): array {
        return (array) $this->get('query_fields', []);
    }
}