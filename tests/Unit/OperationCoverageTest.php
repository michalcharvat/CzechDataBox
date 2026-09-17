<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit;

use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Service as Svc;
use PHPUnit\Framework\TestCase;

final class OperationCoverageTest extends TestCase
{
    private const SERVICE_CLASSES = [
        'Operations' => Svc\MessageOperations::class,
        'Info' => Svc\MessageInfo::class,
        'Search' => Svc\Search::class,
        'Access' => Svc\Access::class,
        'Manipulations' => Svc\Manipulations::class,
        'BigMessages' => Svc\BigMessages::class,
        'Archive' => Svc\Archive::class,
        'ChangePassword' => Svc\PasswordChange::class,
    ];

    public function testEveryWsdlOperationHasAServiceMethod(): void
    {
        $missing = [];
        $total = 0;
        foreach (Service::cases() as $service) {
            $wsdl = dirname(__DIR__, 2) . '/resources/wsdl/' . $service->wsdl();
            $version = $service === Service::BigMessages || $service === Service::Archive ? SOAP_1_2 : SOAP_1_1;
            $functions = (new \SoapClient($wsdl, ['soap_version' => $version]))->__getFunctions() ?? [];
            $class = self::SERVICE_CLASSES[$service->name];
            foreach ($functions as $signature) {
                self::assertSame(1, preg_match('/^\S+ ([A-Za-z0-9_-]+)\(/', $signature, $m), $signature);
                $total++;
                $method = lcfirst(str_replace(['-', '_'], '', $m[1]));
                if (!method_exists($class, $method)) {
                    $missing[] = $service->name . ': ' . $m[1] . ' → ' . $class . '::' . $method;
                }
            }
        }
        self::assertGreaterThan(60, $total, 'WSDL scan found suspiciously few operations');
        self::assertSame([], $missing);
    }
}
