<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Enum;

/** Values of tDbType in dbTypes.xsd (WSDL 3.10). */
enum DataBoxType: string
{
    case FO = 'FO';
    case PFO = 'PFO';
    case PFO_REQ = 'PFO_REQ';
    case PFO_ADVOK = 'PFO_ADVOK';
    case PFO_DANPOR = 'PFO_DANPOR';
    case PFO_INSSPR = 'PFO_INSSPR';
    case PFO_AUDITOR = 'PFO_AUDITOR';
    case PFO_ZNALEC = 'PFO_ZNALEC';
    case PFO_TLUMOCNIK = 'PFO_TLUMOCNIK';
    case PFO_ARCH = 'PFO_ARCH';
    case PFO_AIAT = 'PFO_AIAT';
    case PFO_AZI = 'PFO_AZI';
    case PO = 'PO';
    case PO_ZAK = 'PO_ZAK';
    case PO_REQ = 'PO_REQ';
    case OVM = 'OVM';
    case OVM_NOTAR = 'OVM_NOTAR';
    case OVM_EXEKUT = 'OVM_EXEKUT';
    case OVM_REQ = 'OVM_REQ';
    case OVM_FO = 'OVM_FO';
    case OVM_PFO = 'OVM_PFO';
    case OVM_PO = 'OVM_PO';

    public function isOvm(): bool
    {
        return str_starts_with($this->value, 'OVM');
    }
}
