<?php

declare(strict_types=1);

namespace App\Tests;

use App\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        // Create a temporary directory for test configs
        $this->testDir = sys_get_temp_dir() . '/config-test-' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir . '/*'));
            rmdir($this->testDir);
        }
    }

    public function testGetReturnsConfigValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_KEY'] = 'test-key-123';

        $config = new Config($this->testDir);

        $this->assertSame('test-key-123', $config->get('api_key'));
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $_SERVER['APP_ENV'] = 'local';

        $config = new Config($this->testDir);

        $this->assertNull($config->get('nonexistent_key'));
        $this->assertSame('default-value', $config->get('nonexistent_key', 'default-value'));
    }

    public function testGetApiKeyReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_KEY'] = 'my-secret-key';

        $config = new Config($this->testDir);

        $this->assertIsString($config->getApiKey());
        $this->assertSame('my-secret-key', $config->getApiKey());
    }

    public function testGetApiKeyReturnsEmptyStringWhenMissing(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['API_KEY']);

        $config = new Config($this->testDir);

        $this->assertSame('', $config->getApiKey());
    }

    public function testGetApiSecretReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_SECRET'] = 'my-secret-value';

        $config = new Config($this->testDir);

        $this->assertSame('my-secret-value', $config->getApiSecret());
    }

    public function testGetApiBaseUrlRemovesTrailingSlash(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_BASE_URL'] = 'https://api.example.com/';

        $config = new Config($this->testDir);

        $this->assertSame('https://api.example.com', $config->getApiBaseUrl());
    }

    public function testGetApiBaseUrlKeepsUrlWithoutTrailingSlash(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_BASE_URL'] = 'https://api.example.com';

        $config = new Config($this->testDir);

        $this->assertSame('https://api.example.com', $config->getApiBaseUrl());
    }

    public function testGetTokenEndpointReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['TOKEN_ENDPOINT'] = '/v1/auth/token';

        $config = new Config($this->testDir);

        $this->assertSame('/v1/auth/token', $config->getTokenEndpoint());
    }

    public function testGetQueryEndpointReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_ENDPOINT'] = '/v1/search';

        $config = new Config($this->testDir);

        $this->assertSame('/v1/search', $config->getQueryEndpoint());
    }

    public function testGetItemsEndpointReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['ITEMS_ENDPOINT'] = '/v1/items';

        $config = new Config($this->testDir);

        $this->assertSame('/v1/items', $config->getItemsEndpoint());
    }

    public function testGetAllowedOriginsReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['ALLOWED_ORIGINS'] = 'https://example.com,https://app.example.com';

        $config = new Config($this->testDir);

        $this->assertSame('https://example.com,https://app.example.com', $config->getAllowedOrigins());
    }

    public function testGetAllowedOriginsReturnsEmptyStringWhenMissing(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['ALLOWED_ORIGINS']);

        $config = new Config($this->testDir);

        $this->assertSame('', $config->getAllowedOrigins());
    }

    public function testGetLogLevelConvertsToLowercase(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['LOG_LEVEL'] = 'DEBUG';

        $config = new Config($this->testDir);

        $this->assertSame('debug', $config->getLogLevel());
    }

    public function testGetLogLevelReturnsDebugAsDefault(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['LOG_LEVEL']);

        $config = new Config($this->testDir);

        $this->assertSame('debug', $config->getLogLevel());
    }

    public function testGetLogDestinationReturnsProvidedPath(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['LOG_DESTINATION'] = '/var/log/app.log';

        $config = new Config($this->testDir);

        $this->assertSame('/var/log/app.log', $config->getLogDestination());
    }

    public function testGetLogDestinationReturnsFallbackWhenMissing(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['LOG_DESTINATION']);

        $config = new Config($this->testDir);

        $logDest = $config->getLogDestination();
        $this->assertSame('php://stderr', $logDest);
    }

    public function testGetLogDestinationReturnsFallbackWhenEmpty(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['LOG_DESTINATION'] = '   ';

        $config = new Config($this->testDir);

        $logDest = $config->getLogDestination();
        $this->assertStringEndsWith('/logs/app.log', $logDest);
    }

    public function testGetActiveReturnsBooleanTrue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['ACTIVE'] = 'true';

        $config = new Config($this->testDir);

        $this->assertTrue($config->getActive());
    }

    public function testGetActiveReturnsBooleanFalse(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['ACTIVE'] = 'false';

        $config = new Config($this->testDir);

        $this->assertFalse($config->getActive());
    }

    public function testGetActiveReturnsFalseByDefault(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['ACTIVE']);

        $config = new Config($this->testDir);

        $this->assertFalse($config->getActive());
    }

    public function testGetQueryParametersReturnsArray(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_OFFSET'] = '0';
        $_SERVER['QUERY_LIMIT'] = '10';

        $config = new Config($this->testDir);

        $params = $config->getQueryParameters();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('offset', $params);
        $this->assertArrayHasKey('limit', $params);
        $this->assertSame(0, $params['offset']);
        $this->assertSame(10, $params['limit']);
    }

    public function testGetQueryParametersReturnsDefaults(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['QUERY_OFFSET']);
        unset($_SERVER['QUERY_LIMIT']);

        $config = new Config($this->testDir);

        $params = $config->getQueryParameters();
        $this->assertSame(0, $params['offset']);
        $this->assertSame(10, $params['limit']);
    }

    public function testGetItemFieldsReturnsStringValue(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['ITEM_FIELDS'] = 'location,callNumber,status';

        $config = new Config($this->testDir);

        $this->assertSame('location,callNumber,status', $config->getItemFields());
    }

    public function testGetItemFieldsReturnsDefaultWhenMissing(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['ITEM_FIELDS']);

        $config = new Config($this->testDir);

        $this->assertSame('location,callNumber,status', $config->getItemFields());
    }

    public function testGetQueryFieldsReturnsArray(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_LIBRIS_ID'] = 'tag:j';
        $_SERVER['QUERY_ISBN'] = 'marcTag:022';
        $_SERVER['QUERY_ISSN'] = 'marcTag:022';
        $_SERVER['QUERY_ONR'] = 'tag:onr';

        $config = new Config($this->testDir);

        $fields = $config->getQueryFields();
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('bib_id', $fields);
        $this->assertArrayHasKey('isbn', $fields);
        $this->assertArrayHasKey('issn', $fields);
        $this->assertArrayHasKey('onr', $fields);
    }

    public function testGetQueryFieldsParseFieldStrings(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_LIBRIS_ID'] = 'tag:j';

        $config = new Config($this->testDir);

        $fields = $config->getQueryFields();
        $this->assertIsArray($fields['bib_id']);
        $this->assertSame('tag', $fields['bib_id']['type']);
        $this->assertSame('j', $fields['bib_id']['value']);
    }

    public function testGetQueryFieldsReturnsNullForInvalidFieldString(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_LIBRIS_ID'] = 'invalid';

        $config = new Config($this->testDir);

        $fields = $config->getQueryFields();
        $this->assertNull($fields['bib_id']);
    }

    public function testGetQueryFieldsReturnsNullForMissingField(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        unset($_SERVER['QUERY_LIBRIS_ID']);

        $config = new Config($this->testDir);

        $fields = $config->getQueryFields();
        $this->assertNull($fields['bib_id']);
    }

    public function testEnvironmentVariablePrecedenceOverDefault(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['API_KEY'] = 'env-override-key';

        $config = new Config($this->testDir);

        $this->assertSame('env-override-key', $config->getApiKey());
    }

    public function testConfigHandlesNumericStrings(): void
    {
        $_SERVER['APP_ENV'] = 'local';
        $_SERVER['QUERY_OFFSET'] = '42';
        $_SERVER['QUERY_LIMIT'] = '100';

        $config = new Config($this->testDir);

        $params = $config->getQueryParameters();
        $this->assertSame(42, $params['offset']);
        $this->assertSame(100, $params['limit']);
    }
}
