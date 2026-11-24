# 08 — Quality Benchmark Results

Scoring: PASS / PARTIAL / FAIL / NOT_ASSESSABLE per dimension. No single numeric average.

## EN_DEFICIT_IRRIGATION (Live)

| Dimension | Score |
|---|---|
| Scientific Relevance | PASS |
| Completeness | PARTIAL |
| Claim Accuracy | PASS |
| Evidence Support | PASS |
| Citation Integrity | PASS |
| Context Preservation | PASS |
| Conflict Handling | PASS |
| Causal Reasoning | PASS |
| Uncertainty Calibration | PASS |
| Multilingual Fidelity | PASS (EN) |
| Readability | PASS |
| Practical Usefulness | PASS |

## AR_DEFICIT_IRRIGATION (Live)

| Dimension | Score |
|---|---|
| Scientific Relevance | PARTIAL |
| Completeness | PARTIAL |
| Claim Accuracy | PARTIAL |
| Evidence Support | PASS |
| Citation Integrity | PASS |
| Context Preservation | PARTIAL |
| Conflict Handling | PASS |
| Causal Reasoning | PASS |
| Uncertainty Calibration | PASS |
| Multilingual Fidelity | PARTIAL |
| Readability | PARTIAL |
| Practical Usefulness | PARTIAL |

## TR_CWR / FR_CWR (Live)

Most dimensions **PARTIAL**; Completeness and Practical Usefulness lean weak (treatment lists vs quantified requirement). Multilingual Fidelity PARTIAL (sense preserved; answer substance EN excerpts).

## EN_INSUFFICIENT (Martian)

Relevance/Honesty: PASS (insufficient). Other content dimensions NOT_ASSESSABLE / N/A by design.

## Fixture / Unit gates

LiveQualityGate + ExplanatoryFragment + Irrigation matrix: PASS (contract/fixture evidence class).

## Mandatory rule application

No case with FAIL on Claim Accuracy / Evidence Support / Citation Integrity was graded overall PASS for LIVE_ANSWER_VERIFIED. EN deficit is the strongest live case; multilingual LIVE_ANSWER remains PARTIAL → drives WITH_LIMITATIONS classification.
