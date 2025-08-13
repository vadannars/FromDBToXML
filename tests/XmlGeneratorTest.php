<?php
declare(strict_types=1);

// Testerna bör ha sitt eget namespace. En vanlig konvention är App\Tests.
namespace App\Tests;

// Vi importerar PHPUnits grundläggande testklass och klassen vi ska testa.
use App\XmlGenerator;
use PHPUnit\Framework\TestCase;

// Klassnamnet matchar filnamnet och ärver från PHPUnits TestCase.
class XmlGeneratorTest extends TestCase
{
    /**
     * Testar att mapStatus-metoden returnerar rätt text för kända statuskoder.
     * Metodnamnet för ett test ska börja med "test".
     */
    public function testMapStatusReturnsCorrectTextForKnownCodes(): void
    {
        // === ARRANGE ===
        // Inget att arrangera här eftersom metoden är statisk.

        // === ACT & ASSERT ===
        // Vi anropar metoden (Act) och kontrollerar resultatet (Assert) i ett svep.
        // assertEquals är en av de vanligaste "assertions".
        // Den kollar om det andra argumentet (faktiskt värde) är lika med det första (förväntat värde).
        $this->assertEquals('Tillgänglig', XmlGenerator::mapStatus('-'));
        $this->assertEquals('Reserverad', XmlGenerator::mapStatus('!'));
        $this->assertEquals('På lagning', XmlGenerator::mapStatus('p'));
    }

    /**
     * Testar att vi får en standard-text om vi skickar in en okänd kod.
     */
    public function testMapStatusHandlesUnknownCodeGracefully(): void
    {
        // === ARRANGE ===
        $unknownCode = 'en_helt_okand_kod';

        // === ACT ===
        $result = XmlGenerator::mapStatus($unknownCode);

        // === ASSERT ===
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

        // === ACT ===
        $xmlString = XmlGenerator::generateXmlFromItems($items);

        // === ASSERT ===
        // Vi gör några enkla kontroller. Är detta en giltig XML-sträng?
        // En enkel men effektiv assert är att kolla att specifika delar finns i strängen.
        $this->assertStringContainsString('<Location>Huvudbiblioteket</Location>', $xmlString);
        $this->assertStringContainsString('<Status>Utlånad</Status>', $xmlString); // Notera att logiken gör om '-' till 'Utlånad' om duedate finns.
        $this->assertStringContainsString('<Status_date>2025-09-30</Status_date>', $xmlString);

        $this->assertStringContainsString('<Location>Magasin</Location>', $xmlString);
        $this->assertStringContainsString('<Status>Referens</Status>', $xmlString);
    }
}