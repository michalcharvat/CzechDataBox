<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

/**
 * Builds a multipart/related MTOM request body as a lazy sequence of string and stream segments.
 * Nothing is copied into a temp stream (php://temp spills to disk above its memory limit, and the
 * library never writes message content to disk). @internal
 */
final class MultipartWriter
{
    private string $boundary;
    /** @var list<array{string, resource, string}> cid, stream, content type */
    private array $parts = [];

    public function __construct()
    {
        $this->boundary = 'uuid:' . bin2hex(random_bytes(16));
    }

    /** @param resource $stream seekable, with a known size (fstat); read from its start */
    public function addBinary($stream, string $contentType): string
    {
        $size = fstat($stream)['size'] ?? null;
        if (!is_int($size) || !rewind($stream)) {
            throw new \InvalidArgumentException('MTOM attachment stream must be seekable with a known size');
        }
        $cid = bin2hex(random_bytes(8)) . '@isds-client';
        $this->parts[] = [$cid, $stream, $contentType];
        return $cid;
    }

    public static function xopInclude(string $cid): string
    {
        return '<xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:' . $cid . '"/>';
    }

    public function contentType(): string
    {
        return 'multipart/related; type="application/xop+xml"; boundary="' . $this->boundary
            . '"; start="<root@isds-client>"; start-info="application/soap+xml"';
    }

    /** @param string $action SOAP 1.2 action, carried on the root part as in the WS manual sample */
    public function build(string $soapXml, string $action = ''): MultipartBody
    {
        $segments = ['--' . $this->boundary . "\r\n"
            . "Content-Type: application/xop+xml; charset=UTF-8; type=\"application/soap+xml\"; action=\"" . $action . "\"\r\n"
            . "Content-Transfer-Encoding: 8bit\r\nContent-ID: <root@isds-client>\r\n\r\n" . $soapXml];
        foreach ($this->parts as [$cid, $stream, $type]) {
            $segments[] = "\r\n--" . $this->boundary . "\r\nContent-Type: " . $type
                . "\r\nContent-Transfer-Encoding: binary\r\nContent-ID: <" . $cid . ">\r\n\r\n";
            $segments[] = $stream;
        }
        $segments[] = "\r\n--" . $this->boundary . "--\r\n";
        return new MultipartBody($segments);
    }
}
