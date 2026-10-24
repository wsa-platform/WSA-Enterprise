<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17034). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 5:40 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17035; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION PROMPT
## Full Engineering Authorization for AGRIS and Dr. Duke
### Scope Approval, Implementation, Capability Verification, Governed Registration, and Real Activation
**Mission date:** 2026-10-18  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local repository path:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Scientific source membership:** `110.v1`  
**Primary architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`
**Target sources**
- AGRIS — `G1-01`
- Dr. Duke — `G1-17`
**Previously owner-approved sources that must remain protected from regression**
- Organic Eprints — `G1-02`
- BHL — `G6-20`
- GBIF — retain its verified existing state.
---
# 1. OWNER AUTHORIZATION — EXECUTE THE ENGINEERING WORK
I, as the owner of WSA-Enterprise, explicitly authorize you to act as the responsible:
- Lead Software Engineer.
- Software Architect.
- Scientific Search Integration Engineer.
- Data Ingestion and Projection Engineer.
- Capability and Registry Engineer.
- Test and Quality Engineer.
- Technical Documentation and Audit Engineer.
You are authorized to perform the actual engineering and programming work necessary to complete AGRIS and Dr. Duke integration and achieve their highest legitimately attainable activation state.
This authorization includes architecture design, code implementation, refactoring of affected components, data pipeline improvements, contract implementation, tests, defect correction, onboarding, capability verification, governed registration, and activation.
**This is an execution mandate. It is not a request to prepare another plan, perform another audit without implementation, or return a list of tasks for someone else to perform.**
Use the existing repository, its actual implementation, its architectural records, and the available execution environment.
Inspect the existing implementation only to the extent necessary to determine the current state and safely continue. Do not repeat completed forensic audits without a concrete reason.
Implement the necessary changes, run the permitted tests, correct demonstrated defects, and continue until actual activation is achieved or a genuinely mandatory external dependency remains.
Do not ask for another general authorization to perform work that this prompt explicitly authorizes.
## 1.1 Scope of the authorization
You may make the necessary technical and architectural decisions within the boundaries of:
- Existing owner-approved architectural decisions.
- ADR-023 and the applicable source contracts.
- Actual third-party licenses and terms.
- Scientific integrity and provenance requirements.
- Security and data protection requirements.
- Existing application behavior and regression requirements.
- The repository's mandatory capability, onboarding, and activation gates.
You may choose the most appropriate implementation when several compliant options exist.
You may implement the smallest legitimate activation scope that satisfies the authoritative contract, rather than unnecessarily processing an entire corpus.
You may also expand the scope when required by the approved contract and permitted by the actual license and available resources.
**The authority to make engineering decisions does not authorize you to fabricate legal permissions, invent owner approvals, bypass mandatory gates, or misrepresent partial integration as complete activation.**
---
# 2. MISSION OBJECTIVE
Complete the real integration and activation process for AGRIS and Dr. Duke.
The objective is not merely to produce records, populate a catalog, change a status field, or demonstrate that an adapter returns data.
The objective is to establish a verifiable end-to-end scientific search capability for each source, within its approved scope.
For each source, complete the applicable lifecycle:
1. Establish the actual starting state.
2. Determine and record the appropriate activation scope.
3. Verify license, provenance, data availability, and contractual prerequisites.
4. Implement or repair the necessary source-specific components.
5. Complete required ingestion, reconciliation, projection, and indexing.
6. Integrate the source into the actual scientific search pipeline.
7. Complete onboarding through the authoritative workflow.
8. Execute the actual Capability Verification process.
9. Verify capability persistence through the required store.
10. Register the source through the governed registry and catalog.
11. Execute real application-facing search tests.
12. Verify the actual activation state against authoritative records.
13. Run relevant regressions.
14. Update the architectural decision record and completion evidence.
15. Report the verified final state before commit.
Do not stop after completing only one of these stages.
Do not treat technical integration, data availability, onboarding, CapVer, registration, activation, and production readiness as interchangeable states.
---
# 3. INITIAL STATE AND REPOSITORY PROTECTION
## 3.1 Verify the actual repository state
Before modifying anything, inspect:
- Current repository path.
- Current Git branch.
- Current HEAD.
- `git status --short`.
- Existing modified and untracked files.
- The latest applicable AGRIS and Dr. Duke reports.
- The relevant sections of ADR-023.
- Existing owner decisions and scope-decision packages.
- Actual source adapters, ingestion pipelines, projections, registries, capability contracts, tests, and configuration.
- The actual availability of the required local execution environment.
Use the repository as it actually exists. Do not assume the previous report's HEAD or branch is still current.
Preserve all existing work, including unrelated or pre-existing WIP.
Do not overwrite an existing user change simply because a cleaner implementation is available.
If a necessary edit overlaps pre-existing WIP, inspect the overlap and preserve the existing intent.
## 3.2 Existing reported facts must be revalidated
Previous reports described the following state:
**AGRIS**
- `TECHNICALLY_INTEGRATED / NOT_READY`.
- Approximately `GEN=4`.
- Approximately `1,317` indexed records.
- Approximately `10 / 1,244` providers processed.
- A source-specific scope-decision package exists.
**Dr. Duke**
- `PARTIALLY_INTEGRATED / NOT_READY`.
- Approximately `16` reconciled tables.
- Approximately `GEN=2`.
- A projection of approximately `1,500` records.
- `FARMACY` and `FARMACY_NEW` must remain distinct unless an authoritative contract establishes a legitimate relationship.
**Other sources**
- GBIF has previously been reported as `ACTIVE`.
- Organic Eprints and BHL have previously received explicit owner acceptance for onboarding and conditional CapVer delegation.
- Previous reports identified a PostgreSQL Capability Store availability problem.
These are historical report values, not verified current facts.
Check the actual files, manifests, counters, database state, Git state, and authoritative records before using them in decisions or final reporting.
Do not rerun completed work merely to reproduce an old report.
---
# 4. AUTHORITATIVE ARCHITECTURE AND SCIENTIFIC INTEGRITY
## 4.1 Preserve CSQ as the scientific query authority
The Scientific Search architecture must preserve the identity and semantic intent of the user's scientific question.
Inspect and respect the current implementations and contracts for:
- `ScientificSearchQueryBuilder`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Source-specific adapters
- `ScientificResultNormalizer`
- Source Registry and Capability Contract
- Capability Store and CapVer
- `ScientificSourceCatalog`
- LocalIndex and persistence services
- Application-facing search and result rendering.
Trace the actual execution path from the user's scientific question to the final displayed result.
Do not replace CSQ with keyword-only search.
Do not reduce scientific questions to generic terms in a way that destroys the user's intent.
Do not weaken evidence requirements, claim relationships, provenance requirements, or final-answer eligibility gates merely to increase result counts.
Do not change unrelated architecture decisions unless the existing authoritative contract requires a correction. If a conflict is discovered, document the exact conflict and use the established architectural decision process.
## 4.2 Preserve identity and provenance
For all ingestion, reconciliation, and projection work:
- Preserve original source identifiers.
- Preserve source and artifact provenance.
- Maintain stable result identity.
- Distinguish original records from derived projection records.
- Preserve source-specific identifiers and semantic distinctions.
- Avoid accidental duplicate identities.
- Do not merge records solely because their names are similar.
- Do not fabricate relationships, citations, or provenance.
- Make partial, uncertain, unmatched, and conflicting records distinguishable.
- Preserve the original source files where the project contract requires them to remain unchanged.
Every activated result must be traceable to a legitimate source record and its applicable provenance.
---
# 5. SCOPE DECISIONS — ACT WITHIN THIS OWNER AUTHORIZATION
You are authorized to evaluate and implement the appropriate scope independently for AGRIS and Dr. Duke.
Do not treat an old scope proposal as an already approved decision.
Do not leave a source blocked simply because a previous report contained a request for owner scope selection. This prompt explicitly authorizes you to make the engineering scope decision within the limits below.
## 5.1 AGRIS scope decision
Inspect the actual package:
`2026-10-18-OWNER-DECISION-PACKAGE-G1-01-AGRIS-SCOPE.v1.json`
Verify its actual location and schema before editing or relying on it.
Determine whether a legitimately bounded provider subset can satisfy the source's authoritative activation contract.
If a licensed subset is sufficient:
- Select the smallest meaningful, compliant scope.
- Document the providers and artifacts included.
- Verify the applicable terms for every included provider or artifact category.
- Record the precise coverage and limitations.
- Implement and activate the bounded scope if all mandatory gates pass.
If the authoritative contract requires broader or complete coverage:
- Determine what remains missing.
- Continue the existing ingestion from its valid checkpoint.
- Implement the required coverage when legally and technically possible.
- Do not restart successful work from zero.
- Do not claim full-corpus coverage until it is demonstrated.
Do not infer that every provider permits identical access or redistribution simply because AGRIS offers an official access mechanism.
## 5.2 Dr. Duke scope decision
Inspect the actual package:
`2026-10-18-OWNER-DECISION-PACKAGE-G1-17-DUKE-SCOPE.v1.json`
Verify the real file location and schema.
Determine whether the bounded projection or a broader projection is necessary to satisfy the authoritative activation contract.
If a bounded projection is sufficient:
- Document its exact tables, records, and coverage.
- Verify the required schema relationships and identifiers.
- Preserve the distinction between source tables.
- Implement and activate the bounded scope if all mandatory gates pass.
If broader projection coverage is required:
- Identify the exact missing records, tables, relationships, or mappings.
- Complete the required projection and indexing.
- Verify its coverage and integrity.
- Do not present a partial projection as complete.
## 5.3 Recording scope decisions
Update the appropriate authoritative scope-decision records and ADR-023 using the project's established append-only conventions.
Record the engineering decision, its rationale, license basis, scope, limitations, acceptance criteria, and evidence.
Do not delete the earlier proposal or rewrite history to make the decision appear to have been approved earlier.
If an actual legal permission or contractual authorization is required and absent, do not invent it. Finish all independent engineering work and identify the precise external dependency.
---
# 6. AGRIS — G1-01 — REAL INTEGRATION AND ACTIVATION
## 6.1 Data acquisition and ingestion
Inspect the real AGRIS artifacts, manifests, provider inventory, checkpoints, ingestion implementation, and existing index.
Verify the actual current coverage.
Implement the required work for the approved scope:
- Official artifact discovery and validation.
- Provider-specific license and access checks.
- Resumable ingestion.
- Checkpoint integrity.
- Idempotent processing.
- Duplicate prevention.
- Source-specific identity mapping.
- Provenance preservation.
- Record-level eligibility enforcement where required.
- Accurate coverage accounting.
- Index consistency.
- Reproducible diagnostics.
Reuse valid checkpoints and successful ingestion work.
Do not start a full harvest from zero when the existing checkpoint can safely resume.
Do not treat a successful download as proof of complete ingestion.
Do not treat a successful harvest as proof of successful indexing or application integration.
## 6.2 AGRIS adapter and search path
Implement or repair the actual components required for:
- AGRIS source identity.
- Source Registry and Capability Contract.
- Source-specific query compilation.
- CSQ-preserving query construction.
- Adapter execution.
- Result normalization.
- Stable result identity.
- Provenance and license metadata.
- LocalIndex persistence.
- Orchestrator integration.
- Governed catalog registration.
- Application-facing search.
- Error handling and failure isolation.
Follow the existing architecture instead of creating a parallel, disconnected implementation.
Where an abstraction is missing, introduce the smallest maintainable abstraction needed to complete the actual path.
## 6.3 AGRIS activation acceptance criteria
AGRIS may be classified as activated only when all mandatory criteria for the chosen scope are proven:
1. The scope is recorded and compliant.
2. Applicable license and provenance requirements pass.
3. The required ingestion or data coverage is complete for that scope.
4. Indexing and persistence satisfy the applicable contract.
5. The actual capability-verification workflow succeeds.
6. Required capability evidence is persisted.
7. The governed registry and catalog recognize AGRIS.
8. The application-facing search path executes successfully.
9. Returned results are valid AGRIS records with traceable provenance.
10. Required tests pass.
11. The final activation state is supported by authoritative evidence.
A partial but contract-compliant dataset may support a bounded activation if the contract permits it. The final report must state that coverage accurately.
---
# 7. DR. DUKE — G1-17 — REAL INTEGRATION AND ACTIVATION
## 7.1 Schema and projection engineering
Inspect the actual source database artifacts, tables, reconciliation records, manifests, and projection code.
Implement the required work for:
- Schema validation.
- Source-to-domain mapping.
- Taxonomic and chemical identifiers.
- Relationship integrity.
- Reconciliation of unmatched and ambiguous records.
- Projection completeness.
- Stable source identity.
- Provenance preservation.
- Idempotent projection.
- Index generation.
- Error handling and diagnostics.
Do not assume that a table being reconciled proves that its records are correctly projected or searchable.
## 7.2 Mandatory scientific distinctions
Preserve `FARMACY` and `FARMACY_NEW` as distinct source entities unless a valid authoritative contract establishes a precise relationship.
Do not merge them by name similarity.
Preserve the distinction between original records and derived projections.
Treat unresolved relationships as unresolved rather than confirmed.
`ETHNOBOT` represents traditional-use information and must not be presented as proof of clinical efficacy.
Do not transform traditional use, in-vitro findings, or other limited evidence into stronger claims than the source supports.
Do not fabricate a citation, source relationship, identity mapping, or evidence level.
## 7.3 Dr. Duke adapter and search path
Implement or repair:
- Source Registry identity.
- Capability Contract.
- Projection-to-index integration.
- Source-specific query construction.
- CSQ preservation.
- Query compilation.
- Orchestrator integration.
- Result normalization.
- Stable identity and provenance.
- Governed catalog registration.
- Application-facing search.
- Failure isolation.
- Regression tests.
## 7.4 Dr. Duke activation acceptance criteria
Dr. Duke may be classified as activated only when:
1. The scope is recorded and compliant.
2. Required schema and projection checks pass.
3. Required scientific distinctions are preserved.
4. The required coverage is verified.
5. Indexing and persistence satisfy the applicable contract.
6. Actual CapVer succeeds.
7. Capability evidence is persisted where required.
8. The governed catalog recognizes the source.
9. The real application search path returns valid, traceable results.
10. Required tests pass.
11. The final activation state is supported by authoritative evidence.
A projection file or a non-empty adapter response is not sufficient proof of activation.
---
# 8. REAL CAPABILITY VERIFICATION AND GOVERNED REGISTRATION
## 8.1 Execute the real workflow
For each source, use the actual repository-supported workflow for:
- Prerequisite verification.
- Onboarding.
- Capability evaluation.
- Capability evidence generation.
- Capability persistence.
- Registry and catalog registration.
- Activation.
Do not create a parallel shortcut that bypasses the authoritative workflow.
Do not manually insert a `VERIFIED` record or set `ACTIVE` merely to make the final status appear successful.
Do not weaken the capability contract to pass the tests.
## 8.2 Distinguish the following states
Report each state separately:
- Owner authorization.
- Scope approval or documented scope decision.
- Technical integration.
- Data ingestion or projection completion.
- Onboarding completion.
- Contract validation.
- Capability evaluation.
- Capability persistence.
- Governed catalog registration.
- Actual activation.
- Production readiness.
Do not infer a later state from an earlier one.
A passing contract check does not automatically prove persisted CapVer.
A catalog entry does not automatically prove the source can return valid results through the real application path.
A passing local test does not automatically prove production readiness.
## 8.3 Capability Store availability
Previous reports indicated that the local PostgreSQL Capability Store was unavailable.
Recheck the actual current state.
If the store is available, execute the approved workflow and verify persisted evidence.
If it remains unavailable:
- Do not bypass the required store.
- Do not falsely report persistence.
- Do not label production activation complete if persisted capability is a mandatory requirement.
- Complete all independent source, query, data, testing, and documentation work.
- Identify the exact missing service, connection, driver, or environment dependency.
- Provide the precise next operation required to verify persistence.
Do not change production configuration or invent database access.
---
# 9. LOCALINDEX, POSTGRESQL, AND EXECUTION ENVIRONMENT
The project has previously used SQLite as a local validation option because PostgreSQL was unavailable. Revalidate the current state and the relevant contracts.
Use SQLite only where the existing project explicitly supports it for development or bounded testing.
Do not present SQLite validation as proof of PostgreSQL production readiness.
If PostgreSQL is mandatory for a production capability or index store, verify it through the actual supported environment when available.
Docker remains prohibited for this mission.
PHP may not be available locally. Verify the current environment rather than assuming it is available.
If a test cannot run because the runtime is missing:
- Do not claim it passed.
- Run independent tests that are permitted.
- Use the repository's supported alternative only if it genuinely exercises the relevant contract.
- Record the exact unexecuted test and dependency.
- Continue all other work that does not depend on that runtime.
Do not allow one infrastructure limitation to prevent unrelated implementation and verification.
---
# 10. PROTECT EXISTING SOURCES AND OWNER DECISIONS
Preserve the previously accepted onboarding decisions for:
- Organic Eprints — `G1-02`.
- BHL — `G6-20`.
Preserve the verified GBIF state.
Do not undo, silently downgrade, or rewrite their existing decisions.
The prior reports indicated that Organic Eprints and BHL had owner acceptance and conditional CapVer delegation, but that local PostgreSQL persistence was unavailable.
Do not interpret the owner's prior authorization as proof that their production capability records were persisted.
Do not use the AGRIS and Dr. Duke work to introduce regressions into these sources.
Run relevant regression tests for all protected sources.
If the current code reveals a genuine defect in an existing accepted source, fix it only when necessary and authorized by the applicable contracts. Document the change and test its impact.
For BHL, never print, copy into reports, or archive API credentials. If the repository indicates that key rotation is required, document the security issue and the authorized manual procedure; do not invent access or claim rotation occurred.
---
# 11. REQUIRED ENGINEERING WORKFLOW
Follow this workflow while maintaining a low message count and avoiding unnecessary pauses.
## Step A — Baseline
- Confirm branch, HEAD, and working-tree status.
- Read the latest relevant execution report and applicable ADR sections.
- Inspect the actual scope-decision packages.
- Identify the exact remaining gaps.
- Protect existing WIP.
Do not repeat previously completed audits without a new technical reason.
## Step B — Engineering decisions
For each source, determine:
- Required scope.
- Required data operations.
- Required code changes.
- Required persistence and capability steps.
- Required tests.
- Acceptance criteria.
Make these decisions using the existing authoritative contracts.
Do not wait for another general approval.
## Step C — Implementation
Implement the actual required changes.
Prefer focused changes that solve verified defects.
You may refactor affected components where necessary for correctness and maintainability.
Do not perform unrelated refactoring.
Do not stop after writing a design document if the implementation can be completed.
## Step D — Tests and corrections
Add or update tests for the identified gaps.
Run focused tests first, then relevant integration and regression tests.
When a test fails:
1. Inspect the actual failure.
2. Determine whether it exposes a defect or an environment limitation.
3. Correct the defect when authorized.
4. Rerun the relevant test.
5. Record the actual result.
Do not repeat a failing command indefinitely without changing the diagnosis or approach.
## Step E — Real activation
Execute the actual onboarding, CapVer, persistence, catalog registration, and application search workflows that are supported by the available environment.
Verify the resulting records and behavior.
Do not fabricate success to satisfy the mission.
## Step F — Documentation and review
Update ADR-023 and the appropriate completion matrix using append-only conventions.
Inspect the full relevant diff.
Produce one comprehensive Arabic report.
Stop before commit and push.
---
# 12. TEST REQUIREMENTS
Execute all applicable permitted tests, including:
### AGRIS
- License and provider-scope enforcement.
- Artifact and manifest validation.
- Checkpoint resume.
- Ingestion idempotency.
- Coverage accounting.
- Provenance preservation.
- Identity mapping.
- Index consistency.
- Query integration.
- Capability verification.
- Catalog registration.
- Application-facing search.
### Dr. Duke
- Schema validation.
- Relationship integrity.
- `FARMACY` / `FARMACY_NEW` distinction.
- Ambiguous and unmatched record handling.
- Projection completeness.
- Projection idempotency.
- Provenance preservation.
- Traditional-use semantic safeguards.
- Query integration.
- Capability verification.
- Catalog registration.
- Application-facing search.
### Shared architecture
- CSQ preservation.
- Query compilation.
- Orchestrator behavior.
- Result normalization.
- Stable identity.
- Failure isolation.
- Capability contract enforcement.
- Capability persistence.
- Source catalog behavior.
- Cross-source regressions.
- Regression coverage for GBIF, Organic Eprints, and BHL.
For every executed test, report:
- Exact command.
- Actual exit code.
- Test count and assertion count when available.
- Pass/fail outcome.
- Any skipped tests and the reason.
- Whether the test exercises fixtures, a local integration, a live adapter, a persisted capability record, or the real application path.
Never claim a test ran when it did not.
Do not use an old test result as proof that newly changed code passed.
---
# 13. SOURCE REGISTRY AND APPLICATION SEARCH VERIFICATION
For each target source, prove the full chain:
`User Scientific Question → CSQ → Query Compilation → Source Adapter → Source Data → Result Normalization → Provenance → Governed Results → Application Display`
Use the actual implementation and permitted execution path.
Verify that the query reaches the correct source, results retain their identity and provenance, and source failures do not corrupt the remaining search results.
Where live external access is available and permitted, perform bounded checks.
Where live access is unavailable, distinguish the limitation from a code defect and continue local verification.
Do not treat fixture-only tests as live-source proof.
Do not treat HTTP 200 alone as proof of scientific correctness.
Do not claim an end-to-end result unless the actual tested path reaches the application-facing result.
---
# 14. ARCHITECTURAL RECORDS AND AUDIT TRAIL
Update:
`docs/architecture/ADR-023-scientific-source-expansion.md`
Use the existing section sequence and conventions. Inspect the current file before choosing the next section number.
Append the new decisions and execution evidence. Do not delete old decisions, rewrite history, or replace previous reports.
Update the existing source-scope and completion artifacts rather than creating competing authoritative records.
The records must include:
- The owner's authorization represented by this mission.
- The actual scope chosen for AGRIS.
- The actual scope chosen for Dr. Duke.
- The rationale and applicable license evidence.
- The implementation changes.
- The actual data coverage.
- The onboarding outcome.
- The CapVer outcome.
- The persistence outcome.
- The catalog and application-search outcome.
- Test evidence.
- Production-readiness classification.
- Remaining blockers and exact closure criteria.
Clearly distinguish authorization from execution evidence.
Do not claim a source is activated just because the decision record says it should be.
---
# 15. ENGINEERING QUALITY REQUIREMENTS
Use your engineering judgment to achieve a maintainable and reliable implementation.
Prioritize:
- Correctness over superficial status changes.
- Small, coherent changes over broad speculative rewrites.
- Reproducibility and idempotency.
- Explicit contracts.
- Source-specific failure isolation.
- Strong tests for edge cases.
- Traceable scientific provenance.
- Accurate coverage reporting.
- Clear separation between local validation and production readiness.
- Consistency with the existing architecture.
Do not hard-code success states.
Do not weaken validations or remove assertions to make tests pass.
Do not silently swallow exceptions.
Do not replace meaningful source-specific behavior with generic placeholder results.
Do not fabricate data, identifiers, citations, capability evidence, license permissions, or production configuration.
Do not introduce a second registry or competing activation mechanism when an authoritative mechanism already exists.
---
# 16. STRICT GIT AND SAFETY RESTRICTIONS
You are authorized to modify source code, tests, and documentation necessary for this mission.
The following operations are explicitly prohibited:
- `git commit`
- `git push`
- Deployment.
- Docker or Docker-based execution.
- `git reset`.
- `git clean`.
- Destructive cleanup.
- Deleting or overwriting pre-existing work.
- Removing historical ADR content.
- Unapproved production configuration changes.
- Printing or exposing credentials.
- Destructive data migration.
- Replacing unavailable external services with fake production state.
Preserve all changes for owner review.
Before finishing:
1. Inspect `git status --short`.
2. Confirm the branch and HEAD.
3. Inspect the full relevant diff.
4. Run `git diff --check`.
5. Review new and modified files.
6. Verify that secrets, local databases, datasets, caches, and temporary artifacts are not accidentally included.
7. Confirm that pre-existing WIP remains intact.
8. Confirm that no prohibited operation was performed.
If a test or environment operation would violate these restrictions, do not execute it.
---
# 17. FINAL COMPLETION STANDARD
Your mission is not complete merely because code was changed.
For each source, determine the highest state supported by actual evidence:
- `NOT_READY`
- `TECHNICALLY_INTEGRATED`
- `ONBOARDED`
- `CAPABILITY_VERIFIED`
- `CAPABILITY_PERSISTED`
- `GOVERNED_REGISTERED`
- `ACTIVE`
- `PRODUCTION_READY`
Use the repository's actual state vocabulary if it differs, and map the states explicitly.
Never promote a source to a higher state without satisfying the corresponding authoritative requirements.
If the chosen activation scope is legitimately bounded, the source may be activated within that scope when the contract allows it. State the limitations accurately.
If a mandatory external dependency remains, complete all independent work and provide the exact blocker and acceptance criteria.
---
# 18. REQUIRED FINAL REPORT — ARABIC ONLY
Return one comprehensive Arabic engineering report containing:
## A. Executive summary
- What was actually implemented.
- Which source reached which verified state.
- Which activation requirements remain incomplete.
## B. AGRIS
- Chosen scope and authoritative decision record.
- Actual provider/artifact coverage.
- License and provenance evidence.
- Ingestion/checkpoint/index state.
- Code changes.
- CapVer and persistence outcome.
- Catalog registration.
- Real application-search evidence.
- Tests and results.
- Final verified classification.
## C. Dr. Duke
- Chosen projection scope.
- Actual table/record coverage.
- Schema and relationship integrity.
- `FARMACY` / `FARMACY_NEW` handling.
- `ETHNOBOT` semantic safeguards.
- Code changes.
- CapVer and persistence outcome.
- Catalog registration.
- Real application-search evidence.
- Tests and results.
- Final verified classification.
## D. Shared architecture
- CSQ preservation.
- Query compiler and orchestrator behavior.
- Normalization and provenance.
- Failure isolation.
- Regression results for protected sources.
## E. Files and architecture records
- Exact modified and new files.
- ADR-023 sections appended.
- Completion-matrix updates.
- Important implementation decisions.
## F. Test evidence
- Exact commands.
- Exit codes.
- Test and assertion counts.
- Failures and corrections.
- Skipped tests and environment limitations.
- Clear distinction between fixture, local, live, persisted, and application-facing evidence.
## G. Remaining blockers
For each blocker, provide:
- The exact missing requirement.
- Evidence showing why it remains unresolved.
- Why it cannot be completed in the current environment.
- The smallest required external action.
- The acceptance test that will prove closure.
## H. Final Git state
- Branch and HEAD.
- Working-tree state.
- Confirmation that no commit or push occurred.
- Confirmation that pre-existing WIP was preserved.
Do not use generic statements such as "activation is complete" without evidence.
Do not claim full corpus coverage if only a subset was processed.
Do not claim production readiness based solely on local SQLite tests.
Do not claim CapVer persistence when the required store did not persist the record.
---
# FINAL DIRECTIVE
**Act as the responsible engineer and architect. Execute the actual engineering and programming work needed to complete AGRIS and Dr. Duke activation.**
Make the necessary implementation decisions within the authorized scope, resolve code and integration gaps, complete the required ingestion or projection, integrate the real scientific search path, run actual capability verification, complete governed registration, and verify the resulting state through authoritative records and reproducible tests.
Do not stop at planning or reporting.
Do not ask for another general authorization.
Do not fabricate success or bypass mandatory scientific, licensing, capability, security, or production gates.
Complete every independent task that can be safely executed in the current environment.
If a genuine external dependency remains, finish the rest of the work and document the exact blocker.
**Preserve the repository and all existing work. No commit, push, deployment, Docker, reset, clean, or destructive cleanup. Stop at the fully reviewed pre-commit state and return the comprehensive Arabic report.**
