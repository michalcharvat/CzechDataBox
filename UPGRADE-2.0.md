# Upgrading from 1.x to 2.0

Version 2 is a rewrite; there is no compatibility layer. Package and namespace changed:

| | 1.x | 2.0 |
|---|---|---|
| package | `dfridrich/czech-data-box` | `michalcharvat/czech-data-box` |
| namespace | `Defr\CzechDataBox` | `MichalCharvat\CzechDataBox` |
| entry point | `DataBox` + `DataBoxSimpleApi` | `Connection` + one service per endpoint |
| responses | generated `Api\t*` classes / `stdClass` | `readonly` DTOs (raw response still on `->raw`) |
| errors | `DataBoxException`, `SoapFault` | `Exception\IsdsException` hierarchy |
| WSDL | 3.03 / 3.09, mojedatovaschranka.cz | 3.10, datovka.gov.cz (`legacyDomain: true` for the old hosts) |

## Connecting

```php
// 1.x
$box = new DataBox();
$box->loginWithUsernameAndPassword($user, $pass, true);       // true = production
$box->loginWithCertificateAndPassword($certFile, $pass, true);
$api = $box->getSimpleApi();

// 2.0
$isds = new Connection(Environment::Production, new PasswordCredentials($user, $pass));
$isds = new Connection(Environment::Production, new CertificateAndPasswordCredentials(
    new ClientCertificate(file_get_contents($certFile), $passphrase), $user, $pass));
```

`setTestMode()` / `setProductionMode()` → `Environment::Test` / `Environment::Production`.
`testConnection()` → `messageOperations()->dummyOperation()` (throws on failure) or
`access()->getOwnerInfoFromLogin2()`.

## DataBoxSimpleApi

| 1.x | 2.0 |
|---|---|
| `getDataBoxInfo()` | `access()->getOwnerInfoFromLogin2()` → `Dto\OwnerInfo` |
| `getUserInfo()` | `access()->getUserInfoFromLogin2()` → `Dto\UserInfo` (`->privileges()`) |
| `getPasswordExpires()` | `access()->getPasswordInfo()` → `?DateTimeImmutable` (UTC) |
| `getListOfReceivedMessages($days, $limit)` | `messageInfo()->getListOfReceivedMessages(new ListFilter(new DateTimeImmutable("-$days days"), new DateTimeImmutable(), $limit))` → `list<Dto\MessageRecord>` |
| `getListOfSentMessages($days, $limit)` | `messageInfo()->getListOfSentMessages(new ListFilter(...))` |
| `findDataBoxById($id)` | `search()->checkDataBox($id)` (state) or `search()->findDataBox2(new OwnerSearch(dbID: $id))` |
| `downloadSignedReceivedMessage($id)` | `messageOperations()->signedMessageDownload($id)->bytes` |
| `downloadSignedSentMessage($id)` | `messageOperations()->signedSentMessageDownload($id)->bytes` |
| `downloadDeliveryInfo($id)` | `messageInfo()->getSignedDeliveryInfo($id)->bytes` (unsigned: `getDeliveryInfo($id)`) |
| `getReceivedDataMessageAttachments($id)` | `messageOperations()->messageDownload($id)->files` (`$file->content`, `->dmMimeType`, `->dmFileDescr`) |
| `createBasicDataMessage($recipient, $subject, $attachments)` + `sendDataMessage()` | `messageOperations()->createMessage(new MessageEnvelopeInput(dmAnnotation: $subject, dbIDRecipient: $recipient), [new FileInput($bytes, $mime, $name, 'main')])` → `Dto\CreatedMessage` |
| `getStats()` | dropped (`isds_stat.wsdl` is not part of WSDL 3.10) |

## Web service accessors

`DmOperationsWebService()`, `DmInfoWebService()`, `DataBoxSearch()`, `DataBoxAccess()` returned raw
`SoapClient`s. Use the typed services (`messageOperations()`, `messageInfo()`, `search()`, `access()`,
`manipulations()`, `bigMessages()`, `archive()`, `passwordChange()`). Every method returns a DTO or
the status-checked raw `stdClass`, and each DTO keeps the untouched response in `->raw`.

## Behaviour changes worth knowing

- **Dates** are `DateTimeImmutable` in **UTC** everywhere (1.x returned local-time strings). Values
  without a zone are read as Europe/Prague, requests are sent in UTC.
- **Errors**: a non-`00xx` ISDS status throws instead of being returned. Catch `IsdsException`, or a
  specific subclass (`NotYetDelivered`, `RateLimited`, …). `00xx` informational codes (0002 nothing
  found, 0003 truncated, 0004 partial multi-send, 0009 no PDZ) do not throw.
- **Repeated elements** are always `list<...>`; the 1.x "one object or an array" pitfall is gone.
- **`ConfirmDelivery`** and `isds_stat` are no longer available in ISDS and were dropped.
- **VoDZ and archiving** are new (`bigMessages()`, `archive()`), both streaming over MTOM.
- **ZFO** parsing is built in (`Zfo::parse`, `parseStream`, `kind`, `signer`); 1.x had none.
