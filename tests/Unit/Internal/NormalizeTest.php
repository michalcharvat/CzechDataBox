<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Internal;

use MichalCharvat\CzechDataBox\Internal\Normalize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizeTest extends TestCase
{
    /** @return iterable<string, array{mixed, int}> */
    public static function lists(): iterable
    {
        $one = (object)['dmID' => '1'];
        yield 'null' => [null, 0];
        yield 'nil object' => [(object)[], 0];
        yield 'single object' => [$one, 1];
        yield 'array of one' => [[$one], 1];
        yield 'array of many' => [[$one, $one, $one], 3];
    }

    #[DataProvider('lists')]
    public function testListAcceptsZeroOneMany(mixed $raw, int $count): void
    {
        self::assertCount($count, Normalize::list($raw));
    }

    public function testListUnwrapsContainerProperty(): void
    {
        $records = (object)['dmRecord' => (object)['dmID' => '7']];
        self::assertSame('7', Normalize::list($records, 'dmRecord')[0]->dmID);
        self::assertSame([], Normalize::list((object)[], 'dmRecord'));
        self::assertSame([], Normalize::list(null, 'dmRecord'));
    }

    public function testScalars(): void
    {
        $o = (object)['a' => ' x ', 'n' => '12', 'b' => 'true', 'f' => false, 'empty' => ''];
        self::assertSame(' x ', Normalize::string($o, 'a'));
        self::assertNull(Normalize::string($o, 'missing'));
        self::assertNull(Normalize::string($o, 'empty'));
        self::assertSame(12, Normalize::int($o, 'n'));
        self::assertNull(Normalize::int($o, 'missing'));
        self::assertTrue(Normalize::bool($o, 'b'));
        self::assertFalse(Normalize::bool($o, 'f'));
        self::assertNull(Normalize::bool($o, 'missing'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function dates(): iterable
    {
        yield 'offset +02' => ['2026-09-17T10:15:30.123+02:00', '2026-09-17 08:15:30.123000'];
        yield 'zulu' => ['2026-09-17T08:15:30Z', '2026-09-17 08:15:30.000000'];
        yield 'no zone = Europe/Prague' => ['2026-01-10T10:00:00', '2026-01-10 09:00:00.000000'];
    }

    #[DataProvider('dates')]
    public function testDateTimeIsAlwaysUtc(string $raw, string $expectedUtc): void
    {
        $dt = Normalize::dateTime((object)['t' => $raw], 't');
        self::assertNotNull($dt);
        self::assertSame('UTC', $dt->getTimezone()->getName());
        self::assertSame($expectedUtc, $dt->format('Y-m-d H:i:s.u'));
    }

    public function testDateTimeNullAndGarbage(): void
    {
        self::assertNull(Normalize::dateTime((object)[], 't'));
        $this->expectException(\UnexpectedValueException::class);
        Normalize::dateTime((object)['t' => 'yesterday-ish'], 't');
    }

    public function testDateHasNoZoneShift(): void
    {
        $d = Normalize::date((object)['d' => '1989-12-29+01:00'], 'd');
        self::assertSame('1989-12-29 00:00:00 UTC', $d?->format('Y-m-d H:i:s T'));
        self::assertNull(Normalize::date((object)[], 'd'));
    }
}
