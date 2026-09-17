<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Internal;

use MichalCharvat\CzechDataBox\Internal\Normalize;
use MichalCharvat\CzechDataBox\Internal\XmlToObject;
use PHPUnit\Framework\TestCase;

final class XmlToObjectTest extends TestCase
{
    private static function doc(string $body): \DOMDocument
    {
        $d = new \DOMDocument();
        $d->loadXML('<?xml version="1.0"?><s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"'
            . ' xmlns:p="http://isds.czechpoint.cz/v20" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><s:Header/><s:Body>'
            . $body . '</s:Body></s:Envelope>');
        return $d;
    }

    public function testConvertsLikeExtSoap(): void
    {
        $o = XmlToObject::responseBody(self::doc(
            '<p:R><p:dmStatus><p:dmStatusCode>0000</p:dmStatusCode><p:dmStatusMessage>ok</p:dmStatusMessage></p:dmStatus>'
            . '<p:dmHash algorithm="SHA-256">AAA=</p:dmHash><p:nilled xsi:nil="true"/>'
            . '<p:list><p:item a="1">x</p:item><p:item a="2">y</p:item></p:list><p:one><p:item>z</p:item></p:one>'
            . '<p:dmFile dmMimeType="application/pdf"><p:dmEncodedContent><xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:1"/></p:dmEncodedContent></p:dmFile>'
            . '</p:R>'
        ));

        self::assertSame('0000', $o->dmStatus->dmStatusCode);
        self::assertSame('AAA=', $o->dmHash->_);
        self::assertSame('SHA-256', $o->dmHash->algorithm);
        self::assertNull($o->nilled);
        self::assertCount(2, Normalize::list($o->list, 'item'));
        self::assertSame('2', Normalize::list($o->list, 'item')[1]->a);
        self::assertSame('z', $o->one->item);
        self::assertSame('1', XmlToObject::xopContentId($o->dmFile->dmEncodedContent));
        self::assertNull(XmlToObject::xopContentId('QUJD'));
    }

    public function testMissingBodyFails(): void
    {
        $d = new \DOMDocument();
        $d->loadXML('<x/>');
        $this->expectException(\UnexpectedValueException::class);
        XmlToObject::responseBody($d);
    }
}
