# Phase 1 — Level 1 Scientific Search Audit (Rev 11.0.3)

**Scope refs:** EXIT-09, D-04, D-11  
**Classification:** Level 1 complete for CSQ identity / source routing / evidence framing defects found in the focused suite. Full path (Levels 2–3) not declared `AUDITED_AND_VERIFIED`.

## 1. CSQ Identity and Query Compilation

| Check | Result | Evidence |
|---|---|---|
| CSQ remains semantic authority when present | PASS | `CsqArchitecturalClosureTest`, builder routes to `buildVariantsFromCanonicalSpecification` |
| National land geography preserved on CSQ variants | PASS after SSD-1103-01 | Egypt variants include `egypt` + land/soil |
| Botanical family Latin identity preserved | PASS after SSD-1103-01 | Cucurbitaceae plants/species/members |
| Crop knowledge-option echo ≠ independent roles | PASS after SSD-1103-02 | PostCsq farming-needs |

## 2. Source Routing and Access Families

| Check | Result | Evidence |
|---|---|---|
| Scholarly trio selected; Consensus excluded | PASS | `test_selector_uses_semantic_scholar_not_consensus` |
| Provider isolation | PASS | ScientificProviderIsolation / ContractIsolation suites |
| Live connectivity | PRESERVED (no reprobe) | EXIT-08 v11.0.1 JSON |

## 3. Evidence Pipeline and Traceability

| Check | Result | Evidence |
|---|---|---|
| Outcome B DIRECT gate not weakened | PASS | test_n aligned; citations[] remain DIRECT-only |
| supported_answer hatch retains supporting as additional context | PASS | evidenceReferences + additionalInformation |
| Outcome B provider attribution | PRESERVED | commit `78b9cbc` |

## Before / After

| Component | Before | Repair | After | Evidence |
|---|---|---|---|---|
| CSQ land variants | `types land classification` (no Egypt) | Specialize land inventory + geography retain | National inventory includes Egypt | test_l |
| CSQ family variants | Arabic surface only | Latin family inventory path | Contains `cucurbitaceae` | test_k |
| Evidence citations | Stale test expected SUPPORTING citations | Align to Outcome B | citations=[]; supported_answer mode | test_n |
| Crop option CSQ | process=farming → independent | Strip option echo | independent=false | PostCsq |

## Regression

Focused EXIT-09 suite: **145 passed / 0 failed / 755 assertions / exit 0**  
Prior v11.0.1: 140 / 5 / 737 / exit 1  
Log: `_tmp-r1103-exit09-five-source-csq.txt`
