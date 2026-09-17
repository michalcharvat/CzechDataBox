<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class File
{
    public function __construct(
        public readonly string $content,
        public readonly ?string $dmMimeType,
        public readonly ?string $dmFileMetaType,
        public readonly ?string $dmFileDescr,
        public readonly ?string $dmFileGuid,
        public readonly ?string $dmUpFileGuid,
        public readonly ?string $dmFormat,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $f): self
    {
        return new self(
            N::binary($f, 'dmEncodedContent') ?? self::xmlContent($f),
            N::string($f, 'dmMimeType'), N::string($f, 'dmFileMetaType'), N::string($f, 'dmFileDescr'),
            N::string($f, 'dmFileGuid'), N::string($f, 'dmUpFileGuid'), N::string($f, 'dmFormat'),
            $f,
        );
    }

    /** dmXMLContent (xs:any) arrives from ext-soap as an object whose `any` holds the XML string. */
    private static function xmlContent(\stdClass $f): string
    {
        $x = N::child($f, 'dmXMLContent');
        return $x !== null && isset($x->any) && is_string($x->any) ? $x->any : '';
    }

    public function size(): int
    {
        return strlen($this->content);
    }

    public function isMain(): bool
    {
        return $this->dmFileMetaType === 'main';
    }
}
