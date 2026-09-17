<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Exception\ServiceUnavailable;
use MichalCharvat\CzechDataBox\Internal\StatusGuard;
use MichalCharvat\CzechDataBox\Internal\XmlToObject;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;

/** Services on the ws2 SOAP 1.2 + MTOM endpoints (vodz, arch). */
abstract class AbstractMtomService
{
    protected const NS = 'http://isds.czechpoint.cz/v20';

    public function __construct(protected readonly VodzTransport $transport)
    {
    }

    /**
     * @param list<array{resource, string}> $binaries
     * @param callable(string): resource $sinkFactory
     * @return array{\stdClass, list<string>} status-checked response body and the received part Content-IDs
     */
    protected function checked(string $op, #[\SensitiveParameter] string $body, array $binaries, callable $sinkFactory): array
    {
        [$doc, $cids] = $this->transport->call($op, $body, $binaries, $sinkFactory);
        return [StatusGuard::check(XmlToObject::responseBody($doc), $op), $cids];
    }

    /**
     * One expected binary result: an MTOM part (already streamed into $sink by the transport)
     * or inline base64 (decoded into $sink here).
     *
     * @param resource $sink
     * @param list<string> $receivedCids
     */
    protected function completeBinary(string $op, mixed $value, array $receivedCids, $sink): void
    {
        $cid = XmlToObject::xopContentId($value);
        if ($cid !== null) {
            $this->assertReceived($op, $cid, $receivedCids);
            return;
        }
        if (!is_string($value) || $value === '') {
            throw new ServiceUnavailable(null, 'Response carries no binary content', $op);
        }
        $bytes = base64_decode($value, true);
        if ($bytes === false || fwrite($sink, $bytes) !== strlen($bytes)) {
            throw new ServiceUnavailable(null, 'Cannot decode inline binary content', $op);
        }
    }

    /** @param list<string> $receivedCids */
    protected function assertReceived(string $op, string $cid, array $receivedCids): void
    {
        if (!in_array($cid, $receivedCids, true)) {
            throw new ServiceUnavailable(null, 'MTOM part ' . $cid . ' referenced but not received', $op);
        }
    }

    /**
     * Sink factory accepting exactly one binary part.
     *
     * @param resource $sink
     * @return \Closure(string): resource
     */
    protected static function singleSink($sink): \Closure
    {
        $used = false;
        return static function (string $cid) use ($sink, &$used) {
            if ($used) {
                throw new \UnexpectedValueException('Unexpected second binary part ' . $cid);
            }
            $used = true;
            return $sink;
        };
    }

    /** @return \Closure(string): resource */
    protected static function noSink(): \Closure
    {
        return static function (string $cid) {
            throw new \UnexpectedValueException('Unexpected binary part ' . $cid);
        };
    }

    /** @return resource */
    protected static function memoryStream(string $bytes)
    {
        $s = fopen('php://memory', 'w+b');
        if ($s === false || fwrite($s, $bytes) !== strlen($bytes) || !rewind($s)) {
            throw new \RuntimeException('Cannot buffer document');
        }
        return $s;
    }

    protected static function attr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected static function text(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1, 'UTF-8');
    }
}
