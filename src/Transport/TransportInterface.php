<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

interface TransportInterface
{
    /**
     * Calls one SOAP operation. Returns the raw response body object; never throws \SoapFault.
     *
     * @param array<string, mixed> $params
     * @throws \MichalCharvat\CzechDataBox\Exception\IsdsException
     */
    public function call(string $operation, array $params): \stdClass;
}
