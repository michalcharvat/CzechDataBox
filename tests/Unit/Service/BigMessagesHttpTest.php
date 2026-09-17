<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Service\BigMessages;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;
use MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz\LocalVodzServer;
use PHPUnit\Framework\TestCase;

/**
 * BigMessages through the real MultipartWriter/MultipartStreamParser and cURL against a local fake ws2,
 * i.e. the seam that FakeVodzTransport skips: a non-MTOM answer, and an MTOM answer whose Content-ID is
 * percent-escaped in the xop:Include href.
 */
final class BigMessagesHttpTest extends TestCase
{
    private static LocalVodzServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = LocalVodzServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    private function service(): BigMessages
    {
        return new BigMessages(new VodzTransport(self::$server->url('/DS/vodz'), new PasswordCredentials('u', 'p'), new TransportOptions()));
    }

    public function testUploadAttachmentOverRealTransportWithNonMtomAnswer(): void
    {
        $content = random_bytes(200_000);
        $in = fopen('php://memory', 'w+b');
        fwrite($in, $content);

        $ref = $this->service()->uploadAttachment($in, 'application/pdf', 'big.pdf');

        self::assertSame('54520', $ref->dmAttID);
        self::assertSame(hash('sha256', $content), $ref->dmAttHash1, 'the server hashed exactly what we streamed');
        self::assertSame('SHA3-256', $ref->dmAttHash2Alg);
    }

    public function testSignedDownloadStreamsTheMtomPartIntoTheSink(): void
    {
        $sink = fopen('php://memory', 'w+b');
        $this->service()->signedBigMessageDownload('1544602', $sink);
        rewind($sink);
        self::assertSame(str_repeat('ZFO', 100_000), stream_get_contents($sink));
    }
}
