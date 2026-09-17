<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\OwnerInfo;
use MichalCharvat\CzechDataBox\Dto\UserInfo;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** db_access.wsdl — endpoint …/DS/DsManage. None of these operations delivers messages. */
final class Access extends AbstractService
{
    /** @deprecated Use getOwnerInfoFromLogin2(). Raw response: dbOwnerInfo (tDbOwnerInfo: pnFirstName, …). */
    public function getOwnerInfoFromLogin(): \stdClass
    {
        return $this->call('GetOwnerInfoFromLogin', ['dbDummy' => '']);
    }

    public function getOwnerInfoFromLogin2(): OwnerInfo
    {
        $raw = $this->call('GetOwnerInfoFromLogin2', ['dbDummy' => '']);
        return OwnerInfo::fromRaw(N::child($raw, 'dbOwnerInfo') ?? throw $this->missing('GetOwnerInfoFromLogin2', 'dbOwnerInfo'));
    }

    /** @deprecated Use getUserInfoFromLogin2(). Raw response: dbUserInfo (tDbUserInfo: pnFirstName, userID, …). */
    public function getUserInfoFromLogin(): \stdClass
    {
        return $this->call('GetUserInfoFromLogin', ['dbDummy' => '']);
    }

    public function getUserInfoFromLogin2(): UserInfo
    {
        $raw = $this->call('GetUserInfoFromLogin2', ['dbDummy' => '']);
        return UserInfo::fromRaw(N::child($raw, 'dbUserInfo') ?? throw $this->missing('GetUserInfoFromLogin2', 'dbUserInfo'));
    }

    /** Password expiry in UTC; null when the login has no expiring password. */
    public function getPasswordInfo(): ?\DateTimeImmutable
    {
        return N::dateTime($this->call('GetPasswordInfo', ['dbDummy' => '']), 'pswExpDate');
    }

    /** Name + password logins. OTP logins must use Connection::passwordChange() instead. */
    public function changeIsdsPassword(#[\SensitiveParameter] string $oldPassword, #[\SensitiveParameter] string $newPassword): void
    {
        $this->call('ChangeISDSPassword', ['dbOldPassword' => $oldPassword, 'dbNewPassword' => $newPassword]);
    }
}
