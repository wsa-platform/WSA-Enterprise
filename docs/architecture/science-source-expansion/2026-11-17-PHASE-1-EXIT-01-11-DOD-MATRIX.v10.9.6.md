# EXIT-01 → EXIT-11 Definition of Done — Rev 10.9.6

**Classification:** evidence-derived; does not rewrite original acceptance text.  
**Sources:** ADR-023 §8.24.9 / §8.64.7 / §8.23.8; EXIT original-contract analyses; rollup v10.27; this execution.

| EXIT | Original requirement (faithful extract) | Source | Mandatory subcriteria | PASS condition | Current result | Status |
|---|---|---|---|---|---|---|
| EXIT-01 | Foundation decisions / ordering complete for Phase 1 | §8.21.4; §8.24.9; §8.63.10 | Recorded foundation decisions; undecided items classified | All mandatory foundation items decided or classified | Historical open ordering/Gates | PARTIAL |
| EXIT-02 | Gate A implementation design acceptance per scope | §8.63.10; §8.24.9 | Gate A records per authorized scopes | Formal Gate A set complete for required scopes | Incomplete formal Gate A set | PARTIAL |
| EXIT-03 | Gate B implementation authorization | §8.24.9; §8.25.11 | Explicit Gate B for implemented scopes | No unauthorized scope implementation | Residual unauthorized scopes historically noted | PARTIAL |
| EXIT-04 | IC Persistence Integrity closure | §8.24.9; IC registers | Required IC persistence criteria evidenced | All mandatory IC persistence criteria PASS | Prior rollup PASS retained; not re-opened this execution | PASS |
| EXIT-05 | Staging/operator external evidence | §8.24.9; operator evidence contract | External operator/staging artifacts in required format | Operator evidence present and validated | Missing external operator evidence | BLOCKED |
| EXIT-06 | Runtime Source Registry + Capability contracts | §8.25.11; §8.24.9 item 3 | Catalog + capability wiring; identity bindings | Registry/capability contracts verified | Catalog contracts exist; residual seats | PARTIAL |
| EXIT-07 | Seat review OD-07/OD-10; G2-26 | §8.24.9 item 4; OD-10 | Original IC evidence for G2-26 | Original IC evidence satisfied | Original G2-26 IC evidence missing | BLOCKED |
| EXIT-08 | Adapters only for verified access families; one per family; Stage-3 reuse only with identity binding; no speculative adapters; Builder owns query (D-04) | ADR-023 §8.64.7 P1-EXIT-08; §8.24.5; `EXIT-08-ORIGINAL-CONTRACT-ANALYSIS.v10.14.json` | Verified access families (not arbitrary N; not all 110); family evidence; Gate B adapters; live/search where required | Every required family dimension evidenced | Families partially evidenced; AGRIS BY0 advanced; S2S/Consensus/FAOSTAT residuals | PARTIAL |
| EXIT-09 | DB tests, five-source regression, ServerRestart, OS per §8.23.8 / OD-8.23.8-A | §8.24.9 item 7; §8.23.8 | Process restart; PG restart; SQLite persist; WAL; ServerRestart; isolation; guard — per original OS contract | Every mandatory scenario/environment PASS | Linux evidence historically stronger; OS_SUPPORT residual; WIP PG suite not mixed into acceptance | PARTIAL |
| EXIT-10 | Verified-family acceptance columns (not membership alone) | §8.24.9; EXIT-10 verified-family records | Per-family endpoint/integration/readiness columns | Every mandatory subcriterion PASS | Rest/OAI/Bulk partially verified; readiness incomplete | PARTIAL |
| EXIT-11 | Formal Phase 1 exit gate | §8.63.10; EXIT-11 preflight | EXIT-01…EXIT-10 PASS (verify against original gate wording) | Prerequisites PASS then gate executed | Prerequisites unmet → not run | NOT_RUN |

## EXIT-08 mandatory source set (ambiguity resolution)

- **Original text basis:** adapters for *verified access families*, not “five sources” and not “all 110 seats”.
- **Selected interpretation:** family-based denominator from `2026-11-12-PHASE-1-EXIT-08-ORIGINAL-CONTRACT-ANALYSIS.v10.14.json`.
- **Rejected:** treating current enabled inventory or 110.v1 membership as the EXIT-08 PASS denominator.

## EXIT-09 OS matrix (contract-driven)

| Scenario | Linux | Windows | Required by original contract? | Evidence | Status |
|---|---|---|---|---|---|
| Application process restart | Evidenced historically | Not equated from Linux | Extract §8.23.8 / OD-8.23.8-A | ServerRestart Linux comparison v10.16 | PARTIAL |
| PostgreSQL restart | Isolated PG test path exists | Not proven by Linux alone | Extract | Committed IC PG suites historical | PARTIAL |
| SQLite persistence | LocalIndex/CapVer by design | Same architecture | Yes for SQLite stores | LocalIndex BY0 persist_completed this execution | PARTIAL (store-specific) |
| SQLite WAL recovery | Not PRAGMA-probed (owner boundary) | Same | If mandatory under §8.23.8 | Boundary: no PRAGMA this execution | NOT_RUN / BOUNDARY |
| ServerRestart regression | Linux [2,1] retained | Historical residual | Yes per OD | v10.16 evidence | PARTIAL |
| Test DB isolation | Isolated postgres:16-alpine path | N/A for that harness | Yes for PG tests | Historical isolation design | PARTIAL |

## EXIT-10 subcriteria note

Do not reduce to a family count. Retain per-seat/per-family columns from EXIT-10 verified-family matrices; overall remains PARTIAL until every mandatory column is evidenced.

## EXIT-11 gate

Do not run while any of EXIT-01…EXIT-10 is not PASS. Current: NOT_RUN.
