<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Dto;

use MichalCharvat\CzechDataBox\Dto\DeliveryInfo;
use MichalCharvat\CzechDataBox\Dto\MessageAuthor;
use MichalCharvat\CzechDataBox\Dto\StateChange;
use PHPUnit\Framework\TestCase;

final class DeliveryDtoTest extends TestCase
{
    public function testEventsZeroOneMany(): void
    {
        $ev = (object)['dmEventTime' => '2026-09-01T10:00:00+02:00', 'dmEventDescr' => 'EV1: dodáno'];
        foreach ([[null, 0], [(object)['dmEvent' => $ev], 1], [(object)['dmEvent' => [$ev, $ev]], 2]] as [$events, $n]) {
            $d = DeliveryInfo::fromRaw((object)['dmDm' => (object)['dmID' => '9'], 'dmMessageStatus' => '4',
                'dmDeliveryTime' => '2026-09-01T10:00:00+02:00', 'dmEvents' => $events]);
            self::assertCount($n, $d->events);
            self::assertSame('9', $d->envelope->record->dmID);
        }
        $one = DeliveryInfo::fromRaw((object)['dmDm' => (object)['dmID' => '9'], 'dmEvents' => (object)['dmEvent' => $ev]]);
        self::assertSame('EV1', $one->events[0]->code());
        self::assertSame('2026-09-01 08:00:00', $one->events[0]->time->format('Y-m-d H:i:s'));
    }

    public function testStateChangeAndAuthor(): void
    {
        $s = StateChange::fromRaw((object)['dmID' => '5', 'dmEventTime' => '2026-09-02T12:00:00Z', 'dmMessageStatus' => '6']);
        self::assertSame(6, $s->dmMessageStatus);

        $a = MessageAuthor::fromRaw((object)['userType' => 'PRIMARY_USER', 'authorName' => 'Jan Novák']);
        self::assertSame('PRIMARY_USER', $a->userType);
        self::assertSame('Jan Novák', $a->authorName);
    }

    public function testAuthor2KeyValueItems(): void
    {
        $items = [(object)['key' => 'userType', 'value' => 'PRIMARY_USER'], (object)['key' => 'pnGivenNames', 'value' => 'Jan Ladislav'],
            (object)['key' => 'pnLastName', 'value' => 'Novák'], (object)['key' => 'biDate', 'value' => '1989-12-29'],
            (object)['key' => 'robIdent', 'value' => 'true']];
        $a = MessageAuthor::fromRaw((object)['dmMessageAuthor' => (object)['maItem' => $items]]);
        self::assertSame('PRIMARY_USER', $a->userType);
        self::assertSame('Jan Ladislav Novák', $a->authorName);
        self::assertSame('1989-12-29', $a->biDate?->format('Y-m-d'));
        self::assertTrue($a->robIdent);

        $only = MessageAuthor::fromRaw((object)['dmMessageAuthor' => (object)['maItem' => (object)['key' => 'userType', 'value' => 'VIRTUAL']]]);
        self::assertSame('VIRTUAL', $only->userType);
        self::assertNull($only->authorName);
    }
}
