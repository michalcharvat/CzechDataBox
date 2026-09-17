<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Internal;

use MichalCharvat\CzechDataBox\Exception\IsdsException;

/** @internal */
final class StatusGuard
{
    public static function check(\stdClass $raw, string $operation): \stdClass
    {
        [$code, $message] = self::extract($raw);
        if ($code === null) {
            throw new IsdsException(null, 'Response carries no dmStatus/dbStatus', $operation);
        }
        if (ErrorCodes::isSuccess($code)) {
            return $raw;
        }
        $class = ErrorCodes::MAP[$code] ?? IsdsException::class;
        throw new $class($code, $message ?? '', $operation);
    }

    /** @return array{?string, ?string} */
    private static function extract(\stdClass $raw): array
    {
        if ($s = Normalize::child($raw, 'dmStatus')) {
            return [Normalize::string($s, 'dmStatusCode'), Normalize::string($s, 'dmStatusMessage')];
        }
        if ($s = Normalize::child($raw, 'dbStatus')) {
            return [Normalize::string($s, 'dbStatusCode'), Normalize::string($s, 'dbStatusMessage')];
        }
        return [null, null];
    }
}
