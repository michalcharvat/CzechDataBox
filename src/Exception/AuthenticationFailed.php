<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Exception;

/** HTTP 401/403 before any SOAP parsing. */
final class AuthenticationFailed extends IsdsException
{
}
