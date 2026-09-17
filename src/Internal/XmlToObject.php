<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Internal;

/**
 * Turns the first child of a SOAP 1.2 Body into the stdClass tree ext-soap would produce without a
 * classmap: child elements → properties (repeated → array), attributes → properties, simple content
 * with attributes → "_", xsi:nil → null. Used for the hand-parsed MTOM responses so Normalize and the
 * DTOs work unchanged. Unlike ext-soap it does not know XSD types: base64 stays encoded and an
 * xop:Include stays an object (see xopContentId()).
 *
 * @internal
 */
final class XmlToObject
{
    private const SOAP12 = 'http://www.w3.org/2003/05/soap-envelope';
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';
    private const XOP = 'http://www.w3.org/2004/08/xop/include';

    public static function responseBody(\DOMDocument $doc): \stdClass
    {
        $body = $doc->getElementsByTagNameNS(self::SOAP12, 'Body')->item(0);
        $first = $body === null ? null : self::firstElement($body);
        if ($first === null) {
            throw new \UnexpectedValueException('SOAP response without a Body element');
        }
        $o = self::convert($first);
        return $o instanceof \stdClass ? $o : new \stdClass();
    }

    /** Content-ID when $value is an element holding only an xop:Include, else null. */
    public static function xopContentId(mixed $value): ?string
    {
        if (!$value instanceof \stdClass || !isset($value->{'xop:Include'}) || !$value->{'xop:Include'} instanceof \stdClass) {
            return null;
        }
        $href = $value->{'xop:Include'}->href ?? null;
        return is_string($href) && str_starts_with($href, 'cid:') ? rawurldecode(substr($href, 4)) : null;
    }

    private static function convert(\DOMElement $el): \stdClass|string|null
    {
        if ($el->getAttributeNS(self::XSI, 'nil') === 'true') {
            return null;
        }
        $o = new \stdClass();
        $hasAttributes = false;
        foreach ($el->attributes ?? [] as $attr) {
            if ($attr->namespaceURI === self::XSI) {
                continue;
            }
            $o->{$attr->localName} = $attr->value;
            $hasAttributes = true;
        }
        $children = [];
        foreach ($el->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child;
            }
        }
        if ($children === []) {
            if (!$hasAttributes) {
                return $el->textContent;
            }
            if (trim($el->textContent) !== '') {
                $o->{'_'} = $el->textContent;
            }
            return $o;
        }
        foreach ($children as $child) {
            $name = ($child->namespaceURI === self::XOP ? 'xop:' : '') . (string)$child->localName;
            $value = self::convert($child);
            if (!property_exists($o, $name)) {
                $o->{$name} = $value;
            } elseif (is_array($o->{$name})) {
                $o->{$name}[] = $value;
            } else {
                $o->{$name} = [$o->{$name}, $value];
            }
        }
        return $o;
    }

    private static function firstElement(\DOMNode $node): ?\DOMElement
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return $child;
            }
        }
        return null;
    }
}
