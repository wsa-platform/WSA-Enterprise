# 05 — Context and Conflicts

## Components

- `EvidenceConflictAssessmentOutcome`
- `EvidenceConflictContextAssessor`
- Updated `EvidenceConflictDetector`

## Behavior

| Situation | Outcome | Claim relationship |
|---|---|---|
| Opposing polarity + differing conditions | `resolved_by_context` | Preserved (not forced CONFLICTING) |
| Opposing polarity + same conditions | `genuine_conflict` | CONFLICTING |
| Opposing polarity + no conditions | `insufficient_information` | CONFLICTING (conservative) |
| Different entities / measurement methods | `not_comparable` | Preserved |

## Tests

- `EvidenceConflictContextAssessorTest`
- `EvidenceConflictDetectorContextTest`
- `ScientificSynthesisEvidenceIntegrationContractTest`
