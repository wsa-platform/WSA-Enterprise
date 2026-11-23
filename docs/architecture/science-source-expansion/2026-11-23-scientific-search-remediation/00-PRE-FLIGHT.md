# 00-PRE-FLIGHT — Scientific Search Remediation

**Decision:** `BASELINE_READY_WITH_LIMITATIONS`  
**Date:** 2026-11-23

## A. Git Baseline

| Item | Value |
|---|---|
| Root | `G:/WSA-Enterprise` |
| Branch | `phase-18-m18-ai-marketing-communications` |
| HEAD | `d655c40e523e5245721f177cdd73f7a136ba65fe` |
| Tracking | `origin/phase-18-m18-ai-marketing-communications` |
| Remote | `https://github.com/wsa-platform/WSA-Enterprise.git` |
| WIP | ~228 porcelain lines (M + ??). **Preserve all.** Mission stages only its own files. |

## B. LocalIndex / Persistence

- Pre-flight used QUS + variant + eligibility only (no LocalIndex write).
- Full live search deferred to post-fix probe (prior audit JSON retained as historical).
- No rebuild/repair during pre-flight.

## C. Scientific Search Baseline (QUS path)

Artifact: `backend/storage/app/e2e-tmp/mission_preflight_qus.json`

| Pair | EN sense | AR sense | AR Arabic-in-variants | OE eligible EN | OE eligible AR |
|---|---|---|---|---|---|
| wheat_irrigation | crop_water_requirement | **plant_growth** | **yes** | no | no |
| organic_soil | plant_growth | plant_growth | **yes** | **yes** | **no** |

Historical live validated (audit): wheat 62/33, organic 45/32 — not acceptance targets.

## D. Claim Attribution Ledger

Not re-hit via HTTP in pre-flight. Prior mission: API POPULATED on organic EN path. Will re-check post-fix on in-process compose observability.

## E. Testing Baseline

Focused PHPUnit for mission tests: NOT_RUN before first code change. Known historical suite noise (LanguageContract R2 drift = ISS-TEST-001).

## F. Failure Classification

| Observation | Class |
|---|---|
| AR wheat sense plant_growth | Mission-related PRE_EXISTING |
| Arabic in variants | Mission-related PRE_EXISTING |
| OE AR not eligible | Mission-related PRE_EXISTING |
| Dirty WIP tree | UNRELATED user/WIP — preserve |

## Limitations

- Pre-flight did not re-run full Stage-3 HTTP (time/cost); QUS baseline sufficient to confirm RCA still present.
- TR/FR live NOT_RUN.
