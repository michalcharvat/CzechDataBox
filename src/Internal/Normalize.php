<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Internal;

/**
 * Reads values out of the stdClass trees ext-soap produces (no classmap).
 * Every repeated XSD element must go through list(): ext-soap returns null,
 * a single object or an array depending on the count, whatever the
 * SOAP_SINGLE_ELEMENT_ARRAYS feature promises.
 *
 * @internal
 */
final class Normalize
{
    /** Offset-less ISDS timestamps are local Czech time. */
    private const ISDS_ZONE = 'Europe/Prague';

    /**
     * @return list<\stdClass>
     */
    public static function list(mixed $raw, ?string $child = null): array
    {
        if ($child !== null) {
            $raw = is_object($raw) && property_exists($raw, $child) ? $raw->{$child} : null;
        }
        if ($raw === null) {
            return [];
        }
        if (is_array($raw)) {
            return array_values(array_filter($raw, static fn(mixed $v): bool => $v instanceof \stdClass));
        }
        if ($raw instanceof \stdClass) {
            return get_object_vars($raw) === [] ? [] : [$raw];
        }
        return [];
    }

    public static function child(?object $o, string $prop): ?\stdClass
    {
        $v = $o !== null && property_exists($o, $prop) ? $o->{$prop} : null;
        return $v instanceof \stdClass ? $v : null;
    }

    public static function string(?object $o, string $prop): ?string
    {
        $v = $o !== null && property_exists($o, $prop) ? $o->{$prop} : null;
        if ($v === null || is_object($v) || is_array($v)) {
            return null;
        }
        $s = (string)$v;
        return $s === '' ? null : $s;
    }

    public static function int(?object $o, string $prop): ?int
    {
        $s = self::string($o, $prop);
        return $s === null ? null : (int)$s;
    }

    public static function bool(?object $o, string $prop): ?bool
    {
        $v = $o !== null && property_exists($o, $prop) ? $o->{$prop} : null;
        if ($v === null || is_object($v)) {
            return null;
        }
        if (is_bool($v)) {
            return $v;
        }
        return in_array(strtolower((string)$v), ['1', 'true'], true);
    }

    /** Binary content: ext-soap already base64-decodes xsd:base64Binary. */
    public static function binary(?object $o, string $prop): ?string
    {
        $v = $o !== null && property_exists($o, $prop) ? $o->{$prop} : null;
        return is_string($v) && $v !== '' ? $v : null;
    }

    public static function dateTime(?object $o, string $prop): ?\DateTimeImmutable
    {
        $s = self::string($o, $prop);
        return $s === null ? null : self::parseDateTime($s);
    }

    /** xs:date: calendar date at 00:00 UTC, no zone conversion. */
    public static function date(?object $o, string $prop): ?\DateTimeImmutable
    {
        $s = self::string($o, $prop);
        if ($s === null) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($s, 0, 10), new \DateTimeZone('UTC'));
        if ($d === false) {
            throw new \UnexpectedValueException('Invalid ISDS date: ' . $s);
        }
        return $d;
    }

    public static function parseDateTime(string $s): \DateTimeImmutable
    {
        $hasZone = (bool)preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $s);
        try {
            $dt = new \DateTimeImmutable($s, $hasZone ? null : new \DateTimeZone(self::ISDS_ZONE));
        } catch (\Exception $e) {
            throw new \UnexpectedValueException('Invalid ISDS date-time: ' . $s, 0, $e);
        }
        return $dt->setTimezone(new \DateTimeZone('UTC'));
    }

    /** Formats a UTC-or-any DateTime for an ISDS request. */
    public static function toIsds(\DateTimeInterface $dt): string
    {
        return \DateTimeImmutable::createFromInterface($dt)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');
    }
}
