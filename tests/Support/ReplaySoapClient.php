<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Transport\CurlSoapClient;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;

/** Real ext-soap parsing over the bundled WSDL; the HTTP round trip is replaced by a queue of bodies. */
final class ReplaySoapClient extends CurlSoapClient
{
    /** @var array<string, list<string>> */
    public array $queue = [];
    /** @var list<array{string, string}> operation, request XML */
    public array $requests = [];
    private string $op = '';

    public function __construct(string $wsdl)
    {
        parent::__construct($wsdl, 'https://replay.invalid/', new PasswordCredentials('u', 'p'), new TransportOptions());
    }

    public function beginOperation(string $operation): void
    {
        parent::beginOperation($operation);
        $this->op = $operation;
    }

    public function __doRequest(#[\SensitiveParameter] string $request, string $location, string $action, int $version, bool $oneWay = false, ?string $uriParserClass = null): ?string
    {
        $this->requests[] = [$this->op, $request];
        if (empty($this->queue[$this->op])) {
            throw new \LogicException('No fixture queued for ' . $this->op);
        }
        return array_shift($this->queue[$this->op]);
    }
}
