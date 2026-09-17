<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Enum\MessageStatus;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** tStateChangesRecord of GetMessageStateChanges. */
final class StateChange
{
    public function __construct(
        public readonly string $dmID,
        public readonly \DateTimeImmutable $dmEventTime,
        public readonly ?int $dmMessageStatus,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $r): self
    {
        return new self(
            N::string($r, 'dmID') ?? throw new \UnexpectedValueException('dmRecord without dmID'),
            N::dateTime($r, 'dmEventTime') ?? throw new \UnexpectedValueException('dmRecord without dmEventTime'),
            N::int($r, 'dmMessageStatus'),
            $r,
        );
    }

    public function status(): ?MessageStatus
    {
        return $this->dmMessageStatus === null ? null : MessageStatus::tryFrom($this->dmMessageStatus);
    }
}
