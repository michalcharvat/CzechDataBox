<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox;

use MichalCharvat\CzechDataBox\Credentials\Credentials;
use MichalCharvat\CzechDataBox\Endpoint\EndpointTable;
use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Service as Svc;
use MichalCharvat\CzechDataBox\Transport\CurlSoapClient;
use MichalCharvat\CzechDataBox\Transport\SoapTransport;
use MichalCharvat\CzechDataBox\Transport\TransportInterface;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;

final class Connection
{
    /** @var array<string, TransportInterface> */
    private array $transports = [];
    /** @var array<string, object> */
    private array $services = [];
    /** @var (\Closure(Service, string): TransportInterface)|null */
    private readonly ?\Closure $transportFactory;

    /** @var (\Closure(string): VodzTransport)|null */
    private readonly ?\Closure $vodzTransportFactory;

    /**
     * @param (callable(Service, string): TransportInterface)|null $transportFactory for tests (SOAP 1.1 endpoints)
     * @param (callable(string): VodzTransport)|null $vodzTransportFactory for tests (VoDZ endpoint, receives the URL)
     */
    public function __construct(
        public readonly Environment $environment,
        private readonly Credentials $credentials,
        public readonly bool $legacyDomain = false,
        private readonly TransportOptions $options = new TransportOptions(),
        ?callable $transportFactory = null,
        ?callable $vodzTransportFactory = null,
    ) {
        $this->transportFactory = $transportFactory === null ? null : \Closure::fromCallable($transportFactory);
        $this->vodzTransportFactory = $vodzTransportFactory === null ? null : \Closure::fromCallable($vodzTransportFactory);
    }

    public function messageOperations(): Svc\MessageOperations
    {
        return $this->service(Service::Operations, Svc\MessageOperations::class);
    }
    public function messageInfo(): Svc\MessageInfo
    {
        return $this->service(Service::Info, Svc\MessageInfo::class);
    }
    public function search(): Svc\Search
    {
        return $this->service(Service::Search, Svc\Search::class);
    }
    public function access(): Svc\Access
    {
        return $this->service(Service::Access, Svc\Access::class);
    }
    public function manipulations(): Svc\Manipulations
    {
        return $this->service(Service::Manipulations, Svc\Manipulations::class);
    }
    public function archive(): Svc\Archive
    {
        return $this->service(Service::Archive, Svc\Archive::class);
    }

    public function bigMessages(): Svc\BigMessages
    {
        /** @var Svc\BigMessages */
        return $this->services['vodz'] ??= new Svc\BigMessages($this->vodzTransport());
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(Service $service, string $class): object
    {
        $svc = $this->services[$service->name] ??= new $class($this->transport($service));
        assert($svc instanceof $class);
        return $svc;
    }

    private function transport(Service $service): TransportInterface
    {
        if (!isset($this->transports[$service->name])) {
            $url = EndpointTable::url($this->environment, $this->legacyDomain, $this->credentials->kind(), $service);
            $this->transports[$service->name] = $this->transportFactory !== null
                ? ($this->transportFactory)($service, $url)
                : new SoapTransport(new CurlSoapClient($this->options->wsdl($service->wsdl()), $url, $this->credentials, $this->options));
        }
        return $this->transports[$service->name];
    }

    private function vodzTransport(): VodzTransport
    {
        $url = EndpointTable::url($this->environment, $this->legacyDomain, $this->credentials->kind(), Service::BigMessages);
        return $this->vodzTransportFactory !== null
            ? ($this->vodzTransportFactory)($url)
            : new VodzTransport($url, $this->credentials, $this->options);
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['environment' => $this->environment->name, 'legacyDomain' => $this->legacyDomain,
            'credentials' => $this->credentials->kind()->name];
    }
}
