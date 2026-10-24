<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16959). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 4:05 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16960; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER ENGINEERING AUDIT & COMPLETION MISSION
## Five-Source Finalization + Unified Archive Migration + Per-Source Gap Closure + Owner Decision Readiness
**Mission date:** 2026-10-18  
**Repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Previously reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Scientific source membership:** `110.v1`  
**Archive implementation:** `FilesystemMissionArtifactArchive`  
**Archive CLI:** `mission-artifacts`  
**Architectural record:** ADR-023 §8.51
---
# PART I — MISSION OBJECTIVES AND AUTHORITY
## 1. Role and execution mandate
Act as the Lead Software Engineer, Independent Pre-Commit Reviewer, Scientific Search Integration Engineer, Archive-System Engineer, Persistence Engineer, and Test Engineer for WSA-Enterprise.
This is an execution, verification, and completion-readiness assignment. It is not a request to provide a plan and stop.
You are authorized to:
- Inspect the actual repository, code, runtime wiring, tests, ADRs, owner decisions, reports, manifests, and historical records.
- Review and repair demonstrated defects in the five-source integration and `FilesystemMissionArtifactArchive`.
- Complete all independent engineering work permitted by existing approved architecture and governance.
- Audit the remaining work required to finalize the addition, integration, and legitimate activation of all five scientific sources.
- Implement safe, additive, idempotent migration of eligible historical prompts, reports, and recoverable conversation/task records into the new archive.
- Add focused tests, narrowly scoped documentation, manifests, and audit records.
- Run permitted tests and bounded live probes.
- Reuse completed work and existing evidence whenever valid.
- Prepare a precise, evidence-backed owner-decision package.
- Produce one comprehensive Arabic final report.
Do not stop after investigation, gap identification, or a proposed plan. Execute the authorized independent work, test the results, repair verified defects, and then report the remaining work.
Do not ask repetitive questions or require confirmation for routine engineering steps. If an operation genuinely requires an owner decision, finish all independent work first and record the exact decision required.
## 2. Mandatory workstreams
Complete these workstreams within the same mission.
### Workstream A — Final five-source engineering audit
1. GBIF — `G5-04`
2. Organic Eprints — `G1-02`
3. BHL — `G6-20`
4. AGRIS — `G1-01`
5. Dr. Duke — `G1-17`
Verify existing implementation, actual application integration, persistence, evidence/provenance, licensing, tests, and governance gates.
### Workstream B — Unified archive audit and historical migration
Review and verify:
- `FilesystemMissionArtifactArchive`.
- Blob/content storage.
- SHA-256 integrity verification.
- Stable IDs and version history.
- Append-only supersession.
- Search and retrieval.
- `mission-artifacts` CLI.
- Historical import manifests and checkpoints.
- Eligible JSON, AHO, Cursor prompts, engineering reports, test reports, mission records, stopping points, and recoverable conversation/task records.
- ADR-023 §8.51 and related architectural history.
Migrate all eligible, recoverable, non-sensitive historical artifacts within the authorized scope. Preserve the original content and all historical records.
### Workstream C — Per-source remaining-work and finalization analysis
For **each of the five sources separately**, determine exactly what remains to complete:
1. Source onboarding/addition.
2. Technical integration.
3. Capability verification.
4. Source registry and catalog registration.
5. Query and orchestration integration.
6. Persistence/indexing, where applicable.
7. Application-facing behavior.
8. Licensing and provenance.
9. Test coverage and operational verification.
10. Governance approval.
11. Production readiness.
12. Final legitimate activation.
This workstream must produce a source-specific completion report, not just a shared list of general blockers.
### Workstream D — Condition A / Condition B and owner-decision readiness
Complete all permitted independent engineering work, document remaining governance and production limitations, and prepare the smallest actionable owner-decision package needed to open the activation path.
---
# PART II — SAFETY, BASELINE, AND NON-REGRESSION
## 3. Mandatory restrictions
- No `git commit`.
- No `git push`.
- No deployment.
- No Docker.
- No `git reset`.
- No `git clean`.
- No destructive cleanup.
- No deletion or overwriting of pre-existing work.
- No production database configuration changes.
- No automatic credential rotation.
- No fabricated approvals, provenance, historical artifacts, test results, or activation states.
- No weakening of scientific evidence, licensing, security, or capability gates.
- No indiscriminate repository-wide archival.
- No unrelated refactoring.
Keep all changes uncommitted and stop before commit/push for final review.
## 4. Establish the actual baseline
Before modifying anything:
1. Inspect `git status --short`.
2. Confirm the actual branch and HEAD.
3. Inspect the current diff and untracked files.
4. Identify pre-existing WIP separately from changes made during this mission.
5. Locate the latest five-source report, archive report, readiness matrix, owner-decision proposal, and ADR-023 §8.51.
6. Inspect the current source registry, capability contracts, onboarding state, and application integration paths.
7. Locate existing archive manifests, checkpoints, imported artifacts, and tests.
The previously reported HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0`; the actual checkout is authoritative.
## 5. Verify the reported baseline
The latest report stated:
- GBIF remains `ACTIVE`.
- Organic Eprints is `TECHNICALLY_INTEGRATED / NOT_READY`, with SQLite generation 2 and 189 records.
- BHL has a live adapter/orchestrator path but is not production-activated.
- AGRIS has generation 4 and 1,317 records, with 10 of 1,244 providers processed.
- Dr. Duke has a 16-table reconciliation and a limited 1,500-record search projection.
- The archive is `IMPLEMENTED_WITH_LIMITED_IMPORT`.
- The previous archive import reported 133 artifacts from designated mission roots and D-08-supp-01 plus related material, with 136 total entries.
- No AHO files or schema were identified.
- The latest task-specific run reported 68 tests and 348 assertions passing.
- The broader 214-test/6,902-assertion run was historical evidence and was not fully repeated in that task.
- PostgreSQL production readiness remains pending.
- No new source activation occurred.
- The worktree contains uncommitted changes and local artifacts.
Verify each relevant fact. Do not present a historical report as proof of the current runtime.
## 6. Preserve completed work
Do not restart successful harvesting, extraction, reconciliation, or broad tests without a concrete reason.
Reuse valid artifacts and evidence. Use bounded probes, idempotent ingestion, and targeted regression tests.
If a defect is found, implement the narrowest appropriate correction, add a regression test, and run the affected tests.
Preserve pre-existing WIP and the original ADR history.
---
# PART III — FINAL PER-SOURCE GAP ANALYSIS
## 7. Mandatory deliverable: five independent completion reports
**Do not treat the five sources as one combined integration problem. Produce a separate, evidence-backed completion analysis for each source.**
For each source, investigate the actual current implementation and produce the following structured analysis.
### 7.1 Required analysis for every source
1. **Current verified status**
   - Current activation status.
   - Technical integration status.
   - Governance status.
   - Production readiness.
   - What evidence supports each status.
2. **Completed work**
   - Existing implementation.
   - Successful tests and probes.
   - Existing records, indexes, manifests, and checkpoints.
   - Existing registrations and integration paths.
   - Previously completed work that must not be repeated.
3. **Remaining engineering gaps**
   - Missing or incomplete code.
   - Missing application integration.
   - Missing capability contract or verification.
   - Missing registry/catalog wiring.
   - Missing persistence or indexing behavior.
   - Missing error handling or failure isolation.
   - Missing provenance or license handling.
   - Missing tests.
   - Any architecture or ADR mismatch.
4. **Remaining governance gates**
   - Exact onboarding decision.
   - Capability verification authorization.
   - Licensing approval.
   - Catalog/registry approval.
   - Any other mandatory owner decision.
5. **Remaining production gates**
   - PostgreSQL or other required infrastructure.
   - Runtime configuration.
   - Deployment-specific requirements.
   - Any requirement that cannot be verified locally.
6. **Concrete closure criteria**
   - The exact implementation or decision needed.
   - The test or evidence that proves completion.
   - The authoritative contract or ADR section.
   - Dependencies and blockers.
   - Whether the work can be performed independently now.
7. **Recommended sequence**
   - Order the remaining actions by dependency and readiness.
   - Identify work that can proceed in parallel.
   - Avoid unnecessary full-corpus processing where it is not a prerequisite for a narrower integration milestone.
8. **Final classification**
   - `ACTIVE` only if every applicable requirement is satisfied.
   - `TECHNICALLY_INTEGRATED / NOT_READY` if technical integration is established but mandatory gates remain.
   - `PARTIALLY_INTEGRATED / NOT_READY` if required integration work is still missing.
   - `BLOCKED` when a specific dependency prevents further progress.
Use the project's actual approved status vocabulary if different and document the mapping.
## 8. GBIF — G5-04: completion requirements
Verify the currently active GBIF path.
Check:
- Source identity and membership.
- Capability contract and verification.
- Registry/catalog consistency.
- Query construction and CSQ semantics.
- Adapter behavior and normalization.
- Provenance and stable result identity.
- Application-facing search.
- Failure handling and coexistence with other sources.
- Existing tests and regression coverage.
- Any outstanding operational or governance requirements.
Do not create a new implementation or local snapshot unnecessarily.
If all applicable requirements are already satisfied, explicitly classify GBIF as complete and list only the verification evidence needed to maintain confidence. Do not invent additional work simply to give all sources equal task counts.
## 9. Organic Eprints — G1-02: completion requirements
Verify the reported SQLite generation 2 and 189 active records, if still current.
Check:
- Source onboarding and identity.
- Capability contract.
- Capability verification status.
- Query construction and adapter integration.
- Result normalization and provenance.
- Persistent store and cross-process behavior.
- LocalIndex integration and idempotency.
- Registry/catalog registration.
- Governed application-facing search.
- Failure isolation.
- Licensing and documentation.
- Required onboarding decision.
- CapVer delegation and completion.
- PostgreSQL/production readiness.
Identify which gaps are pure governance gates and which still require engineering.
Do not repeat the harvest unless a specific defect justifies it.
## 10. BHL — G6-20: completion requirements
**Use the saved `BHL_API_KEY` through the existing secure configuration path. Do not ask for the key again.**
Verify:
- The environment variable is available to the actual runtime.
- The live API works using the saved key.
- Adapter and orchestration behavior.
- PartID, PartUrl, and provenance preservation.
- Actual governed application integration.
- Whether the reported temporary LEGACY registration is still used and whether it correctly isolates the probe from production activation.
- Source registry and catalog state.
- Capability and onboarding gates.
- Failure isolation.
- Required tests.
- Licensing/terms evidence.
- Production readiness.
Do not print or log the API key. Do not rotate it automatically.
Explicitly distinguish:
- Live API success.
- Application-path integration.
- Governed registration.
- Approved activation.
Identify the exact remaining requirements for legitimate BHL activation.
## 11. AGRIS — G1-01: completion requirements
Verify the current manifest, checkpoint, generation 4, 1,317 records, and 10/1,244 provider progress.
Check:
- Official ODS artifact and provider inventory.
- Resumable harvesting and checkpoint correctness.
- Provider-specific license evidence.
- Record provenance.
- Ingestion idempotency.
- LocalIndex/query visibility.
- Application integration.
- Source registry and capability.
- Onboarding and CapVer.
- Corpus completion status.
- Production readiness.
Separate the following into distinct requirements:
1. A bounded, technically integrated AGRIS subset.
2. A larger or complete corpus ingestion milestone.
3. Governance-approved source activation.
4. Production persistence readiness.
Do not conflate these milestones.
Determine whether full corpus completion is actually required by the authoritative contract for the specific activation milestone. Do not assume that all 1,244 providers must be harvested before any narrower, explicitly permitted integration milestone can be approved; equally, do not claim full coverage when the corpus remains incomplete.
Do not generalize licenses across providers. Do not restart the harvest from zero.
## 12. Dr. Duke — G1-17: completion requirements
Verify the 16-table reconciliation and the current 1,500-record search projection.
Check:
- Source identity and capability.
- Projection coverage.
- Schema mapping and relationship integrity.
- `FARMACY` versus `FARMACY_NEW` distinction.
- Taxonomic and chemical identifiers.
- Unmatched and ambiguous relationships.
- ETHNOBOT traditional-use semantics.
- Provenance and licensing.
- Query/index integration.
- Governed application path.
- Onboarding and CapVer.
- Whether full projection is a mandatory activation criterion.
- Production readiness.
Separate the minimum legitimate integration milestone from full dataset projection if the approved contracts allow that distinction.
Do not blindly merge schemas, fabricate matches, or claim full projection while the dataset remains partial.
---
# PART IV — CROSS-SOURCE COMPLETION MATRIX
## 13. Build a final dependency-aware matrix
After the five separate analyses, create one consolidated matrix that allows the owner to see:
- Each source's current status.
- What is complete.
- What remains.
- Whether the remaining item is engineering, governance, licensing, corpus coverage, or infrastructure.
- The authoritative requirement.
- The evidence needed to close it.
- Whether it can be executed now.
- The dependency order.
- The responsible decision-maker or external dependency, where known.
Do not reduce this matrix to vague entries such as "finish integration" or "complete tests."
Every outstanding item must be actionable and testable.
Use a status vocabulary consistent with the repository.
## 14. Identify the critical path to final activation
Determine the actual critical path from the current state to legitimate activation for each source.
Identify:
- The next action that unblocks the largest amount of remaining work.
- The earliest source seat ready for an owner decision.
- Which actions can run in parallel.
- Which work is gated by owner approval.
- Which work requires PostgreSQL or other infrastructure.
- Which work depends on corpus completion or additional licensing evidence.
Do not automatically select a single universal sequence if different sources have different dependencies.
---
# PART V — UNIFIED ARCHIVE AND HISTORICAL MIGRATION
## 15. Review the actual archive implementation
Inspect `FilesystemMissionArtifactArchive`, storage, metadata, stable IDs, SHA-256, versioning, search, retrieval, export, CLI, tests, manifests, and ADR-023 §8.51.
Verify:
- Exact original preservation.
- Hash verification and tampering detection.
- Append-only version history.
- Supersession without deletion.
- Idempotent ingestion.
- Cross-process durability.
- Safe path handling.
- Search and retrieval.
- Safe export.
- Secret filtering.
- Failure recovery.
- Separation from scientific LocalIndex.
Repair demonstrated defects only.
## 16. Mandatory migration of historical prompts and reports
**Do not stop at the previously reported 136 archive entries.**
Search all relevant repository and architectural-record locations for eligible, recoverable:
- Cursor master prompts.
- Cursor execution prompts.
- Corrective prompts and follow-ups.
- Cursor final reports.
- Engineering reports.
- Test and regression reports.
- Audit reports.
- Mission specifications.
- Readiness matrices.
- Owner-decision proposals.
- Source manifests and reconciliation reports.
- JSON artifacts.
- Stopping points.
- Recoverable conversation/task records.
- Complete original prompts or reports embedded in ADR-023 or related architectural records.
- Previous versions of those artifacts.
### 16.1 Archive originals and excerpts correctly
For each candidate, distinguish:
- Full original artifact.
- Complete artifact embedded in an ADR.
- Excerpt.
- Summary.
- Decision record.
- Reference to an unavailable artifact.
Archive exact originals when recoverable.
If only an excerpt or summary exists, label it accordingly and link it to the containing ADR. Never fabricate missing text or claim that an excerpt is a full original.
### 16.2 Preserve ADR-023 and history
Do not remove old content from ADR-023.
Archive eligible artifacts separately, preserve their source section/path, stable archive ID, hash, version, and provenance, and add an append-only migration record or dated ADR section.
Do not rewrite old decisions or turn proposals into approvals.
### 16.3 Make migration safe and idempotent
Use a reviewable inventory, manifest, and checkpoint.
For each candidate, record:
- Source path or ADR section.
- Artifact type.
- Source identity.
- Archive ID.
- Hash.
- Version/supersession relationship.
- Import status.
- Duplicate handling.
- Exclusion reason.
- Error or retry state.
Rerunning the importer must not create uncontrolled duplicates.
Preserve distinct historical versions and legitimate distinct provenance.
### 16.4 Security
Exclude `.env`, API keys, private keys, tokens, credentials, and unauthorized sensitive configuration.
Do not print BHL's saved key or copy it into the archive.
Do not modify source files during ingestion.
### 16.5 Migration acceptance
After migration, reconcile all eligible candidates against the import manifest.
Each eligible candidate must be:
- Archived and verified.
- Explicitly excluded with a documented reason.
- Or marked pending with a precise blocker.
Report the actual numbers discovered, eligible, imported, verified, excluded, failed, unsupported, and pending.
Explain the previous report's 133 imported artifacts and 136 total entries using actual records.
Do not claim complete historical migration if eligible recoverable artifacts remain unprocessed.
## 17. AHO
Verify whether AHO files exist in the actual repository and authorized historical sources.
Do not invent the schema.
If no AHO files or schema exist, report the searched locations and keep AHO-specific parsing unsupported. Generic archival support does not imply semantic AHO support.
---
# PART VI — CONDITION A, CONDITION B, AND OWNER DECISION
## 18. Condition A — finish independent engineering work
Verify the project's formal definition of Condition A and complete all permitted work, including:
- Five-source technical audit.
- Targeted defect correction.
- Required tests.
- Archive integrity and retrieval verification.
- Historical migration.
- Per-source gap analysis.
- Final dependency matrix.
- Documentation and ADR updates.
- Evidence preparation for owner decisions.
Do not stop early because an owner decision or infrastructure dependency remains pending.
## 19. Condition B — governance and production blockers
Verify the formal definition of Condition B in the project records.
The reported baseline indicates partial blockers involving governance and PostgreSQL/production readiness.
Separate:
- Onboarding approval.
- CapVer authorization and execution.
- Licensing approval.
- Corpus completion.
- Full Dr. Duke projection.
- PostgreSQL/production persistence.
- Other actual authoritative requirements.
Do not invent approval or bypass a blocker.
Do not change production configuration.
Document precisely what remains blocked and what evidence is required to close it.
## 20. Next necessary owner decision
Unless new authoritative evidence changes the conclusion, preserve this recommendation:
**The next critical owner action is to accept the `onboarding ACTIVE` package for the intended target seat(s), then authorize/delegate CapVer. Start with the technically closest-ready seats: `G1-02` Organic Eprints and/or `G6-20` BHL.**
Prepare a decision-ready package containing:
- Current seat status.
- Exact onboarding acceptance criteria.
- Technical evidence already satisfied.
- Remaining conditions.
- Risks and implications.
- Exact owner decision required.
- CapVer delegation needed after onboarding acceptance.
Do not create approval or modify the activation state yourself.
Do not conflate this onboarding decision with full AGRIS corpus completion, full Dr. Duke projection, PostgreSQL readiness, or historical archive completion.
---
# PART VII — TESTING AND PRE-COMMIT REVIEW
## 21. Testing strategy
The latest report stated that 68 tests and 348 assertions passed. The broader 214-test/6,902-assertion run is historical evidence unless actually rerun.
Do not blindly repeat every test suite.
Run the smallest tests that establish each change, followed by the relevant regression suites.
Required coverage includes:
- Archive original preservation.
- SHA-256 verification and tampering detection.
- Version history and supersession.
- Idempotent migration.
- Cross-process durability.
- Retrieval of historical prompts/reports/stopping points.
- Secret exclusion.
- JSON parsing and metadata extraction.
- AHO behavior where actual artifacts support it.
- BHL live/application integration.
- OE persistence and query behavior.
- AGRIS ingestion and query behavior.
- Dr. Duke projection/reconciliation contracts.
- GBIF regression.
- Cross-source failure isolation.
- Relevant registry, onboarding, capability, and activation tests.
Record exact commands, exit codes, test counts, assertions, and actual outcomes.
Do not claim a test passed if it was not run.
## 22. Final Git and safety review
Before reporting:
1. Inspect `git diff --check`.
2. Inspect the complete relevant diff.
3. Review new and modified files.
4. Check for leaked secrets.
5. Ensure `.env`, SQLite databases, datasets, downloaded archives, caches, and temporary probes are not accidentally included in the change set.
6. Verify original ADR content and historical files remain intact.
7. Verify pre-existing WIP remains preserved.
8. Record actual final branch, HEAD, and worktree state.
No staging, commit, push, reset, clean, deployment, Docker, or destructive cleanup.
---
# PART VIII — FINAL ARABIC REPORT
## 23. Required report structure
Deliver one comprehensive Arabic report.
### Section 1 — Executive summary
State the verified overall status, completed work, unresolved gates, archive migration state, and final recommendation.
### Section 2 — GBIF individual completion report
Include current state, verified functionality, tests, outstanding work, and whether any action remains before retaining `ACTIVE`.
### Section 3 — Organic Eprints individual completion report
Include current state, completed integration, remaining technical gaps, governance gates, PostgreSQL requirements, exact closure criteria, and next actions.
### Section 4 — BHL individual completion report
Include live API evidence using the saved key without exposing it, application-path evidence, registration status, licensing, onboarding/CapVer, remaining gaps, and exact closure criteria.
### Section 5 — AGRIS individual completion report
Include current provider/corpus coverage, generation and record counts, licensing evidence, checkpoint status, query/application integration, activation criteria, and exact remaining actions.
Clearly distinguish partial corpus integration from full corpus completion and from legitimate activation.
### Section 6 — Dr. Duke individual completion report
Include reconciliation, projection coverage, relationship quality, semantic safeguards, licensing, application integration, activation criteria, and exact remaining actions.
Clearly distinguish limited projection from full projection and from legitimate activation.
### Section 7 — Consolidated completion matrix
Include every remaining action with:
- Source.
- Requirement.
- Category.
- Authoritative reference.
- Current evidence.
- Required closure evidence.
- Dependencies.
- Whether execution can proceed now.
- Priority.
- Legitimate completion status.
### Section 8 — Critical path and sequencing
Identify the fastest legitimate path to complete each source, the actions that can run in parallel, the earliest owner decision, and the infrastructure blockers.
### Section 9 — Unified archive implementation
Describe the actual architecture, integrity, versioning, search/retrieval, CLI, durability, and remaining limitations.
### Section 10 — Historical archive migration
Report actual inventory and coverage, including historical prompts, reports, complete artifacts embedded in ADR-023, excerpts, conversation/task records, and stopping points.
Explain how originals and historical versions were preserved.
### Section 11 — AHO status
Report actual discovery and schema evidence.
### Section 12 — Tests
List exact commands, exit codes, counts, assertions, and results. Separate newly executed tests from historical evidence.
### Section 13 — Changed files and ADR updates
List actual changes, purpose, generated artifacts, and local files that must remain uncommitted.
### Section 14 — Condition A / Condition B
State completed Condition A work and every unresolved Condition B requirement, distinguishing governance, corpus coverage, and production infrastructure.
### Section 15 — Owner decision package
Provide the exact onboarding `ACTIVE` and subsequent CapVer decision required for `G1-02` and/or `G6-20`, supported by the verified technical evidence.
### Section 16 — Final readiness recommendation
Unless new evidence legitimately changes the baseline, state:
**Maximum legitimate readiness: GBIF alone is `ACTIVE`; OE, BHL, AGRIS, and Dr. Duke have technical evidence but remain `NOT_READY` where mandatory governance or production gates remain unresolved.**
The next critical owner action is to accept onboarding as `ACTIVE` for the intended seat(s), beginning with `G1-02` and/or `G6-20`, then authorize/delegate CapVer.
Do not conflate this decision with AGRIS full-corpus completion, full Dr. Duke projection, PostgreSQL readiness, or historical archive migration.
### Section 17 — Git and safety
Report actual branch, HEAD, initial/final worktree state, preservation of WIP, and confirmation that no prohibited operation occurred.
### Section 18 — Exact remaining work to finalization
For each source, provide the smallest complete set of remaining tasks required to reach its next legitimate milestone and, separately, final activation.
Do not use vague statements such as "finish integration." State the concrete action, acceptance criteria, test/evidence, dependencies, and decision-maker.
---
# FINAL EXECUTION DIRECTIVE
Start by inspecting the current checkout and latest reports.
Complete all permitted Condition A engineering work. Audit the actual integration of all five sources. Review and repair the archive where necessary. Migrate eligible, recoverable historical prompts, reports, and conversation/task records preserved in ADR-023 and related architectural records. Produce a detailed, independent remaining-work report for each source and a consolidated critical path.
Use the saved BHL API key securely. Preserve all original content, provenance, versions, hashes, and architectural history.
Do not fabricate approvals, full-corpus completion, archive coverage, or production readiness.
**Finish the authorized engineering work, verify the actual results, leave the worktree uncommitted, and stop before commit/push for owner review.**
