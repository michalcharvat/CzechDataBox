<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

/** dmOffset counts from 1; dmLimit defaults to 1000 in ISDS and may be larger (WS manual 2.9). */
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
        if ($limit < 1) {
            throw new \InvalidArgumentException('dmLimit must be at least 1');
        }
        if ($offset !== null && $offset < 1) {
            throw new \InvalidArgumentException('dmOffset counts from 1');
        }
    }
}
