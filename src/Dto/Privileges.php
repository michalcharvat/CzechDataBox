<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Dto;

use MichalCharvat\CzechDataBox\Enum\Privilege;

final class Privileges
{
    public function __construct(public readonly int $mask)
    {
    }

    public function has(Privilege $p): bool
    {
        return ($this->mask & $p->value) === $p->value;
    }

    /** A received-list call with this login delivers messages (Provozní řád §17(3)). */
    public function delivers(): bool
    {
        return $this->has(Privilege::ReadNonPersonal) || $this->has(Privilege::ReadAll);
    }

    /** @return list<Privilege> */
    public function toList(): array
    {
        return array_values(array_filter(Privilege::cases(), $this->has(...)));
    }
}
