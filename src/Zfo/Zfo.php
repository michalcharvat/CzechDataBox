<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

use MichalCharvat\CzechDataBox\Dto\DeliveryEvent;
use MichalCharvat\CzechDataBox\Dto\DeliveryInfo;
use MichalCharvat\CzechDataBox\Dto\File;
use MichalCharvat\CzechDataBox\Dto\Message;
use MichalCharvat\CzechDataBox\Dto\MessageEnvelope;
use MichalCharvat\CzechDataBox\Exception\InvalidZfo;

/**
 * Reads ISDS ZFO documents (CMS SignedData around the MessageDownload / GetDeliveryInfo XML).
 * The signature is unwrapped, not verified — use MessageOperations::authenticateMessage() for that.
 */
final class Zfo
{
    public static function kind(string $zfo): ZfoKind
    {
        $parser = new ZfoXmlParser(new ZfoLimits(), static fn() => throw new InvalidZfo(null, 'unreachable', 'Zfo'), stopAfterRoot: true);
        $parser->feed((new CmsUnwrapper())->content($zfo), true);
        if ($parser->rootNamespace() === null) {
            throw new InvalidZfo(null, 'Empty ZFO', 'Zfo');
        }
        return ZfoKind::fromNamespace($parser->rootNamespace())
            ?? throw new InvalidZfo(null, 'Unknown ZFO root namespace ' . $parser->rootNamespace(), 'Zfo');
    }

    /** Received or sent message, fully in memory (normal messages ≤ 20 MB). */
    public static function parse(string $zfo, ZfoLimits $limits = new ZfoLimits()): Message
    {
        $xml = (new CmsUnwrapper())->content($zfo);
        if (strlen($xml) > $limits->maxInMemoryBytes) {
            throw new InvalidZfo(null, 'ZFO too large for in-memory parse; use parseStream()', 'Zfo');
        }
        /** @var array<int, resource> $sinks */
        $sinks = [];
        $parser = new ZfoXmlParser($limits, static function (array $meta, int $index) use (&$sinks) {
            $s = fopen('php://memory', 'w+b');
            if ($s === false) {
                throw new InvalidZfo(null, 'Cannot buffer file content', 'Zfo');
            }
            return $sinks[$index] = $s;
        });
        $parser->feed($xml, true);
        self::assertKind($parser, [ZfoKind::ReceivedMessage, ZfoKind::SentMessage]);

        $files = [];
        foreach ($parser->fileMeta() as $i => $meta) {
            $content = '';
            if (isset($sinks[$i]) && rewind($sinks[$i])) {
                $content = (string)stream_get_contents($sinks[$i]);
            }
            $files[] = File::fromRaw((object)($meta + ['dmEncodedContent' => $content]));
        }
        return new Message(self::envelope($parser), $files, (object)$parser->fields());
    }

    /**
     * Bounded-memory parse (VoDZ). Unwrap goes through 0600 temp files; each file is decoded into
     * the stream $fileSink returns for its metadata (dmFileDescr, dmMimeType, dmFileMetaType, …) and index.
     * Requires openssl_cms_verify (no DER fallback for streams).
     *
     * @param resource $zfo
     * @param callable(array<string, string>, int): resource $fileSink
     */
    public static function parseStream($zfo, callable $fileSink, ZfoLimits $limits = new ZfoLimits(), ?string $tempDir = null): MessageEnvelope
    {
        $unwrapper = new CmsUnwrapper($tempDir);
        $in = self::tmp($tempDir);
        $out = self::tmp($tempDir);
        try {
            $fh = fopen($in, 'wb');
            if ($fh === false) {
                throw new InvalidZfo(null, 'Cannot buffer ZFO', 'Zfo');
            }
            try {
                $copied = stream_copy_to_stream($zfo, $fh, $limits->maxZfoBytes + 1);
                if ($copied === false) {
                    throw new InvalidZfo(null, 'Cannot buffer ZFO', 'Zfo');
                }
                if ($copied > $limits->maxZfoBytes) {
                    throw new InvalidZfo(null, 'ZFO exceeds ' . $limits->maxZfoBytes . ' bytes', 'Zfo');
                }
            } finally {
                fclose($fh);
            }
            $unwrapper->contentToFile($in, $out);
            $parser = new ZfoXmlParser($limits, \Closure::fromCallable($fileSink));
            $rh = fopen($out, 'rb');
            if ($rh === false) {
                throw new InvalidZfo(null, 'Cannot read unwrapped ZFO', 'Zfo');
            }
            try {
                while (!feof($rh)) {
                    $parser->feed((string)fread($rh, 262_144));
                }
                $parser->feed('', true);
            } finally {
                fclose($rh);
            }
            self::assertKind($parser, [ZfoKind::ReceivedMessage, ZfoKind::SentMessage]);
            return self::envelope($parser);
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    public static function parseDeliveryInfo(string $zfo, ZfoLimits $limits = new ZfoLimits()): DeliveryInfo
    {
        $parser = new ZfoXmlParser($limits, static fn() => throw new InvalidZfo(null, 'Files in delivery info', 'Zfo'));
        $parser->feed((new CmsUnwrapper())->content($zfo), true);
        self::assertKind($parser, [ZfoKind::DeliveryInfo]);
        $events = array_map(static fn(array $e) => DeliveryEvent::fromRaw((object)$e), $parser->events());
        return new DeliveryInfo(self::envelope($parser), $events, (object)$parser->fields());
    }

    public static function signer(string $zfo): SignerInfo
    {
        return (new CmsUnwrapper())->signer($zfo);
    }

    private static function envelope(ZfoXmlParser $parser): MessageEnvelope
    {
        $f = $parser->fields();
        $raw = (object)$f;
        if (isset($f['dmHash'])) {
            // xs:base64Binary: ext-soap hands DTOs the decoded bytes, so decode here too
            $raw->dmHash = (object)['_' => self::binary($f['dmHash'], 'dmHash'), 'algorithm' => $f['dmHashAlgorithm'] ?? null];
        }
        if (isset($f['dmQTimestamp'])) {
            $raw->dmQTimestamp = self::binary($f['dmQTimestamp'], 'dmQTimestamp');
        }
        return MessageEnvelope::fromRaw($raw);
    }

    private static function binary(string $base64, string $element): string
    {
        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            throw new InvalidZfo(null, 'Invalid base64 in ' . $element, 'Zfo');
        }
        return $bytes;
    }

    /** @param list<ZfoKind> $allowed */
    private static function assertKind(ZfoXmlParser $parser, array $allowed): void
    {
        $kind = ZfoKind::fromNamespace($parser->rootNamespace());
        if (!in_array($kind, $allowed, true)) {
            throw new InvalidZfo(null, 'Unexpected ZFO kind (root namespace ' . $parser->rootNamespace() . ')', 'Zfo');
        }
    }

    private static function tmp(?string $dir): string
    {
        $p = tempnam($dir ?? sys_get_temp_dir(), 'isds-zfo-');
        if ($p === false) {
            throw new \RuntimeException('Cannot create temp file');
        }
        chmod($p, 0600);
        return $p;
    }
}
