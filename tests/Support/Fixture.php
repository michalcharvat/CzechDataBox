<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

final class Fixture
{
    public static function load(string $operation, string $case): string
    {
        $path = dirname(__DIR__) . '/fixtures/' . $operation . '/' . $case . '.xml';
        $xml = file_get_contents($path);
        if ($xml === false) {
            throw new \RuntimeException('Missing fixture ' . $path);
        }
        return $xml;
    }

    /** Wraps a response body element in a SOAP 1.1 envelope. */
    public static function envelope(string $bodyXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body>'
            . $bodyXml . '</SOAP-ENV:Body></SOAP-ENV:Envelope>';
    }
}
