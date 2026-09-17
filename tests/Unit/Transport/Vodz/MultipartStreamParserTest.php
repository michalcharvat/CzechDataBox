<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz;

use MichalCharvat\CzechDataBox\Transport\Vodz\MultipartStreamParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultipartStreamParserTest extends TestCase
{
    private const CT = 'multipart/related; type="application/xop+xml"; boundary="uuid:b1"; start="<root@isds>"';

    private static function body(string $binary): string
    {
        return "--uuid:b1\r\nContent-Type: application/xop+xml; charset=UTF-8; type=\"application/soap+xml\"\r\n"
            . "Content-ID: <root@isds>\r\n\r\n"
            . '<env><dmEncodedContent><xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:bin1@isds"/></dmEncodedContent></env>'
            . "\r\n--uuid:b1\r\nContent-Type: application/octet-stream\r\nContent-ID: <bin1@isds>\r\n\r\n"
            . $binary . "\r\n--uuid:b1--\r\n";
    }

    /** @return iterable<string, array{int}> */
    public static function chunkSizes(): iterable
    {
        foreach ([1, 2, 3, 777, 65536] as $n) {
            yield (string)$n => [$n];
        }
    }

    #[DataProvider('chunkSizes')]
    public function testParsesRootAndStreamsBinaryAcrossChunkBoundaries(int $chunkSize): void
    {
        $binary = random_bytes(20_000) . "\r\n--uuid:bX" . random_bytes(1000); // near-miss delimiter inside data (fixed "X", never the real "1")
        $sink = fopen('php://memory', 'w+b');
        $parser = new MultipartStreamParser(self::CT, static fn(string $cid) => $sink);

        foreach (str_split(self::body($binary), $chunkSize) as $chunk) {
            $parser->write($chunk);
        }
        $parser->finish();

        self::assertStringContainsString('cid:bin1@isds', $parser->rootXml());
        rewind($sink);
        self::assertSame($binary, stream_get_contents($sink));
        self::assertSame(['bin1@isds'], $parser->binaryContentIds());
    }

    /** The WS manual's DownloadAttachment sample ends with the delimiter but without the closing "--". */
    public function testMissingCloseDelimiterAfterLastPartIsAccepted(): void
    {
        $sink = fopen('php://memory', 'w+b');
        $parser = new MultipartStreamParser(self::CT, static fn() => $sink);
        $parser->write(substr(self::body('abc'), 0, -strlen("--\r\n")));
        $parser->finish();
        rewind($sink);
        self::assertSame('abc', stream_get_contents($sink));
    }

    public function testIsdsSampleHeadersStartWithoutQuotesAndContentIdCase(): void
    {
        $ct = 'multipart/related; start="<rootpart>"; type="application/xop+xml"; boundary="==1927659895719436937=="; start-info="application/soap+xml"';
        $body = "--==1927659895719436937==\r\nContent-Id: <rootpart>\r\nContent-Type: application/xop+xml;charset=utf-8;type=\"application/soap+xml\"\r\n\r\n<x/>"
            . "\r\n--==1927659895719436937==\r\nContent-Id: <1>\r\nContent-Type: application/pdf\r\n\r\n%PDF-1.5\r\n--==1927659895719436937==\r\n";
        $sink = fopen('php://memory', 'w+b');
        $parser = new MultipartStreamParser($ct, static fn() => $sink);
        $parser->write($body);
        $parser->finish();
        self::assertSame('<x/>', $parser->rootXml());
        self::assertSame(['1'], $parser->binaryContentIds());
    }

    public function testRootPartSizeIsBounded(): void
    {
        $parser = new MultipartStreamParser(self::CT, static fn() => fopen('php://memory', 'w+b'), maxRootBytes: 100);
        $this->expectException(\RuntimeException::class);
        $parser->write(self::body('x'));
        $parser->finish();
    }

    public function testTruncatedBodyFails(): void
    {
        $parser = new MultipartStreamParser(self::CT, static fn() => fopen('php://memory', 'w+b'));
        $parser->write(substr(self::body('abc'), 0, -20));
        $this->expectException(\RuntimeException::class);
        $parser->finish();
    }
}
