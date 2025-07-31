<?php

/*

marc: https://www.oclc.org/bibformats/en/0xx.html
bibframe: https://www.loc.gov/bibframe/

Adress till bibliotekets api:
gotlib.goteborg.se/iii/sierra-api/

FÖR ATT TESTKÖRA:
i terminalen:
apache2ctl start

PROJEKTETS DELMÅL:

* Skapa testmiljö i codespaces - KLART
* Skapa XML - KLART
* Hur loopa igenom apisvar?
	Verkar ej behövas. decodeJson skapar en array direkt. Ska loopa igenom arrayen enligt nedan.
* Hur loopa skapandet av xml?
	// Sample JSON string
	$json = '{"name": "John", "age": 30, "city": "Göteborg"}';

	// Decode JSON into an associative array
	$array = json_decode($json, true);

	// Function to convert array to XML
	function arrayToXml($data, &$xmlData) {
		foreach ($data as $key => $value) {
			// Handle numeric keys
			if (is_numeric($key)) {
				$key = "item$key";
			}
			if (is_array($value)) {
				$subnode = $xmlData->addChild($key);
				arrayToXml($value, $subnode);
			} else {
				$xmlData->addChild($key, htmlspecialchars($value));
			}
		}
	}

	// Create a new XML object
	$xmlData = new SimpleXMLElement('<?xml version="1.0"><root></root>');

	// Convert array to XML
	arrayToXml($array, $xmlData);

	// Output XML
	echo $xmlData->asXML();
* Hur ta parametrar?
	// URL: http://example.com?name=Alice
	$name = $_GET['name'] ?? 'Guest'; // Default to 'Guest' if 'name' is not set
	echo "Hello, " . htmlspecialchars($name) . "!"; // Output: Hello, Alice!

* Hur kontakta API?
	* Kodmässigt php - PÅ GOD VÄG
		
		require 'vendor/autoload.php';

		use GuzzleHttp\Client;

		$client = new Client();
		$response = $client->request('GET', 'https://api.example.com/data', [
			'query' => ['param1' => 'value1', 'param2' => 'value2'] -> FORTSÄTT MED PARAMETRAR
		]);

		$data = json_decode($response->getBody(), true); // Decode JSON response
		print_r($data);

	* Vilken adress till faktiska bibliotekets api-ingång?


API:er att testa emot:
https://petstore.swagger.io/
https://gorest.co.in/
*/

// === CHATGTPs LÖSNING ===
//
// FORTSÄTT MED: Se till att svaren hanteras på rätt sätt för att  hitta items och skapa xml från dem. Kan vara så att vi letar med rätt siffra men fortfarande inte hittar poster med items. fortsätt jämföra med api:et.
// ATT GÖRA: Rätta queryn. Blir mycket riktigt fel svar. Får de 10 första posterna från offset 0, och får andra poster om jag ändrar offset. Har skickat fråga till Ines,
// och hon skickar troligen vidare till supporten.
// Får samma 10 svar som jag fick med sökning på annat bibID och ISBN.
// Webbläsarsträng: https://turbo-goggles-7qq6475rg6p2x7x6-8080.app.github.dev/?Bib_ID=9v8xbqxk785qpxhh&isbn=9789177754657
// Felmeddelande som ges: Array ( [Bib_ID] => 9v8xbqxk785qpxhh [isbn] => 9789177754657 ) SÄNDER HEADER: Content-Type: application/x-www-form-urlencoded SÄNDER HEADER: Accept: application/json SÄNDER HEADER: Authorization: Basic bnhwNHlIUE00Nm4vMTVVdDdkdjR6WTFRVVRrcDpJdm9yeUxlbW9uIzE2OQ== === AUTH RESPONSE === {"access_token":"0fo_BZlT9sDa56SMtEmsWqvjo1so4naMP8c6RQsLTSPtoeR8JFIxaSS_9lyQzzakQ_ZNKdXR8tLa2pVxQ2pzA55PVjqmYkVeyCEnNT-GFfQBvQkCbf29ZpUiax23Ecfa","token_type":"bearer","expires_in":3600} === SÖKER MED bib_id === {"target":{"record":{"type":"bib"}},"expr":{"op":"equals","args":[{"marcTag":"029","subfield":"a"},"9v8xbqxk785qpxhh"]}} === TOKEN I getSierraBibIdsFromIdentifiers === 0fo_BZlT9sDa56SMtEmsWqvjo1so4naMP8c6RQsLTSPtoeR8JFIxaSS_9lyQzzakQ_ZNKdXR8tLa2pVxQ2pzA55PVjqmYkVeyCEnNT-GFfQBvQkCbf29ZpUiax23Ecfa === queryURL === https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/query?limit=10&offset=0 SÄNDER HEADER: Authorization: Bearer 0fo_BZlT9sDa56SMtEmsWqvjo1so4naMP8c6RQsLTSPtoeR8JFIxaSS_9lyQzzakQ_ZNKdXR8tLa2pVxQ2pzA55PVjqmYkVeyCEnNT-GFfQBvQkCbf29ZpUiax23Ecfa SÄNDER HEADER: Content-Type: application/json SÄNDER HEADER: Accept: application/json == RESPONSE FOR bib_id == {"total":10,"start":0,"entries":[{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000025"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000049"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000073"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000110"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000131"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000139"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000213"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000268"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000274"},{"link":"https://gotlib.goteborg.se/iii/sierra-api/v6/bibs/1000302"}]} Hämtar items för Sierra Bib_ID: 1000025 SÄNDER HEADER: Authorization: Bearer 0fo_BZlT9sDa56SMtEmsWqvjo1so4naMP8c6RQsLTSPtoeR8JFIxaSS_9lyQzzakQ_ZNKdXR8tLa2pVxQ2pzA55PVjqmYkVeyCEnNT-GFfQBvQkCbf29ZpUiax23Ecfa SÄNDER HEADER: Accept: application/json Fel: JSON saknar 'entries' eller är inte en array.

// === KONFIGURATION ===
$configPath = __DIR__ . '/config/config.json';
$config = json_decode(file_get_contents($configPath),true);
if (!$config) {
    die("Misslyckades med att läsa konfigurationsfilen.");
}

if ($config['active'] != true) {
    die("Active satt till false i konfigurationsfilen");
}

require_once __DIR__ . '/json_to_array.php';
require_once __DIR__ . '/generatexmlfromitems.php';

$baseUrl = rtrim($config['api_base_url'], '/');
$tokenEndpoint = rtrim($config['token_endpoint'], '/');
$queryEndpoint = rtrim($config['query_endpoint'], '/');
$bibsEndpoint = rtrim($config['bibs_endpoint'], '/');
$itemsEndpoint = rtrim($config['items_endpoint'], '/');
$offset = isset($config['query_parameters']['offset']) ? (int)$config['query_parameters']['offset'] : 0;
$limit  = isset($config['query_parameters']['limit']) ? (int)$config['query_parameters']['limit'] : 10;

$dataUrl = 'https://gotlib.goteborg.se/iii/sierra-api/v6/items';

$normalizedGet = array_change_key_case($_GET, CASE_LOWER);

print_r($_GET);

$identifiers = [
    'bib_id' => $normalizedGet['bib_id'] ?? null,
    'isbn'   => $normalizedGet['isbn'] ?? null,
    'onr'    => $normalizedGet['onr'] ?? null
];



if (!$identifiers) {
    die("Ingen parameter angiven i anropet.");
}


// Autentiseringsuppgifter
$clientKey = $config['api_key'];
$clientSecret = $config['api_secret'];


// === GENERELL FUNKTION FÖR HTTP-ANROP ===
function makeHttpRequest(string $url, string $method = 'GET', array $headers = [], $body = null): array {
    $ch = curl_init();

    // Om det finns parametrar som ska med i GET, hantera dem utanför denna funktion (så att $url är komplett)
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    // Skicka kropp bara om det är POST, PUT, PATCH etc. och $body inte är null
    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && $body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    // Sätt headers
    if (!empty($headers)) {
        // cURL vill ha headers som en array av "Header: value"-strängar
        $formattedHeaders = [];
        foreach ($headers as $key => $value) {
            echo "SÄNDER HEADER: $key: $value\n";
            $formattedHeaders[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
    }

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $statusCode,
        'response' => $response,
        'error' => $error
    ];
}



// === POST-FUNKTION: HÄMTA TOKEN ===
function authenticateAndGetToken($authUrl, $clientKey, $clientSecret): ?string {
    $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json'
    ];

    $body = "grant_type=client_credentials";

    $authHeader = base64_encode("$clientKey:$clientSecret");
    $headers['Authorization'] = 'Basic ' . $authHeader;

    $result = makeHttpRequest($authUrl, 'POST', $headers, $body);

    echo "=== AUTH RESPONSE ===\n" . $result['response'] . "\n";

    if ($result['status'] !== 200) {
        echo "Autentisering misslyckades. Status: {$result['status']}\n";
        echo "Fel: {$result['error']}\n";
        return null;
    }

    $data = json_decode($result['response'], true);
    return $data['access_token'] ?? null;
}


// === GET-FUNKTION: HÄMTA DATA ===
function fetchDataWithToken($dataUrl, $token, array $params = []): ?string {
    if (!empty($params)) {
		$queryString = http_build_query($params);
		$dataUrl .= (strpos($dataUrl, '?') === false ? '?' : '&') . $queryString;
	}

    echo "=== SENT QUERY STRING ===\n";
    echo $dataUrl . "\n";

	$headers = [
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json'
    ];
    echo "=== TOKEN USED ===\n";
    echo $token . "\n";

    $result = makeHttpRequest($dataUrl, 'GET', $headers);

    echo "=== DATA RESPONSE ===\n";
    echo $result['response'] . "\n";

    if ($result['status'] !== 200) {
        echo "Datahämtning misslyckades. Status: {$result['status']}\n";
        echo "Fel: {$result['error']}\n";
        return null;
    }
    return $result['response'];
    // Vill du göra något mer med datan? Lägg till det här!
}

function getSierraBibIdsFromIdentifiers(string $queryUrl, int $limit, int $offset, array $identifiers, string $token): ?array {
    $queryUrlWithParams = $queryUrl . '?limit=' . $limit . '&offset=' . $offset;

    $query = buildCombinedQuery($identifiers);
    if ($query === null) {
        echo "Inga giltiga identifierare angivna.\n";
        return null;
    }

    $jsonQuery = json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "=== SÖKER MED SAMMANSATT QUERY ===\n$jsonQuery\n";

    $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ];

    echo "=== queryURL ===\n$queryUrlWithParams\n";

    $response = makeHttpRequest($queryUrlWithParams, 'POST', $headers, $jsonQuery);
    echo "== RESPONSE ==\n" . $response['response'] . "\n";

    if ($response['status'] !== 200) {
        echo "Sökning misslyckades. Status: {$response['status']}\n";
        return null;
    }

    return extractBibIdsFromResponse($response['response']);
}

function buildCombinedQuery(array $identifiers): ?array {
    $fields = [
        'bib_id' => ['tag' => 'j',        'value' => $identifiers['bib_id'] ?? null],
        'isbn'   => ['tag' => 'i',        'value' => $identifiers['isbn'] ?? null],
        'onr'    => ['marcTag' => '035',  'value' => $identifiers['onr'] ?? null]
    ];

    $queryParts = [];
    foreach ($fields as $field) {
        if (empty($field['value'])) continue;

        if (!empty($queryParts)) {
            $queryParts[] = 'or';
        }

        $targetField = isset($field['tag']) ? ['tag' => $field['tag']] : ['marcTag' => $field['marcTag']];

        $queryParts[] = [
            'target' => [
                'record' => ['type' => 'bib'],
                'field'  => $targetField
            ],
            'expr' => [
                'op' => 'equals',
                'operands' => [$field['value'], '']
            ]
        ];
    }

    return empty($queryParts) ? null : ['queries' => $queryParts];
}

function extractBibIdsFromResponse(string $json): ?array {
    $data = json_decode($json, true);
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        echo "Inga träffar i svaret.\n";
        return null;
    }

    $bibIds = [];
    foreach ($data['entries'] as $entry) {
        $id = extractBibIdFromLink($entry['link'] ?? '');
        if ($id !== null) {
            $bibIds[] = $id;
        }
    }

    return $bibIds ?: null;
}



function extractBibIdFromLink(string $link): ?string {
    $id = basename($link);

    // Enklare validering – tomma ID:n bör ignoreras
    return $id !== '' ? $id : null;
}


/**
 * Bygger URL för att hämta items för ett specifikt bibID.
 */
function buildItemsUrl(string $baseUrl, string $bibId): string {
    $params = http_build_query([
        'fields'     => 'location,callNumber,status',
        'deleted'    => 'false',
        'suppressed' => 'false',
        'bibIds'     => $bibId
    ]);

    return rtrim($baseUrl, '/') . '/?' . $params;
}

/**
 * Tolkar HTTP-svar och returnerar ett strukturerat array-format av items.
 */
function parseItemsResponse(string $jsonResponse): ?array {
    $data = json_decode($jsonResponse, true);

    if (!isset($data['entries']) || empty($data['entries'])) {
        return null;
    }

    $items = [];

    foreach ($data['entries'] as $entry) {
        $items[] = [
            'location'    => $entry['location']['code'] ?? null,
            'callNumber'  => $entry['callNumber'] ?? null,
            'status'      => $entry['status']['code'] ?? null
        ];
    }

    return $items;
}

/**
 * Huvudfunktion som anropar Sierra API för att hämta items till ett bibId.
 */
function fetchItemsForBibId(string $baseUrl, string $bibId, string $token): ?array {
    $url = buildItemsUrl($baseUrl, $bibId);

    $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Accept'        => 'application/json'
    ];

    echo ">>> HÄMTAR FRÅN URL: $url\n";

    $response = makeHttpRequest($url, 'GET', $headers);

    if ($response['status'] !== 200) {
        echo "Kunde inte hämta items. Status: {$response['status']}\n";
        return null;
    }

    echo ">>> Lyckades hämta items för bibID: $bibId\n";
    return parseItemsResponse($response['response']);
}




// === HUVUDFLÖDE ===

// $parameters = [	'limit' => '10',
//  				'createdDate' => '2025-02-07',
//  				'deleted' => 'false',
//                 'suppressed' => 'false'];
$token = authenticateAndGetToken($baseUrl . $tokenEndpoint, $clientKey, $clientSecret);

if ($token) {
    $sierraBibIds = getSierraBibIdsFromIdentifiers($baseUrl . $queryEndpoint, $limit, $offset, $identifiers, $token);

    if (!$sierraBibIds) {
        echo "Inget bibID hittades för angivna parametrar: " . json_encode($identifiers) . "\n";
        exit;
    }

    foreach ($sierraBibIds as $sierraBibId) {
        echo "Hämtar items för Sierra Bib_ID: $sierraBibId\n";
        $items = fetchItemsForBibId($baseUrl . $itemsEndpoint, $sierraBibId, $token);

        if ($items !== null) {
            echo "=== RAW itemsJson respone ===\n";
            print_r($items); // För att se den strukturerade arrayen
            generateXMLFromData($items);
            break; // Sluta efter första som har items
        }
    }
} else {
    echo "Ingen token kunde hämtas. Avbryter.\n";
}

?>
