<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\BigMessage;
use MichalCharvat\CzechDataBox\Dto\CreatedMessage;
use MichalCharvat\CzechDataBox\Dto\Message;
use MichalCharvat\CzechDataBox\Dto\UploadedAttachment;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Input\ExtFileInput;
use MichalCharvat\CzechDataBox\Input\FileInput;
use MichalCharvat\CzechDataBox\Input\MessageEnvelopeInput;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;
use MichalCharvat\CzechDataBox\Internal\XmlToObject;

/** dm_VoDZ.wsdl — endpoint ws2 …/DS/vodz (SOAP 1.2 + MTOM). Messages over 20 MB, up to 50 MB attachments each. */
final class BigMessages extends AbstractMtomService
{
    /** tBigMessEnvelope child order (dm_VoDZ / dmBaseTypes.xsd). */
    private const ENVELOPE_ORDER = [
        'dmSenderOrgUnit', 'dmSenderOrgUnitNum', 'dbIDRecipient', 'dmRecipientOrgUnit', 'dmRecipientOrgUnitNum',
        'dmToHands', 'dmAnnotation', 'dmRecipientRefNumber', 'dmSenderRefNumber', 'dmRecipientIdent', 'dmSenderIdent',
        'dmLegalTitleLaw', 'dmLegalTitleYear', 'dmLegalTitleSect', 'dmLegalTitlePar', 'dmLegalTitlePoint',
        'dmPersonalDelivery', 'dmAllowSubstDelivery', 'dmOVM',
    ];

    /** @param resource $content seekable stream with known size */
    public function uploadAttachment($content, string $mimeType, string $description): UploadedAttachment
    {
        $body = '<p:UploadAttachment xmlns:p="' . self::NS . '"><p:dmFile dmMimeType="' . self::attr($mimeType)
            . '" dmFileDescr="' . self::attr($description) . '"><p:dmEncodedContent>{{cid0}}</p:dmEncodedContent></p:dmFile></p:UploadAttachment>';
        [$raw] = $this->checked('UploadAttachment', $body, [[$content, $mimeType]], self::noSink());
        return UploadedAttachment::fromRaw($raw);
    }

    /**
     * @param list<ExtFileInput> $extFiles uploaded attachments, the first one is the main document
     * @param list<FileInput> $files small files sent inline (base64) after the uploaded ones
     */
    public function createBigMessage(MessageEnvelopeInput $envelope, array $extFiles, array $files = []): CreatedMessage
    {
        if ($envelope->dbIDRecipient === null) {
            throw new \InvalidArgumentException('createBigMessage() needs dbIDRecipient');
        }
        if ($extFiles === [] || $extFiles[0]->metaType !== 'main') {
            throw new \InvalidArgumentException('The first uploaded file must have dmFileMetaType "main"');
        }
        $body = '<p:CreateBigMessage xmlns:p="' . self::NS . '">' . self::envelopeXml($envelope) . '<p:dmFiles>';
        foreach ($extFiles as $f) {
            $a = $f->attachment;
            $body .= '<p:dmExtFile dmFileMetaType="' . self::attr($f->metaType) . '" dmAttID="' . self::attr($a->dmAttID)
                . '" dmAttHash1="' . self::attr($a->dmAttHash1) . '" dmAttHash1Alg="' . self::attr($a->dmAttHash1Alg)
                . '" dmAttHash2="' . self::attr($a->dmAttHash2) . '" dmAttHash2Alg="' . self::attr($a->dmAttHash2Alg) . '"'
                . self::optionalAttr('dmFileGuid', $f->fileGuid) . self::optionalAttr('dmUpFileGuid', $f->upFileGuid) . '/>';
        }
        foreach ($files as $f) {
            $body .= '<p:dmFile dmFileMetaType="' . self::attr($f->metaType) . '" dmFileDescr="' . self::attr($f->description)
                . '" dmMimeType="' . self::attr($f->mimeType) . '"' . self::optionalAttr('dmFileGuid', $f->fileGuid)
                . self::optionalAttr('dmUpFileGuid', $f->upFileGuid) . '><p:dmEncodedContent>' . base64_encode($f->content)
                . '</p:dmEncodedContent></p:dmFile>';
        }
        $body .= '</p:dmFiles></p:CreateBigMessage>';
        [$raw] = $this->checked('CreateBigMessage', $body, [], self::noSink());
        return new CreatedMessage(N::string($raw, 'dmID') ?? throw new IsdsException(null, 'Response lacks dmID', 'CreateBigMessage'), $raw);
    }

    /** @param resource $sink receives the signed ZFO of a received VoDZ */
    public function signedBigMessageDownload(string $dmID, $sink): void
    {
        $this->downloadInto('SignedBigMessageDownload', 'dmSignature', $dmID, $sink);
    }

    /** @param resource $sink receives the signed ZFO of a sent VoDZ */
    public function signedSentBigMessageDownload(string $dmID, $sink): void
    {
        $this->downloadInto('SignedSentBigMessageDownload', 'dmSignature', $dmID, $sink);
    }

    /**
     * Received VoDZ as XML with its files. Each file sent as an MTOM part is streamed into
     * $fileSinkFactory($contentId); BigMessage::$contentIds maps files to those IDs.
     *
     * @param callable(string): resource $fileSinkFactory
     */
    public function bigMessageDownload(string $dmID, callable $fileSinkFactory): BigMessage
    {
        $op = 'BigMessageDownload';
        [$raw, $cids] = $this->checked($op, self::idBody($op, $dmID), [], $fileSinkFactory);
        $returned = N::child($raw, 'dmReturnedMessage') ?? throw new IsdsException(null, 'Response lacks dmReturnedMessage', $op);
        $contentIds = [];
        foreach (N::list(N::child(N::child($returned, 'dmDm'), 'dmFiles'), 'dmFile') as $file) {
            $content = $file->dmEncodedContent ?? null;
            $cid = XmlToObject::xopContentId($content);
            if ($cid !== null) {
                $this->assertReceived($op, $cid, $cids);
                $file->dmEncodedContent = null;
            } elseif (is_string($content)) {
                $file->dmEncodedContent = base64_decode($content, true) ?: '';
            }
            $contentIds[] = $cid;
        }
        return new BigMessage(Message::fromRaw($returned), $contentIds);
    }

    /**
     * One attachment of a VoDZ by its index (0 = first). Returns the dmFile metadata
     * (dmFileMetaType, dmMimeType, dmFileDescr); the content goes into $sink.
     *
     * @param resource $sink
     */
    public function downloadAttachment(string $dmID, int $attNum, $sink): \stdClass
    {
        $op = 'DownloadAttachment';
        $body = '<p:DownloadAttachment xmlns:p="' . self::NS . '"><p:dmID>' . self::text($dmID) . '</p:dmID><p:attNum>'
            . $attNum . '</p:attNum></p:DownloadAttachment>';
        [$raw, $cids] = $this->checked($op, $body, [], self::singleSink($sink));
        $file = N::child($raw, 'dmFile') ?? throw new IsdsException(null, 'Response lacks dmFile', $op);
        $this->completeBinary($op, $file->dmEncodedContent ?? null, $cids, $sink);
        unset($file->dmEncodedContent);
        return $file;
    }

    /** @param resource $zfo ISDS verifies the VoDZ ZFO is authentic and unmodified */
    public function authenticateBigMessage($zfo): bool
    {
        $body = '<p:AuthenticateBigMessage xmlns:p="' . self::NS . '"><p:dmMessage>{{cid0}}</p:dmMessage></p:AuthenticateBigMessage>';
        [$raw] = $this->checked('AuthenticateBigMessage', $body, [[$zfo, 'application/octet-stream']], self::noSink());
        return (bool)N::bool($raw, 'dmAuthResult');
    }

    /** @param resource $sink */
    private function downloadInto(string $op, string $element, string $dmID, $sink): void
    {
        [$raw, $cids] = $this->checked($op, self::idBody($op, $dmID), [], self::singleSink($sink));
        $this->completeBinary($op, $raw->{$element} ?? null, $cids, $sink);
    }

    private static function idBody(string $op, string $dmID): string
    {
        return '<p:' . $op . ' xmlns:p="' . self::NS . '"><p:dmID>' . self::text($dmID) . '</p:dmID></p:' . $op . '>';
    }

    private static function optionalAttr(string $name, ?string $value): string
    {
        return $value === null ? '' : ' ' . $name . '="' . self::attr($value) . '"';
    }

    private static function envelopeXml(MessageEnvelopeInput $e): string
    {
        $xml = '<p:dmEnvelope' . self::optionalAttr('dmType', $e->dmType) . '>';
        $values = get_object_vars($e);
        foreach (self::ENVELOPE_ORDER as $name) {
            $v = $values[$name] ?? null;
            if ($v !== null) {
                $xml .= '<p:' . $name . '>' . (is_bool($v) ? ($v ? 'true' : 'false') : self::text((string)$v)) . '</p:' . $name . '>';
            }
        }
        if ($e->dmPublishOwnID !== null) {
            $xml .= '<p:dmPublishOwnID IdLevel="' . $e->dmPublishOwnID . '">true</p:dmPublishOwnID>';
        }
        return $xml . '</p:dmEnvelope>';
    }
}
