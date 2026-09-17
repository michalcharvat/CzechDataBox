<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Exception\MalformedResponse;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class DeliveryEvent
{
    public function __construct(
        public readonly \DateTimeImmutable $time,
        public readonly string $description,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $e): self
    {
        $time = N::dateTime($e, 'dmEventTime') ?? throw new MalformedResponse(null, 'dmEvent without dmEventTime', 'response');
        return new self($time, N::string($e, 'dmEventDescr') ?? '', $e);
    }

    /** "EV1: …" → "EV1"; null when the description has no code prefix. */
    public function code(): ?string
    {
        return preg_match('/^(EV\d+):/', $this->description, $m) ? $m[1] : null;
    }
}
