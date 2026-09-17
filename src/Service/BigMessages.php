<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;

final class BigMessages
{
    public function __construct(private readonly VodzTransport $transport)
    {
    }
}
