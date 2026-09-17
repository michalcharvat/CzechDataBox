<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Endpoint;

use MichalCharvat\CzechDataBox\Credentials\AuthKind;
use MichalCharvat\CzechDataBox\Endpoint\EndpointTable;
use MichalCharvat\CzechDataBox\Endpoint\Service;
use MichalCharvat\CzechDataBox\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EndpointTableTest extends TestCase
{
    /** @return iterable<string, array{Environment, bool, AuthKind, Service, string}> */
    public static function cells(): iterable
    {
        // environment × legacyDomain × auth × service, values from WS manual 3.8.1 ch. 1.2 (see EndpointTable).
        yield 'prod Password Operations' => [Environment::Production, false, AuthKind::Password, Service::Operations, 'https://ws1.datovka.gov.cz/DS/dz'];
        yield 'prod Password Info' => [Environment::Production, false, AuthKind::Password, Service::Info, 'https://ws1.datovka.gov.cz/DS/dx'];
        yield 'prod Password Search' => [Environment::Production, false, AuthKind::Password, Service::Search, 'https://ws1.datovka.gov.cz/DS/df'];
        yield 'prod Password Access' => [Environment::Production, false, AuthKind::Password, Service::Access, 'https://ws1.datovka.gov.cz/DS/DsManage'];
        yield 'prod Password Manipulations' => [Environment::Production, false, AuthKind::Password, Service::Manipulations, 'https://ws1.datovka.gov.cz/DS/DsManage'];
        yield 'prod Password BigMessages' => [Environment::Production, false, AuthKind::Password, Service::BigMessages, 'https://ws2.datovka.gov.cz/DS/vodz'];
        yield 'prod Password Archive' => [Environment::Production, false, AuthKind::Password, Service::Archive, 'https://ws2.datovka.gov.cz/DS/arch'];
        yield 'prod SystemCertificate Operations' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Operations, 'https://ws1c.datovka.gov.cz/cert/DS/dz'];
        yield 'prod SystemCertificate Info' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Info, 'https://ws1c.datovka.gov.cz/cert/DS/dx'];
        yield 'prod SystemCertificate Search' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Search, 'https://ws1c.datovka.gov.cz/cert/DS/df'];
        yield 'prod SystemCertificate Access' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Access, 'https://ws1c.datovka.gov.cz/cert/DS/DsManage'];
        yield 'prod SystemCertificate Manipulations' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Manipulations, 'https://ws1c.datovka.gov.cz/cert/DS/DsManage'];
        yield 'prod SystemCertificate BigMessages' => [Environment::Production, false, AuthKind::SystemCertificate, Service::BigMessages, 'https://ws2c.datovka.gov.cz/cert/DS/vodz'];
        yield 'prod SystemCertificate Archive' => [Environment::Production, false, AuthKind::SystemCertificate, Service::Archive, 'https://ws2c.datovka.gov.cz/cert/DS/arch'];
        yield 'prod CertificateAndPassword Operations' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Operations, 'https://ws1c.datovka.gov.cz/certds/DS/dz'];
        yield 'prod CertificateAndPassword Info' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Info, 'https://ws1c.datovka.gov.cz/certds/DS/dx'];
        yield 'prod CertificateAndPassword Search' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Search, 'https://ws1c.datovka.gov.cz/certds/DS/df'];
        yield 'prod CertificateAndPassword Access' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Access, 'https://ws1c.datovka.gov.cz/certds/DS/DsManage'];
        yield 'prod CertificateAndPassword Manipulations' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Manipulations, 'https://ws1c.datovka.gov.cz/certds/DS/DsManage'];
        yield 'prod CertificateAndPassword BigMessages' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::BigMessages, 'https://ws2c.datovka.gov.cz/certds/DS/vodz'];
        yield 'prod CertificateAndPassword Archive' => [Environment::Production, false, AuthKind::CertificateAndPassword, Service::Archive, 'https://ws2c.datovka.gov.cz/certds/DS/arch'];
        yield 'prod HostedRecordsService Operations' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Operations, 'https://ws1c.datovka.gov.cz/hspis/DS/dz'];
        yield 'prod HostedRecordsService Info' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Info, 'https://ws1c.datovka.gov.cz/hspis/DS/dx'];
        yield 'prod HostedRecordsService Search' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Search, 'https://ws1c.datovka.gov.cz/hspis/DS/df'];
        yield 'prod HostedRecordsService Access' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Access, 'https://ws1c.datovka.gov.cz/hspis/DS/DsManage'];
        yield 'prod HostedRecordsService Manipulations' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Manipulations, 'https://ws1c.datovka.gov.cz/hspis/DS/DsManage'];
        yield 'prod HostedRecordsService BigMessages' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::BigMessages, 'https://ws2c.datovka.gov.cz/hspis/DS/vodz'];
        yield 'prod HostedRecordsService Archive' => [Environment::Production, false, AuthKind::HostedRecordsService, Service::Archive, 'https://ws2c.datovka.gov.cz/hspis/DS/arch'];
        yield 'test Password Operations' => [Environment::Test, false, AuthKind::Password, Service::Operations, 'https://ws1.datovka-test.gov.cz/DS/dz'];
        yield 'test Password Info' => [Environment::Test, false, AuthKind::Password, Service::Info, 'https://ws1.datovka-test.gov.cz/DS/dx'];
        yield 'test Password Search' => [Environment::Test, false, AuthKind::Password, Service::Search, 'https://ws1.datovka-test.gov.cz/DS/df'];
        yield 'test Password Access' => [Environment::Test, false, AuthKind::Password, Service::Access, 'https://ws1.datovka-test.gov.cz/DS/DsManage'];
        yield 'test Password Manipulations' => [Environment::Test, false, AuthKind::Password, Service::Manipulations, 'https://ws1.datovka-test.gov.cz/DS/DsManage'];
        yield 'test Password BigMessages' => [Environment::Test, false, AuthKind::Password, Service::BigMessages, 'https://ws2.datovka-test.gov.cz/DS/vodz'];
        yield 'test Password Archive' => [Environment::Test, false, AuthKind::Password, Service::Archive, 'https://ws2.datovka-test.gov.cz/DS/arch'];
        yield 'test SystemCertificate Operations' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Operations, 'https://ws1c.datovka-test.gov.cz/cert/DS/dz'];
        yield 'test SystemCertificate Info' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Info, 'https://ws1c.datovka-test.gov.cz/cert/DS/dx'];
        yield 'test SystemCertificate Search' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Search, 'https://ws1c.datovka-test.gov.cz/cert/DS/df'];
        yield 'test SystemCertificate Access' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Access, 'https://ws1c.datovka-test.gov.cz/cert/DS/DsManage'];
        yield 'test SystemCertificate Manipulations' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Manipulations, 'https://ws1c.datovka-test.gov.cz/cert/DS/DsManage'];
        yield 'test SystemCertificate BigMessages' => [Environment::Test, false, AuthKind::SystemCertificate, Service::BigMessages, 'https://ws2c.datovka-test.gov.cz/cert/DS/vodz'];
        yield 'test SystemCertificate Archive' => [Environment::Test, false, AuthKind::SystemCertificate, Service::Archive, 'https://ws2c.datovka-test.gov.cz/cert/DS/arch'];
        yield 'test CertificateAndPassword Operations' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Operations, 'https://ws1c.datovka-test.gov.cz/certds/DS/dz'];
        yield 'test CertificateAndPassword Info' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Info, 'https://ws1c.datovka-test.gov.cz/certds/DS/dx'];
        yield 'test CertificateAndPassword Search' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Search, 'https://ws1c.datovka-test.gov.cz/certds/DS/df'];
        yield 'test CertificateAndPassword Access' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Access, 'https://ws1c.datovka-test.gov.cz/certds/DS/DsManage'];
        yield 'test CertificateAndPassword Manipulations' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Manipulations, 'https://ws1c.datovka-test.gov.cz/certds/DS/DsManage'];
        yield 'test CertificateAndPassword BigMessages' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::BigMessages, 'https://ws2c.datovka-test.gov.cz/certds/DS/vodz'];
        yield 'test CertificateAndPassword Archive' => [Environment::Test, false, AuthKind::CertificateAndPassword, Service::Archive, 'https://ws2c.datovka-test.gov.cz/certds/DS/arch'];
        yield 'test HostedRecordsService Operations' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Operations, 'https://ws1c.datovka-test.gov.cz/hspis/DS/dz'];
        yield 'test HostedRecordsService Info' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Info, 'https://ws1c.datovka-test.gov.cz/hspis/DS/dx'];
        yield 'test HostedRecordsService Search' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Search, 'https://ws1c.datovka-test.gov.cz/hspis/DS/df'];
        yield 'test HostedRecordsService Access' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Access, 'https://ws1c.datovka-test.gov.cz/hspis/DS/DsManage'];
        yield 'test HostedRecordsService Manipulations' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Manipulations, 'https://ws1c.datovka-test.gov.cz/hspis/DS/DsManage'];
        yield 'test HostedRecordsService BigMessages' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::BigMessages, 'https://ws2c.datovka-test.gov.cz/hspis/DS/vodz'];
        yield 'test HostedRecordsService Archive' => [Environment::Test, false, AuthKind::HostedRecordsService, Service::Archive, 'https://ws2c.datovka-test.gov.cz/hspis/DS/arch'];
        yield 'prod-legacy Password Operations' => [Environment::Production, true, AuthKind::Password, Service::Operations, 'https://ws1.mojedatovaschranka.cz/DS/dz'];
        yield 'prod-legacy Password Info' => [Environment::Production, true, AuthKind::Password, Service::Info, 'https://ws1.mojedatovaschranka.cz/DS/dx'];
        yield 'prod-legacy Password Search' => [Environment::Production, true, AuthKind::Password, Service::Search, 'https://ws1.mojedatovaschranka.cz/DS/df'];
        yield 'prod-legacy Password Access' => [Environment::Production, true, AuthKind::Password, Service::Access, 'https://ws1.mojedatovaschranka.cz/DS/DsManage'];
        yield 'prod-legacy Password Manipulations' => [Environment::Production, true, AuthKind::Password, Service::Manipulations, 'https://ws1.mojedatovaschranka.cz/DS/DsManage'];
        yield 'prod-legacy Password BigMessages' => [Environment::Production, true, AuthKind::Password, Service::BigMessages, 'https://ws2.mojedatovaschranka.cz/DS/vodz'];
        yield 'prod-legacy Password Archive' => [Environment::Production, true, AuthKind::Password, Service::Archive, 'https://ws2.mojedatovaschranka.cz/DS/arch'];
        yield 'prod-legacy SystemCertificate Operations' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Operations, 'https://ws1c.mojedatovaschranka.cz/cert/DS/dz'];
        yield 'prod-legacy SystemCertificate Info' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Info, 'https://ws1c.mojedatovaschranka.cz/cert/DS/dx'];
        yield 'prod-legacy SystemCertificate Search' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Search, 'https://ws1c.mojedatovaschranka.cz/cert/DS/df'];
        yield 'prod-legacy SystemCertificate Access' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Access, 'https://ws1c.mojedatovaschranka.cz/cert/DS/DsManage'];
        yield 'prod-legacy SystemCertificate Manipulations' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Manipulations, 'https://ws1c.mojedatovaschranka.cz/cert/DS/DsManage'];
        yield 'prod-legacy SystemCertificate BigMessages' => [Environment::Production, true, AuthKind::SystemCertificate, Service::BigMessages, 'https://ws2c.mojedatovaschranka.cz/cert/DS/vodz'];
        yield 'prod-legacy SystemCertificate Archive' => [Environment::Production, true, AuthKind::SystemCertificate, Service::Archive, 'https://ws2c.mojedatovaschranka.cz/cert/DS/arch'];
        yield 'prod-legacy CertificateAndPassword Operations' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Operations, 'https://ws1c.mojedatovaschranka.cz/certds/DS/dz'];
        yield 'prod-legacy CertificateAndPassword Info' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Info, 'https://ws1c.mojedatovaschranka.cz/certds/DS/dx'];
        yield 'prod-legacy CertificateAndPassword Search' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Search, 'https://ws1c.mojedatovaschranka.cz/certds/DS/df'];
        yield 'prod-legacy CertificateAndPassword Access' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Access, 'https://ws1c.mojedatovaschranka.cz/certds/DS/DsManage'];
        yield 'prod-legacy CertificateAndPassword Manipulations' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Manipulations, 'https://ws1c.mojedatovaschranka.cz/certds/DS/DsManage'];
        yield 'prod-legacy CertificateAndPassword BigMessages' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::BigMessages, 'https://ws2c.mojedatovaschranka.cz/certds/DS/vodz'];
        yield 'prod-legacy CertificateAndPassword Archive' => [Environment::Production, true, AuthKind::CertificateAndPassword, Service::Archive, 'https://ws2c.mojedatovaschranka.cz/certds/DS/arch'];
        yield 'prod-legacy HostedRecordsService Operations' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Operations, 'https://ws1c.mojedatovaschranka.cz/hspis/DS/dz'];
        yield 'prod-legacy HostedRecordsService Info' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Info, 'https://ws1c.mojedatovaschranka.cz/hspis/DS/dx'];
        yield 'prod-legacy HostedRecordsService Search' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Search, 'https://ws1c.mojedatovaschranka.cz/hspis/DS/df'];
        yield 'prod-legacy HostedRecordsService Access' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Access, 'https://ws1c.mojedatovaschranka.cz/hspis/DS/DsManage'];
        yield 'prod-legacy HostedRecordsService Manipulations' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Manipulations, 'https://ws1c.mojedatovaschranka.cz/hspis/DS/DsManage'];
        yield 'prod-legacy HostedRecordsService BigMessages' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::BigMessages, 'https://ws2c.mojedatovaschranka.cz/hspis/DS/vodz'];
        yield 'prod-legacy HostedRecordsService Archive' => [Environment::Production, true, AuthKind::HostedRecordsService, Service::Archive, 'https://ws2c.mojedatovaschranka.cz/hspis/DS/arch'];
        yield 'test-legacy Password Operations' => [Environment::Test, true, AuthKind::Password, Service::Operations, 'https://ws1.czebox.cz/DS/dz'];
        yield 'test-legacy Password Info' => [Environment::Test, true, AuthKind::Password, Service::Info, 'https://ws1.czebox.cz/DS/dx'];
        yield 'test-legacy Password Search' => [Environment::Test, true, AuthKind::Password, Service::Search, 'https://ws1.czebox.cz/DS/df'];
        yield 'test-legacy Password Access' => [Environment::Test, true, AuthKind::Password, Service::Access, 'https://ws1.czebox.cz/DS/DsManage'];
        yield 'test-legacy Password Manipulations' => [Environment::Test, true, AuthKind::Password, Service::Manipulations, 'https://ws1.czebox.cz/DS/DsManage'];
        yield 'test-legacy Password BigMessages' => [Environment::Test, true, AuthKind::Password, Service::BigMessages, 'https://ws2.czebox.cz/DS/vodz'];
        yield 'test-legacy Password Archive' => [Environment::Test, true, AuthKind::Password, Service::Archive, 'https://ws2.czebox.cz/DS/arch'];
        yield 'test-legacy SystemCertificate Operations' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Operations, 'https://ws1c.czebox.cz/cert/DS/dz'];
        yield 'test-legacy SystemCertificate Info' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Info, 'https://ws1c.czebox.cz/cert/DS/dx'];
        yield 'test-legacy SystemCertificate Search' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Search, 'https://ws1c.czebox.cz/cert/DS/df'];
        yield 'test-legacy SystemCertificate Access' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Access, 'https://ws1c.czebox.cz/cert/DS/DsManage'];
        yield 'test-legacy SystemCertificate Manipulations' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Manipulations, 'https://ws1c.czebox.cz/cert/DS/DsManage'];
        yield 'test-legacy SystemCertificate BigMessages' => [Environment::Test, true, AuthKind::SystemCertificate, Service::BigMessages, 'https://ws2c.czebox.cz/cert/DS/vodz'];
        yield 'test-legacy SystemCertificate Archive' => [Environment::Test, true, AuthKind::SystemCertificate, Service::Archive, 'https://ws2c.czebox.cz/cert/DS/arch'];
        yield 'test-legacy CertificateAndPassword Operations' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Operations, 'https://ws1c.czebox.cz/certds/DS/dz'];
        yield 'test-legacy CertificateAndPassword Info' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Info, 'https://ws1c.czebox.cz/certds/DS/dx'];
        yield 'test-legacy CertificateAndPassword Search' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Search, 'https://ws1c.czebox.cz/certds/DS/df'];
        yield 'test-legacy CertificateAndPassword Access' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Access, 'https://ws1c.czebox.cz/certds/DS/DsManage'];
        yield 'test-legacy CertificateAndPassword Manipulations' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Manipulations, 'https://ws1c.czebox.cz/certds/DS/DsManage'];
        yield 'test-legacy CertificateAndPassword BigMessages' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::BigMessages, 'https://ws2c.czebox.cz/certds/DS/vodz'];
        yield 'test-legacy CertificateAndPassword Archive' => [Environment::Test, true, AuthKind::CertificateAndPassword, Service::Archive, 'https://ws2c.czebox.cz/certds/DS/arch'];
        yield 'test-legacy HostedRecordsService Operations' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Operations, 'https://ws1c.czebox.cz/hspis/DS/dz'];
        yield 'test-legacy HostedRecordsService Info' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Info, 'https://ws1c.czebox.cz/hspis/DS/dx'];
        yield 'test-legacy HostedRecordsService Search' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Search, 'https://ws1c.czebox.cz/hspis/DS/df'];
        yield 'test-legacy HostedRecordsService Access' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Access, 'https://ws1c.czebox.cz/hspis/DS/DsManage'];
        yield 'test-legacy HostedRecordsService Manipulations' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Manipulations, 'https://ws1c.czebox.cz/hspis/DS/DsManage'];
        yield 'test-legacy HostedRecordsService BigMessages' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::BigMessages, 'https://ws2c.czebox.cz/hspis/DS/vodz'];
        yield 'test-legacy HostedRecordsService Archive' => [Environment::Test, true, AuthKind::HostedRecordsService, Service::Archive, 'https://ws2c.czebox.cz/hspis/DS/arch'];
    }

    #[DataProvider('cells')]
    public function testCell(Environment $env, bool $legacy, AuthKind $auth, Service $svc, string $url): void
    {
        if ($url === '') {
            $this->expectException(\LogicException::class);
        }
        self::assertSame($url, EndpointTable::url($env, $legacy, $auth, $svc));
    }

    public function testEveryCellIsPinned(): void
    {
        self::assertCount(2 * 2 * count(AuthKind::cases()) * count(Service::cases()), iterator_to_array(self::cells()));
    }
}
