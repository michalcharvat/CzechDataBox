<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class MessageEnvelope
{
    public function __construct(
        public readonly MessageRecord $record,
        public readonly ?string $dmHash,
        public readonly ?string $dmHashAlgorithm,
        public readonly ?string $dmQTimestamp,
        public readonly \stdClass $raw,
    ) {
    }

    /**
     * Accepts the dmReturnedMessage/dmReturnedMessageEnvelope element: envelope fields live either
     * directly on it or inside dmDm; delivery fields (status, times, hash) live on the outer element.
     */
    public static function fromRaw(\stdClass $outer): self
    {
        $dm = N::child($outer, 'dmDm') ?? $outer;
        $merged = (object)array_filter(
            array_merge(get_object_vars($dm), get_object_vars($outer)),
            static fn($k) => $k !== 'dmDm' && $k !== 'dmFiles',   // dmFiles lives inside dmDm
            ARRAY_FILTER_USE_KEY,
        );
        $hash = $outer->dmHash ?? null;
        return new self(
            MessageRecord::fromRaw($merged),
            is_object($hash) ? N::string($hash, '_') : (is_string($hash) ? $hash : null),
            is_object($hash) ? N::string($hash, 'algorithm') : null,
            N::binary($outer, 'dmQTimestamp'),
            $outer,
        );
    }
}
