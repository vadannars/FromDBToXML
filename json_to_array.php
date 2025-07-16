<?php

function jsonToArray(string $json): array {
    $array = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }

    return $array;
}
