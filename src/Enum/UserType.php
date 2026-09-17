<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Enum;

/** Values of tUserType in dbTypes.xsd (WSDL 3.10). */
enum UserType: string
{
    case PRIMARY_USER = 'PRIMARY_USER';
    case ENTRUSTED_USER = 'ENTRUSTED_USER';
    case ADMINISTRATOR = 'ADMINISTRATOR';
    case OFFICIAL = 'OFFICIAL';
    case OFFICIAL_CERT = 'OFFICIAL_CERT';
    case LIQUIDATOR = 'LIQUIDATOR';
    case RECEIVER = 'RECEIVER';
    case GUARDIAN = 'GUARDIAN';
}
