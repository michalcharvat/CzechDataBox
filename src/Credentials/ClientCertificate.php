<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

final class ClientCertificate
{
    /** @param string $pem certificate + private key, PEM */
    public function __construct(
        #[\SensitiveParameter] public readonly string $pem,
        #[\SensitiveParameter] public readonly ?string $passphrase = null,
    ) {
        if (!str_contains($pem, 'BEGIN CERTIFICATE') || !preg_match('/BEGIN (ENCRYPTED |RSA |EC )?PRIVATE KEY/', $pem)) {
            throw new \InvalidArgumentException('PEM must contain a certificate and a private key');
        }
    }

    /** @return array<string, string|null> */
    public function __debugInfo(): array
    {
        return ['pem' => '***', 'passphrase' => $this->passphrase === null ? null : '***'];
    }
}
