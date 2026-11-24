# 00 — Mission Baseline

**Date:** 2026-11-24  
**Mission:** Multilingual Answer Substance Parity & Live UI Closure  
**Branch:** `phase-18-m18-ai-marketing-communications`

## A. Git Baseline

| Field | Value |
|---|---|
| HEAD at start | `648a4b4f791aec90fea997cd4cc5768c75fba254` |
| Prior feature SHA (context) | `813e361b7396426d9bbf1da0ab85e94ce7fbdbd5` |
| Remote tracking | `origin/phase-18-m18-ai-marketing-communications` |
| Sync | Local = Remote at start |
| Dirty tree | Yes — unrelated WIP protected; excluded from mission commits |

**WIP policy:** CGHIA, CapVer, CSQ, phpunit.xml, nginx, archive `_tmp-*`, tools — do not commit.

## B. Component Readiness

| Component | Status |
|---|---|
| AnswerComposer (+Phrases) | READY — Hybrid; live quality gates present; substance parity gaps suspected for CWR TR/FR |
| ScientificSynthesisIntegrator | READY (prior mission) |
| ClaimAttributionLedger | READY |
| Evidence / conflict assessors | READY |
| API `/public/research-agent/query` | READY |
| React home scientific search | READY for Live UI attempt via Playwright CLI |
| Flutter | TBD — smoke only if runtime available |
| LocalIndex | Reachable; not PRODUCTION_READY → live retrieval WITH_LIMITATIONS |

## C. Baseline Tests (mission-related)

Filter: LiveQualityGate + ExplanatoryFragment + SynthesisEvidenceIntegration + ClaimAttributionLedger (+ IrrigationGenerality when finished).

**Initial slice observed green** (LiveQuality 3, Explanatory 4, Ledger 5, Synthesis 2). Full filter completion recorded in regression doc.

## D. Classification

**`BASELINE_READY_WITH_LIMITATIONS`**

- Proceed with fixture/unit remediations and live agent probes.
- Live UI via Playwright (not MCP browser click).
- Flutter may be NOT_RUN.
