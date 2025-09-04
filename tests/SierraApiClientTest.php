<?php
declare(strict_types=1);

namespace App\Tests;

use App\Config;
use App\SierraApiClient;
use App\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class SierraApiClientTest extends TestCase
{
    /**
     * @var string Ett dummy-svar från API:ets token-endpoint
     */
    private const FAKE_AUTH_RESPONSE = '{ "access_token": "en-superhemlig-test-token", "expires_in": 3600 }';

    /**
     * @var string Ett dummy-svar från API:ets query-endpoint
     */
    private const FAKE_QUERY_RESPONSE = '{ "entries": [] }';

    /**
     * Skapar en mock av Config-objektet för att testa SierraApiClient.
     *
     * @return Config
     */
    private function createMockConfig(): Config
    {
        $mockConfig = $this->createMock(Config::class);
        $mockConfig->method('getApiBaseUrl')->willReturn('https://example.com/api/v6');
        $mockConfig->method('getApiKey')->willReturn('test_key');
        $mockConfig->method('getApiSecret')->willReturn('test_secret');
        $mockConfig->method('getTokenEndpoint')->willReturn('/token');
        $mockConfig->method('getQueryEndpoint')->willReturn('/bibs/query');
        $mockConfig->method('getItemsEndpoint')->willReturn('/items');
        $mockConfig->method('getQueryParameters')->willReturn(['limit' => 10, 'offset' => 0]);
        $mockConfig->method('getItemFields')->willReturn('location,callNumber,status');
        $mockConfig->method('getQueryFields')->willReturn([
            'bib_id' => ['type' => 'tag', 'value' => 'j'],
            'isbn' => ['type' => 'tag', 'value' => 'i'],
            'issn' => ['type' => 'marcTag', 'value' => '022'],
            'onr' => ['type' => 'marcTag', 'value' => '035']
        ]);
        return $mockConfig;
    }

    /**
     * Testar att getItemsForIdentifiers() lyckas, vilket i sin tur triggar en lyckad autentisering
     * och sätter token och utgångstid.
     */
    public function testGetItemsForIdentifiersSuccessfullySetsTokenAndExpiry(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $fakeAuthResponse = [
            'status' => 200,
            'response' => self::FAKE_AUTH_RESPONSE,
            'error' => ''
        ];
        $fakeQueryResponse = [
            'status' => 200,
            'response' => self::FAKE_QUERY_RESPONSE,
            'error' => ''
        ];

        $mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($fakeAuthResponse, $fakeQueryResponse);
        
        $sierraClient = new SierraApiClient(
            $this->createMockConfig(),
            $mockHttpClient
        );

        // === ACT ===
        $sierraClient->getItemsForIdentifiers(['isbn' => '978-3-16-148410-0']);

        // === ASSERT ===
        $reflection = new \ReflectionClass(SierraApiClient::class);

        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setAccessible(true);
        $actualToken = $tokenProperty->getValue($sierraClient);

        $expiresAtProperty = $reflection->getProperty('expiresAt');
        $expiresAtProperty->setAccessible(true);
        $actualExpiresAt = $expiresAtProperty->getValue($sierraClient);

        $this->assertEquals('en-superhemlig-test-token', $actualToken);
        $this->assertGreaterThan(time(), $actualExpiresAt);
    }

    /**
     * Testar att getItemsForIdentifiers() kastar ett undantag om autentiseringen misslyckas.
     */
    public function testGetItemsThrowsExceptionOnAuthenticationFailure(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $failedAuthResponse = [
            'status' => 401,
            'response' => '',
            'error' => 'Unauthorized'
        ];
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->willReturn($failedAuthResponse);

        $sierraClient = new SierraApiClient(
            $this->createMockConfig(),
            $mockHttpClient
        );
        
        // === ASSERT (innan ACT) ===
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Autentisering misslyckades: HTTP 401 - Unauthorized');

        // === ACT ===
        $sierraClient->getItemsForIdentifiers(['isbn' => '978-3-16-148410-0']);
    }

    /**
     * Testar att getItemsForIdentifiers anropar API:et korrekt och returnerar parsade item-ID:n.
     */
    public function testGetItemsReturnsParsedIdsOnSuccess(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $fakeAuthResponse = ['status' => 200, 'response' => self::FAKE_AUTH_RESPONSE, 'error' => ''];
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
        $fakeItemsResponse = [
            'status' => 200,
            'response' => json_encode(['entries' => [
                ['id' => 'i1', 'status' => ['code' => '-']],
                ['id' => 'i2', 'status' => ['code' => '!']]
            ]]),
            'error' => ''
        ];
        
        $mockHttpClient->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($fakeAuthResponse, $fakeQueryResponse, $fakeItemsResponse);

        $sierraClient = new SierraApiClient(
            $this->createMockConfig(),
            $mockHttpClient
        );
        
        // === ACT ===
        $result = $sierraClient->getItemsForIdentifiers(['isbn' => '978-3-16-148410-0']);

        // === ASSERT ===
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('i1', $result[0]['id']);
    }

    /**
     * Testar att den interna metoden buildCombinedQuery genererar rätt query.
     */
    public function testBuildsCorrectQueryForMultipleIdentifiers(): void
    {
        // === ARRANGE ===
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $sierraClient = new SierraApiClient(
            $this->createMockConfig(),
            $mockHttpClient
        );
        $reflection = new \ReflectionClass(SierraApiClient::class);
        $method = $reflection->getMethod('buildCombinedQuery');
        $method->setAccessible(true);
        
        // Exempel 1: Endast ISBN
        $identifiers1 = ['isbn' => '978-1234567890', 'bib_id' => null];
        $expected1 = [
            'queries' => [
                [
                    'target' => [ 'record' => ['type' => 'bib'], 'field' => ['tag' => 'i'] ],
                    'expr' => [ 'op' => 'equals', 'operands' => ['978-1234567890'] ]
                ]
            ]
        ];
        $this->assertEquals($expected1, $method->invoke($sierraClient, $identifiers1));
        
        // Exempel 2: Både Bib_ID (prioritet) och ISBN
        $identifiers2 = ['bib_id' => '9v8xbqxk785qpxhh', 'isbn' => '978-1234567890'];
        $expected2 = [
            'queries' => [
                [
                    'target' => [ 'record' => ['type' => 'bib'], 'field' => ['tag' => 'j'] ],
                    'expr' => [ 'op' => 'equals', 'operands' => ['9v8xbqxk785qpxhh'] ]
                ]
            ]
        ];
        $this->assertEquals($expected2, $method->invoke($sierraClient, $identifiers2));

        // Exempel 3: ISBN och ONR (OR-logik)
        $identifiers3 = ['isbn' => '978-1234567890', 'onr' => '1234567'];
        $expected3 = [
            'queries' => [
                [
                    'target' => [ 'record' => ['type' => 'bib'], 'field' => ['tag' => 'i'] ],
                    'expr' => [ 'op' => 'equals', 'operands' => ['978-1234567890'] ]
                ],
                'or',
                [
                    'target' => [ 'record' => ['type' => 'bib'], 'field' => ['marcTag' => '035'] ],
                    'expr' => [ 'op' => 'equals', 'operands' => ['1234567'] ]
                ]
            ]
        ];
        $this->assertEquals($expected3, $method->invoke($sierraClient, $identifiers3));
    }
}