<?php

declare(strict_types=1);

namespace App\Tests;

use App\RequestValidator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    public function testGetOriginReturnsOriginWhenPresent(): void
    {
        $server = ['HTTP_ORIGIN' => 'https://example.com'];
        $result = RequestValidator::getOrigin($server);

        $this->assertSame('https://example.com', $result);
    }

    public function testGetOriginReturnsNullWhenNotPresent(): void
    {
        $server = [];
        $result = RequestValidator::getOrigin($server);

        $this->assertNull($result);
    }

    public function testGetOriginReturnsNullWhenEmpty(): void
    {
        $server = ['HTTP_ORIGIN' => ''];
        $result = RequestValidator::getOrigin($server);

        $this->assertNull($result);
    }

    public function testGetIdentifiersReturnsAllParameters(): void
    {
        $get = [
            'bib_id' => '123456',
            'isbn'   => '978-0-123456-78-9',
            'issn'   => '1234-5678',
            'onr'    => '654321'
        ];

        $result = RequestValidator::getIdentifiers($get);

        $this->assertSame('123456', $result['bib_id']);
        $this->assertSame('978-0-123456-78-9', $result['isbn']);
        $this->assertSame('1234-5678', $result['issn']);
        $this->assertSame('654321', $result['onr']);
    }

    public function testGetIdentifiersHandlesMissingParameters(): void
    {
        $get = ['isbn' => '978-0-123456-78-9'];

        $result = RequestValidator::getIdentifiers($get);

        $this->assertNull($result['bib_id']);
        $this->assertSame('978-0-123456-78-9', $result['isbn']);
        $this->assertNull($result['issn']);
        $this->assertNull($result['onr']);
    }

    public function testGetIdentifiersNormalizesCaseOfKeys(): void
    {
        $get = [
            'BIB_ID' => '123456',
            'ISBN'   => '978-0-123456-78-9',
            'ISSN'   => '1234-5678',
            'ONR'    => '654321'
        ];

        $result = RequestValidator::getIdentifiers($get);

        $this->assertSame('123456', $result['bib_id']);
        $this->assertSame('978-0-123456-78-9', $result['isbn']);
        $this->assertSame('1234-5678', $result['issn']);
        $this->assertSame('654321', $result['onr']);
    }

    public function testGetIdentifiersReturnsNullForEmptyStringValues(): void
    {
        $get = [
            'bib_id' => '',
            'isbn'   => '978-0-123456-78-9',
            'issn'   => '',
            'onr'    => '654321'
        ];

        $result = RequestValidator::getIdentifiers($get);

        $this->assertNull($result['bib_id']);
        $this->assertSame('978-0-123456-78-9', $result['isbn']);
        $this->assertNull($result['issn']);
        $this->assertSame('654321', $result['onr']);
    }

    public function testGetIdentifiersReturnsEmptyArrayWhenGetIsEmpty(): void
    {
        $get = [];

        $result = RequestValidator::getIdentifiers($get);

        $this->assertNull($result['bib_id']);
        $this->assertNull($result['isbn']);
        $this->assertNull($result['issn']);
        $this->assertNull($result['onr']);
    }
}
