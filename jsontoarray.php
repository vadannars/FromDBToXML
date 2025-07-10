<?php
$path = __DIR__ . '/testresponse.json';

if (!file_exists($path)) {
    die("Filen hittades inte: $path");
}

$jsonresponse = file_get_contents($path);

if ($jsonresponse === false) {
    die("Kunde inte läsa filen.");
}

$responseArray = json_decode($jsonresponse,true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Fel i JSON: " . json_last_error_msg());
}

return $responseArray ?? null;

?>