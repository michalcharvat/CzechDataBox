<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

/** Receives a VoDZ response body chunk by chunk. @internal */
interface BodySink
{
    public function write(string $chunk): void;

    /** @throws \RuntimeException when the body is incomplete */
    public function finish(): void;

    public function rootXml(): string;

    /** @return list<string> */
    public function binaryContentIds(): array;
}
