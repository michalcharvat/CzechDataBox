<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Enum;

final class MessageType
{
    /**
     * Documented dmType values (WS manual 3.8.1, GetListOfSentMessages table).
     * A missing or empty dmType means V; the upstream list is open.
     */
    public const KNOWN = [
        'V' => 'veřejná zpráva (adresát nebo odesílatel je OVM)',
        'K' => 'PDZ smluvní',
        'I' => 'PDZ smluvní, iniciuje Odpovědní PDZ',
        'Y' => 'PDZ smluvní, iniciační, již využitá pro odeslání ODZ',
        'X' => 'PDZ smluvní, iniciační, nevyužitá a exspirovaná',
        'A' => 'PDZ dotovaná, iniciuje Odpovědní PDZ',
        'B' => 'PDZ dotovaná, iniciační, již využitá pro odeslání ODZ',
        'C' => 'PDZ dotovaná, iniciační, nevyužitá a exspirovaná',
        'O' => 'Odpovědní PDZ, zdarma na účet odesílatele iniciační PDZ',
        'G' => 'PDZ dotovaná jinou schránkou (donátor)',
        'E' => 'PDZ placená z předplaceného kreditu',
    ];
    private const POSTAL = ['K', 'I', 'Y', 'X', 'A', 'B', 'C', 'O', 'G', 'E'];

    public static function isKnown(string $dmType): bool
    {
        return isset(self::KNOWN[$dmType]);
    }

    public static function isPostal(string $dmType): bool
    {
        return in_array($dmType, self::POSTAL, true);
    }
}
