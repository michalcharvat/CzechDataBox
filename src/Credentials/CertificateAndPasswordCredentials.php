<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

/** User account with a client certificate plus name and password (ws1c …/certds). */
final class CertificateAndPasswordCredentials implements Credentials
{
    public function __construct(
        private readonly ClientCertificate $certificate,
        public readonly string $username,
        #[\SensitiveParameter] private readonly string $password,
    ) {
    }

    public function kind(): AuthKind
    {
        return AuthKind::CertificateAndPassword;
    }

    public function basicAuth(): ?array
    {
        return [$this->username, $this->password];
    }

    public function certificate(): ?ClientCertificate
    {
        return $this->certificate;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['certificate' => '***', 'username' => $this->username, 'password' => '***'];
    }
}
