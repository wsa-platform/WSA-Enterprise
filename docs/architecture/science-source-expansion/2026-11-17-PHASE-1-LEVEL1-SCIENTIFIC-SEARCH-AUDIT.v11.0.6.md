# Level-1 Scientific Search Audit — Rev 11.0.6

**EXIT:** EXIT-09 (CSQ/identity), EXIT-08 (routing/adapters)  
**Baseline HEAD:** `5a643f3`  
**Verdict:** `VERIFIED_WITH_LIMITATIONS` (core path regressions PASS; full live re-probe not re-run)

## Scope

CSQ identity preservation, source routing invocation, adapter resolve, basic evidence path integrity.

## Evidence

| Suite | Result | Artifact |
|---|---|---|
| Prior Level-1 CSQ/routing/evidence regression | 15 passed / 74 assertions | `_tmp-r1106-l1-regression.txt` |
| Governed ACTIVE selection + Egypt CSQ geography | 2 passed / 25 assertions | `GovernedSourceRuntimeActivationContractTest.php` |

## Findings

1. Prior Level-1 repairs (geography, Cucurbitaceae, knowledge-option echo) still hold under regression.
2. Five governed ACTIVE seats are registered, selected, and adapter-resolvable.
3. No fixable missing router binding for authorized ACTIVE seats.
4. `SCIENTIFIC_INTEGRATION_VERIFIED` remains 0 (§8.1).

## Remediation this package

- Added `GovernedSourceRuntimeActivationContractTest` locking ACTIVE governed keys + Egypt geography variants.
- Activation probe corrected to report `$seat->blockers` separately from contract blockers.

## Residual risk

Live connectivity not re-probed in 11.0.6; historical EXIT-08 live evidence retained.
