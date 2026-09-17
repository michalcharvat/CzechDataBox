<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** tDbUserInfoExt2 (GetUserInfoFromLogin2). */
final class UserInfo
{
    public function __construct(
        public readonly ?bool $aifoIsds,
        public readonly ?string $pnGivenNames,
        public readonly ?string $pnLastName,
        public readonly ?string $adCode,
        public readonly ?string $adCity,
        public readonly ?string $adDistrict,
        public readonly ?string $adStreet,
        public readonly ?string $adNumberInStreet,
        public readonly ?string $adNumberInMunicipality,
        public readonly ?string $adZipCode,
        public readonly ?string $adState,
        public readonly ?\DateTimeImmutable $biDate,
        public readonly ?string $isdsID,
        public readonly ?string $userType,
        public readonly ?int $userPrivils,
        public readonly ?string $ic,
        public readonly ?string $firmName,
        public readonly ?string $caStreet,
        public readonly ?string $caCity,
        public readonly ?string $caZipCode,
        public readonly ?string $caState,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $u): self
    {
        return new self(
            N::bool($u, 'aifoIsds'), N::string($u, 'pnGivenNames'), N::string($u, 'pnLastName'),
            N::string($u, 'adCode'), N::string($u, 'adCity'), N::string($u, 'adDistrict'), N::string($u, 'adStreet'),
            N::string($u, 'adNumberInStreet'), N::string($u, 'adNumberInMunicipality'), N::string($u, 'adZipCode'),
            N::string($u, 'adState'), N::date($u, 'biDate'), N::string($u, 'isdsID'), N::string($u, 'userType'),
            N::int($u, 'userPrivils'), N::string($u, 'ic'), N::string($u, 'firmName'),
            N::string($u, 'caStreet'), N::string($u, 'caCity'), N::string($u, 'caZipCode'), N::string($u, 'caState'),
            $u,
        );
    }

    public function privileges(): Privileges
    {
        return new Privileges($this->userPrivils ?? 0);
    }
}
