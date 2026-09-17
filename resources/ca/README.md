# CA bundle for ISDS TLS

`isds-ca-bundle.pem` is the default `CURLOPT_CAINFO` of both transports
(`TransportOptions::caFile()`); pass your own file to override it.

| Certificate | notAfter | Why |
|---|---|---|
| DigiCert Global Root G2 (SHA-256 `CB3CCBB7…5AB1CB5F`) | 2038-01-15 | Issues `GeoTrust EV RSA CA G2`, the TLS chain served by ws1/ws2/www on datovka.gov.cz and datovka-test.gov.cz (checked 2026-09-17) |
| PostSignum Root QCA | 2030-04-06 | The only still-valid PostSignum root from the 1.x bundle; kept in case a host moves back to PostSignum |

The 1.x `postsignum_qca_sub`, `postsignum_qca2_root` and `postsignum_qca2_sub`
expired (2020, 2025, 2020) and never issued the current TLS chain, so they were dropped.

Check after a certificate change:

```bash
for h in ws1.datovka.gov.cz ws1.datovka-test.gov.cz ws2.datovka.gov.cz ws2.datovka-test.gov.cz www.datovka.gov.cz; do
  echo | openssl s_client -connect $h:443 -servername $h -CAfile resources/ca/isds-ca-bundle.pem 2>/dev/null | grep 'Verify return code'
done
```
