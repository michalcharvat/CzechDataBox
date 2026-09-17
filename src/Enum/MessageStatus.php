<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Enum;

enum MessageStatus: int
{
    case Submitted = 1;
    case Stamped = 2;
    case InfectedNotDelivered = 3;
    case DeliveredToBox = 4;
    case TenDaysElapsed = 5;
    case Delivered = 6;
    case Read = 7;
    case Undeliverable = 8;
    /** Not in the 3.8.1 state table (1–8, 10); kept for older data ("erased"). */
    case ContentErased = 9;
    case InVault = 10;

    /** 4/5: in the recipient's box, legally not delivered yet. */
    public function awaitsDelivery(): bool
    {
        return $this === self::DeliveredToBox || $this === self::TenDaysElapsed;
    }

    /** ≥ 6 and not undeliverable. */
    public function isDelivered(): bool
    {
        return $this->value >= 6 && $this !== self::Undeliverable;
    }

    /** Bit for dmStatusFilter: state N is 2^N (WS manual, GetListOf*Messages). */
    public function filterBit(): int
    {
        return 1 << $this->value;
    }
}
