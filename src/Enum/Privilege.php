<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Enum;

enum Privilege: int
{
    case ReadNonPersonal = 1;   // PRIVIL_READ_NON_PERSONAL
    case ReadAll = 2;           // PRIVIL_READ_ALL
    case CreateDm = 4;          // PRIVIL_CREATE_DM
    case ViewInfo = 8;          // PRIVIL_VIEW_INFO
    case SearchDb = 16;         // PRIVIL_SEARCH_DB
    case OwnerAdm = 32;         // PRIVIL_OWNER_ADM
    case ReadVault = 64;        // PRIVIL_READ_VAULT
    case EraseVault = 128;      // PRIVIL_ERASE_VAULT

    public static function mask(self ...$privileges): int
    {
        return array_reduce($privileges, static fn(int $m, self $p) => $m | $p->value, 0);
    }
}
