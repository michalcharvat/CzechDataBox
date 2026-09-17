<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Exception\IsdsException;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class SoapTransportTest extends TestCase
{
    public function testReplaysFixtureThroughRealSoapClient(): void
    {
        $t = FakeTransport::for(Service::Operations)->reply('DummyOperation', 'ok');
        $raw = $t->call('DummyOperation', []);

        self::assertInstanceOf(\stdClass::class, $raw);
        self::assertSame(['DummyOperation'], $t->calledOperations());
    }

    public function testSoapFaultIsWrapped(): void
    {
        $t = FakeTransport::for(Service::Operations)->replyRaw('DummyOperation', FakeTransport::soapFault('Server', 'kaput'));
        try {
            $t->call('DummyOperation', []);
            self::fail('expected exception');
        } catch (IsdsException $e) {
            self::assertSame('DummyOperation', $e->operation);
            self::assertStringContainsString('kaput', $e->getMessage());
            self::assertInstanceOf(\SoapFault::class, $e->getPrevious());
        }
    }
}
