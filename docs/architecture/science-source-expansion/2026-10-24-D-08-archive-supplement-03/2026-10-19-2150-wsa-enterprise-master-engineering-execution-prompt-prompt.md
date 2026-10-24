<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17379). Message timestamp as recorded in the conversation: Monday, Oct 19, 2026, 9:50 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17380, 17381; it is archived once. -->

---
# WSA-Enterprise — MASTER ENGINEERING EXECUTION PROMPT
## Full Migration Gap Closure, Architectural Remediation, Programming, Testing, and Evidence-Based Verification
**Repository:** `wsa-platform/WSA-Enterprise`  
**Project path:** `G:\WSA-Enterprise`  
**Application URL:** `http://127.0.0.1:8079/`  
**Primary PostgreSQL container:** `wsa-enterprise-postgres-1`  
**Primary database:** `wsa_enterprise`  
**Primary PostgreSQL volume:** `wsa-enterprise_postgres_data`  
**Architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`
---
# 1. ROLE AND EXECUTION AUTHORIZATION
Act as the project's senior software architect, database engineer, Laravel backend engineer, programmer, implementation engineer, and QA/verification engineer.
The owner authorizes you to investigate, design, implement, test, and verify the necessary repairs for the migration, schema, CapVer, platform administration/RBAC, and scientific persistence gaps identified in this task, within the boundaries of this prompt.
**This is authorization to deliver working, verified engineering results—not merely another audit, plan, or recommendation.**
You are expected to:
- inspect the actual repository and running system
- identify the true causes of the gaps
- preserve existing data and local work
- create and verify a fresh database backup
- repair demonstrated defects
- apply approved and necessary migrations
- test actual persistence behavior
- validate the application after each change
- update ADR-023 with actual results
- produce one comprehensive Arabic final report
Do not ask for repeated approval for actions explicitly authorized here.
Do not invent a requirement just to make the migration count match.
Do not interpret this authorization as permission to delete existing data, overwrite user files, weaken security, or bypass an unresolved architectural decision.
If one issue requires a genuinely unresolved owner decision, isolate it and continue with independent, safe work.
## 1.1 Explicit boundaries
Authorized within this task:
- read-only inspection and diagnostics
- creating a new, uniquely named PostgreSQL backup
- verifying the backup and recording its SHA-256
- implementing necessary, demonstrated code fixes
- applying approved, necessary migrations after all safety gates pass
- running appropriate tests and health checks
- updating ADR-023 after the corresponding results are verified
Not authorized:
- committing or pushing
- destructive database shortcuts
- deleting or overwriting existing user data
- removing or relocating existing WIP
- silently activating an unapproved feature
- creating an unapproved parallel scientific data store
- changing passwords or exposing secrets
- automatically rolling back a partially applied migration
- making unrelated architectural or formatting changes
---
# 2. KNOWN BASELINE — REVERIFY BEFORE ACTING
The previous report stated:
- application health endpoint returned HTTP 200
- seven primary application containers were healthy
- primary PostgreSQL was version 16.14
- the primary database was `wsa_enterprise`
- Laravel Backend connected through host `postgres`
- 63 migration files existed on disk
- 55 migration records existed in PostgreSQL
- eight migrations were pending
- no migration records lacked corresponding files
- `cghia_capability_cell_records` was absent from PostgreSQL
- a substantial scientific SQLite store already existed
- the scientific-source PostgreSQL migration was untracked in Git
- no fresh backup of the current primary PostgreSQL database had been created
Treat these as prior observations. Recheck all facts using the current environment before acting.
The application is reported to work. Preserve its existing behavior.
---
# 3. PROTECT THE REPOSITORY, ENVIRONMENT, AND DATA
## 3.1 Preserve Git and WIP
Inspect:
- `git status --short`
- `git diff --stat`
- staged and unstaged changes
- untracked files
- relevant Git history
Preserve all existing local changes.
Never run:
- `git reset`
- `git clean`
- `git restore`
- destructive checkout commands
- `git stash`
Do not delete, relocate, overwrite, or stage unrelated files.
Do not commit or push.
## 3.2 Protect PostgreSQL
Before any persistent database change:
1. Confirm the actual target container, database, user, PostgreSQL version, and volume.
2. Create a fresh logical backup.
3. verify the archive and SHA-256.
4. establish a safe recovery plan.
5. inspect the exact migration and dependencies.
6. pass the execution gates in this prompt.
Never start a stopped PostgreSQL container that shares the primary data volume.
Never use:
- `migrate:fresh`
- `migrate:refresh`
- `migrate:reset`
- manual migration-record insertion
- manual migration-record deletion to force reruns
- dropping tables to make migrations pass
- disabling constraints to conceal conflicts
- restoring over the primary database as a routine recovery action
If a migration fails, stop and inspect the actual database state before deciding what to do.
---
# 4. PHASE 1 — VERIFY THE ACTUAL ENVIRONMENT
## 4.1 Services and health
Verify the current state of:
- PostgreSQL
- Redis
- Backend
- Frontend
- Nginx
- Queue
- Scheduler
Verify the primary PostgreSQL container and its mounted volume.
Do not start, stop, restart, or recreate containers without a demonstrated need within this authorization.
## 4.2 Laravel configuration
Inspect `.env`, `config/database.php`, configuration caching, and the actual environment loaded by Backend.
Check:
- malformed dotenv entries
- duplicate relevant variables
- `DATABASE_URL` and `DB_URL`
- actual driver, host, database, and user
- cached configuration
- the real connection used by Laravel
Never print secret values.
The previous report identified duplicate `FAOSTAT_ENABLED` entries on lines 73–74. Determine whether this affects behavior. Do not remove or modify it solely because it is duplicated.
Do not change passwords or connection configuration unless a demonstrated defect requires it and the correction is within this authorization.
## 4.3 Confirm the actual database connection
Use a safe Laravel connection check where Bootstrap is valid.
For example:
```powershell
docker exec wsa-enterprise-backend-1 php artisan tinker --execute='$c=DB::connection(); $c->getPdo(); echo "DB=".$c->getDatabaseName().PHP_EOL; echo "HOST=".$c->getConfig("host").PHP_EOL; echo "USER=".$c->getConfig("username").PHP_EOL; echo "DRIVER=".$c->getDriverName().PHP_EOL;'
```
Also inspect the PostgreSQL session:
```powershell
docker exec wsa-enterprise-postgres-1 psql -U wsa -d wsa_enterprise -c "SELECT current_database(), current_user, version();"
```
Expected values are not proof; record the observed results.
If authentication or Bootstrap fails, do not improvise by changing credentials. Investigate safely.
---
# 5. PHASE 2 — INVENTORY AND CLASSIFY ALL MIGRATIONS
Use Laravel's official status command where safe:
```powershell
docker exec wsa-enterprise-backend-1 php artisan migrate:status
```
Compare:
1. all migration files on disk
2. all records in the primary database's `migrations` table
3. Git tracking status
4. relevant Git history
5. existing schema objects
6. actual migration dependencies
Recalculate the counts. Do not assume the discrepancy is still eight.
The previous report identified the following groups.
## Group A — Platform administrator and RBAC
- `2026_09_23_180000_add_platform_administrator_identity_to_users`
- `2026_09_23_180100_create_platform_rbac_tables`
Both were reported as tracked and pending.
### 5.A.1 Mandatory scope decision before execution
Before considering these migrations for execution, inspect the current application code and answer all of the following:
1. Does current application code reference or depend on `users.is_platform_administrator`?
2. Are there routes, middleware, policies, guards, services, jobs, or authorization flows that require the RBAC tables?
3. Does the existing application operate without these schema objects, as the previous HTTP 200 health check suggests?
4. Is platform administration a current approved project requirement, or is it a future/unused capability?
5. Do existing ADRs, commits, tests, or active UI/API flows establish a requirement for these migrations?
Search the entire repository, including routes, policies, middleware, services, tests, frontend authorization code, and configuration. A simple text search alone is insufficient if behavior is indirect.
### 5.A.2 Decision rules
**If no current functional dependency is established and no approved requirement demands these capabilities:**
- classify both migrations as `REQUIRES_OWNER_DECISION`
- do not execute them in this task
- do not add a default administrator or grant elevated permissions
- document the evidence and pending decision
- continue with independent, necessary work
**If a current, approved requirement is established:**
- classify them as required
- inspect the complete migrations and related application behavior
- verify existing `users` data, schema, foreign keys, and permission semantics
- verify the dependency order
- execute only after all backup and migration gates pass
- test the affected authorization flows
- ensure no unintended administrator or elevated permissions are created
Do not infer that a working health endpoint proves every RBAC-dependent feature is correct. Conversely, do not activate RBAC merely to eliminate pending migrations.
**The migration count is not a reason to execute Group A.**
## Group B — CGHIA and CapVer
The five previously reported tracked migrations are:
1. `2026_10_01_150000_*durable_correlation*`
2. `2026_10_02_160000_*identity_binding*`
3. `2026_10_02_230000_*integration_classification*`
4. `2026_10_09_140000_create_cghia_capability_cell_records_table.php`
5. `2026_10_09_190000_*single_active_index*`
Discover and use the exact filenames.
Review each complete `up()` and `down()` implementation.
Inspect:
- schema definitions
- data operations
- indexes and unique constraints
- partial-index predicates
- foreign keys
- transaction behavior
- existing schema conflicts
- application and repository dependencies
- approved ADR requirements
The single-active index requires the Integration Classification table to exist and satisfy the intended contract. Check for conflicting ACTIVE records before attempting to create the index.
Never delete or rewrite conflicting records arbitrarily to force migration success.
The CapVer table was reported as structurally independent of the other CGHIA tables. Verify that from the actual code before choosing its position in the execution sequence.
## Group C — Scientific Source Record Store
The previous report identified:
`2026_10_15_220000_create_scientific_source_record_store_tables.php`
Git status: `??` untracked.
Do not treat this as an approved migration simply because it appears in the migration directory.
Its disposition is defined in Phase 8. Do not apply it to PostgreSQL unless the approved architecture establishes that this is required.
---
# 6. PHASE 3 — BACKUP BEFORE PERSISTENT CHANGES
A fresh backup of the actual primary PostgreSQL database is mandatory before applying migrations.
The historical Render backup from September 11 is not a substitute.
## 6.1 Exact backup procedure
First verify:
- the current database identity
- available disk space
- PostgreSQL client-tool versions inside the primary container
- the intended host backup path
- that the destination filename will be unique
- that existing backups will not be overwritten
Use the project's `backups` directory if appropriate.
Create the directory if it does not exist, but do not modify or overwrite existing backup files.
Verify that backup artifacts are excluded from Git. If the repository currently tracks or exposes backup artifacts, do not modify `.gitignore` blindly; investigate and apply only a narrowly justified change.
Use PowerShell:
```powershell
# 1. Prepare a unique filename
$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = Join-Path (Get-Location) "backups"
$containerFile = "/tmp/wsa_pre_migrate_$stamp.dump"
$hostFile = Join-Path $backupDir "wsa_pre_migrate_$stamp.dump"
# 2. Create the directory if necessary
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
# 3. Verify the backup tools
docker exec wsa-enterprise-postgres-1 pg_dump --version
if ($LASTEXITCODE -ne 0) { throw "pg_dump version check failed" }
docker exec wsa-enterprise-postgres-1 pg_restore --version
if ($LASTEXITCODE -ne 0) { throw "pg_restore version check failed" }
# 4. Create a PostgreSQL custom-format dump
docker exec wsa-enterprise-postgres-1 pg_dump -U wsa -d wsa_enterprise -F c --no-owner --no-privileges -f $containerFile
if ($LASTEXITCODE -ne 0) { throw "pg_dump failed; stop before migrations" }
# 5. Copy the dump to the host
docker cp "wsa-enterprise-postgres-1:$containerFile" $hostFile
if ($LASTEXITCODE -ne 0) { throw "docker cp failed; stop before migrations" }
# 6. Verify the file exists and is nonempty
$backup = Get-Item -LiteralPath $hostFile -ErrorAction Stop
if ($backup.Length -le 0) { throw "Backup is empty; stop before migrations" }
$backup | Select-Object FullName, Length, LastWriteTime
# 7. Inspect the archive
docker exec wsa-enterprise-postgres-1 pg_restore --list $containerFile
if ($LASTEXITCODE -ne 0) { throw "pg_restore archive inspection failed" }
# 8. Calculate SHA-256
Get-FileHash -LiteralPath $hostFile -Algorithm SHA256 |
    Select-Object Path, Hash
```
Check `$LASTEXITCODE` immediately after each native command before executing another native command.
Do not require a fixed size such as 100 MB. A valid database backup can be smaller or larger. Verify nonzero size, archive structure, expected source, and recovery suitability instead.
If any step fails, stop all migrations and report the exact failure. Do not silently change credentials, install tools, or alter containers to bypass it.
## 6.2 Backup acceptance criteria
Before migrations:
- database identity is confirmed
- backup command succeeded
- file exists and is nonempty
- archive inspection succeeded
- SHA-256 is recorded
- backup path and timestamp are documented
- the recovery plan is clear
Where practical, test restoration into an isolated PostgreSQL instance that does not share the primary volume. Do not restore into the production database as a test.
Archive listing is not equivalent to a fully verified restore. State the exact level of validation achieved.
Do not delete the backup during cleanup.
---
# 7. PHASE 4 — EXACT EXECUTION ORDER AND MIGRATION GATES
## 7.1 Recommended priority: CapVer first
For the current objective of resolving the critical CapVer persistence gap, use this default sequence, provided the actual source code and dependency graph confirm it:
1. `2026_10_09_140000_create_cghia_capability_cell_records_table.php`
2. `2026_10_01_150000_*durable_correlation*`
3. `2026_10_02_160000_*identity_binding*`
4. `2026_10_02_230000_*integration_classification*`
5. `2026_10_09_190000_*single_active_index*`, only after Integration Classification is verified
6. Group A platform administrator/RBAC migrations, only if Phase 5.A establishes a current approved requirement
7. Scientific-source migration, only if Phase 8 establishes an approved requirement for PostgreSQL persistence
This is a proposed priority order, not a substitute for reviewing actual dependencies.
If the repository proves a different dependency order is required, follow the real dependency graph and explain the difference.
Do not execute Group A or Group C merely because they appear in this sequence.
## 7.2 Mandatory per-migration gate
Before each migration:
1. Read its full source, including `up()` and `down()`.
2. Verify architectural approval and actual need.
3. Confirm dependencies.
4. inspect existing schema and data conflicts.
5. run a preview for that exact file
6. inspect the SQL output and any PHP side effects
7. verify the backup gate
8. verify the target database identity
9. verify the application is healthy
10. execute only the approved migration
11. verify the resulting schema and migration record
12. run the health check below
13. proceed only if every check passes
Use the exact path, for example:
```powershell
docker exec wsa-enterprise-backend-1 php artisan migrate --pretend --path=database/migrations/EXACT_MIGRATION_FILENAME.php
```
Do not use wildcards.
`--pretend` is not an absolute safety guarantee. Review the code itself.
Use `--isolated` only if the current Laravel version supports it and the shared cache lock is correctly configured.
Inspect `APP_ENV` before deciding whether `--force` is appropriate. Do not add `--force` automatically.
## 7.3 Failure handling
If a migration fails:
- stop the sequence
- capture the exact error without exposing secrets
- inspect the actual schema and data state
- determine whether the migration partially applied
- do not automatically rerun it
- do not delete its migration record
- do not blindly execute `down()`
- do not restore over the primary database without a specific, justified recovery plan
Continue with independent tasks only if doing so cannot worsen the failure or compromise the evidence.
---
# 8. PHASE 5 — CAPVER REPAIR AND TESTING
The previous report confirmed that the primary PostgreSQL database lacks:
`cghia_capability_cell_records`
Review at minimum:
- `backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php`
- `backend/app/Models/CghiaCapabilityCell.php`
- `backend/app/Services/Agriculture/Research/Capability/Persistence/EloquentCapabilityStoreRepository.php`
- `backend/app/Services/Agriculture/Research/Capability/CapVer/CapVerSeatVerificationService.php`
- `backend/app/Console/Commands/DiagnoseScientificPersistenceCommand.php`
- `backend/app/Console/Commands/CapVerAcceptedSeatCommand.php`
Search for all additional callers and tests.
## 8.1.1 Strict scope of permitted code fixes
Change code only when inspection demonstrates a real defect, such as:
- a migration column does not match the model or repository contract
- required `$fillable` or `$casts` behavior is incorrect
- uniqueness semantics in code do not match the partial unique index
- `ALREADY_CURRENT` behavior contradicts the persisted state
- `persistCurrent()` violates the approved persistence contract
- a demonstrated concurrency, duplicate-write, or failure-handling defect exists
- an actual test or verified runtime path exposes a contract violation
Do not:
- reformat unrelated code
- refactor working functions without a demonstrated defect
- optimize performance outside the defect
- add new features
- weaken validation or unique constraints to make tests pass
- alter working behavior without a requirement
- perform general cleanup
Before each code change:
1. state the demonstrated defect
2. identify the affected contract
3. add or identify a test that exposes the problem
4. make the smallest justified change
5. run the relevant test
6. verify that existing behavior remains correct
If no defect is demonstrated, do not modify the code.
## 8.2 Required verification
Check:
- schema/model/repository agreement
- partial unique indexes and their predicates
- CURRENT-state semantics
- transaction and concurrency behavior
- duplicate submissions
- read-after-write consistency
- failure-closed behavior when persistence is unavailable
- `PERSISTED`, `ALREADY_CURRENT`, and `STORE_UNAVAILABLE` semantics
- alignment with ADR-023
Run relevant unit tests and integration tests against an isolated test database.
Where safely feasible, verify actual PostgreSQL persistence using controlled, unique test data. Never use destructive fixtures against the primary production-like data.
Verify persistence across separate connections or a fresh process where practical.
Do not claim CapVer is fixed merely because the table exists. The schema, actual persistence behavior, failure handling, and required tests must all support the contract.
---
# 9. PHASE 6 — PLATFORM ADMIN/RBAC DECISION AND EXECUTION
Follow the decision rules in Phase 5.A.
If platform admin is not required by current code or approved architecture, leave both migrations pending and record:
`REQUIRES_OWNER_DECISION`
If platform admin is required, verify:
- intended feature behavior
- schema compatibility
- foreign keys
- role and permission semantics
- default authorization behavior
- existing user-data preservation
- all affected routes, middleware, policies, and tests
Do not create a default administrator or assign privileged roles unless an explicit, approved requirement defines that behavior.
If these migrations are not executed, explain why in the final report. Do not claim that all eight migration files have been applied when the decision rules correctly exclude them.
---
# 10. PHASE 7 — HEALTH CHECK AFTER EVERY MIGRATION
Use this exact PowerShell health check after each migration:
```powershell
try {
    $response = Invoke-WebRequest `
        -Uri "http://127.0.0.1:8079/api/v1/health" `
        -UseBasicParsing `
        -TimeoutSec 15
    if ($response.StatusCode -ne 200) {
        throw "Health check failed: HTTP $($response.StatusCode)"
    }
    Write-Host "Health OK: HTTP $($response.StatusCode)"
    Write-Host $response.Content
}
catch {
    Write-Error "Health check failed: $($_.Exception.Message)"
    throw
}
```
If the health check fails:
- stop the migration sequence immediately
- preserve the error and logs
- inspect the state safely
- do not attempt an automatic repair or rollback
- resume only after the cause and recovery path are understood
A passing health check is necessary, but it does not prove that CapVer, RBAC, or scientific persistence is correct.
Run the relevant subsystem tests as well.
---
# 11. PHASE 8 — SCIENTIFIC SOURCE PERSISTENCE AND THE UNTRACKED FILE
The previous audit reported:
- `scientific_record_store.sqlite` already contains scientific source-record tables and FTS structures
- approximately 300,000 records were reported
- PostgreSQL does not currently contain the corresponding `scientific_source_*` tables
- `2026_10_15_220000_create_scientific_source_record_store_tables.php` is untracked
- the scientific SQLite store is distinct from the mission-artifact SQLite archive
Verify these observations and inspect the actual architecture.
## 11.1 Determine the source of truth
Inspect:
- SQLite schema and migration history
- PostgreSQL schema and migration history
- source repositories and persistence services
- scientific search and source-registry flows
- record ownership and synchronization
- existing ADR decisions
- data backup and recovery procedures
Determine whether SQLite is the authoritative durable store, a projection/index, or part of an approved dual-store design.
Do not infer this from table names alone.
## 11.2 Mandatory treatment of the untracked migration
For:
`backend/database/migrations/2026_10_15_220000_create_scientific_source_record_store_tables.php`
Do not:
- delete it
- modify it
- stage it with `git add`
- move it
- apply it to PostgreSQL
- alter its Git history
Leave it in its existing location and preserve its `??` status.
Classify it as:
`REQUIRES_OWNER_DECISION`
unless existing approved architecture unambiguously establishes its intended role and authorizes the corresponding implementation.
If the approved architecture establishes SQLite as the intended authoritative store and PostgreSQL is not required, preserve that design and do not create a parallel store merely to eliminate pending migrations.
If PostgreSQL persistence is demonstrably required but the architecture does not resolve the design, complete independent work and report the exact unresolved decision.
Do not modify `.gitignore` to hide the file.
## 11.3 Documentation
Document the actual findings and unresolved decision in the appropriate ADR section after verifying the current numbering and append-only policy.
If this work's verified results warrant a new §8.60, append it without rewriting previous sections.
Do not falsely declare the scientific issue closed if the source-of-truth decision remains unresolved.
Protect existing scientific records and FTS structures. Do not recreate, truncate, or replace the SQLite store.
---
# 12. PHASE 9 — DATA AND SCHEMA INTEGRITY
Inspect:
- migration records without files
- migration files without records
- existing tables and indexes
- orphaned schema objects
- duplicate ACTIVE records
- foreign-key integrity
- required columns and constraints
- relevant Laravel errors
- SQLite integrity and record counts where applicable
Do not remove objects simply because they look unused.
For any conflicting object or record:
1. identify its origin and dependencies
2. determine whether it is intentional
3. identify the safest remediation
4. preserve the evidence
5. repair only when the repair is justified and authorized
Never perform arbitrary data deletion to make a migration pass.
---
# 13. PHASE 10 — TESTING AND ACCEPTANCE CRITERIA
Run narrow, relevant tests first, then the appropriate broader suite.
Record the exact command, exit status, and actual result.
If a test cannot run, report `NOT TESTED` with the reason. Never report a test as passing when it did not run.
## 13.1 Migration acceptance
For each executed migration:
- the target database is confirmed
- the preview was reviewed
- execution succeeded
- the expected schema exists
- indexes and constraints are correct
- Laravel's migration record reflects the actual execution
- no unexpected schema changes occurred
- the post-migration health check passed
## 13.2 CapVer acceptance
- required schema exists
- model/repository contract is correct
- real persistence works
- CURRENT and duplicate-state semantics are correct
- persistence-unavailable behavior fails closed
- relevant unit tests pass
- relevant integration tests pass
- actual PostgreSQL persistence is verified where feasible
## 13.3 Platform Admin/RBAC acceptance
If implemented:
- approved requirement is documented
- schema and foreign keys are correct
- no unintended elevated privileges are introduced
- relevant authorization flows and tests pass
If not implemented:
- the reason is documented
- no migration was applied merely to close the numerical gap
## 13.4 Scientific persistence acceptance
- source-of-truth decision is grounded in approved architecture
- existing scientific records are preserved
- no unapproved parallel store was created
- required integrity checks pass
## 13.5 Application acceptance
- health endpoint returns HTTP 200
- relevant regression tests pass
- no new critical Laravel errors appear
- existing application behavior remains correct
Do not declare the entire task complete if a required acceptance criterion remains unresolved.
---
# 14. PHASE 11 — UPDATE ADR-023
After implementing and verifying the relevant fixes, inspect the actual last section in:
`docs/architecture/ADR-023-scientific-source-expansion.md`
The previous report identified §8.59 as the latest section and §8.60 as a possible next section. Verify the current file before choosing the number.
Append a new section without modifying historical sections.
Document:
- date and scope
- current migration inventory
- all migrations executed and the evidence for each
- migrations deliberately left pending and why
- dependency order
- backup path, SHA-256, and verification level
- schema/index/constraint verification
- CapVer results
- platform admin/RBAC decision
- scientific persistence decision and evidence
- health-check results
- tests actually run and their results
- unresolved decisions and blockers
Do not fabricate results or mark untested work as successful.
If scientific persistence or Group A remains unresolved, record that explicitly.
Do not commit or push.
---
# 15. EXECUTION WORKFLOW
Execute in this order:
1. Verify the current environment and preserve all WIP.
2. Inspect the current architecture and migration source code.
3. Recalculate the complete migration inventory.
4. Determine whether Group A is genuinely required.
5. Establish and verify the backup.
6. Build the actual dependency graph and risk matrix.
7. Fix only demonstrated code defects.
8. Preview and execute necessary, approved migrations one at a time.
9. Verify schema and health after every migration.
10. Test CapVer and the affected application paths.
11. Resolve scientific persistence using approved architecture, leaving unresolved decisions pending.
12. Run regression tests and integrity checks.
13. Append verified results to ADR-023.
14. Produce one comprehensive Arabic final report.
Do not stop after discovering a problem when an authorized, safe, and technically justified fix can be implemented.
Do stop when:
- backup creation or verification fails
- database identity is uncertain
- unexpected data conflicts appear
- a migration fails or partially applies
- a destructive recovery would be required
- a required architectural decision is absent
- the next action would delete or overwrite existing work
When blocked, preserve the current state, document the exact blocker, and continue with independent work where safe.
---
# 16. FINAL ARABIC ENGINEERING REPORT
Provide one comprehensive Arabic report containing:
1. الملخص التنفيذي
2. الحالة الفعلية للتطبيق والخدمات
3. هوية قاعدة البيانات والنسخة الاحتياطية
4. جرد Migrations الكامل ومقارنة القرص بقاعدة البيانات
5. جدول بكل Migration وحالتها وسبب القرار
6. قرار Platform Admin/RBAC والأدلة
7. الإصلاحات البرمجية الفعلية والملفات المعدّلة
8. نتائج CapVer والحفظ الدائم
9. نتائج الفهارس والقيود وسلامة البيانات
10. قرار Scientific Persistence وحالة الملف غير المتتبع
11. الاختبارات والأوامر ونتائجها الفعلية
12. Health checks وسجلات الأخطاء
13. تحديث ADR-023
14. حالة Git والملفات المحلية
15. الثغرات المغلقة فعليًا
16. الثغرات المتبقية وأسبابها
17. الخطوات المطلوبة من المالك، إن وجدت
Use these status labels precisely:
- `CONFIRMED`
- `IMPLEMENTED`
- `TESTED`
- `VERIFIED`
- `BLOCKED`
- `REQUIRES_OWNER_DECISION`
- `NOT TESTED`
Distinguish implementation from testing and testing from verified operational success.
---
# 17. FINAL DIRECTIVE
Begin execution now.
Act as the project's architect, designer, programmer, implementer, database engineer, and verifier.
Close all demonstrated and safely resolvable gaps. Execute authorized repairs, run the relevant tests, preserve application behavior and existing data, and document the actual results.
**Do not execute Platform Admin migrations without proving their current need. Do not apply the untracked scientific migration without resolving its architectural role. Do not modify code without a demonstrated defect. Do not proceed without a verified backup.**
No destructive shortcuts. No unrelated refactoring. No fabricated success. No unnecessary questions. No commit or push.
The objective is verified engineering closure—not merely matching the number of migration files to the number of database records.
