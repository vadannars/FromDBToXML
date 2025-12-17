<?php
declare(strict_types=1);

namespace App\Tests;

use App\XmlGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class XmlGeneratorTest extends TestCase
{
    /**
     * Testar att mapStatus-metoden returnerar rätt text för kända statuskoder.
     */
    public function testMapStatusReturnsCorrectTextForKnownCodes(): void
    {
        $this->assertEquals('Tillgänglig', XmlGenerator::mapStatus('-'));
        $this->assertEquals('Reserverad', XmlGenerator::mapStatus('!'));
        $this->assertEquals('På lagning', XmlGenerator::mapStatus('p'));
    }

    /**
     * Testar att vi får en standard-text om vi skickar in en okänd kod.
     */
    public function testMapStatusHandlesUnknownCodeGracefully(): void
    {
        $unknownCode = 'en_helt_okand_kod';
        $result = XmlGenerator::mapStatus($unknownCode);
        $this->assertEquals('Okänd status', $result);
    }

    /**
     * Testar att XML-genereringen fungerar med en typisk item-array.
     */
    public function testGenerateXmlFromItemsCreatesCorrectStructure(): void
    {
        // === ARRANGE ===
        // Vi skapar en låtsas-array med data som ser ut som den från API:et.
        $items = [
            [
                'status' => ['code' => '-', 'duedate' => '2025-09-30'],
                'location' => ['name' => 'Huvudbiblioteket'],
                'callNumber' => 'Hce.3'
            ],
            [
                'status' => ['code' => 'o', 'duedate' => null],
                'location' => ['name' => 'Magasin'],
                'callNumber' => 'Mce.3'
            ]
        ];

        // Skapa en mockad logger som inte gör något. Vi behöver den för att instansiera XmlGenerator.
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Skapa en instans av XmlGenerator med den mockade loggern.
        $xmlGenerator = new XmlGenerator($mockLogger);

        // === ACT ===
        // Anropa den icke-statiska metoden på instansen.
        $xmlString = $xmlGenerator->generateXmlFromItems($items);

        // === ASSERT ===
        $this->assertStringContainsString('<Location>Huvudbiblioteket</Location>', $xmlString);
        $this->assertStringContainsString('<Status>Utlånad</Status>', $xmlString);
        $this->assertStringContainsString('<Status_Date>2025-09-30</Status_Date>', $xmlString);

        $this->assertStringContainsString('<Location>Magasin</Location>', $xmlString);
        $this->assertStringContainsString('<Status>Referens</Status>', $xmlString);
    }
}
