<?php

declare(strict_types=1);

namespace App;

class RequestValidator
{
    /**
     * Extracts and validates the CORS origin from server variables.
     * Returns the origin as a string, or null if not present or empty.
     *
     * @param array<string, mixed> $server
     */
    public static function getOrigin(array $server): ?string
    {
        $origin = $server['HTTP_ORIGIN'] ?? null;

        if (!is_string($origin) || $origin === '') {
            return null;
        }

        return $origin;
    }

    /**
     * Extracts and validates identifier parameters from GET request.
     * Returns an array with string|null values for each identifier type.
     *
     * @param array<string, mixed> $get
     * @return array<string, string|null>
     */
    public static function getIdentifiers(array $get): array
    {
        $normalizedGet = array_change_key_case($get, CASE_LOWER);

        return [
            'bib_id' => self::extractStringOrNull($normalizedGet, 'bib_id'),
            'isbn'   => self::extractStringOrNull($normalizedGet, 'isbn'),
            'issn'   => self::extractStringOrNull($normalizedGet, 'issn'),
            'onr'    => self::extractStringOrNull($normalizedGet, 'onr')
        ];
    }

    /**
     * Extracts a value from an array and casts it to string or null.
     *
     * @param array<string, mixed> $array
     */
    private static function extractStringOrNull(array $array, string $key): ?string
    {
        $value = $array[$key] ?? null;

        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
