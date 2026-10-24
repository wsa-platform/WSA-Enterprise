<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18000). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 2:00 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18001; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER ENGINEERING EXECUTION MANDATE
## CapVer SQLite Persistence Recovery, Historical Data Preservation, Migration Compatibility, Durable Read-Back, and Idempotency Verification
**Repository:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Architecture authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Execution mode:** Autonomous Senior Software Engineer, Software Architect, Database Engineer, Test Engineer, and Reliability Engineer  
**Primary objective:** Restore and verify CapVer persistence using a durable, correctly scoped SQLite store, while preserving potentially existing PostgreSQL data, keeping the scientific record store isolated, and proving real persistence, repository read-back, and idempotency.  
**Final boundary:** Complete all safe, authorized implementation and verification, then STOP BEFORE staging, commit, or push.
---
# 1. ROLE AND ENGINEERING AUTHORITY
Act as the senior engineer and architect responsible for completing the authorized CapVer persistence repair.
You have broad authority to investigate, design, implement, test, diagnose, and verify the solution independently within the explicit boundaries of this mandate.
You are expected to perform real engineering work, not merely provide a plan or recommendations.
You are authorized to:
- Inspect the repository, architecture decisions, current and historical reports, code, configuration, database files, migrations, tests, logs, and existing WIP.
- Trace the complete CapVer execution path from command invocation through authorization, dependency injection, model, repository, transaction, persistence, read-back, and idempotency.
- Inspect existing PostgreSQL and SQLite artifacts through approved, available, non-destructive mechanisms.
- Determine the fate of potentially existing PostgreSQL CapVer data before deciding whether an empty SQLite store is acceptable.
- Reuse an existing compatible CapVer SQLite store if one exists.
- Design and implement a dedicated, persistent SQLite store for CapVer if no suitable existing store exists and the historical-data gate has been satisfied.
- Implement a narrowly scoped Laravel connection and correct model/repository binding where required.
- Reuse the existing CapVer migration when compatible and safe.
- Develop a SQLite-compatible migration strategy if the current migration is not compatible, while preserving the historical PostgreSQL contract and migration semantics.
- Add focused regression tests and integration tests.
- Execute the real authorized CapVer command.
- Verify persisted records using the actual application repository and independent read-only database inspection.
- Execute the command a second time and prove the documented idempotent behavior.
- Make narrowly scoped ADR-023 amendments if required.
- Continue until all applicable acceptance criteria pass or a concrete blocker requiring an owner decision is established.
- Produce one comprehensive Arabic engineering report based on actual evidence.
Do not stop at diagnosis when safe, authorized implementation is possible.
Do not invent execution results, schema details, backup evidence, historical data status, authorization, or test success.
# 2. AUTHORITATIVE PROJECT CONTEXT AND HISTORICAL BASELINE
Use the following as historical evidence. Verify current repository state and inspect relevant artifacts before relying on historical assumptions.
Repository:
`G:\WSA-Enterprise`
Branch:
`phase-18-m18-ai-marketing-communications`
The most recently reported HEAD was:
`fb60b60746ed487e2bfa04e3d335827d91cda446`
The latest PostgreSQL investigation reported:
- The CapVer command, model, and repository used Laravel's default connection.
- The default connection was configured for PostgreSQL.
- TCP connections to `127.0.0.1:5432` were refused with Windows error `10061`.
- No PostgreSQL session was established.
- The target schema and CapVer table were not inspected during that investigation.
- The actual CapVer command was not run.
- No real persistence, read-back, or second-run idempotency was verified.
- The scientific record store was not modified.
- No Docker, staging, commit, or push occurred.
- Unit tests passed using a fake repository, but those tests did not establish durable database persistence.
- ADR-023 §8.67 was added to document the owner authorization for PostgreSQL CapVer persistence.
The owner has now directed the project back to the SQLite repair path.
Do not repeat the entire PostgreSQL diagnosis unnecessarily. Resume from the current repository and available evidence, while investigating the unresolved fate of historical PostgreSQL data before starting a new empty store.
# 3. NON-NEGOTIABLE STORAGE ARCHITECTURE
## 3.1 Scientific record store
ADR-023 §8.62 establishes the scientific record store's SQLite architecture.
The previously reported file is:
`backend/storage/app/scientific_record_store.sqlite`
Its schema was reported not to contain:
`cghia_capability_cell_records`
Keep this store separate from CapVer persistence.
Do not:
- Add the CapVer table to the scientific record store merely because SQLite is available.
- Change the scientific record store schema to accommodate CapVer.
- Move scientific records to PostgreSQL.
- Replace, recreate, truncate, or reset the scientific record store.
- Modify scientific record persistence as an incidental side effect.
- Change the global default connection without a demonstrated architectural necessity.
The scientific record store must remain unchanged throughout this task.
## 3.2 CapVer persistence contract
The existing CapVer table contract is:
`cghia_capability_cell_records`
The existing migration reported by the previous investigation is:
`backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php`
The migration reportedly defines a unique logical identifier, `capability_record_id`, and partial unique indexes for `CURRENT` rows. Inspect the actual migration before relying on these details.
Preferred architecture:
1. Reuse an existing suitable, durable CapVer SQLite store if it exists and is compatible.
2. Otherwise, create a dedicated SQLite database for CapVer after satisfying the historical-data preservation gate.
3. Configure a narrowly scoped Laravel connection for that database.
4. Bind the CapVer model, repository, transaction handling, and read-back to the same intended connection.
5. Reuse the existing migration if compatible and safe.
6. Preserve logical identity, uniqueness, lifecycle, evidence, provenance, authorization, and idempotency semantics.
Do not create a parallel schema or a new migration merely to bypass a defect in the existing contract.
# 4. STRICT PRESERVATION AND GIT SAFETY
Before any changes:
1. Confirm the repository root and branch.
2. Record the actual HEAD.
3. Record `git status --short`.
4. Record `git diff --cached`.
5. Identify tracked, untracked, staged, unstaged, and pre-existing WIP.
6. Identify relevant CapVer code, migrations, ADR sections, and existing SQLite files.
7. Capture a read-only baseline for the scientific record store and every candidate CapVer store.
8. Document the proposed changes before applying them.
Preserve all existing work, including untracked reports and prior ADR changes.
Never execute:
- `git reset`
- `git clean`
- Branch switching
- Forced checkout that overwrites local changes
- Broad deletion or cleanup
- Unrelated refactoring
- `git add`
- Commit creation or amendment
- Push or pull
Do not overwrite existing SQLite files or delete local database data.
Do not expose passwords, tokens, or other secrets.
Do not use Docker or container-based workarounds.
Stop before staging, commit, and push.
# 5. PHASE A — HISTORICAL POSTGRESQL DATA PRESERVATION GATE
**This gate must be resolved before creating a new empty persistent CapVer store or executing CapVer writes against it.**
Earlier project reports described PostgreSQL migrations and a previously intended CapVer persistence path. A later report showed that the PostgreSQL server was unreachable. Neither fact establishes whether previous CapVer records exist or whether they were lost.
Do not assume the historical database is empty, deleted, irrelevant, or recoverable.
## 5.1 Investigate available evidence
Inspect:
- Historical engineering reports and saved execution results.
- ADR-023, including §§8.60, 8.62, 8.66, and 8.67.
- The current and historical CapVer migrations.
- Existing database and backup locations accessible through approved mechanisms.
- Existing project documentation that identifies prior PostgreSQL data locations or backups.
- Available PostgreSQL backup artifacts and their provenance.
- Any accessible PostgreSQL instance or approved export mechanism, without changing the source database.
Do not use Docker.
Do not assume that a backup exists merely because a migration was previously applied.
Do not assume that the lack of a reachable PostgreSQL server proves historical data was lost.
## 5.2 If a backup or source database is found
Inspect it read-only.
Determine:
- Whether it contains `cghia_capability_cell_records`.
- Whether records exist.
- Whether relevant migration metadata exists.
- Whether logical identities, lifecycle states, seat identifiers, evidence references, and provenance can be preserved.
- Whether records correspond to the current authorized CapVer contract.
Record the backup's source, timestamp, size, hash, and integrity results when available.
If relevant records exist, determine whether they must be migrated to SQLite. Do not import records until source and target schemas, identity mapping, duplicate handling, and lifecycle behavior are verified.
Preserve the original source and backup.
Any approved data transfer must be idempotent, auditable, and validated using source-to-target counts and logical identities.
## 5.3 Decision outcomes
Use one of the following evidence-supported outcomes:
**A. Verified backup or source contains CapVer records**
Preserve the source. Determine and document whether migration is necessary. Do not start an empty store and ignore existing records.
**B. Verified backup exists and contains no CapVer records**
Document the inspected artifact and evidence. Proceed with an empty store only if no other evidence indicates that records requiring preservation exist.
**C. No backup found**
Search approved, available locations and document the search scope. If historical CapVer records may exist and their fate cannot be established, stop before persistent writes and request the smallest necessary owner decision.
**D. Owner explicitly authorizes starting with an empty CapVer store**
Record the authorization, its scope, and the evidence reviewed. Proceed without claiming that historical PostgreSQL records never existed.
Do not silently interpret the decision to return to SQLite as proof that PostgreSQL data can be discarded.
If this gate cannot be resolved safely, report:
`BLOCKED — HISTORICAL DATA PRESERVATION DECISION REQUIRED`
# 6. PHASE B — INVENTORY EXISTING SQLITE STORES
Perform a targeted, read-only inventory.
Inspect:
- Laravel database configuration.
- `.env` database-related key names without revealing secrets.
- Existing database connections.
- The `scientific_records` connection and its resolved path.
- Existing SQLite files in relevant application storage and database directories.
- Existing CapVer-related SQLite files.
- Candidate database schemas, tables, indexes, constraints, and record counts.
- Migration status where it can be obtained safely.
- File sizes, timestamps, and integrity checks.
- Whether any candidate store contains records that must be preserved.
For each candidate store, document:
- Exact path.
- Existence and accessibility.
- SQLite integrity-check result.
- Relevant tables and schema.
- Presence or absence of `cghia_capability_cell_records`.
- Migration compatibility.
- Existing logical identities and lifecycle states.
- Suitability for reuse.
Do not scan or dump unrelated personal data.
Do not assume a file is disposable because it is untracked, unfamiliar, or absent from current configuration.
Do not write to any candidate store during discovery.
# 7. PHASE C — ROOT-CAUSE ANALYSIS
Trace the actual code path for:
- `CghiaCapabilityCell`
- `EloquentCapabilityStoreRepository`
- `CapVerSeatVerificationService`
- `CapVerAcceptedSeatCommand`
- `scientific:capver-accepted`
- `AgriculturalIntelligenceServiceProvider`
- Laravel database configuration
- The existing CapVer migration
- Seat authorization catalog
- Transaction, identity, lifecycle, and idempotency behavior
Use the following diagnostic sequence.
## 7.1 Code inspection
Verify:
- Whether `CghiaCapabilityCell::$connection` is set or null.
- Which connection the repository uses for transactions.
- Which connection model queries use.
- Whether command, service, repository, and model resolve to the same intended store.
- Whether the read-back path uses the same store as the write path.
- Whether model table and identity mappings match the actual schema.
## 7.2 Configuration inspection
Inspect:
- `config/database.php`.
- Existing connection definitions.
- `.env` key names without printing secrets.
- Any existing `CAPVER_DB_*` configuration.
- Whether a `capver` connection already exists.
- The actual resolved database path.
- Path resolution behavior across application working directories.
Do not assume a `capver` connection exists before verifying it.
## 7.3 Runtime inspection
Verify:
- Actual PHP executable and version.
- Required SQLite extensions.
- Laravel application boot.
- Effective connection name and driver.
- Resolved database path.
- Successful connection opening.
- Parent-directory availability and file permissions.
Use the project's approved host runtime. Do not use Docker.
## 7.4 Evidence-supported hypotheses
Form a hypothesis only after collecting relevant code, configuration, or runtime evidence.
Examples, only when supported:
- The model uses the default connection instead of the intended CapVer connection.
- Repository transactions use a different connection from model queries.
- The migration has not been applied to the selected store.
- The migration is incompatible with the actual SQLite runtime.
- The resolved database path changes according to the working directory.
- The read-back path queries a different store from the write path.
Test the hypothesis with the smallest discriminating test. Implement a scoped repair, rerun the test, and verify the persisted outcome.
Do not invent hypotheses, force an arbitrary number of hypotheses, or repeat tests without a specific reason.
# 8. PHASE D — SQLITE MIGRATION COMPATIBILITY GATE
**Do not apply the existing CapVer migration until its complete definition has been reviewed for SQLite compatibility.**
Read the complete file:
`backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php`
Inspect:
- All column types.
- Defaults and nullability.
- Primary and foreign keys.
- Unique constraints.
- Partial indexes and their predicates.
- CHECK constraints.
- Generated expressions.
- Raw SQL.
- JSON-related types or functions.
- PostgreSQL-specific syntax and assumptions.
- Any driver-specific schema behavior.
Verify the actual installed Laravel version and SQLite runtime version.
Do not infer compatibility from feature names alone.
SQLite supports partial indexes in modern versions, but compatibility depends on the actual syntax, SQLite version, and Laravel migration implementation.
Do not assume PostgreSQL `JSONB`, JSON-specific functions, CHECK expressions, generated columns, or index definitions will behave identically in SQLite.
## 8.1 Isolated migration test
Before modifying the persistent CapVer store:
1. Create an isolated temporary SQLite test database.
2. Run the proposed migration against that database using the actual project runtime.
3. Inspect the resulting schema and indexes.
4. Test the unique logical identity contract.
5. Test the `CURRENT` lifecycle uniqueness rules.
6. Test relevant repository operations.
7. Verify that migration failure does not leave an incorrectly reported successful schema state.
Never use the production CapVer database as a destructive migration test target.
## 8.2 Compatibility decision
- If the existing migration is compatible, reuse it.
- If incompatible, identify the exact incompatibility and choose the narrowest safe architecture.
- Consider a driver-aware migration or a separate SQLite-specific migration only after evaluating migration ordering, schema parity, historical PostgreSQL behavior, and long-term maintenance.
- Do not silently rewrite a migration that may already have been applied to PostgreSQL in a way that changes its historical meaning.
- Do not create a conflicting or parallel schema.
- If compatibility cannot be established safely, stop before persistent migration and report the exact blocker.
# 9. PHASE E — SELECT AND CONFIGURE THE PERSISTENT CAPVER SQLITE STORE
First reuse an existing compatible CapVer SQLite store if one exists.
If no suitable store exists and the historical-data preservation gate is satisfied, use the following preferred path, provided it conforms to the actual project conventions:
`backend/storage/app/capver.sqlite`
Alternative paths may be used only when the repository's architecture or storage conventions justify them:
- `backend/storage/app/capver/capver.sqlite`
- `backend/database/capver.sqlite`
The final path must be explicitly selected and documented.
Never use these as the persistent production store:
- Test-only directories such as `storage/framework/testing/`.
- `/tmp/`.
- SQLite `:memory:`.
- Any ephemeral location that does not preserve data across application restarts.
## 9.1 Connection configuration
If a dedicated connection is required:
- Register a narrowly scoped CapVer connection.
- Resolve the database path deterministically.
- Ensure the parent directory exists where appropriate.
- Verify write permissions.
- Bind the model and repository to the same connection.
- Ensure transaction handling and read-back use the same database.
- Keep the global default connection unchanged unless a demonstrated architectural necessity requires otherwise.
Do not route CapVer writes into:
`backend/storage/app/scientific_record_store.sqlite`
Verify the actual resolved path at runtime before every write-capable operation.
# 10. PHASE F — PROTECT SQLITE DATABASE FILES FROM GIT
Inspect the current:
`backend/.gitignore`
Also inspect applicable parent ignore rules.
Before or immediately after creating a persistent CapVer database:
1. Determine whether the actual CapVer database path is already ignored.
2. Determine whether local CapVer backup artifacts are protected.
3. Add only the narrowest required ignore rules if missing.
4. Preserve all existing `.gitignore` content.
5. Do not broadly ignore unrelated database or application files.
6. Verify behavior using Git's ignore-checking commands.
7. Inspect `git status --short`.
Ensure that the production SQLite database, local backups, and temporary migration artifacts are not accidentally staged or committed.
An ignore rule is not a substitute for checking actual Git status.
Do not stage or commit anything.
# 11. PHASE G — BACKUP AND RECOVERY SAFETY GATE
Before any required schema-changing operation on a persistent CapVer database:
1. Confirm the exact database path and connection.
2. Confirm the target is the intended CapVer store.
3. Record the current schema, migration status, and relevant record counts.
4. Use a consistent SQLite backup mechanism appropriate for the database's current journal mode and active connections.
5. Account for WAL and journal files when applicable.
6. Prefer SQLite's supported backup mechanism over blindly copying an active database file.
7. Store the backup in an approved persistent location outside temporary test directories.
8. Verify the artifact exists and is non-empty.
9. Record its path, size, timestamp, and SHA-256.
10. Open and inspect the backup.
11. Run an integrity check on the backup.
12. Where feasible, test restoration in an isolated location without overwriting the source.
Do not claim a backup succeeded merely because a file was copied or a hash was computed.
If a consistent backup cannot be established, do not apply the persistent schema change.
Never automatically:
- Drop a table.
- Truncate records.
- Reset or recreate the database.
- Delete migration-history rows.
- Restore a backup over the source database without a documented recovery assessment.
If a migration fails partially, stop further writes, preserve evidence, inspect the schema and migration state, and determine the least destructive recovery strategy.
# 12. PHASE H — VERIFY THE CONNECTION BEFORE WRITES
Before each migration or CapVer write-capable operation, verify:
- Actual connection name.
- Driver.
- Resolved database path.
- Successful PDO connection.
- Expected target identity.
- Confirmation that the target is not the scientific record store.
- Schema and migration readiness.
Use the actual connection name and runtime.
An example diagnostic command, run from the `backend` directory after the connection has been implemented and verified, may use Laravel Tinker:
```php
$conn = DB::connection('ACTUAL_CAPVER_CONNECTION');
$conn->getPdo();
echo 'Driver: ' . $conn->getDriverName() . PHP_EOL;
echo 'Database: ' . $conn->getDatabaseName() . PHP_EOL;
echo 'Path: ' . $conn->getConfig('database') . PHP_EOL;
```
Replace the placeholder with the actual verified connection name. Do not assume `capver` exists before creating or confirming it.
If the project does not provide the relevant Tinker command, use an approved equivalent.
Never print secrets.
If the effective connection or resolved path is incorrect, stop before writing.
# 13. PHASE I — PRE-RUN BASELINE
Before the first persistent CapVer execution, capture:
- Actual database path and file size.
- Connection name and driver.
- SQLite integrity-check result.
- Table schema and indexes.
- Migration status.
- Total CapVer record count.
- Distinct authorized seat identities where supported by the real schema.
- Relevant logical record identities and lifecycle states.
- Existing records that must be preserved.
- Scientific record store size and integrity baseline where feasible.
Discover actual column names before writing SQL.
Do not assume fields such as `seat_id`, `persisted_at`, `capability`, or `decision` exist.
For example, only after confirming the schema, obtain the total record count and distinct seat identities using the real column names. Save sanitized outputs for comparison with post-run evidence.
# 14. PHASE J — IMPLEMENT THE PERSISTENCE REPAIR
Implement the smallest correct changes required to make CapVer use the intended persistent SQLite store.
Potential changes, only when supported by evidence:
- Add a dedicated CapVer connection.
- Bind `CghiaCapabilityCell` to that connection.
- Ensure repository queries and transactions use the same connection.
- Fix path resolution and directory creation.
- Apply the existing compatible migration.
- Add a separate SQLite-specific migration only if the compatibility review establishes that it is necessary and safe.
- Repair schema/model mismatches.
- Correct transaction and read-back behavior.
- Add focused regression tests.
For every changed file, document:
- The confirmed defect.
- The reason the change is necessary.
- The architectural rationale.
- The relevant regression test.
Do not perform unrelated refactoring.
Do not weaken authorization, uniqueness, validation, lifecycle, or database safety guards to make tests pass.
# 15. PHASE K — AUTHORIZATION BOUNDARIES
The intended seats are:
- GBIF — `G5-04`
- Organic Eprints — `G1-02`
- BHL — `G6-20`
- AGRIS — `G1-01`
- Dr. Duke — `G1-17`
Inspect the current authoritative owner decisions, ADR-023, and the actual authorization catalog.
ADR-023 §8.66 records scoped authorization for `G5-04`; it is not blanket authorization for unrelated seats.
Process only seats authorized by the current authoritative decisions and code.
`G6-06` / `dr_duke_g6` must remain excluded unless a separate valid owner decision already authorizes it.
Do not invent authorization or broaden the authorized seat list.
Report the result for each seat individually.
# 16. PHASE L — EXECUTE REAL CAPVER PERSISTENCE
After the historical-data, connection, schema, migration, backup, and authorization gates pass:
1. Capture the pre-run baseline.
2. Verify the actual CapVer connection and path.
3. Verify the scientific record store is not the target.
4. Execute the real `scientific:capver-accepted` command.
5. Record the actual command, sanitized output, and exit code.
6. Verify transaction completion.
7. Read persisted cells through the actual application repository.
8. Independently inspect corresponding SQLite records through read-only queries.
9. Validate logical identity, seat, capability contract, decision state, evidence references, lifecycle, and provenance against the actual contract.
10. Confirm records remain available after the command process exits.
11. Capture post-run counts and compare them with the baseline.
12. Confirm no unintended tables, duplicate records, or unrelated changes were introduced.
13. Verify the scientific record store remains unchanged.
A zero exit code alone does not prove persistence.
A fake repository, in-memory store, or unit test alone does not prove persistence.
A table existing without correct records does not prove successful CapVer execution.
# 17. PHASE M — PROVE DURABLE IDEMPOTENCY
After the first successful run:
1. Preserve the database state.
2. Verify the effective connection and path again.
3. Run the same authorized operation a second time without deleting or resetting records.
4. Record the actual output and exit code.
5. Verify that already-current records return `ALREADY_CURRENT` or the exact equivalent defined by the existing contract.
6. Read the records back again.
7. Compare logical identities and counts before and after.
8. Confirm no unintended duplicate logical capability cells were created.
9. Confirm already-current records were not unnecessarily rewritten or assigned incorrect lifecycle states.
10. Verify the second run used persisted state rather than transient memory.
If the command reports `ALREADY_CURRENT` while the corresponding record is absent, treat the workflow as failed.
Do not claim a stronger guarantee than the evidence supports.
# 18. PHASE N — TESTING
Run tests in this order:
1. Focused CapVer unit tests.
2. Connection and model-binding regression tests.
3. Migration compatibility tests on an isolated SQLite database.
4. Schema and uniqueness tests.
5. Real repository persistence integration tests on an isolated SQLite database.
6. Tests that prove CapVer does not write to the scientific record store.
7. Transaction and persistence-failure tests.
8. Repeated-run idempotency tests.
9. Authorization tests, including rejection of `G6-06`.
10. Relevant broader regression tests if the environment supports them.
Run the actual CapVer command against the persistent store only after all safety gates pass.
Distinguish clearly between fake-repository unit tests, isolated SQLite integration tests, and actual persistent-store verification.
Do not run destructive tests against the persistent CapVer database.
If tests cannot run, record the exact blocker and continue other safe verification.
# 19. PHASE O — ADR-023 DOCUMENTATION
Inspect ADR-023 before modifying it.
Historical PostgreSQL authorization is recorded in §8.67. Returning to SQLite for CapVer does not erase that history.
If the verified SQLite design requires an architectural amendment:
- Add a narrowly scoped, dated section at the next genuinely unused section number.
- Use §8.68 only if it is actually unused.
- Preserve existing historical sections and their context.
- Document the dedicated CapVer SQLite store and its exact path.
- Document the reason for the chosen architecture.
- Explicitly distinguish CapVer persistence from the scientific record store.
- Document the PostgreSQL data-preservation investigation and its outcome.
- Document migration compatibility and any driver-specific strategy.
- Document backup and recovery requirements.
- Document the connection binding, identity contract, and idempotency behavior.
- Include only verification results supported by actual evidence.
- Do not claim successful persistence before real execution proves it.
If no ADR amendment is necessary, document why.
# 20. STRICTLY PROHIBITED OPERATIONS
You must not:
- Use Docker.
- Run `git add`.
- Stage files.
- Create, amend, or rewrite commits.
- Push or pull.
- Change branches.
- Reset HEAD.
- Clean the working tree.
- Delete or overwrite existing WIP.
- Drop, truncate, reset, or recreate a persistent database as a routine fix.
- Delete migration-history rows to force a migration.
- Discard potentially existing PostgreSQL CapVer data without an explicit, documented basis.
- Overwrite the scientific record store.
- Route CapVer writes into the scientific record store.
- Expose credentials.
- Enable `G6-06` without valid authorization.
- Expand the task into unrelated scientific source onboarding, harvesting, or archive migration.
Stop before staging, commit, and push.
# 21. ACCEPTANCE CRITERIA
The task is complete only when all applicable criteria are supported by actual evidence.
## Historical data preservation
- [ ] Historical PostgreSQL CapVer data and backup evidence investigated.
- [ ] Fate of potentially existing records documented.
- [ ] Any required owner decision obtained before discarding or ignoring potentially important records.
- [ ] No unsupported claim that historical records never existed.
## Repository and safety
- [ ] Initial and final Git state recorded.
- [ ] Existing WIP preserved.
- [ ] No prohibited Git operation performed.
- [ ] No Docker used.
- [ ] No secrets exposed.
## SQLite architecture
- [ ] Existing SQLite stores inventoried.
- [ ] Correct CapVer store identified.
- [ ] Dedicated path documented if a new store is needed.
- [ ] Scientific record store remains separate and unchanged.
- [ ] Connection and path verified at runtime.
- [ ] Migration compatibility established.
- [ ] Backup verified before any required persistent schema change.
- [ ] Git ignore behavior verified.
## Persistence and idempotency
- [ ] Real CapVer command executed.
- [ ] Only authorized seats processed.
- [ ] Persisted records verified in SQLite.
- [ ] Real repository read-back succeeded.
- [ ] Records survive command-process termination.
- [ ] Second run returns the documented idempotent result.
- [ ] No unintended duplicate logical records.
- [ ] Before/after evidence captured.
- [ ] Scientific record store remains unchanged.
## Testing and documentation
- [ ] Focused regression tests passed.
- [ ] Relevant integration tests passed or blockers documented.
- [ ] ADR-023 updated if required.
- [ ] Final acceptance matrix completed.
- [ ] Final Git state reported.
- [ ] No staging, commit, or push occurred.
Do not mark a criterion as passed merely because code was changed or unit tests passed.
# 22. BLOCKER POLICY
If full completion is impossible, do not conceal the blocker or claim success.
Report:
- The exact failed acceptance criterion.
- The root cause supported by evidence.
- The actual command and exit code.
- Relevant sanitized output.
- Historical data and backup status.
- The actual SQLite path and connection state.
- Schema and migration compatibility status.
- Files changed and reasons.
- Tests passed and failed.
- The smallest remaining action required.
Use one explicit final status:
- `COMPLETE — SQLITE CAPVER PERSISTENCE AND IDEMPOTENCY VERIFIED`
- `PARTIALLY COMPLETE — IMPLEMENTATION DONE, VERIFICATION INCOMPLETE`
- `BLOCKED — HISTORICAL DATA PRESERVATION DECISION REQUIRED`
- `BLOCKED — SQLITE STORE SELECTION`
- `BLOCKED — SQLITE CONNECTION OR PATH`
- `BLOCKED — SCHEMA OR MIGRATION`
- `BLOCKED — BACKUP OR RECOVERY`
- `BLOCKED — AUTHORIZATION`
- `BLOCKED — ENVIRONMENT`
# 23. FINAL ARABIC ENGINEERING REPORT
Produce one comprehensive Arabic engineering report containing:
1. Executive summary and final status.
2. Repository, branch, HEAD, and WIP baseline.
3. Historical PostgreSQL data-preservation investigation.
4. SQLite store inventory and selected CapVer store.
5. Root-cause analysis.
6. Effective connection and database path evidence.
7. Scientific record store isolation evidence.
8. Migration compatibility review.
9. Backup artifact details, SHA-256, and verification results where applicable.
10. Files changed and rationale.
11. Actual commands, sanitized outputs, and exit codes.
12. Real persisted records and repository read-back evidence.
13. First-run and second-run idempotency results.
14. Before/after counts and duplicate checks.
15. Test commands, counts, assertions, and results.
16. Seat authorization matrix.
17. ADR changes, if any.
18. Final acceptance matrix.
19. Remaining risks and blockers.
20. Final Git state and explicit confirmation that no staging, commit, or push occurred.
Clearly distinguish observed facts, historical evidence, assumptions, and unverified claims.
---
# FINAL EXECUTION DIRECTIVE
Begin from the actual repository and preserve the current working tree.
Investigate the fate of potentially existing PostgreSQL CapVer data before starting an empty store. Inventory existing SQLite databases. Keep the scientific record store untouched. Verify full migration compatibility with the actual SQLite runtime before applying any schema change.
Reuse an existing compatible CapVer store where possible. Otherwise, implement a dedicated durable SQLite store with correct connection, model, repository, transaction, migration, and read-back behavior.
Verify the actual runtime connection and path, execute the authorized CapVer command, prove real persistence, repeat the operation, establish idempotency, and run relevant regression tests.
Use broad engineering autonomy to complete safe, authorized work. Do not stop at a plan when implementation is feasible. Do not invent evidence or bypass preservation gates.
**Complete the authorized CapVer SQLite persistence repair and verification, preserve historical data and all existing WIP, and stop before staging, commit, and push.**
