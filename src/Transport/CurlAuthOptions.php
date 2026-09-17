<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

use MichalCharvat\CzechDataBox\Credentials\Credentials;

/** cURL TLS, Basic-auth and client-certificate options shared by both transports. @internal */
final class CurlAuthOptions
{
    /** @return array<int, mixed> */
    public static function tls(TransportOptions $options): array
    {
        return [
            CURLOPT_CONNECTTIMEOUT => $options->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CAINFO => $options->caFile(),
            CURLOPT_USERAGENT => $options->userAgent,
        ];
    }

    /**
     * @param CertificateFile|null $certFile in/out: created on first use when blobs are unsupported;
     *                                       the caller keeps it alive for as long as it makes requests
     * @return array<int, mixed>
     */
    public static function for(Credentials $credentials, TransportOptions $options, ?CertificateFile &$certFile): array
    {
        $o = [];
        if ($basic = $credentials->basicAuth()) {
            $o[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $o[CURLOPT_USERPWD] = $basic[0] . ':' . $basic[1];
        }
        if ($cert = $credentials->certificate()) {
            $o[CURLOPT_SSLCERTTYPE] = 'PEM';
            $o[CURLOPT_SSLKEYTYPE] = 'PEM';
            if (defined('CURLOPT_SSLCERT_BLOB') && defined('CURLOPT_SSLKEY_BLOB')) {
                $o[CURLOPT_SSLCERT_BLOB] = $cert->pem;
                $o[CURLOPT_SSLKEY_BLOB] = $cert->pem;
            } else {
                $certFile ??= new CertificateFile($cert->pem, $options->tempDir());
                $o[CURLOPT_SSLCERT] = $certFile->path;
            }
            if ($cert->passphrase !== null) {
                $o[CURLOPT_KEYPASSWD] = $cert->passphrase;
            }
        }
        return $o;
    }
}
