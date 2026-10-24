<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16975). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 4:47 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16976, 16981; it is archived once. -->

---
# WSA-Enterprise — FINAL OWNER AUTHORIZATION & FIVE-SOURCE COMPLETION MISSION
## Full Five-Source Integration, Explicit Owner Decisions, CapVer, Legitimate Activation, Archive Integrity, and Pre-Commit Verification
**Mission date:** 2026-10-18  
**Repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Previously reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Scientific source membership:** `110.v1`  
**Architectural record:** ADR-023, including §8.51 and §8.52  
**Archive implementation:** `FilesystemMissionArtifactArchive`  
**Archive CLI:** `mission-artifacts`
---
# PART I — EXPLICIT OWNER AUTHORIZATION
## 1. Owner authorization for all five scientific sources
I, as the owner of WSA-Enterprise, explicitly authorize proceeding with the engineering completion, onboarding, integration, capability verification, and legitimate activation process for all five scientific sources:
1. **GBIF — `G5-04`**
2. **Organic Eprints — `G1-02`**
3. **BHL — `G6-20`**
4. **AGRIS — `G1-01`**
5. **Dr. Duke — `G1-17`**
This authorization covers the remaining engineering, programming, integration, testing, documentation, source-specific onboarding procedures, and legitimate activation work permitted by the approved architecture and project governance.
Do not limit the mission to Organic Eprints and BHL. Continue independent engineering work on all five sources.
Do not stop after preparing a plan, identifying gaps, or writing a report. Execute authorized work, repair demonstrated defects, run permitted tests, verify actual results, and complete every milestone whose prerequisites have been satisfied.
This authorization does not waive scientific evidence requirements, licensing restrictions, security controls, capability verification, or production acceptance criteria.
## 2. Explicit owner decision — Organic Eprints and BHL
The following is the owner's explicit decision and must be incorporated into the mission records and the final report.
**WSA-Enterprise — Owner Decision**
- **G1-02 Organic Eprints:** ACCEPT onboarding ACTIVE.
- **G6-20 BHL:** ACCEPT onboarding ACTIVE.
- **CapVer delegation:** AUTHORIZE for accepted seats, subject to approved prerequisites.
**Owner notes:**
"الموافقة على onboarding للمصدرين وفق معايير القبول المعتمدة، ثم تفويض CapVer لكل مصدر مقبول. لا يُعتبر أي مصدر ACTIVE قبل اجتياز التحقق والتسجيل والاختبارات المطلوبة."
### 2.1 Required interpretation
Treat this as an explicit owner authorization to accept onboarding for `G1-02` and `G6-20` and to authorize the subsequent CapVer process for both seats, subject to the approved prerequisites.
Do not leave these two onboarding decisions classified as awaiting the same owner approval already granted here.
Record the decision in the appropriate authoritative project record, using the project's established decision-recording procedure. Preserve the original decision history and add this decision append-only.
The original draft stated that the decision had not yet been recorded in the repository. Therefore, verify whether it has been recorded during this mission. Do not claim that repository recording has succeeded until the actual write and subsequent verification are complete.
### 2.2 CapVer execution
After verifying the applicable prerequisites, execute the authorized CapVer workflow for both accepted seats.
For each seat:
1. Verify the approved capability contract and required evidence.
2. Execute the project's actual CapVer procedure.
3. Record the real outcome in the authoritative Capability Store through the approved workflow.
4. Run the required regression and governed application-path tests.
5. Complete the required catalog and activation-registration steps only after their prerequisites are satisfied.
Do not treat authorization to perform CapVer as proof that CapVer has passed.
Do not simulate a successful verification by directly inserting or modifying capability records.
Do not report either source as activated merely because onboarding has been accepted.
If a formally required prerequisite remains unresolved, complete all independent work and report the exact blocker, required evidence, and next action. Do not silently bypass it.
## 3. Owner authorization for AGRIS and Dr. Duke
The owner authorizes the remaining engineering and programming work for:
- AGRIS — `G1-01`
- Dr. Duke — `G1-17`
This includes completing permitted integration work, source-specific contract verification, query and orchestration integration, persistence and indexing where required, provenance and licensing verification, tests, and preparation of any remaining source-specific owner decisions.
Determine from the authoritative ADRs, contracts, and existing governance records whether additional scope-specific approval is required for each source.
For AGRIS, distinguish a bounded, licensed subset from full-corpus completion.
For Dr. Duke, distinguish a limited legitimate search projection from a full projection, preserving all semantic and data-integrity safeguards.
Where this authorization and the existing authoritative decisions already permit the work, proceed without requesting redundant approval.
Where a distinct mandatory owner decision remains outside this authorization, complete the independent engineering work first and prepare a precise decision package. Do not invent approval, assume that a proposal is an accepted decision, or bypass a mandatory gate.
## 4. GBIF authorization
Preserve GBIF's existing legitimate `ACTIVE` state if the current evidence confirms it remains valid.
Verify its current registration, capability, governed application path, provenance, error handling, and regression coverage.
Do not rebuild working components or introduce unnecessary changes.
---
# PART II — ROLE AND EXECUTION MANDATE
## 5. Required role
Act as the Lead Software Engineer, Independent Pre-Commit Reviewer, Scientific Search Integration Engineer, Archive-System Engineer, Persistence Engineer, and Test Engineer for WSA-Enterprise.
This is an execution and verification assignment, not a planning-only exercise.
You are authorized to:
- Inspect the actual repository, source code, runtime wiring, tests, ADRs, owner decisions, manifests, reports, and historical records.
- Review and repair demonstrated defects in the five-source integration and unified archive.
- Complete all independent engineering work permitted by approved architecture and governance.
- Execute the onboarding and CapVer procedures authorized by this mission when their prerequisites are satisfied.
- Complete the legitimate activation process for each source when its acceptance criteria have been met.
- Migrate eligible, recoverable historical prompts, reports, and conversation/task records into the unified archive.
- Add focused tests, narrow documentation updates, manifests, and audit records.
- Run permitted tests and bounded live probes.
- Reuse valid completed work and existing evidence.
- Produce a precise, evidence-backed final Arabic report.
Do not ask repetitive questions or request approval again for decisions explicitly granted in Part I.
If a genuinely separate mandatory decision or external dependency remains, finish all independent work first and identify the exact decision or dependency.
## 6. Mandatory workstreams
Complete these workstreams within the same mission.
### Workstream A — Five-source completion
- GBIF — `G5-04`
- Organic Eprints — `G1-02`
- BHL — `G6-20`
- AGRIS — `G1-01`
- Dr. Duke — `G1-17`
Verify actual implementation, application integration, persistence, evidence and provenance, licensing, capability contracts, onboarding state, tests, governance, and activation gates.
### Workstream B — Unified archive and historical migration
Review and verify:
- `FilesystemMissionArtifactArchive`.
- Blob/content storage.
- SHA-256 integrity verification.
- Stable IDs and version history.
- Append-only supersession.
- Search, retrieval, and safe export.
- `mission-artifacts` CLI.
- Historical import manifests and checkpoints.
- Eligible JSON, AHO, Cursor prompts, engineering reports, test reports, mission records, stopping points, and recoverable conversation/task records.
- ADR-023 §8.51, §8.52, and related architectural history.
Migrate eligible, recoverable, non-sensitive artifacts within the authorized scope without deleting or overwriting originals.
### Workstream C — Per-source completion reports
Produce a separate evidence-backed completion report for each source. Specify exactly what remains for onboarding, integration, capability verification, registry/catalog registration, query and orchestration integration, persistence, application behavior, licensing, testing, governance, production readiness, and final legitimate activation.
### Workstream D — Condition A / Condition B
Verify the project's formal definitions of Condition A and Condition B.
Complete all permitted independent engineering work. Record all unresolved governance, licensing, corpus-scope, and production-infrastructure requirements.
Prepare decision packages only for decisions that remain genuinely outstanding after applying the explicit owner authorization in Part I.
---
# PART III — SAFETY AND BASELINE
## 7. Mandatory restrictions
The following restrictions are absolute:
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
Keep all changes uncommitted and stop before commit/push for final owner review.
## 8. Establish the actual baseline
Before modifying anything:
1. Inspect `git status --short`.
2. Confirm the actual branch and HEAD.
3. Inspect the current diff and untracked files.
4. Identify pre-existing WIP separately from changes made during this mission.
5. Locate the latest five-source report, archive report, readiness matrix, owner-decision package, and ADR-023 §8.51/§8.52.
6. Inspect the source registry, capability contracts, onboarding state, activation registrations, and application integration paths.
7. Locate archive manifests, checkpoints, imported artifacts, and tests.
8. Verify the actual state of the owner decision in Part I.
9. Verify whether any earlier execution changed the worktree after the previously reported HEAD.
The previously reported HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0`; the actual checkout is authoritative.
Do not assume that an earlier report proves the current runtime state.
## 9. Preserve completed work
Do not restart successful harvesting, extraction, reconciliation, or broad tests without a concrete reason.
Reuse valid artifacts and evidence. Use bounded probes, idempotent ingestion, and targeted regression tests.
If a defect is found, implement the narrowest appropriate correction, add a regression test, and run the affected tests.
Preserve pre-existing WIP, original source artifacts, and the original ADR history.
---
# PART IV — SOURCE-SPECIFIC COMPLETION
## 10. Required analysis for every source
For each source separately, document:
1. Current verified activation, technical, governance, and production status.
2. Completed implementation, tests, probes, records, indexes, manifests, and registrations.
3. Remaining engineering gaps.
4. Remaining governance or licensing gates.
5. Remaining production requirements.
6. Exact closure criteria, authoritative contract/ADR, required evidence, and dependencies.
7. Recommended sequence and work that can proceed in parallel.
8. Final classification using the repository's approved status vocabulary.
Use `ACTIVE` only when every applicable requirement has actually been satisfied and verified.
Use `TECHNICALLY_INTEGRATED / NOT_READY` when integration is established but required gates remain.
Use `PARTIALLY_INTEGRATED / NOT_READY` when required integration work remains incomplete.
Use `BLOCKED` when a specific dependency prevents further progress.
If the repository uses different status terms, document the mapping.
## 11. GBIF — `G5-04`
Verify:
- Source identity and membership.
- Capability contract and verification.
- Registry/catalog consistency.
- Query construction and CSQ semantics.
- Adapter behavior and normalization.
- Provenance and stable result identity.
- Governed application-facing search.
- Failure handling and coexistence with other sources.
- Existing tests and regression coverage.
- Any outstanding operational requirements.
Preserve the existing legitimate `ACTIVE` state if all applicable requirements remain satisfied.
Do not invent work simply to give GBIF the same number of tasks as other sources.
## 12. Organic Eprints — `G1-02`
Verify the reported SQLite generation 2 and 189 records if still current.
Check:
- Onboarding decision from Part I and its authoritative recording.
- Capability contract and actual CapVer outcome.
- Query construction and adapter integration.
- Result normalization and provenance.
- Persistent store and cross-process behavior.
- LocalIndex integration and idempotency.
- Registry/catalog registration.
- Governed application-facing search.
- Failure isolation.
- Licensing and documentation.
- PostgreSQL/production readiness.
Do not request the same onboarding approval again.
Execute the authorized CapVer procedure after confirming prerequisites. Then complete the governed registration and application-path verification.
Do not repeat the harvest unless a demonstrated defect justifies it.
## 13. BHL — `G6-20`
Use the saved `BHL_API_KEY` through the existing secure configuration path. Do not ask for the key again.
Verify:
- The environment variable is available to the actual runtime.
- The live API works using the saved key.
- Adapter and orchestration behavior.
- PartID, PartUrl, and provenance preservation.
- Actual governed application integration.
- Whether the temporary LEGACY registration is still used and whether it remains isolated from production activation.
- Source registry and catalog state.
- Capability contract and actual CapVer outcome.
- Failure isolation.
- Required tests.
- Licensing/terms evidence.
- Production readiness.
Do not print, log, archive, or otherwise expose the API key. Do not rotate it automatically.
Do not request the same onboarding approval again.
Execute the authorized CapVer procedure after confirming prerequisites, then complete production registration and regression testing without relying on a temporary LEGACY probe.
Distinguish live API success, application-path integration, governed registration, and approved activation.
## 14. AGRIS — `G1-01`
Verify the current manifest, checkpoint, generation, record count, and provider progress.
Check:
- Official ODS artifact and provider inventory.
- Resumable harvesting and checkpoint correctness.
- Provider-specific license evidence.
- Record provenance.
- Ingestion idempotency.
- LocalIndex/query visibility.
- Application integration.
- Source registry and capability contract.
- Required onboarding and CapVer workflow.
- Corpus completion status.
- Production readiness.
Separate these milestones:
1. A bounded, technically integrated and appropriately licensed subset.
2. A larger or complete corpus ingestion milestone.
3. Governance-approved activation.
4. Production persistence readiness.
Determine whether full-corpus completion is actually required by the authoritative contract for the specific activation milestone.
Do not assume all providers must be harvested before a narrower milestone can be approved if the approved contract allows that milestone.
Do not claim full coverage when the corpus remains incomplete.
Do not generalize licenses across providers. Do not restart the harvest from zero.
Complete all independent authorized work and prepare any genuinely outstanding source-specific decision.
## 15. Dr. Duke — `G1-17`
Verify the 16-table reconciliation and current search projection.
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
- Required onboarding and CapVer workflow.
- Whether full projection is a mandatory activation criterion.
- Production readiness.
Separate the minimum legitimate integration milestone from full dataset projection if approved contracts permit that distinction.
Do not blindly merge schemas, fabricate matches, or claim full projection while the dataset remains partial.
Complete all independent authorized work and prepare any genuinely outstanding source-specific decision.
---
# PART V — CROSS-SOURCE COMPLETION MATRIX
## 16. Consolidated dependency-aware matrix
After the five separate analyses, create a matrix showing:
- Each source's current status.
- What is complete.
- What remains.
- Whether each item is engineering, governance, licensing, corpus coverage, or infrastructure.
- The authoritative requirement.
- Current evidence.
- Required closure evidence.
- Whether it can be executed now.
- Dependency order and priority.
- Responsible decision-maker or external dependency, where known.
Incorporate the explicit owner decisions in Part I so that `G1-02` and `G6-20` are not incorrectly left waiting for the same onboarding decision.
Every outstanding item must be actionable and testable. Avoid vague entries such as "finish integration."
## 17. Critical path to final activation
Determine the actual critical path for each source.
Identify:
- The next action that unblocks the most remaining work.
- The earliest legitimate activation milestone for each source.
- Actions that can run in parallel.
- Any genuinely outstanding owner approval.
- Work requiring PostgreSQL or other infrastructure.
- Work dependent on corpus completion or additional licensing evidence.
- The precise conditions for each source to become `ACTIVE`.
Do not use one universal sequence when sources have different dependencies.
---
# PART VI — UNIFIED ARCHIVE AND HISTORICAL MIGRATION
## 18. Review the archive implementation
Inspect `FilesystemMissionArtifactArchive`, storage, metadata, stable IDs, SHA-256, versioning, search, retrieval, export, CLI, tests, manifests, and ADR-023 §8.51/§8.52.
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
## 19. Mandatory historical migration
Do not stop at the previously reported 632 archive entries.
Search all relevant authorized repository and architectural-record locations for eligible, recoverable:
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
### 19.1 Correct artifact classification
For each candidate, distinguish:
- Full original artifact.
- Complete artifact embedded in an ADR.
- Excerpt.
- Summary.
- Decision record.
- Reference to an unavailable artifact.
Archive exact originals when recoverable.
If only an excerpt or summary exists, label it accordingly and link it to the containing ADR. Never fabricate missing text or claim an excerpt is a full original.
### 19.2 Preserve architectural history
Do not remove old content from ADR-023.
Archive eligible artifacts separately, preserve their source section/path, stable archive ID, hash, version, and provenance, and add an append-only migration record or dated ADR section.
Do not rewrite old decisions or turn proposals into approvals.
Record the explicit owner decision in Part I as a new decision with its actual date and provenance. Do not retroactively alter historical records.
### 19.3 Safe, idempotent migration
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
### 19.4 Security
Exclude `.env`, API keys, private keys, tokens, credentials, and unauthorized sensitive configuration.
Do not print BHL's saved key or copy it into the archive.
Do not modify source files during ingestion.
### 19.5 Migration acceptance
Reconcile all eligible candidates against the import manifest.
Each eligible candidate must be archived and verified, explicitly excluded with a documented reason, or marked pending with a precise blocker.
Report actual numbers discovered, eligible, imported, verified, excluded, failed, unsupported, and pending.
Explain the previously reported 133 imported artifacts, 136 total entries, 584 eligible candidates, 450 new entries, 134 unchanged entries, and 632 total entries using the actual manifests and records.
Do not claim complete historical migration if eligible recoverable artifacts remain unprocessed.
## 20. AHO
Verify whether AHO files exist in the actual repository and authorized historical sources.
Do not invent the schema.
If no AHO files or schema exist, report the searched locations and keep AHO-specific parsing unsupported. Generic archival support does not imply semantic AHO support.
---
# PART VII — CONDITION A, CONDITION B, AND GOVERNANCE
## 21. Condition A — independent engineering
Verify the formal project definition of Condition A and complete all permitted work, including:
- Five-source technical audit.
- Targeted defect correction.
- Required tests.
- Archive integrity and retrieval verification.
- Historical migration.
- Per-source gap analysis.
- Final dependency matrix.
- Documentation and ADR updates.
- Recording the explicit owner decision in Part I.
- Execution of authorized onboarding and CapVer workflows where prerequisites are satisfied.
- Evidence preparation for any remaining owner decisions.
Do not stop early because an unrelated owner decision or infrastructure dependency remains pending.
## 22. Condition B — remaining blockers
Verify the formal project definition of Condition B.
Separate:
- Owner decisions already granted in Part I.
- CapVer prerequisites and actual verification outcomes.
- Any additional source-specific approval that remains mandatory.
- Licensing approval or restrictions.
- AGRIS corpus completion.
- Dr. Duke projection scope.
- PostgreSQL/production persistence.
- Other actual authoritative requirements.
Do not continue reporting the onboarding decisions for `G1-02` and `G6-20` as pending owner approval after the decision has been recorded and verified.
Do not bypass a separate legitimate prerequisite simply because the general owner authorization exists.
Do not change production configuration.
Document exactly what remains blocked and the evidence required to close each blocker.
## 23. Owner decision handling
The onboarding approval for `G1-02` and `G6-20` and the authorization to proceed with CapVer for accepted seats are explicitly granted in Part I.
Do not generate a duplicate request for those same decisions.
For AGRIS and Dr. Duke, use the approved architecture to determine whether a specific scope or licensing decision remains necessary. If it does, prepare a decision-ready package after completing independent engineering work.
A decision package is a request, not an approval. Never treat a proposal as accepted until an actual owner decision exists.
---
# PART VIII — TESTING AND PRE-COMMIT REVIEW
## 24. Testing strategy
The previous report stated that 68 tests and 377 assertions passed. Treat those as reported results until verified against actual execution evidence.
The broader 214-test/6,902-assertion run is historical evidence unless actually rerun.
Do not blindly repeat every test suite.
Run the smallest tests that establish each change, followed by relevant regression suites.
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
- Registry, onboarding, capability, and activation tests.
- Owner-decision recording and retrieval.
- CapVer workflow outcomes for accepted seats.
Record exact commands, exit codes, test counts, assertions, and actual outcomes.
Do not claim a test passed if it was not run.
## 25. Final Git and safety review
Before reporting:
1. Inspect `git diff --check`.
2. Inspect the complete relevant diff.
3. Review new and modified files.
4. Check for leaked secrets.
5. Ensure `.env`, SQLite databases, datasets, downloaded archives, caches, and temporary probes are not accidentally included in the change set.
6. Verify original ADR content and historical files remain intact.
7. Verify pre-existing WIP remains preserved.
8. Verify the explicit owner decision was recorded and that its provenance is correct.
9. Record actual final branch, HEAD, and worktree state.
No staging, commit, push, reset, clean, deployment, Docker, or destructive cleanup.
---
# PART IX — FINAL ARABIC REPORT
## 26. Required report structure
Deliver one comprehensive Arabic report with these sections:
### Section 1 — Executive summary
Verified overall status, completed work, unresolved gates, owner decision status, archive migration, and final recommendation.
### Section 2 — GBIF individual completion report
Current state, verified functionality, tests, outstanding work, and evidence supporting `ACTIVE`.
### Section 3 — Organic Eprints individual completion report
Owner decision recording, onboarding status, CapVer outcome, integration, catalog registration, persistence, remaining requirements, and exact closure criteria.
### Section 4 — BHL individual completion report
Owner decision recording, live API evidence without exposing the key, application-path evidence, CapVer outcome, registration, licensing, security, remaining gaps, and closure criteria.
### Section 5 — AGRIS individual completion report
Provider/corpus coverage, generation and record counts, licensing, checkpoint status, query/application integration, activation criteria, and exact remaining actions.
Clearly distinguish partial corpus integration from full corpus completion and legitimate activation.
### Section 6 — Dr. Duke individual completion report
Reconciliation, projection coverage, relationship quality, semantic safeguards, licensing, application integration, activation criteria, and exact remaining actions.
Clearly distinguish limited projection from full projection and legitimate activation.
### Section 7 — Consolidated completion matrix
Every remaining action with source, requirement, category, authoritative reference, evidence, required closure evidence, dependencies, execution status, priority, and legitimate completion status.
### Section 8 — Critical path and sequencing
Fastest legitimate path for each source, parallel actions, completed owner decisions, remaining owner decisions, and infrastructure blockers.
### Section 9 — Unified archive implementation
Actual architecture, integrity, versioning, search/retrieval, CLI, durability, and remaining limitations.
### Section 10 — Historical archive migration
Actual inventory and coverage, including historical prompts, reports, complete artifacts embedded in ADR-023, excerpts, conversation/task records, and stopping points.
Explain how originals and historical versions were preserved.
### Section 11 — AHO status
Actual discovery and schema evidence.
### Section 12 — Tests
Exact commands, exit codes, counts, assertions, and results. Separate newly executed tests from historical evidence.
### Section 13 — Changed files and ADR updates
Actual changes, purpose, generated artifacts, owner-decision recording, and local files that must remain uncommitted.
### Section 14 — Condition A / Condition B
Completed Condition A work and every unresolved Condition B requirement, distinguishing governance, corpus coverage, licensing, and production infrastructure.
### Section 15 — Owner decision and CapVer
Record the explicit decision for `G1-02` and `G6-20`, confirm the actual repository recording, and report CapVer authorization, prerequisites, execution results, and evidence. Do not report authorization as a successful CapVer result.
### Section 16 — Final readiness recommendation
State the verified state of all five sources. Do not claim `ACTIVE` without evidence that every applicable requirement has been satisfied.
### Section 17 — Git and safety
Actual branch, HEAD, initial/final worktree state, preservation of WIP, and confirmation that no prohibited operation occurred.
### Section 18 — Exact remaining work to finalization
For each source, provide the smallest complete set of tasks required to reach its next legitimate milestone and, separately, final activation.
Do not use vague statements such as "finish integration." State the concrete action, acceptance criteria, test/evidence, dependencies, and decision-maker.
---
# FINAL EXECUTION DIRECTIVE
1. Inspect the actual checkout and latest reports before modifying anything.
2. Apply and record the explicit owner decision for `G1-02` and `G6-20` exactly as stated in Part I.
3. Execute the authorized CapVer workflow for both seats when approved prerequisites are satisfied.
4. Continue the full authorized engineering and integration work for GBIF, Organic Eprints, BHL, AGRIS, and Dr. Duke.
5. Complete the permitted Condition A work, repair demonstrated defects, run required tests, and verify actual results.
6. Review and repair the unified archive where necessary, and migrate eligible, recoverable historical artifacts without losing original content, provenance, versions, or hashes.
7. Complete the per-source gap reports and consolidated critical path.
8. Complete every legitimate activation milestone whose prerequisites are satisfied.
9. For any remaining blocker, document its authoritative basis, missing evidence, and exact next action.
10. Produce one comprehensive Arabic final report.
**Owner approval is granted to proceed with all five sources. The explicit onboarding decisions for G1-02 and G6-20 are ACCEPT onboarding ACTIVE, with CapVer authorized for accepted seats subject to approved prerequisites. This is not a claim that either source has already passed CapVer or become activated.**
**Preserve all existing work. Do not commit or push. Stop at the fully verified pre-commit state for final owner review.**
