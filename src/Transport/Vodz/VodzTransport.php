<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport\Vodz;

use MichalCharvat\CzechDataBox\Credentials\Credentials;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;
use MichalCharvat\CzechDataBox\Transport\CertificateFile;
use MichalCharvat\CzechDataBox\Transport\CurlAuthOptions;
use MichalCharvat\CzechDataBox\Transport\HttpErrorMapper;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;

/**
 * SOAP 1.2 + MTOM over cURL for the ws2 endpoints (vodz, arch). Request XML is built by the caller;
 * binary parts are streamed in and out, never held in memory.
 *
 * WS manual 3.8.1, 1.3.3: uploads are multipart/related; type="application/xop+xml" with Content-Length;
 * binary results come back as MTOM parts only when the request sends Accept: multipart/related,
 * otherwise base64 inside the XML (capped by PlainBodyCollector).
 */
class VodzTransport
{
    private ?CertificateFile $certFile = null;

    public function __construct(
        private readonly string $url,
        private readonly Credentials $credentials,
        private readonly TransportOptions $options,
    ) {
    }

    /**
     * @param string $bodyXml the SOAP Body child element (namespaced), binaries referenced by {{cid0}}, {{cid1}}…
     * @param list<array{resource, string}> $binaries seekable streams with known size + content types
     * @param callable(string): resource $sinkFactory destination per response binary Content-ID
     * @return array{\DOMDocument, list<string>} parsed root XML and binary Content-IDs in order
     */
    public function call(string $operation, #[\SensitiveParameter] string $bodyXml, array $binaries, callable $sinkFactory): array
    {
        $writer = new MultipartWriter();
        foreach ($binaries as $i => [$stream, $type]) {
            $bodyXml = str_replace('{{cid' . $i . '}}', MultipartWriter::xopInclude($writer->addBinary($stream, $type)), $bodyXml);
        }
        $envelope = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'
            . '<soap:Body>' . $bodyXml . '</soap:Body></soap:Envelope>';
        $request = $writer->build($envelope, $operation);

        $responseType = '';
        /** @var BodySink|null $sink */
        $sink = null;
        /** @var \Throwable|null $callbackError */
        $callbackError = null;
        $ch = curl_init($this->url);
        $opts = [
            // UPLOAD + CUSTOMREQUEST POST + INFILESIZE = streamed body with Content-Length, never chunked
            CURLOPT_UPLOAD => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_INFILESIZE => $request->length(),
            CURLOPT_READFUNCTION => static function ($ch, $fd, int $len) use ($request, &$callbackError): string {
                try {
                    return $request->read($len);
                } catch (\Throwable $e) {
                    $callbackError = $e;
                    return '';
                }
            },
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $writer->contentType(),
                'MIME-Version: 1.0',
                'Accept: multipart/related, application/soap+xml',
                'Expect:',
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseType): int {
                if (stripos($line, 'Content-Type:') === 0) {
                    $responseType = trim(substr($line, 13));
                }
                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function ($ch, string $data) use (&$sink, &$responseType, &$callbackError, $sinkFactory): int {
                try {
                    $sink ??= str_starts_with(strtolower($responseType), 'multipart/')
                        ? new MultipartStreamParser($responseType, $sinkFactory)
                        : new PlainBodyCollector();
                    $sink->write($data);
                    return strlen($data);
                } catch (\Throwable $e) {
                    $callbackError = $e;
                    return 0; // aborts the transfer
                }
            },
            CURLOPT_TIMEOUT => max($this->options->timeout, 1800),
        ];
        curl_setopt_array($ch, $opts + CurlAuthOptions::tls($this->options)
            + CurlAuthOptions::for($this->credentials, $this->options, $this->certFile));

        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($callbackError !== null) {
            throw new ServiceUnavailable(null, 'VoDZ stream error: ' . $callbackError->getMessage(), $operation, $callbackError);
        }
        // A SOAP 1.2 fault may arrive with any 4xx/5xx (manual: "HTTP 599 or similar"): read it before mapping the status.
        if ($errno === 0 && $status !== 401 && $status !== 403 && $sink instanceof PlainBodyCollector) {
            $this->throwIfFault($sink->rootXml(), $operation);
        }
        $httpError = HttpErrorMapper::map($status, $errno, $error, $operation);
        if ($httpError !== null) {
            throw $httpError;
        }
        if ($ok === false || $sink === null) {
            throw new ServiceUnavailable(null, 'Empty VoDZ response', $operation);
        }
        try {
            $sink->finish();
        } catch (\RuntimeException $e) {
            throw new ServiceUnavailable(null, $e->getMessage(), $operation, $e);
        }

        return [$this->loadXml($sink->rootXml(), $operation), $sink->binaryContentIds()];
    }

    private function throwIfFault(string $xml, string $operation): void
    {
        if ($xml === '' || !str_contains($xml, 'Fault')) {
            return;
        }
        $this->loadXml($xml, $operation);
    }

    private function loadXml(string $xml, string $operation): \DOMDocument
    {
        if (stripos($xml, '<!DOCTYPE') !== false) {
            throw new IsdsException(null, 'DOCTYPE in SOAP response', $operation);
        }
        $doc = new \DOMDocument();
        if ($xml === '' || !@$doc->loadXML($xml, LIBXML_NONET)) {
            throw new ServiceUnavailable(null, 'Unparseable VoDZ response', $operation);
        }
        $fault = $doc->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', 'Fault')->item(0);
        if ($fault !== null) {
            $reason = $doc->getElementsByTagNameNS('http://www.w3.org/2003/05/soap-envelope', 'Text')->item(0);
            throw new IsdsException(null, 'SOAP fault: ' . trim(($reason ?? $fault)->textContent), $operation);
        }
        return $doc;
    }
}
