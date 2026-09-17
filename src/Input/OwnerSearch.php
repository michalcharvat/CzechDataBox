<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

/**
 * Search template for FindDataBox2 (tDbOwnerInfoExt21). Unset fields are sent as nil and ignored by ISDS.
 * dbType also accepts the search-only virtual types OVM_MAIN, PO_BASE and PFO_BASE (WSDL 3.10).
 * Minimum inputs (WS search manual): FO pnLastName; PFO ic or firmName + pnLastName; PO/OVM ic or firmName.
 */
final class OwnerSearch
{
    public function __construct(
        public readonly ?string $dbID = null,
        public readonly ?string $dbType = null,
        public readonly ?string $ic = null,
        public readonly ?string $pnGivenNames = null,
        public readonly ?string $pnLastName = null,
        public readonly ?string $firmName = null,
        public readonly ?\DateTimeInterface $biDate = null,
        public readonly ?string $biCity = null,
        public readonly ?string $biCounty = null,
        public readonly ?string $biState = null,
        public readonly ?string $adCode = null,
        public readonly ?string $adCity = null,
        public readonly ?string $adDistrict = null,
        public readonly ?string $adStreet = null,
        public readonly ?string $adNumberInStreet = null,
        public readonly ?string $adNumberInMunicipality = null,
        public readonly ?string $adZipCode = null,
        public readonly ?string $adState = null,
        public readonly ?string $nationality = null,
        public readonly ?string $dbIdOVM = null,
        public readonly ?int $dbState = null,
        public readonly ?bool $dbOpenAddressing = null,
        public readonly ?string $dbUpperID = null,
    ) {
    }

    /** @return array<string, mixed> SoapClient array for dbOwnerInfo; nulls dropped */
    public function toSoap(): array
    {
        $v = array_filter(get_object_vars($this), static fn($x) => $x !== null);
        if ($this->biDate !== null) {
            $v['biDate'] = $this->biDate->format('Y-m-d');
        }
        return $v;
    }
}
