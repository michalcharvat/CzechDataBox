<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

use MichalCharvat\CzechDataBox\Credentials\Credentials;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;

final class VodzTransport
{
    public function __construct(
        private readonly string $url,
        private readonly Credentials $credentials,
        private readonly TransportOptions $options,
    ) {
    }
}
