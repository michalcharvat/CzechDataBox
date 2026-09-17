<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\ArchivedDocument;
use MichalCharvat\CzechDataBox\Dto\SignedDocument;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/**
 * dm_arch.wsdl — endpoint ws2 …/DS/arch (SOAP 1.2 + MTOM). ArchiveISDSDocument adds a new archive
 * time stamp to a downloaded ZFO (WS manual 2.5.5). Errors: 2206 stamp still valid long enough, 2210 too many requests.
 */
final class Archive extends AbstractMtomService
{
    /**
     * @param string|resource $zfo signed message / delivery info bytes or a seekable stream
     * @param resource|null $sink receives the re-stamped document; null keeps it in ArchivedDocument::$document
     */
    public function archiveIsdsDocument($zfo, $sink = null): ArchivedDocument
    {
        $op = 'ArchiveISDSDocument';
        $in = is_string($zfo) ? self::memoryStream($zfo) : $zfo;
        $out = $sink ?? fopen('php://memory', 'w+b');
        if ($out === false) {
            throw new \RuntimeException('Cannot buffer archived document');
        }
        $body = '<p:ArchiveISDSDocument xmlns:p="' . self::NS . '"><p:dmMessage>{{cid0}}</p:dmMessage></p:ArchiveISDSDocument>';
        [$raw, $cids] = $this->checked($op, $body, [[$in, 'application/octet-stream']], self::singleSink($out));
        $this->completeBinary($op, $raw->dmResultDoc ?? null, $cids, $out);

        $document = null;
        if ($sink === null) {
            rewind($out);
            $bytes = (string)stream_get_contents($out);
            $document = $bytes === '' ? throw new IsdsException(null, 'Response lacks dmResultDoc', $op) : new SignedDocument($bytes);
        }
        return new ArchivedDocument($document, N::date($raw, 'nextStampTo'), $raw);
    }
}
