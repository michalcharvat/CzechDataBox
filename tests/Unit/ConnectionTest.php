<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit;

use MichalCharvat\CzechDataBox\Connection;
use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Environment;
use MichalCharvat\CzechDataBox\Service\MessageInfo;
use MichalCharvat\CzechDataBox\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function testServicesAreLazyAndReused(): void
    {
        $built = [];
        $conn = new Connection(Environment::Test, new PasswordCredentials('u', 'p'), transportFactory:
            function (Service $s, string $url) use (&$built) {
                $built[] = [$s, $url];
                return FakeTransport::for($s);
            });

        self::assertSame([], $built);
        $info = $conn->messageInfo();
        self::assertInstanceOf(MessageInfo::class, $info);
        self::assertSame($info, $conn->messageInfo());
        self::assertCount(1, $built);
        self::assertSame(Service::Info, $built[0][0]);
        self::assertStringContainsString('/DS/dx', $built[0][1]);
    }

    public function testDebugInfoHidesSecrets(): void
    {
        $conn = new Connection(Environment::Test, new PasswordCredentials('user', 'secret'));
        self::assertStringNotContainsString('secret', print_r($conn, true));
    }
}
