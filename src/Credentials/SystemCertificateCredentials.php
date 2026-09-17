<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

/** Records-management application logging in with its system certificate (ws1c …/cert). */
final class SystemCertificateCredentials implements Credentials
{
    public function __construct(
        private readonly ClientCertificate $certificate,
    ) {
    }

    public function kind(): AuthKind
    {
        return AuthKind::SystemCertificate;
    }

    public function basicAuth(): ?array
    {
        return null;
    }

    public function certificate(): ?ClientCertificate
    {
        return $this->certificate;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['certificate' => '***'];
    }
}
