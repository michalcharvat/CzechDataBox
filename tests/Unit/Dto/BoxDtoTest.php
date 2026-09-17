<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Dto;

use MichalCharvat\CzechDataBox\Dto\OwnerInfo;
use MichalCharvat\CzechDataBox\Dto\UserInfo;
use MichalCharvat\CzechDataBox\Enum\Privilege;
use PHPUnit\Framework\TestCase;

final class BoxDtoTest extends TestCase
{
    public function testOwnerInfoExt2(): void
    {
        $o = OwnerInfo::fromRaw((object)[
            'dbID' => 'abc1234', 'dbType' => 'PO', 'ic' => '12345678', 'firmName' => 'ACME s.r.o.',
            'adCity' => 'Praha', 'dbState' => '1', 'dbOpenAddressing' => 'true', 'biDate' => null,
        ]);
        self::assertSame('abc1234', $o->dbID);
        self::assertSame('ACME s.r.o.', $o->displayName());
        self::assertSame(1, $o->dbState);
        self::assertTrue($o->dbOpenAddressing);
        self::assertNull($o->biDate);

        $person = OwnerInfo::fromRaw((object)['dbID' => 'x', 'pnGivenNames' => 'Jan', 'pnLastName' => 'Novák']);
        self::assertSame('Jan Novák', $person->displayName());
    }

    public function testUserInfoPrivileges(): void
    {
        $u = UserInfo::fromRaw((object)['isdsID' => 'u1', 'userType' => 'ENTRUSTED_USER', 'userPrivils' => '5']);
        self::assertTrue($u->privileges()->has(Privilege::ReadNonPersonal));
        self::assertTrue($u->privileges()->has(Privilege::CreateDm));
        self::assertFalse($u->privileges()->has(Privilege::ReadAll));
        self::assertTrue($u->privileges()->delivers()); // ReadNonPersonal (1) is in mask 5

        self::assertFalse(UserInfo::fromRaw((object)['userPrivils' => '4'])->privileges()->delivers());

        self::assertTrue(UserInfo::fromRaw((object)['userPrivils' => '2'])->privileges()->delivers());
    }
}
