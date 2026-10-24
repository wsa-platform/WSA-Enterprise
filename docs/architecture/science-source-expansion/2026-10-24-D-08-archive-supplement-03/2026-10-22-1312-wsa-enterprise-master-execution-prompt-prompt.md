<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17972). Message timestamp as recorded in the conversation: Thursday, Oct 22, 2026, 1:12 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17973; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION PROMPT
## CapVer SQLite Persistence Recovery, Read-Back Verification & Idempotency Closure
**Execution mode:** Autonomous Senior Software Engineer + Software Architect + Test Engineer  
**Repository:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Architecture authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Scope:** Workstream 1 — resolve the existing SQLite CapVer persistence path. Workstream 2 — execute authorized CapVer persistence, read-back verification, and idempotency verification.  
**Required final state:** Evidence-backed engineering closure of these two workstreams, or a precisely diagnosed and documented blocker if closure is impossible under the existing architecture and permissions.  
**Commit policy:** STOP BEFORE STAGING, COMMIT, OR PUSH.
---
# 1. YOUR ROLE AND AUTHORITY
Act as the project's senior software engineer, software architect, persistence engineer, and test engineer. You are authorized to investigate deeply, make engineering decisions, implement necessary code corrections, and execute permitted tests to complete the two workstreams—not merely describe a plan.
Work autonomously within the authorized scope. Do not stop after discovering the first defect. Trace the actual execution path, identify root causes, implement the smallest architecturally correct repair, run the relevant tests, inspect the resulting database state, and repeat verification until the acceptance criteria are satisfied or a genuine external blocker is established.
You have broad authority to:
- Inspect repository code, architecture decisions, configuration, migrations, models, repositories, services, commands, tests, and relevant execution logs.
- Trace configuration resolution and database connection selection end to end.
- Determine which SQLite database file is authoritative for the existing CapVer persistence contract.
- Diagnose connection mismatches, incorrect default-connection assumptions, incorrect model connection resolution, transaction boundaries, path-resolution defects, and repository integration problems.
- Modify relevant application code, configuration, and tests when necessary and authorized by this prompt.
- Reuse the existing SQLite-backed CapVer persistence infrastructure and the existing `cghia_capability_cell_records` contract if it is already available in an approved location.
- Repair the repository/model/connection integration so it uses the existing approved SQLite persistence path without introducing a new schema.
- Execute the authorized CapVer command, inspect persisted records through the actual read path, and verify idempotency through a repeated execution.
- Add focused regression tests for every confirmed root cause.
- Produce an evidence-based Arabic engineering report documenting actual changes, commands, test results, persistence evidence, remaining risks, and the exact stopping point.
Do not ask the user to perform an investigation you can safely perform yourself. Do not ask for repeated confirmation for ordinary, reversible, in-scope engineering changes.
**Authority boundary:** Broad engineering authority does not authorize violating the mandatory architecture, database, security, data-preservation, or Git restrictions below. If the required outcome cannot be achieved without violating a restriction, document the blocker instead of bypassing the restriction.
---
# 2. AUTHORITATIVE CONTEXT AND PREVIOUS FINDINGS
Use these findings as the initial investigation baseline, not as assumptions that cannot be challenged by fresh evidence.
The engineering report dated 2026-10-22 recorded:
- Repository: `G:\WSA-Enterprise`.
- Branch: `phase-18-m18-ai-marketing-communications`.
- Reported HEAD: `fb60b60746ed487e2bfa04e3d335827d91cda446`.
- The current CapVer repository is `EloquentCapabilityStoreRepository`.
- The relevant model is `CghiaCapabilityCell`.
- The expected table name is `cghia_capability_cell_records`.
- The current repository/model path reportedly relies on the default database connection.
- The inspected isolated database `scientific_records_108.sqlite` contained `scientific_source_*` tables, but the CapVer table was not found there.
- The operational scientific record store is SQLite under the approved architecture recorded in ADR-023 §8.62.
- The host PHP runtime previously reported PHP 8.4.26 with `pdo_sqlite` and `sqlite3` loaded.
- The authorized command is `scientific:capver-accepted`.
- The report did not execute that command and did not verify persisted CapVer records or SQLite idempotency.
- ADR-023 §8.66 records the scoped GBIF authorization for seat `G5-04` and explicitly states that completion requires SQLite persistence, read-back of a CURRENT VERIFIED cell, and a repeated result of `ALREADY_CURRENT`.
- The report explicitly states that no source reached `CAPABILITY_PERSISTED` or `PRODUCTION_READY` in that run.
Previous report evidence is not a substitute for current verification. Inspect the current repository, current working tree, current configuration, and current database paths before making changes.
Do not reset the repository to the reported HEAD. The repository may have advanced or contain important work in progress.
---
# 3. STRICT SCOPE — WORKSTREAMS 1 AND 2 ONLY
Complete only the following:
## Workstream 1 — Resolve the existing CapVer SQLite persistence path
Determine and prove:
1. Which existing SQLite database file is the approved destination for CapVer persistence.
2. Whether the required CapVer table exists in that database or in another already-authorized SQLite persistence location.
3. How Laravel resolves the active connection for:
   - `EloquentCapabilityStoreRepository`
   - `CghiaCapabilityCell`
   - The `scientific:capver-accepted` command
   - The service that resolves and persists the accepted capability decisions.
4. Whether the command, repository, model, and read-back path resolve to the same approved SQLite database.
5. Whether any default-connection assumption causes the current persistence path to point somewhere else.
6. Whether the existing implementation supports the required CapVer contract without a new schema or whether a genuine architecture-level blocker exists.
Search the entire relevant code path and configuration. Do not infer the authoritative database solely from a filename or from the existence of `scientific_source_*` tables.
## Workstream 2 — Execute CapVer and verify durable persistence
After Workstream 1 is resolved:
1. Run the authorized command `scientific:capver-accepted` using the host PHP runtime and the approved existing SQLite connection.
2. Capture the actual command exit code and meaningful output.
3. Verify that the expected authorized capability cells have been written to the existing SQLite store.
4. Read the persisted records back using the application's real repository/read path.
5. Verify the expected identity, seat, capability contract, decision, status, and provenance fields using the actual contract definitions in the repository.
6. Execute the same authorized operation again without resetting or deleting persisted data.
7. Verify that the repeated operation returns `ALREADY_CURRENT`, or the exact equivalent required by the existing implementation, based on the persisted state—not a mocked repository or an in-memory substitute.
8. Demonstrate that the second run does not create unintended duplicate logical records or alter an already-current decision incorrectly.
9. Run the relevant regression tests and any additional narrowly scoped tests required by discovered defects.
10. Report which acceptance criteria passed, failed, or remain blocked.
Do not expand into AGRIS harvesting, source expansion, BHL live retrieval, Organic Eprints harvesting, Dr. Duke projection, archive migration, unrelated onboarding, or general repository cleanup.
---
# 4. NON-NEGOTIABLE ARCHITECTURE AND SAFETY RULES
## 4.1 Existing SQLite persistence only
**Reuse the existing CapVer SQLite persistence path and table contract.**
- Do not create a new CapVer table.
- Do not add a new CapVer migration.
- Do not create a parallel persistence schema.
- Do not duplicate the capability store under a different table name.
- Do not change the scientific record store schema to make CapVer fit.
- Do not modify historical ADR text to conceal the current blocker.
- Do not assume that `scientific_source_*` tables satisfy the CapVer contract.
- Do not treat an isolated test database as the operational database.
- Do not silently redirect persistence to a newly created SQLite file.
If the required table is missing from every approved existing SQLite location, do not create it. Document exactly what was searched, which paths were verified, what the existing contract requires, and why execution cannot proceed without a separately authorized architecture decision.
## 4.2 PostgreSQL is prohibited for this task
- Do not connect to PostgreSQL for CapVer.
- Do not run CapVer against the default PostgreSQL connection.
- Do not use historical PostgreSQL rows as evidence of current SQLite persistence.
- Do not modify connection settings to silently fall back to PostgreSQL.
- Do not use PostgreSQL to bypass an SQLite defect.
- Do not describe historical PostgreSQL success as completion of the current task.
If Laravel's default connection is PostgreSQL, explicitly resolve the approved existing SQLite connection through the project's supported configuration and architecture. Do not change the global default connection if doing so would affect unrelated application behavior.
## 4.3 Docker is prohibited
Do not run Docker, Docker Compose, containers, or container-based test alternatives.
Use the existing host PHP runtime and installed project dependencies.
If a missing extension, permission, path, or dependency prevents the authorized operation, diagnose the exact blocker and continue any independent safe investigation or tests. Do not introduce Docker as a fallback.
## 4.4 Preserve all existing work
Before editing:
- Record the current branch and HEAD.
- Capture `git status --short`.
- Determine whether the working tree contains staged, tracked, or untracked work in progress.
- Identify which relevant files are pre-existing untracked files.
- Preserve all existing user and agent work.
- Record the hashes and metadata of any SQLite files that may be modified.
- Identify the exact database file the application would use before running a write operation.
Never use `git reset`, `git clean`, destructive cleanup, broad file deletion, or a command that overwrites existing work.
Never delete, recreate, truncate, or reset an existing database to simplify testing.
Do not modify unrelated source files or reformat unrelated sections.
## 4.5 Git boundary
You may inspect diffs and edit authorized files. You may run tests and commands.
You must NOT:
- Run `git add`.
- Stage files.
- Create commits.
- Amend commits.
- Push or pull.
- Change branches.
- Reset HEAD.
- Clean the working tree.
- Deploy or publish the application.
Stop after completing the engineering work and presenting the pre-commit report.
---
# 5. INVESTIGATION AND IMPLEMENTATION METHOD
Follow this sequence. Do not skip a phase simply because a test passes.
## Phase A — Establish the actual execution environment
1. Confirm repository root, branch, HEAD, working-tree state, and upstream relationship.
2. Verify the host PHP executable and version.
3. Verify `pdo_sqlite` and `sqlite3`.
4. Verify Laravel application boot and the availability of the required command.
5. Inspect the effective Laravel database configuration without printing passwords, tokens, URLs containing credentials, or secrets.
6. Identify the exact configuration source for the CapVer command and repository.
7. Determine whether cached configuration affects connection selection.
8. Do not clear caches or modify global configuration unless the investigation establishes that doing so is necessary, safe, and within scope.
Report the actual results, not merely the commands that would check them.
## Phase B — Trace the complete CapVer call path
Inspect, as applicable:
- `EloquentCapabilityStoreRepository`
- `CghiaCapabilityCell`
- `AcceptedOwnerDecisionCatalog`
- `CapVerSeatVerificationService`
- `NextGateEligibleSeatCatalog`
- `scientific:capver-accepted`
- Capability-cell identity and contract classes
- Database connection and path configuration
- Existing migrations and approved persistence definitions
- Related ADR-023 sections, particularly §8.58.5, §8.60, §8.62, §8.62.4, and §8.66
- Existing unit and integration tests
- Any approved SQLite-backed persistence abstractions already used by the application
Trace the real runtime path from command invocation to decision evaluation, repository write, model connection selection, transaction commit, and subsequent read.
Answer explicitly:
- What connection does the command use?
- What connection does the model use?
- What connection does the repository use?
- What database file does each connection resolve to?
- Are the write and read paths guaranteed to target the same approved file?
- Does the required table exist there?
- Which exact defect prevents durable persistence?
- Is the problem code, configuration, schema location, environment, or an unresolved architecture contract?
Do not invent class methods or schema fields. Inspect their real definitions.
## Phase C — Diagnose root cause and select the smallest correct repair
For each confirmed defect:
1. State the observed failure.
2. Identify the concrete root cause.
3. Show the relevant code/configuration evidence.
4. Identify the smallest authorized fix.
5. Add a regression test that would fail before the fix and pass afterward.
6. Verify that the fix does not weaken database isolation or change unrelated behavior.
Investigate up to three genuinely distinct root-cause hypotheses per defect where needed. A hypothesis counts as distinct only if it leads to a meaningful code/configuration change or a test that isolates a different failure mode. Repeating the same test or collecting more logs is not a new hypothesis.
Prefer an explicit, narrowly scoped SQLite connection binding for the CapVer persistence path when supported by the existing architecture. Ensure that the command, model, repository, and read-back path all resolve to the same approved existing SQLite store.
Do not introduce a new schema, new table, new migration, parallel repository, or new persistence format.
If no approved existing CapVer table can be located, stop short of creating one. Complete the diagnosis, run safe independent tests, and report `BLOCKED — EXISTING SQLITE CAPVER STORE NOT AVAILABLE` with the evidence needed for an owner-level decision.
## Phase D — Establish a safe pre-execution baseline
Before running any command that may write:
1. Determine whether the command is read-only, write-capable, or mixed.
2. Identify all database connections it may open.
3. Confirm the exact resolved SQLite path.
4. Confirm the path is approved and not an operational database outside the permitted scope.
5. Confirm the target database is not a disposable test file unless that is explicitly the approved store.
6. Capture the pre-run count and relevant existing rows through a safe read path.
7. Record a safe file hash/metadata baseline where appropriate.
8. Confirm the command will not reset existing records, rebuild unrelated indexes, or mutate other source datasets.
9. Confirm that the operation is restricted to the authorized capability seats.
Do not run the command against an uncertain or unverified target.
If the actual path is ambiguous, resolve the ambiguity before executing a write.
## Phase E — Execute the authorized CapVer operation
Once the approved SQLite path is established and the existing table contract is confirmed:
1. Execute `scientific:capver-accepted` through the host PHP runtime.
2. Capture the exit code and complete relevant output, redacting secrets.
3. Determine which seats were evaluated and which decisions were eligible.
4. Verify that only authorized seats were processed.
5. Verify that no unauthorized seat, especially `G6-06`, was enabled.
6. Verify that no other database was opened for CapVer persistence.
7. Confirm that the transaction committed successfully.
8. Read the persisted records using the actual application repository/read path.
9. Compare the returned records with the actual decision and identity contracts.
10. Independently verify the underlying SQLite state using a safe read-only inspection where possible.
The existing approved scope includes the five source seats:
- GBIF: `G5-04`
- Organic Eprints: `G1-02`
- BHL: `G6-20`
- AGRIS: `G1-01`
- Dr. Duke: `G1-17`
However, the current authorization is scoped: the newly documented authorization in ADR-023 §8.66 applies only to `G5-04`. Do not treat that amendment as blanket authorization for unrelated excluded seats. For every seat, inspect the authoritative owner decision and existing authorization before processing it.
If a seat lacks required authorization, do not manufacture authorization merely to make the command report success. Record the seat as blocked or ineligible according to the existing contract.
Do not claim all five seats were persisted unless actual evidence proves it.
## Phase F — Verify read-back and idempotency
After the first successful run:
1. Read back every expected persisted cell using the real repository.
2. Verify stable logical identity and uniqueness using the application's actual key definition.
3. Verify the decision status, seat identity, capability contract reference, and provenance using the real schema.
4. Verify that a successful write is durably visible after the command process exits.
5. Run the same authorized command again without clearing the database or deleting records.
6. Capture the second run's actual output and exit code.
7. Confirm that already-current records return `ALREADY_CURRENT` or the exact existing equivalent.
8. Confirm the second run does not create unintended duplicate logical cells.
9. Compare relevant before/after counts and records.
10. If the command reports `ALREADY_CURRENT` but no durable row exists, classify the result as a failure.
11. If the command exits successfully but the read-back path cannot find the row, classify the result as a failure.
12. If the second run inserts duplicate logical records, diagnose and fix the actual identity/idempotency defect within scope, then repeat the tests.
A unit test that mocks the store is not evidence of persistence or idempotency. It may support regression coverage, but it cannot substitute for the real SQLite execution and read-back.
Do not claim “exactly once” in the sense of one method call over the application's lifetime. Prove the actual contract: one logical persisted cell per approved identity, durable read-back, and idempotent repeated execution.
## Phase G — Regression tests and safety verification
Run the narrowest relevant tests first, then the necessary broader test subset.
At minimum, verify:
- Repository/model SQLite connection binding.
- Persistence to the correct existing SQLite store.
- Read-back of a persisted cell.
- Idempotent repeated CapVer execution.
- Duplicate logical identity prevention.
- Seat authorization boundaries.
- `G5-04` scoped authorization.
- `G6-06` remains unauthorized unless an independently approved existing decision says otherwise.
- DatabaseSafety and test-database isolation guards remain intact.
- The existing scientific record store is not accidentally used as a substitute for the CapVer table.
- No regression in existing CapVer contract tests.
Do not weaken `DatabaseSafety` to make a test pass. Preserve rejection of operational database paths, traversal paths such as `..`, paths outside the permitted test root, prohibited connection URLs, and other existing isolation rules.
Never point a destructive test at the operational database.
If a test requires a temporary SQLite database, use a safe isolated test database under the established test root and never confuse it with evidence of operational persistence.
Run additional relevant tests when the root-cause fix warrants them. Do not run unrelated test suites merely to inflate test counts.
---
# 6. REQUIRED ACCEPTANCE CRITERIA
The task is complete only when the applicable criteria below are supported by actual evidence.
## Workstream 1 — SQLite path resolution
- [ ] The authoritative existing CapVer SQLite database path is identified.
- [ ] The source of that path is documented.
- [ ] The existing CapVer table contract is identified.
- [ ] The table is verified to exist in the approved SQLite database.
- [ ] The command, model, repository, write path, and read-back path use the same approved SQLite store.
- [ ] No PostgreSQL connection is used for CapVer.
- [ ] No new table or migration is created.
- [ ] Any confirmed connection or repository defect is corrected with focused regression coverage.
- [ ] Existing DatabaseSafety protections remain intact.
## Workstream 2 — Durable persistence and idempotency
- [ ] The authorized command is actually executed against the approved SQLite store.
- [ ] The actual exit code and output are recorded.
- [ ] Only seats with valid authoritative authorization are processed.
- [ ] Persisted cells are read back through the application's real read path.
- [ ] Cell identity, status, contract reference, and provenance match the actual contract.
- [ ] The persisted state survives command-process termination.
- [ ] A second execution is performed without resetting or deleting data.
- [ ] The second execution returns `ALREADY_CURRENT` or the existing contract's equivalent for already-current cells.
- [ ] No unintended duplicate logical cells are created.
- [ ] Relevant regression tests pass.
- [ ] The final report clearly separates confirmed results from unverified claims.
**Do not mark the workstream complete merely because code changes or unit tests succeed.**
---
# 7. FAILURE AND BLOCKER POLICY
If the task cannot be completed, do not disguise the blocker or stop at a vague statement.
Provide:
1. The exact acceptance criterion that failed.
2. The command or read operation that was attempted.
3. The actual exit code or error.
4. The database path and connection identity involved, without exposing secrets.
5. Whether the cause is code, configuration, missing existing schema, filesystem permissions, PHP extensions, or an architecture decision that requires owner authorization.
6. Which safe work was completed despite the blocker.
7. Which tests passed and failed.
8. The smallest specific owner decision or environmental correction required to proceed.
Use one of these explicit statuses:
- `COMPLETE — SQLITE PERSISTENCE AND IDEMPOTENCY VERIFIED`
- `PARTIALLY COMPLETE — CODE FIXED, OPERATIONAL VERIFICATION INCOMPLETE`
- `BLOCKED — EXISTING SQLITE CAPVER STORE NOT AVAILABLE`
- `BLOCKED — ENVIRONMENT`
- `BLOCKED — AUTHORIZATION`
Do not create a table, migrate a schema, use PostgreSQL, or invoke Docker merely to avoid reporting a blocker.
---
# 8. FINAL REPORT REQUIREMENTS — ARABIC
After execution, produce one comprehensive Arabic engineering report. Do not send multiple competing analyses of the same execution.
The report must include:
## A. Executive summary
- Final status.
- Whether Workstream 1 is complete.
- Whether Workstream 2 is complete.
- The most important confirmed outcome.
- Any blocker preventing closure.
## B. Repository and safety baseline
- Repository root, branch, HEAD before and after.
- Initial and final `git status --short` summary.
- Relevant staged/unstaged/untracked state.
- Confirmation that no reset, clean, staging, commit, push, Docker, or PostgreSQL CapVer operation occurred.
## C. Root-cause analysis
For every confirmed defect:
- Observed behavior.
- Root cause.
- Evidence.
- Exact files changed.
- Why the chosen repair is architecturally correct.
- Regression test added.
## D. SQLite path proof
- Approved SQLite file path.
- Source of path resolution.
- Connection name and resolution behavior.
- Table name and schema evidence.
- Proof that command, repository, model, and read-back target the same database.
- Proof that no new table or migration was created.
## E. Execution evidence
For every CapVer execution:
- Exact command.
- Actual exit code.
- Relevant output.
- Authorized seats evaluated.
- Cells inserted, already current, skipped, or rejected.
- Read-back results.
- Before/after logical record counts.
- Second-run idempotency result.
- Evidence of no unintended duplicates.
Do not expose credentials, database passwords, API keys, or secret connection strings.
## F. Test report
For each test command:
- Exact command.
- Exit code.
- Test count and assertions when available.
- Pass/fail/skip counts.
- What the test actually proves.
- Any unexecuted tests and why.
## G. Acceptance matrix
Show each acceptance criterion as:
- `PASS`
- `FAIL`
- `BLOCKED`
- `NOT VERIFIED`
Include a concise evidence reference for each row.
## H. Changed-file inventory
For every changed file:
- Path.
- Reason for change.
- Change summary.
- Whether the file was tracked or already untracked before the task.
- Relevant diff or test evidence.
Distinguish task changes from pre-existing work in progress.
## I. Final state and next action
State exactly what remains, if anything. If blocked, identify the smallest next action needed. Do not broaden the scope into AGRIS, BHL, source expansion, archive migration, or other workstreams.
## J. Commit boundary
Explicitly confirm that execution stopped before staging, commit, and push.
---
# 9. FINAL INSTRUCTION
Start by inspecting the real repository and working-tree state. Then execute the investigation and repair sequence above.
Use engineering judgment, not blind obedience to a failing implementation. Fix the root cause when a safe, authorized fix exists. Do not invent success, test results, schema, or authorization. Do not stop after producing recommendations when safe in-scope implementation is possible.
**Your mission is to finish Workstream 1 and Workstream 2 through actual code-level investigation, implementation, execution, durable SQLite read-back, idempotency verification, and regression tests—not merely to write a plan.**
If the existing approved SQLite CapVer table cannot be found, the correct outcome is a precise, evidence-backed blocker report, not an unauthorized new table or a PostgreSQL/Docker workaround.
**STOP BEFORE `git add`, `git commit`, and `git push`.**
