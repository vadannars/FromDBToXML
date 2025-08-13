<?php
declare(strict_types=1);

namespace App\Tests;

use App\SierraApiClient;
use App\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class SierraApiClientTest extends TestCase
{
    /**
     * Testar att authenticate() lyckas och sätter en token vid ett lyckat API-svar.
     */
    public function testAuthenticateSuccessfullySetsToken(): void
    {
        // === ARRANGE ===
        // Skapa en mock av gränssnittet
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // 2. Definiera det låtsas-svar vi vill ha
        $fakeApiResponse = [
            'status' => 200,
            'response' => json_encode(['access_token' => 'en-superhemlig-test-token']),
            'error' => ''
        ];

        // 3. Konfigurera mock-objektet.
        // "NÄR metoden 'request' anropas, FÖRVÄNTAR vi oss det en gång,
        // och den SKA returnera vårt låtsas-svar".
        $mockHttpClient->expects($this->once())
                   ->method('request')
                   ->willReturn($fakeApiResponse);
       
        // 4. Skapa en instans av SierraApiClient som vi vill testa.
        // Skapa en instans av SierraApiClient med vår mock
        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient // Injicera mock-objektet här
        );
        // === ACT ===
        // Anropa metoden vi faktiskt vill testa.
        // Internt kommer denna metod anropa $this->makeHttpRequest(), men eftersom
        // vi använder en mock kommer den anropa vår fejkade version.
        $sierraClient->authenticate();

        // === ASSERT ===
        // Nu vill vi verifiera att den privata egenskapen 'token' har fått rätt värde.
        // Vi använder Reflection för att kunna läsa den.
        $reflection = new \ReflectionClass(SierraApiClient::class);
        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true); // Gör privat egenskap tillgänglig
        $actualToken = $tokenProperty->getValue($sierraClient);

        $this->assertEquals('en-superhemlig-test-token', $actualToken);
    }

    /**
     * Testar att authenticate() kastar ett undantag (Exception) om API:et svarar med ett fel.
     */
    public function testAuthenticateThrowsExceptionOnApiFailure(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // Definiera ett misslyckat svar från API:et
        $failedApiResponse = [
            'status' => 401,
            'response' => '',
            'error' => 'Unauthorized'
        ];

        // Konfigurera mocken att returnera det misslyckade svaret
        $mockHttpClient->method('request')
                   ->willReturn($failedApiResponse);

        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient
        );
        // === ASSERT (innan ACT) ===
        // Vi talar om för PHPUnit att vi FÖRVÄNTAR oss att en \RuntimeException
        // kommer att "kastas" under körningen av detta test.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Autentisering misslyckades: HTTP 401 - Unauthorized');

        // === ACT ===
        // Anropa metoden. Om den kastar förväntad exception så blir testet godkänt.
        // Om den inte kastar någon exception alls, eller fel typ, så misslyckas testet.
        $sierraClient->authenticate();
    }

    /**
     * Testar att queryBibs anropar API:et korrekt och returnerar parsade bib-ID:n.
     */
    public function testQueryBibsReturnsParsedIdsOnSuccess(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // Steg 2: Definiera det svar vi förväntar oss från bibs/query-endpointen
        $fakeQueryResponse = [
            'status' => 200,
            'response' => json_encode([
                'entries' => [
                    ['link' => 'https://example.com/sierra-api/v6/bibs/12345'],
                    ['link' => 'https://example.com/sierra-api/v6/bibs/67890']
                ]
            ]),
            'error' => ''
        ];

        // Steg 3: Konfigurera mocken att returnera detta svar.
        $mockHttpClient->method('request')->willReturn($fakeQueryResponse);

        // Steg 4. Skapa en instans av SierraApiClient med vår mock.
        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient
        );
        
        // 5. Använd Reflection för att sätta token direkt, så vi kan testa queryBibs
        // isolerat från authenticate().
        $reflection = new \ReflectionClass(SierraApiClient::class);
        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($sierraClient, 'en-giltig-token');

        // === ACT ===
        $result = $sierraClient->queryBibs(['isbn' => '978-3-16-148410-0']);

        // === ASSERT ===
        $this->assertEquals(['12345', '67890'], $result);
    }
}