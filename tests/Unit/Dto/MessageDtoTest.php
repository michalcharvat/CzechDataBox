<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Dto;

use MichalCharvat\CzechDataBox\Dto\File;
use MichalCharvat\CzechDataBox\Dto\Message;
use MichalCharvat\CzechDataBox\Dto\MessageRecord;
use MichalCharvat\CzechDataBox\Enum\MessageStatus;
use PHPUnit\Framework\TestCase;

final class MessageDtoTest extends TestCase
{
    public function testRecordMapsFieldsAndKeepsRaw(): void
    {
        $raw = (object)[
            'dmID' => '1234567', 'dbIDSender' => 'abc1234', 'dmSender' => 'Úřad', 'dbIDRecipient' => 'xyz9876',
            'dmAnnotation' => 'Výzva', 'dmMessageStatus' => '6', 'dmAttachmentSize' => '120',
            'dmDeliveryTime' => '2026-09-01T10:00:00.000+02:00', 'dmAcceptanceTime' => null,
            'dmPersonalDelivery' => 'false', 'dmVODZ' => 'true', 'attsNum' => '3', 'dmType' => 'V',
            'somethingNew' => 'kept',
        ];

        $r = MessageRecord::fromRaw($raw);

        self::assertSame('1234567', $r->dmID);
        self::assertSame(MessageStatus::Delivered, $r->status());
        self::assertSame('2026-09-01 08:00:00', $r->dmDeliveryTime?->format('Y-m-d H:i:s'));
        self::assertNull($r->dmAcceptanceTime);
        self::assertFalse($r->dmPersonalDelivery);
        self::assertTrue($r->dmVODZ);
        self::assertSame(3, $r->attsNum);
        self::assertSame('kept', $r->raw->somethingNew);
    }

    public function testMessageWithZeroOneManyFiles(): void
    {
        $file = (object)['dmEncodedContent' => 'PDFDATA', 'dmMimeType' => 'application/pdf',
            'dmFileMetaType' => 'main', 'dmFileDescr' => 'a.pdf'];
        $env = (object)['dmID' => '1', 'dmAnnotation' => 'x'];

        foreach ([[null, 0], [(object)['dmFile' => $file], 1], [(object)['dmFile' => [$file, $file]], 2]] as [$files, $n]) {
            $m = Message::fromRaw((object)['dmDm' => (object)((array)$env + ['dmFiles' => $files])]);
            self::assertCount($n, $m->files);
            self::assertSame('1', $m->envelope->record->dmID);
        }

        $f = File::fromRaw($file);
        self::assertSame('PDFDATA', $f->content);
        self::assertSame(7, $f->size());
        self::assertTrue($f->isMain());
    }
}
