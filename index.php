<?php

/*----------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 * Licensed under the MIT License. See LICENSE in the project root for license information.
 *---------------------------------------------------------------------------------------*/
/*
PROJEKTETS DELMÅL:

* Skapa testmiljö i codespaces - KLART
* Skapa XML - KLART
* Hur loopa igenom apisvar?
* Hur loopa skapandet av xml?
* Hur ta parametrar?
	// URL: http://example.com?name=Alice
	$name = $_GET['name'] ?? 'Guest'; // Default to 'Guest' if 'name' is not set
	echo "Hello, " . htmlspecialchars($name) . "!"; // Output: Hello, Alice!

* Hur kontakta API?
	* Kodmässigt php
		
		require 'vendor/autoload.php';

		use GuzzleHttp\Client;

		$client = new Client();
		$response = $client->request('GET', 'https://api.example.com/data', [
			'query' => ['param1' => 'value1', 'param2' => 'value2']
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
$client = new Client();

function callAPI() {


}

function sayHello($name) {
	echo "Hello $name!";
}

?>

<html>
	<head>
		<title>Visual Studio Code Remote :: PHP</title>
	</head>
	<body>
		<?php 
		
		sayHello('remote world');
			
		phpinfo(); 
			
		?>
	</body>
</html>