<?php
namespace App;

class Config {
    private array $data;

    public function __construct(string $filePath) {
        $json = file_get_contents($filePath);
        if ($json === false) {
            throw new \RuntimeException("Kunde inte läsa konfigurationsfilen.");
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON-fel i konfigurationsfilen: " . json_last_error_msg());
        }

        if (!($data['active'] ?? false)) {
            throw new \RuntimeException("Applikationen är inaktiverad i konfigurationen.");
        }

        $this->data = $data;
    }

    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
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


}
