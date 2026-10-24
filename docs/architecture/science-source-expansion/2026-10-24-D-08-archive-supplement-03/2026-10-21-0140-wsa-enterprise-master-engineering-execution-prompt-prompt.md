<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17611). Message timestamp as recorded in the conversation: Wednesday, Oct 21, 2026, 1:40 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17612; it is archived once. -->

---
# WSA-Enterprise — Master Engineering Execution Prompt
## LocalIndex / SQLite Isolation, Backend Resolution, Data-Flow Verification, Historical Risk Assessment, and Pre-Commit Audit
**Repository:** `wsa-platform/WSA-Enterprise`  
**Primary working directory:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Previously observed local HEAD:** `f0ac89700b7a822bd83b2cd821a18e493089d786`  
**Previously observed remote reconciliation baseline:** `52e904ea7e037747bf9147ce95baef80965e195e`  
**Operational scientific store:** `storage/app/scientific_record_store.sqlite`  
**Primary objective:** Implement and verify safe isolation of the LocalIndex/scientific SQLite tests while preserving production search semantics and all existing user work.
The commit hashes and working-tree details above are historical reference points. Revalidate the current repository state before taking action.
---
# 1. Role, standards, and working method
Act as a senior Laravel/PHP engineer, database-isolation specialist, test-infrastructure engineer, and architectural auditor.
Use actual source code, configuration, Git history, and reproducible command output. Do not substitute assumptions for evidence.
Use these status labels consistently:
- **VERIFIED:** established by source inspection, actual command output, or test evidence.
- **IMPLEMENTED:** a change was made and its diff was inspected.
- **PASSED:** an executed test completed successfully with its actual exit code and result.
- **FAILED:** an executed test or safety check failed.
- **NOT RUN:** no execution evidence exists.
- **INFERRED:** a plausible explanation not directly proved.
- **UNKNOWN:** available evidence is insufficient.
- **BLOCKED:** safe progress is not possible under the current constraints.
- **PROPOSED:** a change has been recommended but not implemented.
Do not claim a test passed unless it was actually run. Do not claim a database is unchanged without reporting the available evidence and its limitations.
Minimize communication overhead. Perform one coherent investigation and implementation cycle, then deliver one comprehensive Arabic report. Do not send repetitive interim summaries or ask questions that can be resolved from the repository.
---
# 2. Authorization and strict scope
## 2.1 Authorized actions
You may:
1. Inspect Git state, source files, configuration, and relevant history.
2. Implement narrowly scoped test-isolation changes.
3. Add focused test helpers and safety tests when required.
4. Run only the isolated tests explicitly authorized in this prompt.
5. Diagnose failures and make additional in-scope fixes necessary for isolation.
6. Perform read-only filesystem inspection of the operational database.
7. Inspect existing logs or runtime process information using verified read-only methods.
8. Produce a complete Arabic pre-commit report.
## 2.2 Prohibited actions
Do not:
- Commit, push, merge, rebase, cherry-pick, switch branches, reset, clean, stash, or rewrite Git history.
- Discard, overwrite, restore, stage, or remove unrelated working-tree changes.
- Delete, rename, or modify existing worktrees.
- Run tests that may write to operational databases.
- Run `backend/tests/Pgsql/`, the full backend suite, scientific harvesting, operational ingestion, or production indexing.
- Apply database migrations to operational stores.
- Open or query the operational scientific SQLite file through Laravel, Tinker, SQLite CLI, or another database client during this task.
- Execute FTS integrity commands against the operational SQLite file.
- Make the operational database read-only, rename it, replace it, restore it, or modify its attributes.
- Stop, restart, recreate, or reconfigure running production services.
- Modify production search semantics to make tests pass.
- Disable or skip the production-orchestrator integration test.
- Replace the real orchestrator, registry, or adapter with mocks that conceal the unsafe path.
- Modify the scientific-source migration or move scientific data to PostgreSQL.
- Modify unrelated ADRs, source-expansion reports, generated files, or application features.
- Add unrelated Composer files or lockfiles.
- Print credentials, access tokens, passwords, or complete environment files.
- Use Docker operations that mutate containers, networks, volumes, or database state.
**Commit and Push are explicitly forbidden, regardless of test results.**
If a required safety condition cannot be established, stop the affected operation before its first write. Do not bypass the protection to get a green test.
---
# 3. Preserve repository state and all existing work
The original worktree has historically contained extensive uncommitted changes and has been behind the remote. Treat it as sensitive and non-disposable.
Before editing:
1. Record the current working directory, branch, HEAD, upstream, and divergence.
2. Confirm whether a merge, rebase, cherry-pick, or other Git operation is active.
3. Record the count of modified, staged, and untracked paths without printing sensitive contents.
4. Inspect existing diffs for every proposed target file.
5. Identify target files already containing user changes.
6. Record the operational database's filesystem identity and safe metadata baseline.
7. Confirm no relevant write-capable test, migration, harvest, or ingestion is currently running.
Previously observed context to revalidate:
- Original worktree: `G:\WSA-Enterprise`
- Reconciliation worktree: `G:\WSA-Enterprise-archive-reconcile`
- Isolated frontend worktree: `G:\WSA-Enterprise-push-isolated`
- Previously reported remote baseline: `52e904ea7e037747bf9147ce95baef80965e195e`
Do not assume these facts remain current.
### Git safety rules
- Do not synchronize the original dirty worktree with the remote.
- Do not merge the remote reconciliation branch into the original worktree.
- Do not create commits on the original branch.
- Do not modify or remove the two existing auxiliary worktrees.
- Preserve all user modifications, including modifications to in-scope files.
- If a target file has conflicting or ambiguous user changes, stop before editing that file and report the exact conflict.
- Do not use Git operations to make the working tree appear clean.
If the original worktree cannot be modified safely, stop and report the blocker. Do not automatically create a new worktree or switch branches.
---
# 4. Revalidate the source audit and establish the actual connection path
Inspect the current implementation of:
- `LocalIndexLifecycleTest`
- `IncrementalBulkIngestDoesNotWithdrawPeersTest`
- `ScientificRecordsSqliteDurabilityTest`
- `LocalIndexSearchIntegrationTest`
- `backend/tests/bootstrap.php`
- `backend/tests/TestCase.php`
- `backend/tests/Support/DatabaseSafety.php`
- The actual PHPUnit configuration
- `backend/config/database.php`
- `backend/config/agricultural_intelligence.php`
- `AgriculturalIntelligenceServiceProvider`
- `ScientificSourceAdapterRegistry`
- `LocalIndexScientificSourceAdapter`
- `SqliteFtsScientificIndex`
- `LocalScientificIndex`
- `LocalIndexIngestionService`
- `ScientificRecordStore`
- `LocalIndexSearchEngine`
- `MultiSourceScientificSearchOrchestrator`
Resolve the actual paths and namespaces from the repository.
Trace connection selection, application bootstrap, service-provider registration, lazy resolution, cached configuration, singleton reuse, transactions, SQLite file paths, FTS5 tables, generation metadata, and cleanup.
For each relevant test, establish:
- The actual connection and services used.
- The final resolved database path.
- Which code performs writes, updates, deletes, migrations, or rebuilds.
- Whether test configuration is established before connection resolution.
- Whether a production service or singleton bypasses test bindings.
- Whether rollback protects the actual connection being written to.
- Whether WAL/SHM sidecars may escape the intended test directory.
- Whether teardown can remove a file not owned by the test.
Do not run tests during initial inspection.
---
# 5. Mandatory investigation: `search_backend`
Before changing any production configuration, investigate the actual backend-selection design.
## 5.1 Inspect configuration and history
Inspect:
1. `backend/config/agricultural_intelligence.php`
2. Every actual code read of `search_backend`.
3. Service-provider bindings, constructors, factories, and selection logic that use the value.
4. Existing tests that exercise automatic backend selection.
5. Git history, including read-only queries equivalent to:
   `git log -S "search_backend" --all -- <relevant configuration path>`
   `git log --all -- <relevant configuration path>`
6. Historical diffs and commit messages relevant to adding, changing, or removing the key.
7. The actual default when the key is absent and the code path selected under the SQLite driver.
Establish whether the system uses an explicit value, an intentional `auto` default, a previously removed key, or an unresolved configuration inconsistency.
## 5.2 Required decision gate
Do not assume that a missing `search_backend` key is a design defect. Do not assume that `auto` is incorrect.
Choose and document one outcome based on evidence:
**Outcome A — Preserve `auto`**
If automatic selection is intentional, or no evidence establishes otherwise, preserve the production behavior. Implement test isolation without changing backend-selection semantics.
**Outcome B — Potential configuration defect**
If code history and tests establish a real configuration defect, explain the evidence and the smallest correction. Do not make the production-configuration change without separate explicit authorization.
**Outcome C — Intent unresolved**
If the intended architecture remains ambiguous, do not change production configuration. Continue safe test-only isolation where possible and report the architectural decision that remains unresolved.
Do not introduce `SCIENTIFIC_SEARCH_BACKEND` or a new environment variable unless the repository's actual configuration supports it and the change has been separately authorized.
**Default for this task: preserve existing production backend selection.**
---
# 6. Mandatory investigation: ingestion-to-search consistency
Do not assume that the ingestion service and search index use the same implementation or database.
Trace the actual data flow through:
- `LocalIndexIngestionService`
- `ScientificRecordStore`
- `LocalScientificIndex`
- `LocalIndexSearchEngine`
- `SqliteFtsScientificIndex`
- `LocalIndexScientificSourceAdapter`
- `ScientificSourceAdapterRegistry`
- `MultiSourceScientificSearchOrchestrator`
Establish:
1. Where ingestion writes each record.
2. How source identity, generation, freshness, and metadata are stored.
3. What `SqliteFtsScientificIndex::rebuild()` reads as its source of truth.
4. Which connection every read, insert, update, delete, and rebuild uses.
5. How the adapter resolves its search engine.
6. Whether the production adapter can find the records produced by the test ingestion service.
7. Whether the current test binds an in-memory store that the real adapter bypasses.
8. Whether the components share a file, share only a contract, or use a different synchronization mechanism.
Present the actual dependency graph and data-flow path before proposing changes.
Evaluate these scenarios against source evidence rather than assuming one:
- Ingestion writes to `LocalScientificIndex` while search reads from `SqliteFtsScientificIndex`.
- Ingestion and search both use `SqliteFtsScientificIndex`.
- Ingestion and search both use `LocalScientificIndex`.
- Another arrangement established by the actual implementation.
If a source-of-truth mismatch exists, explain its exact consequences. Do not silently change production bindings or search semantics. If a production architectural decision is required, stop that part of the work and request separate authorization.
The integration test must continue to exercise the actual production orchestrator, adapter registry, and adapter path.
---
# 7. Assess possible historical damage without opening the operational database
Investigate whether there is reliable evidence that the dangerous integration test may previously have run against the operational scientific store.
Use only existing evidence that can be inspected safely, such as:
- Existing test logs and CI artifacts.
- Existing application and container logs.
- Prior documented test reports.
- Git history.
- Existing backups and previously captured fingerprints.
- Repository records establishing prior execution history.
Do not assume that missing logs prove the test was never run.
Do not execute Laravel Tinker, application queries, SQLite queries, migrations, FTS commands, or other database operations against the operational scientific file.
Do not run this or equivalent operations against the operational database:
- Counts of `scientific_source_fts`
- Counts of `scientific_source_records`
- Inserts using FTS `integrity-check`
- FTS rebuilds or synchronization
- Deletions or writes intended to test safety
An FTS integrity-check issued as an SQLite special `INSERT` command is not an acceptable read-only diagnostic for the operational database.
## 7.1 Interpret evidence conservatively
A reported `dataset_example` count of zero does not prove damage. It may mean the source was never indexed, is absent, or is represented differently.
A successful FTS integrity check does not prove that all expected scientific records are present.
Separate these concepts:
- Structural integrity of the FTS index.
- Number of indexed documents.
- Consistency between source records and indexed documents.
- Evidence of a historical test run.
- Evidence of actual operational data mutation.
If historical evidence is insufficient, classify possible prior damage as `UNKNOWN`.
Do not repair, rebuild, repopulate, restore, or mutate the operational store. Do not automatically append an ADR section. If a credible damage indicator is discovered, report it as a separate blocker with the evidence and decision required from the project owner.
---
# 8. Investigate `database is locked` safely
The previous integration test reportedly failed with `database is locked` while the production scientific SQLite file may have been selected.
Determine what can be established about processes that may open the operational file, including backend, queue, scheduler, worker, or other services.
First inspect:
- Existing runtime configuration.
- Actual container names, if available through read-only inspection.
- Existing logs.
- Available process-inspection tools.
- Existing documentation about database access.
Do not assume that `lsof` exists inside a container. Do not assume container names from historical examples are current.
Any process inspection must be read-only and verified safe before execution. Do not install packages in running containers.
Do not stop, restart, recreate, or reconfigure production services.
Do not open the operational database through Laravel or SQLite merely to check whether it is locked.
Classify the result as one of:
- Confirmed lock owner.
- Open file handle observed, but causality unproven.
- Historical lock error with no evidence of the current owner.
- Unknown cause.
A current lock does not prove the historical cause, and the absence of a current lock does not disprove a past lock.
A production process holding the operational database open is not a reason to stop that service. Correct test isolation must work regardless of whether the operational file is open.
---
# 9. Required test-isolation architecture
## 9.1 Isolate the scientific SQLite connection
For tests requiring SQLite, use a test-owned database file under the approved testing root, normally:
`storage/framework/testing/`
Use a unique per-test or per-suite filename appropriate for the actual PHPUnit execution mode and parallelization behavior.
Inspect the actual configuration for:
- `SCIENTIFIC_RECORD_STORE_DRIVER`
- `SCIENTIFIC_RECORD_STORE_URL`
- `SCIENTIFIC_RECORD_STORE_DATABASE`
- Any other relevant connection configuration keys.
Ensure that a URL cannot override the intended test file.
Set test-only configuration before the connection is resolved. After changing configuration, purge the named connection if required by the actual Laravel lifecycle. Verify that no previously resolved connection or singleton still points to an operational path.
Do not modify production defaults just to make test isolation easier.
## 9.2 Fail-closed guard
Extend the existing `DatabaseSafety` protection. Do not weaken existing application-database safety or create competing checks.
The guard must protect both:
- The application's default database connection.
- The separate `scientific_records` connection.
Before any migration, ingestion, rebuild, insert, update, or delete, establish that:
1. The expected SQLite driver is configured.
2. A URL cannot redirect the connection to an unexpected target.
3. The resolved database path is within the permitted test root.
4. The path cannot resolve to the operational scientific database.
5. The default application database remains isolated.
6. No previously resolved unsafe connection survives a configuration change.
7. The database file is owned by the current test or isolated suite.
8. Any additional connection used by the test is isolated.
9. No operational database is selected.
10. A failed check stops execution before the first write.
Do not treat changing an environment variable as proof of isolation.
Trace the actual bootstrap order, configuration loading, connection resolution, service-provider execution, test setup, and migration calls.
The guard must run early enough to prevent unsafe writes. A guard invoked only after a migration is insufficient.
If the necessary guarantee cannot be achieved through the existing bootstrap, identify the smallest safe restructuring within the authorized test scope. Do not claim the guard is fail-closed until the ordering is verified.
## 9.3 Preserve the production integration test
For:
`LocalIndexSearchIntegrationTest::test_a_local_index_source_runs_through_the_production_orchestrator_without_http`
preserve:
- `MultiSourceScientificSearchOrchestrator`
- The actual `ScientificSourceAdapterRegistry`
- The real `LocalIndexScientificSourceAdapter`
- The intended production backend-selection path
- The no-HTTP property
- Meaningful assertions proving source discovery and retrieval
Do not mock away the defect, disable the test, weaken its assertions, or replace the integration test with a unit test.
Supply the narrowest legitimate test injection or configuration seam that makes the actual production path use the test-owned scientific store.
Prove that the test reaches the real orchestrator and adapter, not merely that it returns a successful result through a bypass path.
## 9.4 Align ingestion and search
Use the source-of-truth analysis from Section 6 to determine how ingestion, record storage, FTS indexing, and adapter search must be wired for the test.
Ensure the test's records can actually be discovered by the production search path.
Do not assume the correct solution is to force every component into one implementation. Follow the existing production contracts and preserve production semantics.
If the production architecture itself is inconsistent, stop and report the evidence rather than concealing it with a test-only workaround that misrepresents production behavior.
## 9.5 Connection lifecycle and cleanup
Where configuration changes:
- Set the intended path before the first connection resolution.
- Call `DB::purge('scientific_records')` when required, at the correct point.
- Ensure no cached unsafe connection survives.
- Close or purge connections safely before deleting owned files.
- Account for the main SQLite file and its `-wal` and `-shm` sidecars.
- Delete only files created and owned by the test.
- Avoid recursive cleanup over directories containing other tests or user data.
Do not use `purge` mechanically; verify why it is required and whether other cached services also need safe re-resolution.
## 9.6 Durability test
Correct `ScientificRecordsSqliteDurabilityTest` so that:
- The test path is selected before the scientific connection is resolved.
- The guard runs before migrations.
- Any required connection purge happens before reconnecting.
- Persistence is verified across independent store instances using the same isolated file.
- The application database and scientific store are distinct when required by the architecture.
- Cleanup handles the main file and sidecars without touching unowned files.
Do not replace a persistence test with an in-memory-only test.
## 9.7 Preserve other LocalIndex tests
Preserve the intended in-memory and JSONL behavior of:
- `LocalIndexLifecycleTest`
- `IncrementalBulkIngestDoesNotWithdrawPeersTest`
- Other correctly isolated LocalIndex tests.
Do not force all tests to use SQLite if the test does not require it.
Verify that generated JSONL files remain within the intended testing root and that cleanup removes only exact test-owned files.
---
# 10. Implementation scope
The default in-scope files are:
- `backend/tests/bootstrap.php`
- `backend/tests/Support/DatabaseSafety.php`
- `backend/tests/TestCase.php`
- `backend/tests/Unit/Agriculture/Research/Search/LocalIndex/LocalIndexSearchIntegrationTest.php`
- `backend/tests/Unit/Agriculture/Research/Search/LocalIndex/ScientificRecordsSqliteDurabilityTest.php`
Add a narrowly scoped test helper only if necessary and justified.
`backend/config/database.php` and `backend/config/agricultural_intelligence.php` are not default edit targets.
Before changing production configuration or backend-selection behavior, follow Section 5. A production configuration change requires separate explicit authorization. Do not make such a change as part of this task.
Do not edit:
- The operational SQLite database.
- Scientific-source migrations.
- PostgreSQL schema or migrations.
- Production search algorithms or semantics.
- ADR-021, ADR-023, or unrelated documentation.
- Generated reports and source-expansion tools.
- Composer dependency manifests or lockfiles.
- Unrelated application features.
If a production defect makes the task impossible to complete through safe test-only isolation, stop and report it.
---
# 11. Operational database baseline and protection
Before running any permitted test, capture safe filesystem-level evidence for:
`storage/app/scientific_record_store.sqlite`
Record, where feasible:
- Canonical absolute path.
- File existence.
- Size.
- Last-modified timestamp.
- SHA-256.
- Configured operational path, without exposing credentials.
Do not query or open the operational database through SQLite or application services for this baseline.
Verify that each test's final resolved connection path points to the test-owned file and cannot select the operational path.
After each permitted test phase, compare operational file metadata and SHA-256 against the baseline.
State the limitations accurately: matching metadata and hash support the claim that the file contents are unchanged, but cannot prove that no attempted access occurred.
The strongest protection is preventing the test connection from selecting the operational path and proving that the guard fails before writes.
If the operational fingerprint changes unexpectedly:
1. Stop the affected test sequence.
2. Do not continue the suite.
3. Do not restore or repair the file.
4. Preserve logs and report the exact evidence.
5. Wait for explicit authorization.
---
# 12. Prove the guard rejects unsafe paths without reproducing the risk
Before running the normal integration test, implement or verify narrowly scoped negative tests using harmless configuration fixtures, temporary files, or safe test doubles.
Prove that the guard rejects:
1. The exact configured operational path.
2. A path outside the permitted test root.
3. A URL or connection configuration that overrides the permitted test path.
4. An already-resolved unsafe connection, if that condition can be simulated without opening the operational database.
5. A missing or invalid test database path.
Prove that the guard stops execution before any write-capable operation.
Do not point a write-capable test at the actual operational database to prove that the guard catches it.
Do not make the operational file read-only, rename it, copy over it, or alter its attributes.
If a safety case cannot be verified without touching the operational database, stop and report that limitation.
---
# 13. Authorized execution sequence
Perform one coherent execution cycle. Do not ask for confirmation between safe steps unless a real blocker or an out-of-scope architectural decision arises.
## Phase A — Baseline and source audit
1. Verify Git state and relevant diffs.
2. Reinspect the actual connection and dependency paths.
3. Analyze `search_backend` and its Git history.
4. Trace ingestion-to-search consistency.
5. Inspect available historical evidence for possible prior damage.
6. Investigate available read-only evidence about the lock error.
7. Capture the operational file's filesystem baseline.
8. Identify conflicts with existing user changes.
9. Confirm that the required in-scope edits can be made safely.
Do not run tests or edit files in this phase.
If a production architecture decision is required, stop the affected work and report it before making changes.
## Phase B — Implement isolation
1. Configure the test-owned scientific SQLite path before connection resolution.
2. Prevent URL-based connection overrides in the test process.
3. Extend the existing safety guard to protect the scientific connection.
4. Ensure the guard executes before any write.
5. Correct the integration test's dependency wiring without bypassing the production orchestrator.
6. Correct the durability test's configuration, connection purge, persistence assertions, and safe cleanup.
7. Preserve unrelated in-memory and JSONL tests.
8. Inspect all changed files and verify that cleanup targets are strictly test-owned.
9. Inspect the diff for accidental production configuration or unrelated changes.
Do not run tests until the guard and resolved connection paths are verified.
## Phase C — Verify guard behavior
Run the focused safety tests using harmless fixtures or temporary paths.
Verify the allowed path passes and unsafe configurations fail before writes.
If any guard can be bypassed, stop before the normal integration test and fix only the in-scope safety defect.
## Phase D — Run authorized tests in order
Only after the actual resolved paths and guard ordering are verified, run:
1. `ScientificRecordsSqliteDurabilityTest`
2. `LocalIndexSearchIntegrationTest::test_a_local_index_source_runs_through_the_production_orchestrator_without_http`
3. Other `LocalIndexSearchIntegrationTest` methods
4. `LocalIndexLifecycleTest`
5. `IncrementalBulkIngestDoesNotWithdrawPeersTest`
6. Relevant isolation-guard tests added in this task
7. One combined run of the relevant LocalIndex test group, only if all earlier stages are safe
Use the actual PHPUnit executable, configuration, working directory, and test syntax established from the repository. Do not guess commands.
Run tests sequentially unless parallel execution has been proved safe.
For every executed command, record:
- Exact command.
- Exit code.
- Test and assertion counts.
- Failures, errors, and skips.
- Resolved test database path.
- Guard result.
- Operational-file fingerprint comparison.
If a test fails, diagnose the actual failure and fix only in-scope issues. Rerun the relevant failed test first. Repeat the combined group only when its safety conditions still hold.
Do not hide failed attempts or claim success from partial output.
## Phase E — Post-test audit
1. Verify every resolved test connection remained within the test root.
2. Verify the operational path was never selected by the test connection.
3. Compare operational database size, modification time, and SHA-256 with baseline.
4. Verify owned temporary files and sidecars were cleaned safely.
5. Inspect `git diff --check`.
6. Review the complete diff for scope compliance.
7. Confirm pre-existing changes remain present.
8. Confirm no unrelated files were staged or altered.
9. Confirm no migration, Commit, Push, or branch movement occurred.
If any protection fails, stop and report the evidence.
---
# 14. Success criteria
Report full success only if all applicable criteria are verified:
1. The scientific test connection resolves to a test-owned SQLite file before writes.
2. URL-based configuration cannot redirect it to an operational target.
3. The guard protects both the application database and scientific store.
4. The guard runs before migration, ingestion, rebuild, or other writes.
5. The production orchestrator, registry, and adapter remain active in the integration test.
6. The integration test exercises the real production path and discovers the expected source.
7. Ingestion and search use compatible data sources under the actual production contracts.
8. The durability test proves persistence across independent store instances.
9. WAL/SHM cleanup is limited to test-owned files.
10. Existing in-memory and JSONL tests preserve their intended behavior.
11. All permitted tests actually run and pass, or any failures are explicitly reported.
12. Operational database fingerprints remain consistent with baseline evidence.
13. `git diff --check` and the scoped diff review have known results.
14. No unrelated user work is lost or overwritten.
15. No unauthorized production configuration or architecture change occurs.
16. No Commit or Push occurs.
If any criterion is not met, report `PARTIAL` or `BLOCKED` with the exact evidence. Do not label the task fully successful.
---
# 15. Required final Arabic report — one message
Deliver one comprehensive report containing:
## A. Executive summary
- Overall status: `PASS`, `PARTIAL`, or `BLOCKED`.
- What was implemented.
- What remains unresolved.
- Whether human review can safely proceed.
## B. Initial repository state
- Branch, initial HEAD, upstream divergence.
- Initial dirty-worktree summary.
- Existing modifications to target files.
- How user changes were preserved.
- Final Git status summary.
## C. Root cause and final connection architecture
- Original unsafe connection path.
- Exact failure mechanism.
- Final test connection path.
- Guard location and execution order.
- Integration-test path through the real orchestrator and adapter.
- Durability-test path and persistence proof.
## D. `search_backend` decision
- Current configuration.
- Effective default.
- Relevant Git history.
- Whether `auto` is intentional, removed, or unresolved.
- Whether any production configuration was changed; expected answer is no.
## E. Ingestion-to-search consistency
- Actual dependency/data-flow map.
- Which components write and read.
- How generation and freshness are handled.
- Whether a source-of-truth mismatch exists.
- Any unresolved architectural decision.
## F. Historical damage assessment
- Evidence for prior execution.
- Evidence for or against possible FTS inconsistency.
- What is verified and what remains unknown.
- Any separate blocker.
- Explicit confirmation that the operational database was not queried for this investigation.
## G. Lock investigation
- Whether a process was identified.
- Evidence source and limitations.
- Whether historical causality was established.
- Confirmation that no production service was stopped or changed.
## H. File-by-file implementation
For each changed file:
- Exact path.
- Exact behavior changed.
- Why it was necessary.
- Safety invariant.
- Test coverage.
## I. Test results
Use a table:
| Test/command | Exit code | Tests | Assertions | Result | Resolved test path | Notes |
Include every command actually run, including failures and reruns.
## J. Operational database safety evidence
- Baseline and final canonical path.
- Baseline and final size, modification time, and SHA-256 where available.
- Resolved test database paths.
- Negative-guard test results.
- WAL/SHM cleanup results.
- Limitations of the evidence.
## K. Diff and scope audit
- `git diff --check` result.
- Changed tracked files.
- New files attributable to this task.
- Confirmation that unrelated work was preserved.
- Any out-of-scope modifications.
## L. Remaining risks and decisions
- Every unresolved risk.
- Every test not run and why.
- Any required separate authorization.
- Whether the implementation is ready for human review.
## M. Pre-commit checklist
Explicitly report:
- Tests: actual result.
- Operational database writes: none, if proven.
- Operational database reads through SQLite/application: none.
- Migrations: none against operational stores.
- PostgreSQL suite: not run.
- Commit: not created.
- Push: not performed.
- Existing worktrees and unrelated WIP: preserved, if verified.
- Ready for human review: yes/no and why.
---
# 16. Stop conditions
Stop immediately if:
- An in-scope file cannot be safely edited without risking existing user work.
- The safety guard cannot be guaranteed to run before the first write.
- A test connection resolves to the operational scientific database.
- A test attempts to mutate an operational store.
- The operational file fingerprint changes unexpectedly.
- The intended production backend architecture cannot be established and a production change would be required.
- A real ingestion/search source-of-truth mismatch requires an architectural decision.
- A required environment or dependency is unavailable.
- The proposed solution requires disabling the production integration test or changing production search semantics.
- The task requires PostgreSQL work, scientific-source migration, or unrelated architectural changes.
Do not improvise around these conditions. Preserve the current state, stop the unsafe operation, and report the exact blocker.
# 17. Final execution instruction
Start with Phase A and proceed through Phases B–E only when their safety prerequisites are verified.
Implement the authorized test-isolation changes, investigate the backend-selection and data-flow questions, assess historical risk using safe evidence, run only the authorized isolated tests, and deliver the single comprehensive Arabic report.
**Never mutate or query the operational scientific SQLite database. Never alter production backend selection without separate explicit authorization. Never stop production services. Never discard existing user work. Never create a Commit or Push.**
