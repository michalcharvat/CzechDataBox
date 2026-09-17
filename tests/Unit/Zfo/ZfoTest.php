<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Zfo;

use MichalCharvat\CzechDataBox\Exception\InvalidZfo;
use MichalCharvat\CzechDataBox\Tests\Support\ZfoFactory;
use MichalCharvat\CzechDataBox\Zfo\CmsUnwrapper;
use MichalCharvat\CzechDataBox\Zfo\Zfo;
use MichalCharvat\CzechDataBox\Zfo\ZfoKind;
use MichalCharvat\CzechDataBox\Zfo\ZfoLimits;
use MichalCharvat\CzechDataBox\Zfo\ZfoXmlParser;
use PHPUnit\Framework\TestCase;

final class ZfoTest extends TestCase
{
    public function testParseReceivedMessage(): void
    {
        $zfo = ZfoFactory::sign(ZfoFactory::receivedMessage('1234567', [
            ['a.pdf', 'application/pdf', '%PDF-1.4 main'],
            ['b.txt', 'text/plain', 'příloha'],
        ]));

        self::assertSame(ZfoKind::ReceivedMessage, Zfo::kind($zfo));
        $m = Zfo::parse($zfo);

        self::assertSame('1234567', $m->envelope->record->dmID);
        self::assertSame(6, $m->envelope->record->dmMessageStatus);
        self::assertSame('SHA-256', $m->envelope->dmHashAlgorithm);
        self::assertCount(2, $m->files);
        self::assertSame('%PDF-1.4 main', $m->files[0]->content);
        self::assertSame('příloha', $m->files[1]->content);
        self::assertTrue($m->files[0]->isMain());
        self::assertSame('V', $m->envelope->record->dmType);
        self::assertSame('2026-09-01 08:00:00', $m->envelope->record->dmDeliveryTime?->format('Y-m-d H:i:s'));
    }

    public function testKindAcceptsBothIsdsNamespaceHosts(): void
    {
        $xml = str_replace('isds.czebox.cz', 'isds.czechpoint.cz', ZfoFactory::receivedMessage('1', [['a', 'text/plain', 'x']]));
        self::assertSame(ZfoKind::ReceivedMessage, Zfo::kind(ZfoFactory::sign($xml)));
        $this->expectException(InvalidZfo::class);
        Zfo::kind(ZfoFactory::sign('<x xmlns="http://example.com/v20/message"/>'));
    }

    public function testDoctypeSplitAcrossChunksIsRejected(): void
    {
        $parser = new ZfoXmlParser(new ZfoLimits(), static fn() => fopen('php://memory', 'w+b'));
        $this->expectException(InvalidZfo::class);
        $parser->feed('<?xml version="1.0"?><!DOC');
        $parser->feed('TYPE x [<!ENTITY e "boom">]><x>&e;</x>', true);
    }

    public function testParseDeliveryInfoRejectsMessage(): void
    {
        $this->expectException(InvalidZfo::class);
        Zfo::parseDeliveryInfo(ZfoFactory::sign(ZfoFactory::receivedMessage('1', [['a', 'text/plain', 'x']])));
    }

    public function testKindSentAndDelivery(): void
    {
        self::assertSame(ZfoKind::SentMessage, Zfo::kind(ZfoFactory::sign(ZfoFactory::receivedMessage('1', [['a', 'text/plain', 'x']], ZfoFactory::NS_SENT))));
        $d = ZfoFactory::sign(ZfoFactory::deliveryInfo('55'));
        self::assertSame(ZfoKind::DeliveryInfo, Zfo::kind($d));
        $info = Zfo::parseDeliveryInfo($d);
        self::assertSame('55', $info->envelope->record->dmID);
        self::assertSame('EV5', $info->events[0]->code());
    }

    public function testParseStreamWritesFilesToSinks(): void
    {
        $big = random_bytes(3_000_000);
        $zfo = ZfoFactory::sign(ZfoFactory::receivedMessage('9', [['big.bin', 'application/octet-stream', $big]]));
        $in = fopen('php://memory', 'w+b');
        fwrite($in, $zfo);
        rewind($in);
        $sinks = [];

        $env = Zfo::parseStream($in, function (array $meta) use (&$sinks) {
            return $sinks[$meta['dmFileDescr']] = fopen('php://memory', 'w+b');
        });

        self::assertSame('9', $env->record->dmID);
        rewind($sinks['big.bin']);
        self::assertSame($big, stream_get_contents($sinks['big.bin']));
    }

    public function testSignerInfoExposed(): void
    {
        $info = Zfo::signer(ZfoFactory::sign(ZfoFactory::deliveryInfo('1')));
        self::assertNotNull($info->signingTime);
        self::assertNotNull($info->certificateValidTo);
    }

    public function testRejectsDoctype(): void
    {
        $xml = '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e "boom">]><x>&e;</x>';
        $this->expectException(InvalidZfo::class);
        Zfo::parse(ZfoFactory::sign($xml));
    }

    public function testRejectsGarbage(): void
    {
        $this->expectException(InvalidZfo::class);
        Zfo::parse('not a cms structure');
    }

    public function testFileCountAndSizeLimits(): void
    {
        $zfo = ZfoFactory::sign(ZfoFactory::receivedMessage('1', [['a', 'text/plain', 'x'], ['b', 'text/plain', 'y']]));
        try {
            Zfo::parse($zfo, new ZfoLimits(maxFiles: 1));
            self::fail('file count limit not enforced');
        } catch (InvalidZfo) {
        }
        $this->expectException(InvalidZfo::class);
        Zfo::parse(ZfoFactory::sign(ZfoFactory::receivedMessage('1', [['a', 'text/plain', str_repeat('x', 2000)]])), new ZfoLimits(maxFileBytes: 1000));
    }

    public function testDepthAndElementLimits(): void
    {
        $deep = '<?xml version="1.0"?>' . str_repeat('<a>', 200) . str_repeat('</a>', 200);
        $this->expectException(InvalidZfo::class);
        Zfo::parse(ZfoFactory::sign($deep), new ZfoLimits(maxDepth: 64));
    }

    public function testDerFallbackMatchesOpenssl(): void
    {
        $inner = ZfoFactory::receivedMessage('1', [['a', 'text/plain', 'x']]);
        $zfo = ZfoFactory::sign($inner);
        self::assertSame($inner, (new CmsUnwrapper(forceDerFallback: true))->content($zfo));
        self::assertSame($inner, (new CmsUnwrapper())->content($zfo));
    }

    public function testTempFilesAreRemoved(): void
    {
        $dir = sys_get_temp_dir() . '/zfo-test-' . bin2hex(random_bytes(4));
        mkdir($dir, 0700);
        (new CmsUnwrapper(tempDir: $dir))->content(ZfoFactory::sign('<x/>'));
        self::assertSame([], array_values(array_diff(scandir($dir) ?: [], ['.', '..'])));
        rmdir($dir);
    }
}
