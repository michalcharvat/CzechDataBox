<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

use MichalCharvat\CzechDataBox\Exception\InvalidZfo;

/**
 * Streaming expat parser for the inner ZFO XML. Collects every non-file leaf element as a scalar field
 * (local name → text), the attributes of the returned message/delivery element, dmHash's algorithm,
 * dmEvent entries, and streams each dmEncodedContent through a base64 decoder into a caller-provided sink.
 * Element names are matched by local name only. DOCTYPE/ENTITY declarations are rejected.
 *
 * @internal
 */
final class ZfoXmlParser
{
    private const SEP = '|';
    private const HEAD_LIMIT = 65_536;

    private \XMLParser $parser;
    private int $depth = 0;
    private int $elements = 0;
    private ?string $rootNs = null;
    /** @var list<string> local names on the stack */
    private array $stack = [];
    private string $text = '';
    private string $head = '';
    /** @var array<string, string|null> */
    private array $fields = [];
    /** @var list<array<string, string>> */
    private array $events = [];
    /** @var array<string, string> */
    private array $currentEvent = [];
    /** @var list<array<string, string>> */
    private array $fileMeta = [];
    /** @var resource|null */
    private $fileSink = null;
    private string $b64Carry = '';
    private int $fileBytes = 0;
    private int $totalBytes = 0;

    /**
     * @param \Closure(array<string, string>, int): resource $fileSinkFactory metadata and index of the dmFile
     * @param bool $stopAfterRoot parse only up to the root element (kind detection)
     */
    public function __construct(
        private readonly ZfoLimits $limits,
        private readonly \Closure $fileSinkFactory,
        private readonly bool $stopAfterRoot = false,
    ) {
        $p = xml_parser_create_ns('UTF-8', self::SEP);
        xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($p, $this->start(...), $this->end(...));
        xml_set_character_data_handler($p, $this->chars(...));
        xml_set_default_handler($p, $this->other(...));
        $this->parser = $p;
    }

    public function feed(string $chunk, bool $final = false): void
    {
        if ($this->rootNs === null && strlen($this->head) < self::HEAD_LIMIT) {
            // before the root element: look for declarations across chunk boundaries
            $this->head .= $chunk;
            if (stripos($this->head, '<!DOCTYPE') !== false || stripos($this->head, '<!ENTITY') !== false) {
                throw new InvalidZfo(null, 'DOCTYPE/ENTITY not allowed in ZFO', 'Zfo');
            }
        }
        if ($this->stopAfterRoot && $this->rootNs !== null) {
            return;
        }
        if (!xml_parse($this->parser, $chunk, $final)) {
            throw new InvalidZfo(null, 'XML error: ' . xml_error_string(xml_get_error_code($this->parser))
                . ' at line ' . xml_get_current_line_number($this->parser), 'Zfo');
        }
    }

    public function rootNamespace(): ?string
    {
        return $this->rootNs;
    }

    /** @return array<string, string|null> */
    public function fields(): array
    {
        return $this->fields;
    }

    /** @return list<array<string, string>> */
    public function events(): array
    {
        return $this->events;
    }

    /** @return list<array<string, string>> */
    public function fileMeta(): array
    {
        return $this->fileMeta;
    }

    /** @param array<string, string> $attrs */
    private function start(\XMLParser $p, string $name, array $attrs): void
    {
        [$ns, $local] = str_contains($name, self::SEP) ? explode(self::SEP, $name, 2) : ['', $name];
        $this->rootNs ??= $ns;
        if ($this->stopAfterRoot) {
            return;
        }
        if (++$this->depth > $this->limits->maxDepth) {
            throw new InvalidZfo(null, 'XML nesting too deep', 'Zfo');
        }
        if (++$this->elements > $this->limits->maxElements) {
            throw new InvalidZfo(null, 'Too many XML elements', 'Zfo');
        }
        $this->stack[] = $local;
        $this->text = '';
        $plain = [];
        foreach ($attrs as $k => $v) {
            $plain[str_contains($k, self::SEP) ? substr($k, strrpos($k, self::SEP) + 1) : $k] = $v;
        }
        if ($this->depth === 2) {
            // dmReturnedMessage / dmDelivery: dmType, dmVODZ, attsNum, specMessFlag
            foreach ($plain as $k => $v) {
                $this->fields[$k] = $v;
            }
        }
        if ($local === 'dmFile') {
            if (count($this->fileMeta) >= $this->limits->maxFiles) {
                throw new InvalidZfo(null, 'Too many files in ZFO', 'Zfo');
            }
            $this->fileMeta[] = $plain;
        } elseif ($local === 'dmEncodedContent') {
            $index = count($this->fileMeta) - 1;
            $this->fileSink = ($this->fileSinkFactory)($this->fileMeta[$index] ?? [], $index);
            $this->b64Carry = '';
            $this->fileBytes = 0;
        } elseif ($local === 'dmHash') {
            $this->fields['dmHashAlgorithm'] = $plain['algorithm'] ?? null;
        } elseif ($local === 'dmEvent') {
            $this->currentEvent = [];
        }
    }

    private function chars(\XMLParser $p, string $data): void
    {
        if ($this->stopAfterRoot) {
            return;
        }
        if ($this->fileSink !== null) {
            $this->writeBase64($data);
            return;
        }
        $this->text .= $data;
        if (strlen($this->text) > 1_048_576) {
            throw new InvalidZfo(null, 'Text node too large', 'Zfo');
        }
    }

    private function end(\XMLParser $p, string $name): void
    {
        if ($this->stopAfterRoot) {
            return;
        }
        $local = array_pop($this->stack) ?? '';
        $this->depth--;
        $parent = end($this->stack) ?: '';
        if ($local === 'dmEncodedContent') {
            $this->writeBase64('', true);
            $this->fileSink = null;
        } elseif ($parent === 'dmEvent') {
            $this->currentEvent[$local] = $this->text;
        } elseif ($local === 'dmEvent') {
            $this->events[] = $this->currentEvent;
        } elseif (!in_array($local, ['dmFile', 'dmFiles', 'dmEvents', 'dmDm'], true) && trim($this->text) !== ''
            && !in_array('dmFiles', $this->stack, true)) {
            $this->fields[$local] = trim($this->text);
        }
        $this->text = '';
    }

    private function other(\XMLParser $p, string $data): void
    {
        if (stripos($data, '<!DOCTYPE') === 0 || stripos($data, '<!ENTITY') === 0) {
            throw new InvalidZfo(null, 'DOCTYPE/ENTITY not allowed in ZFO', 'Zfo');
        }
    }

    private function writeBase64(string $data, bool $flush = false): void
    {
        $buf = $this->b64Carry . preg_replace('/[^A-Za-z0-9+\/=]/', '', $data);
        $usable = $flush ? strlen($buf) : strlen($buf) - (strlen($buf) % 4);
        $decoded = base64_decode(substr($buf, 0, $usable), true);
        if ($decoded === false) {
            throw new InvalidZfo(null, 'Invalid base64 file content', 'Zfo');
        }
        $this->b64Carry = substr($buf, $usable);
        $len = strlen($decoded);
        $this->fileBytes += $len;
        $this->totalBytes += $len;
        if ($this->fileBytes > $this->limits->maxFileBytes || $this->totalBytes > $this->limits->maxTotalBytes) {
            throw new InvalidZfo(null, 'File content exceeds limit', 'Zfo');
        }
        if ($len > 0 && ($this->fileSink === null || fwrite($this->fileSink, $decoded) !== $len)) {
            throw new InvalidZfo(null, 'Cannot write file content', 'Zfo');
        }
    }
}
