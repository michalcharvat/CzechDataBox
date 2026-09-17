<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Exception;

class IsdsException extends \RuntimeException
{
    public function __construct(
        public readonly ?string $isdsCode,
        public readonly string $isdsMessage,
        public readonly string $operation,
        ?\Throwable $previous = null,
    ) {
        $prefix = $isdsCode !== null ? '[' . $isdsCode . '] ' : '';
        parent::__construct($operation . ': ' . $prefix . $isdsMessage, 0, $previous);
    }
}
