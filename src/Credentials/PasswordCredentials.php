<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

final class PasswordCredentials implements Credentials
{
    public function __construct(
        public readonly string $username,
        #[\SensitiveParameter] private readonly string $password,
    ) {
    }

    public function kind(): AuthKind
    {
        return AuthKind::Password;
    }

    public function basicAuth(): ?array
    {
        return [$this->username, $this->password];
    }

    public function certificate(): ?ClientCertificate
    {
        return null;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['username' => $this->username, 'password' => '***'];
    }
}
