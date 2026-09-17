<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

/** Raw signed CMS (ZFO) bytes as returned by ISDS. */
final class SignedDocument
{
    public function __construct(public readonly string $bytes)
    {
        if ($bytes === '') {
            throw new \InvalidArgumentException('Empty signed document');
        }
    }

    public function sha256(): string
    {
        return hash('sha256', $this->bytes);
    }
}
