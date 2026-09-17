<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Service\Access;
use MichalCharvat\CzechDataBox\Service\Manipulations;
use MichalCharvat\CzechDataBox\Service\MessageInfo;
use MichalCharvat\CzechDataBox\Service\MessageOperations;
use MichalCharvat\CzechDataBox\Service\PasswordChange;
use MichalCharvat\CzechDataBox\Service\Search;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

/**
 * The request each service method builds, for the operations whose parameters are typed or reshaped.
 * The fixtures are parsed by real ext-soap over the bundled WSDL, so a request that the schema rejects
 * cannot pass here either.
 */
final class RequestShapeTest extends TestCase
{
    public function testMessageInfoSimpleIdOperations(): void
    {
        $t = FakeTransport::for(Service::Info)
            ->reply('MarkMessageAsDownloaded', 'ok')
            ->reply('EraseMessage', 'ok')
            ->reply('SentMessageEnvelopeDownload', 'ok')
            ->reply('GetMessageAuthor', 'ok');
        $svc = new MessageInfo($t);

        $svc->markMessageAsDownloaded('1234567');
        self::assertStringContainsString('<ns1:MarkMessageAsDownloaded><ns1:dmID>1234567</ns1:dmID>', $t->lastRequestXml());

        $svc->eraseMessage('1234567', true);
        self::assertStringContainsString('<ns1:dmIncoming>true</ns1:dmIncoming>', $t->lastRequestXml());

        $env = $svc->sentMessageEnvelopeDownload('7654321');
        self::assertSame('7654321', $env->record->dmID);
        self::assertSame('hash', $env->dmHash, 'dmHash is decoded by ext-soap');
        self::assertSame(1, $env->record->attsNum);

        self::assertSame('Jan Novák', $svc->getMessageAuthor('1')->authorName);
    }

    public function testErasedMessagesIsAsynchronous(): void
    {
        $t = FakeTransport::for(Service::Info)->reply('GetListOfErasedMessages', 'ok')->reply('PickUpAsyncResponse', 'ok');
        $svc = new MessageInfo($t);

        $async = $svc->getListOfErasedMessages(['dmYear' => 2026, 'dmMessageType' => 'RECEIVED', 'dmOutFormat' => 'XML']);
        self::assertSame('async-42', $async->asyncID);
        self::assertStringContainsString('<ns1:dmMessageType>RECEIVED</ns1:dmMessageType>', $t->lastRequestXml());

        $result = $svc->pickUpAsyncResponse('async-42', 'LIST_ERASED');
        self::assertSame('LIST_ERASED', $result->asyncReqType);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:asyncID>async-42</ns1:asyncID>', $xml);
        self::assertStringContainsString('<ns1:asyncReqType>LIST_ERASED</ns1:asyncReqType>', $xml);
    }

    public function testResignDocumentSendsBase64AndReturnsBytes(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('Re-signISDSDocument', 'ok');
        $doc = (new MessageOperations($t))->resignIsdsDocument('ZFO-BYTES');
        self::assertSame('RESIGNED', $doc->bytes);
        self::assertStringContainsString('<ns1:dmDoc>' . base64_encode('ZFO-BYTES') . '</ns1:dmDoc>', $t->lastRequestXml());
    }

    public function testIsdsSearch2SendsOnlyGivenParameters(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('ISDSSearch2', 'ok');
        (new Search($t))->isdsSearch2('Úřad', 'GENERAL', 'OVM', page: 0, pageSize: 20);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:searchText>Úřad</ns1:searchText>', $xml);
        self::assertStringContainsString('<ns1:searchType>GENERAL</ns1:searchType>', $xml);
        self::assertStringContainsString('<ns1:page>0</ns1:page>', $xml);
        self::assertStringNotContainsString('highlighting', $xml);
    }

    public function testDeprecatedV1AccessOperationsStillWork(): void
    {
        $t = FakeTransport::for(Service::Access)->reply('GetOwnerInfoFromLogin', 'ok');
        $raw = (new Access($t))->getOwnerInfoFromLogin();
        self::assertSame('ACME s.r.o.', $raw->dbOwnerInfo->firmName);
        self::assertStringContainsString('<ns1:dbDummy></ns1:dbDummy>', $t->lastRequestXml());
    }

    public function testManipulationsPassesParamsThrough(): void
    {
        $t = FakeTransport::for(Service::Manipulations)->reply('UpdateDataBoxDescr2', 'ok');
        (new Manipulations($t))->updateDataBoxDescr2([
            'dbID' => 'abc1234',
            'dbNewOwnerInfo' => ['dbID' => 'abc1234', 'firmName' => 'ACME s.r.o.'],
        ]);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:dbID>abc1234</ns1:dbID>', $xml);
        self::assertStringContainsString('<ns1:firmName>ACME s.r.o.</ns1:firmName>', $xml);
    }

    public function testChangePasswordOtpSendsOtpType(): void
    {
        $t = FakeTransport::for(Service::ChangePassword)->reply('ChangePasswordOTP', 'ok');
        (new PasswordChange($t))->changePasswordOtp('Old-Password-1', 'New-Password-2', 'TOTP');
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:dbOTPType>TOTP</ns1:dbOTPType>', $xml);
        self::assertStringContainsString('<ns1:dbNewPassword>New-Password-2</ns1:dbNewPassword>', $xml);
    }
}
