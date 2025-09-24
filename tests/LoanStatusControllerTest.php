<?php

declare(strict_types=1);

namespace Tests;

use App\LoanStatusController;
use App\SierraApiClientInterface;
use App\XmlGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoanStatusControllerTest extends TestCase
{
    private $apiClient;
    private $xmlGenerator;
    private $logger;

    protected function setUp(): void
    {
        // Skapa mock-objekt för beroendena.
        // Vi behöver inte den faktiska implementationen av dessa klasser för att testa kontrollern.
        $this->apiClient = $this->createMock(SierraApiClientInterface::class);
        $this->xmlGenerator = $this->createMock(XmlGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Testar ett lyckat anrop med giltiga identifierare.
     */
    public function testHandleRequestSuccess(): void
    {
        // Förbereder mock-objekten för testet.
        $identifiers = ['bib_id' => '9v8xbqxk785qpxhh'];
        $items = [['id' => 'i123', 'status' => 'AVAILABLE']];
        $expectedXml = '<items><item><id>i123</id><status>AVAILABLE</status></item></items>';

        // Konfigurera SierraApiClient-mocken att returnera testdata när getItemsForIdentifiers anropas.
        $this->apiClient->method('getItemsForIdentifiers')
            ->with($identifiers)
            ->willReturn($items);

        // Konfigurera XmlGenerator-mocken att returnera den förväntade XML-strängen.
        $this->xmlGenerator->method('generateXmlFromItems')
            ->with($items)
            ->willReturn($expectedXml);

        // Kontrollera att loggern kallas med rätt meddelande.
        $this->logger->expects($this->once())
            ->method('info')
            ->with('API-anrop mottaget', ['params' => $identifiers]);

        // Skapa en instans av kontrollern med våra mock-objekt.
        $controller = new LoanStatusController($this->apiClient, $this->xmlGenerator, $this->logger);

        // Utför anropet och kontrollera att resultatet är det förväntade.
        $result = $controller->handleRequest($identifiers);
        $this->assertEquals($expectedXml, $result);
    }

    /**
     * Testar att en exception kastas när inga identifierare anges.
     */
    public function testHandleRequestThrowsExceptionOnEmptyIdentifiers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ingen parameter angiven i anropet.');

        $controller = new LoanStatusController($this->apiClient, $this->xmlGenerator, $this->logger);
        $controller->handleRequest([]);
    }

    /**
     * Testar att en exception kastas när API-klienten inte hittar några exemplar.
     */
    public function testHandleRequestThrowsExceptionWhenNoItemsFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inga exemplar hittades för mediet.');

        $identifiers = ['bib_id' => 'non-existent'];

        // Konfigurera apiClient-mocken att returnera null för att simulera att inga exemplar hittas.
        $this->apiClient->method('getItemsForIdentifiers')
            ->with($identifiers)
            ->willReturn(null);

        $controller = new LoanStatusController($this->apiClient, $this->xmlGenerator, $this->logger);
        $controller->handleRequest($identifiers);
    }
}
