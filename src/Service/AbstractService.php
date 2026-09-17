<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Internal\StatusGuard;
use MichalCharvat\CzechDataBox\Transport\TransportInterface;

abstract class AbstractService
{
    public function __construct(protected readonly TransportInterface $transport)
    {
    }

    /** @param array<string, mixed> $params */
    protected function call(string $operation, array $params = []): \stdClass
    {
        return StatusGuard::check($this->transport->call($operation, $params), $operation);
    }
}
