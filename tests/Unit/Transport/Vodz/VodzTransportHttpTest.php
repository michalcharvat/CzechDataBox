<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz;

use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;
use MichalCharvat\CzechDataBox\Tests\Support\FailingStream;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;
use PHPUnit\Framework\TestCase;

/** Real cURL round trip against a local `php -S` fake of the ws2 endpoint (tests/Support/vodz-server.php). */
final class VodzTransportHttpTest extends TestCase
{
    private static LocalVodzServer $server;
    private static string $base = '';

    public static function setUpBeforeClass(): void
    {
        self::$server = LocalVodzServer::start();
        self::$base = self::$server->url('');
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    public function testStreamsUploadWithContentLengthAndParsesMtomResponse(): void
    {
        $payload = random_bytes(300_000);
        $in = fopen('php://memory', 'w+b');
        fwrite($in, $payload);
        $out = fopen('php://memory', 'w+b');

        [$doc, $cids] = (new VodzTransport(self::$base . '/DS/vodz', new PasswordCredentials('user1', 'pw'), new TransportOptions()))
            ->call('BigMessageDownload', '<v20:Echo xmlns:v20="http://isds.czechpoint.cz/v20">{{cid0}}</v20:Echo>', [[$in, 'application/pdf']], static fn() => $out);

        $info = json_decode((string)$doc->getElementsByTagNameNS('http://isds.czechpoint.cz/v20', 'info')->item(0)?->textContent, true);
        self::assertIsArray($info);
        self::assertSame((string)$info['bodyLength'], $info['contentLength'], 'Content-Length matches the streamed body');
        self::assertNull($info['transferEncoding'], 'no chunked upload');
        self::assertStringContainsString('multipart/related', (string)$info['accept']);
        self::assertStringStartsWith('multipart/related; type="application/xop+xml"', (string)$info['contentType']);
        self::assertSame('BigMessageDownload', $info['rootAction']);
        self::assertSame('user1', $info['auth']);
        self::assertSame(['1'], $cids);
        rewind($out);
        self::assertSame($payload, stream_get_contents($out));
    }

    /**
     * A caller stream that dies mid-upload must surface its own error and not wait out the VoDZ timeout.
     * `php -S` answers a short body immediately, so this pins the error reporting and the absence of a
     * local stall; the XFERINFOFUNCTION abort matters against a server that waits for Content-Length and
     * can only be confirmed live.
     */
    public function testBrokenUploadStreamAbortsImmediately(): void
    {
        FailingStream::register();
        $in = fopen('failing://x', 'rb');
        self::assertIsResource($in);

        $started = microtime(true);
        try {
            (new VodzTransport(self::$base . '/DS/vodz', new PasswordCredentials('u', 'p'), new TransportOptions(vodzTimeout: 120)))
                ->call('BigMessageDownload', '<v20:Echo xmlns:v20="http://isds.czechpoint.cz/v20">{{cid0}}</v20:Echo>', [[$in, 'application/pdf']], static fn() => fopen('php://memory', 'w+b'));
            self::fail('expected the upload to fail');
        } catch (ServiceUnavailable $e) {
            self::assertStringContainsString('Cannot read MTOM attachment stream', $e->getMessage());
        }
        self::assertLessThan(20.0, microtime(true) - $started, 'must not hang until the timeout');
    }

    public function testSoap12FaultOnHttp599BecomesIsdsException(): void
    {
        $this->expectException(IsdsException::class);
        $this->expectExceptionMessage('should be application/xop+xml');
        (new VodzTransport(self::$base . '/DS/fault', new PasswordCredentials('u', 'p'), new TransportOptions()))
            ->call('BigMessageDownload', '<x/>', [], static fn() => fopen('php://memory', 'w+b'));
    }
}
