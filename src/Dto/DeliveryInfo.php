<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class DeliveryInfo
{
    /** @param list<DeliveryEvent> $events */
    public function __construct(
        public readonly MessageEnvelope $envelope,
        public readonly array $events,
        public readonly \stdClass $raw,
    ) {
    }

    /** @param \stdClass $delivery the dmDelivery element */
    public static function fromRaw(\stdClass $delivery): self
    {
        return new self(
            MessageEnvelope::fromRaw($delivery),
            array_map(DeliveryEvent::fromRaw(...), N::list($delivery->dmEvents ?? null, 'dmEvent')),
            $delivery,
        );
    }
}
