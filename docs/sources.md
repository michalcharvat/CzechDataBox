# Sources

Downloaded 2026-09-17.

| Source | Version / date |
|---|---|
| Provozní řád ISDS (https://datovka.gov.cz/info/cs/80.html, `2256_Provozni_rad_ISDS_26_06_2026.zip`) | 26. 6. 2026 |
| Příloha 2 — WS manipulace s datovými zprávami → `isds-ws-manual-3.8.1.pdf` | 3.8.1 (7. 7. 2026) |
| Příloha 2 — WS související s přístupem do ISDS → `isds-ws-access-3.8.1.pdf` | 3.8.1 |
| Příloha 2 — WS vyhledávání datových schránek → `isds-ws-search-3.8.1.pdf` | 3.8.1 |
| WSDL changelog → `isds_wsdl_v310.txt` (converted from CP1250) | WSDL 3.10, TEST+PROD 16. 4. 2026 |
| `resources/wsdl/*` from https://www.datovka.gov.cz/static/wsdl/v20/ | header "verze 3.11" |

## WSDL 3.10 vs the files online

The online set is byte-identical to Příloha 2 of the Provozní řád zip except:

- `dmBaseTypes.xsd` — header 3.10 → 3.11, changelog line "19.05.2026 verze 3.11 - sjednocení verzí"; no schema change.
- `ChangePassword.wsdl`, `ChangePasswordTypes.xsd` — header 2.31 → 3.11; `ChangePassword.wsdl` service
  address moved from `https://www.mojedatovaschranka.cz/asws/changePassword` to
  `https://www.datovka.gov.cz/asws/changePassword` (16. 6. 2026).

So the bundled files are the WSDL 3.10 operation set with the current domain.
Not bundled: `SetConcept.wsdl`, `ExtWs.wsdl` (sending gateway, out of scope), 1.x `isds_stat.wsdl`.

## Endpoint addresses (`grep -h 'soap:address\|soap12:address' *.wsdl | sort -u`)

```
      <soap:address location="https://ws1.datovka.gov.cz/DS/df"/>
      <soap:address location="https://ws1.datovka.gov.cz/DS/DsManage"/>
      <soap:address location="https://ws1.datovka.gov.cz/DS/dx"/>
      <soap:address location="https://ws1.datovka.gov.cz/DS/dz"/>
      <soap:address location="https://www.datovka.gov.cz/asws/changePassword"/>
      <soap12:address location="https://ws2.datovka.gov.cz/DS/arch"/>
      <soap12:address location="https://ws2.datovka.gov.cz/DS/vodz"/>
```

## Version headers (`grep -m1 -o "verze: *[0-9.]*"`)

```
ChangePassword.wsdl verze: 3.11
ChangePasswordTypes.xsd verze: 3.11
db_access.wsdl verze: 3.11
db_manipulations.wsdl verze: 3.11
db_search.wsdl verze: 3.11
dbTypes.xsd verze: 3.11
dm_arch.wsdl verze: 3.11
dm_info.wsdl verze: 3.11
dm_operations.wsdl verze: 3.11
dm_VoDZ.wsdl verze: 3.11
dmBaseTypes.xsd verze: 3.11
```

## Endpoint matrix (`src/Endpoint/EndpointTable.php`)

WS manual 3.8.1, ch. 1.2.1.1 baseURL per login kind, 1.2.2.1 VoDZ, 1.2.3 archive:

| Login | ws1 services (dz, dx, df, DsManage) | ws2 services (vodz, arch) |
|---|---|---|
| name + password | `https://ws1.{d}/DS/{svc}` | `https://ws2.{d}/DS/{svc}` |
| system certificate | `https://ws1c.{d}/cert/DS/{svc}` | `https://ws2c.{d}/cert/DS/{svc}` |
| certificate + name + password | `https://ws1c.{d}/certds/DS/{svc}` | `https://ws2c.{d}/certds/DS/{svc}` |
| hosted records service (box id as Basic user) | `https://ws1c.{d}/hspis/DS/{svc}` | `https://ws2c.{d}/hspis/DS/{svc}` |

`{d}` = `datovka.gov.cz` / `datovka-test.gov.cz`; legacy `mojedatovaschranka.cz` / `czebox.cz` stay valid
("Stará URL … jsou i nadále použitelná"). The manual shows ws2c only as `…/DS/vodz` "see 1.2.1.1" with the
`/cert` example, and `ws2c/cert/DS/arch` "atd."; the `/certds` and `/hspis` ws2c cells follow that rule and
are confirmed only once a live certificate login exercises them. All 16 host names resolve and verify
against `resources/ca/isds-ca-bundle.pem` (2026-09-17). Not modelled: `www.datovka.gov.cz/apps` (OTP/mobile key),
`ws1c/hssu` (§14a access interface), `*.datovka.cms2.cz` (KIVS network).

## Local patches to the bundled WSDL/XSD

| File | Patch | Why |
|---|---|---|
| `dbTypes.xsd` `tGetAddressOutput` | added optional `dbStatus` (`tns:tDbReqStatus`) | upstream schema omits it although ISDS returns it (WS search manual 3.8.1, GetDataBoxAddress sample); ext-soap drops undeclared elements, so the status (0000 / 0009 / errors) was invisible. Every other operation output declares its status (checked by script 2026-09-17). |

When refreshing the WSDL set, re-apply every patch in this table (search for `LOCAL PATCH`).
