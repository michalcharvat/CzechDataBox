<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;

final class SoapTransport implements TransportInterface
{
    public function __construct(private readonly CurlSoapClient $client)
    {
    }

    public function call(string $operation, array $params): \stdClass
    {
        $this->client->beginOperation($operation);
        try {
            // No parameters: null encodes as an empty request element for both xs:string
            // (DummyOperation) and complex inputs; [] would be "Array" or an encoding fault.
            $result = $this->client->__soapCall($operation, [$params === [] ? null : $params]);
        } catch (\SoapFault $fault) {
            $transport = $this->client->takeTransportError();
            if ($transport !== null) {
                throw $transport;
            }
            throw new IsdsException($fault->faultcode ?? null, 'SOAP fault: ' . $fault->getMessage(), $operation, $fault);
        }
        if (!$result instanceof \stdClass) {
            throw new ServiceUnavailable(null, 'Empty SOAP response', $operation);
        }
        return $result;
    }
}
