<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz;

use MichalCharvat\CzechDataBox\Transport\Vodz\MultipartStreamParser;
use MichalCharvat\CzechDataBox\Transport\Vodz\MultipartWriter;
use PHPUnit\Framework\TestCase;

final class MultipartWriterTest extends TestCase
{
    public function testRoundTripThroughParser(): void
    {
        $file = fopen('php://memory', 'w+b');
        fwrite($file, $payload = random_bytes(50_000));
        rewind($file);

        $w = new MultipartWriter();
        $cid = $w->addBinary($file, 'application/pdf');
        $body = $w->build('<Envelope>' . MultipartWriter::xopInclude($cid) . '</Envelope>');

        $sink = fopen('php://memory', 'w+b');
        $p = new MultipartStreamParser($w->contentType(), static fn() => $sink);
        $sent = 0;
        while (($chunk = $body->read(8192)) !== '') {
            $sent += strlen($chunk);
            $p->write($chunk);
        }
        $p->finish();

        self::assertSame($body->length(), $sent, 'declared Content-Length equals the bytes actually streamed');

        self::assertStringContainsString('cid:' . $cid, $p->rootXml());
        rewind($sink);
        self::assertSame($payload, stream_get_contents($sink));
    }
}
