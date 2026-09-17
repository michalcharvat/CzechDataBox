<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport;

use MichalCharvat\CzechDataBox\Exception\AuthenticationFailed;
use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;
use MichalCharvat\CzechDataBox\Transport\HttpErrorMapper;
use PHPUnit\Framework\TestCase;

final class HttpErrorMapperTest extends TestCase
{
    public function testMapping(): void
    {
        self::assertNull(HttpErrorMapper::map(200, 0, '', 'Op'));
        self::assertNull(HttpErrorMapper::map(500, 0, '', 'Op'), 'SOAP faults arrive as HTTP 500 with XML; parse them');
        self::assertInstanceOf(AuthenticationFailed::class, HttpErrorMapper::map(401, 0, '', 'Op'));
        self::assertInstanceOf(AuthenticationFailed::class, HttpErrorMapper::map(403, 0, '', 'Op'));
        self::assertInstanceOf(ServiceUnavailable::class, HttpErrorMapper::map(503, 0, '', 'Op'));
        self::assertInstanceOf(ServiceUnavailable::class, HttpErrorMapper::map(502, 0, '', 'Op'));
        self::assertInstanceOf(ServiceUnavailable::class, HttpErrorMapper::map(0, CURLE_OPERATION_TIMEDOUT, 'timeout', 'Op'));
        self::assertInstanceOf(ServiceUnavailable::class, HttpErrorMapper::map(404, 0, '', 'Op'));
    }
}
