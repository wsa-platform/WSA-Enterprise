# 00 — Mission Baseline

**Date:** 2026-11-24  
**Mission:** Multilingual Live Answer Quality & Synthesis Verification  
**Branch:** `phase-18-m18-ai-marketing-communications`

## A. Git Baseline

| Field | Value |
|---|---|
| HEAD at start | `9e733514b1650e3019bde120d6ded515353b4d5d` |
| Remote HEAD | matched |
| Sync | Local = Remote |
| Dirty tree | Yes — protected unrelated WIP + mission-related AnswerComposer quality WIP |

**Mission-related WIP (protected, under evaluation):**
- `AnswerComposer.php` / `AnswerComposerPhrases.php` — low-quality fragment filtering + preferred empirical sentence selection (~+204 lines)

**Unrelated WIP:** CGHIA, CSQ, phpunit, many untracked docs/tools — excluded from commits.

## B. Component Readiness

| Component | Path | Readiness |
|---|---|---|
| AnswerComposer | Synthesis/AnswerComposer.php | Present; Hybrid (extractive findings + ScientificSynthesisIntegrator packet). WIP improves fragment quality. |
| ScientificSynthesisIntegrator | Synthesis/ScientificSynthesisIntegrator.php | Present (prior mission) |
| EvidenceConflictContextAssessor | Validation/… | Present |
| EvidenceConflictDetector | Validation/… | Present with context assessment |
| ClaimAttributionLedger | Synthesis/… | Present; baseline tests PASS |
| Evidence pipeline | Validation services + agent stages 3–5 | Exercisable via AgriculturalResearchAgent |
| API | `POST /api/v1/.../research-agent/query` | Present |
| React / Flutter | Frontend present | Live UI TBD |

## C. LocalIndex

`php artisan scientific:diagnose-persistence`:
- local_index connection reachable, `scientific_source_records` present
- Note: `SQLite LocalIndex ≠ PRODUCTION_READY`
- Classification for live retrieval: **READY_WITH_LIMITATIONS**

## D. Baseline Tests (with current WIP)

```text
docker compose exec -T backend php artisan test --filter="ScientificSynthesisEvidenceIntegrationContractTest|ComposerConflictExpressionContractTest|IrrigationGeneralitySemanticMatrixTest|ClaimAttributionLedgerTest"
```

**Result:** 48 passed (227 assertions). Failures: none.

## E. Decision

**`BASELINE_READY_WITH_LIMITATIONS`**

- Live retrieval possible but not PRODUCTION_READY.
- Unrelated WIP present and protected.
- AnswerComposer WIP is candidate remediation for extractive fragment quality (to be verified).
