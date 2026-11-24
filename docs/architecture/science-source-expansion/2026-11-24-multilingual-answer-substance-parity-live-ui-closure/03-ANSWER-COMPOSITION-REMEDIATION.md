# 03 — Answer Composition Remediation

## Root causes

| ID | Severity | Cause |
|---|---|---|
| SP-01 | HIGH | CWR answered by irrigation treatment inventories |
| SP-02 | HIGH | Preferred sentence ignored quantitative CWR when treatment list led |
| SP-03 | HIGH | FR range mode promoted grain yield kg as «Besoin» |
| SP-04 | MEDIUM | CWR motivation sentences (“requires accurate measurement…”) as findings |
| SP-05 | HIGH | React Stable Result Set showed weaker multi_answer before composed primary |

## Fixes

### Backend `AnswerComposer.php`
- CWR act/need matching rejects treatment-inventory-only findings.
- `preferredFindingSentence` scores empirical / water-requirement / quantitative substance.
- `isLowQualityFindingFragment` rejects CWR motivation chrome.
- `measurementMatchesRequestedProperty` blocks non-water units for CWR quantity questions (not deficit-effects).
- Deficit finding match expanded (`(di)`, `% etc`, …).

### Frontend `stableResearchPresentation.ts`
- `promoteComposedPrimaryAnswer` on page-1 stable/multi answers so composed scientific answer is primary card.

## Tests
- `AnswerComposerLiveQualityGateTest` (+ CWR/motivation/measurement cases) — PASS
- `stableResearchPresentation.test.ts` — PASS (4)
