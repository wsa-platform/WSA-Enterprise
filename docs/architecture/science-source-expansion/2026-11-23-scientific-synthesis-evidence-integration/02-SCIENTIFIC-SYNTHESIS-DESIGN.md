# 02 — Scientific Synthesis Design

## Target architecture (additive)

```
EvidenceConflictDetector
  → EvidenceConflictContextAssessor (NEW)
      outcomes: genuine_conflict | resolved_by_context | not_comparable | insufficient_information
AnswerComposer.compose
  → buildClaims / ClaimAttributionLedger (existing)
  → buildConflicts (genuine only; enriched with assessment)
  → ScientificSynthesisIntegrator (NEW)
      packet → researchMetadata.scientific_synthesis
  → detailedExplanation includes context notes + synthesis notes
```

## Principles

- Do not invent facts or experimental metadata.
- Do not auto-pick a “winning” study.
- Contextual differences must not erase supported findings.
- Genuine conflicts remain unresolved in the answer.
- Keep Membership/Capability/Eligibility/Activation untouched.
