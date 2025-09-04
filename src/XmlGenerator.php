<?php
namespace App;

/**
 * Genererar XML-utdata baserat på data från Sierra API:et.
 *
 * Denna klass är ansvarig för att transformera rå data till det
 * specifika XML-format som krävs.
 */
class XmlGenerator {
    /**
     * Mappar statuskoder från Sierra till läsbara svenska texter.
     *
     * @param string $code Den korta statuskoden från API:et.
     * @return string Den läsbara statusbeskrivningen.
     */
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

    /**
     * Genererar en XML-sträng från en array av exemplarinformation.
     *
     * @param array<array<string, mixed>> $items En array med exemplarposter från API:et.
     * @return string Den genererade XML-strängen.
     */ 
    public static function generateXmlFromItems(array $items): string {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');

        $channel = $xml->addChild('channel');
        $channel->addChild('description', 'Exemplarstatus för böcker i Göteborgs biblioteks katalog');
        $itemInfo = $channel->addChild('Item_information');

        $counter = 1;

        foreach ($items as $item) {
            $xmlItem = $itemInfo->addChild('Item');

            $statusCode = trim((string) $item['status']['code'] ?? 'UNKNOWN');
            $duedate = (string) $item['status']['duedate'] ?? '';

            $statusText = '-';
            if ($statusCode === '-') {
                $statusText = $duedate !== '' ? 'Utlånad' : 'Tillgänglig';
            } else {
                $statusText = self::mapStatus($statusCode);
            }

            $fields = [
                'Item_No' => $counter++,
                'Location' => (string) $item['location']['name'] ?? '',
                'Call_No' => (string) $item['callNumber'] ?? '',
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
        $xmlResult = $xml->asXML();
        if ($xmlResult === false) {
            throw new \RuntimeException("Ingen xml-data genererades.");
        }
        return $xmlResult;
    }
}
