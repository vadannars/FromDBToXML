<?php
/*
* Detta är en ingångsfil för Libris. 
* Den tar emot en identifierare (som en ISBN, ISSN eller Libris ID) via URL-parametrar,
* söker i bibliotekets API efter relaterade media och genererar en XML-fil med status för varje exemplar.
*/
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\ConfigInterface;
use App\SierraApiClient;
use App\SierraApiClientInterface;
use App\GuzzleHttpClient;
use App\XmlGenerator;
use App\LoggerFactory;
use App\LoanStatusController;
use Psr\Log\LoggerInterface;

/** @var ConfigInterface $config */
/** @var LoggerInterface $logger */
$logger = null;

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

    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $allowedOrigins = explode(',', $config->getAllowedOrigins());

    if ($origin === null || in_array($origin, $allowed_origins)) {
        if ($origin !== null) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
        }
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

    $httpClient = new GuzzleHttpClient();
    $apiClient = new SierraApiClient($config, $httpClient, $logger);

    $xmlGenerator = new XmlGenerator($logger);

    $controller = new LoanStatusController($apiClient, $xmlGenerator, $logger);
    
    $xml = $controller->handleRequest($identifiers);

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
    echo json_encode(['error' => 'Ett internt serverfel inträffade. Vänligen kontrollera serverloggarna för mer information.'], JSON_UNESCAPED_UNICODE);
    exit;
}


