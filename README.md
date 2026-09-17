# czech-data-box

PHP client for the Czech data box information system (ISDS) web services, **WSDL 3.10**
(Provozní řád ISDS 26. 6. 2026, WS manuals 3.8.1).

Version 2 is a rewrite: typed DTOs, a typed exception hierarchy, every WSDL operation, a streaming
MTOM transport for large messages (VoDZ) and a hardened ZFO reader. No runtime dependencies beyond
PHP extensions. Upgrading from 1.x: see [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Installation

```bash
composer require michalcharvat/czech-data-box:^2.0
```

Until the package is on Packagist, add the repository:

```json
{
  "repositories": [
    {"type": "vcs", "url": "https://github.com/michalcharvat/CzechDataBox.git"}
  ]
}
```

## Requirements

PHP ≥ 8.1 with `soap`, `openssl`, `curl`, `xml` and `dom`. ISDS requires TLS 1.2+.
The bundled `resources/ca/isds-ca-bundle.pem` is used for peer verification
(DigiCert Global Root G2 — the current ISDS chain — plus PostSignum Root QCA).

## Quick start

```php
use MichalCharvat\CzechDataBox\{Connection, Environment};
use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Input\{FileInput, ListFilter, MessageEnvelopeInput};

$isds = new Connection(Environment::Production, new PasswordCredentials('login', 'password'));

// who am I
$owner = $isds->access()->getOwnerInfoFromLogin2();
echo $owner->dbID, ' ', $owner->displayName(), PHP_EOL;

// received messages of the last 7 days — WARNING: this call DELIVERS them (see below)
$records = $isds->messageInfo()->getListOfReceivedMessages(
    new ListFilter(new DateTimeImmutable('-7 days'), new DateTimeImmutable(), limit: 100),
);

// download one with its attachments
$message = $isds->messageOperations()->messageDownload($records[0]->dmID);
foreach ($message->files as $file) {
    file_put_contents('/tmp/' . $file->dmFileDescr, $file->content);
}

// send
$created = $isds->messageOperations()->createMessage(
    new MessageEnvelopeInput(dmAnnotation: 'Faktura 2026/15', dbIDRecipient: 'abc1234'),
    [new FileInput(file_get_contents('invoice.pdf'), 'application/pdf', 'invoice.pdf', 'main')],
);
echo $created->dmID, PHP_EOL;
```

System-certificate login (records-management applications), hosted variants and OTP accounts:

```php
use MichalCharvat\CzechDataBox\Credentials\{CertificateAndPasswordCredentials, ClientCertificate,
    HostedRecordsServiceCredentials, SystemCertificateCredentials};

$cert = new ClientCertificate(file_get_contents('spisovka.pem'), 'passphrase');
$isds = new Connection(Environment::Production, new SystemCertificateCredentials($cert));
// or new CertificateAndPasswordCredentials($cert, 'login', 'password')
// or new HostedRecordsServiceCredentials($cert, 'abc1234')   // box id goes in the Basic-auth user field
```

The endpoint URL follows from environment + login kind (`Endpoint\EndpointTable`). The pre-2026 host
names still work: `new Connection(..., legacyDomain: true)` uses mojedatovaschranka.cz / czebox.cz.
`Transport\TransportOptions` carries the knobs: `connectTimeout`, `timeout`, `vodzTimeout` (large
transfers, default 1800 s), `maxResponseBytes` (cap for a response held in memory, default 8 MB),
`caFile`, `userAgent` and `tempDir`.

## Services

| `Connection` method | Endpoint | WSDL | Contents |
|---|---|---|---|
| `messageOperations()` | `…/DS/dz` | dm_operations | CreateMessage, CreateMultipleMessage, MessageDownload, Signed(Sent)MessageDownload, AuthenticateMessage, Re-signISDSDocument, DummyOperation |
| `messageInfo()` | `…/DS/dx` | dm_info | message lists, (Sent)MessageEnvelopeDownload, MarkMessageAsDownloaded, Get(Signed)DeliveryInfo, GetMessageStateChanges, GetMessageAuthor(2), EraseMessage, GetListOfErasedMessages + PickUpAsyncResponse, notifications, SuspMessageReport, VerifyMessage |
| `search()` | `…/DS/df` | db_search | FindDataBox(2), ISDSSearch2/3, CheckDataBox, GetDataBoxActivityStatus, GetDataBoxAddress, GetDataBoxList, FindPersonalDataBox, PDZInfo, PDZSendInfo, DataBoxCreditInfo, DTInfo, GetConstants |
| `access()` | `…/DS/DsManage` | db_access | GetOwnerInfoFromLogin(2), GetUserInfoFromLogin(2), GetPasswordInfo, ChangeISDSPassword |
| `manipulations()` | `…/DS/DsManage` | db_manipulations | box and user administration (data providers) |
| `bigMessages()` | `ws2 …/DS/vodz` | dm_VoDZ | UploadAttachment, CreateBigMessage, BigMessageDownload, Signed(Sent)BigMessageDownload, DownloadAttachment, AuthenticateBigMessage |
| `archive()` | `ws2 …/DS/arch` | dm_arch | ArchiveISDSDocument (re-stamping) |
| `passwordChange()` | `www…/asws/changePassword` | ChangePassword | ChangePasswordOTP, SendSMSCode |

`tests/Unit/OperationCoverageTest.php` fails if any of the 78 WSDL operations loses its method.

### Delivery semantics — read this

`GetListOfReceivedMessages` **legally delivers** every listed message when the login holds
`PRIVIL_READ_NON_PERSONAL` or `PRIVIL_READ_ALL` (`$userInfo->privileges()->delivers()`). There is no
way to list without delivering under such a login; use a `PRIVIL_VIEW_INFO`-only login to look
without delivering. `MarkMessageAsDownloaded` only sets state 7 and has no legal effect.
`ConfirmDelivery` no longer exists in ISDS (removed in WSDL 2.33) and is not offered by this library.

### Raw responses

Operations whose output no DTO covers yet (`Manipulations::*`, `Search::isdsSearch3()`, …) return the
status-checked `\stdClass` that ext-soap produced. Read repeated elements through `Internal\Normalize`,
never by direct property access — ext-soap gives you `null`, a single object or an array depending on how
many there are:

```php
use MichalCharvat\CzechDataBox\Internal\Normalize;

$result = $isds->search()->isdsSearch3('ACME');
foreach (Normalize::list($result->dbResults, 'dbResult') as $box) {   // works for 0, 1 and many
    echo $box->dbID, ' ', $box->dbName, PHP_EOL;
}
```

## Errors

Everything throws `Exception\IsdsException` (extends `RuntimeException`) carrying `$isdsCode`,
`$isdsMessage` and `$operation`. Subclasses: `AuthenticationFailed` (HTTP 401/403),
`RateLimited` (3008, 3009, 3013), `DeliveryInProgress` (3006), `NotYetDelivered` (1222),
`NotAvailableYet` (1229, 2352), `WrongMessageKind` (1281), `MessageNotFound` (1211, 1600),
`MessageErased` (1219), `ServiceUnavailable` (network, timeout, unexpected HTTP status),
`InvalidZfo`, and `MalformedResponse` when ISDS reports success but the payload lacks an element the
WSDL promises. Status codes `00xx` are successes, so an empty search result (0002) or a partial
`CreateMultipleMessage` (0004, per-recipient statuses in `dmMultipleStatus`) does not throw.

## Large messages (VoDZ)

`bigMessages()` streams: attachments are uploaded from a stream, and downloads are written into
streams you provide, so memory stays flat regardless of message size.

```php
$att = $isds->bigMessages()->uploadAttachment(fopen('big.pdf', 'rb'), 'application/pdf', 'big.pdf');
$isds->bigMessages()->createBigMessage(
    new MessageEnvelopeInput(dmAnnotation: 'Velká zpráva', dbIDRecipient: 'abc1234'),
    [new \MichalCharvat\CzechDataBox\Input\ExtFileInput($att, 'main')],
);
$isds->bigMessages()->signedBigMessageDownload('1544602', fopen('message.zfo', 'wb'));
```

## ZFO

```php
use MichalCharvat\CzechDataBox\Zfo\{Zfo, ZfoKind, ZfoLimits};

$bytes = $isds->messageOperations()->signedMessageDownload('1234567')->bytes;
Zfo::kind($bytes);                        // ReceivedMessage | SentMessage | DeliveryInfo
$message = Zfo::parse($bytes);            // Dto\Message (≤ ~20 MB, in memory)
$info = Zfo::parseDeliveryInfo($bytes);   // Dto\DeliveryInfo with its events
$signer = Zfo::signer($bytes);            // signing time, certificate validity, CN

// bounded memory for any size: each attachment goes into the stream you return
$envelope = Zfo::parseStream(fopen('big.zfo', 'rb'), fn(array $meta) => fopen('/tmp/' . $meta['dmFileDescr'], 'wb'));
```

`Zfo` only **unwraps** the CMS envelope: it does not verify the signature, the certificate chain or
the time stamp. For an authenticity check ask ISDS: `messageOperations()->authenticateMessage($bytes)`.
Parsing is hardened — DOCTYPE and entity declarations are rejected, and `ZfoLimits` caps file count,
file and total size, XML depth, element count and in-memory size. `parseStream()` needs
`openssl_cms_verify` (the pure-PHP DER fallback works only on in-memory input).

## Security notes

- TLS 1.2+, peer and host verification always on, bundled CA file.
- Passwords and PEM data are `#[\SensitiveParameter]` and hidden from `var_dump`/`print_r` via
  `__debugInfo()`. A SOAP fault is re-created inside the library before being chained, so the
  `SoapClient::__soapCall` frame (which holds the request parameters and cannot be redacted) never
  reaches an exception trace. On **PHP 8.1** the attribute is ignored, so set
  `zend.exception_ignore_args=1` (the php.ini-production default) to keep credentials out of traces.
- Message content is never written to disk, except: 0600 temp files while openssl unwraps a CMS
  document (`Zfo`), and a 0600 client-certificate file only on cURL builds without
  `CURLOPT_SSLCERT_BLOB`. Both are deleted immediately.
- `resources/wsdl` is bundled, so no WSDL is fetched at runtime and `LIBXML_NONET` is set where XML
  is loaded directly.

## Testing

```bash
composer test      # unit suite, no network
composer analyse   # PHPStan level 8
```

The live suite talks to the public test environment and is skipped without credentials:

```bash
ISDS_TEST_USER=… ISDS_TEST_PASS=… ISDS_TEST_SELF_DBID=… \
  [ISDS_TEST_RECIPIENT_DBID=…] [ISDS_TEST_CERT=…] [ISDS_CAPTURE=1] \
  vendor/bin/phpunit --testsuite live
```

It sends a message, reads lists, downloads signed documents and (with `ISDS_CAPTURE=1`) stores real
ZFO captures in `tests/fixtures/captured/` (git-ignored). Note it delivers received messages of that box.

## Sources

`docs/sources.md` records the exact documents, versions, endpoint matrix and the one local WSDL patch;
the WS manuals are in `docs/`. Bundled WSDL/XSD: `resources/wsdl/`.

## License

MIT. Originally based on [dfridrich/CzechDataBox](https://github.com/dfridrich/CzechDataBox).
