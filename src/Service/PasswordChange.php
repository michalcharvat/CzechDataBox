<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

/**
 * ChangePassword.wsdl — https://www…/asws/changePassword, for accounts with OTP login (OTP manual 3.x).
 * The Basic-auth password of the Connection must be the account password immediately followed by the
 * HOTP/TOTP code; sendSmsCode() needs the plain password only.
 */
final class PasswordChange extends AbstractService
{
    /** @param string $otpType HOTP (security code) or TOTP (SMS) */
    public function changePasswordOtp(
        #[\SensitiveParameter] string $oldPassword,
        #[\SensitiveParameter] string $newPassword,
        string $otpType,
    ): void {
        $this->call('ChangePasswordOTP', ['dbOldPassword' => $oldPassword, 'dbNewPassword' => $newPassword, 'dbOTPType' => $otpType]);
    }

    /** Sends a premium SMS with a TOTP code (at most once per 30 s; errors 2300–2302). */
    public function sendSmsCode(): void
    {
        $this->call('SendSMSCode');
    }
}
