<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/**
 * Sender details stored with a message. GetMessageAuthor2 returns dmMessageAuthor/maItem key-value
 * pairs (keys per the CreateMessage IdLevel table); GetMessageAuthor returns userType + authorName.
 * userType is a string: besides tUserType it can be "VIRTUAL" (system-certificate application).
 */
final class MessageAuthor
{
    public function __construct(
        public readonly ?string $userType,
        public readonly ?string $authorName,
        public readonly ?string $pnGivenNames,
        public readonly ?string $pnLastName,
        public readonly ?\DateTimeImmutable $biDate,
        public readonly ?string $biCity,
        public readonly ?string $biCounty,
        public readonly ?string $adCode,
        public readonly ?string $fullAddress,
        public readonly ?bool $robIdent,
        public readonly \stdClass $raw,
    ) {
    }

    /** @param \stdClass $r GetMessageAuthor2Response / GetMessageAuthorResponse body */
    public static function fromRaw(\stdClass $r): self
    {
        $v = $r;
        if (($author = N::child($r, 'dmMessageAuthor')) !== null) {
            $v = new \stdClass();
            foreach (N::list($author, 'maItem') as $item) {
                $key = N::string($item, 'key');
                if ($key !== null) {
                    $v->{$key} = N::string($item, 'value');
                }
            }
        }
        $given = N::string($v, 'pnGivenNames');
        $last = N::string($v, 'pnLastName');
        $name = N::string($v, 'authorName') ?? (trim(($given ?? '') . ' ' . ($last ?? '')) ?: null);
        return new self(
            N::string($v, 'userType'), $name, $given, $last, N::date($v, 'biDate'),
            N::string($v, 'biCity'), N::string($v, 'biCounty'), N::string($v, 'adCode'),
            N::string($v, 'fullAddress'), N::bool($v, 'robIdent'),
            $r,
        );
    }
}
