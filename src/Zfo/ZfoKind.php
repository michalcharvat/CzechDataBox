<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

enum ZfoKind
{
    case ReceivedMessage;
    case SentMessage;
    case DeliveryInfo;

    /**
     * WS manual 3.8.1: http://isds.czebox.cz/v20/message | /SentMessage | /delivery. The path suffix decides;
     * the host may be isds.czebox.cz or isds.czechpoint.cz.
     */
    public static function fromNamespace(?string $ns): ?self
    {
        if ($ns === null || !preg_match('#^http://isds\.(czebox|czechpoint)\.cz/v20/(message|SentMessage|delivery)$#', $ns, $m)) {
            return null;
        }
        return match ($m[2]) {
            'message' => self::ReceivedMessage,
            'SentMessage' => self::SentMessage,
            default => self::DeliveryInfo,
        };
    }
}
