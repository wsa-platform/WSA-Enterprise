# EXIT-01 → EXIT-11 Definition of Done — Rev 10.9.6

**Classification:** evidence-derived; does not rewrite original acceptance text.  
**Sources:** ADR-023 §8.64.7 (not sole authority) + §8.21.4 / §8.24.9 / §8.25.10–11 / §8.26.7 / §8.63.10; `2026-11-17-PHASE-1-EXIT-08-10-ACCEPTANCE-EXTRACTION.v10.9.6.json`; rollup v10.27; this execution.

| EXIT | Original requirement (faithful extract) | Source | Mandatory subcriteria | PASS condition | Current result | Status |
|---|---|---|---|---|---|---|
| EXIT-01 | Foundation decisions / ordering complete for Phase 1 | §8.21.4; §8.24.9; §8.63.10 | Recorded foundation decisions; undecided items classified | All mandatory foundation items decided or classified | Historical open ordering/Gates | PARTIAL |
| EXIT-02 | Gate A implementation design acceptance per scope | §8.63.10; §8.24.9 | Gate A records per authorized scopes | Formal Gate A set complete for required scopes | Incomplete formal Gate A set | PARTIAL |
| EXIT-03 | Gate B implementation authorization | §8.24.9; §8.25.11 | Explicit Gate B for implemented scopes | No unauthorized scope implementation | Residual unauthorized scopes historically noted | PARTIAL |
| EXIT-04 | IC Persistence Integrity closure | §8.24.9; IC registers | Required IC persistence criteria evidenced | All mandatory IC persistence criteria PASS | Prior rollup PASS retained; not re-opened this execution | PASS |
| EXIT-05 | Staging/operator external evidence | §8.24.9; operator evidence contract | External operator/staging artifacts in required format | Operator evidence present and validated | Missing external operator evidence | BLOCKED |
| EXIT-06 | Runtime Source Registry + Capability contracts | §8.25.11; §8.24.9 item 3 | Catalog + capability wiring; identity bindings | Registry/capability contracts verified | Catalog contracts exist; residual seats | PARTIAL |
| EXIT-07 | Seat review OD-07/OD-10; G2-26 | §8.24.9 item 4; OD-10 | Original IC evidence for G2-26 | Original IC evidence satisfied | Original G2-26 IC evidence missing | BLOCKED |
| EXIT-08 | Adapters only for verified access families, one per family with per-seat constraints; Stage-3 reuse only with identity binding; no speculative adapters; Builder owns query (D-04) | ADR-023 §8.64.7 P1-EXIT-08; §8.24.5; EXIT-08 original-contract analysis v10.14 | Authorized families (latest approved: RestJson, OAI-PMH, Bulk/LocalIndex); family evidence; Gate B; no invented source-count denominator | Every authorized family has endpoint_tested VERIFIED and integration_verified VERIFIED | Families partially evidenced; AGRIS BY0 advanced; Stage-3 S2S/Consensus/FAOSTAT residuals outside RestJson governed seats | PARTIAL |
| EXIT-09 | Focused SQLite tests; isolated PostgreSQL guarded suite incl. §8.23.8 Cap CURRENT concurrency rule as interpreted by OD-8.23.8-A; five-source / CSQ / Home-Crop regression; recorded commands/exit codes/counts | §8.64.7; §8.24.9 item 7; §8.23.8; OD-EXIT09-LINUX-CONTAINERS-2026-11-14; OD-8.23.8-A | Linux-container target scenarios; CapVer-SQLite CURRENT satisfied; IC/SRS isolated PG; ServerRestart [2,1]; five-source/CSQ/Home-Crop green | Every mandatory Linux-container scenario PASS with recorded evidence | Linux ServerRestart [2,1] evidenced; Cap CURRENT N/A-on-PG with CapVer-SQLite satisfied; five-source/CSQ/Home-Crop residual open | PARTIAL |
| EXIT-10 | Matrix updated from evidence only; rows in register order; every state cites an artifact; register unchanged | §8.64.7 P1-EXIT-10; OD-10 (110); 109.v1 preserved | (1) evidence-only updates (2) 110 rows in register order (3) every state cites artifact (4) register unchanged — plus separate status dimensions | Matrix-to-register comparison PASS and every state evidence-backed | Membership identity contract green (1 test / 6 assertions); authentic per-seat citations incomplete | PARTIAL |
| EXIT-11 | Phase 1 verification report appended to ADR re-verifying EXIT-01…10, then explicit exit decision | §8.64.7; EXIT-11 gate evaluation v10.8.3 | Every EXIT-01…10 PASS; D-01…D-14 satisfied; archive/evidence integrity; no mandatory blocker | Prerequisites met + report + exit decision recorded | Prerequisites unmet → not run | NOT_RUN |

## EXIT-08 mandatory source set (ambiguity resolution)

- **Original text basis:** adapters for *verified access families*, not “five sources” and not “all 110 seats”.
- **Latest approved family list:** RestJson, OAI-PMH, Bulk/LocalIndex (`EXIT-08-FAMILY-VERIFICATION-MATRIX.v10.14.json`).
- **Selected interpretation:** family-based denominator; PASS requires verified-family evidence per authorized family.
- **Rejected:** treating current enabled inventory, Stage-3 five, or 110.v1 membership as the EXIT-08 PASS denominator.
- **Note:** Master Prompt 10.27 §5.3 five E2E sources (GBIF/BHL/Duke/OE/AGRIS) are a later scope cluster, not the EXIT-08 denominator.

## EXIT-09 OS matrix (contract-driven)

**Selected OS interpretation:** Linux containers only (`OD-EXIT09-LINUX-CONTAINERS-2026-11-14`). Native Windows is **not** required absent an approved support contract. Linux evidence must not be relabeled as Native WSL/Windows.

| Scenario | Linux containers | Native Windows | Required by original contract? | Evidence | Status |
|---|---|---|---|---|---|
| Application process restart / ServerRestart | Required target | Not required | Yes on Linux containers; expected generations [2,1] | ServerRestart Linux comparison v10.16 | PARTIAL (regression suite residual elsewhere) |
| PostgreSQL restart (ServerRestart path) | Required target | Not required | Yes on Linux containers | v10.16 write/verify PASS | Evidenced for ServerRestart path |
| Isolated PostgreSQL IC/SRS suites | Required | Not required | Yes (§8.24.9 / guarded suite) | Historical committed IC/SRS results | PARTIAL |
| Cap CURRENT concurrency | CapVer-SQLite satisfies; Cap-on-PG N/A with OD-8.23.8-A | N/A | §8.23.8 via OD-8.23.8-A | CapabilityStorePersistenceTest historical PASS | Satisfied via approved alternative |
| SQLite LocalIndex/CapVer persistence | By design | Same architecture | Yes for those stores | BY0 persist_completed this execution (LocalIndex) | PARTIAL (store-specific; FTS deferred) |
| SQLite WAL / PRAGMA integrity | Not PRAGMA-probed (owner boundary) | Same | Not executed under overlay | Boundary: no PRAGMA this execution | NOT_RUN / BOUNDARY |
| Five-source / CSQ / Home-Crop regression | Required | Not required | Yes (§8.64.7 EXIT-09) | Residual historically open | PARTIAL |
| Test DB isolation / guard | Isolated postgres harness | N/A for that harness | Yes for PG tests | Historical isolation design | PARTIAL |

## EXIT-10 original subcriteria

The original §8.64.7 text has **no** named “verified-family subcriterion list”. Mandatory original clauses:

1. Matrix updated from evidence only.
2. Rows in register order (110 after OD-10; 109.v1 preserved).
3. Every state backed by a cited artifact.
4. The register unchanged.

Do not treat the “1/6” PHPUnit metric as “1 of 6 acceptance criteria”. Per-seat verified-family columns remain evidence dimensions under clause 1 and EXIT-06/08 dependencies; overall EXIT-10 stays PARTIAL until every mandatory clause is evidenced.

## EXIT-11 gate

`every_dependency_must_be_pass: true`; `partial_permitted_for_gate_run: false`. Do not run while any of EXIT-01…EXIT-10 is not PASS, or while mandatory D-register / archive conditions fail. Current: NOT_RUN; `formal_gate_eligible=false`.
