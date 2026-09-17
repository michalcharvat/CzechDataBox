<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\SignedDocument;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;
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

    /** @param array<string, mixed> $params */
    protected function signed(string $op, array $params): SignedDocument
    {
        $raw = $this->call($op, $params);
        return new SignedDocument(N::binary($raw, 'dmSignature') ?? throw $this->missing($op, 'dmSignature'));
    }

    protected function missing(string $op, string $element): IsdsException
    {
        return new IsdsException(null, 'Response lacks ' . $element, $op);
    }
}
