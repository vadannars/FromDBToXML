<?php

/*
curl -X POST https://gotlib.goteborg.se/iii/sierra-api/v6/token -H "Authorization: Basic $(echo -n 'nxp4yHPM46n/15Ut7dv4zY1QUTkp:IvoryLemon#169' | base64)" -H "Content-Type: application/x-www-form-urlencoded" -d "grant_type=client_credentials"

  $clientKey = 'nxp4yHPM46n/15Ut7dv4zY1QUTkp';
$clientSecret = 'IvoryLemon#169';

curl -X POST https://gotlib.goteborg.se/iii/sierra-api/v6/token -H "Authorization: Basic bnhwNHlIUE00Nm4vMTVVdDdkdjR6WTFRVVRrcDpJdm9yeUxlbW9uIzE2OQ==" -H "Content-Type: application/x-www-form-urlencoded" -d "grant_type=client_credentials"



marc: https://www.oclc.org/bibformats/en/0xx.html
bibframe: https://www.loc.gov/bibframe/

Adress till bibliotekets api:
gotlib.goteborg.se/iii/sierra-api/

API Key:
nxp4yHPM46n/15Ut7dv4zY1QUTkp

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

Samplekod för att generera en xml:
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"><products></products>');

$product = $xml->addChild('product');
$product->addChild('name','Product 1');
$product->addChild('price','19.99');

$xml->asXML('products.xml')

*/

//require 'vendor/autoload.php';

//use GuzzleHttp\Client;

// $apiAuth = 'Basic bnhwNHlIUE00Nm4vMTVVdDdkdjR6WTFRVVRrcDpJdm9yeUxlbW9uIzE2OQ==';
// $headers = ['Authorization' => $apiAuth];
// $parameters = [	'limit' => '5',
// 				'fields' => 'isbn,issn,title,author,locations,holdCount,callNumber,items',
// 				'cratedDate' => '2025-05-05',
// 				'deleted' => 'false'];

// $xmlString = '<?xml version="1.0" encoding="UTF-8"><status></status>';
// $responseAsXML = new SimpleXMLElement($xmlString);
// $callMethod = 'GET';
// $apiAdress = 'https://gotlib.goteborg.se/iii/sierra-api//swagger/index.html#!/bibs/Get_a_list_of_bibs_get_0';

//$apiToken = callAPI('POST','https://gotlib.goteborg.se/iii/sierra-api/v6/token',[], $headers);
//$APIresponse = callAPI($callMethod, $apiAdress, $parameters =[], $headers = []);
//$responseData = decodeJSONResponse($APIresponse);
//$tokenResponse = decodeJSONResponse($apiToken);
//printJSONdata($responseData);
//printJSONdata($tokenResponse);
//$token = getTokenFromArray($tokenResponse);
//echo $token;
// echo 'test'; // FUNGERAR, det är något med resten av koden som inte vill (lol).
// //arrayToXml($responseData,$responseAsXML);
// //$responseAsXML->asXML('loanstatus.xml');

// function getTokenFromArray($tokenResponse) {
// 	foreach ($tokenResponse as $key => $value) {
// 		if ($key == 'access_token') {
// 			return $value;
// 		}
// 	}
// }

// function callAPI($method, $apiAdress, $queryParameters, $headers) {
// 	$client = new Client();
// 	$response = $client->request($method, $apiAdress, [
// 			'query' => $queryParameters, [
// 			'headers' => $headers
// 			]
// 		]);
// 	return$response;
// }

// function decodeJSONResponse($APIresponse){
// 	$responseData = json_decode($APIresponse->getBody(), true);
// 	return$responseData;
// }

// function printJSONdata ($data){
// 	print_r($data);
// }

// function arrayToXml($data, $xmlData) {
// 	$libraryDescription = 'Exemplarstatus för böcker i Göteborgs Stadsbiblioteks katalog';

// 	if ($xmlData->getName() !== 'channel') {
// 		$channel = $xmlData->addChild('channel');
// 	} else {$channel = $xmlData;}

// 	$channel->addChild('description', htmlspecialchars($libraryDescription));
// 	$itemInfoNode = $channel->addChild('Item_information');

// 	foreach ($data as $key => $value) {
// 		if (is_numeric($key)) {
// 			$key = "item$key";
// 		}
// 		if (is_array($value)) {
// 			$subnode = $itemInfoNode->addChild($key);
// 			arrayToXml($value, $subnode);
// 		} else {
// 			$itemInfoNode->addChild($key, htmlspecialchars($value));
// 		}
// 	}
// }

// === CHATGTPs LÖSNING ===


// === KONFIGURATION ===
require_once __DIR__ . '/json_to_array.php';
require_once __DIR__ . '/generatexmlfromitems.php';

$authUrl = 'https://gotlib.goteborg.se/iii/sierra-api/v6/token';
$dataUrl = 'https://gotlib.goteborg.se/iii/sierra-api/v6/items';

// Autentiseringsuppgifter
$clientKey = 'nxp4yHPM46n/15Ut7dv4zY1QUTkp';
$clientSecret = 'IvoryLemon#169';


// === GENERELL FUNKTION FÖR HTTP-ANROP ===
// function makeHttpRequest(string $url, string $method = 'GET', array $headers = [], $body = null, $userpwd = null): array {
//     $ch = curl_init($url);

//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

//     if ($body !== null) {
//         curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
//     }
//     curl_setopt($ch, CURLOPT_USERPWD, "$userpwd");

//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

//     curl_setopt($ch, CURLOPT_HEADER, true);  // få med headers i responsen
//     curl_setopt($ch, CURLOPT_VERBOSE, true); // för felsökning
//     curl_setopt($ch, CURLINFO_HEADER_OUT, true); // få ut headers i info

//     $response = curl_exec($ch);
//     $error = curl_error($ch);
//     $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);

//     curl_close($ch);

//     return [
//         'status' => $statusCode,
//         'response' => $response,
//         'error' => $error,
//         'sent_headers' => $headerSent
//     ];
// }

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

    echo "Authorization header: Basic $authHeader\n";
    echo "Body: $body\n";

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


// === HUVUDFLÖDE ===

$parameters = [	'limit' => '10',
 				'createdDate' => '2025-02-07',
 				'deleted' => 'false',
                'suppressed' => 'false'];
$token = authenticateAndGetToken($authUrl, $clientKey, $clientSecret);
//$token = ' OEqPT2z77G85Hg9R9VEfq0GuOzqOO0Sf5PmFhwS3Kg77vyjSw6bb293YpVlkLFGB0VtD2Vhcs0lkhBSup13VszDllt8JTUycnOnIVoLANjGRG4XkQL2trnHfjELxYHEy';

if ($token) {
    $jsonResponse = fetchDataWithToken($dataUrl, $token, $parameters);
    if ($jsonResponse !== null) {
        $dataArray = jsonToArray($jsonResponse);
        generateXMLFromData($dataArray);
    } else {
        echo "Ingen data att bearbeta.\n";
    }
} else {
    echo "Ingen token kunde hämtas. Avbryter.\n";
}
?>
