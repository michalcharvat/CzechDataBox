<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

interface Credentials
{
    public function kind(): AuthKind;

    /**
     * HTTP Basic user/password, or null for certificate-only logins.
     *
     * @return array{string, string}|null
     */
    public function basicAuth(): ?array;

    public function certificate(): ?ClientCertificate;
}
