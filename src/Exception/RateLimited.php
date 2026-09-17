<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Exception;

/** 3008 parallel requests, 3009 throttled mode, 3013 VoDZ overload. */
final class RateLimited extends IsdsException
{
}
