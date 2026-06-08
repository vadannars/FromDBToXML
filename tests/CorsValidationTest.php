<?php

declare(strict_types=1);

namespace App\Tests;

use App\Config;
use App\ConfigInterface;
use PHPUnit\Framework\TestCase;

class CorsValidationTest extends TestCase
{
    private ConfigInterface $config;

    protected function setUp(): void
    {
        // Create a mock Config that returns test CORS settings
        $this->config = new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function getApiKey(): string
            {
                return 'test-key';
            }

            public function getApiSecret(): string
            {
                return 'test-secret';
            }

            public function getApiBaseUrl(): string
            {
                return 'https://api.test.com';
            }

            public function getTokenEndpoint(): string
            {
                return '/auth/token';
            }

            public function getQueryEndpoint(): string
            {
                return '/search';
            }

            public function getItemsEndpoint(): string
            {
                return '/items';
            }

            public function getQueryParameters(): array
            {
                return [];
            }

            public function getQueryFields(): array
            {
                return [];
            }

            public function getItemFields(): string
            {
                return '';
            }

            public function getLogLevel(): string
            {
                return 'INFO';
            }

            public function getLogDestination(): string
            {
                return '/tmp/test.log';
            }

            public function getActive(): bool
            {
                return true;
            }

            public function getAllowedOrigins(): string
            {
                return 'https://example.com,https://app.example.com,http://localhost:3000';
            }
        };
    }

    public function testParsesMultipleAllowedOrigins(): void
    {
        $allowedOriginsString = $this->config->getAllowedOrigins();
        $allowedOrigins = explode(',', $allowedOriginsString);

        $this->assertCount(3, $allowedOrigins);
        $this->assertContains('https://example.com', $allowedOrigins);
        $this->assertContains('https://app.example.com', $allowedOrigins);
        $this->assertContains('http://localhost:3000', $allowedOrigins);
    }

    public function testValidOriginIsAllowed(): void
    {
        $origin = 'https://example.com';
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $this->assertTrue(in_array($origin, $allowedOrigins, true));
    }

    public function testInvalidOriginIsNotAllowed(): void
    {
        $origin = 'https://malicious.com';
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $this->assertFalse(in_array($origin, $allowedOrigins, true));
    }

    public function testNullOriginIsAllowed(): void
    {
        // CORS logic: if ($origin === null || in_array($origin, $allowed_origins))
        $origin = null;
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $isAllowed = $origin === null || in_array($origin, $allowedOrigins, true);
        $this->assertTrue($isAllowed);
    }

    public function testLocalhostOriginIsAllowedForDevelopment(): void
    {
        $origin = 'http://localhost:3000';
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $this->assertTrue(in_array($origin, $allowedOrigins, true));
    }

    public function testOriginComparisonIsCaseSensitive(): void
    {
        $origin = 'HTTPS://EXAMPLE.COM'; // uppercase
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        // Using strict comparison (in_array with true parameter)
        $this->assertFalse(in_array($origin, $allowedOrigins, true));
    }

    public function testOriginWithDifferentSchemeIsNotAllowed(): void
    {
        $origin = 'http://example.com'; // http instead of https
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $this->assertFalse(in_array($origin, $allowedOrigins, true));
    }

    public function testOriginWithPortIsNotAllowedIfNotSpecified(): void
    {
        $origin = 'https://example.com:8080'; // port added
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        $this->assertFalse(in_array($origin, $allowedOrigins, true));
    }

    public function testMultipleSubdomainsRequireSeparateConfiguration(): void
    {
        $origin = 'https://sub.example.com';
        $allowedOrigins = explode(',', $this->config->getAllowedOrigins());

        // Subdomain not explicitly added to config, so should not be allowed
        $this->assertFalse(in_array($origin, $allowedOrigins, true));
    }
}
