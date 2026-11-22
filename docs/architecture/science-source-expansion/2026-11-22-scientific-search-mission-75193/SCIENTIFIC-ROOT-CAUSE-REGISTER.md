# Scientific Root Cause Register — Mission 75193

## Purpose
Root causes found and disposition during mission 75193.

## Status Summary

| ID | Root Cause | Layer | Disposition | Evidence |
|---|---|---|---|---|
| RC-75193-01 | Stage-3 readiness called FTS COUNT/EXISTS on multi-GB SQLite | LocalIndex adapter | FIXED | SqliteFtsScientificIndex + Adapter |
| RC-75193-02 | Agent force_execute bypassed Agent gate but SearchService still short-circuited | AgriculturalScientificSearchService | FIXED | ForceExecuteHonorsStage3GateTest |
| RC-75193-03 | Composer isSynthesizable rejected AGRIS yield/irrigation evidence under quantity family | AnswerComposer / ExpressionAccuracyGate | FIXED | QuantityPropertyAddressTermsTest + AGRIS L7 probe |
| RC-75193-04 | Public API 504 under multi-source before readiness fix | Runtime/nginx | MITIGATED | Live query now completes ~70s with ledger |
| RC-75193-05 | Dr Duke chemical rows fail scholarly validation for phytochemical claim | Validation/claim match | OPEN | compose_dr_duke highest=L4 |

## Remaining Issues
RC-75193-05 open; Full Suite historical failures out of scope.
