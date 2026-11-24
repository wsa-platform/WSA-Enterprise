# 04 — Context and Conflict Assessment

## Contract Layer

| Test | Result |
|---|---|
| `ScientificSynthesisEvidenceIntegrationContractTest` | PASS |
| `ComposerConflictExpressionContractTest` | PASS |

Labels preserved from prior mission:
- `genuine_conflict`
- `resolved_by_context`
- `not_comparable`
- `insufficient_information`

## Live Probe Observations

| Case | contextual_differences | genuine_conflicts | Notes in answer language |
|---|---|---|---|
| EN_DEFICIT | 5 | 0 | Contextual differences explained; no genuine conflict claimed |
| AR_DEFICIT | 6 | 2 | Both contextual note and genuine-conflict note emitted |
| TR_CWR | 7 | 1 | Mixed notes TR |
| FR_CWR | 10 | 1 | Mixed notes FR |
| EN_CONFLICT_CONTEXT | 7 | 0 | Context-heavy; arid vs humid not fully answered |
| EN_SALINITY | 6 | 0 | Contextual differences noted |

## Assessment

- System does **not** treat every difference as genuine conflict (EN deficit: 0 genuine).
- Genuine conflicts are surfaced when assessor classifies them (AR/TR/FR cases).
- Uncertainty strings remain calibrated (“secondary sources disagree…” / multilingual equivalents).
- Limitation: live conflict counts depend on retrieved corpus; not a full designed 5-scenario fixture matrix re-run beyond prior synthesis contracts.
