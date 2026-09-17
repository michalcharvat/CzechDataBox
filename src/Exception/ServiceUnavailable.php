<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Exception;

/** Network error, timeout, HTTP 5xx, maintenance window. */
final class ServiceUnavailable extends IsdsException
{
}
