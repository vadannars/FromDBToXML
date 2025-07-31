<?php

function mapStatus($code) {
        $code = trim($code ?? '');
        $map = [
            '-' => 'Tillgänglig',
            '!' => 'Reserverad',
            'o' => 'Referens',
            't' => 'På väg',
            'm' => 'Saknas',
            'l' => 'Ej tillgänglig',
            'p' => 'På lagning',
            'u' => 'Under arbete',
            'UNKNOWN' => 'Okänd status'
        ];
        return $map[$code] ?? 'Okänd status';
    }

function getMappedValue($tagRule, $data) {
    if (is_callable($tagRule)) {
        return $tagRule($data);
    }

    if (is_string($tagRule)) {
        return $data[$tagRule] ?? null;
    }

    if (is_array($tagRule) && isset($tagRule['path'])) {
        $value = $data;
        foreach ($tagRule['path'] as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        if (isset($tagRule['map']) && $tagRule['map'] === 'status') {
            return mapStatus($value);
        }

        return $value;
    }

    return null;
}

function generateXmlFromData(array $data): void {
    // Starta XML
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');

    // Skapa channel-nod
    $channel = $xml->addChild('channel');
    $channel->addChild('description', 'Exemplarstatus för böcker i Göteborgs biblioteks katalog');

    // Skapa Item_information-nod
    $itemInfo = $channel->addChild('Item_information');

    $tagMap = [
        'Item_No' => 'counter',

        'Location' => ['path' => ['location', 'name']],

        'Call_No' => ['path' => ['callNumber']],

        'Status' => function ($data) {
            error_log('STATUS code: "' . ($data['status']['code'] ?? 'NULL') . '"');
            $code = trim($data['status']['code'] ?? '');
            $duedate = $data['status']['duedate'] ?? '';

            if ($code === '-') {
                return !empty($duedate) ? 'Utlånad' : 'Tillgänglig';
            }
            return mapStatus($code?: 'UNKNOWN');
        },

        'Status_date' => ['path' => ['status', 'duedate']],

        'Status_Date_Description' => function ($data) {
            if (($data['status']['code'] ?? null) === '-' && !empty($data['status']['duedate'])) {
                return 'ÅTER ';
            }
            return ''; 
        },

        'Loan_Policy' => ['path' => ['location', 'name']],

        'UniqueItemId' => 'placeholder'
    ];

    // Räkna item_nr
    $itemNr = 1;

    if (!is_array($data)) {
    die("Fel: Data är inte en array.");
    }

    // Loopa igenom alla Items
    foreach ($data as $entry) {
        error_log(print_r($entry, true));
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
            $value = getMappedValue($info, $entry);
            // Om värdet är null, skapa tom nod
            $xmlItem->addChild($tag, htmlspecialchars($value ?? ''));
        }
    }

    // Spara till fil
    $xmlString = $xml->asXML();
    file_put_contents('loanstatuslist.xml', $xmlString);
}