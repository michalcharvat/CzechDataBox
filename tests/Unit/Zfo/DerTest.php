<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Zfo;

use MichalCharvat\CzechDataBox\Exception\InvalidZfo;
use MichalCharvat\CzechDataBox\Zfo\Der;
use PHPUnit\Framework\TestCase;

final class DerTest extends TestCase
{
    public function testReadsNestedStructureAndLongLength(): void
    {
        // SEQUENCE { INTEGER 5, OCTET STRING "ab", [0] EXPLICIT { UTF8String "x" }, OCTET STRING (200 bytes, long form) }
        $long = str_repeat('z', 200);
        $inner = "\x02\x01\x05" . "\x04\x02ab" . "\xa0\x03\x0c\x01x" . "\x04\x81\xc8" . $long;
        $der = "\x30" . "\x81" . chr(strlen($inner)) . $inner;

        $seq = Der::read($der, 0);
        self::assertSame(['tag' => 0x10, 'class' => 0, 'constructed' => true, 'headerLen' => 3, 'len' => strlen($inner), 'offset' => 3], $seq);

        [$int, $oct, $ctx, $big] = Der::children($der, $seq);
        self::assertSame(2, $int['tag']);
        self::assertSame("\x05", Der::content($der, $int));
        self::assertSame('ab', Der::content($der, $oct));
        self::assertSame(2, $ctx['class']);
        self::assertTrue($ctx['constructed']);
        self::assertSame(0, $ctx['tag']);
        self::assertSame('x', Der::content($der, Der::children($der, $ctx)[0]));
        self::assertSame(3, $big['headerLen']);
        self::assertSame($long, Der::content($der, $big));
    }

    public function testRejectsIndefiniteAndOverlongLengths(): void
    {
        foreach (["\x30\x80\x00\x00", "\x04\x05abc", "\x04\x85\x01\x00\x00\x00\x00"] as $bad) {
            try {
                Der::read($bad, 0);
                self::fail('accepted ' . bin2hex($bad));
            } catch (InvalidZfo) {
                self::addToAssertionCount(1);
            }
        }
    }
}
