<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

final class TransportOptions
{
    public function __construct(
        public readonly int $connectTimeout = 10,
        public readonly int $timeout = 120,
        public readonly ?string $caFile = null,
        public readonly string $userAgent = 'michalcharvat/czech-data-box 2',
        public readonly ?string $tempDir = null,
    ) {
    }

    public function caFile(): string
    {
        return $this->caFile ?? dirname(__DIR__, 2) . '/resources/ca/isds-ca-bundle.pem';
    }

    public function wsdl(string $file): string
    {
        return dirname(__DIR__, 2) . '/resources/wsdl/' . $file;
    }

    public function tempDir(): string
    {
        return $this->tempDir ?? sys_get_temp_dir();
    }
}
