<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Exception;

use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Exception\RateLimited;
use PHPUnit\Framework\TestCase;

final class IsdsExceptionTest extends TestCase
{
    public function testCarriesIsdsCodeMessageAndOperation(): void
    {
        $e = new RateLimited('3009', 'Omezený režim', 'GetListOfReceivedMessages');

        self::assertInstanceOf(IsdsException::class, $e);
        self::assertSame('3009', $e->isdsCode);
        self::assertSame('Omezený režim', $e->isdsMessage);
        self::assertSame('GetListOfReceivedMessages', $e->operation);
        self::assertSame('GetListOfReceivedMessages: [3009] Omezený režim', $e->getMessage());
    }

    public function testWrapsPrevious(): void
    {
        $prev = new \RuntimeException('boom');
        $e = new IsdsException(null, 'network', 'DummyOperation', $prev);

        self::assertSame($prev, $e->getPrevious());
        self::assertSame('DummyOperation: network', $e->getMessage());
    }
}
