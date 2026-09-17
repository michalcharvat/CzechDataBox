<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Endpoint;

enum Service
{
    case Operations;
    case Info;
    case Search;
    case Access;          // db_access
    case Manipulations;   // db_manipulations — same URL as Access, distinct WSDL
    case BigMessages;
    case Archive;
    case ChangePassword;  // OTP logins only (www…/asws/changePassword)

    public function wsdl(): string
    {
        return match ($this) {
            self::Operations => 'dm_operations.wsdl',
            self::Info => 'dm_info.wsdl',
            self::Search => 'db_search.wsdl',
            self::Access => 'db_access.wsdl',
            self::Manipulations => 'db_manipulations.wsdl',
            self::BigMessages => 'dm_VoDZ.wsdl',
            self::Archive => 'dm_arch.wsdl',
            self::ChangePassword => 'ChangePassword.wsdl',
        };
    }

    /** ws2 endpoints speak SOAP 1.2 with MTOM (VodzTransport), the others SOAP 1.1. */
    public function isMtom(): bool
    {
        return $this === self::BigMessages || $this === self::Archive;
    }
}
