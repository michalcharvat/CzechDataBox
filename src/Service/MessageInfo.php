<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Service;

use MichalCharvat\CzechDataBox\Dto\DeliveryInfo;
use MichalCharvat\CzechDataBox\Dto\MessageAuthor;
use MichalCharvat\CzechDataBox\Dto\MessageEnvelope;
use MichalCharvat\CzechDataBox\Dto\MessageRecord;
use MichalCharvat\CzechDataBox\Dto\SignedDocument;
use MichalCharvat\CzechDataBox\Dto\StateChange;
use MichalCharvat\CzechDataBox\Input\ListFilter;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

/** dm_info.wsdl — endpoint …/DS/dx. */
final class MessageInfo extends AbstractService
{
    /** @return list<MessageRecord> Delivers the listed messages when the login has READ_NON_PERSONAL/READ_ALL. */
    public function getListOfReceivedMessages(ListFilter $f): array
    {
        return $this->records('GetListOfReceivedMessages', $f, 'dmRecipientOrgUnitNum');
    }

    /** @return list<MessageRecord> */
    public function getListOfSentMessages(ListFilter $f): array
    {
        return $this->records('GetListOfSentMessages', $f, 'dmSenderOrgUnitNum');
    }

    public function messageEnvelopeDownload(string $dmID): MessageEnvelope
    {
        return $this->envelope('MessageEnvelopeDownload', $dmID);
    }

    public function sentMessageEnvelopeDownload(string $dmID): MessageEnvelope
    {
        return $this->envelope('SentMessageEnvelopeDownload', $dmID);
    }

    /** Sets state 7 (read). No legal effect. */
    public function markMessageAsDownloaded(string $dmID): void
    {
        $this->call('MarkMessageAsDownloaded', ['dmID' => $dmID]);
    }

    public function getDeliveryInfo(string $dmID): DeliveryInfo
    {
        $raw = $this->call('GetDeliveryInfo', ['dmID' => $dmID]);
        return DeliveryInfo::fromRaw(N::child($raw, 'dmDelivery') ?? throw $this->missing('GetDeliveryInfo', 'dmDelivery'));
    }

    public function getSignedDeliveryInfo(string $dmID): SignedDocument
    {
        return $this->signed('GetSignedDeliveryInfo', ['dmID' => $dmID]);
    }

    /** @return list<StateChange> Sent messages only, at most the last 15 days. */
    public function getMessageStateChanges(\DateTimeInterface $from, ?\DateTimeInterface $to): array
    {
        $params = ['dmFromTime' => N::toIsds($from)];
        if ($to !== null) {
            $params['dmToTime'] = N::toIsds($to);
        }
        $raw = $this->call('GetMessageStateChanges', $params);
        return array_map(StateChange::fromRaw(...), N::list(N::child($raw, 'dmRecords'), 'dmRecord'));
    }

    public function getMessageAuthor(string $dmID): MessageAuthor
    {
        return MessageAuthor::fromRaw($this->call('GetMessageAuthor', ['dmID' => $dmID]));
    }

    public function getMessageAuthor2(string $dmID): MessageAuthor
    {
        return MessageAuthor::fromRaw($this->call('GetMessageAuthor2', ['dmID' => $dmID]));
    }

    /**
     * Returns the raw response (dmHash of the message as stored in ISDS).
     *
     * @deprecated Deprecated upstream; use MessageOperations::authenticateMessage().
     */
    public function verifyMessage(string $dmID): \stdClass
    {
        return $this->call('VerifyMessage', ['dmID' => $dmID]);
    }

    /** Erases a message in state 10 (data vault). */
    public function eraseMessage(string $dmID, bool $incoming): void
    {
        $this->call('EraseMessage', ['dmID' => $dmID, 'dmIncoming' => $incoming]);
    }

    /**
     * Asynchronous: returns the raw response carrying asyncID; collect the result with pickUpAsyncResponse().
     *
     * @param array<string, mixed> $params as in tGetListOfErasedInput (dmFromDate+dmToDate or dmYear[+dmMonth],
     *                                    dmMessageType SENT|RECEIVED, dmOutFormat XML|CSV)
     */
    public function getListOfErasedMessages(array $params): \stdClass
    {
        return $this->call('GetListOfErasedMessages', $params);
    }

    /** Raw response: asyncReqType, asyncResponse (zip bytes). NotAvailableYet (2352) while not ready. */
    public function pickUpAsyncResponse(string $asyncID, string $asyncReqType): \stdClass
    {
        return $this->call('PickUpAsyncResponse', ['asyncID' => $asyncID, 'asyncReqType' => $asyncReqType]);
    }

    /** @param int $action 1 = register, 0 = unregister (system-certificate logins only) */
    public function registerForNotifications(int $action): void
    {
        $this->call('RegisterForNotifications', ['action' => $action]);
    }

    /** Raw response: ntfRecords/ntfRecord (ntfType, dmID, dmDeliveryTime), ntfListContinues. */
    public function getListForNotifications(\DateTimeInterface $from, string $scope): \stdClass
    {
        return $this->call('GetListForNotifications', ['ntfFromTime' => N::toIsds($from), 'ntfScope' => $scope]);
    }

    /** Reports a received message as spam (WSDL 3.08). */
    public function suspMessageReport(
        string $dmID,
        bool $allowComplete,
        ?string $repName = null,
        ?string $repMail = null,
        ?string $repTel = null,
        ?string $note = null,
    ): void {
        $params = ['dmID' => $dmID];
        foreach (['repName' => $repName, 'repMail' => $repMail, 'repTel' => $repTel] as $k => $v) {
            if ($v !== null) {
                $params[$k] = $v;
            }
        }
        $params['allowComplete'] = $allowComplete;
        if ($note !== null) {
            $params['note'] = $note;
        }
        $this->call('SuspMessageReport', $params);
    }

    private function envelope(string $op, string $dmID): MessageEnvelope
    {
        $raw = $this->call($op, ['dmID' => $dmID]);
        return MessageEnvelope::fromRaw(N::child($raw, 'dmReturnedMessageEnvelope') ?? throw $this->missing($op, 'dmReturnedMessageEnvelope'));
    }

    /** @return list<MessageRecord> */
    private function records(string $op, ListFilter $f, string $orgUnitParam): array
    {
        $params = [
            'dmFromTime' => N::toIsds($f->from),
            'dmToTime' => N::toIsds($f->to),
            'dmStatusFilter' => (string)$f->statusFilter,
            'dmLimit' => (string)$f->limit,
        ];
        if ($f->offset !== null) {
            $params['dmOffset'] = (string)$f->offset;
        }
        if ($f->orgUnitNum !== null) {
            $params[$orgUnitParam] = (string)$f->orgUnitNum;
        }
        $raw = $this->call($op, $params);
        return array_map(MessageRecord::fromRaw(...), N::list(N::child($raw, 'dmRecords'), 'dmRecord'));
    }
}
