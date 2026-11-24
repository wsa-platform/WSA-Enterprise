# 07 — Regression Results

```text
docker compose exec -T backend php artisan test --filter="AnswerComposerLiveQualityGateTest|AnswerComposerExplanatoryFragmentContractTest|ScientificSynthesisEvidenceIntegrationContractTest|ClaimAttributionLedgerTest"
→ 18 passed (54 assertions)

docker compose exec -T backend php artisan test --filter="IrrigationGeneralitySemanticMatrixTest"
→ 33 passed (173 assertions)

npm test -- --run src/public/stableResearchPresentation.test.ts
→ 4 passed

npx playwright test tests/live-scientific-answer-ui.spec.ts
→ 1 passed (live)
```

No intentional weakening of evidence gates. Irrigation generality matrix green.
