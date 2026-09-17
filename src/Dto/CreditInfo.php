<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** DataBoxCreditInfoResponse (tDBCreditInfoOutput). */
final class CreditInfo
{
    /** @param list<\stdClass> $ciRecords raw tCiRecord rows */
    public function __construct(
        /** haléře */
        public readonly ?int $currentCredit,
        public readonly ?string $notifEmail,
        public readonly array $ciRecords,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $r): self
    {
        return new self(
            N::int($r, 'currentCredit'),
            N::string($r, 'notifEmail'),
            N::list(N::child($r, 'ciRecords'), 'ciRecord'),
            $r,
        );
    }
}
