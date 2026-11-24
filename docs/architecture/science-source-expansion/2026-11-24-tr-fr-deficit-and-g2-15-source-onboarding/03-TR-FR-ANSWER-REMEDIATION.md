# 03 — TR/FR Answer Remediation

## Code changes

| File | Change |
|---|---|
| `AgriculturalEntityCatalog.php` | Multi-word TR possessive causal spans; stronger TR/FR deficit morphology cues |
| `RetrievalSpecification.php` | Project `original_question` / `normalized_question` (+ snake `scientific_sense`) into context |
| `ScientificQueryCompiler.php` | Mirror ask-surface into identity context |
| `ScientificSearchQueryBuilder.php` | Apply English scholarly fallback **after** concept compile; map TR/FR yield targets → `yield` |
| `AnswerComposer.php` | MAD/(DI) deficit findings acceptance; skip chrome re-wrap for Latin limited chrome |
| `TrFrDeficitIrrigationRecoveryTest.php` | Possessive CSQ, concept map, executable process, MAD selection |

## Tests

```text
docker compose exec -T backend php artisan test --filter=TrFrDeficitIrrigationRecoveryTest
→ 5 passed (22 assertions)

docker compose exec -T backend php artisan test --filter="TrFrDeficitIrrigationRecoveryTest|IrrigationGeneralitySemanticMatrixTest|ClaimAttributionLedgerTest"
→ 43 passed (207 assertions)
```

## Live

```text
php storage/app/e2e-tmp/_tr_fr_deficit_live_probe.php
→ EN/AR/TR/FR deficit SUBSTANTIVELY_COMPLETE; EN-CWR SUBSTANTIVELY_COMPLETE
```
