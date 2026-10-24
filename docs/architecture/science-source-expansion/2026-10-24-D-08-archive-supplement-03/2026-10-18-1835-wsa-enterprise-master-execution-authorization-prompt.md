<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17067). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 6:35 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17068, 17072; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION AUTHORIZATION
## Full Activation of Five Scientific Sources
### PostgreSQL Capability Store + Production LocalIndex + Complete AGRIS Harvest + Complete Dr. Duke Projection + Full Verification of All Five Sources
**Mission date:** 2026-10-18  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local path:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Source membership:** `110.v1`  
**Primary architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0` — revalidate before acting.
**Five target sources**
1. AGRIS — `G1-01`
2. Dr. Duke — `G1-17`
3. Organic Eprints — `G1-02`
4. BHL — `G6-20`
5. GBIF — `G5-04`
---
# 1. OWNER'S EXECUTION AUTHORIZATION
I explicitly authorize you to act as the responsible Lead Software Engineer, Software Architect, Database Engineer, Data Ingestion Engineer, Scientific Search Integration Engineer, Capability Verification Engineer, Test Engineer, and Technical Documentation Engineer for this mission.
This is a broad engineering and programming authorization to complete the actual work required to achieve full legitimate activation of the five sources.
You are authorized to:
- Design and implement necessary architectural improvements.
- Modify existing code and introduce missing components where justified.
- Repair and refactor adapters, ingestion pipelines, projections, persistence layers, registries, contracts, configuration, and tests.
- Use and extend existing source-download, file-storage, artifact-management, manifest, checkpoint, and LocalIndex layers.
- Design the appropriate PostgreSQL storage and migration strategy within the existing architecture.
- Implement resumable and idempotent harvesting, reconciliation, projection, and indexing.
- Correct defects discovered during implementation.
- Improve operational commands, diagnostics, recovery, and failure isolation.
- Complete source-specific onboarding and actual Capability Verification.
- Complete governed registration and real application-facing search integration.
- Make necessary engineering decisions without requesting another general authorization.
- Update ADR-023 and other authoritative records using the project's established append-only conventions.
**Execute the engineering work. Do not stop at an audit, a plan, a list of recommendations, or another report describing work that could be done.**
Inspect only what is necessary to establish the current state and safely continue. Reuse completed work and existing architecture wherever appropriate.
This authorization does not permit fabricating license permissions, inventing owner approvals, bypassing mandatory contracts, exposing secrets, misrepresenting coverage, or declaring a source fully activated without the required evidence.
If a genuinely external legal permission or infrastructure dependency cannot be resolved from the available environment, complete all independent work and document the exact remaining requirement.
---
# 2. MISSION OBJECTIVE
Resolve the four known activation blockers and complete the end-to-end activation requirements for all five sources.
## Blocker A — PostgreSQL Capability Store
Provide the supported PHP PostgreSQL driver and a valid, authorized PostgreSQL connection in the permitted environment. Execute actual CapVer persistence, then verify the persisted evidence through an independent read.
## Blocker B — Production LocalIndex
Implement or complete the project's intended PostgreSQL-backed LocalIndex path, including migrations, persistence, indexing, generation management, consistency checks, and application integration.
Do not treat SQLite validation as proof of PostgreSQL production readiness.
## Blocker C — Complete AGRIS body
Continue resumable harvesting from the verified checkpoint. Establish complete coverage of the authoritative, legally accessible scope required by the project contract, including provider-level provenance, license evidence, reconciliation, and indexing.
## Blocker D — Complete Dr. Duke projection
Complete the required projection and reconciliation for the authoritative scope. Validate table and record coverage, relationships, identifiers, provenance, index consistency, and scientific semantic safeguards.
## Five-source activation
In parallel with the two data-completeness workstreams, complete the required production-oriented verification and activation for:
- Organic Eprints.
- BHL.
- GBIF.
Do not assume these three sources are production-ready merely because earlier reports classified them as `ACTIVE`. Revalidate their current contracts, capabilities, persistence, storage, ingestion, indexing, search paths, licenses, and operational requirements.
The goal is full legitimate activation across the five sources, not simply changing their status fields.
---
# 3. PROTECT THE REPOSITORY AND EXISTING WORK
Before changing files:
1. Confirm the actual repository path.
2. Confirm the branch and HEAD.
3. Record `git status --short`.
4. Identify pre-existing modified and untracked files.
5. Review the current ADR-023 sections and latest relevant reports.
6. Identify existing download, storage, LocalIndex, PostgreSQL, ingestion, projection, and capability implementations.
7. Verify the actual runtime and database availability.
Preserve all existing WIP and unrelated changes.
Do not overwrite user work. Do not delete historical decisions or reports. Do not restart completed harvests or projections unnecessarily.
Do not assume that the previously reported HEAD remains current.
## Mandatory execution restrictions
- No `git commit`.
- No `git push`.
- No deployment.
- No Docker or Docker-based execution.
- No `git reset`.
- No `git clean`.
- No destructive cleanup.
- No destructive database operations.
- No dropping or truncating existing production tables.
- No automatic replacement of production configuration.
- No exposing credentials, API keys, tokens, or connection strings.
- No unapproved production migrations.
- No modification of external production data without explicit environment authorization.
You may implement code, reversible migrations, tests, documentation, and permitted local operational procedures.
If a production database is not available, do not invent one. Complete all safe, independent work and identify the exact external step needed to finish.
Stop at the fully reviewed pre-commit state.
---
# 4. FIRST PRIORITY — POSTGRESQL CAPABILITY STORE
## 4.1 Diagnose the real failure
Previous reports indicated:
- `STORE_UNAVAILABLE`.
- `could not find driver`.
- PostgreSQL host or service unavailable.
- `activation_pass=yes` despite unsuccessful capability persistence.
Revalidate the current state.
Determine whether the failure is caused by:
- Missing PHP `pdo_pgsql` or other required PostgreSQL driver.
- Missing PostgreSQL service.
- Incorrect host or port.
- Missing database or schema.
- Authentication or permission failure.
- Incorrect environment configuration.
- Unsupported connection resolution.
- Migration or table-schema mismatch.
- Application code incorrectly treating persistence failure as nonfatal.
Record the exact root cause rather than treating every failure as the same infrastructure issue.
## 4.2 Implement the supported persistence path
Use the existing Capability Store abstraction and repository conventions.
Implement or repair:
- PostgreSQL connection resolution.
- Schema and migrations.
- Capability record persistence.
- Transaction handling.
- Idempotency and duplicate prevention.
- Appropriate uniqueness constraints.
- Error propagation.
- Read-after-write verification.
- Operational diagnostics.
- Recovery and retry behavior where appropriate.
Do not introduce a parallel capability store if the repository already has an authoritative implementation.
Do not weaken the contract to permit a fake or local-only persistence result.
If the current application reports a successful activation despite failed mandatory persistence, correct the state semantics and failure handling.
## 4.3 Execute actual CapVer persistence
For each of the five sources:
1. Validate prerequisites.
2. Execute the actual repository-supported CapVer workflow.
3. Verify contract evaluation.
4. Persist the capability evidence through the authoritative PostgreSQL store.
5. Read the record back independently.
6. Verify its source identity, contract version, scope, status, timestamps, and evidence references.
7. Verify that a repeated execution is idempotent.
8. Verify that a persistence failure cannot be misreported as successful persistence.
Required proof must distinguish:
- `CAPABILITY_VERIFIED`
- `CAPABILITY_PERSISTED`
- `GOVERNED_REGISTERED`
- `ACTIVE`
- `PRODUCTION_READY`
These states are not interchangeable.
A successful contract check is not proof of persisted capability.
A successful database connection is not proof that CapVer was persisted.
A record existing in a local SQLite database is not proof of PostgreSQL persistence.
## 4.4 Acceptance test
For every source, the completion report must show:
- Actual PostgreSQL connection used, without exposing credentials.
- Exact command executed.
- Actual exit code.
- Persisted record identifier or safe reference.
- Successful independent read-back.
- Verified source identity and status.
- Idempotency result.
- Failure behavior when persistence is unavailable.
Do not claim closure until the evidence can be reproduced.
---
# 5. SECOND PRIORITY — PRODUCTION POSTGRESQL LOCALINDEX
## 5.1 Inspect and reuse the existing architecture
Find the existing LocalIndex, source-file storage, `scientific_records` connection, generation-management logic, repositories, migrations, and query services.
Determine whether PostgreSQL support is partially implemented, missing, or configured incorrectly.
Reuse the existing architecture. Do not build a second indexing subsystem unnecessarily.
## 5.2 Complete the PostgreSQL implementation
Implement the missing production path required by the existing contract, including:
- PostgreSQL schema and migrations.
- Record persistence and retrieval.
- Stable record identity.
- Source and artifact provenance.
- Generation management.
- Atomic or safely recoverable indexing.
- Idempotent ingestion and upserts.
- Duplicate detection.
- Index creation and query performance.
- Pagination and bounded retrieval.
- Transaction and error handling.
- Consistency validation.
- Recovery after interrupted indexing.
- Source-specific failure isolation.
- Application-facing query integration.
- Operational diagnostics and health checks.
Use database abstractions and project conventions where possible. Avoid scattering database-specific SQL across unrelated application components.
Where the project explicitly supports SQLite for development, preserve that capability. Do not silently treat SQLite and PostgreSQL as equivalent environments.
## 5.3 Data migration and cutover
Inspect the existing SQLite LocalIndex and determine how to migrate or rebuild it safely.
Before a migration:
- Inventory the source database and its contents.
- Record the current generation and record counts.
- Establish stable source identities.
- Verify source provenance and required file references.
- Design a repeatable import or reindex procedure.
- Preserve the original SQLite data until the PostgreSQL result is verified.
- Prevent duplicate or missing records.
- Define rollback and recovery behavior.
Do not delete the SQLite index or its source files merely because a PostgreSQL implementation exists.
Do not switch production traffic until the target PostgreSQL index is verified and the application's configuration is explicitly authorized.
If production access is unavailable, implement and test the migration procedure in an approved environment and state that production cutover remains unverified.
## 5.4 Acceptance tests
Prove:
1. The application uses the intended PostgreSQL connection for LocalIndex.
2. Migrations execute successfully.
3. Source records can be written and independently read back.
4. Record identity and provenance remain intact.
5. Generation and record counts are correct.
6. Repeated indexing is idempotent.
7. Queries return the expected records.
8. Interrupted operations recover safely.
9. SQLite-to-PostgreSQL reconciliation accounts for every in-scope record.
10. Production readiness is not inferred from local-only tests.
---
# 6. THIRD PRIORITY — COMPLETE AGRIS HARVEST
Source: `G1-01`.
Previously reported state, to be revalidated:
- `GEN=4`.
- Approximately 1,317 active records.
- Approximately 10 of 1,244 providers processed.
- Bounded activation previously accepted.
- Full-body harvest not completed.
## 6.1 Define the authoritative completion scope
Inspect the existing owner decision, ADR-023, AGRIS contracts, provider inventory, access terms, manifests, and harvest checkpoints.
Determine what “complete AGRIS body” means under the actual project contract.
Do not assume that every provider has identical access or license terms.
For every included provider or artifact category, establish:
- Source identity.
- Provider identity.
- Artifact identity.
- Access method.
- License and redistribution conditions.
- Download and retention constraints.
- Provenance requirements.
- Processing eligibility.
- Coverage and completion criteria.
If the contract requires the complete legally accessible AGRIS body, complete that scope. If specific providers are legally inaccessible or require permission, document those exact exclusions and do not fabricate permission.
Do not silently redefine “complete” to mean “a small subset processed successfully.”
## 6.2 Use the existing download and file-storage layers
**Before writing any new downloader or artifact-storage implementation, discover and use the layers already created in WSA-Enterprise for source file downloads and storage.**
Identify the existing components responsible for:
- Source artifact discovery.
- Download execution.
- Original file preservation.
- File storage disk and path.
- Content type and size.
- SHA-256 or equivalent integrity verification.
- Manifests.
- Download checkpoints.
- Resume and retry.
- Duplicate detection.
- Download provenance.
- Source-specific retention and access policies.
Reuse these components and extend them only when necessary.
Do not create an AGRIS-specific duplicate of a general download or file-storage subsystem unless the existing architecture demonstrably cannot support the required behavior.
Preserve original downloaded files unchanged whenever required by the project's file-retention contract. Keep derived normalization and projection artifacts separate from original files.
Ensure every processed record can be traced back to its source artifact and source identifier.
## 6.3 Complete resumable harvesting
Implement and execute the actual harvesting workflow:
1. Validate existing manifests and checkpoints.
2. Reconcile checkpoint position against the actual artifact inventory.
3. Discover newly available artifacts without skipping them.
4. Resume from the last valid checkpoint.
5. Download permitted artifacts through the existing download layer.
6. Verify artifact integrity.
7. Persist original files through the approved storage layer.
8. Record file paths, hashes, source identities, licenses, and provenance.
9. Parse and normalize eligible records.
10. Reconcile identities and duplicates.
11. Index all required records.
12. Persist generation and coverage metadata.
13. Reconcile expected versus downloaded, parsed, rejected, indexed, and failed records.
14. Resume interrupted work without reprocessing completed artifacts unnecessarily.
Ensure retry logic does not create duplicate records or silently skip new files.
Do not load a massive corpus entirely into memory if a streaming or bounded-batch approach is appropriate.
Use bounded concurrency, rate limits, backoff, and source-respecting access behavior.
## 6.4 AGRIS acceptance criteria
The report must demonstrate:
- Defined and compliant completion scope.
- Provider-by-provider coverage.
- Artifact counts and integrity.
- Original file storage locations, without exposing sensitive paths unnecessarily.
- Downloaded, parsed, rejected, indexed, and failed counts.
- Checkpoint correctness.
- No unexplained missing artifacts.
- No unexplained duplicate records.
- Stable identities and provenance.
- LocalIndex generation and record counts.
- PostgreSQL persistence where required.
- Successful real search through the application.
- Reproducible tests and evidence.
If any provider remains inaccessible or requires external authorization, record it as an explicit scope limitation with its exact reason.
---
# 7. FOURTH PRIORITY — COMPLETE DR. DUKE PROJECTION
Source: `G1-17`.
Previously reported state, to be revalidated:
- Approximately 16 tables reconciled.
- Approximately `GEN=2`.
- Approximately 1,500 indexed search rows.
- Bounded projection previously accepted.
- Complete projection not achieved.
## 7.1 Establish the actual source inventory
Inspect the original Dr. Duke source artifacts, database files, table inventory, schema definitions, row counts, existing manifests, reconciliation results, and projection checkpoints.
Determine the full authoritative set of tables and records required by the project contract.
Record, for every table:
- Original table name.
- Original row count.
- Included row count.
- Rejected row count and reason.
- Projection target.
- Relationship and key mapping.
- Duplicate and unmatched counts.
- Indexing status.
- Provenance and source-file references.
Do not assume that 16 reconciled tables means all rows have been projected.
## 7.2 Complete the projection
Implement or repair:
- Schema discovery and validation.
- Table-specific mappings.
- Stable source identifiers.
- Primary and foreign-key preservation where applicable.
- Taxonomic and chemical identity mappings.
- Relationship validation.
- Duplicate handling.
- Unmatched and ambiguous record handling.
- Projection completeness accounting.
- Original-to-projection lineage.
- Idempotent batch processing.
- LocalIndex integration.
- PostgreSQL persistence.
- Recovery from interrupted projection.
- Reconciliation reports.
Use the existing source-file storage and ingestion layers where applicable. Preserve original source artifacts and avoid creating unnecessary parallel storage systems.
## 7.3 Mandatory scientific constraints
Maintain all of the following:
- `FARMACY` and `FARMACY_NEW` remain distinct unless an authoritative relationship contract establishes otherwise.
- Do not merge records solely because names or identifiers look similar.
- Preserve original table identity and source provenance.
- Keep unresolved relationships explicitly unresolved.
- Do not transform traditional-use evidence into proof of clinical efficacy.
- `ETHNOBOT` must retain its traditional-use meaning.
- Do not invent citations, evidence levels, relationships, or identifiers.
These constraints must be enforced in code and tests, not only in documentation.
## 7.4 Dr. Duke acceptance criteria
Demonstrate:
- Complete table inventory.
- Expected and actual row counts per table.
- Projection completeness for the authoritative scope.
- Explained exclusions and rejections.
- Relationship and identity integrity.
- Correct `FARMACY` / `FARMACY_NEW` separation.
- `ETHNOBOT` semantic safeguards.
- No unexplained data loss.
- Idempotent re-projection.
- LocalIndex generation and counts.
- PostgreSQL persistence where required.
- Real application-facing searches returning traceable records.
- Reproducible tests.
Do not declare the projection complete while any required table or row remains unexplained.
---
# 8. FULL REVALIDATION OF ORGANIC EPRINTS, BHL, AND GBIF
Sources:
- Organic Eprints — `G1-02`.
- BHL — `G6-20`.
- GBIF — `G5-04`.
Previous reports classified these sources as `ACTIVE`, but the Cap Store was reportedly unavailable. Their current state must be revalidated.
## 8.1 For every source
Verify:
- Source Registry identity.
- Authoritative source contract.
- Current onboarding decision.
- Applicable license and access conditions.
- Actual adapter behavior.
- Current data and artifact availability.
- Use of the existing download and storage layers where required.
- Original-file preservation.
- Checkpoint and manifest integrity.
- Data normalization and provenance.
- LocalIndex persistence.
- PostgreSQL connection and query path.
- CapVer evaluation.
- CapVer persistence and independent read-back.
- Governed catalog registration.
- Application-facing search.
- Error handling and failure isolation.
- Regression tests.
Do not downgrade a legitimate existing state without evidence. Do not preserve an inaccurate state merely because it was previously reported.
## 8.2 Organic Eprints
Verify the previously accepted onboarding decision and complete the required actual capability workflow.
Confirm that downloaded or ingested material is permitted by the source's terms, that file provenance is retained, and that the application returns valid source-specific results.
## 8.3 BHL
Protect all API credentials.
Do not print, log, or archive secrets. Inspect how credentials are loaded and ensure they are not embedded in source code or emitted in reports.
If previous exposure warrants key rotation, document the precise required action. Perform rotation only if the authorized mechanism and required owner access are actually available. Never claim rotation without evidence.
Verify BHL data ingestion, provenance, search, capability persistence, and catalog state.
## 8.4 GBIF
Revalidate the previously reported active state, data provenance, indexing, PostgreSQL behavior, CapVer persistence, and application-facing search.
Protect existing successful functionality and run relevant regression tests after shared-layer changes.
---
# 9. COMPLETE END-TO-END SCIENTIFIC SEARCH
For all five sources, verify the actual search path:
`User Question → CSQ → ScientificSearchQueryBuilder → ScientificQueryCompiler → MultiSourceScientificSearchOrchestrator → Source Adapter → Source Data → ScientificResultNormalizer → LocalIndex/Results → Application Display`
Use the actual names and boundaries found in the repository; do not force an artificial architecture if the current implementation differs.
Preserve the semantic identity of the user's scientific question.
Do not replace CSQ with keyword-only search.
Do not weaken evidence sufficiency, claim relationships, directness, provenance, disposition, or final-answer eligibility rules to increase result counts.
Test representative source-specific queries and verify:
- The correct source is selected.
- The correct query contract is applied.
- The returned record belongs to the correct source.
- The original identifier is preserved.
- Provenance is present and traceable.
- Source-specific semantics are preserved.
- Results are not duplicated incorrectly.
- A failed source does not corrupt other sources' results.
- The final application output accurately reflects the evidence.
For live-source tests, use bounded, compliant queries. Distinguish fixture tests, local integration tests, live external queries, PostgreSQL persistence tests, and real application-facing tests.
Do not claim end-to-end success based only on a unit test or a direct LocalIndex probe.
---
# 10. DATABASE MIGRATIONS, DATA INTEGRITY, AND RECOVERY
All migrations and data transformations must be non-destructive by default.
Before migrating or rebuilding:
- Establish source and target inventories.
- Record record counts and generations.
- Validate identities and provenance.
- Verify backup or recovery requirements.
- Define idempotency.
- Define rollback or restoration procedures.
- Establish reconciliation invariants.
- Keep original source artifacts intact.
Use the repository's supported migration tooling.
Do not use destructive schema changes to make tests pass.
Do not silently truncate or replace existing data.
Do not switch the application to a new production store until the target is verified and the switch is explicitly authorized by the existing operational contract.
Where an external environment is unavailable, finish implementation and safe local tests but do not represent the production migration as completed.
---
# 11. TESTING AND VERIFICATION STRATEGY
Add or update tests for the actual gaps discovered.
## Capability Store
- PostgreSQL connection resolution.
- Migration correctness.
- CapVer persistence.
- Independent read-back.
- Idempotency.
- Failure handling.
- `STORE_UNAVAILABLE` semantics.
- No false `CAPABILITY_PERSISTED` state.
## LocalIndex
- PostgreSQL persistence.
- Query correctness.
- Generation consistency.
- Provenance and identity.
- Duplicate prevention.
- Migration reconciliation.
- Restart and recovery.
- SQLite compatibility where supported.
- Correct production connection selection.
## AGRIS
- Provider/license enforcement.
- Artifact discovery and integrity.
- Checkpoint resume.
- Newly discovered artifact handling.
- Download/storage integrity.
- Parsing and reconciliation.
- Identity and provenance.
- Full required coverage.
- PostgreSQL indexing.
- Real application search.
## Dr. Duke
- Table and row coverage.
- Schema and relationship integrity.
- Projection idempotency.
- Unmatched and ambiguous records.
- `FARMACY` / `FARMACY_NEW` distinction.
- `ETHNOBOT` semantics.
- Provenance and identity.
- PostgreSQL indexing.
- Real application search.
## Organic Eprints, BHL, and GBIF
- Source-specific contract and adapter behavior.
- Download and storage behavior where applicable.
- Capability persistence.
- PostgreSQL LocalIndex.
- Provenance.
- Application search.
- Regression and failure isolation.
## Shared architecture
- CSQ semantic preservation.
- Query compiler and orchestrator behavior.
- Result normalization.
- Evidence integrity.
- Source Registry and Capability Contract.
- Governed catalog.
- All relevant regression suites.
Run focused tests first, then broader permitted tests.
For every command, capture:
- Exact command.
- Actual exit code.
- Test count and assertions where available.
- Pass/fail result.
- Environment and database used.
- Whether the test used fixtures, local data, live source access, PostgreSQL persistence, or the application path.
Do not reuse historical test results as evidence for newly modified code.
If PHP, PostgreSQL, a driver, or an external source is unavailable, report the actual failure and continue all independent work. Do not fabricate successful test output.
---
# 12. USE THE EXISTING FILE-DOWNLOAD AND STORAGE ARCHITECTURE
This requirement applies to every source that requires source artifacts to be downloaded and retained.
Search the existing codebase for the established download, artifact, file-storage, ingestion, and manifest abstractions before implementing anything new.
Determine whether the existing layers already support:
- Remote download.
- Source-specific authorization and headers.
- Bounded streaming.
- Retry and backoff.
- Content hashing.
- File metadata.
- Storage disks and paths.
- Original file preservation.
- Manifests and checkpoints.
- Idempotency.
- Artifact lineage.
- Source-specific retention.
- Recovery and diagnostics.
Extend existing components to satisfy missing requirements.
Do not duplicate general infrastructure in individual source adapters.
Preserve the project's existing `file_disk` / `file_path` or equivalent contract when applicable. Store the actual downloaded artifact, not merely its URL, when the project's requirements call for retaining the file.
Do not construct fake file paths or claim a file was downloaded when only metadata was recorded.
For every downloaded artifact, record the relevant metadata and integrity evidence according to the current architecture.
Keep large downloads bounded, resumable, and safe to retry.
Protect credentials and avoid logging sensitive authorization headers.
---
# 13. PRODUCTION CONFIGURATION AND EXTERNAL DEPENDENCIES
Identify the approved way to provide PostgreSQL and PHP PostgreSQL support in the actual environment.
Do not assume Docker is available or permitted.
Do not silently edit production credentials or deployment configuration.
Do not invent hostnames, credentials, database instances, or external access.
If the current machine cannot access the approved PostgreSQL service:
1. Complete code and migration work.
2. Run permitted unit and integration tests.
3. Verify behavior using existing supported local facilities.
4. Prepare reproducible commands for the approved environment.
5. Identify the precise missing driver, service, permission, or configuration.
6. Define the acceptance test that closes the blocker.
7. Mark the relevant production requirement as unverified.
The mission should continue across independent workstreams rather than stopping entirely because one infrastructure dependency is missing.
---
# 14. ARCHITECTURAL RECORDS AND COMPLETION MATRIX
Update `docs/architecture/ADR-023-scientific-source-expansion.md` using append-only conventions and the next appropriate section number.
Do not overwrite §8.54 or delete previous decisions.
Update the existing completion matrix and source-specific records rather than creating competing authorities.
Record:
- Scope and completion criteria for each of the five sources.
- License and access evidence.
- Data coverage and limitations.
- Download and file-storage behavior.
- Artifact manifests and checkpoints.
- Projection and ingestion results.
- PostgreSQL architecture and migration status.
- CapVer verification and persistence evidence.
- LocalIndex generation and row counts.
- Catalog registration.
- Application-facing search results.
- Test commands and outcomes.
- Production-readiness state.
- Remaining external dependencies.
- Recovery procedures and next milestones.
Keep owner decisions, engineering decisions, and execution evidence distinct.
Do not rewrite the historical record to make incomplete work appear complete.
---
# 15. REQUIRED FINAL ACTIVATION STATES
For each source, determine the actual state according to the authoritative repository contract.
At minimum, distinguish:
- `TECHNICALLY_INTEGRATED`
- `ONBOARDED`
- `CAPABILITY_VERIFIED`
- `CAPABILITY_PERSISTED`
- `GOVERNED_REGISTERED`
- `ACTIVE`
- `PRODUCTION_READY`
- `FULL_SCOPE_COMPLETE`
Use the project's actual state vocabulary where it differs, and document the mapping.
A source must not be marked `PRODUCTION_READY` solely because:
- Its adapter returns data.
- Its catalog entry exists.
- Its contract check passes.
- SQLite tests pass.
- Its bounded dataset is searchable.
- A CapVer write was attempted.
- A status field says `ACTIVE`.
For every source, full activation must be backed by the mandatory contract, capability evidence, persistence, indexing, application integration, license compliance, and operational acceptance criteria.
Where full-body completion is a separate requirement, report it independently from technical activation.
Do not label the five-source mission complete while mandatory activation criteria remain unverified.
---
# 16. EXECUTION WORKFLOW — IMPLEMENT, TEST, CORRECT, VERIFY
## Phase A — Establish baseline
Inspect the actual state, relevant code, current ADR, WIP, runtime, database availability, and source inventories.
Avoid repeating completed forensic work.
## Phase B — Design and implement shared infrastructure
Prioritize the Capability Store and production LocalIndex because they affect all five sources.
Make the smallest sound architectural changes that support the five sources without creating parallel systems.
## Phase C — Complete source data
Run AGRIS harvesting and Dr. Duke projection using existing download, file-storage, manifest, and checkpoint layers.
Reconcile the results and preserve source artifacts.
## Phase D — Revalidate all five sources
Verify Organic Eprints, BHL, and GBIF against the same production requirements, correcting necessary defects without damaging existing behavior.
## Phase E — Real persistence and activation
Execute actual PostgreSQL migrations, capability persistence, read-back verification, indexing, and application search in the permitted environment.
## Phase F — Regression and quality review
Run focused and broader permitted tests. Correct actual failures and rerun affected tests.
## Phase G — Documentation and pre-commit review
Update the ADR and completion matrix, inspect the full diff, and produce one comprehensive Arabic report.
Do not stop at a plan or partial implementation when the required work can be performed safely.
Do not claim that an external operation succeeded when it was not executed.
---
# 17. FINAL REPORT — ARABIC ONLY
Return one comprehensive engineering report containing:
## A. Executive summary
- What was actually implemented.
- Which blockers were closed.
- The verified final state of each source.
- Whether full activation and full-body requirements are complete.
## B. PostgreSQL Capability Store
- Root cause.
- Driver and connection outcome.
- Schema and migration changes.
- Actual CapVer persistence.
- Independent read-back evidence.
- Idempotency and failure tests.
- Remaining production blockers.
## C. Production LocalIndex
- Actual backend and connection used.
- Migrations and implementation.
- SQLite-to-PostgreSQL reconciliation.
- Record counts and generation.
- Provenance and identity integrity.
- Query and recovery tests.
- Actual production-readiness status.
## D. AGRIS
- Defined full scope.
- Provider-level license and access evidence.
- Artifact download and storage counts.
- Manifest and checkpoint integrity.
- Parsed, rejected, indexed, and failed counts.
- Final LocalIndex generation.
- PostgreSQL persistence.
- Application-facing search.
- Final state and any exclusions.
## E. Dr. Duke
- Complete table inventory.
- Expected and projected row counts.
- Reconciliation and relationship integrity.
- `FARMACY` / `FARMACY_NEW` safeguards.
- `ETHNOBOT` semantic safeguards.
- Index and PostgreSQL results.
- Application-facing search.
- Final state and any exclusions.
## F. Organic Eprints, BHL, and GBIF
For each source, provide:
- Actual current contract and onboarding state.
- Download/storage and ingestion results where applicable.
- CapVer persistence evidence.
- LocalIndex backend.
- Catalog registration.
- Application-facing search.
- Regression tests.
- Actual final state.
## G. Shared architecture
- CSQ preservation.
- Query compiler and orchestrator.
- Adapters and normalization.
- Provenance and stable identity.
- Download/file-storage reuse.
- Failure isolation.
## H. Tests
- Exact commands.
- Exit codes.
- Counts and assertions.
- Failures and corrections.
- Skipped tests and reasons.
- Clear separation between fixtures, local SQLite, PostgreSQL, live-source, and application-facing evidence.
## I. Remaining blockers
For each unresolved item, state:
- Exact missing requirement.
- Actual evidence.
- Why it remains unresolved.
- Smallest required external action.
- Exact acceptance test.
## J. Git and artifact safety
- Branch and HEAD.
- `git status --short`.
- Relevant diff summary.
- `git diff --check`.
- Confirmation that pre-existing WIP remains intact.
- Confirmation that no commit, push, deployment, Docker, reset, clean, or destructive cleanup occurred.
---
# FINAL DIRECTIVE
Execute the engineering work necessary to complete full legitimate activation of AGRIS, Dr. Duke, Organic Eprints, BHL, and GBIF.
Resolve the PostgreSQL Capability Store and production LocalIndex gaps. Complete the required AGRIS harvest and Dr. Duke projection. Revalidate and complete the other three sources. Use the existing download, original-file storage, manifests, checkpoints, ingestion, projection, and indexing layers wherever appropriate.
You have broad authority to design, implement, refactor, test, and correct the project within its authoritative architecture, source contracts, licenses, and safety requirements.
**Do not merely prepare another plan. Perform the actual work.**
Do not fabricate data, permissions, persisted capability records, test results, or production readiness.
If the environment prevents a required external operation, finish every independent task, document the precise blocker, and provide the reproducible closure procedure.
Preserve all previous work. Do not commit, push, deploy, use Docker, reset, clean, or perform destructive cleanup.
**Stop at the fully reviewed pre-commit state and return one comprehensive Arabic report distinguishing completed work from anything still unverified.**
