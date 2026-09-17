<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Endpoint;

use MichalCharvat\CzechDataBox\Credentials\AuthKind;
use MichalCharvat\CzechDataBox\Environment;

final class EndpointTable
{
    /**
     * [domainKey][AuthKind name][Service name] => URL.
     * WS manual 3.8.1 ch. 1.2.1.1 (baseURL per login kind: ws1 / ws1c/cert / ws1c/certds / ws1c/hspis),
     * 1.2.2.1 (ws2 / ws2c/.../DS/vodz), 1.2.3 + ArchiveISDSDocument (ws2c/cert/DS/arch "atd."),
     * test = datovka-test.gov.cz, legacy mojedatovaschranka.cz / czebox.cz "still usable" (docs/sources.md).
     * Missing entry = combination not offered by ISDS.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private const URLS = [
        'prod' => [
            'Password' => [
                'Operations' => 'https://ws1.datovka.gov.cz/DS/dz',
                'Info' => 'https://ws1.datovka.gov.cz/DS/dx',
                'Search' => 'https://ws1.datovka.gov.cz/DS/df',
                'Access' => 'https://ws1.datovka.gov.cz/DS/DsManage',
                'Manipulations' => 'https://ws1.datovka.gov.cz/DS/DsManage',
                'BigMessages' => 'https://ws2.datovka.gov.cz/DS/vodz',
                'Archive' => 'https://ws2.datovka.gov.cz/DS/arch',
            ],
            'SystemCertificate' => [
                'Operations' => 'https://ws1c.datovka.gov.cz/cert/DS/dz',
                'Info' => 'https://ws1c.datovka.gov.cz/cert/DS/dx',
                'Search' => 'https://ws1c.datovka.gov.cz/cert/DS/df',
                'Access' => 'https://ws1c.datovka.gov.cz/cert/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka.gov.cz/cert/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka.gov.cz/cert/DS/vodz',
                'Archive' => 'https://ws2c.datovka.gov.cz/cert/DS/arch',
            ],
            'CertificateAndPassword' => [
                'Operations' => 'https://ws1c.datovka.gov.cz/certds/DS/dz',
                'Info' => 'https://ws1c.datovka.gov.cz/certds/DS/dx',
                'Search' => 'https://ws1c.datovka.gov.cz/certds/DS/df',
                'Access' => 'https://ws1c.datovka.gov.cz/certds/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka.gov.cz/certds/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka.gov.cz/certds/DS/vodz',
                'Archive' => 'https://ws2c.datovka.gov.cz/certds/DS/arch',
            ],
            'HostedRecordsService' => [
                'Operations' => 'https://ws1c.datovka.gov.cz/hspis/DS/dz',
                'Info' => 'https://ws1c.datovka.gov.cz/hspis/DS/dx',
                'Search' => 'https://ws1c.datovka.gov.cz/hspis/DS/df',
                'Access' => 'https://ws1c.datovka.gov.cz/hspis/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka.gov.cz/hspis/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka.gov.cz/hspis/DS/vodz',
                'Archive' => 'https://ws2c.datovka.gov.cz/hspis/DS/arch',
            ],
        ],
        'test' => [
            'Password' => [
                'Operations' => 'https://ws1.datovka-test.gov.cz/DS/dz',
                'Info' => 'https://ws1.datovka-test.gov.cz/DS/dx',
                'Search' => 'https://ws1.datovka-test.gov.cz/DS/df',
                'Access' => 'https://ws1.datovka-test.gov.cz/DS/DsManage',
                'Manipulations' => 'https://ws1.datovka-test.gov.cz/DS/DsManage',
                'BigMessages' => 'https://ws2.datovka-test.gov.cz/DS/vodz',
                'Archive' => 'https://ws2.datovka-test.gov.cz/DS/arch',
            ],
            'SystemCertificate' => [
                'Operations' => 'https://ws1c.datovka-test.gov.cz/cert/DS/dz',
                'Info' => 'https://ws1c.datovka-test.gov.cz/cert/DS/dx',
                'Search' => 'https://ws1c.datovka-test.gov.cz/cert/DS/df',
                'Access' => 'https://ws1c.datovka-test.gov.cz/cert/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka-test.gov.cz/cert/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka-test.gov.cz/cert/DS/vodz',
                'Archive' => 'https://ws2c.datovka-test.gov.cz/cert/DS/arch',
            ],
            'CertificateAndPassword' => [
                'Operations' => 'https://ws1c.datovka-test.gov.cz/certds/DS/dz',
                'Info' => 'https://ws1c.datovka-test.gov.cz/certds/DS/dx',
                'Search' => 'https://ws1c.datovka-test.gov.cz/certds/DS/df',
                'Access' => 'https://ws1c.datovka-test.gov.cz/certds/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka-test.gov.cz/certds/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka-test.gov.cz/certds/DS/vodz',
                'Archive' => 'https://ws2c.datovka-test.gov.cz/certds/DS/arch',
            ],
            'HostedRecordsService' => [
                'Operations' => 'https://ws1c.datovka-test.gov.cz/hspis/DS/dz',
                'Info' => 'https://ws1c.datovka-test.gov.cz/hspis/DS/dx',
                'Search' => 'https://ws1c.datovka-test.gov.cz/hspis/DS/df',
                'Access' => 'https://ws1c.datovka-test.gov.cz/hspis/DS/DsManage',
                'Manipulations' => 'https://ws1c.datovka-test.gov.cz/hspis/DS/DsManage',
                'BigMessages' => 'https://ws2c.datovka-test.gov.cz/hspis/DS/vodz',
                'Archive' => 'https://ws2c.datovka-test.gov.cz/hspis/DS/arch',
            ],
        ],
        'prod-legacy' => [
            'Password' => [
                'Operations' => 'https://ws1.mojedatovaschranka.cz/DS/dz',
                'Info' => 'https://ws1.mojedatovaschranka.cz/DS/dx',
                'Search' => 'https://ws1.mojedatovaschranka.cz/DS/df',
                'Access' => 'https://ws1.mojedatovaschranka.cz/DS/DsManage',
                'Manipulations' => 'https://ws1.mojedatovaschranka.cz/DS/DsManage',
                'BigMessages' => 'https://ws2.mojedatovaschranka.cz/DS/vodz',
                'Archive' => 'https://ws2.mojedatovaschranka.cz/DS/arch',
            ],
            'SystemCertificate' => [
                'Operations' => 'https://ws1c.mojedatovaschranka.cz/cert/DS/dz',
                'Info' => 'https://ws1c.mojedatovaschranka.cz/cert/DS/dx',
                'Search' => 'https://ws1c.mojedatovaschranka.cz/cert/DS/df',
                'Access' => 'https://ws1c.mojedatovaschranka.cz/cert/DS/DsManage',
                'Manipulations' => 'https://ws1c.mojedatovaschranka.cz/cert/DS/DsManage',
                'BigMessages' => 'https://ws2c.mojedatovaschranka.cz/cert/DS/vodz',
                'Archive' => 'https://ws2c.mojedatovaschranka.cz/cert/DS/arch',
            ],
            'CertificateAndPassword' => [
                'Operations' => 'https://ws1c.mojedatovaschranka.cz/certds/DS/dz',
                'Info' => 'https://ws1c.mojedatovaschranka.cz/certds/DS/dx',
                'Search' => 'https://ws1c.mojedatovaschranka.cz/certds/DS/df',
                'Access' => 'https://ws1c.mojedatovaschranka.cz/certds/DS/DsManage',
                'Manipulations' => 'https://ws1c.mojedatovaschranka.cz/certds/DS/DsManage',
                'BigMessages' => 'https://ws2c.mojedatovaschranka.cz/certds/DS/vodz',
                'Archive' => 'https://ws2c.mojedatovaschranka.cz/certds/DS/arch',
            ],
            'HostedRecordsService' => [
                'Operations' => 'https://ws1c.mojedatovaschranka.cz/hspis/DS/dz',
                'Info' => 'https://ws1c.mojedatovaschranka.cz/hspis/DS/dx',
                'Search' => 'https://ws1c.mojedatovaschranka.cz/hspis/DS/df',
                'Access' => 'https://ws1c.mojedatovaschranka.cz/hspis/DS/DsManage',
                'Manipulations' => 'https://ws1c.mojedatovaschranka.cz/hspis/DS/DsManage',
                'BigMessages' => 'https://ws2c.mojedatovaschranka.cz/hspis/DS/vodz',
                'Archive' => 'https://ws2c.mojedatovaschranka.cz/hspis/DS/arch',
            ],
        ],
        'test-legacy' => [
            'Password' => [
                'Operations' => 'https://ws1.czebox.cz/DS/dz',
                'Info' => 'https://ws1.czebox.cz/DS/dx',
                'Search' => 'https://ws1.czebox.cz/DS/df',
                'Access' => 'https://ws1.czebox.cz/DS/DsManage',
                'Manipulations' => 'https://ws1.czebox.cz/DS/DsManage',
                'BigMessages' => 'https://ws2.czebox.cz/DS/vodz',
                'Archive' => 'https://ws2.czebox.cz/DS/arch',
            ],
            'SystemCertificate' => [
                'Operations' => 'https://ws1c.czebox.cz/cert/DS/dz',
                'Info' => 'https://ws1c.czebox.cz/cert/DS/dx',
                'Search' => 'https://ws1c.czebox.cz/cert/DS/df',
                'Access' => 'https://ws1c.czebox.cz/cert/DS/DsManage',
                'Manipulations' => 'https://ws1c.czebox.cz/cert/DS/DsManage',
                'BigMessages' => 'https://ws2c.czebox.cz/cert/DS/vodz',
                'Archive' => 'https://ws2c.czebox.cz/cert/DS/arch',
            ],
            'CertificateAndPassword' => [
                'Operations' => 'https://ws1c.czebox.cz/certds/DS/dz',
                'Info' => 'https://ws1c.czebox.cz/certds/DS/dx',
                'Search' => 'https://ws1c.czebox.cz/certds/DS/df',
                'Access' => 'https://ws1c.czebox.cz/certds/DS/DsManage',
                'Manipulations' => 'https://ws1c.czebox.cz/certds/DS/DsManage',
                'BigMessages' => 'https://ws2c.czebox.cz/certds/DS/vodz',
                'Archive' => 'https://ws2c.czebox.cz/certds/DS/arch',
            ],
            'HostedRecordsService' => [
                'Operations' => 'https://ws1c.czebox.cz/hspis/DS/dz',
                'Info' => 'https://ws1c.czebox.cz/hspis/DS/dx',
                'Search' => 'https://ws1c.czebox.cz/hspis/DS/df',
                'Access' => 'https://ws1c.czebox.cz/hspis/DS/DsManage',
                'Manipulations' => 'https://ws1c.czebox.cz/hspis/DS/DsManage',
                'BigMessages' => 'https://ws2c.czebox.cz/hspis/DS/vodz',
                'Archive' => 'https://ws2c.czebox.cz/hspis/DS/arch',
            ],
        ],
    ];

    public static function url(Environment $env, bool $legacyDomain, AuthKind $auth, Service $service): string
    {
        $domainKey = ($env === Environment::Production ? 'prod' : 'test') . ($legacyDomain ? '-legacy' : '');
        /** @var array<string, array<string, array<string, string>>> $urls */
        $urls = self::URLS;
        $url = $urls[$domainKey][$auth->name][$service->name] ?? null;
        if ($url === null) {
            throw new \LogicException(sprintf('ISDS does not offer %s for %s login (%s)', $service->name, $auth->name, $domainKey));
        }
        return $url;
    }
}
