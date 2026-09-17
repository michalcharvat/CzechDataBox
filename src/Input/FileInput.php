<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

final class FileInput
{
    public const META_TYPES = ['main', 'enclosure', 'signature', 'meta'];

    public function __construct(
        public readonly string $content,
        public readonly string $mimeType,
        public readonly string $description,
        public readonly string $metaType = 'enclosure',
        public readonly ?string $fileGuid = null,
        public readonly ?string $upFileGuid = null,
        public readonly ?string $format = null,
    ) {
        if (!in_array($metaType, self::META_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown dmFileMetaType ' . $metaType);
        }
    }

    /** @return array<string, mixed> SoapClient array for tFilesArray/dmFile */
    public function toSoap(): array
    {
        return array_filter([
            'dmEncodedContent' => $this->content, // ext-soap base64-encodes xsd:base64Binary
            'dmMimeType' => $this->mimeType,
            'dmFileMetaType' => $this->metaType,
            'dmFileDescr' => $this->description,
            'dmFileGuid' => $this->fileGuid,
            'dmUpFileGuid' => $this->upFileGuid,
            'dmFormat' => $this->format,
        ], static fn($v) => $v !== null);
    }
}
