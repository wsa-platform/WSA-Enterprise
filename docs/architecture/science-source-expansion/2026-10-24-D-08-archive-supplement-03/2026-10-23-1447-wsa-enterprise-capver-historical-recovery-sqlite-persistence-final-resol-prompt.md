<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18046). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 2:47 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18047; it is archived once. -->

---
# WSA-Enterprise — CapVer Historical Recovery & SQLite Persistence Final Resolution
## One-Pass Autonomous Engineering Execution — Recover, Implement, Verify, Report

**Repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications` — verify actual state without switching branches.  
**Architecture authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Execution mode:** Autonomous Senior Software Engineer, Solution Architect, Database Engineer, and Test Engineer.  
**Final boundary:** Stop before Git staging, commit, or push.

# 1. MISSION

Resolve the CapVer persistence problem from end to end.

Your objective is to:

1. Recover the four historical Capability cells if the original Docker PostgreSQL data is available.
2. Preserve the original historical records, evidence, provenance, identity, and lifecycle.
3. Implement a dedicated durable SQLite store for CapVer.
4. Correct Laravel connection, model, repository, migration, and transaction behavior as needed.
5. Import only verified historical records.
6. Verify persistence through independent database read-back, idempotency checks, integrity checks, and relevant regression tests.
7. Document actual findings and changes in Arabic.

Do not return a plan alone. Investigate, implement, test, repair, and verify within the authorized scope. Do not ask for approval for ordinary reversible engineering decisions that follow the existing architecture.

# 2. ESTABLISHED CONTEXT — DO NOT REPEAT PRIOR INVESTIGATIONS WITHOUT REASON

The previous reports establish the following working context. Treat these as recorded findings, not as proof that the source is still accessible:

- Native Windows PostgreSQL was not found in the previous investigation.
- The reported historical writer was Docker PostgreSQL 16.14, container `wsa-enterprise-postgres-1`.
- The associated volume was reported as `wsa-enterprise_postgres_data`, approximately 194 MB.
- Four historical cells were reported as `VERIFIED / CURRENT` following writes on 2026-10-22.
- The known dump `backups/wsa_pre_migrate_20261019_215350.dump` predates those writes and does not contain `cghia_capability_cell_records`.
- The report `docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md` is a summary, not a complete record export.
- The migration has previously been tested against isolated SQLite 3.53.4.
- The historical records cannot be considered recovered from summary data alone.
- Regeneration was previously classified as possible but not historically equivalent.
- `backend/storage/app/scientific_record_store.sqlite` must remain unchanged.
- The prior report identified 45 tracked paths modified before that execution. Preserve the actual current working tree and all existing work.

Historical cells to recover:

| Seat | Reported capability_record_id |
|---|---|
| `G1-02` | `cap-rec-r11-20261018-G1-02-oai_pmh` |
| `G6-20` | `cap-rec-r11-20261018-G6-20-api` |
| `G1-01` | `cap-rec-r12-20261018-G1-01-bulk` |
| `G1-17` | `cap-rec-r12-20261018-G1-17-static` |

These identifiers are search clues, not proof that the rows currently exist.

# 3. AUTONOMOUS ENGINEERING AUTHORITY

You may independently:

- Inspect source code, configuration, migrations, architecture decisions, tests, logs, and Git history.
- Edit source code, tests, configuration, and documentation.
- Design and implement the dedicated CapVer persistence architecture.
- Fix verified defects and perform necessary focused refactoring.
- Run safe local commands, static analysis, isolated database tests, and relevant regression tests.
- Use Docker solely within the explicit recovery authorization in Section 4.
- Create `backend/storage/app/capver.sqlite` after satisfying the conditions in Section 7.
- Add regression tests and append ADR-023 §8.70 if justified by actual findings.
- Produce a complete Arabic report without requiring approval for routine implementation details.

Do not repeat the previous 22 rounds of investigation unless a new result or necessary verification justifies it.

# 4. PHASE A — DOCKER SOURCE DISCOVERY AND PROTECTED RECOVERY

## 4.1 Docker authorization

Docker is explicitly authorized only for:

1. Inspecting the existing container and volume.
2. Starting the existing `wsa-enterprise-postgres-1` container if its identity and safe startup conditions are verified.
3. Connecting to the intended PostgreSQL 16 instance.
4. Reading the historical table using read-only SQL.
5. Exporting verified records.
6. Stopping the container after extraction if appropriate and safe.

Forbidden:

- `docker compose up` or starting the full application stack.
- Creating replacement containers.
- Removing, recreating, pruning, or modifying the existing volume.
- Starting backend, queue, scheduler, Redis, or unrelated containers.
- Executing SQL writes, DDL, migrations, or cleanup against historical PostgreSQL.
- Editing files directly inside the PostgreSQL volume.
- Running `docker system prune`, `docker volume rm`, or equivalent destructive operations.

## 4.2 Inspect before starting

Use read-only inspection commands, including:

```powershell
docker version
docker ps -a --filter "name=wsa-enterprise-postgres" --format "table {{.Names}}\t{{.Status}}\t{{.Image}}"
docker volume ls --filter "name=wsa-enterprise_postgres_data"
docker volume inspect wsa-enterprise_postgres_data
docker inspect wsa-enterprise-postgres-1
```

Record the actual command results and exit codes.

Verify:

- The Docker engine is available.
- The exact existing container is present.
- The expected image and PostgreSQL version are identified.
- The container's actual volume mount points to `wsa-enterprise_postgres_data`.
- The volume is not merely similarly named or associated with a different environment.
- The database identity and configuration are consistent with the historical writer.
- The existing container is not configured to launch or modify unrelated services.

Do not create a new container if the expected container is absent.

## 4.3 Protect the source data

PostgreSQL may write WAL, checkpoint, recovery, or other runtime data when starting. Therefore, do not equate SQL `SELECT`-only access with zero filesystem writes.

Before starting the container, determine whether a safe and verifiable recovery copy can be made without changing or endangering the original volume.

If the source is stopped, do not copy a live database directory and assume that the copy is valid. Use a database-appropriate, verified recovery method. If a filesystem-level copy is considered, establish that the source is stopped and that the method preserves a consistent PostgreSQL data directory.

Do not perform any operation that could upgrade, initialize, repair, or overwrite the original data directory.

If the original source cannot be protected adequately, do not start it. Complete all safe inspection and report the specific blocker.

If the source can be protected and its identity established, start only the existing authorized container, without recreating it or starting the rest of the stack.

## 4.4 Read-only database access

Connect only after confirming the correct container and database.

Use a read-only transaction or equivalent database-enforced read-only session where supported. Do not rely solely on the intention to issue `SELECT`.

Inspect the actual schema before querying. Verify the existence and structure of `cghia_capability_cell_records`.

Read the four target records and all contract-required fields, including identity, evidence, provenance, lifecycle, freshness, snapshot, timestamps, and any additional actual schema requirements.

If the table or rows are missing, do not fabricate them. Investigate other evidence or backups only where safe and relevant.

Never print credentials or connection secrets.

## 4.5 Recovery artifact

If records are recovered, export them into a JSON recovery artifact in an appropriate non-production recovery directory, such as:

`storage/framework/recovery/capver-historical-recovery.json`

Include:

- Export timestamp.
- Verified source type and database identity without credentials.
- Source verification method.
- Actual record count.
- Original record identifiers.
- All actual contract-required persisted fields.
- Evidence references and provenance.
- Original lifecycle and relevant timestamps.
- Per-record integrity hashes where canonical serialization is well-defined.
- Missing or uncertain fields, if any.

Compute SHA-256 for the completed artifact.

The file hash proves artifact integrity relative to the recorded hash; it does not independently prove authenticity or correctness of the source.

Do not create a purported complete recovery artifact from the summary report alone.

Once extraction is complete and verified, stop the container if appropriate. Do not stop or disrupt other unrelated workloads.

# 5. PHASE B — CONTRACT AND REGENERATION ANALYSIS

Inspect:

- `CapVerSeatVerificationService`, especially `seatSpec()`.
- The `scientific:capver-accepted` command.
- The model and repository for `cghia_capability_cell_records`.
- The migration `2026_10_09_140000_create_cghia_capability_cell_records_table.php`.
- Related factories, source adapters, tests, and consumers.

Confirm the actual persisted schema rather than assuming columns exist because they appear in a proposed JSON format.

The previously reported natural key for a `CURRENT` cell is:

`adr_id + canonical_identity_id + dimension_family + dimension_code + seat_override_applied`

Verify this against the actual migration, repository, and domain contract.

Determine:

1. How `capability_record_id` is generated.
2. Whether it is fixed, deterministic, or random.
3. Which inputs affect record identity and payload.
4. Whether evidence, freshness, snapshots, provenance, or timestamps change on regeneration.
5. Whether the historical source inputs and versions remain available.
6. Whether any code, rows, files, or external artifacts refer to the original identifiers.
7. Whether current regeneration would produce historically equivalent records.
8. Whether the acceptance command has external side effects or writes to other stores.
9. Why `G5-04` appears in the current batch but not in the reported historical set.

Use one of these explicit conclusions:

- `REGENERATION VERIFIED EQUIVALENT`
- `REGENERATION POSSIBLE BUT NOT EQUIVALENT`
- `REGENERATION UNSAFE`
- `REGENERATION UNVERIFIABLE`

A fixed ID alone does not prove historical equivalence.

Do not run the acceptance command against the persistent store during exploratory analysis.

# 6. PHASE C — IMPLEMENT THE DEDICATED CAPVER STORE

Preferred database path:

`backend/storage/app/capver.sqlite`

Use a different path only if the actual approved architecture requires it.

Implement and verify:

1. A dedicated Laravel database connection named `capver`.
2. Correct model connection selection for `CghiaCapabilityCell`.
3. Repository and transaction alignment with the same connection.
4. Correct migration targeting.
5. Preservation of the real contract, natural key, unique constraints, and partial unique index semantics.
6. Explicit conflict detection and safe idempotency.
7. Proper handling of evidence, provenance, freshness, lifecycle, and timestamps.
8. Narrow `.gitignore` rules that protect the persistent local database.
9. Tests proving that CapVer does not accidentally use the default PostgreSQL connection or the scientific store.
10. Appropriate database backup and restoration procedures.

Do not silently alter the approved domain contract merely to make the migration easier.

If a schema change is necessary, explain why, update the appropriate tests, and document the decision in a new ADR section. Preserve existing ADR sections.

# 7. PHASE D — MIGRATION, RECOVERY AND IMPORT GATES

Before writing to the persistent SQLite store:

1. Inspect the actual migration and all schema requirements.
2. Test the migration in an isolated database.
3. Verify required columns, types, nullability, defaults, unique indexes, partial indexes, foreign keys, and CHECK constraints where applicable.
4. Verify rollback behavior in the isolated database.
5. Establish the source recovery result and classify the data.

### If historical records are recovered

- Verify all required fields and evidence references.
- Import only the verified records.
- Preserve their original identifiers, evidence, provenance, and timestamps.
- Detect conflicts explicitly.
- Never overwrite an incompatible existing row silently.
- Verify the imported rows through independent read-back.

### If historical records are not recovered

- Do not claim historical recovery.
- Do not fabricate missing fields.
- Do not treat an empty SQLite file as a completed resolution.
- Continue implementing and testing the dedicated connection and migration in an isolated database where safe.
- Do not create or initialize the operational `capver.sqlite` as an empty substitute without explicit owner approval.

The current authorization to create `capver.sqlite` applies only after the historical recovery gate has been resolved. If the source is unavailable and the store would be initialized empty, the explicit no-empty-store prohibition in Section 1.3 takes precedence.

# 8. PHASE E — PERSISTENCE AND REGRESSION VERIFICATION

Use an isolated test database such as:

`backend/storage/framework/testing/migration_test.sqlite`

Run relevant tests for:

- Migration and schema integrity.
- Unique `capability_record_id`.
- Correct partial unique index behavior for `CURRENT` records.
- Conflict handling.
- Required fields and invalid payload rejection.
- Dedicated connection selection.
- Repository write/read behavior.
- Transaction behavior.
- Recovery import idempotency.
- Independent read-back.
- SQLite integrity checks.
- Backup and restoration, where implemented.
- Regression safety for scientific records and related research flows.

Use actual schema-valid fixtures. Do not use synthetic records to claim historical recovery.

Record exact commands, exit codes, test counts, and whether each test used mocks, an isolated database, or the persistent runtime.

## 8.1 Acceptance command

`scientific:capver-accepted` is authorized only after:

1. The intended target connection has been verified.
2. The recovery decision is documented.
3. Its write and external side effects are understood.
4. The command has been tested in an isolated environment where feasible.
5. Existing recovered records are protected against silent replacement.
6. The persistent operation is safe and idempotent.

After any authorized persistent run, read the resulting records independently from the intended database.

Do not claim that a command's `PERSISTED` output proves durability without independent read-back.

# 9. PHASE F — ADR AND GIT SAFETY

Review ADR-023 numbering and existing content before editing.

You are authorized to append ADR-023 §8.70 only if the actual findings or implementation justify a new architectural record.

Do not edit or delete ADR-023 §§8.60–8.69. Preserve all previous decisions and the historical audit trail.

Inspect the current Git state before editing. Preserve every pre-existing user modification.

Absolutely forbidden:

- `git add` or staging.
- Commit, push, pull, merge, rebase, cherry-pick.
- Branch switching.
- `git reset`, `git clean`, stash, or destructive checkout/restore.
- Deleting or recreating existing databases.
- Modifying `scientific_record_store.sqlite`.
- Modifying or deleting `wsa-enterprise_postgres_data`.
- Any write, migration, or DDL against the historical PostgreSQL source.
- Credential disclosure.

Do not clean the working tree to simplify your diff.

# 10. COMPLETION CRITERIA

Do not declare complete merely because the migration passes or the SQLite file exists.

A historical recovery and persistence resolution requires verified evidence of:

1. Correct historical source identification.
2. Recovery of the actual four historical records, or an explicit statement that they remain unrecovered.
3. Correct dedicated Laravel connection.
4. Correct schema and constraints.
5. Safe import and preservation of identity and provenance.
6. Independent read-back from the intended persistent store.
7. Idempotent repeated execution.
8. SQLite integrity and a verified backup/restore procedure.
9. No modification to the scientific store.
10. Passing relevant regression tests.
11. Accurate ADR and report updates.

If the source is unavailable, complete all independent safe engineering work but do not claim full historical recovery. Do not silently replace the missing records with newly generated ones.

# 11. FINAL ARABIC REPORT — ONE RESPONSE

Provide one comprehensive Arabic report containing:

1. Final status.
2. Actual branch, HEAD, and initial/final working-tree state.
3. Docker engine status, existing container identity, volume mapping, and source verification.
4. Whether the original four records were found and recovered.
5. A per-seat matrix for `G1-02`, `G6-20`, `G1-01`, and `G1-17`.
6. Recovery artifact path, record count, and SHA-256, if created.
7. Actual SQLite path and Laravel connection verification.
8. Files changed and concise diff summary.
9. Exact test commands, exit codes, and results.
10. Independent read-back, idempotency, integrity, and backup/restore results.
11. Any unresolved blocker and the exact reason.
12. Confirmation that no Git staging, commit, or push occurred and that the scientific store was preserved.

Use exactly one applicable final status:

- `RESOLVED — HISTORICAL DATA RECOVERED AND PERSISTENCE VERIFIED`
- `RESOLVED — NEW PERSISTENCE VERIFIED, HISTORICAL DATA NOT RECOVERED`
- `PARTIALLY RESOLVED — RECOVERY OR VERIFICATION INCOMPLETE`
- `BLOCKED — HISTORICAL DATA SOURCE UNAVAILABLE`
- `BLOCKED — OWNER DECISION REQUIRED`

Do not use a `RESOLVED` status unless all criteria for that specific status are verified.

# START NOW

Inspect the current repository state and proceed directly with the authorized Docker source discovery.

Do not repeat the previous investigations without reason. Do not provide another plan-only response. Recover the original data if possible, implement the dedicated CapVer persistence architecture, run the relevant tests, repair any verified defects, and report the actual results.

Stop before staging, commit, or push.
