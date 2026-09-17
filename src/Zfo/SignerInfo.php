<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

/** Facts about the CMS signature; this library does not judge trust or validity. */
final class SignerInfo
{
    public function __construct(
        public readonly ?\DateTimeImmutable $signingTime,
        public readonly ?\DateTimeImmutable $certificateValidFrom,
        public readonly ?\DateTimeImmutable $certificateValidTo,
        public readonly ?string $subjectCommonName,
    ) {
    }
}
