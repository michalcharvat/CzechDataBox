<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

/** Pull-based request body for CURLOPT_READFUNCTION; exact length known up front. @internal */
final class MultipartBody
{
    private int $index = 0;
    private int $offset = 0;
    private readonly int $length;

    /** @param list<string|resource> $segments */
    public function __construct(private readonly array $segments)
    {
        $length = 0;
        foreach ($segments as $segment) {
            $length += is_string($segment) ? strlen($segment) : (int)(fstat($segment)['size'] ?? 0);
        }
        $this->length = $length;
    }

    public function length(): int
    {
        return $this->length;
    }

    /** @return string up to $max bytes; '' when the body is exhausted */
    public function read(int $max): string
    {
        if ($max < 1) {
            return '';
        }
        while ($this->index < count($this->segments)) {
            $segment = $this->segments[$this->index];
            if (is_string($segment)) {
                $chunk = substr($segment, $this->offset, $max);
                $this->offset += strlen($chunk);
            } else {
                $chunk = (string)fread($segment, $max);
            }
            if ($chunk !== '') {
                return $chunk;
            }
            if (!is_string($segment) && !feof($segment)) {
                throw new \RuntimeException('Cannot read MTOM attachment stream');
            }
            $this->index++;
            $this->offset = 0;
        }
        return '';
    }
}
