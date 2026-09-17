<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

/** BigMessageDownload result: files streamed as MTOM parts have content '' and their Content-ID here. */
final class BigMessage
{
    /** @param list<?string> $contentIds aligned with $message->files; null = content inline in File::$content */
    public function __construct(
        public readonly Message $message,
        public readonly array $contentIds,
    ) {
    }
}
