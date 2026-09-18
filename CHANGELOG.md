# Changelog

## Unreleased

### Fixed
- VoDZ transport: a caller stream that fails mid-upload now aborts the transfer instead of waiting out
  `vodzTimeout` (the progress callback never saw the error).
- VoDZ transport works on PHP 8.1 again (`CURLOPT_XFERINFOFUNCTION` is PHP 8.2+; 8.1 uses
  `CURLOPT_PROGRESSFUNCTION`).

## 2.0.0 (2026-09-18)

Rewrite for ISDS WSDL 3.10. See [UPGRADE-2.0.md](UPGRADE-2.0.md).

### Added
- `Connection` with lazy per-endpoint transports and one service class per WSDL: `MessageOperations`,
  `MessageInfo`, `Search`, `Access`, `Manipulations`, `BigMessages`, `Archive`, `PasswordChange`.
  All 78 operations of WSDL 3.10 are covered and pinned by `OperationCoverageTest`.
- Typed `readonly` DTOs (messages, envelopes, files, delivery info and events, state changes, message
  author, owner/user info, privileges, box state, credit info, uploaded attachments) that keep the raw
  response in `->raw`; dates are `DateTimeImmutable` in UTC.
- Typed exception hierarchy under `Exception\IsdsException` with ISDS codes mapped from WS manual 3.8.1.
- Four login kinds (password, system certificate, certificate + password, hosted records service) and a
  pinned endpoint matrix for production/test and the legacy domains.
- SOAP 1.1 transport over cURL (HTTP status mapped before XML parsing, client certificate as a blob),
  and a streaming SOAP 1.2 + MTOM transport for the ws2 endpoints (VoDZ, archiving) that never holds
  message content in memory.
- ZFO support: CMS unwrap via openssl with a pure-PHP DER fallback, hardened streaming XML parser
  (`ZfoLimits`, DOCTYPE/ENTITY rejected), `Zfo::parse`/`parseStream`/`kind`/`parseDeliveryInfo`/`signer`.
- Bundled WSDL 3.10 set and an ISDS TLS CA bundle; `docs/sources.md` records versions and the one local
  WSDL patch (`tGetAddressOutput` is missing `dbStatus` upstream).
- Unit suite with fixtures replayed through the real ext-soap + bundled WSDL, a local HTTP fake for the
  MTOM transport, and a live suite against datovka-test.gov.cz.

### Changed
- Package `michalcharvat/czech-data-box`, namespace `MichalCharvat\CzechDataBox`, PHP ≥ 8.1.
- `Internal\Normalize` is public API: it is how consumers read the raw `\stdClass` responses safely.
- A malformed but "successful" response raises `Exception\MalformedResponse` instead of an SPL exception.
- `ListFilter` no longer caps `dmLimit` at 1000 (ISDS allows more) and rejects `dmOffset < 1` (it counts from 1).
- Hosts moved to datovka.gov.cz / datovka-test.gov.cz; the old names are available via `legacyDomain: true`.

### Removed
- The generated 1.x client (`Api\*`, `DataBox`, `DataBoxSimpleApi`), `ConfirmDelivery` (removed by ISDS
  in WSDL 2.33) and `isds_stat` (not part of WSDL 3.10).

## 1.x

See the history of the `master` branch.
