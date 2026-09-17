<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

/**
 * 0600 temp file holding the client certificate PEM, for cURL builds without CURLOPT_SSLCERT_BLOB.
 * Deleted when the owning transport drops it. @internal
 */
final class CertificateFile
{
    public readonly string $path;

    public function __construct(#[\SensitiveParameter] string $pem, string $dir)
    {
        $path = tempnam($dir, 'isds-cert-');
        if ($path === false) {
            throw new \RuntimeException('Cannot create certificate temp file');
        }
        chmod($path, 0600);
        if (file_put_contents($path, $pem) !== strlen($pem)) {
            @unlink($path);
            throw new \RuntimeException('Cannot write certificate temp file');
        }
        $this->path = $path;
    }

    public function __destruct()
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }
}
