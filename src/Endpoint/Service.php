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
        };
    }

    public function pathSuffix(): string
    {
        return match ($this) {
            self::Operations => 'dz',
            self::Info => 'dx',
            self::Search => 'df',
            self::Access, self::Manipulations => 'DsManage',
            self::BigMessages => 'vodz',
            self::Archive => 'arch',
        };
    }

    public function isSecondaryHost(): bool
    {
        return $this === self::BigMessages || $this === self::Archive;
    }
}
