<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

/**
 * Hosted records-management application: system certificate and the data box id
 * "in the login (name) field of Basic authentication" (WS manual 3.8.1, 1.2.1.1; ws1c …/hspis).
 */
final class HostedRecordsServiceCredentials implements Credentials
{
    public function __construct(
        private readonly ClientCertificate $certificate,
        public readonly string $dataBoxId,
    ) {
    }

    public function kind(): AuthKind
    {
        return AuthKind::HostedRecordsService;
    }

    public function basicAuth(): ?array
    {
        return [$this->dataBoxId, ''];
    }

    public function certificate(): ?ClientCertificate
    {
        return $this->certificate;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['certificate' => '***', 'dataBoxId' => $this->dataBoxId];
    }
}
