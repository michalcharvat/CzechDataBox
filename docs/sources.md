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
