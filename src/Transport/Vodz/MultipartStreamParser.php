<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

/**
 * Incremental multipart/related parser. The root (SOAP XML) part is kept in memory up to
 * $maxRootBytes; every other part is written to the stream the sink factory returns for its
 * Content-ID. Only a delimiter-length tail is ever buffered.
 *
 * @internal
 */
final class MultipartStreamParser implements BodySink
{
    private const STATE_PREAMBLE = 0;
    private const STATE_HEADERS = 1;
    private const STATE_BODY = 2;
    private const STATE_DONE = 3;
    private const STATE_AFTER_DELIMITER = 4;

    private string $delimiter;
    private string $startCid;
    private string $buffer = '';
    private int $state = self::STATE_PREAMBLE;
    private string $root = '';
    private bool $inRoot = false;
    /** @var resource|null */
    private $sink = null;
    /** @var list<string> */
    private array $binaryIds = [];
    /** @var \Closure(string): resource */
    private \Closure $sinkFactory;

    /** @param callable(string): resource $sinkFactory */
    public function __construct(string $contentType, callable $sinkFactory, private readonly int $maxRootBytes = 1_048_576)
    {
        if (!preg_match('/boundary="?([^";]+)"?/i', $contentType, $m)) {
            throw new \RuntimeException('multipart response without boundary');
        }
        $this->delimiter = "\r\n--" . $m[1];
        $this->startCid = preg_match('/start="?<?([^">;]+)>?"?/i', $contentType, $s) ? $s[1] : '';
        $this->sinkFactory = \Closure::fromCallable($sinkFactory);
        $this->buffer = "\r\n"; // lets the first delimiter match without a leading CRLF
    }

    public function write(string $chunk): void
    {
        $this->buffer .= $chunk;
        while ($this->step()) {
        }
    }

    public function finish(): void
    {
        // ISDS (WS manual sample) may end right after the last delimiter, without the closing "--".
        $endedAfterDelimiter = ($this->state === self::STATE_AFTER_DELIMITER || $this->state === self::STATE_HEADERS)
            && trim($this->buffer) === '' && $this->root !== '';
        if ($this->state !== self::STATE_DONE && !$endedAfterDelimiter) {
            throw new \RuntimeException('Truncated multipart response');
        }
    }

    public function rootXml(): string
    {
        return $this->root;
    }

    /** @return list<string> */
    public function binaryContentIds(): array
    {
        return $this->binaryIds;
    }

    private function step(): bool
    {
        switch ($this->state) {
            case self::STATE_PREAMBLE:
                $p = strpos($this->buffer, $this->delimiter);
                if ($p === false) {
                    $this->buffer = substr($this->buffer, -strlen($this->delimiter));
                    return false;
                }
                $this->buffer = substr($this->buffer, $p + strlen($this->delimiter));
                $this->state = self::STATE_AFTER_DELIMITER;
                return true;

            case self::STATE_AFTER_DELIMITER:
                return $this->afterDelimiter();

            case self::STATE_HEADERS:
                $p = strpos($this->buffer, "\r\n\r\n");
                if ($p === false) {
                    if (strlen($this->buffer) > 16_384) {
                        throw new \RuntimeException('Multipart headers too large');
                    }
                    return false;
                }
                $this->openPart(substr($this->buffer, 0, $p));
                $this->buffer = substr($this->buffer, $p + 4);
                $this->state = self::STATE_BODY;
                return true;

            case self::STATE_BODY:
                $p = strpos($this->buffer, $this->delimiter);
                if ($p === false) {
                    $safe = strlen($this->buffer) - strlen($this->delimiter);
                    if ($safe > 0) {
                        $this->emit(substr($this->buffer, 0, $safe));
                        $this->buffer = substr($this->buffer, $safe);
                    }
                    return false;
                }
                $this->emit(substr($this->buffer, 0, $p));
                $this->buffer = substr($this->buffer, $p + strlen($this->delimiter));
                $this->state = self::STATE_AFTER_DELIMITER;
                return true;
        }
        return false;
    }

    /** Runs in STATE_AFTER_DELIMITER; waits (state kept) until the two bytes after the delimiter have arrived. */
    private function afterDelimiter(): bool
    {
        if (strlen($this->buffer) < 2) {
            return false; // need to see "--" or CRLF; the next write() resumes in this state
        }
        if (str_starts_with($this->buffer, '--')) {
            $this->state = self::STATE_DONE;
            $this->buffer = '';
            return false;
        }
        if (!str_starts_with($this->buffer, "\r\n")) {
            throw new \RuntimeException('Malformed multipart delimiter line');
        }
        $this->buffer = substr($this->buffer, 2);
        $this->state = self::STATE_HEADERS;
        return true;
    }

    private function openPart(string $headers): void
    {
        $cid = preg_match('/^Content-ID:\s*<?([^>\r\n]+)>?/mi', $headers, $m) ? trim($m[1]) : '';
        $this->inRoot = $this->root === '' && ($this->startCid === '' || $cid === $this->startCid);
        if (!$this->inRoot) {
            $this->binaryIds[] = $cid;
            $this->sink = ($this->sinkFactory)($cid);
        }
    }

    private function emit(string $data): void
    {
        if ($data === '') {
            return;
        }
        if ($this->inRoot) {
            $this->root .= $data;
            if (strlen($this->root) > $this->maxRootBytes) {
                throw new \RuntimeException('SOAP root part exceeds ' . $this->maxRootBytes . ' bytes');
            }
            return;
        }
        if ($this->sink === null || fwrite($this->sink, $data) !== strlen($data)) {
            throw new \RuntimeException('Cannot write multipart binary part');
        }
    }
}
