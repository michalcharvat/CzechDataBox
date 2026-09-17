<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Zfo;

use MichalCharvat\CzechDataBox\Exception\InvalidZfo;

/** @internal Minimal definite-length DER reader for CMS SignedData navigation. */
final class Der
{
    /** @return array{tag: int, class: int, constructed: bool, headerLen: int, len: int, offset: int} */
    public static function read(string $bytes, int $offset): array
    {
        $n = strlen($bytes);
        if ($offset + 2 > $n) {
            throw new InvalidZfo(null, 'DER truncated', 'Zfo');
        }
        $b = ord($bytes[$offset]);
        $class = $b >> 6;
        $constructed = (bool)($b & 0x20);
        $tag = $b & 0x1f;
        $p = $offset + 1;
        if ($tag === 0x1f) {
            throw new InvalidZfo(null, 'DER high tag numbers not supported', 'Zfo');
        }
        $l = ord($bytes[$p++]);
        if ($l === 0x80) {
            throw new InvalidZfo(null, 'Indefinite DER length', 'Zfo');
        }
        if ($l & 0x80) {
            $k = $l & 0x7f;
            if ($k > 4 || $p + $k > $n) {
                throw new InvalidZfo(null, 'DER length too large', 'Zfo');
            }
            $l = 0;
            for ($i = 0; $i < $k; $i++) {
                $l = ($l << 8) | ord($bytes[$p++]);
            }
        }
        if ($p + $l > $n) {
            throw new InvalidZfo(null, 'DER element exceeds input', 'Zfo');
        }
        return ['tag' => $tag, 'class' => $class, 'constructed' => $constructed, 'headerLen' => $p - $offset, 'len' => $l, 'offset' => $p];
    }

    /**
     * @param array{tag: int, class: int, constructed: bool, headerLen: int, len: int, offset: int} $parent
     * @return list<array{tag: int, class: int, constructed: bool, headerLen: int, len: int, offset: int}>
     */
    public static function children(string $bytes, array $parent): array
    {
        $out = [];
        $p = $parent['offset'];
        $end = $parent['offset'] + $parent['len'];
        while ($p < $end) {
            $el = self::read($bytes, $p);
            $out[] = $el;
            $p = $el['offset'] + $el['len'];
        }
        return $out;
    }

    /** @param array{tag: int, class: int, constructed: bool, headerLen: int, len: int, offset: int} $el */
    public static function content(string $bytes, array $el): string
    {
        return substr($bytes, $el['offset'], $el['len']);
    }
}
