<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

/** Builds CMS SignedData (DER) around arbitrary inner XML with a throw-away self-signed certificate. */
final class ZfoFactory
{
    // WS manual 3.8.1: signed content = MessageDownload / GetDeliveryInfo output "only with a different namespace"
    public const NS_MESSAGE = 'http://isds.czebox.cz/v20/message';
    public const NS_SENT = 'http://isds.czebox.cz/v20/SentMessage';
    public const NS_DELIVERY = 'http://isds.czebox.cz/v20/delivery';

    public static function sign(string $innerXml): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'ISDS test'], $key);
        $cert = openssl_csr_sign($csr, null, $key, 1);
        if ($key === false || $csr === false || $cert === false) {
            throw new \RuntimeException('Cannot create test certificate: ' . openssl_error_string());
        }
        $dir = sys_get_temp_dir();
        $in = tempnam($dir, 'zfo-in-');
        $out = tempnam($dir, 'zfo-out-');
        try {
            file_put_contents($in, $innerXml);
            if (!openssl_cms_sign($in, $out, $cert, $key, null, OPENSSL_CMS_BINARY, OPENSSL_ENCODING_DER)) {
                throw new \RuntimeException('openssl_cms_sign failed: ' . openssl_error_string());
            }
            return (string)file_get_contents($out);
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    /** @param list<array{string, string, string}> $files [descr, mime, bytes] */
    public static function receivedMessage(string $dmID, array $files, string $ns = self::NS_MESSAGE): string
    {
        $fx = '';
        foreach ($files as $i => [$descr, $mime, $bytes]) {
            $fx .= '<p:dmFile dmMimeType="' . $mime . '" dmFileMetaType="' . ($i === 0 ? 'main' : 'enclosure')
                . '" dmFileDescr="' . htmlspecialchars($descr, ENT_XML1 | ENT_QUOTES) . '"><p:dmEncodedContent>'
                . chunk_split(base64_encode($bytes), 76, "\n") . '</p:dmEncodedContent></p:dmFile>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<q:MessageDownloadResponse xmlns:q="' . $ns . '" xmlns:p="' . $ns . '">'
            . '<q:dmReturnedMessage dmType="V" dmVODZ="false" attsNum="' . count($files) . '"><p:dmDm><p:dmID>' . $dmID . '</p:dmID><p:dbIDSender>abc1234</p:dbIDSender>'
            . '<p:dmSender>Úřad</p:dmSender><p:dmAnnotation>Předmět</p:dmAnnotation>'
            . '<p:dmFiles>' . $fx . '</p:dmFiles></p:dmDm>'
            . '<p:dmHash algorithm="SHA-256">aGFzaA==</p:dmHash>'
            . '<p:dmDeliveryTime>2026-09-01T10:00:00.000+02:00</p:dmDeliveryTime>'
            . '<p:dmMessageStatus>6</p:dmMessageStatus></q:dmReturnedMessage></q:MessageDownloadResponse>';
    }

    public static function deliveryInfo(string $dmID): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<q:GetDeliveryInfoResponse xmlns:q="' . self::NS_DELIVERY . '" xmlns:p="' . self::NS_DELIVERY . '">'
            . '<q:dmDelivery><p:dmDm><p:dmID>' . $dmID . '</p:dmID></p:dmDm>'
            . '<p:dmEvents><p:dmEvent><p:dmEventTime>2026-09-01T10:00:00+02:00</p:dmEventTime>'
            . '<p:dmEventDescr>EV5: Doručeno</p:dmEventDescr></p:dmEvent></p:dmEvents>'
            . '</q:dmDelivery></q:GetDeliveryInfoResponse>';
    }
}
