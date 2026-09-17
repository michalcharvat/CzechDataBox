<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

final class ListFilter
{
    public function __construct(
        public readonly \DateTimeInterface $from,
        public readonly \DateTimeInterface $to,
        public readonly int $limit = 1000,
        public readonly ?int $offset = null,
        public readonly int $statusFilter = -1,
        public readonly ?int $orgUnitNum = null,
    ) {
        if ($limit < 1 || $limit > 1000) {
            throw new \InvalidArgumentException('dmLimit must be 1..1000');
        }
    }
}
