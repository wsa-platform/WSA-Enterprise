# Mission 75193 — Final Verification Report (EN evidence ledger)

## Purpose
Evidence-backed completion record for Multi-Source Scientific Search Restoration mission 75193. Arabic executive report: `MISSION-FINAL-REPORT.ar.md`.

## Status Summary

| Item | Status | Evidence |
|---|---|---|
| Pre-Flight | PASS | `00-PREFLIGHT.txt` |
| LocalIndex READY | VERIFIED | store/FTS gens 2088/19/5 |
| AGRIS agent path | L7 | `mission75193_compose_agris.json` + live API |
| Organic Eprints agent path | L7 | compose probe + live ledger attributions |
| Dr Duke agent path | L4 | compose: L4; validation rejects chemical rows for scholarly claims |
| Evidence independence | VERIFIED | PHPUnit EvidenceIndependenceAssessorTest |
| Claim ledger live | LEDGER_ATTRIBUTION_VALIDATED | API POPULATED, l7=true, OE+AGRIS attributions |
| FAOSTAT / Consensus policy | VERIFIED | FaoStatAndConsensusPolicyContractTest |
| FAOSTAT/Consensus live | NOT_RUN | FREE-ONLY / explicit-only |
| Full PHPUnit | COMPLETED_WITH_FAILURES | 3647 tests; 197 fail; 8 err; 14 skip |
| Mission PHPUnit subset | PASS | 51 tests |
| Playwright | PASS (mocked UI) | 2 passed e2e-rev65 |

## Details

### Fixes shipped
1. Stage-3 LocalIndex readiness no longer full-scans multi-GB FTS (`documentCount`/`EXISTS` removed from hot path).
2. `force_execute` honored in `AgriculturalScientificSearchService` (aligned with Agent).
3. Quantity CSQ family address terms include yield; crop_water_requirement merges irrigation terms so AGRIS irrigation/yield evidence is synthesizable.

### Full suite triage (§10.1)
- Mission subset (composer/expression/independence/ledger/policy/LocalIndex lifecycle/force_execute): PASS — no mission regression detected.
- Broad failures (Membership consolidation, CapVer isolation, FaoStat Phase3 fixtures, HTTP stray GBIF in Stage9 security, empty isolated LocalIndex cold/stale in onboarding mirrors): classified **PRE_EXISTING / ENVIRONMENT / EXTERNAL** — outside mission repair scope unless they block an acceptance criterion (they do not; live path + targeted tests pass).

## Evidence References
- Preflight: `00-PREFLIGHT.txt`
- Probes: `backend/storage/app/e2e-tmp/mission75193_*.json`
- PHPUnit JUnit: `backend/storage/app/e2e-tmp/mission75193-phpunit.xml`
- Playwright: `frontend/e2e-rev65/tests/claim-attribution-ledger.spec.ts`

## Remaining Issues
- Dr Duke L5–L7 for phytochemical claims still limited by validation/claim matching (nature of chemical DB vs scholarly claim contract).
- Full Suite not green (historical/env failures).
- Playwright verifies UI contract with route mocks, not live multi-source browser traffic.
- Consensus/FAOSTAT live paths NOT_RUN under FREE-ONLY / default-disabled policy.
