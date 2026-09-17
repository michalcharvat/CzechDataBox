<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

/**
 * Stream wrapper that reports a large size but stops delivering data part-way through without reaching
 * EOF — what a network filesystem looks like when it drops mid-upload.
 */
final class FailingStream
{
    public const SIZE = 300_000;
    private const GOOD_BYTES = 8_192;

    /** @var resource|null */
    public $context;
    private int $position = 0;

    public static function register(): void
    {
        if (!in_array('failing', stream_get_wrappers(), true)) {
            stream_wrapper_register('failing', self::class);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_read(int $count): string
    {
        if ($this->position >= self::GOOD_BYTES) {
            return '';                       // no data, and not EOF either
        }
        $chunk = str_repeat('x', min($count, self::GOOD_BYTES - $this->position));
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= self::SIZE;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->position = $whence === SEEK_SET ? $offset : $this->position + $offset;
        return true;
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    /** @return array<int|string, int> */
    public function stream_stat(): array
    {
        return ['size' => self::SIZE, 7 => self::SIZE];
    }
}
