<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Enum;

use MichalCharvat\CzechDataBox\Enum\MessageStatus;
use MichalCharvat\CzechDataBox\Enum\MessageType;
use MichalCharvat\CzechDataBox\Enum\Privilege;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function testMessageStatusHelpers(): void
    {
        self::assertSame(MessageStatus::Delivered, MessageStatus::from(6));
        self::assertTrue(MessageStatus::DeliveredToBox->awaitsDelivery());
        self::assertTrue(MessageStatus::TenDaysElapsed->awaitsDelivery());
        self::assertFalse(MessageStatus::Read->awaitsDelivery());
        self::assertTrue(MessageStatus::Delivered->isDelivered());
        self::assertTrue(MessageStatus::InVault->isDelivered());
        self::assertFalse(MessageStatus::Submitted->isDelivered());
    }

    public function testStatusFilterBits(): void
    {
        // WS manual worked examples: delivered = states 5+6 → 96, vault = state 10 → 1024
        self::assertSame(96, MessageStatus::TenDaysElapsed->filterBit() | MessageStatus::Delivered->filterBit());
        self::assertSame(1024, MessageStatus::InVault->filterBit());
        self::assertSame(2, MessageStatus::Submitted->filterBit());
    }

    public function testPrivilegeBits(): void
    {
        self::assertSame(159, Privilege::mask(
            Privilege::ReadNonPersonal, Privilege::ReadAll, Privilege::CreateDm,
            Privilege::ViewInfo, Privilege::SearchDb, Privilege::EraseVault,
        ));
    }

    public function testMessageTypeKnownAndUnknown(): void
    {
        self::assertTrue(MessageType::isKnown('V'));
        self::assertFalse(MessageType::isKnown('Q'));
        self::assertTrue(MessageType::isPostal('K'));
    }
}
