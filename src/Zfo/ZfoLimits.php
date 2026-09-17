<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

final class ZfoLimits
{
    public function __construct(
        public readonly int $maxFiles = 100,
        public readonly int $maxFileBytes = 110 * 1024 * 1024,    // VoDZ max 100 MB + margin
        public readonly int $maxTotalBytes = 110 * 1024 * 1024,
        public readonly int $maxDepth = 32,
        public readonly int $maxElements = 20_000,
        public readonly int $maxInMemoryBytes = 30 * 1024 * 1024, // parse(): normal messages ≤ 20 MB
    ) {
    }
}
