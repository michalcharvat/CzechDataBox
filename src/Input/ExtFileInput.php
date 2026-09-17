<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

use MichalCharvat\CzechDataBox\Dto\UploadedAttachment;

/** dmExtFile of CreateBigMessage: a previously uploaded attachment plus its role in the message. */
final class ExtFileInput
{
    public function __construct(
        public readonly UploadedAttachment $attachment,
        public readonly string $metaType = 'enclosure',
        public readonly ?string $fileGuid = null,
        public readonly ?string $upFileGuid = null,
    ) {
        if (!in_array($metaType, FileInput::META_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown dmFileMetaType ' . $metaType);
        }
    }
}
