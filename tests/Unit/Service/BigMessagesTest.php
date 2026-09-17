<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;
use MichalCharvat\CzechDataBox\Exception\WrongMessageKind;
use MichalCharvat\CzechDataBox\Input\ExtFileInput;
use MichalCharvat\CzechDataBox\Input\FileInput;
use MichalCharvat\CzechDataBox\Input\MessageEnvelopeInput;
use MichalCharvat\CzechDataBox\Service\BigMessages;
use MichalCharvat\CzechDataBox\Tests\Support\FakeVodzTransport;
use PHPUnit\Framework\TestCase;

final class BigMessagesTest extends TestCase
{
    /** @return resource */
    private static function stream(string $data)
    {
        $s = fopen('php://memory', 'w+b');
        fwrite($s, $data);
        rewind($s);
        return $s;
    }

    public function testUploadAttachmentStreamsAndReturnsReference(): void
    {
        $t = (new FakeVodzTransport())->reply('UploadAttachment', 'upload-ok');

        $ref = (new BigMessages($t))->uploadAttachment(self::stream('BIGDATA'), 'application/pdf', 'big <1>.pdf');

        self::assertSame('54520', $ref->dmAttID);
        self::assertSame('SHA-256', $ref->dmAttHash1Alg);
        self::assertSame('SHA3-256', $ref->dmAttHash2Alg);
        self::assertSame('BIGDATA', $t->uploadedBinaries[0]);
        self::assertStringContainsString('dmFileDescr="big &lt;1&gt;.pdf"', $t->lastBodyXml);
        self::assertStringContainsString('<p:dmEncodedContent>{{cid0}}</p:dmEncodedContent>', $t->lastBodyXml);
    }

    public function testSignedBigMessageDownloadStreamsIntoSink(): void
    {
        $t = (new FakeVodzTransport())->reply('SignedBigMessageDownload', 'signed-ok', binary: 'ZFOBYTES');
        $sink = fopen('php://memory', 'w+b');

        (new BigMessages($t))->signedBigMessageDownload('123', $sink);

        rewind($sink);
        self::assertSame('ZFOBYTES', stream_get_contents($sink));
    }

    public function testInlineBase64FallbackIsDecodedIntoSink(): void
    {
        $t = (new FakeVodzTransport())->reply('SignedBigMessageDownload', 'signed-inline');
        $sink = fopen('php://memory', 'w+b');
        (new BigMessages($t))->signedBigMessageDownload('123', $sink);
        rewind($sink);
        self::assertSame('ZFOBYTES', stream_get_contents($sink));
    }

    public function testReferencedMtomPartMustHaveArrived(): void
    {
        $t = (new FakeVodzTransport())->reply('SignedBigMessageDownload', 'signed-missing-part', binary: 'X');
        $this->expectException(ServiceUnavailable::class);
        (new BigMessages($t))->signedBigMessageDownload('123', fopen('php://memory', 'w+b'));
    }

    public function testSignedSentBigMessageDownload(): void
    {
        $t = (new FakeVodzTransport())->reply('SignedSentBigMessageDownload', 'signed-sent-ok', binary: 'SENT-ZFO');
        $sink = fopen('php://memory', 'w+b');
        (new BigMessages($t))->signedSentBigMessageDownload('123', $sink);
        self::assertSame(['SignedSentBigMessageDownload'], $t->operations);
        self::assertStringContainsString('<p:SignedSentBigMessageDownload xmlns:p="http://isds.czechpoint.cz/v20"><p:dmID>123</p:dmID>', $t->lastBodyXml);
        rewind($sink);
        self::assertSame('SENT-ZFO', stream_get_contents($sink));
    }

    public function testStatusCodeStillMapped(): void
    {
        $t = (new FakeVodzTransport())->reply('BigMessageDownload', 'err-1281');
        $this->expectException(WrongMessageKind::class);
        (new BigMessages($t))->bigMessageDownload('1', static fn() => fopen('php://memory', 'w+b'));
    }

    public function testBigMessageDownloadMapsStreamedAndInlineFiles(): void
    {
        $t = (new FakeVodzTransport())->reply('BigMessageDownload', 'big-download', binary: '%PDF big');
        $sinks = [];
        $big = (new BigMessages($t))->bigMessageDownload('1544602', static function (string $cid) use (&$sinks) {
            return $sinks[$cid] = fopen('php://memory', 'w+b');
        });

        self::assertSame('1544602', $big->message->envelope->record->dmID);
        self::assertTrue($big->message->envelope->record->dmVODZ);
        self::assertCount(2, $big->message->files);
        self::assertSame(['fixture@cid', null], $big->contentIds);
        self::assertSame('', $big->message->files[0]->content);
        self::assertSame('ahoj', $big->message->files[1]->content);
        rewind($sinks['fixture@cid']);
        self::assertSame('%PDF big', stream_get_contents($sinks['fixture@cid']));
    }

    public function testDownloadAttachmentReturnsMetadataAndStreams(): void
    {
        $t = (new FakeVodzTransport())->reply('DownloadAttachment', 'download-attachment', binary: 'PDF');
        $sink = fopen('php://memory', 'w+b');
        $meta = (new BigMessages($t))->downloadAttachment('1544602', 0, $sink);
        self::assertSame('big.pdf', $meta->dmFileDescr);
        self::assertStringContainsString('<p:attNum>0</p:attNum>', $t->lastBodyXml);
        rewind($sink);
        self::assertSame('PDF', stream_get_contents($sink));
    }

    public function testCreateBigMessageRequestXml(): void
    {
        $t = (new FakeVodzTransport())->reply('UploadAttachment', 'upload-ok')->reply('CreateBigMessage', 'create-ok');
        $svc = new BigMessages($t);
        $ref = $svc->uploadAttachment(self::stream('x'), 'application/pdf', 'big.pdf');

        $created = $svc->createBigMessage(
            new MessageEnvelopeInput(dmAnnotation: 'Velká & zpráva', dbIDRecipient: 'abc1234', dmPersonalDelivery: false, dmType: 'K', dmPublishOwnID: 1),
            [new ExtFileInput($ref, 'main')],
            [new FileInput('ahoj', 'text/plain', 'small.txt', 'enclosure')],
        );

        self::assertSame('7654399', $created->dmID);
        $xml = $t->lastBodyXml;
        self::assertMatchesRegularExpression('#<p:dmEnvelope dmType="K"><p:dbIDRecipient>abc1234</p:dbIDRecipient><p:dmAnnotation>Velká &amp; zpráva</p:dmAnnotation><p:dmPersonalDelivery>false</p:dmPersonalDelivery><p:dmPublishOwnID IdLevel="1">true</p:dmPublishOwnID></p:dmEnvelope>#', $xml);
        self::assertStringContainsString('<p:dmExtFile dmFileMetaType="main" dmAttID="54520" dmAttHash1="e226488b85c22f80bc0bb91580e09292c83ba70222201ce6a1473c5a2dfc2ae3" dmAttHash1Alg="SHA-256"', $xml);
        self::assertStringContainsString('<p:dmFile dmFileMetaType="enclosure" dmFileDescr="small.txt" dmMimeType="text/plain"><p:dmEncodedContent>' . base64_encode('ahoj') . '</p:dmEncodedContent></p:dmFile>', $xml);
        self::assertLessThan(strpos($xml, '<p:dmFile '), strpos($xml, '<p:dmExtFile '), 'dmExtFile elements precede dmFile');
    }

    public function testCreateBigMessageNeedsMainFirstAndRecipient(): void
    {
        $t = (new FakeVodzTransport())->reply('UploadAttachment', 'upload-ok');
        $svc = new BigMessages($t);
        $ref = $svc->uploadAttachment(self::stream('x'), 'application/pdf', 'big.pdf');
        $this->expectException(\InvalidArgumentException::class);
        $svc->createBigMessage(new MessageEnvelopeInput(dmAnnotation: 'x', dbIDRecipient: 'abc1234'), [new ExtFileInput($ref, 'enclosure')]);
    }

    public function testAuthenticateBigMessage(): void
    {
        $t = (new FakeVodzTransport())->reply('AuthenticateBigMessage', 'auth-ok');
        self::assertTrue((new BigMessages($t))->authenticateBigMessage(self::stream('ZFO')));
        self::assertSame('ZFO', $t->uploadedBinaries[0]);
    }
}
