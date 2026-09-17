<?php

// Router for `php -S`: a fake ws2 endpoint for VodzTransportHttpTest.
declare(strict_types=1);

$body = file_get_contents('php://input');
$info = [
    'contentLength' => $_SERVER['CONTENT_LENGTH'] ?? null,
    'bodyLength' => strlen($body),
    'transferEncoding' => $_SERVER['HTTP_TRANSFER_ENCODING'] ?? null,
    'accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
    'contentType' => $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? null),
    'auth' => $_SERVER['PHP_AUTH_USER'] ?? null,
    'rootAction' => preg_match('/Content-Type: application\/xop\+xml[^\r\n]*action="([^"]*)"/', $body, $m) ? $m[1] : null,
];

if (str_ends_with($_SERVER['REQUEST_URI'], '/fault')) {
    http_response_code(599);
    header('Content-Type: application/soap+xml; charset=UTF-8');
    echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body><SOAP-ENV:Fault>'
        . '<SOAP-ENV:Code><SOAP-ENV:Value>SOAP-ENV:Sender</SOAP-ENV:Value></SOAP-ENV:Code>'
        . '<SOAP-ENV:Reason><SOAP-ENV:Text xml:lang="en-US">Outer Content-Type has type parameter=application/soap+xml, should be application/xop+xml. Use SOAP 1.2.</SOAP-ENV:Text></SOAP-ENV:Reason>'
        . '</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
    return true;
}

// ISDS-shaped answers for the service-level test (BigMessagesHttpTest).
$op = $info['rootAction'];
if ($op === 'UploadAttachment' || $op === 'SignedBigMessageDownload') {
    preg_match('/boundary="([^"]+)"/', (string)$info['contentType'], $b);
    $uploaded = '';
    foreach (explode("\r\n--" . ($b[1] ?? 'x'), "\r\n" . $body) as $part) {
        if (stripos($part, 'Content-Transfer-Encoding: binary') !== false) {
            $uploaded = substr($part, strpos($part, "\r\n\r\n") + 4);
            break;
        }
    }
    if ($op === 'UploadAttachment') {
        header('Content-Type: application/soap+xml; charset=UTF-8');   // no MTOM needed
        echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body>'
            . '<q:UploadAttachmentResponse xmlns:q="http://isds.czechpoint.cz/v20"><q:dmAttID>54520</q:dmAttID>'
            . '<q:dmAttHash1 AttHashAlg="SHA-256">' . hash('sha256', $uploaded) . '</q:dmAttHash1>'
            . '<q:dmAttHash2 AttHashAlg="SHA3-256">' . hash('sha3-256', $uploaded) . '</q:dmAttHash2>'
            . '<q:dmStatus><q:dmStatusCode>0000</q:dmStatusCode><q:dmStatusMessage>Provedeno úspěšně.</q:dmStatusMessage></q:dmStatus>'
            . '</q:UploadAttachmentResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>';
        return true;
    }
    // SignedBigMessageDownload: MTOM answer with two parts, root last-but-one cid ordering as ISDS sends it
    $zfo = str_repeat('ZFO', 100_000);
    $boundary = '==isds-signed==';
    header('Content-Type: multipart/related; start="<rootpart>"; type="application/xop+xml"; boundary="' . $boundary . '"; start-info="application/soap+xml"');
    echo "--$boundary\r\nContent-Id: <rootpart>\r\nContent-Type: application/xop+xml;charset=utf-8;type=\"application/soap+xml\"\r\n\r\n"
        . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body>'
        . '<q:SignedBigMessageDownloadResponse xmlns:q="http://isds.czechpoint.cz/v20"><q:dmSignature>'
        . '<xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:sig%40isds"/></q:dmSignature>'
        . '<q:dmStatus><q:dmStatusCode>0000</q:dmStatusCode><q:dmStatusMessage>Provedeno úspěšně.</q:dmStatusMessage></q:dmStatus>'
        . "</q:SignedBigMessageDownloadResponse></SOAP-ENV:Body></SOAP-ENV:Envelope>\r\n"
        . "--$boundary\r\nContent-Id: <sig@isds>\r\nContent-Type: application/octet-stream\r\n\r\n" . $zfo . "\r\n--$boundary--\r\n";
    return true;
}

// Echo the first binary part of the request back as an MTOM part.
preg_match('/boundary="([^"]+)"/', (string)$info['contentType'], $b);
$parts = explode("\r\n--" . $b[1], "\r\n" . $body);
$binary = '';
foreach ($parts as $part) {
    if (stripos($part, 'Content-Transfer-Encoding: binary') !== false) {
        $binary = substr($part, strpos($part, "\r\n\r\n") + 4);
        break;
    }
}
$boundary = '==1927659895719436937==';
header('Content-Type: multipart/related; start="<rootpart>"; type="application/xop+xml"; boundary="' . $boundary . '"; start-info="application/soap+xml"');
echo "--$boundary\r\nContent-Id: <rootpart>\r\nContent-Type: application/xop+xml;charset=utf-8;type=\"application/soap+xml\"\r\n\r\n"
    . '<?xml version="1.0" encoding="utf-8"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope"><SOAP-ENV:Body>'
    . '<q:EchoResponse xmlns:q="http://isds.czechpoint.cz/v20"><q:info>' . htmlspecialchars(json_encode($info)) . '</q:info>'
    . '<q:data><xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:1"/></q:data></q:EchoResponse>'
    . "</SOAP-ENV:Body></SOAP-ENV:Envelope>\r\n--$boundary\r\nContent-Id: <1>\r\nContent-Type: application/octet-stream\r\n\r\n"
    . $binary . "\r\n--$boundary\r\n";
return true;
