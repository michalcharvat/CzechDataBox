<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Service\Access;
use MichalCharvat\CzechDataBox\Service\PasswordChange;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

final class AccessTest extends TestCase
{
    public function testOwnerAndUserInfo2(): void
    {
        $t = FakeTransport::for(Service::Access)
            ->reply('GetOwnerInfoFromLogin2', 'po')
            ->reply('GetUserInfoFromLogin2', 'entrusted-read-nonpersonal');
        $a = new Access($t);

        $o = $a->getOwnerInfoFromLogin2();
        self::assertSame('abc1234', $o->dbID);
        self::assertSame('ACME s.r.o.', $o->displayName());
        self::assertStringContainsString('<ns1:dbDummy></ns1:dbDummy>', $t->lastRequestXml());
        $u = $a->getUserInfoFromLogin2();
        self::assertSame(1, $u->userPrivils);
        self::assertTrue($u->privileges()->delivers());
    }

    public function testPasswordInfoNilMeansNoExpiry(): void
    {
        $t = FakeTransport::for(Service::Access)->reply('GetPasswordInfo', 'nil')->reply('GetPasswordInfo', 'ok');
        $a = new Access($t);
        self::assertNull($a->getPasswordInfo());
        self::assertSame('2026-12-01 12:45:00 UTC', $a->getPasswordInfo()?->format('Y-m-d H:i:s T'));
    }

    /** PHP 8.1 ignores #[\SensitiveParameter]; there zend.exception_ignore_args=1 is required (README). */
    #[RequiresPhp('>= 8.2')]
    public function testChangePasswordDoesNotLeakSecretsIntoExceptions(): void
    {
        $t = FakeTransport::for(Service::Access)->reply('ChangeISDSPassword', 'err-weak');
        try {
            (new Access($t))->changeIsdsPassword('Old-Secret-1', 'New-Secret-2');
            self::fail('expected exception');
        } catch (IsdsException $e) {
            // needle = the password values; the test method name itself contains "Secret"
            $dump = $e->getMessage() . print_r($e->getTrace(), true);
            self::assertStringNotContainsString('Old-Secret-1', $dump);
            self::assertStringNotContainsString('New-Secret-2', $dump);
        }
    }

    public function testPasswordChangeServiceSendsSmsCode(): void
    {
        $t = FakeTransport::for(Service::ChangePassword)->reply('SendSMSCode', 'ok');
        (new PasswordChange($t))->sendSmsCode();
        self::assertSame(['SendSMSCode'], $t->calledOperations());
    }
}
