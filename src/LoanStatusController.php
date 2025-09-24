<?php

declare(strict_types=1);

namespace App;

use App\SierraApiClientInterface;
use App\XmlGenerator;
use Psr\Log\LoggerInterface;

/**
 * Kontrollerklass för att hantera begäran om låne-/exemplarstatus.
 *
 * Denna klass innehåller affärslogiken för att hämta exemplarstatus från Sierra API
 * och generera en XML-fil. Denna separation från den publika ingressfilen
 * möjliggör en mer robust och testbar applikationsstruktur.
 */
class LoanStatusController
{
    private SierraApiClientInterface $apiClient;
    private XmlGenerator $xmlGenerator;
    private LoggerInterface $logger;

    public function __construct(
        SierraApiClientInterface $apiClient,
        XmlGenerator $xmlGenerator,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->xmlGenerator = $xmlGenerator;
        $this->logger = $logger;
    }

    /**
     * Hanterar en inkommande HTTP-förfrågan och genererar ett XML-svar.
     *
     * @param array<string, string|null> $identifiers En array av sökidentifierare
     * som bib_id, isbn etc.
     * @return string En sträng som innehåller den genererade XML-datan.
     * @throws \InvalidArgumentException Om inga giltiga identifierare hittades.
     * @throws \RuntimeException Om inga exemplar hittas eller om något går fel under API-anropet.
     */
    public function handleRequest(array $identifiers): string
    {
        if (empty(array_filter($identifiers))) {
            throw new \InvalidArgumentException("Ingen parameter angiven i anropet.");
        }

        $this->logger->info('API-anrop mottaget', ['params' => $identifiers]);

        // Använder det injicerade klientobjektet för att hämta data
        $allItems = $this->apiClient->getItemsForIdentifiers($identifiers);

        if (empty($allItems)) {
            throw new \RuntimeException("Inga exemplar hittades för mediet.");
        }

        // Använder den injicerade XML-generatorn
        return $this->xmlGenerator->generateXmlFromItems($allItems);
    }
}
