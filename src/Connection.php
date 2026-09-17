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
    /** @var array<string, VodzTransport> */
    private array $vodzTransports = [];
    /** @var (\Closure(Service, string): TransportInterface)|null */
    private readonly ?\Closure $transportFactory;

    /** @var (\Closure(Service, string): VodzTransport)|null */
    private readonly ?\Closure $vodzTransportFactory;

    /**
     * @param (callable(Service, string): TransportInterface)|null $transportFactory for tests (SOAP 1.1 endpoints)
     * @param (callable(Service, string): VodzTransport)|null $vodzTransportFactory for tests (ws2 SOAP 1.2 + MTOM endpoints: vodz, arch)
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
        /** @var Svc\Archive */
        return $this->services[Service::Archive->name] ??= new Svc\Archive($this->vodzTransport(Service::Archive));
    }

    /** OTP accounts only: build this Connection with password . otpCode as the Basic-auth password. */
    public function passwordChange(): Svc\PasswordChange
    {
        return $this->service(Service::ChangePassword, Svc\PasswordChange::class);
    }

    public function bigMessages(): Svc\BigMessages
    {
        /** @var Svc\BigMessages */
        return $this->services[Service::BigMessages->name] ??= new Svc\BigMessages($this->vodzTransport(Service::BigMessages));
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

    private function vodzTransport(Service $service): VodzTransport
    {
        if (!isset($this->vodzTransports[$service->name])) {
            $url = EndpointTable::url($this->environment, $this->legacyDomain, $this->credentials->kind(), $service);
            $this->vodzTransports[$service->name] = $this->vodzTransportFactory !== null
                ? ($this->vodzTransportFactory)($service, $url)
                : new VodzTransport($url, $this->credentials, $this->options);
        }
        return $this->vodzTransports[$service->name];
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['environment' => $this->environment->name, 'legacyDomain' => $this->legacyDomain,
            'credentials' => $this->credentials->kind()->name];
    }
}
