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
        /** VoDZ/arch calls (large transfers); a lower generic timeout must not apply to them. */
        public readonly int $vodzTimeout = 1800,
        /** Cap for a SOAP response held in memory: the MTOM root part, or a whole non-MTOM response. */
        public readonly int $maxResponseBytes = 8 * 1024 * 1024,
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
