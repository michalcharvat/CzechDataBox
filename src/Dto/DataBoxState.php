<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/**
 * CheckDataBoxResponse (dbState only) or GetDataBoxActivityStatusResponse (dbID + Periods/Period).
 * dbState 1 = accessible (WS search manual).
 */
final class DataBoxState
{
    /** @param list<array{from: \DateTimeImmutable, to: ?\DateTimeImmutable, state: ?int}> $periods */
    public function __construct(
        public readonly ?string $dbID,
        public readonly ?int $dbState,
        public readonly array $periods,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $r): self
    {
        $periods = [];
        foreach (N::list(N::child($r, 'Periods'), 'Period') as $p) {
            $from = N::dateTime($p, 'PeriodFrom');
            if ($from !== null) {
                $periods[] = ['from' => $from, 'to' => N::dateTime($p, 'PeriodTo'), 'state' => N::int($p, 'DbState')];
            }
        }
        return new self(N::string($r, 'dbID'), N::int($r, 'dbState'), $periods, $r);
    }

    public function isAccessible(): bool
    {
        return $this->dbState === 1;
    }
}
