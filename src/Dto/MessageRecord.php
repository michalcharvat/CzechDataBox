<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Enum\MessageStatus;
use MichalCharvat\CzechDataBox\Internal\Normalize as N;

final class MessageRecord
{
    public function __construct(
        public readonly string $dmID,
        public readonly ?string $dbIDSender,
        public readonly ?string $dmSender,
        public readonly ?string $dmSenderAddress,
        public readonly ?int $dmSenderType,
        public readonly ?string $dmRecipient,
        public readonly ?string $dmRecipientAddress,
        public readonly ?bool $dmAmbiguousRecipient,
        public readonly ?string $dmSenderOrgUnit,
        public readonly ?int $dmSenderOrgUnitNum,
        public readonly ?string $dbIDRecipient,
        public readonly ?string $dmRecipientOrgUnit,
        public readonly ?int $dmRecipientOrgUnitNum,
        public readonly ?string $dmToHands,
        public readonly ?string $dmAnnotation,
        public readonly ?string $dmRecipientRefNumber,
        public readonly ?string $dmSenderRefNumber,
        public readonly ?string $dmRecipientIdent,
        public readonly ?string $dmSenderIdent,
        public readonly ?int $dmLegalTitleLaw,
        public readonly ?int $dmLegalTitleYear,
        public readonly ?string $dmLegalTitleSect,
        public readonly ?string $dmLegalTitlePar,
        public readonly ?string $dmLegalTitlePoint,
        public readonly ?bool $dmPersonalDelivery,
        public readonly ?bool $dmAllowSubstDelivery,
        public readonly ?string $dmType,
        public readonly ?bool $dmVODZ,
        public readonly ?int $attsNum,
        public readonly ?int $dmMessageStatus,
        public readonly ?int $dmAttachmentSize,
        public readonly ?\DateTimeImmutable $dmDeliveryTime,
        public readonly ?\DateTimeImmutable $dmAcceptanceTime,
        public readonly ?int $dmOrdinal,
        /** 1 = suspicious message (spam), WSDL 3.08 */
        public readonly ?int $specMessFlag,
        public readonly \stdClass $raw,
    ) {
    }

    public static function fromRaw(\stdClass $r): self
    {
        return new self(
            (string)N::string($r, 'dmID'),
            N::string($r, 'dbIDSender'), N::string($r, 'dmSender'), N::string($r, 'dmSenderAddress'),
            N::int($r, 'dmSenderType'), N::string($r, 'dmRecipient'), N::string($r, 'dmRecipientAddress'),
            N::bool($r, 'dmAmbiguousRecipient'), N::string($r, 'dmSenderOrgUnit'), N::int($r, 'dmSenderOrgUnitNum'),
            N::string($r, 'dbIDRecipient'), N::string($r, 'dmRecipientOrgUnit'), N::int($r, 'dmRecipientOrgUnitNum'),
            N::string($r, 'dmToHands'), N::string($r, 'dmAnnotation'), N::string($r, 'dmRecipientRefNumber'),
            N::string($r, 'dmSenderRefNumber'), N::string($r, 'dmRecipientIdent'), N::string($r, 'dmSenderIdent'),
            N::int($r, 'dmLegalTitleLaw'), N::int($r, 'dmLegalTitleYear'), N::string($r, 'dmLegalTitleSect'),
            N::string($r, 'dmLegalTitlePar'), N::string($r, 'dmLegalTitlePoint'), N::bool($r, 'dmPersonalDelivery'),
            N::bool($r, 'dmAllowSubstDelivery'), N::string($r, 'dmType'), N::bool($r, 'dmVODZ'), N::int($r, 'attsNum'),
            N::int($r, 'dmMessageStatus'), N::int($r, 'dmAttachmentSize'),
            N::dateTime($r, 'dmDeliveryTime'), N::dateTime($r, 'dmAcceptanceTime'),
            N::int($r, 'dmOrdinal'), N::int($r, 'specMessFlag'),
            $r,
        );
    }

    public function isSuspicious(): bool
    {
        return $this->specMessFlag === 1;
    }

    public function status(): ?MessageStatus
    {
        return $this->dmMessageStatus === null ? null : MessageStatus::tryFrom($this->dmMessageStatus);
    }
}
