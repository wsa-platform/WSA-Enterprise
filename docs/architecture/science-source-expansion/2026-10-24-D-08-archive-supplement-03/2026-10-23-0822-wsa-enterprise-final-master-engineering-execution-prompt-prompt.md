<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17974). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 8:22 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17975, 17987; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER ENGINEERING EXECUTION PROMPT
## CapVer Persistence Recovery, PostgreSQL Integration, Backup Safety, Read-Back & Idempotency Closure

**Repository:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Architecture authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Execution mode:** Autonomous Senior Software Engineer, Software Architect, Database Engineer, and Test Engineer  
**Primary objective:** Complete CapVer persistence through actual investigation, implementation, safe database operations, durable read-back, idempotency verification, regression testing, and evidence-based closure.  
**Final boundary:** Complete authorized work, report the results, and STOP BEFORE staging, commit, or push.

---

# 1. YOUR ROLE AND ENGINEERING AUTHORITY

Act as the senior engineer responsible for finishing the authorized CapVer work. You are not limited to reviewing code or proposing plans.

Use broad engineering authority to investigate, design, implement, test, and verify the solution independently within the scope and safety boundaries of this mandate.

You are authorized to:

- Inspect the relevant repository, architecture decisions, code, configuration, database schema, migrations, logs, tests, and existing work in progress.
- Trace the complete CapVer execution path from command invocation through authorization, decision evaluation, dependency injection, repository, model, connection, transaction, persisted records, and read-back.
- Diagnose and repair confirmed defects in connection binding, repository/model integration, transaction behavior, identity mapping, persistence, idempotency, and error handling.
- Use the existing PostgreSQL connection and database `wsa_enterprise` for CapVer under the explicit owner authorization below.
- Reuse the existing CapVer table contract and existing migration when appropriate.
- Apply the existing migration if necessary, compatible, non-destructive, and safe under the migration and backup gates in this prompt.
- Add focused regression tests for confirmed defects.
- Execute the authorized CapVer command using the actual approved runtime.
- Read persisted records through the real application repository and verify them independently where appropriate.
- Repeat the operation to establish durable idempotency.
- Update the relevant architecture documentation if the verified architecture requires a narrowly scoped amendment.
- Continue investigating after the first successful test until every applicable acceptance criterion is verified or a specific external blocker is proven.
- Produce one comprehensive Arabic engineering report grounded in actual execution evidence.

Do not stop at a diagnosis when an authorized, safe implementation is possible. Do not ask the user to perform routine inspections that you can safely execute yourself.

Broad authority does not authorize destructive changes, unauthorized seat activation, unrelated project work, or bypassing the mandatory safety gates.

# 2. EXPLICIT OWNER AUTHORIZATION — POSTGRESQL FOR CAPVER

The owner explicitly authorizes using PostgreSQL database `wsa_enterprise` to resolve the CapVer persistence blocker.

This authorization supersedes the earlier restriction against PostgreSQL **for CapVer only**.

The previously reported configuration was:

- `DB_CONNECTION=pgsql`
- `DB_HOST=127.0.0.1`
- `DB_DATABASE=wsa_enterprise`

Verify the actual current configuration and effective connection before relying on these values.

## 2.1 Persistence boundaries

- CapVer capability-cell persistence may use PostgreSQL `wsa_enterprise`.
- The scientific record store remains on its existing SQLite architecture under ADR-023 §8.62.
- Do not move scientific records to PostgreSQL.
- Do not modify the scientific record store schema to accommodate CapVer.
- Do not change the global default connection unless a demonstrated architectural necessity makes it unavoidable. Prefer a narrowly scoped CapVer connection binding consistent with the existing architecture.
- Do not claim CapVer is persisted in SQLite if its actual records are stored in PostgreSQL.
- Document the actual connection and database used for every write.

# 3. REPOSITORY BASELINE AND DATA PRESERVATION

Before modifying code, configuration, or database state:

1. Confirm the repository root, current branch, HEAD, upstream relationship, and working-tree state.
2. Record `git status --short` and `git diff --cached`.
3. Identify tracked, untracked, staged, unstaged, and pre-existing WIP files.
4. Preserve all existing work and do not overwrite or discard user or agent changes.
5. Inspect the effective database configuration without printing secrets.
6. Identify the actual target database and current CapVer table state.
7. Record a read-only baseline of relevant records, logical identities, row counts, schema, indexes, constraints, and migration status where applicable.
8. Identify the exact changes and database operations planned before executing them.
9. Confirm that the operation cannot reset the database, truncate existing records, or mutate unrelated datasets.

Never run `git reset`, `git clean`, destructive cleanup, broad deletion, or commands that overwrite existing work.

Never drop, truncate, recreate, or reset an existing database or table to simplify testing.

Do not expose database passwords, API keys, tokens, credential-bearing connection strings, or other secrets in command output, logs, or the final report.

Docker remains prohibited under this mandate. Do not use Docker or container-based workarounds.

# 4. TRACE THE REAL CAPVER EXECUTION PATH

Inspect the actual implementations and configuration for:

- `EloquentCapabilityStoreRepository`
- `CghiaCapabilityCell`
- `CapVerSeatVerificationService`
- `AcceptedOwnerDecisionCatalog`
- `NextGateEligibleSeatCatalog`
- `CapVerAcceptedSeatCommand`
- `scientific:capver-accepted`
- `AgriculturalIntelligenceServiceProvider`
- Database connection and path configuration
- Existing CapVer migrations
- Capability identity, decision, evidence, lifecycle, and persistence contracts
- Relevant unit and integration tests
- ADR-023 §§8.58.5, 8.60, 8.62, 8.62.4, and 8.66

Verify rather than assume:

1. Which connection the command uses.
2. Which connection the repository uses for transactions.
3. Which connection the model uses for writes and reads.
4. Whether all write and read operations target the same database.
5. Whether the expected table exists and matches the real contract.
6. Whether unique constraints and indexes protect logical identity.
7. Whether transactions commit successfully.
8. Whether persistence failures are handled accurately.
9. Whether idempotency is determined from persisted state rather than mocks or transient memory.
10. Whether the command's authorization scope matches the authoritative owner decisions.

Identify each root cause using code and runtime evidence.

# 5. MANDATORY PRE-MIGRATION BACKUP GATE

**No migration may be applied to `wsa_enterprise` until every applicable requirement in this section has passed.**

First determine whether a migration is necessary at all. If the existing table is present and compatible, do not run a redundant migration.

If a migration is necessary:

## 5.1 Confirm the target and migration

- Verify the actual server, database, user, and effective Laravel connection.
- Inspect the existing schema and migration history.
- Identify the exact existing migration required by the CapVer contract.
- Confirm that the migration is compatible and does not drop, truncate, replace, or otherwise destroy existing data.
- Confirm that the target is the intended database, not a test or unrelated database.
- Do not create a replacement table or a new migration merely to bypass the existing contract.

The known existing migration is:

`2026_10_09_140000_create_cghia_capability_cell_records_table.php`

The expected table is:

`cghia_capability_cell_records`

Verify the actual file and schema before acting.

## 5.2 Create and verify the backup

Before applying the migration:

1. Use an approved backup mechanism appropriate to the actual database runtime.
2. Create a consistent backup of the target database, including the schema and data required for recovery.
3. Store the backup artifact in an approved backup location outside temporary storage.
4. Confirm that the file exists and is non-empty.
5. Verify that the backup archive or artifact can be inspected.
6. Record its absolute path, timestamp, file size, and SHA-256 hash.
7. Verify that the artifact includes the required schema and data.
8. When feasible, test restoration into an isolated database or environment without modifying the target database.
9. Record the result of every verification step.

Do not assume Docker is available or authorized. Do not use Docker commands as the default backup method.

A successful archive listing alone does not prove complete recoverability. A hash proves artifact identity, not restoreability.

If a required backup or verification step fails, **STOP. Do not apply the migration.**

## 5.3 Apply the existing migration

Only after the backup gate passes:

- Reconfirm the database identity.
- Reconfirm the migration is necessary and has not already been safely applied.
- Apply the existing migration using the project's supported mechanism.
- Capture the real exit code and output.
- Inspect the resulting schema, indexes, constraints, and migration history.
- Confirm that existing data remains intact.
- Do not run unrelated migrations.
- Do not change the scientific record store.

If the migration would be destructive, incompatible, or ambiguous, stop and report the exact blocker.

# 6. CONNECTION IDENTITY GATE BEFORE EVERY WRITE

**Before every migration, CapVer execution, or other database write, verify the effective connection.**

Capture the following without exposing secrets:

- Effective Laravel connection name.
- Actual database driver.
- Actual server and port where available.
- Actual database name.
- Actual database user.
- Actual database server version.
- Successful connection status.
- Confirmation that the target matches the approved `wsa_enterprise` database.

Use the actual available host or application runtime. Verify both the application's effective connection and the database server's identity.

Do not rely only on `.env`, an assumed container name, a remembered server version, or a previously successful connection.

The previously reported PostgreSQL configuration is a reference only. Verify current reality.

If the effective connection, database, user, or server does not match the approved target, **STOP before writing**.

Repeat this gate after any connection or configuration change and immediately before the next write.

Do not print credentials or credential-bearing connection strings.

# 7. ROLLBACK AND PARTIAL-MIGRATION RECOVERY

Before applying a migration, document a recovery plan linked to the verified backup artifact.

If a migration fails or partially applies:

1. Stop further writes and automatic retries.
2. Capture the exact error, exit code, migration status, schema state, and relevant data state.
3. Determine whether the migration ran completely, partially, or not at all.
4. Compare the actual database state with the migration definition and recorded migration history.
5. Preserve diagnostic evidence and the database's current state.
6. Determine the least destructive recovery strategy supported by the evidence.
7. Use the verified backup only through an approved, controlled recovery procedure when necessary.
8. Verify the database state after any approved recovery.

**Never automatically:**

- Drop a table to make the migration retry succeed.
- Delete rows from the `migrations` table to force Laravel to rerun a migration.
- Assume a failed migration left no changes.
- Retry a migration blindly.
- Restore a backup over the target database without evaluating data loss and obtaining the necessary authorization.

If the table exists but the migration is not recorded, inspect the schema and migration history. Do not manually mark the migration as applied. Determine the safe supported resolution from the actual state.

If the migration is recorded but the table is missing, investigate the discrepancy before changing migration history.

If the table is partially created or malformed, preserve evidence and assess recovery options. Any destructive repair or restoration affecting existing data requires a documented safety assessment and must not proceed if its impact is uncertain or outside the current authorization.

If no safe recovery can be established, stop and report the exact owner decision required.

# 8. BEFORE-FIX EVIDENCE AND BASELINE

Before modifying CapVer persistence or database schema, capture the following using safe read-only operations.

## 8.1 Database baseline

Where the table exists and the actual schema supports the query, capture:

- Total rows in `cghia_capability_cell_records`.
- Distinct authorized seat identities and row counts.
- Relevant records for the seats in scope.
- Logical identity keys and lifecycle states.
- Current schema and column definitions.
- Indexes, unique constraints, and applicable partial indexes.
- Current migration history.
- Effective connection identity.
- Original failing behavior and relevant errors, where safely reproducible.

Discover actual column names before writing SQL. Do not assume fields such as `seat_id`, `capability`, `decision`, or `persisted_at` exist.

If the table does not exist, record that fact and inspect the migration definition and current schema without inventing a baseline query against a missing table.

## 8.2 Preserve and compare evidence

- Save the sanitized outputs or structured results in the execution report.
- Do not expose credentials or unnecessary sensitive records.
- Capture equivalent post-change evidence.
- Explain all material differences in row counts, identities, lifecycle state, indexes, and migration status.
- Distinguish pre-existing data from records created or changed by this task.

Do not treat a successful command exit as a substitute for before/after database evidence.

# 9. RUNTIME AND PHP ENVIRONMENT VERIFICATION

Before executing CapVer, establish the actual approved runtime.

For host PHP, verify:

- PHP version.
- Availability of `pdo_pgsql`.
- Required project dependencies.
- Successful Laravel application boot.
- Effective database configuration.
- Successful connection to the approved target.

If host PHP is unavailable or cannot connect, report the exact blocker.

Do not automatically fall back to Docker. Docker remains prohibited.

Do not switch to another database, environment, or runtime without verifying that it is authorized and targets the intended database.

If the host runtime is unsuitable, investigate approved non-Docker alternatives that already exist in the project environment. If no approved alternative is available, stop and document the smallest environmental correction needed.

Record the actual runtime used for each command.

# 10. ROOT-CAUSE DIAGNOSIS AND IMPLEMENTATION METHOD

Use an evidence-driven sequence:

1. Inspect code, dependency injection, model, repository, transaction, and connection binding.
2. Inspect runtime configuration, errors, and relevant logs.
3. Inspect the database schema, records, indexes, constraints, and migration state.
4. Form one or more root-cause hypotheses only when supported by evidence.
5. Design a test that distinguishes the hypothesis from alternatives.
6. Implement the smallest correct repair.
7. Run the relevant regression test.
8. Verify the actual persisted state.
9. If the hypothesis is disproven, use the new evidence to choose the next investigation step.

Do not invent hypotheses or impose an arbitrary number of hypotheses. Do not repeat the same test without a specific reason.

You may repair confirmed defects involving:

- CapVer connection binding.
- Repository/model connection consistency.
- Transaction and commit behavior.
- Dependency injection.
- Persistence identity and status mapping.
- Read-back logic.
- Idempotency checks.
- Unique identity enforcement.
- Error reporting and failure handling.
- Focused regression tests.

Do not modify unrelated modules merely for style or convenience.

Do not weaken authorization checks, database safety guards, or isolation tests to make a test pass.

# 11. CAPVER AUTHORIZATION BOUNDARIES

The intended source seats are:

- GBIF — `G5-04`
- Organic Eprints — `G1-02`
- BHL — `G6-20`
- AGRIS — `G1-01`
- Dr. Duke — `G1-17`

Inspect the authoritative owner decisions and current authorization catalog before executing CapVer.

ADR-023 §8.66 records scoped authorization for `G5-04`. Do not interpret it as blanket authorization for unrelated seats.

Only process seats authorized by the existing authoritative decisions and code.

Keep `G6-06` / `dr_duke_g6` excluded unless a separate valid owner decision already authorizes it. Do not invent authorization merely to make the command succeed.

For each seat, report whether it was eligible, persisted, already current, rejected, or blocked, with evidence.

# 12. EXECUTE THE REAL CAPVER PERSISTENCE WORKFLOW

Once the connection, schema, backup requirements where applicable, and seat authorization are verified:

1. Capture the pre-run database baseline.
2. Verify the connection identity immediately before writing.
3. Confirm that only authorized seats can be processed.
4. Execute `scientific:capver-accepted` using the verified approved runtime.
5. Capture the real exit code and sanitized output.
6. Inspect transaction and persistence results.
7. Read the saved cells back through the application's actual repository or approved read path.
8. Independently inspect the corresponding database rows using safe read-only queries where appropriate.
9. Compare persisted identity, seat, capability contract, decision state, evidence references, lifecycle, and provenance with the actual contract.
10. Verify that the records remain available after the command process exits.
11. Capture post-run row counts and compare them with the baseline.
12. Confirm that unrelated tables and the scientific record store were not modified by this operation.

Do not infer persistence from a zero exit code alone.

Do not infer persistence from a mocked repository, in-memory store, or isolated unit test.

Do not count a decision as persisted unless the corresponding database record is present and correct.

If a write fails, diagnose the actual failure, implement a safe authorized repair if possible, and repeat the relevant verification.

# 13. VERIFY DURABLE IDEMPOTENCY

After the first successful execution:

1. Preserve the database state.
2. Run the same authorized operation again without deleting or resetting records.
3. Verify the database connection identity before the second write-capable operation.
4. Capture the second run's actual exit code and output.
5. Confirm that existing current records return `ALREADY_CURRENT` or the exact equivalent defined by the implementation contract.
6. Verify that no unintended duplicate logical capability cells are created.
7. Compare before/after counts and logical identities.
8. Confirm that already-current records are not unnecessarily rewritten or assigned incorrect lifecycle states.
9. Verify that the result is based on the persisted database state.

If the application reports `ALREADY_CURRENT` while the record is absent from the database, treat the workflow as failed.

If duplicate logical records appear, diagnose the identity, transaction, uniqueness, or query defect and fix it within scope.

Do not claim "exactly once" in the sense of one method invocation over the application's lifetime. Prove the real contract: durable logical identity, successful read-back, no unintended duplicate current cells, and correct repeated-run behavior.

# 14. REGRESSION AND INTEGRATION TESTING

Use the host PHP runtime and existing installed dependencies. Do not use Docker.

Run focused tests first, followed by the relevant broader test subset.

Verify:

- Repository/model connection binding.
- Schema and model compatibility.
- Successful persistent writes.
- Real repository read-back.
- Transaction and failure behavior.
- Identity and lifecycle mapping.
- Idempotent repeated CapVer execution.
- Duplicate logical identity prevention.
- Seat authorization boundaries.
- `G5-04` scoped authorization.
- `G6-06` remains excluded unless independently authorized.
- DatabaseSafety and test-database isolation protections remain intact.
- The scientific record store remains isolated.
- Existing CapVer contract tests remain valid.

Tests using isolated databases are useful for regression coverage but do not replace real PostgreSQL persistence and read-back evidence.

Never run destructive test operations against `wsa_enterprise`.

If tests cannot run, document the exact environmental blocker and continue independent safe checks.

# 15. ADR-023 AND DOCUMENTATION

Inspect ADR-023 before deciding whether an amendment is needed.

If the verified architecture changes the CapVer persistence decision to PostgreSQL:

- Add a narrowly scoped, dated architectural amendment at the next genuinely unused section number.
- Preserve historical sections; do not silently rewrite prior decisions.
- Distinguish PostgreSQL CapVer capability-cell persistence from the SQLite scientific record store.
- Document the owner authorization, rationale, connection behavior, migration status, idempotency contract, backup gate, recovery approach, and verification evidence.
- Do not claim successful persistence before runtime evidence supports it.
- Do not start a broad archive migration or rewrite unrelated reports.

If the existing ADR already adequately documents the approved decision, avoid unnecessary documentation churn.

# 16. STRICTLY PROHIBITED ACTIONS

You must not:

- Run `git add`.
- Stage files.
- Create, amend, or rewrite commits.
- Push or pull.
- Change branches.
- Reset HEAD.
- Clean the working tree.
- Delete or overwrite existing WIP.
- Drop or truncate tables to force a successful migration.
- Reset the target database.
- Delete migration-history rows to force a retry.
- Restore a backup over the target without an approved recovery assessment.
- Expose credentials.
- Use Docker.
- Move the scientific record store to PostgreSQL.
- Enable `G6-06` without valid authorization.
- Expand into AGRIS harvesting, BHL retrieval, general source expansion, or archive migration.

Stop before staging, commit, and push.

# 17. ACCEPTANCE CRITERIA

The work is complete only when all applicable criteria are supported by actual evidence.

## Repository and safety

- [ ] Initial and final repository state recorded.
- [ ] Existing WIP preserved.
- [ ] No prohibited Git operations performed.
- [ ] No Docker used.
- [ ] No credentials exposed.

## Connection and schema

- [ ] Effective database identity verified before writes.
- [ ] Correct `wsa_enterprise` target confirmed.
- [ ] Existing CapVer table and schema inspected.
- [ ] Existing migration applied only if required and safe.
- [ ] Backup gate passed before any migration.
- [ ] Recovery plan documented.
- [ ] Connection and transaction paths verified.
- [ ] Scientific record store remains isolated.

## Persistence and idempotency

- [ ] Only authorized seats processed.
- [ ] Actual CapVer command executed.
- [ ] Real persisted records verified.
- [ ] Real application read-back succeeds.
- [ ] Data survives command-process termination.
- [ ] Second execution returns `ALREADY_CURRENT` or the contract's equivalent.
- [ ] No unintended duplicate logical records.
- [ ] Before/after evidence captured.
- [ ] Relevant regression and integration tests pass.

## Documentation and final state

- [ ] ADR updated only if necessary.
- [ ] Final acceptance matrix completed.
- [ ] All blockers and remaining risks documented.
- [ ] Execution stopped before staging, commit, and push.

Do not mark a criterion as passed merely because code was changed or unit tests passed.

# 18. BLOCKER POLICY

If completion is impossible, do not conceal the blocker or claim success.

Report:

- The exact failed acceptance criterion.
- The attempted command and actual exit code.
- The relevant sanitized error.
- The actual connection and schema state.
- The root cause.
- The backup and recovery status, if a migration was involved.
- Changes already made.
- Tests that passed and failed.
- The smallest remaining action required.

Use one explicit final status:

- `COMPLETE — POSTGRESQL PERSISTENCE AND IDEMPOTENCY VERIFIED`
- `PARTIALLY COMPLETE — IMPLEMENTATION DONE, VERIFICATION INCOMPLETE`
- `BLOCKED — DATABASE CONNECTION`
- `BLOCKED — SCHEMA OR MIGRATION`
- `BLOCKED — BACKUP OR RECOVERY`
- `BLOCKED — AUTHORIZATION`
- `BLOCKED — ENVIRONMENT`

# 19. FINAL REPORT — ARABIC

Produce one comprehensive Arabic engineering report containing:

1. Executive summary and final status.
2. Repository, branch, HEAD, and WIP baseline.
3. Root-cause analysis and confirmed defects.
4. Actual database connection and schema evidence.
5. Backup artifact details, SHA-256, and verification results if a migration was needed.
6. Migration status and recovery evidence, if applicable.
7. Files changed and reasons for each change.
8. Exact execution commands, sanitized outputs, and exit codes.
9. Persisted records and read-back evidence.
10. First-run and second-run idempotency results.
11. Before/after logical counts and duplicate checks.
12. Test commands, counts, assertions, and results.
13. Seat authorization matrix.
14. ADR changes, if any.
15. Final acceptance matrix.
16. Remaining risks and blockers.
17. Final Git state and explicit confirmation that no staging, commit, or push occurred.

Clearly distinguish observed facts, historical evidence, assumptions, and unverified claims.

---

# FINAL EXECUTION DIRECTIVE

Start with the real repository and working-tree state. Trace the actual code path. Use the explicit owner authorization to resolve the CapVer persistence blocker safely through PostgreSQL `wsa_enterprise`.

Implement the smallest architecturally correct repair. If a migration is required, do not proceed until the mandatory backup and connection-identity gates pass.

Execute the real workflow, verify durable read-back, repeat the operation, prove idempotency, and run the relevant regression tests.

Do not stop at recommendations when safe, authorized implementation is possible. Do not invent schema, authorization, backup evidence, or test success.

**Your mission is to complete the authorized CapVer persistence work through actual engineering and verification, preserve all existing work, and stop before staging, commit, and push.**
