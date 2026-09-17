<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

/** Non-multipart application/soap+xml response (faults, small results), capped in memory. @internal */
final class PlainBodyCollector implements BodySink
{
    private string $body = '';

    public function __construct(private readonly int $maxBytes = 1_048_576)
    {
    }

    public function write(string $chunk): void
    {
        $this->body .= $chunk;
        if (strlen($this->body) > $this->maxBytes) {
            throw new \RuntimeException('SOAP response exceeds ' . $this->maxBytes . ' bytes without MTOM');
        }
    }

    public function finish(): void
    {
    }

    public function rootXml(): string
    {
        return $this->body;
    }

    public function binaryContentIds(): array
    {
        return [];
    }
}
