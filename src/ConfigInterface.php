<?php

declare(strict_types=1);

namespace App;

/**
 * Gränssnitt för att hantera applikationens konfiguration.
 *
 * Definierar de metoder som måste implementeras av en konfigurationsklass
 * för att ge strukturerad och typ-säker åtkomst till konfigurationsvärden.
 */
interface ConfigInterface
{
    /**
     * Hämtar ett konfigurationsvärde.
     *
     * @param string $key Nyckeln för konfigurationsvärdet.
     * @param mixed|null $default Standardvärde om nyckeln inte finns.
     * @return mixed Det hämtade värdet.
     */
    public function get(string $key, mixed $default = null): mixed;

    public function getAllowedOrigins(): string;

    public function getApiBaseUrl(): string;

    public function getApiKey(): string;

    public function getApiSecret(): string;

    public function getTokenEndpoint(): string;

    public function getQueryEndpoint(): string;

    public function getItemsEndpoint(): string;

    public function getItemFields(): string;

    public function getLogLevel(): string;

    public function getLogDestination(): string;

    public function getActive(): bool;

    /**
     * @return array<string, int>
     */
    public function getQueryParameters(): array;

    /**
     * @return array<string, array<string, string>|null>
     */
    public function getQueryFields(): array;
}
