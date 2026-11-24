# 02 — Scientific Answer Quality Audit

**Evidence primary:** `live_answer_quality_probe.json` (schema v2, generated 2026-11-24T13:30:53Z)  
**Remediation:** AnswerComposer low-quality fragment / act-match / exotic-domain gates

## Case Summaries (post-remediation live probe)

| Case | Lang | Status | Relevance | Synthesis packet | Notes |
|---|---|---|---|---|---|
| EN_DEFICIT_IRRIGATION | en | scientific_generated | PASS | yes | Empirical deficit-yield findings; no chrome hits |
| AR_DEFICIT_IRRIGATION | ar | scientific_generated | PARTIAL | yes | Same sense; some field-investigation chrome still in findings |
| TR_CWR | tr | scientific_generated | PARTIAL | yes | Treatment-list excerpt; weak CWR completeness |
| FR_CWR | fr | scientific_generated | PARTIAL | yes | Same excerpt pattern as TR |
| EN_CONFLICT_CONTEXT | en | scientific_generated | PARTIAL | yes | Context notes present; arid/humid comparison incomplete |
| EN_SALINITY | en | scientific_generated | PASS | yes | Salinity–yield finding on-topic |
| AR_DISEASE | ar | insufficient_evidence | PASS* | no | Correctly insufficient (*for honesty) |
| EN_INSUFFICIENT_STYLE | en | insufficient_evidence | PASS | no | Martian domain blocked (no confident terrestrial answer) |

## Dimension Notes

1. **Scientific Relevance:** EN deficit improved after wastewater/act filter. AR still mixes methods chrome.
2. **Completeness:** Multi-factor arid/humid and TR/FR CWR incomplete relative to question scope.
3. **Scientific Synthesis:** `has_synthesis_packet=true` on supported cases; notes distinguish contextual vs genuine conflict.
4. **Claim Attribution:** Ledger rows present when claims exist (`ledger_rows` ≥ 1).
5. **Practical Usefulness:** EN deficit useful; TR/FR often descriptive treatment lists, not quantified CWR.

## Root Causes Addressed

| ID | Severity | Cause | Fix |
|---|---|---|---|
| AQ-01 | HIGH | Meta/chrome first sentences as findings | `isLowQualityFindingFragment` + `preferredFindingSentence` |
| AQ-02 | HIGH | Wastewater findings for deficit questions | `findingAddressesAskedAct` |
| AQ-03 | CRITICAL | Exotic/Martian answered from earth evidence | `questionRequiresUnavailableDomainEvidence` → insufficient |
| AQ-04 | MEDIUM | Causal questions labeled as water-requirement heading | Causal heading override in `mainAnswerHeading` |

## Remaining Gaps (not fully closed)

- AR/TR/FR still surface EN source excerpts with language prefixes (expected source/answer separation; readability PARTIAL).
- TR/FR CWR answers not LIVE_ANSWER quality-equivalent to EN deficit.
- AR methods-sentence (“field investigation was conducted…”) may still pass if not caught by chrome patterns.
