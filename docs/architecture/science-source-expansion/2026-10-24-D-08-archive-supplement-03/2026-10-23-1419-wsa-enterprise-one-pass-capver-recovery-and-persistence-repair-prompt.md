<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 18018). Message timestamp as recorded in the conversation: Friday, Oct 23, 2026, 2:19 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 18019, 18021; it is archived once. -->

---
# WSA-Enterprise — ONE-PASS CAPVER RECOVERY AND PERSISTENCE REPAIR
## Senior Engineer Execution Mandate — Evidence First, Recover, Implement, Verify
**Repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications` — verify actual state.  
**Architecture authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Mission:** Resolve the historical CapVer persistence problem with the fewest execution cycles. Investigate all viable recovery paths before attempting PostgreSQL discovery, implement the safest verified solution, run real tests, and report the actual outcome in Arabic.
You are authorized to act as the senior software engineer, architect, database engineer, and test engineer. Read, investigate, design, edit code, run permitted commands, repair defects, and verify the result. Do not respond with a plan alone. Do not ask routine implementation questions. Preserve all existing user work.
## PHASE 0 — READ THE HISTORICAL REPORT FIRST
Before searching for PostgreSQL, read the entire file:
`docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md`
Also inspect the relevant implementation, migration, tests, and ADR-023.
For each historically reported cell — `G1-02`, `G6-20`, `G1-01`, `G1-17` — establish whether the report contains the actual record data or only a summary.
Inspect the real schema and contract before deciding which fields are required. Check, at minimum:
- `seat_id` and the exact logical/natural identity.
- `capability_record_id`.
- The full `capability` contract or payload.
- `decision`.
- Complete `evidence` and all referenced evidence identifiers.
- `lifecycle`.
- `provenance`.
- `persisted_at` and any other required timestamps.
- Any additional non-nullable, unique, relational, or application-required fields in the actual schema.
- Whether evidence and provenance refer to retrievable artifacts, versions, source responses, or other external records.
Do not assume the example field list is the entire schema.
Classify the report:
**A — Complete recovery source:** all required persisted fields and their meanings can be verified, including identity, evidence, provenance, lifecycle, and timestamps. All four records must pass the actual contract.
**B — Partial recovery source:** some required values are missing or unverifiable. List the exact gaps per record and continue searching for those values.
**C — Summary only:** the report cannot reconstruct the actual records. Continue with regeneration analysis and PostgreSQL/backup discovery.
A report containing seven field names is not sufficient by itself. Validate the values, types, semantics, required columns, uniqueness constraints, provenance, and evidence references against the real contract.
Do not create or import records yet.
## PHASE 1 — DETERMINE WHETHER SAFE REGENERATION IS POSSIBLE
Inspect the actual `CapVerSeatVerificationService`, the `scientific:capver-accepted` command, related factories, repositories, persistence code, migrations, and tests.
Determine:
1. Exactly how `capability_record_id` is generated.
2. Whether it is deterministic or random, and which input fields contribute to it.
3. Whether it depends on timestamps, generated UUIDs, evidence ordering, serialization, source response content, or environment-specific values.
4. Whether the current implementation would reproduce the same IDs and payloads from the same historical inputs.
5. Whether the relevant source data, artifacts, configurations, and versions from 2026-10-22 remain available.
6. Whether decisions depend on current time, expiry/freshness rules, source availability, changing evidence, or other dynamic inputs.
7. Whether database rows, foreign keys, code, serialized documents, logs, or external artifacts reference the old `capability_record_id` values.
8. Whether `git log` and relevant repository history show changes affecting the generating code or input data since 2026-10-22.
Inspect the actual repository paths; do not assume `source_data/` is the only relevant location.
Produce a short evidence-based determination:
- `REGENERATION VERIFIED SAFE`
- `REGENERATION POSSIBLE BUT NOT EQUIVALENT`
- `REGENERATION UNSAFE`
- `REGENERATION UNVERIFIABLE`
Regeneration is safe only if it preserves the required identity, evidence, provenance, lifecycle semantics, and dependent references, or if the architecture explicitly permits replacement and all dependencies are accounted for.
A deterministic ID alone is not proof of equivalent records. New UUIDs do not prove that old records are disposable. Do not run the production acceptance command as an exploratory test.
If regeneration is demonstrably safe, prepare the regeneration plan and test it using an isolated test environment and historical inputs where available. Do not silently replace the historical records or declare them recovered merely because a new execution succeeds.
## PHASE 1.1 — DIAGNOSE POSTGRESQL BEFORE DECLARING IT UNAVAILABLE
Use safe, read-only diagnostics first. On Windows, inspect:
- PostgreSQL services with `Get-Service`.
- Port listeners with `Get-NetTCPConnection -LocalPort 5432`.
- Relevant PostgreSQL processes.
- `pg_ctl` and PostgreSQL installation locations, if present.
- PostgreSQL-related Windows application events and available PostgreSQL logs.
- The actual configured host, port, database name, connection name, and environment source, without printing credentials.
- Whether the configured endpoint is actually the historical writer.
Use commands compatible with the current Windows environment and record their real output.
Docker is prohibited. Do not start, stop, or modify any Docker container or access Docker-managed storage through unsupported or unsafe means.
If a PostgreSQL Windows service is found stopped, first identify its exact instance, data directory, service account, database identity, and whether starting it could change or upgrade the data directory. Starting the correct local service is permitted only if it is clearly the intended historical instance and is safe under the existing setup. If those conditions cannot be established, do not start it; report the evidence and blocker.
Do not modify firewall rules, `pg_hba.conf`, ports, authentication, or other security settings.
If a service is started safely, verify its endpoint, identify the database, and perform read-only queries only. Never assume that a listening PostgreSQL server contains the historical records.
## PHASE 1.2 — SEARCH SPECIFIC RECOVERY LOCATIONS
Inspect these paths if they exist:
- `G:\WSA-Enterprise\backups`
- `G:\WSA-Enterprise\backend\storage\app`
- `G:\WSA-Enterprise-archive-reconcile\backups`
- `G:\WSA-Enterprise-push-isolated\backups`
- `G:\New folder (3)`
- `$env:USERPROFILE\Desktop`
- `$env:USERPROFILE\Downloads`
- `$env:USERPROFILE\Documents`
Search for relevant `.dump`, `.sql.gz`, `.sqlite`, `.backup`, `.sql`, and other actual backup formats. Record full paths, sizes, and timestamps. Prioritize artifacts created after 2026-10-19, but do not exclude older files that could contain relevant data.
Do not search or dump secret stores indiscriminately. Do not expose credentials. Do not overwrite, restore over, delete, or modify original backups.
For each candidate, determine whether it actually contains the required CapVer schema and records. File size, filename, and timestamp are not proof of recoverability.
If a backup is a PostgreSQL dump, inspect it using appropriate read-only/listing tools where possible. Any restoration or import for inspection must occur in a separate isolated environment, never over the original database.
## PHASE 1.3 — CREATE A VERIFIED RECOVERY ARTIFACT
Once a valid recovery source is identified, create a JSON artifact in an appropriate non-production recovery directory, for example:
`storage/framework/recovery/capver-historical-recovery.json`
Use the actual project conventions if a safer location is established.
The artifact should include:
- Export timestamp in ISO 8601 format.
- Source type: `report`, `postgresql`, `backup`, or another explicitly identified type.
- Verified source identity/location, without secrets.
- Verification timestamp and method.
- Expected and actually recovered record counts.
- Per-record data containing all required contract fields.
- Provenance linking each record to the original source.
- A record-level integrity hash where canonical serialization is well-defined.
- Any unresolved field, evidence reference, or identity issue.
Compute and record the SHA-256 hash of the completed artifact using an available, verified tool.
Do not invent missing values, fill unknown fields with plausible defaults, or claim the hash proves authenticity. The hash establishes file integrity relative to the hashed artifact; provenance and source verification establish trust.
If the report is the only source, clearly distinguish reconstructed data from an original persisted record.
Do not commit or stage the recovery artifact.
## PHASE 2 — IMPLEMENT DEDICATED PERSISTENT CAPVER STORAGE
Proceed with historical import only after verifying the recovery data and resolving any contract-critical ambiguities.
Use the intended dedicated store:
`backend/storage/app/capver.sqlite`
unless the existing approved architecture proves a different location is required.
Requirements:
1. Preserve `backend/storage/app/scientific_record_store.sqlite` completely unchanged.
2. Implement a dedicated Laravel database connection for CapVer.
3. Align the model, repository, transactions, commands, and migrations to the dedicated connection.
4. Verify the real migration schema, required columns, types, defaults, nullability, unique indexes, partial unique indexes, foreign keys, and check constraints.
5. Ensure all writes and reads for CapVer consistently use the intended connection.
6. Ensure the migration cannot accidentally target the scientific record store.
7. Use isolated test databases for schema and constraint tests before touching the intended persistent store.
8. Import only verified historical records and preserve their identity, evidence, provenance, timestamps, and lifecycle.
9. Make import idempotent and explicitly detect conflicts. Never overwrite an incompatible existing row silently.
10. Verify `.gitignore` and ensure persistent local database files are not accidentally staged.
11. Implement an appropriate SQLite backup and restore procedure using supported SQLite mechanisms.
12. Verify database integrity and independent read-back after authorized writes.
If the report provides complete records and the contract verifies them, PostgreSQL is not automatically required. If safe regeneration is independently proven, PostgreSQL is not automatically required either. Choose the recovery path with the strongest evidence and lowest data-loss risk.
Do not create an empty destination and report success while the historical rows remain unrecovered.
## PHASE 2.5 — REQUIRED MIGRATION TESTS
Use an isolated database such as:
`backend/storage/framework/testing/migration_test.sqlite`
First inspect the actual migration and schema. Derive expected columns and constraints from the implementation, not from an assumed schema.
Test:
1. Migration up and rollback behavior in the isolated database, where safe.
2. All required columns, types, nullability, and defaults.
3. Unique `capability_record_id` behavior if the contract requires it.
4. Partial unique index enforcement for the actual natural key and `CURRENT` lifecycle semantics.
5. Rejection of conflicting current records.
6. Foreign-key behavior if defined.
7. Check constraints if defined.
8. Required-field validation and malformed payload handling.
9. Correct database connection selection.
10. Idempotent import and conflict handling.
11. Independent persistence read-back and data integrity.
12. Regression safety for the scientific record store.
Use valid fixtures based on the actual schema. Do not use incomplete inserts or hand-written placeholder payloads that bypass application-level invariants.
A test passing against an isolated database does not establish that production persistence is working. Report the evidence type accurately.
## PHASE 3 — FINAL EXECUTION AND REVIEW
Run the relevant tests, inspect the final diff, and verify that no unrelated files or pre-existing user changes were lost.
Update `docs/architecture/ADR-023-scientific-source-expansion.md` only where necessary. Inspect section numbering first. Preserve all historical decisions, including §8.67 and subsequent records; append a new section rather than deleting or silently rewriting history.
Do not run `scientific:capver-accepted` against a persistent target until source recovery or safe regeneration has been proven, the destination connection has been verified, and the operation is safe and idempotent. If the command's external source calls or side effects cannot be safely controlled, test it in isolation first.
## NON-NEGOTIABLE SAFETY RULES
- No Docker.
- No `git add`, staging, commit, push, pull, or branch switching.
- No `git reset`, `git clean`, destructive checkout/restore, or deletion of existing user changes.
- No deleting, truncating, or recreating existing databases.
- No writes, migrations, updates, or deletes against the historical PostgreSQL source.
- No modification of `scientific_record_store.sqlite`.
- No credential disclosure.
- No fabricated historical data or unsupported completion claims.
- No silent architectural changes.
- No stopping after planning if safe implementation work remains.
Inspect the actual Git state first and preserve all existing modifications.
If a hard blocker remains, finish all safe independent tasks, collect concrete evidence, and state exactly what cannot be completed and why. Ask at most one consolidated question only if a genuinely irreversible or owner-only decision is required.
## FINAL RESPONSE — ONE COMPREHENSIVE ARABIC REPORT
Report:
1. Final status.
2. Initial branch, HEAD, and working-tree state.
3. Whether the 2026-10-22 report contained complete records, partial records, or summary only.
4. Regeneration analysis and actual evidence.
5. PostgreSQL diagnosis and exact source/backup findings.
6. Recovery artifact path, record count, and SHA-256 if created.
7. Historical record matrix for `G1-02`, `G6-20`, `G1-01`, and `G1-17`.
8. Actual implementation changes and file paths.
9. Exact test commands, exit codes, counts, and evidence types.
10. Database integrity, independent read-back, idempotency, and backup/restore results.
11. Any remaining blocker and the single next action needed.
12. Confirmation that no commit/push occurred and the scientific record store was preserved.
Choose one final status:
- `RESOLVED — HISTORICAL DATA RECOVERED AND PERSISTENCE VERIFIED`
- `PARTIALLY RESOLVED — RECOVERY OR VERIFICATION INCOMPLETE`
- `BLOCKED — HISTORICAL DATA SOURCE UNAVAILABLE`
- `BLOCKED — VERIFIED OWNER DECISION REQUIRED`
**START NOW. Read the historical report first, then perform the recovery analysis and safe implementation in the same work session. Do not send another plan-only response.**
