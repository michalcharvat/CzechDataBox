<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

final class CreatedMessage
{
    public function __construct(
        public readonly string $dmID,
        public readonly \stdClass $raw,
    ) {
    }
}
