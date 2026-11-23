# 06 — Semantic Regression Matrix

**Test:** `Tests\Unit\Agriculture\Research\IrrigationGeneralitySemanticMatrixTest`  
**Result:** PASS (contract level)

## Counts

| Polarity | Count | Status |
|---|---|---|
| Positive (expect CWR) | 12 | PASS |
| Negative (forbid CWR) | 18 | PASS |

## Language × family coverage

| Family | EN | AR | TR | FR |
|---|---|---|---|---|
| Explicit / deficit intervention | PASS | PASS | PASS | PARTIALLY_VERIFIED* |
| Crop water requirement | PASS | PASS | PASS | PASS |
| Irrigation quantity | PASS | PASS | — | — |
| Irrigation scheduling | PASS | PASS | — | PASS |
| Environmental constraint | PASS | — | PASS | PASS |
| Disease + incidental irrigation | PASS | — | — | — |
| Salinity physiology | PASS | PASS | — | — |
| Varieties / suitability | PASS | PASS | — | — |
| Extension / adoption | PASS | — | — | — |
| Irrigation systems / technology | PASS | — | — | — |
| Fertilizer / seed quantity | PASS | — | — | — |
| Organic / soil fertility | — | PASS | — | — |

\* FR deficit covered via scheduling/CWR positives; dedicated FR deficit phrase unit-covered in helper signals.

`—` = no dedicated fixture in this matrix (not claimed PASS).

## Asserted dimensions (where applicable)

- Helper result, research intent, scientific sense, crop id, water role, executable variants, Arabic leakage absence, retrieval concept presence/absence.
