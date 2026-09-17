<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Live;

use MichalCharvat\CzechDataBox\Connection;
use MichalCharvat\CzechDataBox\Credentials\ClientCertificate;
use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Credentials\SystemCertificateCredentials;
use MichalCharvat\CzechDataBox\Environment;
use PHPUnit\Framework\TestCase;

/**
 * Live tests against the public ISDS test environment (datovka-test.gov.cz). Skipped without env:
 * ISDS_TEST_USER, ISDS_TEST_PASS, ISDS_TEST_SELF_DBID [, ISDS_TEST_RECIPIENT_DBID, ISDS_TEST_CERT(+_PASS), ISDS_CAPTURE=1].
 * A skipped live suite is not evidence of anything.
 */
abstract class LiveTestCase extends TestCase
{
    protected static function env(string $name): string
    {
        $v = getenv($name);
        if ($v === false || $v === '') {
            self::markTestSkipped($name . ' not set');
        }
        return $v;
    }

    protected function passwordConnection(): Connection
    {
        return new Connection(Environment::Test, new PasswordCredentials(self::env('ISDS_TEST_USER'), self::env('ISDS_TEST_PASS')));
    }

    protected function certificateConnection(): Connection
    {
        $path = self::env('ISDS_TEST_CERT');
        if (!is_readable($path)) {
            self::markTestSkipped('ISDS_TEST_CERT not readable');
        }
        return new Connection(Environment::Test, new SystemCertificateCredentials(
            new ClientCertificate((string)file_get_contents($path), getenv('ISDS_TEST_CERT_PASS') ?: null),
        ));
    }

    protected static function capturedPath(string $name): string
    {
        return dirname(__DIR__) . '/fixtures/captured/' . $name;
    }

    /** One-page PDF, enough for a test message. */
    protected static function tinyPdf(string $text): string
    {
        $stream = 'BT /F1 18 Tf 72 720 Td (' . $text . ') Tj ET';
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $o . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        return $pdf . "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    }
}
