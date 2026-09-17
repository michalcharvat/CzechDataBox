<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Transport;

use MichalCharvat\CzechDataBox\Credentials\Credentials;
use MichalCharvat\CzechDataBox\Exception\IsdsException;

/**
 * SoapClient that performs HTTP with cURL: HTTP status is inspected before ext-soap
 * tries to parse the body (a 401 HTML page otherwise surfaces as a misleading
 * "DTD are not supported by SOAP" fault).
 *
 * @internal
 */
class CurlSoapClient extends \SoapClient
{
    private ?IsdsException $transportError = null;
    private string $currentOperation = '';
    private ?string $certFile = null;

    public function __construct(
        string $wsdl,
        private readonly string $location,
        private readonly Credentials $credentials,
        private readonly TransportOptions $options,
    ) {
        parent::__construct($wsdl, [
            'soap_version' => SOAP_1_1,
            'location' => $location,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'trace' => false,
        ]);
    }

    public function beginOperation(string $operation): void
    {
        $this->currentOperation = $operation;
        $this->transportError = null;
    }

    public function takeTransportError(): ?IsdsException
    {
        $e = $this->transportError;
        $this->transportError = null;
        return $e;
    }

    public function __doRequest(#[\SensitiveParameter] string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
    {
        $ch = curl_init($this->location);
        $headers = ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: "' . $action . '"'];
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->options->connectTimeout,
            CURLOPT_TIMEOUT => $this->options->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CAINFO => $this->options->caFile(),
            CURLOPT_USERAGENT => $this->options->userAgent,
        ];
        if ($basic = $this->credentials->basicAuth()) {
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $opts[CURLOPT_USERPWD] = $basic[0] . ':' . $basic[1];
        }
        if ($cert = $this->credentials->certificate()) {
            $opts += $this->certificateOptions($cert->pem, $cert->passphrase);
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        $this->transportError = HttpErrorMapper::map($status, $errno, $error, $this->currentOperation);
        if ($this->transportError !== null || !is_string($body)) {
            // Return a harmless fault so ext-soap stops; SoapTransport rethrows transportError.
            return '<?xml version="1.0"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
                . '<SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>Client</faultcode><faultstring>transport</faultstring>'
                . '</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        }
        return $body;
    }

    /** @return array<int, mixed> */
    private function certificateOptions(string $pem, ?string $passphrase): array
    {
        $o = [CURLOPT_SSLCERTTYPE => 'PEM', CURLOPT_SSLKEYTYPE => 'PEM'];
        if (defined('CURLOPT_SSLCERT_BLOB') && defined('CURLOPT_SSLKEY_BLOB')) {
            $o[CURLOPT_SSLCERT_BLOB] = $pem;
            $o[CURLOPT_SSLKEY_BLOB] = $pem;
        } else {
            $o[CURLOPT_SSLCERT] = $this->certFile();
        }
        if ($passphrase !== null) {
            $o[CURLOPT_KEYPASSWD] = $passphrase;
        }
        return $o;
    }

    private function certFile(): string
    {
        if ($this->certFile === null) {
            $cert = $this->credentials->certificate();
            $path = tempnam($this->options->tempDir(), 'isds-cert-');
            if ($path === false || $cert === null) {
                throw new \RuntimeException('Cannot create certificate temp file');
            }
            chmod($path, 0600);
            file_put_contents($path, $cert->pem);
            $this->certFile = $path;
        }
        return $this->certFile;
    }

    public function __destruct()
    {
        if ($this->certFile !== null && is_file($this->certFile)) {
            unlink($this->certFile);
        }
    }
}
