# 09 — Integration and Regression Results

## Commands

```text
docker compose exec -T backend php artisan test --filter="IrrigationGeneralitySemanticMatrixTest|ArabicScientificSearchRemediationContractTest|CompositionalAgriculturalSemanticsTest|GenericIrrigationQuantitySearchArchitectureTest"
```

## Result

**Tests: 63 passed (456 assertions)**

| Suite | Classification |
|---|---|
| IrrigationGeneralitySemanticMatrixTest | INTRODUCED (mission) — PASS |
| ArabicScientificSearchRemediationContractTest | RELATED — PASS |
| CompositionalAgriculturalSemanticsTest | REGRESSION — PASS |
| GenericIrrigationQuantitySearchArchitectureTest | REGRESSION — PASS |

## Performance / reliability

- No LocalIndex rebuild.
- No new network calls in contract path.
- Variant budgets unchanged (`MAX_VARIANTS`).

## Attribution

No INTRODUCED_REGRESSION observed in the focused suites above.
