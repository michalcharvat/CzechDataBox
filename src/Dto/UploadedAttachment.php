<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Exception\MalformedResponse;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** UploadAttachmentResponse: reference for CreateBigMessage/dmExtFile. ISDS keeps unused uploads ~2 hours. */
final class UploadedAttachment
{
    public function __construct(
        public readonly string $dmAttID,
        public readonly string $dmAttHash1,
        public readonly string $dmAttHash1Alg,
        public readonly string $dmAttHash2,
        public readonly string $dmAttHash2Alg,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $r): self
    {
        $missing = static fn(string $what) => new MalformedResponse(null, 'UploadAttachmentResponse lacks ' . $what, 'UploadAttachment');
        $h1 = N::child($r, 'dmAttHash1') ?? throw $missing('dmAttHash1');
        $h2 = N::child($r, 'dmAttHash2') ?? throw $missing('dmAttHash2');
        return new self(
            N::string($r, 'dmAttID') ?? throw $missing('dmAttID'),
            trim(N::string($h1, '_') ?? ''),
            N::string($h1, 'AttHashAlg') ?? '',
            trim(N::string($h2, '_') ?? ''),
            N::string($h2, 'AttHashAlg') ?? '',
            $r,
        );
    }
}
