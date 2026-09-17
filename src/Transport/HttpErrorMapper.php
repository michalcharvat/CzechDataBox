<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

use MichalCharvat\CzechDataBox\Exception\AuthenticationFailed;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;

/** @internal */
final class HttpErrorMapper
{
    public static function map(int $httpStatus, int $curlErrno, string $curlError, string $operation): ?IsdsException
    {
        if ($curlErrno !== 0) {
            return new ServiceUnavailable(null, 'Network error: ' . $curlError, $operation);
        }
        if ($httpStatus === 401 || $httpStatus === 403) {
            return new AuthenticationFailed((string)$httpStatus, 'Login rejected by ISDS (HTTP ' . $httpStatus . ')', $operation);
        }
        if ($httpStatus === 200 || $httpStatus === 500) {
            return null; // 500 carries a SOAP fault body
        }
        return new ServiceUnavailable((string)$httpStatus, 'Unexpected HTTP status ' . $httpStatus, $operation);
    }
}
