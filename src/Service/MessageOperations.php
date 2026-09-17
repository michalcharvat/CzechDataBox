<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\CreatedMessage;
use MichalCharvat\CzechDataBox\Dto\Message;
use MichalCharvat\CzechDataBox\Dto\SignedDocument;
use MichalCharvat\CzechDataBox\Input\FileInput;
use MichalCharvat\CzechDataBox\Input\MessageEnvelopeInput;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** dm_operations.wsdl — endpoint …/DS/dz. */
final class MessageOperations extends AbstractService
{
    /** @param list<FileInput> $files */
    public function createMessage(MessageEnvelopeInput $envelope, array $files): CreatedMessage
    {
        if ($envelope->dbIDRecipient === null) {
            throw new \InvalidArgumentException('createMessage() needs dbIDRecipient');
        }
        $raw = $this->call('CreateMessage', [
            'dmEnvelope' => $envelope->toSoap(),
            'dmFiles' => ['dmFile' => self::files($files)],
        ]);
        return new CreatedMessage(N::string($raw, 'dmID') ?? throw $this->missing('CreateMessage', 'dmID'), $raw);
    }

    /**
     * Status 0004 (some recipients failed) does not throw: read dmMultipleStatus/dmSingleStatus
     * (dmID + dmStatus per recipient, in request order) from the returned raw response.
     *
     * @param list<string> $recipientBoxIds
     * @param list<FileInput> $files
     */
    public function createMultipleMessage(MessageEnvelopeInput $envelope, array $recipientBoxIds, array $files): \stdClass
    {
        $recipients = array_map(static fn(string $id) => ['dbIDRecipient' => $id], $recipientBoxIds);
        return $this->call('CreateMultipleMessage', [
            'dmRecipients' => ['dmRecipient' => $recipients],
            'dmEnvelope' => $envelope->toSoap(),
            'dmFiles' => ['dmFile' => self::files($files)],
        ]);
    }

    public function messageDownload(string $dmID): Message
    {
        $raw = $this->call('MessageDownload', ['dmID' => $dmID]);
        return Message::fromRaw(N::child($raw, 'dmReturnedMessage') ?? throw $this->missing('MessageDownload', 'dmReturnedMessage'));
    }

    public function signedMessageDownload(string $dmID): SignedDocument
    {
        return $this->signed('SignedMessageDownload', ['dmID' => $dmID]);
    }

    public function signedSentMessageDownload(string $dmID): SignedDocument
    {
        return $this->signed('SignedSentMessageDownload', ['dmID' => $dmID]);
    }

    /** ISDS verifies that the ZFO is an authentic, unmodified ISDS document. */
    public function authenticateMessage(string $zfoBytes): bool
    {
        $raw = $this->call('AuthenticateMessage', ['dmMessage' => $zfoBytes]);
        return (bool)N::bool($raw, 'dmAuthResult');
    }

    public function resignIsdsDocument(string $document): SignedDocument
    {
        $raw = $this->call('Re-signISDSDocument', ['dmDoc' => $document]);
        return new SignedDocument(N::binary($raw, 'dmResultDoc') ?? throw $this->missing('Re-signISDSDocument', 'dmResultDoc'));
    }

    /** Keep-alive / login check. tDummyOutput carries dmStatus since WSDL 3.06. */
    public function dummyOperation(): void
    {
        $this->call('DummyOperation');
    }

    /**
     * @param list<FileInput> $files
     * @return list<array<string, mixed>>
     */
    private static function files(array $files): array
    {
        if ($files === [] || $files[0]->metaType !== 'main') {
            throw new \InvalidArgumentException('The first file must have dmFileMetaType "main"');
        }
        return array_map(static fn(FileInput $f) => $f->toSoap(), $files);
    }
}
