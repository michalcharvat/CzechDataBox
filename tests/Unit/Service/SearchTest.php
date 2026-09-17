<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Input\OwnerSearch;
use MichalCharvat\CzechDataBox\Internal\Normalize;
use MichalCharvat\CzechDataBox\Service\Search;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class SearchTest extends TestCase
{
    public function testCheckDataBoxReturnsState(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('CheckDataBox', 'active');
        self::assertSame(1, (new Search($t))->checkDataBox('abc1234')->dbState);
    }

    public function testFindDataBoxZeroOneMany(): void
    {
        foreach (['zero' => 0, 'one' => 1, 'many' => 2] as $case => $n) {
            $t = FakeTransport::for(Service::Search)->reply('FindDataBox2', $case);
            self::assertCount($n, (new Search($t))->findDataBox2(new OwnerSearch(ic: '12345678')));
        }
    }

    public function testIsdsSearch3(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('ISDSSearch3', 'ok');
        $result = (new Search($t))->isdsSearch3('ACME', page: 0, pageSize: 10);
        // one dbResult arrives as an object, not a list — raw responses need Normalize::list()
        self::assertSame('ACME s.r.o.', Normalize::list($result->dbResults, 'dbResult')[0]->dbName);
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:searchText>ACME</ns1:searchText>', $xml);
        self::assertStringContainsString('<ns1:pageSize>10</ns1:pageSize>', $xml);
    }

    public function testCreditInfo(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('DataBoxCreditInfo', 'ok');
        $c = (new Search($t))->dataBoxCreditInfo('abc1234', new \DateTimeImmutable('2026-09-01'));
        self::assertSame(12500, $c->currentCredit);
        self::assertCount(1, $c->ciRecords);
        self::assertStringContainsString('<ns1:ciFromDate>2026-09-01</ns1:ciFromDate>', $t->lastRequestXml());
    }

    public function testGetConstantsAlwaysSendsTheRequiredConstDate(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('GetConstants', 'ok')->reply('GetConstants', 'ok');
        $svc = new Search($t);
        $svc->getConstants();
        self::assertStringContainsString('<ns1:constDate xsi:nil="true"/>', $t->lastRequestXml(), 'constDate is required (nillable)');
        $svc->getConstants(new \DateTimeImmutable('2026-09-17'));
        self::assertStringContainsString('<ns1:constDate>2026-09-17</ns1:constDate>', $t->lastRequestXml());
    }

    public function testFindDataBox2MapsOwnersAndSendsTemplate(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('FindDataBox2', 'many');
        $owners = (new Search($t))->findDataBox2(new OwnerSearch(dbType: 'PO', firmName: 'ACME'));
        self::assertSame('ACME Trade a.s.', $owners[1]->displayName());
        self::assertTrue($owners[0]->type()?->value === 'PO');
        $xml = $t->lastRequestXml();
        self::assertStringContainsString('<ns1:firmName>ACME</ns1:firmName>', $xml);
        self::assertStringContainsString('<ns1:dbType>PO</ns1:dbType>', $xml);
    }

    public function testDataBoxAddressKeepsStatusViaPatchedXsd(): void
    {
        $t = FakeTransport::for(Service::Search)->reply('GetDataBoxAddress', 'ok');
        $a = (new Search($t))->getDataBoxAddress('abc1234');
        self::assertSame('Bazovského 1117/7~Řepy~16300 Praha 6', $a->adFullAddress2);
    }

    public function testActivityStatusAndPdzSendInfo(): void
    {
        $t = FakeTransport::for(Service::Search)
            ->reply('GetDataBoxActivityStatus', 'ok')
            ->reply('PDZSendInfo', 'ok');
        $svc = new Search($t);
        $s = $svc->getDataBoxActivityStatus('abc1231', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-09-17'));
        self::assertCount(1, $s->periods);
        self::assertSame(1, $s->periods[0]['state']);
        self::assertTrue($svc->pdzSendInfo('abc1231', 'Normal'));
    }
}
