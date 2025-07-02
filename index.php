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
* Hur kontakta API?


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