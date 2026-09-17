<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Transport\SoapTransport;
use MichalCharvat\CzechDataBox\Transport\TransportInterface;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;

final class FakeTransport implements TransportInterface
{
    private function __construct(private readonly ReplaySoapClient $client)
    {
    }

    public static function for(Service $service): self
    {
        return new self(new ReplaySoapClient((new TransportOptions())->wsdl($service->wsdl())));
    }

    public function reply(string $operation, string $case): self
    {
        return $this->replyRaw($operation, Fixture::load($operation, $case));
    }

    public function replyRaw(string $operation, string $xml): self
    {
        $this->client->queue[$operation][] = $xml;
        return $this;
    }

    public function call(string $operation, #[\SensitiveParameter] array $params): \stdClass
    {
        return (new SoapTransport($this->client))->call($operation, $params);
    }

    /** @return list<string> */
    public function calledOperations(): array
    {
        return array_column($this->client->requests, 0);
    }

    public function lastRequestXml(): string
    {
        $last = end($this->client->requests);
        return $last === false ? '' : $last[1];
    }

    public static function soapFault(string $code, string $message): string
    {
        return Fixture::envelope('<SOAP-ENV:Fault><faultcode>' . $code . '</faultcode><faultstring>'
            . htmlspecialchars($message) . '</faultstring></SOAP-ENV:Fault>');
    }
}
