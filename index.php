<?php

/*
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
	$xmlData = new SimpleXMLElement('<?xml version="1.0"?><root></root>');

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
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products></products>');

$product = $xml->addChild('product');
$product->addChild('name','Product 1');
$product->addChild('price','19.99');

$xml->asXML('products.xml')

*/

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$xmlString = '<?xml version="1.0" encoding="UTF-8"?><status></status>';
$responseAsXML = new SimpleXMLElement($xmlString);
$callMethod = 'GET';
$apiAdress = 'https://gotlib.goteborg.se/iii/sierra-api//swagger/index.html#!/bibs/Get_a_list_of_bibs_get_0';

$APIresponse = callAPI($callMethod, $apiAdress);
$responseData = decodeJSONResponse($APIresponse);
printJSONdata($responseData);
arrayToXml($responseData,$responseAsXML);
$responseAsXML->asXML('loanstatus.xml');

function callAPI($method, $apiAdress) {
	$client = new Client();
	$response = $client->request($method, $apiAdress, [
			'query' => ['param1' => 'value1', 'param2' => 'value2']
		]);
	return$response;
}

function decodeJSONResponse($APIresponse){
	$responseData = json_decode($APIresponse->getBody(), true);
	return$responseData;
}

function printJSONdata ($data){
	print_r($data);
}

function arrayToXml($data, $xmlData) {
	$libraryDescription = 'Exemplarstatus för böcker i Göteborgs Stadsbiblioteks katalog';

	if ($xmlData->getName() !== 'channel') {
		$channel = $xmlData->addChild('channel');
	} else {$channel = $xmlData;}

	$channel->addChild('description', htmlspecialchars($libraryDescription));
	$itemInfoNode = $channel->addChild('Item_information');

	foreach ($data as $key => $value) {
		if (is_numeric($key)) {
			$key = "item$key";
		}
		if (is_array($value)) {
			$subnode = $itemInfoNode->addChild($key);
			arrayToXml($value, $subnode);
		} else {
			$itemInfoNode->addChild($key, htmlspecialchars($value));
		}
	}
	}