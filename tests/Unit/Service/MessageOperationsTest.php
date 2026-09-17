<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Exception\NotAvailableYet;
use MichalCharvat\CzechDataBox\Input\FileInput;
use MichalCharvat\CzechDataBox\Input\MessageEnvelopeInput;
use MichalCharvat\CzechDataBox\Service\MessageOperations;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class MessageOperationsTest extends TestCase
{
    public function testCreateMessageSendsEnvelopeAndBase64Files(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('CreateMessage', 'ok');
        $env = new MessageEnvelopeInput(dbIDRecipient: 'abc1234', dmAnnotation: 'Faktura 1', dmPersonalDelivery: false);
        $created = (new MessageOperations($t))->createMessage($env, [
            new FileInput('%PDF-1.4 x', 'application/pdf', 'faktura.pdf', 'main'),
        ]);

        self::assertSame('7654321', $created->dmID);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:dbIDRecipient>abc1234</ns1:dbIDRecipient>', $xml, 'envelope fields are elements (tMessageEnvelopeSub)');
        self::assertStringContainsString('dmFileDescr="faktura.pdf"', $xml);
        self::assertStringContainsString(base64_encode('%PDF-1.4 x'), $xml);
    }

    public function testFirstFileMustBeMain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MessageOperations(FakeTransport::for(Service::Operations)))->createMessage(
            new MessageEnvelopeInput(dbIDRecipient: 'abc1234', dmAnnotation: 'b'), // valid id: the failure must come from the file order
            [new FileInput('x', 'text/plain', 'a.txt', 'enclosure')],
        );
    }

    public function testMessageDownloadParsesFiles(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('MessageDownload', 'two-files');
        $m = (new MessageOperations($t))->messageDownload('1');
        self::assertCount(2, $m->files);
        self::assertSame('1234567', $m->envelope->record->dmID);
        self::assertSame('%PDF-1.4 fake', $m->files[0]->content, 'ext-soap base64-decodes dmEncodedContent');
        self::assertTrue($m->files[0]->isMain());
        self::assertSame('K', $m->envelope->record->dmType);
        self::assertSame('FV-15', $m->envelope->record->dmSenderRefNumber);
    }

    public function testSignedDownloadsAndErrors(): void
    {
        $t = FakeTransport::for(Service::Operations)
            ->reply('SignedMessageDownload', 'ok')
            ->reply('SignedSentMessageDownload', 'err-1229');
        $svc = new MessageOperations($t);
        self::assertNotSame('', $svc->signedMessageDownload('1')->bytes);
        $this->expectException(NotAvailableYet::class);
        $svc->signedSentMessageDownload('2');
    }

    public function testAuthenticateMessage(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('AuthenticateMessage', 'valid');
        self::assertTrue((new MessageOperations($t))->authenticateMessage('ZFOBYTES'));
    }

    public function testMultipleMessagePartialFailureReturnsPerRecipientStatus(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('CreateMultipleMessage', 'partial-0004');
        $raw = (new MessageOperations($t))->createMultipleMessage(
            new MessageEnvelopeInput(dmAnnotation: 'Oběžník'),
            ['abc1234', 'xyz9876'],
            [new FileInput('x', 'text/plain', 'a.txt', 'main')],
        );
        self::assertCount(2, $raw->dmMultipleStatus->dmSingleStatus);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:dbIDRecipient>xyz9876</ns1:dbIDRecipient>', $xml);
    }

    public function testEnvelopeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MessageEnvelopeInput(dbIDRecipient: 'ABC', dmAnnotation: 'x');
    }

    public function testCreateMessageRequiresRecipient(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MessageOperations(FakeTransport::for(Service::Operations)))->createMessage(
            new MessageEnvelopeInput(dmAnnotation: 'x'),
            [new FileInput('x', 'text/plain', 'a.txt', 'main')],
        );
    }

    public function testPublishOwnIdAndDmTypeEncoding(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('CreateMessage', 'ok');
        (new MessageOperations($t))->createMessage(
            new MessageEnvelopeInput(dbIDRecipient: 'abc1234', dmAnnotation: 'x', dmType: 'I', dmPublishOwnID: 3),
            [new FileInput('x', 'text/plain', 'a.txt', 'main')],
        );
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('dmType="I"', $xml);
        self::assertStringContainsString('<ns1:dmPublishOwnID IdLevel="3">true</ns1:dmPublishOwnID>', $xml);
    }
}
