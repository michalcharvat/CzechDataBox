<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

use MichalCharvat\CzechDataBox\Exception\InvalidZfo;

/** @internal */
final class CmsUnwrapper
{
    private const OID_SIGNED_DATA = "\x2a\x86\x48\x86\xf7\x0d\x01\x07\x02";   // 1.2.840.113549.1.7.2
    private const OID_SIGNING_TIME = "\x2a\x86\x48\x86\xf7\x0d\x01\x09\x05";  // 1.2.840.113549.1.9.5

    public function __construct(
        private readonly ?string $tempDir = null,
        private readonly bool $forceDerFallback = false,
    ) {
    }

    /** Inner content of a CMS SignedData (the signature itself is not judged here). */
    public function content(string $der): string
    {
        if (!$this->forceDerFallback && function_exists('openssl_cms_verify')) {
            $viaOpenssl = $this->viaOpenssl($der);
            if ($viaOpenssl !== null) {
                return $viaOpenssl;
            }
        }
        return $this->viaDer($der);
    }

    /**
     * Bounded-memory unwrap for parseStream(): openssl reads and writes files natively.
     * The DER fallback needs the whole input in memory, so it is not offered here.
     */
    public function contentToFile(string $inPath, string $outPath): void
    {
        if (!function_exists('openssl_cms_verify')) {
            throw new InvalidZfo(null, 'CMS support missing in openssl build', 'Zfo');
        }
        if (!@openssl_cms_verify($inPath, OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_BINARY, null, [], null, $outPath, null, null, OPENSSL_ENCODING_DER)) {
            throw new InvalidZfo(null, 'Not a CMS SignedData (openssl)', 'Zfo');
        }
    }

    public function signer(string $der): SignerInfo
    {
        $in = $this->temp($der);
        $certs = $this->temp('');
        try {
            $validTo = $validFrom = $subject = null;
            if (@openssl_cms_verify($in, OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_BINARY, $certs, [], null, null, null, null, OPENSSL_ENCODING_DER)) {
                $x = openssl_x509_parse((string)file_get_contents($certs));
                if (is_array($x)) {
                    $validFrom = (new \DateTimeImmutable('@' . $x['validFrom_time_t']));
                    $validTo = (new \DateTimeImmutable('@' . $x['validTo_time_t']));
                    $subject = is_array($x['subject'] ?? null) ? ($x['subject']['CN'] ?? null) : null;
                }
            }
            return new SignerInfo($this->signingTime($der), $validFrom, $validTo, is_string($subject) ? $subject : null);
        } finally {
            $this->remove($in, $certs);
        }
    }

    private function viaOpenssl(string $der): ?string
    {
        $in = $this->temp($der);
        $out = $this->temp('');
        try {
            $ok = @openssl_cms_verify($in, OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_BINARY, null, [], null, $out, null, null, OPENSSL_ENCODING_DER);
            return $ok ? (string)file_get_contents($out) : null;
        } finally {
            $this->remove($in, $out);
        }
    }

    /** ContentInfo → [0] SignedData → encapContentInfo → [0] eContent OCTET STRING. */
    private function viaDer(string $der): string
    {
        try {
            $ci = Der::read($der, 0);
            [$oid, $explicit] = Der::children($der, $ci);
            if (Der::content($der, $oid) !== self::OID_SIGNED_DATA) {
                throw new InvalidZfo(null, 'Not a CMS SignedData', 'Zfo');
            }
            $signedData = Der::children($der, $explicit)[0];
            $encap = Der::children($der, $signedData)[2];         // version, digestAlgorithms, encapContentInfo
            $eContentExplicit = Der::children($der, $encap)[1] ?? throw new InvalidZfo(null, 'Detached CMS', 'Zfo');
            $octets = Der::children($der, $eContentExplicit)[0];
            if (!$octets['constructed']) {
                return Der::content($der, $octets);
            }
            return implode('', array_map(static fn($c) => Der::content($der, $c), Der::children($der, $octets)));
        } catch (InvalidZfo $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidZfo(null, 'Malformed CMS structure', 'Zfo', $e);
        }
    }

    private function signingTime(string $der): ?\DateTimeImmutable
    {
        $p = strpos($der, self::OID_SIGNING_TIME);
        if ($p === false) {
            return null;
        }
        // OID (06 09 …) is followed by SET { UTCTime | GeneralizedTime }
        $set = Der::read($der, $p + strlen(self::OID_SIGNING_TIME));
        $time = Der::children($der, $set)[0] ?? null;
        if ($time === null) {
            return null;
        }
        $s = Der::content($der, $time);
        $dt = $time['tag'] === 0x17
            ? \DateTimeImmutable::createFromFormat('ymdHis\Z', $s, new \DateTimeZone('UTC'))
            : \DateTimeImmutable::createFromFormat('YmdHis\Z', $s, new \DateTimeZone('UTC'));
        return $dt ?: null;
    }

    private function temp(string $content): string
    {
        $path = tempnam($this->tempDir ?? sys_get_temp_dir(), 'isds-zfo-');
        if ($path === false) {
            throw new \RuntimeException('Cannot create ZFO temp file');
        }
        chmod($path, 0600);
        file_put_contents($path, $content);
        return $path;
    }

    private function remove(string ...$paths): void
    {
        foreach ($paths as $p) {
            if (is_file($p)) {
                unlink($p);
            }
        }
    }
}
