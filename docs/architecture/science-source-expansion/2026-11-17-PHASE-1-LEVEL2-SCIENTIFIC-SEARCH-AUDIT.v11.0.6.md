# Level-2 Scientific Search Audit — Rev 11.0.6

**EXIT:** EXIT-09 / EXIT-10  
**Baseline HEAD:** `5a643f3`  
**Verdict:** `PARTIAL` (composer Outcome B PASS; full provenance/synthesis matrix not exhausted)

## Scope

AnswerComposer, Outcome B citation gate, provenance/synthesis integrity overlapping Level-1.

## Evidence

| Suite | Result | Artifact |
|---|---|---|
| `OutcomeBIndependentCitationProvidersTest` | 4 passed / 17 assertions | `_tmp-r1106-l2-composer.txt` |

## Findings

1. SUPPORTING-only evidence does not enter primary `citations[]` (Outcome B preserved).
2. Independent DIRECT providers appear as separate citation contributors.
3. Broader provenance/normalization/dedup/synthesis coverage remains PARTIAL pending Level-3 seat-specific §8.1 packages.
4. Wrong prior test path (`Research/OutcomeB...`) corrected to `Research/Synthesis/OutcomeB...`.

## Remediation this package

- Corrected Level-2 composer verification path and re-recorded PASS evidence.
- Documented ACTIVE vs answer-traceability separation in Option B delta.

## Residual risk

Per-source answer traceability for all 110 seats is NOT claimed. Five ACTIVE seats remain `PARTIALLY_VERIFIED`.
