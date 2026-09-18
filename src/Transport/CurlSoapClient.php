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
    private ?CertificateFile $certFile = null;

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

    public function __doRequest(#[\SensitiveParameter] string $request, string $location, string $action, int $version, bool $oneWay = false, ?string $uriParserClass = null): ?string
    {
        $ch = curl_init($this->location);
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=utf-8', 'SOAPAction: "' . $action . '"'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->options->timeout,
        ];
        curl_setopt_array($ch, $opts + CurlAuthOptions::tls($this->options)
            + CurlAuthOptions::for($this->credentials, $this->options, $this->certFile));
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        $this->transportError = HttpErrorMapper::map($status, $errno, $error, $this->currentOperation);
        if ($this->transportError !== null || !is_string($body)) {
            // Return a harmless fault so ext-soap stops; SoapTransport rethrows transportError.
            return '<?xml version="1.0"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">'
                . '<SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>Client</faultcode><faultstring>transport</faultstring>'
                . '</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        }
        return $body;
    }
}
