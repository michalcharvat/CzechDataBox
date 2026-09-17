<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz;

use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;
use PHPUnit\Framework\TestCase;

/** Real cURL round trip against a local `php -S` fake of the ws2 endpoint (tests/Support/vodz-server.php). */
final class VodzTransportHttpTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;
    private static string $base = '';

    public static function setUpBeforeClass(): void
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($probe);
        $port = (int)substr((string)strrchr((string)stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);
        $router = dirname(__DIR__, 3) . '/Support/vodz-server.php';
        $cmd = [PHP_BINARY, '-n', '-S', '127.0.0.1:' . $port, $router];
        self::$server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        self::$base = 'http://127.0.0.1:' . $port;
        for ($i = 0; $i < 100; $i++) {
            if ($c = @fsockopen('127.0.0.1', $port)) {
                fclose($c);
                return;
            }
            usleep(50_000);
        }
        self::fail('php -S did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    public function testStreamsUploadWithContentLengthAndParsesMtomResponse(): void
    {
        $payload = random_bytes(300_000);
        $in = fopen('php://memory', 'w+b');
        fwrite($in, $payload);
        $out = fopen('php://memory', 'w+b');

        [$doc, $cids] = (new VodzTransport(self::$base . '/DS/vodz', new PasswordCredentials('user1', 'pw'), new TransportOptions()))
            ->call('UploadAttachment', '<v20:Echo xmlns:v20="http://isds.czechpoint.cz/v20">{{cid0}}</v20:Echo>', [[$in, 'application/pdf']], static fn() => $out);

        $info = json_decode((string)$doc->getElementsByTagNameNS('http://isds.czechpoint.cz/v20', 'info')->item(0)?->textContent, true);
        self::assertIsArray($info);
        self::assertSame((string)$info['bodyLength'], $info['contentLength'], 'Content-Length matches the streamed body');
        self::assertNull($info['transferEncoding'], 'no chunked upload');
        self::assertStringContainsString('multipart/related', (string)$info['accept']);
        self::assertStringStartsWith('multipart/related; type="application/xop+xml"', (string)$info['contentType']);
        self::assertSame('UploadAttachment', $info['rootAction']);
        self::assertSame('user1', $info['auth']);
        self::assertSame(['1'], $cids);
        rewind($out);
        self::assertSame($payload, stream_get_contents($out));
    }

    public function testSoap12FaultOnHttp599BecomesIsdsException(): void
    {
        $this->expectException(IsdsException::class);
        $this->expectExceptionMessage('should be application/xop+xml');
        (new VodzTransport(self::$base . '/DS/fault', new PasswordCredentials('u', 'p'), new TransportOptions()))
            ->call('UploadAttachment', '<x/>', [], static fn() => fopen('php://memory', 'w+b'));
    }
}
