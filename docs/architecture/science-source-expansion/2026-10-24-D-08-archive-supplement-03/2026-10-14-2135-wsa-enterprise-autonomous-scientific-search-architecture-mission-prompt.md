<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15311). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 9:35 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15312; it is archived once. -->

---
# WSA-Enterprise — Autonomous Scientific Search Architecture Mission
## 109-Source Expansion | Forensic Audit | Capability Architecture | Strict Security Protocol
**Mission type:** Large autonomous engineering investigation  
**Primary objective:** Establish an evidence-backed, implementation-ready architecture for a general-purpose scientific search engine that integrates the 109 approved scientific source entries and supports future expansion without source-specific patches or case-by-case exceptions.
**Operating mode:** Maximum safe autonomy within the authorized read-only scope. Work continuously through all investigation stages, maintain evidence, resolve questions through repository inspection where possible, and deliver one consolidated report. Do not stop for routine clarification. Do not make owner decisions or cross authorization boundaries.
---
# 1. Mission objective
Build the complete architectural understanding needed to evolve WSA-Enterprise into a scalable scientific search engine that can:
- Preserve the scientific identity and intent of the user's question.
- Select sources based on verified capabilities and the requirements of the question.
- Generate source-appropriate queries through stable integration contracts.
- Support heterogeneous APIs, repositories, OAI-PMH interfaces, datasets, and other verified access mechanisms.
- Normalize results without losing source identity, provenance, scientific meaning, or relevant metadata.
- Evaluate evidence consistently across sources.
- Add future sources without repeatedly changing shared search logic.
- Validate source membership, capabilities, integrations, and behavior through repeatable tests and evidence.
The 109 approved source entries are the current membership baseline to reconcile, not proof that all 109 sources are implemented, accessible, activated, or operational.
**Do not turn this mission into a collection of individual bug fixes.** A source-specific observation may be used to identify a general architectural weakness, but the mission must focus on the underlying contract or shared mechanism.
# 2. Non-negotiable security and preservation rules
These rules override every other instruction in this mission.
## 2.1 Git and repository preservation
- Do not stage, commit, push, pull, fetch, switch branches, reset, clean, stash, rebase, cherry-pick, or rewrite history.
- Do not delete, rename, move, overwrite, restore, or normalize any existing file.
- Do not discard or modify user changes, untracked files, ignored files, temporary evidence, generated artifacts, or worktrees.
- Do not modify `.git` metadata, Git configuration, remotes, hooks, refs, or index state.
- Do not assume the current branch or HEAD matches a previous report. Verify the current state first.
- If an inspection command might change repository state, do not run it.
- Preserve all existing uncommitted changes exactly as found.
## 2.2 Execution restrictions
Unless separately authorized in a future instruction, do not:
- Run Docker or other containers.
- Access a network or call external source APIs.
- Connect to databases or run migrations.
- Install, update, or download dependencies.
- Run deployments, smoke tests against real environments, or operational scripts.
- Execute application tests or commands that may write caches, logs, databases, snapshots, or generated files.
- Enable or activate any source.
- Modify application code, tests, configuration, documentation, or architectural decision records.
Read-only static inspection is permitted, provided it does not violate the preservation rules.
## 2.3 Architectural protections
- Do not edit ADR-023 or any other architectural decision record.
- Do not declare Phase 1 closed.
- Do not start Phase 2.
- Do not interpret a previous approval for a narrow task as authorization for unrelated work.
- Do not modify the source registry, capability declarations, selectors, adapters, CSQ, query builders, normalizers, or answer synthesis components.
- Do not create new exceptions to accommodate individual source behavior.
- Do not claim that tests passed unless they were actually executed and their results were observed.
- Do not claim that a source is accessible, licensed, integrated, or operational without supporting evidence.
If a proposed action requires additional authorization, document it and continue all independent read-only investigation.
# 3. Stage 0 — Establish and preserve the baseline
Before examining architecture:
1. Record the current branch and HEAD.
2. Record staged and unstaged tracked changes.
3. Record untracked and ignored files without altering them.
4. Record stashes and worktree information without changing either.
5. Identify the latest available baseline and prior audit reports.
6. Record the exact commands used and their outputs where practical.
7. Verify that the inspection itself did not change Git state or tracked/untracked content.
Use existing evidence and hashes where available. Do not create new files in the repository for audit output. Keep the report outside the repository or return it in the final response.
If the current state differs from previous reports, use the actual current state as the baseline and explain the difference. Never silently reconcile or overwrite historical evidence.
# 4. Stage 1 — Reconstruct the real production search architecture
Trace the actual execution path from user question to displayed scientific result.
Investigate the following components, verifying their existence, responsibilities, callers, and data contracts:
- CSQ — canonical semantic authority for the scientific question.
- `ScientificSearchQueryBuilder`.
- `ScientificQueryCompiler`.
- RSC and `MultiSourceScientificSearchOrchestrator`.
- Source Registry and source identity mechanisms.
- Source Capability Contract and capability consumers.
- Source adapters and source-specific query translation.
- `ScientificResultNormalizer`.
- Evidence and provenance handling.
- Relevance, confidence, and eligibility gates.
- `AnswerComposer`.
- Results List and Viewer presentation paths.
- Relevant Home and Crop search paths and their isolation boundaries.
For each component, document:
- Exact file path and principal symbols.
- Inputs and outputs.
- Upstream callers and downstream consumers.
- Which layer owns semantic meaning.
- Which layer owns query construction.
- Which layer owns source selection.
- Which layer owns normalization and provenance.
- Which layer owns evidence evaluation and final display.
- Whether its responsibilities are explicit, duplicated, incomplete, or contradictory.
- The evidence supporting each conclusion.
Do not assume the architecture described in previous reports is identical to the current repository. Verify it.
Produce an architecture flow diagram in the report, with unresolved or inferred edges clearly marked.
# 5. Stage 2 — Source Registry forensic investigation
Find every registry-like structure, source catalog, source list, configuration map, adapter registration mechanism, and source membership record.
Determine whether the project has one canonical registry or competing sources of truth.
Inspect:
- Identity and stable identifiers.
- Canonical names and aliases.
- Scientific specialization and subject taxonomy.
- Source type and access mechanism.
- Registry ownership and update process.
- Duplicate detection and identity collisions.
- Verification status and evidence.
- Runtime registration and discovery.
- Enablement or activation flags.
- Relationship between membership and operational availability.
- Validation of completeness and consistency.
Reconcile the complete approved list of 109 source entries against the actual repository structures.
Do not recreate the list from memory or silently replace it with a different list. Locate the authoritative membership source and preserve its original identifiers and classifications.
Every entry must receive a documented status such as:
- `VERIFIED`
- `PARTIAL`
- `MISSING`
- `UNVERIFIED`
- `NOT_EXECUTED`
Define these statuses precisely in the report. Use `NOT_EXECUTED` for runtime checks that were not performed, and never interpret it as a confirmed failure.
# 6. Stage 3 — Source Capability Contract investigation
Locate the actual capability contract, if one exists, and every implementation that declares, consumes, or validates it.
Determine whether the contract can accurately represent, where applicable:
- Source identity and specialization.
- Supported scientific content and record types.
- Search and query mechanisms.
- Query syntax and source-specific query translation.
- Structured filters and supported query fields.
- Pagination and result limits.
- Authentication requirements.
- Rate limits and usage restrictions.
- Licensing and redistribution constraints.
- Availability and verification evidence.
- Provenance and metadata completeness.
- Error classification and retryability.
- Unsupported operations.
- Capability confidence or verification state.
Do not require every source to support every capability. The architecture must distinguish between:
1. Supported and verified.
2. Supported but not yet verified.
3. Unsupported by design.
4. Unknown due to insufficient evidence.
5. Temporarily unavailable or not tested.
Check whether capabilities are executable contracts enforced at integration boundaries or merely descriptive metadata.
Determine whether a new source can be integrated by registering its identity, declaring verified capabilities, implementing the required adapter boundary, and passing shared contract tests—without adding source-specific branches to shared search logic.
# 7. Stage 4 — Reconcile all 109 source entries
Build a complete matrix covering all 109 approved entries, with no silent omissions.
Required columns:
1. Original approved source name.
2. Existing stable identifier.
3. Scientific specialization.
4. Canonical identity verification.
5. Registry location and status.
6. Capability contract location and status.
7. Verified access method.
8. Query-construction mechanism.
9. Adapter or integration status.
10. Normalization and provenance status.
11. Runtime verification status.
12. Licensing/access restrictions, where documented.
13. Missing general architectural capability.
14. Evidence needed to close the gap.
15. Dependency or blocker.
Where an entry cannot be matched confidently, mark it unresolved rather than inventing an identity.
Where external facts would normally be required, record the evidence needed. Do not access the network in this mission.
Provide both:
- A summary by status and scientific group.
- The full entry-by-entry reconciliation.
Ensure summary counts reconcile exactly with the detailed matrix. Do not claim the matrix is complete until all 109 entries are accounted for.
# 8. Stage 5 — General query and capability-driven selection architecture
Investigate whether the engine currently distinguishes these responsibilities:
1. Understanding the scientific question.
2. Preserving question identity and scientific relationships.
3. Translating question requirements into a retrieval specification.
4. Determining source capability requirements.
5. Selecting suitable sources.
6. Constructing source-specific queries.
7. Executing retrieval.
8. Normalizing and deduplicating results.
9. Evaluating evidence.
10. Composing and displaying an answer.
Identify any responsibilities that are missing, duplicated, or coupled too tightly.
Evaluate the architecture against these general principles:
- The scientific question remains authoritative; keyword expansion must not replace its meaning.
- The selector uses verified capability contracts rather than arbitrary source-specific conditions.
- Shared orchestration coordinates retrieval without embedding every source's peculiarities.
- Source adapters isolate protocol and query differences.
- Normalization preserves provenance and scientific distinctions.
- Unknown capability is not automatically treated as supported.
- Unsupported operations fail transparently and do not silently alter the user's question.
- One source's limitation does not corrupt another source's query or results.
- Adding a source does not require changing unrelated sources.
- New source integration follows a repeatable, evidence-backed process.
Do not use the previously observed FAOSTAT behavior, or any other isolated case, as a reason to implement a dedicated repair. Use observations only to evaluate whether a general contract is adequate.
# 9. Stage 6 — Contract testing and scalability
Inventory existing tests and test contracts relevant to:
- Registry identity and membership completeness.
- Capability validation.
- Source selection.
- Query translation.
- Preservation of scientific question identity.
- Adapter conformance.
- Normalization.
- Provenance preservation.
- Duplicate handling.
- Error isolation.
- Confidence and evidence boundaries.
- Results List and Viewer contracts.
- Isolation between Home and Crop search paths.
- Adding a new source without regression in existing sources.
For each test suite, record the file path, test class, tested invariant, and whether its evidence is current, historical, or not executed in this mission.
Do not execute tests under the current authorization.
Design a future shared source-adapter contract test suite that can validate each source against the same core invariants while permitting legitimate source-specific differences.
The goal is to scale test coverage as sources are added, not to accumulate special-case tests for every previously observed incident.
# 10. Stage 7 — Architectural gap analysis
Create a ranked gap matrix with:
- Gap identifier.
- General architectural problem.
- Evidence and affected components.
- Impact on integrating the 109 sources.
- Impact on future expansion.
- Security or provenance implications.
- Dependencies.
- Smallest coherent architectural remedy.
- Files/components potentially affected.
- Tests and acceptance criteria required.
- Authorization required.
- Whether the gap blocks source expansion.
Rank by dependency and architectural importance, not by how easy a fix appears.
Separate:
- Confirmed defects.
- Missing functionality.
- Design ambiguity.
- Missing evidence.
- Environment limitations.
- Owner decisions.
- External dependencies.
Do not conflate these categories.
# 11. Stage 8 — Prepare a future implementation roadmap
Without implementing anything, produce a staged roadmap that minimizes unnecessary changes and avoids per-source patches.
For each proposed stage, state:
- Objective.
- Prerequisites.
- Files/components likely to change.
- Required architectural decisions.
- Security boundaries.
- Acceptance criteria.
- Test strategy.
- Rollback and preservation considerations.
- Explicit authorization needed before work begins.
The roadmap must establish a clear sequence for:
1. Confirming canonical source membership.
2. Establishing or completing the Registry and Capability Contract.
3. Defining shared adapter conformance.
4. Establishing capability-driven selection and source-specific query boundaries.
5. Completing normalization and provenance invariants.
6. Validating the architecture against the 109 entries.
7. Enabling sources only after their individual evidence and contract requirements are satisfied.
8. Expanding through a repeatable onboarding process.
These are planning stages, not permission to execute them.
# 12. Stage 9 — Evidence integrity and final verification
Before reporting completion:
- Recheck branch and HEAD.
- Recheck Git status and preservation indicators.
- Confirm no tracked or untracked project files were modified by the investigation.
- Confirm no source was activated.
- Confirm ADR-023 was not changed.
- Confirm no tests, Docker operations, network operations, database operations, installations, or deployment operations were performed.
- Distinguish repository evidence from historical reports and inference.
- Identify any checks that could not be completed safely.
If any unexpected mutation is detected, stop further work, preserve the resulting state, document the exact evidence, and do not attempt automatic cleanup or restoration.
# 13. Final deliverables
Return one consolidated report, not a series of partial reports.
The report must contain:
1. Executive summary.
2. Verified repository baseline.
3. Production architecture diagram and component trace.
4. Source Registry audit.
5. Capability Contract audit.
6. Complete reconciliation of the 109 approved entries.
7. Source selection and query-construction analysis.
8. Normalization and provenance analysis.
9. Shared contract-testing inventory and proposed framework.
10. Ranked architectural gap matrix.
11. Staged implementation roadmap.
12. Unresolved owner decisions.
13. Evidence limitations.
14. Preservation verification.
15. One recommended next action.
For every significant finding, provide exact repository paths, symbols, line references, and the basis of the claim where available.
Do not fabricate counts, statuses, tests, source identities, external verification, or approvals.
## Definition of mission completion
This investigation is complete only when:
- All 109 approved entries are reconciled or individually identified as unresolved.
- Registry and capability architecture have been examined from declaration through consumption.
- The production search path has been traced using repository evidence.
- General architectural gaps are separated from source-specific incidents.
- A coherent, dependency-ordered implementation roadmap exists.
- Preservation and authorization boundaries are verified.
- The final report identifies the single best next action.
**Stop after delivering the consolidated report. Do not modify files or begin implementation. Wait for explicit authorization for any subsequent implementation, runtime testing, Docker use, network access, or architectural decision-record changes.**
