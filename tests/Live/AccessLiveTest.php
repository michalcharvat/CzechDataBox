<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Live;

use MichalCharvat\CzechDataBox\Connection;
use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Environment;
use MichalCharvat\CzechDataBox\Exception\AuthenticationFailed;

final class AccessLiveTest extends LiveTestCase
{
    public function testDummyOperationAndOwnerUserPasswordInfo(): void
    {
        $conn = $this->passwordConnection();
        $conn->messageOperations()->dummyOperation();

        $owner = $conn->access()->getOwnerInfoFromLogin2();
        self::assertMatchesRegularExpression('/^[a-z0-9]{7}$/', (string)$owner->dbID);
        self::assertSame(self::env('ISDS_TEST_SELF_DBID'), $owner->dbID);

        $user = $conn->access()->getUserInfoFromLogin2();
        self::assertNotNull($user->userPrivils);

        $conn->access()->getPasswordInfo(); // must not throw; null = no expiry
    }

    public function testSearchCheckOwnBox(): void
    {
        $conn = $this->passwordConnection();
        self::assertTrue($conn->search()->checkDataBox(self::env('ISDS_TEST_SELF_DBID'))->isAccessible());
        $address = $conn->search()->getDataBoxAddress(self::env('ISDS_TEST_SELF_DBID')); // patched XSD: status must be read
        self::assertInstanceOf(\stdClass::class, $address);
    }

    /** One attempt only: repeated failures lock the account. */
    public function testWrongPasswordIsAuthenticationFailed(): void
    {
        $conn = new Connection(Environment::Test, new PasswordCredentials(self::env('ISDS_TEST_USER'), 'definitely-wrong-' . bin2hex(random_bytes(4))));
        $this->expectException(AuthenticationFailed::class);
        $conn->access()->getOwnerInfoFromLogin2();
    }

    public function testSystemCertificateLogin(): void
    {
        self::assertNotNull($this->certificateConnection()->access()->getOwnerInfoFromLogin2()->dbID);
    }
}
