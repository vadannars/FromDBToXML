<?php

/*

Adress till bibliotekets api:
gotlib.goteborg.se/iii/sierra-api/

CLEVER CLOUD:
Adress: https://app-881532c0-e6ee-4503-8aae-0116c1a5d144.cleverapps.io/?Bib_ID=9v8xbqxk785qpxhh&isbn=9789177754657

FÖR ATT TESTKÖRA:
i terminalen:
 $ php -S 0.0.0.0:8080 -t public

=== FORTSÄTT MED ===
    * Ta reda på, och justera om det jag har nu kommer att fungera när det anropas som libris vill göra - CHECK
    * Rensa loggutskrifter och annat wip-grejs //TODO
    * Lägg in läsbara felmeddelanden för kommande generationer //TODO
    * Snygga till kod och kommentarer //TODO
    * Strukturera om koden så den är mer robust och modulär //TODO
    * Ta reda på vad som behövs för att lägga upp den i webmaster
        //TODO
        - MÅSTE HA EN SERVER ATT LAGRA KODEN PÅ
        - Kolla med Valle eller någon annan om hur vi ordnar det. MAIL SKICKAT TILL VALLE
        - Ordna så att koden är redo för att läggas i en server och justerad för den scopen
        - Skriv JS-fil som ska läggas i live web server och koppla till php-koden
        - Testa att lägga php på en gratis serverlösning medans vi väntar på den riktiga, testa både via en js-lösning i codespaces och via live web server.
    * Skriv en teknisk beskrivning av utvecklingen så att kommande justeringar blir lätta att göra

Webbläsarsträng: https://turbo-goggles-7qq6475rg6p2x7x6-8080.app.github.dev/loanstatus.php?Bib_ID=9v8xbqxk785qpxhh&isbn=9789177754657
*/
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config;
use App\SierraApiClient;
use App\GuzzleHttpClient;
use App\XmlGenerator;
use App\LoggerFactory;
use Monolog\Logger;

try {
    $config = new Config(__DIR__ . '/..');

    if (!$config->getActive()) {
        throw new \Exception("Applikationen är inaktiverad.");
    }

    if (empty($config->getApiKey()) ||
        empty($config->getApiSecret()) ||
        empty($config->getApiBaseUrl())){
            throw new \Exception("Viktiga API-nycklar saknas. Kontrollera konfigurationen.");
    }

    $allowed_origins_string = $config->getAllowedOrigins();
    $allowed_origins = explode(',', $allowed_origins_string);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    } else {
        throw new \Exception("Origin not allowed.");
    }

    $logger = LoggerFactory::createLogger($config);
    $normalizedGet = array_change_key_case($_GET, CASE_LOWER);

    $identifiers = [
        'bib_id' => $normalizedGet['bib_id'] ?? null,
        'isbn'   => $normalizedGet['isbn'] ?? null,
        'issn'   => $normalizedGet['issn'] ?? null,
        'onr'    => $normalizedGet['onr'] ?? null
    ];

    if (empty(array_filter($identifiers))) {
        throw new \InvalidArgumentException("Ingen parameter angiven i anropet.");
    }

    $logger->info('API-anrop mottaget', ['params' => $identifiers]);

    $httpClient = new GuzzleHttpClient();
    $apiClient = new SierraApiClient(
        $config->getApiBaseUrl(),
        $config->getApiKey(),
        $config->getApiSecret(),
        $httpClient);
    
    $queryParams = $config->get('query_parameters');
    $limit = $queryParams['limit'];
    $offset = $queryParams['offset'];

    $bibIds = $apiClient->queryBibs($identifiers, $limit, $offset);
    if (empty($bibIds)) {
        throw new \RuntimeException("Inga media hittades för de angivna parametrarna.");
    }

    $allItems = [];
    foreach ($bibIds as $bibId) {
        $items = $apiClient->fetchItems($bibId);
        if ($items !== null) {
            $allItems = array_merge($allItems, $items);
        }
    }

    if (empty($allItems)) {
        throw new \RuntimeException("Inga exemplar hittades för mediet.");
    }

    $xml = XmlGenerator::generateXmlFromItems($allItems);

    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    exit;

} catch (\Exception $e) {
    if (isset($logger)) {
        $logger->error('Fel i loanstatus.php', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'Ett internt serverfel inträffade. Vänligen kontrollera serverloggarna för mer information.']);
    exit;
}


