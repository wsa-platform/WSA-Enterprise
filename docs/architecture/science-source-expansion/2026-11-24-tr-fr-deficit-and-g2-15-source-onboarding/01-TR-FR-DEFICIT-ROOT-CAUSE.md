# 01 — TR/FR Deficit Root Cause

## Failure stages (evidence-based)

| Stage | Pre-fix TR/FR | Evidence |
|---|---|---|
| Language detection | OK | `question_language` tr/fr |
| Query Understanding / CSQ | Partial | process collapsed |
| Query Building | **FAIL** | executable process ≠ deficit |
| Retrieval | Off-topic survivors | climate/salinity/irrigation level |
| Evidence Validation | Non-empty | validated counts > 0 |
| Result Selection / Composition | Emptied findings | `limited_conflicting` chrome |

**Primary classifications:** Scientific Identity / CSQ (Turkish possessive collapse) + Query Building (English fallback skipped after `compileTerm`) + Result Selection (prior MAD/DI filter gap, already remediating).

## Root causes (ordered)

1. **CSQ-TR-POSSESSIVE:** `causalArgumentSpans` used `/(\S+?)(?:nin|nın|…)/` so `eksik sulamanın` → affector `sulama`, dropping the deficit qualifier.
2. **QB-COMPILE-EARLY-RETURN:** `specExecutableNonEntityRole` returned `compileTerm(process)` (`eksik sulama` / `l irrigation déficitaire`) **before** English scholarly fallback, so LocalIndex never searched `deficit irrigation`.
3. **QB-CONTEXT-ASK-SURFACE:** `RetrievalSpecification::fromCanonical` omitted `original_question`, so morphology recovery from the ask surface was unavailable when process collapsed.
4. **SELECTION (prior):** Deficit act filter required literal “deficit”; MAD/(DI) yield sentences rejected → empty findings (addressed in AnswerComposer).
5. **SEMANTICS (prior):** `déficit hydrique` / TR deficit cues incomplete in English concept map.

## Not primary

Language Detection; Evidence Validation emptiness; Claim Attribution ledger absence; Membership/Activation of new sources.
