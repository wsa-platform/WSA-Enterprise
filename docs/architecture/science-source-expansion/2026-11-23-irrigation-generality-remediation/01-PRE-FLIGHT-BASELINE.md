# 01 — Pre-Flight Baseline

**Date:** 2026-11-23  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Baseline HEAD:** `63d693fd7a0f4686f37f7e55b2d088b6b6152a7e`  
**Remote match at start:** local HEAD == `origin/phase-18-m18-ai-marketing-communications`

## Git status (start)

- Tracked WIP present (unrelated): `.gitignore`, `backend/.env.example`, CGHIA capability cell, CSQ, migrations, `phpunit.xml`, storage gitignore, plus other modified/untracked trees from prior missions.
- Mission rule: isolate irrigation-generality files only; do not stage unrelated WIP.
- Prior irrigation follow-up already at `63d693f` (partial Gaps A/B/D).

## Database / search safety

- Primary DB remains SQLite (project default). No PostgreSQL replacement.
- No LocalIndex rebuild performed.
- Contract tests are plan/QUS/query-builder only — no persistent search mutation.

## Baseline focused tests (pre-edit after `63d693f`)

- `ArabicScientificSearchRemediationContractTest` — PASS (prior follow-up).

## Live call sites verified

| Component | Path | Observation |
|---|---|---|
| Helper | `AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement` | Partial EN/AR; systems phrases over-broad; TR/FR incomplete |
| Promotion | `QueryUnderstandingService` `$explicitIrrigation` | Required `factorAskedAsAttribute` (good); disease/systems not gated |
| Sense | `resolveScientificSense` CWR branch | **`$researchIntent === 'irrigation'` alone forced CWR** (Gap C) |
| Fallback | `englishRetrievalFallbackForRole` | Conditional deficit/quantity; still coarse |
