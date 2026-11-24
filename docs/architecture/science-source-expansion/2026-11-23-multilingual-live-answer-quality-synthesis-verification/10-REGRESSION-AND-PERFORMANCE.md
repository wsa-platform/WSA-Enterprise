# 10 — Regression and Performance

## Regression command (mission gate)

```text
docker compose exec -T backend php artisan test --filter="ScientificSynthesisEvidenceIntegrationContractTest|ComposerConflictExpressionContractTest|IrrigationGeneralitySemanticMatrixTest|ClaimAttributionLedgerTest|AnswerComposerLiveQualityGateTest|AnswerComposerExplanatoryFragmentContractTest"
```

**Result:** 55 passed (250 assertions). Duration ~52.5s.

### Coverage implied

- Scientific synthesis + contextual conflict expression
- Irrigation generality EN/AR/TR/FR semantic matrix
- Claim attribution ledger
- Live quality gates + explanatory fragment contracts

## Performance (live probe observations)

| Case | elapsed_ms |
|---|---|
| EN_DEFICIT | 18094 |
| AR_DEFICIT | 8784 |
| TR_CWR | 9136 |
| FR_CWR | 8824 |
| EN_CONFLICT_CONTEXT | 6114 |
| EN_SALINITY | 8838 |
| AR_DISEASE | 11475 |
| EN_MARTIAN | 26147 |

No performance optimization that weakens evidence gates was applied. Martian latency higher (retrieval attempted before domain insufficient). No memory profiling instrumentation in this mission → Performance gate = PARTIAL (latency sampled only).
