# 6.5 — Risks, Dependencies, Parallelism

## Legal

| Risk | Handling |
|---|---|
| About-page CC BY ≠ record rights (REMVT) | Reject G2-17; require Crossref `license.URL` containing `by/4.0` and excluding `by-nc` |
| Full-text reuse beyond metadata | Metadata + citation linking PERMITTED; FULL_TEXT CONDITIONAL — harvest stores Crossref metadata/abstracts only |

## Technical

| Risk | Handling |
|---|---|
| Sqlite FTS5 `SQLSTATE[HY000]` on Windows Docker bind-mount | Durable rows still written; memory LocalScientificIndex proves searchability; FTS rebuild deferred to Linux/CI volume |
| Stage-3 early-stop on OA/CR/S2 | Eligibility scopes osmia to bee/pollinator cues; after FTS warm, force-source / protected-source path for verification |
| Evidence validation rejects abstracts | Attribution gate remains open; tune retain/validation only after FTS live path works |

## Scientific

| Risk | Handling |
|---|---|
| Over-broad activation pollutes non-bee answers | `GovernedScientificSourceEligibility::osmiaEligible` cue + beekeeping domain gate |
| French-dominant corpus | Language catalog registers LocalIndex multilingual path; probe queries include FR (`abeille`) and genus (`Nomada`) |

## Parallel vs sequential

| Workstream | Parallelizable? | Depends on |
|---|---|---|
| A Contract | No | Selection gate |
| B Harvest | After A | Crossref free API |
| C Normalize | Parallel with late B | Snapshot schema |
| D Persist/FTS | After B | Store path; FTS may fail independently of store |
| E Retrieval wiring | Parallel with B/C | Catalog + eligibility |
| F Attribution | After D+E with searchable index | Validated hits |
| G Regression | Parallel after E unit wiring | Existing suites |
| H Activation flag | After A legal PASS | NearReady + CapVer |

**Next candidate rule:** FTS HY000 is environment-wide, not OSMIA-specific — switching seats would not clear the durable-index gate. REMVT/Andalasian remain BLOCKED_LICENSE. Stay on G3-05 until FTS+attribution close, then re-evaluate.
