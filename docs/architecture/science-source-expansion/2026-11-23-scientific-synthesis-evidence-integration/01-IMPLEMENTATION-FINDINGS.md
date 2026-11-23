# 01 — Implementation Findings

## Verified gaps

1. **Conflict polarity without context:** `EvidenceConflictDetector` marked opposing keyword polarity as `CONFLICTING` even when experimental conditions differed (irrigation method, climate, etc.).
2. **No conflict-assessment vocabulary:** No canonical outcome distinguishing `RESOLVED_BY_CONTEXT` vs `GENUINE_CONFLICT` vs `NOT_COMPARABLE`.
3. **Extractive synthesis packet absent:** Composer built findings/conflicts but did not expose a structured synthesis packet separating supported / partial / contextual / genuine conflict / gaps.
4. **Answer explanation:** Contextual differences reused the same “conflicting evidence” phrasing.

## Root causes

- Detector compared polarity only; `conditions` on `ScientificEvidenceItem` unused for conflict classification.
- Synthesis layer concatenated findings without an integration step over claim relationships + context assessments.

## Contracts reused (not replaced)

- `ClaimEvidenceRelationship` (support axis unchanged).
- `ClaimAttributionLedger` (L7 unchanged).
- `QuestionClaimSynthesisContract` (question→claim→binding unchanged).
- Directness axis remains on `ScientificEvidenceDirectnessAssessor`.
