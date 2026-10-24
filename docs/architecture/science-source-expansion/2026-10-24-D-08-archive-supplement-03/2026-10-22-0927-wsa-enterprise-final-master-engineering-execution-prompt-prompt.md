<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17820). Message timestamp as recorded in the conversation: Thursday, Oct 22, 2026, 9:27 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17821; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER ENGINEERING EXECUTION PROMPT
## Five-Source Scientific Integration — Autonomous Implementation, Architecture Integrity, Database Safety, Verification, and Closure
**Project:** WSA-Enterprise  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local path:** `G:\WSA-Enterprise`  
**Target branch:** `phase-18-m18-ai-marketing-communications`  
**Last reported HEAD:** `fb60b60746ed487e2bfa04e3d335827d91cda446`  
**Primary ADR:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Report language:** Arabic  
**Execution mode:** Autonomous engineering execution. Inspect, implement, test, repair, verify, document, and deliver.
---
# 1. MISSION AND ENGINEERING ROLE
Act as the senior software engineer, software architect, scientific search engineer, data integrity engineer, QA engineer, and integration engineer responsible for completing the five-source scientific integration.
Your mission is to complete as much of the authorized implementation as possible, verify it against the current repository and approved architectural decisions, repair confirmed defects, and deliver an evidence-based closure report.
**Do not respond with a plan alone. Execute the work.**
You are authorized to:
- Inspect the repository, Git history, ADRs, owner decisions, source catalogs, registries, adapters, orchestrators, query builders, compilers, normalizers, persistence layers, commands, configuration, and tests.
- Modify source code, tests, configuration documentation, operational runbooks, and implementation reports within the approved scope.
- Repair verified defects when the intended behavior is established by an approved ADR, an existing owner decision, or an unambiguous current contract.
- Add regression tests, improve test isolation, and correct unsafe fixtures.
- Run tests through a permitted and verified execution environment.
- Perform bounded, rate-limited live retrieval probes when network access and credentials are available.
- Inspect current source metadata and data counts using safe, isolated methods.
- Execute the explicitly authorized, bounded CapVer operations defined in Section 8, after verifying the exact target and write scope.
- Correct stale documentation when current evidence supports the correction.
- Continue independent work when a separate task is blocked.
- Make normal engineering decisions without repeatedly asking the owner to approve every routine implementation step.
You must not invent approvals, test results, data counts, credentials, successful requests, or commits.
## 1.1 Completion states
Assign one of the following states to each acceptance requirement:
- `PASS — VERIFIED`
- `PASS — VERIFIED WITH BOUNDED SCOPE`
- `BLOCKED — EXTERNAL DEPENDENCY`
- `BLOCKED — OWNER DECISION`
- `FAIL — REQUIREMENT NOT SATISFIED`
- `NOT VERIFIED — EVIDENCE MISSING`
- `SKIPPED — EXTERNAL`
A skipped external test does not count as a pass.
A source is not operational merely because it is registered, has an adapter, is enabled by default, or passed a historical test.
Do not declare the whole task complete while a required acceptance criterion remains unverified.
---
# 2. MANDATORY BASELINE RECONCILIATION
No test, probe, capability write, or data-mutating command may run until the relevant baseline and safety gates have been checked.
## 2.1 Inspect the current repository
Record the actual:
- Repository root.
- Branch and HEAD.
- Last ten commits.
- Upstream tracking state.
- Local/remote divergence, if available.
- Staged, unstaged, and untracked files.
- Relevant ignored files.
- Active Git operations and conflicts.
- Registered worktrees and their branches/HEADs.
- Existing source-expansion changes.
Use equivalent read-only commands:
```text
git rev-parse --show-toplevel
git rev-parse HEAD
git log --oneline -10
git branch -vv
git status --porcelain=v2
git worktree list --porcelain
git stash list
git rev-list --left-right --count HEAD...@{upstream}
```
Adapt the commands to the current shell. If no upstream exists, report that fact rather than assuming synchronization.
Do not apply, pop, drop, or delete any Git stash.
Do not assume the reported HEAD remains current. Record the actual output before doing any work.
## 2.2 Reconcile historical HEADs
Two previous reports identified:
- LocalIndex report: `f0ac897`
- Five-source report: `fb60b60746ed487e2bfa04e3d335827d91cda446`
Determine from actual Git history whether the five-source HEAD descends from the LocalIndex HEAD.
If both commits are available:
1. Establish their ancestry.
2. Identify the intervening commits.
3. Inspect changes affecting database isolation, LocalIndex, test bootstrap, safety guards, and source integration.
4. Verify that no later change weakened or bypassed the safety infrastructure.
If the relationship cannot be established, report `BASELINE DIVERGENCE`.
When baseline divergence is unresolved, do not run tests, probes, or modifications that depend on the disputed baseline. Continue only independent read-only work that is safe and does not depend on the disputed code.
If the divergence remains unresolved and prevents a trustworthy final acceptance decision, the overall result must not be `COMPLETED AND VERIFIED`. If an owner decision is required to resolve it, the final status must be `BLOCKED BY OWNER DECISION`; otherwise report the exact external repository blocker.
## 2.3 Protect all existing work
The previous report described 145 porcelain entries, comprising 42 tracked modified files and 103 untracked files. These are historical counts, not current facts.
Recalculate the actual state and preserve existing work.
Never:
- Run `git reset --hard`.
- Run `git clean`.
- Discard user modifications.
- Overwrite untracked files.
- Apply or drop existing stashes.
- Force-push or rewrite history.
- Delete other worktrees.
- Stage unrelated files.
- Assume untracked files are disposable.
Other previously reported worktrees include:
- `G:\WSA-Enterprise-adr023-reconcile`
- `G:\WSA-Enterprise-archive-reconcile`
- `G:\WSA-Enterprise-push-isolated`
- Worktrees under `G:\tmp` and `.kilo`
Verify what actually exists. Inspect other worktrees read-only. Never modify, reset, clean, or remove them during this task.
---
# 3. THE FIVE SOURCES IN SCOPE
| Source | Key | Seat | Group | Expected integration family |
|---|---|---|---|---|
| GBIF | `gbif` | `G5-04` | G5 | REST API |
| Organic Eprints | `organic_eprints` | `G1-02` | G1 | OAI-PMH + LocalIndex |
| BHL | `bhl` | `G6-20` | G6 | REST API |
| FAO AGRIS | `agris` | `G1-01` | G1 | ODS/bulk + LocalIndex |
| Dr. Duke's Databases | `dr_duke` | `G1-17` | G1 | Static dataset + LocalIndex |
Verify each seat and identity against the current membership register, source catalog, ADR-023, and owner decisions.
Membership `CURRENT` does not prove operational activation.
Do not merge these identities with older sources:
- `openalex`
- `crossref`
- `semantic_scholar`
- `consensus`
- `fao_stat`
Do not activate or merge the deferred seat `G6-06` into this task.
---
# 4. MANDATORY OPERATIONAL SQLITE PROTECTION
The previously reported operational scientific store is:
`G:\WSA-Enterprise\backend\storage\app\scientific_record_store.sqlite`
Confirm the actual configured path through source inspection without opening the operational database. If the actual path differs, document it and apply equivalent protections to the real operational file.
## 4.1 Forbidden direct access
For this task, do not open the operational scientific SQLite file using:
- SQLite CLI.
- PDO.
- Laravel DB facade.
- Database-aware inspection tools.
- FTS queries.
- Migrations.
- Ingestion or projection commands.
- Tests or bootstrap code that may resolve the operational path.
Do not open it merely to count rows or inspect schema.
The purpose is to prevent unexpected SQLite initialization, `-wal`/`-shm` creation, locking, index rebuilding, and unintended data or metadata changes.
## 4.2 Filesystem-only baseline
Before any test, probe, or command that might resolve the operational path, record filesystem-only metadata using tools such as PowerShell `Get-Item` and `Get-FileHash`.
Record:
- File existence.
- File size.
- Last-write timestamp.
- SHA-256 hash.
- Presence, size, and timestamps of adjacent `-wal` and `-shm` files.
Repeat the measurements after each potentially relevant operation.
Compare the before-and-after states. If file size, last-write timestamp, SHA-256, or sidecar state changes unexpectedly:
1. Stop the affected operation immediately.
2. Do not attempt automatic repair.
3. Do not delete, rename, truncate, restore, or replace the database or its sidecars.
4. Preserve diagnostic evidence.
5. Write an execution checkpoint.
6. Report the discrepancy.
`atime` may change during legitimate filesystem reads. An `atime` change alone is not evidence of unauthorized database modification. Size, last-write timestamp, SHA-256, and the presence/size/timestamps of `-wal` and `-shm` are the primary integrity indicators.
## 4.3 Isolated inspection
Any database-aware count or query must use a verified isolated copy under the approved test area, such as:
`backend/storage/framework/testing/`
Do not use the isolated copy as proof of operational file integrity; use the filesystem-only measurements for that purpose.
Do not create a copy from an actively changing database using an unsafe procedure. If no safe copy can be established, report the blocker and continue independent work.
## 4.4 Prohibited operations
Never delete or rename the operational database or its `-wal` or `-shm` sidecars.
Never run migrations, refreshes, truncations, FTS rebuilds, deletions, or other destructive operations against the operational scientific store.
The scientific record store remains SQLite under the approved architecture. Do not migrate it to PostgreSQL.
The capability store, including `cghia_capability_cell_records`, is a separate concern. Do not confuse its target with the SQLite scientific record store.
---
# 5. MANDATORY TEST-ISOLATION INFRASTRUCTURE
Before running tests touching `scientific_records`, LocalIndex, FTS, ingestion, projection, or the scientific search pipeline, verify the actual safety infrastructure.
## 5.1 Verify `backend/tests/bootstrap.php`
Confirm that the bootstrap:
- Creates the test directory safely and fails clearly if it cannot.
- Sets the required SQLite test driver before application bootstrap can resolve the connection.
- Sets `SCIENTIFIC_RECORD_STORE_URL` to an empty value.
- Sets `SCIENTIFIC_RECORD_STORE_DATABASE` to a unique test database file under the approved testing root.
- Does not silently fall back to the operational database.
- Does not overwrite real environment configuration or secrets.
- Uses a path that cannot collide with another test process or parallel worker.
The previously proposed pattern `scientific_records_<pid>.sqlite` is only a starting point. Verify uniqueness under the actual runner and concurrency model.
## 5.2 Verify `backend/tests/Support/DatabaseSafety.php`
Confirm that the guard:
- Rejects the exact operational path.
- Rejects paths outside the approved test root.
- Normalizes paths before comparison.
- Uses `realpath` or equivalent canonicalization when available.
- Rejects traversal such as `..`.
- Rejects symlink/path tricks that escape the test root.
- Rejects non-empty connection URLs when file-backed SQLite is required.
- Rejects `:memory:` when file-backed durability is required.
- Revalidates the target before deleting test-owned files.
- Limits cleanup to validated test-owned databases and their `-wal`/`-shm` sidecars.
A path is not safe merely because its string begins with the test directory.
## 5.3 Verify `backend/tests/TestCase.php`
Confirm that `setUp()` invokes the guard before any database migration or mutation.
Inspect the actual framework lifecycle. Implement the safest correct order rather than blindly copying an ordering that may conflict with the current framework.
Any test writing to `scientific_records` must revalidate the target after connection purging/reinitialization and before migration, insertion, update, deletion, or index operations.
## 5.4 Unsafe tests
If the guard is missing, bypassed, or unverified:
- Do not run the affected test.
- Identify the precise missing safety condition.
- Repair the guard when clearly within scope.
- Add regression tests for the guard.
- Verify the repaired isolation before running data-mutating tests.
Do not run the entire LocalIndex test directory until each relevant test has been checked for safe isolation.
A previously documented `database is locked` incident involving operational FTS makes this a concrete safety concern.
## 5.5 Test-owned cleanup
Delete SQLite files and sidecars only after proving that:
1. The target is inside the approved test root.
2. The safety guard validates ownership and path.
3. No active connection is using the test file.
4. Cleanup cannot reach operational data.
Never apply test cleanup to the operational database.
---
# 6. TEST RUNNER RECONCILIATION — DOCKER, PHP, AND DEPENDENCIES
Do not assume Docker is permitted or prohibited. Do not assume PHP is installed on the host.
The previous LocalIndex verification reportedly used Docker because PHP was unavailable on the host at that time. That historical fact does not prove Docker is currently permitted, available, or forbidden.
## 6.1 Verify the runner
Before selecting a runner:
1. Inspect the current project constraints and identify the exact source of any Docker restriction.
2. Check whether Docker is available and permitted in the current environment.
3. If Docker is unavailable or prohibited, check whether host PHP and PHPUnit are available.
4. Record the actual runner selected and the evidence supporting its availability.
5. Never use a runner prohibited by current project constraints, even if an earlier task used it successfully.
If no permitted runner exists:
- Do not install PHP or Docker.
- Do not install Composer dependencies.
- Do not alter the host environment to force test execution.
- Classify affected tests as `SKIPPED — EXTERNAL` and record the missing dependency.
- Continue safe source inspection, ADR review, documentation, and other independent work.
- Do not claim full verification or `COMPLETED AND VERIFIED`.
## 6.2 Explicit test invocation
Before each focused test run, verify:
- PHP and PHPUnit are available through the permitted runner.
- The working directory is correct.
- The correct `phpunit.xml` exists.
- The target test file or directory exists.
- The test bootstrap and database guard are verified.
- The target database is isolated.
- The command uses an explicit test path.
Do not invoke PHPUnit with a bare command that could select an unintended default suite.
Do not use PowerShell's automatic `$args` variable as the test path. Use a literal path or a correctly constructed argument array.
Record the equivalent of:
- `TARGET_FILE_OK` or `TARGET_DIR_OK`.
- `PHPUNIT_CONFIG_OK`.
- `TEST_BOOTSTRAP_OK`.
- `DATABASE_GUARD_OK`.
- `ISOLATED_DATABASE_PATH_OK`.
If a mandatory check fails, do not run the test.
## 6.3 Memory limits
Check the actual runtime memory limit before attributing a failure to memory.
If a test demonstrably requires more memory and the permitted runner supports it, use a command-scoped override only. Do not change `php.ini`, `phpunit.xml`, `composer.json`, or global configuration merely for one test.
## 6.4 Dependency installation
Do not run `composer install`, `composer update`, or equivalent dependency installation/update commands automatically.
First inspect the dependency state and lockfile. If dependencies block testing, report the exact issue. Do not modify `composer.lock`, install packages, or change the environment without a separately justified and authorized action.
## 6.5 Test reporting
For each test run, record:
- Exact command.
- Runner and environment.
- Exit code.
- Number of tests and assertions, when available.
- Pass/fail/skip/incomplete/risky/warning counts.
- External dependencies used.
- Database and filesystem side effects, if any.
A skipped test is not a pass. A historical success is not a current result.
---
# 7. PROTECTED FILES AND MINIMAL-CHANGE POLICY
Treat these files as protected because they control runtime behavior, dependencies, credentials, database access, or test isolation:
- `backend/config/database.php`
- `backend/config/agricultural_intelligence.php`
- `.env`
- `.env.example`
- `backend/.env`
- `backend/.env.example`
- `composer.json`
- `composer.lock`
- `phpunit.xml`
- `backend/phpunit.xml`, if present
- `backend/tests/bootstrap.php`
- `backend/tests/TestCase.php`
- `backend/tests/Support/DatabaseSafety.php`
Verify which paths exist in the current repository.
Changes are permitted only when demonstrably necessary, consistent with approved architecture, and supported by tests or direct verification.
Never include real credentials in source code, fixtures, reports, or example configuration.
## 7.1 Minimal-change rule
Prefer the smallest complete, architecturally correct fix. Avoid unrelated refactoring.
Any change exceeding 30 added or modified lines in a production file is permitted, but requires a written rationale in the execution report explaining:
- Why the change is necessary.
- Why a smaller repair is insufficient.
- Which contract it implements.
- Which tests validate it.
- Which other source paths might be affected.
The 30-line threshold is a review requirement, not a reason to avoid a correct repair. Do not split changes merely to evade the threshold.
---
# 8. OWNER AUTHORIZATION AND DECISION BOUNDARIES
The project owner authorizes autonomous implementation and verification within the specific boundaries of this prompt.
Do not request approval for ordinary, reversible, in-scope engineering actions.
## 8.1 Pre-approved engineering actions
### A. Repair existing contracts
**OWNER APPROVAL: GRANTED**
Repair verified defects when the intended behavior is established by an approved ADR, owner decision, or unambiguous current contract.
This includes parsing, error handling, provenance, normalization, source activation, configuration documentation, test isolation, and regression coverage.
### B. Four-seat CapVer verification and bounded persistence
**OWNER APPROVAL: GRANTED WITH MANDATORY AUTHORIZATION AND SCOPE CHECKS**
The owner authorizes bounded capability verification and persistence for:
- `G1-02` — Organic Eprints.
- `G6-20` — BHL.
- `G1-01` — AGRIS.
- `G1-17` — Dr. Duke.
Before any write:
1. Inspect the current ADR-023, relevant owner decisions, capability contracts, and command implementation.
2. Record the exact authorization source, ADR section, decision ID, target connection/table, seat identity, and permitted capability-cell scope.
3. Verify the command's side effects and recovery behavior.
4. Confirm that the target is the separately governed capability store, not the SQLite scientific record store.
5. Confirm that the write is bounded to the four authorized seat records and only the capability cells explicitly permitted by the verified contract.
6. Independently verify persistence after execution.
**Bounded means no other seat, no unapproved capability cell, no unrelated table, and no unrelated data mutation.** Four authorized seats do not imply permission to write every possible capability cell.
If the general authorization in this prompt conflicts with a specific current ADR restriction, or the exact write scope cannot be established, do not execute that write. Mark it `BLOCKED — OWNER DECISION`, identify the missing authorization, and continue independent work.
Do not fabricate capability facts or weaken validation to force a passing result.
### C. GBIF CapVer decision rule
Investigate `G5-04` by reading ADR-023 §8.58.5 and subsequent relevant sections.
- If the current ADR explicitly authorizes capability persistence for `G5-04` and code omits the authorization, repair the code and add a regression test.
- If the current ADR explicitly excludes `G5-04`, preserve the exclusion and report it as conforming within its approved scope.
- If the ADR is silent, ambiguous, or contradictory, do not modify `capVerAuthorizedSeats()`. Report `BLOCKED — OWNER DECISION` with the exact question.
Do not infer authorization from registration or runtime activation.
### D. Source-specific query integration
**OWNER APPROVAL: GRANTED WHEN REQUIRED BY THE EXISTING CONTRACT**
If the approved D-04 contract requires compiled source-specific query consumption and the implementation ignores it, repair the defect and add regression tests.
If D-04 explicitly preserves variant-text-as-search-query behavior, preserve that behavior.
If the contract is ambiguous, do not introduce a new `sourceQuery` contract based on inference alone. Document the exact ambiguity and continue independent work.
### E. Test isolation
**OWNER APPROVAL: GRANTED**
Repair bootstrap, safety guards, isolated test paths, and safe test-owned cleanup when necessary. Do not weaken safety controls.
### F. Source-specific implementation and configuration
**OWNER APPROVAL: GRANTED**
Repair verified adapter, retrieval, parsing, normalization, provenance, activation, licensing, bounded projection, and error-handling defects within the five-source scope.
### G. Documentation
**OWNER APPROVAL: GRANTED**
Create a dated implementation/verification report and correct stale catalog notes when current evidence supports the change. Preserve ADR history and numbering.
## 8.2 Explicitly excluded decisions
The general mandate does not authorize:
1. Expanding AGRIS from its approved bounded subset to all 1,244 providers.
2. Raising Dr. Duke's approved projection caps.
3. Activating or merging the deferred `G6-06` seat.
4. Migrating the scientific record store from SQLite to PostgreSQL.
5. Changing approved licensing policy or assuming unverified permissions.
6. Rotating BHL credentials without a concrete security reason and a verified safe procedure.
7. Changing canonical CSQ semantics or public contracts in contradiction to an approved ADR.
8. Destructive changes to operational data.
9. Modifying, cleaning, or deleting other worktrees.
10. Committing, pushing, force-pushing, or rewriting Git history.
Finish all independent work when an excluded decision is required. Record the precise decision, options, risks, and recommended resolution.
---
# 9. ARCHITECTURAL AND SCIENTIFIC QUERY INTEGRITY
Read ADR-023 §§8.45 and 8.51–8.62, plus all later sections affecting the current implementation. Verify the actual current text and numbering before relying on historical references.
Preserve the approved pipeline:
`Canonical Scientific Question (CSQ) → ScientificSearchQueryBuilder → ScientificQueryCompiler → source-specific query variants → MultiSourceScientificSearchOrchestrator → Source Adapter → normalization → evidence/provenance pipeline`
Inspect the actual code path.
Verify that:
- CSQ remains the authoritative semantic input.
- Source-specific variants derive from the canonical question and approved specification.
- Compilation output is explicit and traceable.
- Adapters receive the representation defined by their contract.
- Original question, compiled representation, source variant, and actual submitted query remain distinguishable.
- One source's variant does not silently alter another source's query.
- Scientific meaning is not degraded into uncontrolled keyword-only retrieval.
- Compilation failures do not silently produce misleading successful results.
## 9.1 D-04 resolution
Prove or disprove the reported mismatch between the compiled query bundle and the actual adapter input.
Apply this decision rule:
- D-04 requires compiled source-query consumption → repair and test.
- D-04 explicitly preserves variant-text-as-search-query → preserve the contract and verify remaining behavior.
- D-04 is silent or ambiguous → do not invent a new contract; document the decision required.
The absence of a `sourceQuery` field alone is not proof of a defect.
## 9.2 Registry and activation
Inspect:
- `ScientificSourceCatalog::productionRegistrations()`
- `NextGateEligibleSeatCatalog`
- `CurrentMembershipNearReadySeatCatalog`
- `ScientificSourceSelector`
- Registry and adapter factory.
- Source eligibility and activation gates.
- Production orchestrator.
Verify one correct registration per source, correct seat/group identity, correct adapter family, and absence of conflicting registrations.
Verify the current equivalent of `isRuntimeActive()`. The previous audit described it as requiring `ACTIVE` status, an active contract, and no blockers; confirm current behavior.
Do not bypass eligibility or blockers to make a source appear active.
---
# 10. RESULT NORMALIZATION, PROVENANCE, AND EVIDENCE
Inspect the actual raw-response-to-result path, including:
- `ScientificSourceFamilyRecord`
- `ScientificSearchResult`
- `ScientificSourceRecordProvenance`
- `ScientificResultNormalizer`
- Related evidence, identity, and display logic.
Verify the contract-required fields:
- Stable source key.
- Stable source-native identifier.
- Source URL or grounded locator.
- Source-native metadata needed to verify the record.
- Retrieval timestamp and ingestion/generation metadata, where applicable.
- License information.
- Relationship to the canonical question and query variant.
- Source identity and evidence relationship.
Do not assume object construction proves correct normalization or downstream preservation.
Repair verified provenance loss, unstable identifiers, invalid URLs, metadata corruption, duplicate collisions, or incorrect attribution.
Preserve the distinction between observed source facts, traditional-use records, hypotheses, and evidence of efficacy. Dr. Duke ETHNOBOT records must not be represented as efficacy proof merely because they describe traditional use.
Do not fabricate scientific conclusions.
---
# 11. CAPVER AUTHORIZATION AND PERSISTENCE
The four-seat authorization scope is limited to `G1-02`, `G6-20`, `G1-01`, and `G1-17`, subject to Section 8.
## 11.1 Authorization provenance
Before running `scientific:capver-accepted`:
1. Locate the exact current ADR section and owner decision for each seat.
2. Verify that the approved contract supports the specific capability facts to be written.
3. Record the target connection, table, seat scope, permitted capability cells, and side effects.
4. Verify that the command cannot write to the operational scientific SQLite store.
5. Confirm idempotency and recovery behavior.
6. Run only when the target and write scope are demonstrably safe.
Previously reported owner decision IDs include:
- `OD-G1-02-ONBOARDING-ACTIVE`
- `OD-G6-20-ONBOARDING-ACTIVE`
- `OD-G1-01-SCOPE-BOUNDED-SUBSET`
- `OD-G1-17-SCOPE-LIMITED-PROJECTION`
These IDs are search leads, not proof that every capability write is authorized.
## 11.2 Ambiguity protocol
If authorization for a seat or capability-cell write cannot be established:
- Do not write capability facts based only on a general desire to finish the project.
- Mark the specific operation `BLOCKED — OWNER DECISION`.
- State which ADR sections were inspected and what authorization is missing.
- Continue with the other authorized seats and all independent work.
## 11.3 Independent verification
For each authorized seat, verify:
- Contract correctness.
- Required capability-cell definitions.
- Current version/freshness.
- Persisted row existence.
- Correct seat identity.
- Absence of stale/conflicting records.
- Actual returned status.
Distinguish `PERSISTED`, `ALREADY_CURRENT`, `STORE_UNAVAILABLE`, and any other actual status.
Do not manufacture evidence or bypass the authorization list.
---
# 12. SOURCE-SPECIFIC REQUIREMENTS
## 12.1 GBIF — `gbif`, `G5-04`
Expected endpoint:
`https://api.gbif.org/v1/species/search`
Required work:
1. Verify current registration, activation, REST adapter mapping, and configuration.
2. Verify API contract and query parameters.
3. Perform one narrow live probe if permitted and reachable.
4. Verify status, parsing, result count, stable identifier, source URL, and normalized provenance.
5. Verify timeout, malformed JSON, empty results, server errors, and rate-limit handling.
6. Run applicable GBIF orchestrator and catalog contract tests in isolation.
7. Add regression tests for confirmed defects.
8. Verify real orchestrator retrieval, not just adapter construction.
GBIF does not require LocalIndex harvesting under the current contract.
Apply the GBIF CapVer decision rule in Section 8. Do not add `G5-04` to the authorization list based on inference.
## 12.2 Organic Eprints — `organic_eprints`, `G1-02`
Expected endpoint:
`https://orgprints.org/cgi/oai2`
Previously reported decision:
`OD-G1-02-ONBOARDING-ACTIVE`
Required work:
1. Verify OAI-PMH request handling, parsing, configuration, and error handling.
2. Inspect the bounded LocalIndex path.
3. Verify current counts/generation using safe isolated methods.
4. Verify capability contract and persistence.
5. Run `OrganicEprintsOaiLocalIndexPathTest.php` or the current equivalent.
6. Perform safe local retrieval without rebuilding operational FTS.
7. Verify source IDs, source locators, and provenance.
8. Update stale notes only with current evidence.
9. Preserve approved bounded scope.
Historical generation 2 and 189 records are not current proof or automatically approved limits.
## 12.3 BHL — `bhl`, `G6-20`
Expected endpoint:
`https://www.biodiversitylibrary.org/api3`
Expected operation: `PublicationSearch`  
Expected query parameter: `searchterm`  
Previously identified configuration: `agricultural_intelligence.bhl.api_key`
Required work:
1. Verify current endpoint and response envelope.
2. Check credential presence without printing/logging the value.
3. Verify secret redaction, timeout, missing-key, and invalid-key behavior.
4. Run one narrow live probe if a valid key and network access are available.
5. Test success, error, empty-result, and malformed-payload behavior.
6. Run applicable BHL orchestrator and credential/envelope tests in isolation.
7. Verify normalized identifiers, metadata, source URLs, and provenance.
8. Ensure missing credentials result in a truthful blocked state.
If a required credential is missing or invalid and no approved alternative exists, classify BHL as `BLOCKED — EXTERNAL DEPENDENCY`. Do not claim full operation.
Do not rotate credentials without a concrete security reason and verified safe procedure.
BHL does not require LocalIndex harvesting under the current contract.
## 12.4 FAO AGRIS — `agris`, `G1-01`
Expected endpoint:
`https://agris.fao.org/ods/AGRIS.ODS.xml`
Previously reported decision:
`OD-G1-01-SCOPE-BOUNDED-SUBSET`
Required work:
1. Inspect the ODS reader, bounded harvester, ingestion command, parser, projector, and failure handling.
2. Verify `scientific:harvest-agris-ods` and `scientific:ingest-bulk`.
3. Confirm the bounded scope is enforced in code.
4. Verify the approved license policy against current ADR and actual record metadata.
5. Exclude or quarantine records whose licenses cannot be verified, as required by the approved policy.
6. Obtain current counts/generations safely.
7. Run applicable bounded-harvester, projector, and governed-activation tests in isolation.
8. Test idempotency, duplicate handling, and partial-failure behavior.
9. Verify source IDs, URLs, and provenance.
10. Update catalog notes only with verified current evidence.
Historical figures of 97/1,244 providers, 87,984 rows, and generation 29 are reference observations only.
Do not expand to the full 1,244-provider corpus without a separate explicit owner authorization.
## 12.5 Dr. Duke's Databases — `dr_duke`, `G1-17`
Previously reported decision:
`OD-G1-17-SCOPE-LIMITED-PROJECTION`
Expected identities:
- `FARMACY`
- `FARMACY_NEW`
- `ETHNOBOT`
Required work:
1. Verify loader, reconciler, projection logic, and LocalIndex integration.
2. Run applicable reconciler and governed-activation tests in isolation.
3. Obtain current per-dataset counts, total counts, generations, and projection status safely.
4. Preserve FARMACY and FARMACY_NEW as separate identities.
5. Ensure ETHNOBOT traditional-use records are not presented as efficacy proof.
6. Verify approved projection caps.
7. Verify identifiers, locators, and provenance.
8. Test local retrieval without destructive operational FTS access.
9. Update catalog notes only with current evidence.
Historical 211,948 rows and generation 19 are not current proof.
The previous audit reported 60,000-row caps for ETHNOBOT, FARMACY, and FARMACY_NEW. Verify the exact approved limits in current ADR-023 before treating them as authoritative.
Do not raise caps or activate `G6-06`.
---
# 13. BOUNDED DATA SCOPE
Distinguish historical observations from approved limits.
| Source | Historical observation | Required current verification |
|---|---|---|
| Organic Eprints | Generation 2; 189 records | Current generation/count and approved scope |
| AGRIS | Generation 29; earlier figures included 97/1,244 providers and 87,984 rows | Current bounded scope, license compliance, count, and any approved cap |
| Dr. Duke | Generation 19; 211,948 rows; previously reported 60,000 per-dataset caps | Current per-dataset counts and exact approved cap enforcement |
| GBIF | Historical API success | Current retrieval and normalized output |
| BHL | Historical API success and previously configured key | Current credential state, retrieval, and normalized output |
Historical counts are not automatically current counts or hard caps.
Extract approved limits from the current ADR and owner decisions. If a limit is unclear, do not invent one. Preserve the existing bounded behavior and report the ambiguity.
Do not exceed an explicitly approved scope to increase coverage.
---
# 14. LIVE PROBE BUDGET
Live probes must be narrow, rate-limited, and evidence-driven.
For each source per task:
- Default maximum: one live request.
- If a genuine transport error occurs, allow at most two additional retries.
- Never retry indefinitely.
- Wait at least 30 seconds between consecutive requests to the same provider.
- Respect published provider policies and rate limits.
- Stop immediately if HTTP 429 or explicit rate-limit exhaustion is observed.
- Do not repeatedly probe authentication, permission, or license errors.
- Do not probe the operational record store.
Record:
- Timestamp.
- Source key.
- Query shape, omitting unnecessary user data.
- HTTP status or local retrieval result.
- Response size.
- Result count.
- Rate-limit headers, when available.
- Parsing, normalization, and provenance results.
Never log API keys, authorization headers, or secrets embedded in URLs.
If network access is unavailable, classify the probe as blocked. Do not invent a live success.
---
# 15. CONFIGURATION AND SECRET PROTECTION
Inspect actual configuration for:
- `GBIF_ENABLED`
- `ORGANIC_EPRINTS_ENABLED`
- `BHL_ENABLED`
- `AGRIS_ENABLED`
- `DR_DUKE_ENABLED`
Verify current names, defaults, parsing, runtime behavior, and `.env.example` documentation.
Ensure:
- Disabled sources cannot run accidentally.
- Configuration flags do not bypass seat eligibility, capability, scope, or licensing.
- Missing required credentials produce explicit blocked states.
- Documentation contains no real credentials.
- Existing secrets are not modified unnecessarily.
## 15.1 Mandatory final secret scan
Inspect task diffs, logs, command output, and generated documentation for likely secret leakage.
Check for patterns such as:
- `BHL_API_KEY`
- `apikey=`
- `Bearer `
- `sk-`
- Other credential patterns identified in current code.
Review email-like strings or other sensitive identifiers if accidentally included.
Redact values. Report only the finding type, file/path, and remediation status. Never print the secret itself.
A variable name or placeholder is not automatically a secret leak; inspect context safely.
If a real secret is exposed in a task-generated artifact, stop further publication or staging of that artifact, preserve evidence safely, redact the artifact, and report the issue.
---
# 16. TESTING AND LEGACY REGRESSION
## 16.1 Locate current tests
Find the actual current test files for:
- GBIF orchestrator end-to-end behavior.
- Shared catalog contracts.
- Organic Eprints OAI-PMH and LocalIndex.
- BHL orchestrator and API credential/envelope behavior.
- AGRIS bounded harvesting and projection.
- Dr. Duke reconciliation.
- AGRIS/Dr. Duke governed activation.
- Query builder/compiler.
- Registry/selector.
- Normalization/provenance.
- Capability contract and persistence.
- Test bootstrap/database safety.
- Configuration and activation.
Previously reported filenames include:
- `GbifOrchestratorEndToEndTest.php`
- `ScientificSourceCatalogContractTest.php`
- `OrganicEprintsOaiLocalIndexPathTest.php`
- `BhlOrchestratorNearReadyPathTest.php`
- `BhlApiEnvelopeAndCredentialTest.php`
- `AgrisOdsBoundedHarvesterTest.php`
- `AgrisOdsAndDrDukeProjectorTest.php`
- `AgrisAndDrDukeGovernedActivationTest.php`
- `DrDukeDatasetReconcilerTest.php`
Treat these as search leads, not proof of current existence or behavior.
## 16.2 Failure classification
If a test cannot execute solely because a required external service, network, credential, or provider is unavailable, classify it as `SKIPPED — EXTERNAL`, record the exact dependency, and continue independent work.
If the dependency is available but application behavior or assertions fail, classify it as `FAIL`.
Do not classify a code defect as an external blocker. Do not classify an external outage as a code failure without evidence.
## 16.3 Repair loop and retry limit
For each failure:
1. Capture the error and context.
2. Determine whether it is a code defect, contract violation, isolation problem, environment limitation, stale assertion, or unrelated issue.
3. Inspect the approved contract.
4. Repair the underlying defect when authorized and safe.
5. Add regression coverage.
6. Rerun the test and relevant neighboring tests.
7. Record the outcome.
Make no more than three distinct repair attempts for the same failing test or defect. If it still fails after three attempts, stop retrying that defect, document the attempts, classify it accurately, and continue unrelated safe work.
A rerun merely verifying a repair does not count as a new repair attempt.
Never weaken assertions, skip required tests to obtain green results, or mock away the behavior under test.
## 16.4 Legacy five-source regression gate
Any change to shared pipeline classes requires regression review for:
- OpenAlex.
- Crossref.
- Semantic Scholar.
- Consensus.
- FAOSTAT.
This includes changes to:
- `ScientificSourceFamilyRecord`
- `ScientificSearchResult`
- `ScientificSearchQueryBuilder`
- `ScientificQueryCompiler`
- `DelegatesSearchRequestToSearch`
- `ScientificResultNormalizer`
- Shared registry, selector, and orchestration code.
Run the relevant existing Stage-3/legacy-source tests against the current tree.
Historical success is not current proof.
If legacy tests cannot safely execute, report the exact blocker. Do not claim the shared change passed regression verification.
## 16.5 Broader suite
After focused tests pass, run the largest relevant broader suite that is safe in the actual environment.
Do not run an indiscriminate suite if it might access operational SQLite, invoke prohibited migrations, rebuild FTS, write outside the test root, or consume unbounded external resources.
Record exactly what ran, passed, failed, was skipped, or was blocked.
---
# 17. CURRENT COUNTS AND GENERATIONS
For Organic Eprints, AGRIS, and Dr. Duke, obtain current counts and generations through safe, isolated methods.
Preferred approach:
1. Inspect authoritative ingestion metadata or safe application reports.
2. If database-aware access is needed, use a verified isolated copy.
3. Record timestamp, method, database identity, and results.
4. Reconcile the evidence with catalog notes and ADR-023.
5. Update stale notes only when current evidence supports the change.
Do not open operational SQLite for counting.
Do not run ingestion/projection just to obtain a count.
If a count cannot be safely verified, report `NOT VERIFIED` and explain why.
The previously reported 300,635 total scientific records is historical evidence, not a current count.
---
# 18. SAFE STOPPING AND CHECKPOINTS
Stop the affected operation immediately if:
- Operational SQLite or its sidecars change unexpectedly.
- A test bypasses database isolation.
- A command resolves to operational SQLite when isolation is required.
- CapVer writes beyond the approved seat/cell scope.
- A real secret is exposed in task output or a generated artifact.
- The Git baseline is unresolved in a way that makes the operation unsafe.
- A proposed change crosses an explicitly excluded owner-approval boundary.
When a critical safety gate fails:
1. Stop the affected operation.
2. Do not attempt automatic database repair.
3. Preserve the repository and operational files.
4. Write a dated checkpoint in the execution record.
5. Record the last completed stage, commands already executed, exact failure, and remaining safe work.
6. Continue independent work only if it cannot worsen the issue.
7. Report the blocker in the same comprehensive Arabic report.
Do not stop the whole task because of a localized issue when safe independent work remains.
---
# 19. GIT CHANGE MANAGEMENT
Do not commit, push, force-push, rewrite history, or delete worktrees.
Before final reporting:
- Record branch and HEAD.
- Record staged, tracked modified, and untracked counts.
- List all files changed by this task and why.
- Distinguish task changes from pre-existing user work.
- Confirm unrelated files were not staged.
- Check for secrets, local databases, temporary files, and generated artifacts.
- Confirm no prohibited migration or unsafe database operation occurred.
Run `git diff --check` on files changed by this task and inspect the task-specific diff.
Previous reports mentioned baseline ADR whitespace issues and a CRLF warning involving `bootstrap.php`. Recheck the actual current state. Report pre-existing issues separately; do not silently fix unrelated whitespace or line endings.
Do not run dependency installation/update commands to resolve unrelated environment issues.
You may propose a logical commit breakdown, but do not create commits or push.
---
# 20. EXECUTION REPORT LOCATION
Create the dated Arabic report at the first applicable existing location, in this order:
1. `docs/reports/YYYY-MM-DD-FIVE-SOURCE-COMPLETION-REPORT.ar.md`
2. `docs/architecture/reports/YYYY-MM-DD-FIVE-SOURCE-COMPLETION-REPORT.ar.md`
3. `docs/YYYY-MM-DD-FIVE-SOURCE-COMPLETION-REPORT.ar.md`
Verify directory existence before writing. Do not create a new top-level directory solely for this report.
If none of these locations is appropriate, record the reason and use an existing suitable documentation location without modifying an unrelated ADR.
Do not invent an ADR number or delete/rewrite existing architectural history.
The report must contain:
- Execution date and environment.
- Starting branch and HEAD.
- Git baseline reconciliation.
- Worktree and stash inspection.
- Database safety baseline and post-operation comparison.
- Test isolation verification.
- Applicable ADRs and owner decisions.
- Five-source acceptance matrix.
- Query integrity findings.
- CapVer authorization and persistence evidence.
- Current counts/generations where safely verified.
- Live probe results and rate-limit outcomes.
- Actual test commands and results.
- Defects found and root causes.
- Repairs made and regression tests added.
- Configuration and secret-scan results.
- Legacy-source regression results.
- Files changed and reasons.
- Remaining blockers and owner decisions.
- Final Git state.
- Confirmation that no commit/push was performed.
Label evidence as:
- Current execution evidence.
- Current source-code inspection.
- Historical ADR/report evidence.
- Inference not yet verified.
- External dependency limitation.
---
# 21. FINAL ACCEPTANCE MATRIX
Complete the matrix with evidence.
| Dimension | GBIF | Organic Eprints | BHL | AGRIS | Dr. Duke |
|---|---|---|---|---|---|
| Seat identity/membership | | | | | |
| Production registration | | | | | |
| Capability contract | | | | | |
| Capability persistence | Apply authorization rule | | | | |
| Runtime activation/configuration | | | | | |
| Correct adapter family | | | | | |
| Canonical query integrity | | | | | |
| Source-specific query behavior | | | | | |
| Orchestrator integration | | | | | |
| Retrieval verified | | | | | |
| Error/empty-response handling | | | | | |
| Normalization | | | | | |
| Stable IDs and provenance | | | | | |
| License and bounded scope | As applicable | | | | |
| Current count/generation | N/A for direct API | | N/A for direct API | | |
| Source-specific tests | | | | | |
| Regression tests | | | | | |
| Operational database integrity | | | | | |
| Final status | | | | | |
Every `PASS` requires concrete evidence such as a test name/result, verified command, current source location, safely obtained count, live response, or independent persistence check.
Use `N/A` only when the dimension genuinely does not apply, and explain why.
Do not use a checkmark without evidence.
---
# 22. EXECUTION STAGES
Execute these stages autonomously without repeatedly asking for approval for actions already authorized.
## Stage A — Baseline and safety
- Inspect Git, HEAD, branch, stash, and worktrees.
- Reconcile the historical commits.
- Inspect protected files and existing changes.
- Establish the operational SQLite filesystem-only baseline.
- Verify test bootstrap, guard, and TestCase isolation.
- Resolve Docker/host runner availability and permission.
- Create an initial checkpoint.
Do not run tests until mandatory safety gates pass.
## Stage B — Architecture and query integrity
- Trace CSQ through compilation, selection, adapter, normalization, and provenance.
- Verify registry, selector, orchestrator, and adapter behavior.
- Resolve D-04 according to the current approved contract.
- Repair confirmed defects.
- Add regression tests.
## Stage C — CapVer
- Establish current authorization for each of the four seats.
- Inspect command side effects and exact write scope.
- Execute only approved bounded writes.
- Independently verify persistence.
- Preserve GBIF's separate authorization boundary.
## Stage D — Source-specific implementation
- Repair GBIF retrieval and normalization.
- Repair Organic Eprints OAI-PMH and bounded LocalIndex behavior.
- Repair BHL credential-safe retrieval and response handling.
- Repair AGRIS bounded ingestion, license handling, and projection.
- Repair Dr. Duke identity, bounded projection, and provenance.
- Preserve all approved scope limits.
## Stage E — Testing and live probes
- Run safe source-specific tests using the permitted runner.
- Repair and retest defects.
- Execute narrow, rate-limited probes where possible.
- Verify LocalIndex without destructive operational FTS access.
- Obtain current counts/generations safely.
- Verify actual orchestrator behavior.
## Stage F — Regression and integrity
- Run legacy-source regression tests for shared changes.
- Run the relevant broader safe test suite.
- Compare operational SQLite metadata with baseline.
- Check configuration and secret handling.
- Run `git diff --check` on task-changed files.
- Inspect line endings, unrelated diffs, and generated artifacts.
- Confirm no prohibited migration, destructive operation, commit, or push occurred.
## Stage G — Closure
- Complete the acceptance matrix.
- Write the dated report in the prescribed location.
- Record exact Git status and changed files.
- List remaining blockers and owner decisions.
- Deliver one comprehensive Arabic report.
- Stop before commit/push.
Do not stop merely because a defect is found. Repair it when authorized and safe, then continue.
---
# 23. FINAL ARABIC REPORT FORMAT
Deliver one comprehensive report with:
1. **الملخص التنفيذي**
2. **خط الأساس ومصالحة Git**
3. **حماية قاعدة البيانات وعزل الاختبارات**
4. **ما تم تنفيذه والملفات المعدلة**
5. **حالة المصادر العلمية الخمسة**
6. **سلامة الاستعلام العلمي وD-04**
7. **CapVer: التفويض والتنفيذ والتحقق**
8. **البيانات والنطاقات والسقوف**
9. **الاختبارات وبيئة التشغيل**
10. **التحقق التشغيلي وطلبات الشبكة**
11. **اختبارات الانحدار للمصادر الخمسة القديمة**
12. **الإعدادات وفحص الأسرار**
13. **المشكلات التي تم إصلاحها وأسبابها**
14. **الفجوات المتبقية والأدلة**
15. **مصفوفة القبول النهائية**
16. **حالة Git والتسليم**
17. **الحكم النهائي**
Choose one overall status:
- `COMPLETED AND VERIFIED`
- `COMPLETED WITH EXPLICIT EXTERNAL BLOCKERS`
- `PARTIALLY COMPLETED — REMAINING ENGINEERING WORK`
- `BLOCKED BY OWNER DECISION`
Do not use `COMPLETED AND VERIFIED` if required tests were not run, the baseline is unresolved, operational integrity is uncertain, or mandatory acceptance criteria remain unverified.
Distinguish a missing external dependency from an owner decision. Do not request architectural approval when the actual problem is merely an unavailable runner or network.
---
# 24. FINAL OWNER DIRECTIVE
The owner authorizes autonomous engineering implementation and verification within this prompt.
Do not ask for permission for routine, reversible, in-scope actions.
Do not interpret this authorization as permission to:
- Destroy or modify operational data.
- Bypass test isolation.
- Expose secrets.
- Invent capability evidence or authorization.
- Exceed approved source scopes.
- Change CSQ semantics against ADR-023.
- Migrate the scientific record store to PostgreSQL.
- Modify or remove other worktrees.
- Commit or push.
When a specific operation is blocked, continue all independent safe work and document the exact blocker.
**Start now. Inspect the actual repository, reconcile the baseline, establish safety gates, implement verified repairs, run permitted tests, validate the five sources, and deliver the final Arabic evidence-based report. Do not return a plan alone. Stop before commit and push.**
