<?php

$data = require 'jsontoarray.php';

// === Mappning: Array-key => XML-tagg ===
function generateXMLFromData(array $data): SimpleXMLElement {
    $tagMap = [
        'name' => 'FullName',
        'email' => 'EmailAddress',
        'role' => 'UserRole'
    ];

    // === Skapa XML-dokument ===
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');
    $libraryDescription = 'Exemplarstatus för böcker i Göteborgs biblioteks katalog';

    if ($xml->getName() !== 'channel') {
        $channel = $xml->addChild('channel');
    } else {$channel = $xml;}

    $channel->addChild('description', htmlspecialchars($libraryDescription));
    $itemInfoNode = $channel->addChild('Item_information');
    $counter = 1;
    foreach ($data as $item) {
        $listItem = $itemInfoNode->addChild('Item');
        $listItem->addChild('Item_No', $counter++);

        foreach ($tagMap as $key => $tagName) {
            if (isset($item[$key])) {
                $listItem->addChild($tagName, htmlspecialchars($item[$key]));
            }
        }
    }

    return $xml;
}

$xml = generateXMLFromData($data);

// === Spara till fil (valfritt) ===
$xml->asXML('output.xml');

// === Visa XML (valfritt) ===
Header('Content-type: text/xml');
echo $xml->asXML();

?>

<!-- ChatGPT sade:
Ja, det går absolut att göra i din tagMap — men då behöver du lite mer flexibel kod som kan hantera:

Undertaggar (nästlade XML-element)

Hämta värden från "djupare" nycklar i arrayen (dvs. t.ex. $item['address']['city'])

Hur kan man göra? Här är en möjlig strategi:
1. Utöka tagMap så att värdet kan vara:
En sträng (enkel key → taggnamn)

En array för undertaggar

En "path" (sträng eller array) som pekar på ett djupt värde i arrayen

Exempel på tagMap med undertaggar och "paths"
php
Kopiera
Redigera
$tagMap = [
    'name' => 'FullName',
    'email' => 'EmailAddress',
    'role' => 'UserRole',
    'address' => [                  // undertaggar under <Address>
        'street' => 'Street',
        'city' => 'City',
        'zip' => 'ZipCode'
    ],
    'profile' => [
        'path' => 'profile.details.status',  // hämtar djup nyckel som en sträng med punkter
        'tag' => 'Status'
    ]
];
Funktion för att hämta värde via "path"
php
Kopiera
Redigera
function getValueByPath(array $array, string $path) {
    $keys = explode('.', $path);
    $value = $array;
    foreach ($keys as $key) {
        if (is_array($value) && isset($value[$key])) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    return $value;
}
Modifierad XML-generering som hanterar undertaggar och path
php
Kopiera
Redigera
function addChildrenFromMap(SimpleXMLElement $parent, array $item, array $map) {
    foreach ($map as $key => $value) {
        if (is_array($value)) {
            // Kolla om det är undertaggar eller 'path'-struktur
            if (isset($value['path']) && isset($value['tag'])) {
                // Hämtar värde via path
                $val = getValueByPath($item, $value['path']);
                if ($val !== null) {
                    $parent->addChild($value['tag'], htmlspecialchars($val));
                }
            } else {
                // Undertaggar - skapa en ny nod och rekursivt lägg till
                if (isset($item[$key]) && is_array($item[$key])) {
                    $child = $parent->addChild(ucfirst($key)); // t.ex. <Address>
                    addChildrenFromMap($child, $item[$key], $value);
                }
            }
        } else {
            // Enkel tagg
            if (isset($item[$key])) {
                $parent->addChild($value, htmlspecialchars($item[$key]));
            }
        }
    }
}
Exempel på användning
php
Kopiera
Redigera
$xml = new SimpleXMLElement('<Users></Users>');

foreach ($data as $item) {
    $user = $xml->addChild('User');
    addChildrenFromMap($user, $item, $tagMap);
}

Header('Content-type: text/xml');
echo $xml->asXML();
Kort sammanfattning:
tagMap kan innehålla:

String → enkel key → tag

Array utan path → undertaggar, rekursivt

Array med path & tag → hämta djupt värde från arrayen

Funktionen addChildrenFromMap() loopar rekursivt genom kartan och lägger till XML-taggar

getValueByPath() läser ut värdet från nested keys som 'profile.details.status' -->