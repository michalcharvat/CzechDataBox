<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Internal;

use MichalCharvat\CzechDataBox\Exception;
use MichalCharvat\CzechDataBox\Internal\StatusGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StatusGuardTest extends TestCase
{
    private static function dm(string $code, string $msg = 'text'): \stdClass
    {
        return (object)['dmStatus' => (object)['dmStatusCode' => $code, 'dmStatusMessage' => $msg]];
    }

    public function testSuccessPassesThrough(): void
    {
        $raw = self::dm('0000');
        self::assertSame($raw, StatusGuard::check($raw, 'MessageDownload'));
    }

    public function testEveryDoubleZeroCodeIsSuccess(): void
    {
        $raw = self::dm('0004', 'partial');
        self::assertSame($raw, StatusGuard::check($raw, 'CreateMultipleMessage'));
    }

    public function testDbStatusIsCheckedToo(): void
    {
        $raw = (object)['dbStatus' => (object)['dbStatusCode' => '0000', 'dbStatusMessage' => 'ok']];
        self::assertSame($raw, StatusGuard::check($raw, 'GetOwnerInfoFromLogin2'));
    }

    /** @return iterable<string, array{string, class-string<Exception\IsdsException>}> */
    public static function codes(): iterable
    {
        yield '1222' => ['1222', Exception\NotYetDelivered::class];
        yield '1229' => ['1229', Exception\NotAvailableYet::class];
        yield '1281' => ['1281', Exception\WrongMessageKind::class];
        yield '3006' => ['3006', Exception\DeliveryInProgress::class];
        yield '3008' => ['3008', Exception\RateLimited::class];
        yield '3009' => ['3009', Exception\RateLimited::class];
        yield '3013' => ['3013', Exception\RateLimited::class];
        yield '1211 other box' => ['1211', Exception\MessageNotFound::class];
        yield '1600 no live message' => ['1600', Exception\MessageNotFound::class];
        yield '1219 erased' => ['1219', Exception\MessageErased::class];
        yield '2352 async not ready' => ['2352', Exception\NotAvailableYet::class];
        yield 'unknown' => ['9999', Exception\IsdsException::class];
    }

    /** @param class-string<Exception\IsdsException> $class */
    #[DataProvider('codes')]
    public function testCodeMapsToException(string $code, string $class): void
    {
        try {
            StatusGuard::check(self::dm($code, 'popis'), 'Op');
            self::fail('no exception');
        } catch (Exception\IsdsException $e) {
            self::assertSame($class, $e::class);
            self::assertSame($code, $e->isdsCode);
            self::assertSame('popis', $e->isdsMessage);
            self::assertSame('Op', $e->operation);
        }
    }

    public function testMissingStatusIsAnError(): void
    {
        $this->expectException(Exception\IsdsException::class);
        StatusGuard::check((object)['foo' => 1], 'Op');
    }
}
