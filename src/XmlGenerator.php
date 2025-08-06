<?php
namespace App;

class XmlGenerator {
    public static function mapStatus(string $code): string {
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

    public static function generateXmlFromItems(array $items): string {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');

        $channel = $xml->addChild('channel');
        $channel->addChild('description', 'Exemplarstatus för böcker i Göteborgs biblioteks katalog');
        $itemInfo = $channel->addChild('Item_information');

        $counter = 1;

        foreach ($items as $item) {
            $xmlItem = $itemInfo->addChild('Item');

            $statusCode = trim($item['status']['code'] ?? 'UNKNOWN');
            $duedate = $item['status']['duedate'] ?? '';

            $statusText = '-';
            if ($statusCode === '-') {
                $statusText = $duedate !== '' ? 'Utlånad' : 'Tillgänglig';
            } else {
                $statusText = self::mapStatus($statusCode);
            }

            $fields = [
                'Item_No' => $counter++,
                'Location' => $item['location']['name'] ?? '',
                'Call_No' => $item['callNumber'] ?? '',
                'Status' => $statusText,
                'Status_date' => $duedate,
                'Status_Date_Description' => ($statusCode === '-' && $duedate !== '') ? 'ÅTER ' : '',
                'Loan_Policy' => '', // Placeholder
                'UniqueItemId' => '' // Placeholder
            ];

            foreach ($fields as $tag => $value) {
                $xmlItem->addChild($tag, htmlspecialchars((string)$value));
            }
        }

        return $xml->asXML();
    }
}
