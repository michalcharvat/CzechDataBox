<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class Message
{
    /** @param list<File> $files */
    public function __construct(
        public readonly MessageEnvelope $envelope,
        public readonly array $files,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $returnedMessage): self
    {
        $dm = N::child($returnedMessage, 'dmDm') ?? $returnedMessage;
        $files = array_map(File::fromRaw(...), N::list($dm->dmFiles ?? null, 'dmFile'));
        return new self(MessageEnvelope::fromRaw($returnedMessage), $files, $returnedMessage);
    }
}
