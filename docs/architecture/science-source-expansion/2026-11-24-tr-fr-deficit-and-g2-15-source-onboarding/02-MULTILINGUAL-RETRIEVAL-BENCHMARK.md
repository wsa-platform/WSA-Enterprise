# 02 — Multilingual Deficit Retrieval Benchmark

Live agent probe after remediation: `tr_fr_deficit_live_probe.json`.

| ID | Language | Question (abbrev.) | Completeness | Citations | Answer signal |
|---|---|---|---|---:|---|
| EN-DEF | en | effects of deficit irrigation on wheat yield | SUBSTANTIVELY_COMPLETE | 16 | 50% MAD (DI); regulated deficit LLL 90% ETc |
| AR-DEF | ar | تأثير الري الناقص على إنتاجية القمح | SUBSTANTIVELY_COMPLETE | 17 | same MAD/DI + regulated deficit evidence |
| TR-DEF | tr | Eksik sulamanın buğday verimine etkisi | SUBSTANTIVELY_COMPLETE | 17 | MAD (DI) grain yield + WUE |
| FR-DEF | fr | effet de l'irrigation déficitaire… | SUBSTANTIVELY_COMPLETE | 14 | deficit irrigation + WUE |
| EN-CWR | en | crop water requirement of wheat | SUBSTANTIVELY_COMPLETE | 6 | ETc / water requirements (regression) |

## CSQ / executable terms (post-fix)

Artifact: `tr_fr_deficit_csq_probe.json` + compiled-query probe.

| Lang | CSQ process | Executable process |
|---|---|---|
| EN | deficit irrigation | deficit irrigation |
| TR | eksik sulama | **deficit irrigation** |
| FR | l irrigation déficitaire | **deficit irrigation** |

## Expected elements (derived from retrieved evidence, not golden invent)

- Deficit / DI / MAD / regulated deficit treatment
- Yield or WUE outcome with conditions
- Citation to scholarly source

No language is forced to share identical document IDs.
