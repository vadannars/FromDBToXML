<?php
// Inkludera datakällan
$data = include('jsontoarray.php');

// Starta XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');

// Skapa channel-nod
$channel = $xml->addChild('channel');
$channel->addChild('description', 'Exemplarstatus för böcker i Göteborgs biblioteks katalog');

// Skapa Item_information-nod
$itemInfo = $channel->addChild('Item_information');

// Status enum-liknande mappning
function mapStatus($display) {
    $map = [
        'CHECK SHELF' => 'Tillgänglig',
        'ON HOLDSHELF' => 'På hållhylla',
        'IN TRANSIT' => 'På väg',
        'CHECKED OUT' => 'Utlånad',
        'MISSING' => 'Saknas',
        'LOST' => 'Förlorad',
        'IN PROCESS' => 'Under behandling',
        'UNKNOWN' => 'Okänd status'
    ];
    return $map[$display] ?? 'Okänd status';
}

// Tag-mapp
$tagMap = [
    'Item_No' => 'counter',
    'Location' => ['path' => ['location', 'name']],
    'Call_No' => ['path' => ['callNumber']],
    'Status' => ['path' => ['status', 'display'], 'map' => 'status'],
    'Status_date' => ['path' => ['status', 'duedate']],
    'Status_Date_Description' => ['path' => ['status', 'display']],
    'Loan_Policy' => ['path' => ['location', 'name']],
    'UniqueItemId' => 'placeholder'
];

// Räkna item_nr
$itemNr = 1;

// Kontroll: finns "entries" i arrayen?
if (!isset($data['entries']) || !is_array($data['entries'])) {
    die("Fel: JSON saknar 'entries' eller är inte en array.");
}

// Loopa igenom alla Items
foreach ($data['entries'] as $entry) {
    $xmlItem = $itemInfo->addChild('Item');

    foreach ($tagMap as $tag => $info) {
        // Item_No – specialhantering
        if ($info === 'counter') {
            $xmlItem->addChild($tag, $itemNr++);
            continue;
        }

        // UniqueItemId – placeholder
        if ($info === 'placeholder') {
            $xmlItem->addChild($tag, '');
            continue;
        }

        // Navigera i arrayen
        $value = $entry;
        foreach ($info['path'] as $key) {
            $value = $value[$key] ?? null;
            if ($value === null) break;
        }

        // Mappning via enum?
        if (isset($info['map']) && $info['map'] === 'status') {
            $value = mapStatus($value);
        }

        // Om värdet är null, skapa tom nod
        $xmlItem->addChild($tag, htmlspecialchars($value ?? ''));
    }
}

// Spara till fil
$xmlString = $xml->asXML();
file_put_contents('loanstatuslist.xml', $xmlString);

// Skriv ut på sidan
header('Content-Type: application/xml');
echo $xmlString;
