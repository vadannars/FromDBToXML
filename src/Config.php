<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use App\LoggerFactory;

/**
 * Hanterar applikationens konfiguration genom att läsa miljövariabler från en .env-fil.
 *
 * Denna klass ger en strukturerad åtkomst till konfigurationsvärden och ser till att
 * de har rätt datatyp.
 */
class Config implements ConfigInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Skapar en ny instans av Config och laddar miljövariabler.
     *
     * @param string $envPath Sökvägen till mappen där .env-filen finns.
     */
    public function __construct(string $envPath)
    {
        $appEnv = $_SERVER['APP_ENV'] ?? 'local';
        $env = [];

        if ($appEnv === 'local' || $appEnv === 'development') {
            try {
                $dotenv = Dotenv::createImmutable($envPath);
                $env = $dotenv->safeLoad();
            } catch (\Exception $e) {
                error_log("Varning: .env-filen hittades inte lokalt. " . $e->getMessage());
            }
        }

        $env = array_merge($env, $_ENV, $_SERVER);

        /**
         * @var array<string, mixed> $env
         */
        $env = $env; // Denna rad behövs tekniskt inte men gör DocBlock-tilldelningen tydlig.

        $this->data = [
            'api_key' => $this->getStringValue($env, 'API_KEY'),
            'api_secret' => $this->getStringValue($env, 'API_SECRET'),
            'allowed_origins' => $this->getStringValue($env, 'ALLOWED_ORIGINS'),
            'api_base_url' => $this->getStringValue($env, 'API_BASE_URL'),
            'token_endpoint' => $this->getStringValue($env, 'TOKEN_ENDPOINT'),
            'query_endpoint' => $this->getStringValue($env, 'QUERY_ENDPOINT'),
            'items_endpoint' => $this->getStringValue($env, 'ITEMS_ENDPOINT'),

            'query_parameters' => [
                'offset' => $this->getIntValue($env, 'QUERY_OFFSET', 0),
                'limit' => $this->getIntValue($env, 'QUERY_LIMIT', 10)
            ],

            'query_fields' => [
                'bib_id' => $this->parseFieldString($this->getStringValue($env, 'QUERY_LIBRIS_ID', null)),
                'isbn' => $this->parseFieldString($this->getStringValue($env, 'QUERY_ISBN', null)),
                'issn' => $this->parseFieldString($this->getStringValue($env, 'QUERY_ISSN', null)),
                'onr' => $this->parseFieldString($this->getStringValue($env, 'QUERY_ONR', null))
            ],

            'item_fields' => $this->getStringValue($env, 'ITEM_FIELDS', 'location,callNumber,status'),

            'active' => filter_var($env['ACTIVE'] ?? false, FILTER_VALIDATE_BOOL),

            'log_level' => $this->getStringValue($env, 'LOG_LEVEL', 'debug'),
            'log_destination' => $this->getStringValue($env, 'LOG_DESTINATION', 'php://stderr')
        ];
    }

    /**
     * Parsar en sträng från .env-filen (t.ex. "tag:j") till en array.
     *
     * @param  string|null $fieldString Strängen som ska parsas.
     * @return array<string, string>|null En associativ array med 'type' och 'value', eller null om strängen är ogiltig.
     */
    private function parseFieldString(?string $fieldString): ?array
    {
        if (empty($fieldString)) {
            return null;
        }
        $parts = explode(':', $fieldString, 2);
        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            return null;
        }
        return [
            'type' => $parts[0],
            'value' => $parts[1]
        ];
    }
    /**
     * Hämtar en sträng från env-källan, garanterar att den är en sträng (eller null).
     * @param array<string, mixed> $env
     */
    private function getStringValue(array $env, string $key, ?string $default = ''): ?string
    {
        $value = $env[$key] ?? $default;

        if (is_string($value) || $value === null) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return $default;
    }

    /**
     * Hämtar ett heltal från env-källan, garanterar att det är ett heltal.
     * @param array<string, mixed> $env
     */
    private function getIntValue(array $env, string $key, int $default = 0): int
    {
        $value = $env[$key] ?? $default;

        if (is_scalar($value)) {
            return (int) \intval($value);
        }

        return $default;
    }

    /**
     * Hämtar ett konfigurationsvärde.
     *
     * @param  string     $key     Nyckeln
     *                             för
     *                             konfigurationsvärdet.
     * @param  mixed|null $default Standardvärde om nyckeln inte finns.
     * @return mixed Det hämtade värdet.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function getAllowedOrigins(): string
    {
        $value = $this->get('allowed_origins', '');
        return is_string($value) ? $value : '';
    }

    public function getApiBaseUrl(): string
    {
        $value = $this->get('api_base_url');
        return is_string($value) ? rtrim($value, '/') : '';
    }

    public function getApiKey(): string
    {
        $value = $this->get('api_key');
        return is_string($value) ? $value : '';
    }

    public function getApiSecret(): string
    {
        $value = $this->get('api_secret');
        return is_string($value) ? $value : '';
    }

    public function getTokenEndpoint(): string
    {
        $value = $this->get('token_endpoint');
        return is_string($value) ? $value : '';
    }

    public function getQueryEndpoint(): string
    {
        $value = $this->get('query_endpoint');
        return is_string($value) ? $value : '';
    }

    public function getItemsEndpoint(): string
    {
        $value = $this->get('items_endpoint');
        return is_string($value) ? $value : '';
    }

    public function getItemFields(): string
    {
        $value = $this->get('item_fields');
        return is_string($value) ? $value : '';
    }

    public function getLogLevel(): string
    {
        $value = $this->get('log_level', 'debug');
        return is_string($value) ? strtolower($value) : 'debug';
    }

    public function getLogDestination(): string
    {
        $value = $this->get('log_destination');
        if (is_string($value) && !empty(trim($value))) {
            return $value;
        }
        return __DIR__ . '/../logs/app.log';
    }

    public function getActive(): bool
    {
        $value = $this->get('active');
        return is_bool($value) ? $value : false;
    }

    /**
     * @return array<string, int>
     */
    public function getQueryParameters(): array
    {
        /**
 * @var mixed $params
*/
        $params = $this->get('query_parameters', []);

        $sanitized = [];
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (is_string($key) && is_int($value)) {
                    $sanitized[$key] = $value;
                }
            }
        }
        return $sanitized;
    }

    /**
     * @return array<string, array<string, string>|null>
     */
    public function getQueryFields(): array
    {
        /**
        * @var mixed $fields
        */
        $fields = $this->get('query_fields', []);

        $sanitized = [];
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                if (is_string($key)) {
                    if (
                        is_array($value) &&
                        isset($value['type'], $value['value']) &&
                        is_string($value['type']) &&
                        is_string($value['value'])
                    ) {
                        $sanitized[$key] = ['type' => $value['type'], 'value' => $value['value']];
                    } elseif ($value === null) {
                        $sanitized[$key] = null;
                    }
                }
            }
        }
        return $sanitized;
    }
}
