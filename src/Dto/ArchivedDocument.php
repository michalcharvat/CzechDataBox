<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

final class ArchivedDocument
{
    /** @param SignedDocument|null $document null when the result was streamed into a sink */
    public function __construct(
        public readonly ?SignedDocument $document,
        public readonly ?\DateTimeImmutable $nextStampTo,
        public readonly \stdClass $raw,
    ) {
    }
}
