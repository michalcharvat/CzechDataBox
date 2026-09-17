<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Exception\NotYetDelivered;
use MichalCharvat\CzechDataBox\Exception\RateLimited;
use MichalCharvat\CzechDataBox\Input\ListFilter;
use MichalCharvat\CzechDataBox\Service\MessageInfo;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MessageInfoTest extends TestCase
{
    /** @return iterable<string, array{string, int}> */
    public static function listCases(): iterable
    {
        yield 'zero' => ['zero', 0];
        yield 'one' => ['one', 1];
        yield 'many' => ['many', 3];
        yield 'nil' => ['nil', 0];
    }

    #[DataProvider('listCases')]
    public function testListOfReceivedMessages(string $case, int $count): void
    {
        $t = FakeTransport::for(Service::Info)->reply('GetListOfReceivedMessages', $case);
        $from = new \DateTimeImmutable('2026-09-01T00:00:00Z');
        $to = new \DateTimeImmutable('2026-09-02T00:00:00Z');

        $records = (new MessageInfo($t))->getListOfReceivedMessages(new ListFilter($from, $to, limit: 1000));

        self::assertCount($count, $records);
        if ($count === 3) {
            self::assertTrue($records[1]->dmVODZ);
            self::assertSame('7800001', $records[0]->dmID);
        }
        $req = $t->lastRequestXml();
        self::assertStringContainsString('2026-09-01T00:00:00.000Z', $req);
        self::assertStringContainsString('>1000<', $req);
        self::assertStringContainsString('>-1<', $req, 'status filter defaults to all');
        // ext-soap nil-fills the required-but-unset elements, which ISDS reads as "no filter"
        self::assertStringContainsString('<ns1:dmOffset xsi:nil="true"/>', $req);
    }

    public function testDeliveryInfoWithEvents(): void
    {
        $t = FakeTransport::for(Service::Info)->reply('GetDeliveryInfo', 'ok');
        $d = (new MessageInfo($t))->getDeliveryInfo('1234567');
        self::assertSame('1234567', $d->envelope->record->dmID);
        self::assertCount(2, $d->events);
        self::assertSame('EV5', $d->events[1]->code());
        self::assertSame('2026-09-01 08:00:00.123', $d->envelope->record->dmDeliveryTime?->format('Y-m-d H:i:s.v'));
        self::assertSame('SHA-256', $d->envelope->dmHashAlgorithm);
        self::assertSame(6, $d->envelope->record->dmMessageStatus);
    }

    public function testSignedDeliveryInfoReturnsBytes(): void
    {
        $t = FakeTransport::for(Service::Info)->reply('GetSignedDeliveryInfo', 'ok');
        self::assertNotSame('', (new MessageInfo($t))->getSignedDeliveryInfo('1')->bytes);
    }

    public function testStatusErrorsSurface(): void
    {
        $t = FakeTransport::for(Service::Info)->reply('GetListOfReceivedMessages', 'err-3009');
        $this->expectException(RateLimited::class);
        (new MessageInfo($t))->getListOfReceivedMessages(new ListFilter(new \DateTimeImmutable('-1 day'), new \DateTimeImmutable()));
    }

    public function testEnvelopeDownloadNotYetDelivered(): void
    {
        $t = FakeTransport::for(Service::Info)->reply('MessageEnvelopeDownload', 'err-1222');
        $this->expectException(NotYetDelivered::class);
        (new MessageInfo($t))->messageEnvelopeDownload('1');
    }

    public function testStateChangesAndAuthor(): void
    {
        $t = FakeTransport::for(Service::Info)
            ->reply('GetMessageStateChanges', 'many')
            ->reply('GetMessageAuthor2', 'ok');
        $svc = new MessageInfo($t);
        self::assertCount(3, $svc->getMessageStateChanges(new \DateTimeImmutable('-1 day'), null));
        self::assertSame('PRIMARY_USER', $svc->getMessageAuthor2('1')->userType);
    }
}
