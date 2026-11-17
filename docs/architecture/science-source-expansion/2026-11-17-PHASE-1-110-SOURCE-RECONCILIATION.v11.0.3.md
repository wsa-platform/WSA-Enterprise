# Phase 1 — 110-Source Reconciliation (Rev 11.0.3)

Recorded: `2026-11-17T19:49:01Z`

**Result: `PASS`**

## Counts

| Artifact | Count | Unique adr_id |
|---|---:|---:|
| ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json | 110 | 110 |
| 2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json | 110 | 110 |
| ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v3.json | 110 | 110 |
| Master Status Registry v11.0.3 | 110 | 110 |

## ID set equality

- register == matrix: `True`
- register == canonical: `True`
- register == master registry: `True`
- register order == matrix order: `True`

## Mismatches

None. Exactly 110 unique source identities preserved from membership 110.v1.

## Explicit non-membership note

Legacy Stage3 runtime keys (`openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`) are registered in `ScientificSourceCatalog` but are **not** CURRENT membership seats under 110.v1. They are therefore excluded from the 110-source identity count and documented in the Evidence Index.
