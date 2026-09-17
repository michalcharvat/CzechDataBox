<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Credentials;

enum AuthKind
{
    case Password;
    case SystemCertificate;
    case CertificateAndPassword;
    case HostedRecordsService;
}
