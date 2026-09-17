<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Live;

use MichalCharvat\CzechDataBox\Exception\NotAvailableYet;
use MichalCharvat\CzechDataBox\Input\FileInput;
use MichalCharvat\CzechDataBox\Input\ListFilter;
use MichalCharvat\CzechDataBox\Input\MessageEnvelopeInput;
use MichalCharvat\CzechDataBox\Zfo\Zfo;
use MichalCharvat\CzechDataBox\Zfo\ZfoKind;

final class MessagesLiveTest extends LiveTestCase
{
    public function testSendToOwnOrGivenBoxAndSeeItInSentList(): void
    {
        $conn = $this->passwordConnection();
        $recipient = getenv('ISDS_TEST_RECIPIENT_DBID') ?: self::env('ISDS_TEST_SELF_DBID');
        $annotation = 'czech-data-box 2.x live ' . date('c');

        $created = $conn->messageOperations()->createMessage(
            new MessageEnvelopeInput(dmAnnotation: $annotation, dbIDRecipient: $recipient),
            [new FileInput(self::tinyPdf('live test'), 'application/pdf', 'live-test.pdf', 'main')],
        );
        self::assertMatchesRegularExpression('/^\d+$/', $created->dmID);

        $found = false;
        for ($i = 0; $i < 10 && !$found; $i++) {
            sleep($i === 0 ? 0 : 3);
            $sent = $conn->messageInfo()->getListOfSentMessages(new ListFilter(new \DateTimeImmutable('-1 hour'), new \DateTimeImmutable('+1 minute')));
            foreach ($sent as $r) {
                $found = $found || $r->dmID === $created->dmID;
            }
        }
        self::assertTrue($found, 'created message appears in the sent list');
    }

    public function testSentMessageDeliveryInfoSignedDownloadAndZfo(): void
    {
        $conn = $this->passwordConnection();
        $sent = $conn->messageInfo()->getListOfSentMessages(new ListFilter(new \DateTimeImmutable('-90 days'), new \DateTimeImmutable()));
        $record = null;
        foreach ($sent as $r) {
            if (($r->dmMessageStatus ?? 0) >= 4) {
                $record = $r;
                break;
            }
        }
        if ($record === null) {
            self::markTestSkipped('no sent message in state ≥ 4 within 90 days (run the send test first, wait a minute)');
        }

        $info = $conn->messageInfo()->getDeliveryInfo($record->dmID);
        self::assertSame($record->dmID, $info->envelope->record->dmID);

        $signedInfo = $conn->messageInfo()->getSignedDeliveryInfo($record->dmID);
        self::assertSame(ZfoKind::DeliveryInfo, Zfo::kind($signedInfo->bytes));
        self::assertSame($record->dmID, Zfo::parseDeliveryInfo($signedInfo->bytes)->envelope->record->dmID);

        try {
            $zfo = $conn->messageOperations()->signedSentMessageDownload($record->dmID);
        } catch (NotAvailableYet) {
            self::markTestSkipped('signed sent message not available yet (1229)');
        }
        self::assertSame(ZfoKind::SentMessage, Zfo::kind($zfo->bytes));
        self::assertSame($record->dmID, Zfo::parse($zfo->bytes)->envelope->record->dmID);
        self::assertTrue($conn->messageOperations()->authenticateMessage($zfo->bytes));
    }

    /**
     * WARNING: GetListOfReceivedMessages DELIVERS every listed message with a READ privilege.
     * Acceptable only because the test box is disposable.
     */
    public function testReceivedListDownloadAndSignedZfo(): void
    {
        $conn = $this->passwordConnection();
        $received = $conn->messageInfo()->getListOfReceivedMessages(new ListFilter(new \DateTimeImmutable('-90 days'), new \DateTimeImmutable()));
        if ($received === []) {
            self::markTestSkipped('no received message within 90 days');
        }
        $id = $received[0]->dmID;
        $message = $conn->messageOperations()->messageDownload($id);
        self::assertSame($id, $message->envelope->record->dmID);
        self::assertNotEmpty($message->files);

        $zfo = $conn->messageOperations()->signedMessageDownload($id);
        self::assertSame(ZfoKind::ReceivedMessage, Zfo::kind($zfo->bytes));
        $parsed = Zfo::parse($zfo->bytes);
        self::assertSame($id, $parsed->envelope->record->dmID);
        self::assertCount(count($message->files), $parsed->files);
        self::assertSame($message->files[0]->content, $parsed->files[0]->content);
    }

    public function testVodzUploadSmallAttachment(): void
    {
        $conn = $this->passwordConnection();
        $s = fopen('php://memory', 'w+b');
        fwrite($s, self::tinyPdf('vodz upload'));
        $ref = $conn->bigMessages()->uploadAttachment($s, 'application/pdf', 'vodz-live.pdf');
        self::assertNotSame('', $ref->dmAttID);
        self::assertNotSame('', $ref->dmAttHash1);
    }
}
