<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Input;

/**
 * dmEnvelope of CreateMessage / CreateMultipleMessage (tMessageEnvelopeSub, WSDL 3.10).
 * Fields are child elements; dmType is the only attribute. dbIDRecipient is required by
 * createMessage() and ignored by createMultipleMessage() (recipients are passed separately).
 */
final class MessageEnvelopeInput
{
    /**
     * @param int|null $dmPublishOwnID IdLevel bit mask of sender details to publish
     *                                 (0 userType … 64 robIdent, 127 = all); null = element not sent
     * @param string|null $dmType "I" initiating PDZ, "O" reply PDZ, "K"/"E" contract vs credit payment
     */
    public function __construct(
        public readonly string $dmAnnotation,
        public readonly ?string $dbIDRecipient = null,
        public readonly ?string $dmSenderOrgUnit = null,
        public readonly ?int $dmSenderOrgUnitNum = null,
        public readonly ?string $dmRecipientOrgUnit = null,
        public readonly ?int $dmRecipientOrgUnitNum = null,
        public readonly ?string $dmToHands = null,
        public readonly ?string $dmRecipientRefNumber = null,
        public readonly ?string $dmSenderRefNumber = null,
        public readonly ?string $dmRecipientIdent = null,
        public readonly ?string $dmSenderIdent = null,
        public readonly ?int $dmLegalTitleLaw = null,
        public readonly ?int $dmLegalTitleYear = null,
        public readonly ?string $dmLegalTitleSect = null,
        public readonly ?string $dmLegalTitlePar = null,
        public readonly ?string $dmLegalTitlePoint = null,
        public readonly ?bool $dmPersonalDelivery = null,
        public readonly ?bool $dmAllowSubstDelivery = null,
        public readonly ?bool $dmOVM = null,
        public readonly ?int $dmPublishOwnID = null,
        public readonly ?string $dmType = null,
    ) {
        if ($dmAnnotation === '' || mb_strlen($dmAnnotation) > 255) {
            throw new \InvalidArgumentException('dmAnnotation must be 1..255 characters');
        }
        if ($dbIDRecipient !== null && !preg_match('/^[a-z0-9]{7}$/', $dbIDRecipient)) {
            throw new \InvalidArgumentException('dbIDRecipient must be 7 lower-case letters or digits');
        }
        if ($dmPublishOwnID !== null && ($dmPublishOwnID < 0 || $dmPublishOwnID > 127)) {
            throw new \InvalidArgumentException('dmPublishOwnID IdLevel must be 0..127');
        }
    }

    /** @return array<string, mixed> SoapClient array for dmEnvelope; nulls dropped */
    public function toSoap(): array
    {
        $v = array_filter(get_object_vars($this), static fn($x) => $x !== null);
        if ($this->dmPublishOwnID !== null) {
            $v['dmPublishOwnID'] = ['_' => true, 'IdLevel' => $this->dmPublishOwnID];
        }
        return $v;
    }
}
