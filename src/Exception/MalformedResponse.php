<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Exception;

/** ISDS answered with status 0000 but the payload is not what the WSDL promises (missing or unparseable element). */
final class MalformedResponse extends IsdsException
{
}
