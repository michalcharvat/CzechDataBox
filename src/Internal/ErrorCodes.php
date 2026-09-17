<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Internal;

use MichalCharvat\CzechDataBox\Exception;

/** @internal Pinned from WS manual v3.8.1; covered by StatusGuardTest. */
final class ErrorCodes
{
    /**
     * WS manual 1.3: 0000 is success and "other codes starting with 00 can be
     * considered success too". 0004 = CreateMultipleMessage with some recipients
     * failed; the per-recipient statuses are in the response.
     */
    public static function isSuccess(string $code): bool
    {
        return (bool)preg_match('/^00\d\d$/', $code);
    }

    /** @var array<int, class-string<Exception\IsdsException>> numeric-string keys become int keys in PHP */
    public const MAP = [
        '1211' => Exception\MessageNotFound::class,      // message of another box / wrong direction
        '1219' => Exception\MessageErased::class,        // erased (90 days after delivery / 3 years)
        '1222' => Exception\NotYetDelivered::class,
        '1229' => Exception\NotAvailableYet::class,
        '1281' => Exception\WrongMessageKind::class,
        '1600' => Exception\MessageNotFound::class,      // no live message with this id (SuspMessageReport)
        '2352' => Exception\NotAvailableYet::class,      // async response not ready, ask again later
        '3006' => Exception\DeliveryInProgress::class,
        '3008' => Exception\RateLimited::class,
        '3009' => Exception\RateLimited::class,
        '3013' => Exception\RateLimited::class,
    ];
}
