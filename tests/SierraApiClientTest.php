<?php
declare(strict_types=1);

namespace App\Tests;

use App\SierraApiClient;
use App\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class SierraApiClientTest extends TestCase
{
    /**
     * Testar att queryBibs() lyckas, vilket i sin tur triggar en lyckad autentisering
     * och sätter token och utgångstid.
     */
    public function testAuthenticateSuccessfullySetsTokenAndExpiry(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // API-svaret för token måste innehålla expires_in.
        $fakeAuthResponse = [
            'status' => 200,
            'response' => json_encode([
                'access_token' => 'en-superhemlig-test-token',
                'expires_in' => 3600 // T.ex. 1 timme
            ]),
            'error' => ''
        ];

        // API-svaret för bibs/query. Innehållet här spelar inte så stor roll för detta test.
        $fakeQueryResponse = [
            'status' => 200,
            'response' => json_encode(['entries' => []]),
            'error' => ''
        ];

        // Konfigurera mock-objektet att förvänta sig TVÅ anrop, ett efter det andra,
        // med hjälp av den moderna metoden.
        $mockHttpClient->expects($this->exactly(2))
                       ->method('request')
                       ->willReturnOnConsecutiveCalls($fakeAuthResponse, $fakeQueryResponse);
        
        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient
        );

        // === ACT ===
        // Vi anropar den publika metoden. Detta kommer att trigga båda API-anropen internt.
        $sierraClient->queryBibs(['isbn' => '978-3-16-148410-0']);

        // === ASSERT ===
        // Nu verifierar vi att de privata egenskaperna har fått rätt värden.
        $reflection = new \ReflectionClass(SierraApiClient::class);

        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true);
        $actualToken = $tokenProperty->getValue($sierraClient);

        $expiresAtProperty = $reflection->getProperty('expiresAt');
        $expiresAtProperty->setAccessible(true);
        $actualExpiresAt = $expiresAtProperty->getValue($sierraClient);

        $this->assertEquals('en-superhemlig-test-token', $actualToken);
        // Kontrollerar att expiresAt är satt till en tidpunkt i framtiden.
        $this->assertGreaterThan(time(), $actualExpiresAt);
    }

    /**
     * Testar att queryBibs() kastar ett undantag om autentiseringen misslyckas.
     * Denna testar den publika metoden och den felhanteringslogik som är inbyggd i getToken().
     */
    public function testQueryBibsThrowsExceptionOnAuthenticationFailure(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // Vårt mockade svar simulerar en misslyckad autentisering (t.ex. 401 Unauthorized).
        $failedAuthResponse = [
            'status' => 401,
            'response' => '',
            'error' => 'Unauthorized'
        ];

        // Konfigurera mocken. Vi förväntar oss ett anrop till 'request' som returnerar ett fel.
        $mockHttpClient->expects($this->once())
                       ->method('request')
                       ->willReturn($failedAuthResponse);

        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient
        );
        
        // === ASSERT (innan ACT) ===
        // Vi förväntar oss att en RuntimeException kastas.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Autentisering misslyckades: HTTP 401 - Unauthorized');

        // === ACT ===
        // Anropa den publika metoden. Den interna getToken() kommer att anropa authenticate() och misslyckas.
        $sierraClient->queryBibs(['isbn' => '978-3-16-148410-0']);
    }

    /**
     * Testar att queryBibs anropar API:et korrekt och returnerar parsade bib-ID:n.
     * Vi måste nu mocka TVÅ anrop: ett för token och ett för bib-query.
     */
    public function testQueryBibsReturnsParsedIdsOnSuccess(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);

        // Steg 1: Mocka det första anropet för autentisering.
        $fakeAuthResponse = [
            'status' => 200,
            'response' => json_encode(['access_token' => 'en-giltig-token', 'expires_in' => 3600]),
            'error' => ''
        ];

        // Steg 2: Mocka det andra anropet för sökningen.
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
        
        // Använd willReturnOnConsecutiveCalls() för att definiera svaren för de två anropen i ordning.
        $mockHttpClient->expects($this->exactly(2))
                       ->method('request')
                       ->willReturnOnConsecutiveCalls($fakeAuthResponse, $fakeQueryResponse);

        // Steg 3: Skapa en instans av SierraApiClient med vår mock.
        $sierraClient = new SierraApiClient(
            'https://example.com/api/v6',
            'test_key',
            'test_secret',
            $mockHttpClient
        );
        
        // === ACT ===
        // Anropa den publika metoden. Det första anropet kommer att trigga autentisering.
        $result = $sierraClient->queryBibs(['isbn' => '978-3-16-148410-0']);

        // === ASSERT ===
        $this->assertEquals(['12345', '67890'], $result);
    }
}
