<?php

namespace App;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Exception;
use DateTime;

/**
 * Genererar XML-utdata baserat på data från Sierra API:et.
 *
 * Denna klass är ansvarig för att transformera rå data till det
 * specifika XML-format som krävs.
 */
class XmlGenerator
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Mappar statuskoder från Sierra till läsbara svenska texter.
     *
     * @param  string $code Den korta statuskoden från API:et.
     * @return string Den läsbara statusbeskrivningen.
     */
    public static function mapStatus(string $code): string
    {
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
     * @param  array<array<string, mixed>> $items En array med exemplarposter från API:et.
     * @return string Den genererade XML-strängen.
     */
    public function generateXmlFromItems(array $items): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><status></status>');

        $channel = $xml->addChild('channel');
        $channel->addChild('description', 'Exemplarstatus för böcker i Göteborgs biblioteks katalog');
        $itemInfo = $channel->addChild('Item_information');

        $counter = 1;

        foreach ($items as $item) {
            $xmlItem = $itemInfo->addChild('Item');

            $statusCode = 'UNKNOWN';
            $duedate = '';
            $locationName = '';
            $callNumber = '';

            if (isset($item['status']) && is_array($item['status'])) {
                $statusData = $item['status'];
                if (isset($statusData['code']) && is_string($statusData['code'])) {
                    $statusCode = $statusData['code'];
                }
                if (isset($statusData['duedate']) && is_string($statusData['duedate'])) {
                    $duedate = $statusData['duedate'];
                }
            }

            if (
                isset($item['location']) &&
                is_array($item['location']) &&
                isset($item['location']['name']) &&
                is_string($item['location']['name'])
            ) {
                $locationName = $item['location']['name'];
            }

            if (isset($item['callNumber']) && is_string($item['callNumber'])) {
                $callNumber = $item['callNumber'];
            }

            $statusCode = trim($statusCode);
            $statusText = '-';
            if ($statusCode === '-') {
                $statusText = $duedate !== '' ? 'Utlånad' : 'Tillgänglig';
            } else {
                $statusText = self::mapStatus($statusCode);
            }

            if ($duedate !== '') {
                try {
                    $dateObj = new DateTime($duedate);
                    $duedate = $dateObj->format('Y-m-d');
                } catch (Exception $e) {
                    $this->logger->warning('Ogiltigt datumformat från API, kunde inte formatera.', [
                        'original_date' => $duedate,
                        'exception_message' => $e->getMessage(),
                    ]);
                    $duedate = '';
                }
            }

            $fields = [
                'Item_No' => $counter++,
                'Location' => $locationName,
                'Call_No' => $callNumber,
                'Status' => $statusText,
                'Status_Date' => $duedate,
                'Status_Date_Description' => ($statusCode === '-' && $duedate !== '') ? 'ÅTER ' : '',
                'Loan_Policy' => '', // Placeholder
                'UniqueItemId' => '' // Placeholder
            ];

            foreach ($fields as $tag => $value) {
                // Säkerställer att värdet är en sträng innan vi använder htmlspecialchars
                $xmlItem->addChild($tag, htmlspecialchars((string)$value));
            }
        }
        $xmlResult = $xml->asXML();
        if ($xmlResult === false) {
            $this->logger->error('Misslyckades med att generera XML');
            throw new \RuntimeException("Ingen xml-data genererades.");
        }
        return $xmlResult;
    }
}
