<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Enum\DataBoxType;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** tDbOwnerInfoExt2 (GetOwnerInfoFromLogin2, FindDataBox2 rows). */
final class OwnerInfo
{
    public function __construct(
        public readonly ?string $dbID,
        public readonly ?bool $aifoIsds,
        public readonly ?string $dbType,
        public readonly ?string $ic,
        public readonly ?string $pnGivenNames,
        public readonly ?string $pnLastName,
        public readonly ?string $firmName,
        public readonly ?\DateTimeImmutable $biDate,
        public readonly ?string $biCity,
        public readonly ?string $biCounty,
        public readonly ?string $biState,
        public readonly ?string $adCode,
        public readonly ?string $adCity,
        public readonly ?string $adDistrict,
        public readonly ?string $adStreet,
        public readonly ?string $adNumberInStreet,
        public readonly ?string $adNumberInMunicipality,
        public readonly ?string $adZipCode,
        public readonly ?string $adState,
        public readonly ?string $nationality,
        public readonly ?string $dbIdOVM,
        public readonly ?int $dbState,
        public readonly ?bool $dbOpenAddressing,
        public readonly ?string $dbUpperID,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $o): self
    {
        return new self(
            N::string($o, 'dbID'), N::bool($o, 'aifoIsds'), N::string($o, 'dbType'), N::string($o, 'ic'),
            N::string($o, 'pnGivenNames'), N::string($o, 'pnLastName'), N::string($o, 'firmName'),
            N::date($o, 'biDate'), N::string($o, 'biCity'), N::string($o, 'biCounty'), N::string($o, 'biState'),
            N::string($o, 'adCode'), N::string($o, 'adCity'), N::string($o, 'adDistrict'), N::string($o, 'adStreet'),
            N::string($o, 'adNumberInStreet'), N::string($o, 'adNumberInMunicipality'), N::string($o, 'adZipCode'),
            N::string($o, 'adState'), N::string($o, 'nationality'), N::string($o, 'dbIdOVM'), N::int($o, 'dbState'),
            N::bool($o, 'dbOpenAddressing'), N::string($o, 'dbUpperID'),
            $o,
        );
    }

    public function displayName(): string
    {
        return $this->firmName ?? trim(($this->pnGivenNames ?? '') . ' ' . ($this->pnLastName ?? ''));
    }

    /** Null when ISDS returns a type this library does not know yet. */
    public function type(): ?DataBoxType
    {
        return $this->dbType === null ? null : DataBoxType::tryFrom($this->dbType);
    }
}
