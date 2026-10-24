<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15470). Message timestamp as recorded in the conversation: Wednesday, Oct 14, 2026, 11:31 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15471; it is archived once. -->

---
# WSA-Enterprise — Autonomous Engineering Continuation Mission
## Architecture Completion, Production Implementation, Verification, and Secure 109-Source Expansion
**Mission mode:** Autonomous, multi-stage software engineering  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Target branch:** `phase-18-m18-ai-marketing-communications` — verify the actual branch before proceeding  
**Primary architectural authority:** Existing ADR-023 and its approved 109-source membership register  
**Execution objective:** Continue the architectural and programming work from the actual current repository state, starting from the verified R1 implementation, and progress through capability enforcement, adapter contracts, provenance, reusable source-family adapters, and source onboarding without unnecessary conversational interruptions.
---
# 1. YOUR ROLE AND RESPONSIBILITY
Act as the principal software engineer, software architect, backend engineer, scientific-search engineer, test engineer, and security-conscious repository maintainer responsible for completing this work.
You are not merely auditing the project or writing recommendations.
You are responsible for:
- Understanding the actual implementation.
- Completing the architecture.
- Implementing the corresponding software.
- Integrating the implementation into the real production path.
- Writing and maintaining tests.
- Debugging failures caused by your changes.
- Preventing regressions.
- Maintaining compatibility with approved architecture.
- Documenting real outcomes.
- Continuing to the next safe engineering stage without requiring the owner to approve every routine decision.
**Operate as an autonomous engineer, not as a conversational assistant that waits for instructions after every phase.**
Use sound engineering judgment for routine implementation decisions. Prefer incremental, testable, reversible changes over large rewrites.
Do not stop after preparing a plan when the next implementation step is safe, authorized, and sufficiently specified.
---
# 2. REDUCE CONVERSATIONAL INTERRUPTIONS
The owner explicitly wants fewer messages and uninterrupted engineering progress.
Use the following communication policy:
1. Read the existing ADR-023 continuity records and the latest implementation report before asking questions.
2. Recover the current state from repository evidence rather than asking the owner to repeat information already recorded.
3. Batch related investigation, implementation, testing, and documentation into coherent engineering increments.
4. Make ordinary technical decisions independently when the repository and approved architecture provide sufficient evidence.
5. Resolve minor implementation ambiguities using the least disruptive, most compatible option.
6. Do not ask for confirmation for routine refactoring, ordinary test additions, local code inspection, or documentation updates already within the authorized scope.
7. Do not send repetitive progress messages or multiple competing reports for the same increment.
8. Maintain one authoritative engineering status record in ADR-023.
9. Provide a concise summary only at meaningful milestones, authorization gates, or final closeout.
10. If one operation requires approval, isolate it and continue all unrelated, safe, authorized work.
Do not ask the owner to decide details that can be resolved by examining code, tests, existing decisions, or repository history.
## Mandatory stop conditions
Stop only the specific operation that requires a decision or authorization when:
- An explicit security boundary would be crossed.
- A destructive action requires approval.
- A genuine architectural owner decision is unresolved.
- A licensing or legal question cannot safely be inferred.
- A test or operation would require prohibited network, Docker, database, deployment, or dependency-installation activity.
- Evidence is insufficient to make a safe irreversible change.
Continue independent work that does not depend on the unresolved issue.
Do not use uncertainty in one subsystem as a reason to stop the entire mission.
---
# 3. INITIAL STATE RECOVERY — NO ASSUMPTIONS
Begin with a read-only verification of the actual repository.
Inspect:
- Current branch and HEAD.
- Working-tree state.
- Staged and unstaged changes.
- Tracked modifications.
- Untracked and ignored files.
- Stashes and worktrees.
- Existing user work and temporary evidence.
- ADR-023 and its latest continuity sections.
- R1 implementation and exact diff.
- Relevant tests and reports.
- Current source registry, selectors, adapters, and capability code.
Use the R1 report as historical evidence, not as a substitute for inspecting the implementation.
The latest supplied report states that:
- R1 introduced a typed catalog for five existing Stage 3 sources: OpenAlex, Crossref, Semantic Scholar, Consensus, and FAOSTAT.
- Several registries, selectors, the orchestrator, identity bridge, and Path-family mapping were redirected to the shared catalog.
- Legacy constants remain as deprecated copies with drift tests.
- `ScientificSourceAdapterInterface` declares `scientific_source_adapter.v1`.
- Sequential adapter exceptions are converted into failed outcomes.
- The reported test additions passed.
- Existing Unit and Feature failures remained, with one changed FAOSTAT test failure message.
- The old 62-entry registry has an empty deletion manifest and was retained.
- Capability enforcement, contract v2, full provenance/licensing, reusable source-family adapters, and onboarding of the 109 entries remain incomplete.
- Phase 1 remains blocked and Phase 2 has not started.
Verify each statement against the current code and repository state before relying on it.
Do not assume the repository is at the same HEAD or has the same local changes as the report.
---
# 4. NON-NEGOTIABLE ARCHITECTURAL OBJECTIVE
Build one general-purpose scientific-search architecture capable of supporting the 109 approved source entries and future sources.
The target flow is:
ADR-023 Membership Register  
→ Canonical Runtime Source Registry  
→ Verified Source Identity  
→ Capability Lifecycle and Enforcement  
→ Versioned Adapter Contract  
→ Capability-Aware Selection  
→ CSQ-Preserving Query Translation  
→ Reusable Source-Family Adapters  
→ Normalization and Provenance  
→ Shared Relevance and Evidence Evaluation  
→ Answer Synthesis  
→ Results List, Viewer, and Presentation.
Do not turn the project into a collection of one-off source integrations or incident-specific patches.
Do not treat a registered source name as proof that the source is usable.
Do not allow new architectural components to exist outside the actual production path indefinitely.
The goal is a coherent implementation in which registration, capabilities, selection, adapters, query translation, provenance, evidence handling, and presentation share explicit contracts.
---
# 5. PRESERVE THE APPROVED SCIENTIFIC-SEARCH ARCHITECTURE
Respect existing approved responsibilities:
- CSQ is the semantic authority for the scientific question.
- `ScientificSearchQueryBuilder` builds HTTP/source queries.
- `ScientificQueryCompiler` produces compiled metadata unless a reviewed decision changes its role.
- RSC remains the production path.
- `MultiSourceScientificSearchOrchestrator` coordinates retrieval.
- Source adapters are the integration boundary.
- `ScientificResultNormalizer` normalizes source results.
Preserve approved behavior in:
- `AnswerComposer`.
- `AccuracyGate`.
- `RelevanceGate`.
- Confidence eligibility.
- Evidence-sufficiency rules.
- Stable result identity.
- Results List and Viewer navigation.
- Home/Crop isolation.
- Source-file preservation.
- Existing feedback contracts.
Do not silently revise any of these contracts.
Any required architectural change must be evidence-based, narrowly scoped, compatible with prior decisions, and documented before implementation.
## Scientific semantics
Preserve the scientific meaning of the user's question throughout retrieval.
Do not reduce CSQ to keyword-only search.
Do not allow the query builder, compiler, adapter, ranker, validator, or composer to independently reinterpret the same scientific question in conflicting ways.
Use FAOSTAT and crop-query incidents as diagnostic evidence of general architectural weaknesses. Do not implement isolated fixes for individual terms such as “wheat production” or “wheat yield” unless the change is part of a justified reusable architectural solution.
---
# 6. ENGINEERING INCREMENTS — AUTOMATIC CONTINUATION
Work through the following increments in order.
For each increment:
1. Verify prerequisites.
2. Inspect the actual implementation.
3. Define precise acceptance criteria.
4. Implement the smallest coherent change.
5. Add or update tests.
6. Execute authorized local verification.
7. Diagnose and fix regressions caused by the change.
8. Inspect the complete diff.
9. Update ADR-023 append-only.
10. Continue to the next increment if no approval gate blocks it.
Do not repeatedly stop after each increment to ask whether you should continue.
## R1 — Verify and stabilize the current registry baseline
Confirm that the shared catalog really governs the intended production paths.
Verify:
- The five existing source definitions.
- Selector and orchestrator resolution.
- Identity bridge behavior.
- Path-family mapping.
- Deprecated constant consistency.
- Deterministic source resolution.
- Duplicate and unknown source handling.
- Existing compatibility behavior.
Inspect the sequential failure-isolation change.
### Mandatory exception-boundary investigation
Determine whether the current implementation catches:
- Expected external-source failures.
- Unexpected application defects.
- Assertions from test-framework callbacks.
- Exceptions from `Http::fake` or similar test doubles.
- Programming errors that should not be converted into ordinary source failures.
Define a safe exception taxonomy.
Preserve source-failure isolation without suppressing test assertions or concealing internal programming defects.
Compare sequential and concurrent behavior.
Investigate the reported difference in the FAOSTAT test failure message and the discrepancy between 23 and 25 `ProjectionQueryConstraintsTest` cases.
Do not fix unrelated legacy failures unless they block a necessary regression check. Record their baseline status accurately.
**Acceptance:** The existing registry behavior is understood, the exception policy is explicit, and any necessary R1 corrections are implemented and verified.
## R2 — Capability Lifecycle and Production Enforcement
This is the next major implementation priority.
Investigate the existing capability v2 model, persistence, producers, consumers, and selector integration.
Implement a complete lifecycle:
1. Declaration.
2. Evidence collection.
3. Verification.
4. Persistence or authoritative registration.
5. Freshness and expiry.
6. Production consumption.
7. Enforcement.
8. Auditing and failure reporting.
Distinguish:
- Verified supported.
- Verified unsupported.
- Not verified.
- Not tested.
- Temporarily unavailable.
- Access denied.
- Authentication required.
- Licence or usage status unresolved.
Unknown must never mean supported.
A source must not be selected for an operation whose required capability has not been sufficiently verified.
Different operations may require different evidence: search, pagination, filtering, full-text retrieval, bulk access, or other capabilities.
Implement deterministic rules for:
- Required capabilities.
- Missing capability evidence.
- Stale evidence.
- Temporary unavailability.
- Access denial.
- Safe degradation.
- Observability and diagnostic reasons.
Integrate enforcement into the real production selector, not merely a monitoring service.
Add tests for both allowed and denied selections, including sources whose capabilities are unknown or stale.
Do not activate additional sources as part of capability implementation.
**Acceptance:** Production source selection actually uses verified capability information, and unverified capabilities cannot silently authorize unsupported operations.
## R3 — Versioned Adapter Contract v2
The existing `scientific_source_adapter.v1` declaration is not a complete contract.
Design and implement a compatible v2 contract with:
- Adapter identity and version.
- Registry resolution.
- Structured scientific-query input.
- Required capabilities.
- Timeouts and cancellation.
- Rate limits and concurrency.
- Pagination and result limits.
- Typed error categories.
- Retryability and retry policy.
- Partial-result behavior.
- Normalized results.
- Stable source identifiers.
- Provenance and licensing.
- Compatibility and migration rules.
- Observability without secret leakage.
Use typed outcomes where appropriate so expected source failures can be represented without swallowing unrelated application or test failures.
Define how v1 adapters coexist with v2 during migration.
Do not force every adapter to migrate in one unreviewable change.
Add conformance tests and migrate the five current adapters incrementally.
**Acceptance:** The versioned contract is used by production adapters and orchestrator code, and migration behavior is tested.
## R4 — Provenance, Licensing, and Error Integrity
Implement a consistent provenance contract from retrieval through presentation.
Preserve, where available and applicable:
- Canonical source identity.
- Original source identifier.
- Original URL.
- Retrieval timestamp.
- Publication timestamp.
- Licence and usage terms.
- Access method.
- Adapter and source-family provenance.
- Normalization transformations.
- Link to the original record.
Never invent source identifiers, DOI values, URLs, licences, or permissions.
Unknown licensing is not permission to redistribute.
Distinguish permission for retrieval, metadata storage, full-text storage, excerpt display, redistribution, and citation linking.
Do not change original-file preservation behavior.
Ensure that source errors, partial results, and provenance are not lost when results pass through normalization, ranking, validation, synthesis, and presentation.
Add tests for missing metadata, unknown licensing, source failure, and provenance propagation.
**Acceptance:** Provenance and licensing status survive the production path and are represented honestly at the user-facing boundary.
## R5 — Reusable Source-Family Adapters
Implement reusable adapters based on verified protocol requirements, beginning with source families that are supported by available evidence and required by the 109-source inventory.
Candidates include:
- REST APIs.
- OAI-PMH.
- RSS/Atom.
- Bulk/download datasets.
- Static or periodically refreshed datasets.
Do not assume every family is required or implement one merely because it appears in this list. Establish the actual need from the membership register and access evidence.
Use a shared outer contract with family-specific behavior where protocols differ.
Avoid source-specific branches in shared orchestration and scientific evaluation.
Add family-level contract tests and representative fixtures.
Do not access live external sources without explicit network authorization.
**Acceptance:** A new source using an existing family can be integrated without rewriting shared scientific-search logic.
## R6 — Onboarding Contract and 109-Source Reconciliation
The 109 approved membership entries remain authoritative.
Do not bind an `adr_id` merely because an entry exists.
Design an onboarding contract that records:
- Membership ID.
- Canonical identity evidence.
- Operational source key.
- Source family.
- Access method.
- Authentication requirements.
- Licence and usage status.
- Declared capabilities.
- Verification evidence.
- Adapter identity and version.
- Contract-test status.
- Runtime-verification status.
- Activation eligibility and blockers.
Handle ambiguous identities and cross-group pairs without inventing identities or silently merging entries.
Preserve the five existing live sources and their current behavior during the migration.
Reconcile all 109 entries against the actual implementation and available evidence.
Use truthful lifecycle states such as:
- Candidate.
- Identity verified.
- Access documented.
- Access verified.
- Adapter designed.
- Adapter implemented.
- Contract tests passed.
- Runtime verified.
- Blocked.
- Not tested.
Do not mark a source operational merely because its class or name exists.
Do not perform live verification or activation without explicit authorization.
**Acceptance:** Every membership entry has a truthful status and a defined path to onboarding; no unverified source is silently activated.
## R7 — Shared Scientific Logic and Isolation
Audit shared responsibilities across:
- Query interpretation.
- Source selection.
- Ranking and relevance.
- Evidence validation.
- Conflict handling.
- Answer synthesis.
- Provenance and citation construction.
- Home/Crop isolation.
- Results List and Viewer navigation.
Consolidate duplicated logic only where the authoritative owner and compatibility requirements are clear.
Preserve existing approved confidence, evidence-sufficiency, result identity, and Viewer contracts.
Add regression tests proving that:
- Home and Crop episodes remain isolated.
- Shared scientific logic does not leak crop-specific constraints into Home.
- Source failures do not erase successful results from other sources.
- Query meaning remains stable.
- Invalid results remain excluded.
- Confidence and evidence rules remain enforced.
Do not change feedback behavior without an explicit architectural decision.
**Acceptance:** Shared logic has clear ownership and existing user-facing contracts remain intact.
## R8 — Source Onboarding Automation and Final Integration
After the preceding contracts are implemented and verified, make source onboarding repeatable.
Provide a process that can:
1. Validate an onboarding manifest.
2. Verify the membership reference.
3. Check identity and access metadata.
4. Check capability evidence.
5. Resolve the adapter family.
6. Run adapter-conformance tests.
7. Validate provenance and licensing requirements.
8. Produce an auditable eligibility result.
9. Prevent activation if required evidence is missing.
Do not automate live access or production activation without the necessary authorization.
Prefer configuration-driven integration and reusable adapters over per-source conditionals.
**Acceptance:** The next source can be prepared and tested through a repeatable workflow without creating another competing registry or modifying unrelated scientific logic.
---
# 7. LEGACY 62-ENTRY REGISTER — PRESERVE AND REASSESS SAFELY
The owner has identified the old 62-entry register as obsolete.
The latest report says the deletion manifest is empty and the register has been retained.
Do not delete anything simply because it belongs to the old register.
Continue investigating whether any of its files or operational mechanisms are demonstrably unused.
Build and maintain a dependency matrix that records:
- Exact path.
- Relationship to the old register.
- Runtime and build references.
- Test and tooling dependencies.
- Architectural references.
- Historical value.
- Confidence.
- Proposed action.
A file is a deletion candidate only when it is proven to be exclusively obsolete and has no active or historical value that needs preservation.
Absence of a direct import is not sufficient proof.
Before any deletion, present the exact file-by-file manifest and obtain explicit owner approval.
Until then:
- Keep the files.
- Do not use broad wildcard deletion.
- Do not delete directories recursively.
- Do not alter the 109-entry membership register.
- Preserve ADR-023 and historical source evidence.
- Continue unrelated safe engineering work.
If no file meets the deletion criteria, leave the manifest empty and explain why.
---
# 8. TESTING AND FAILURE MANAGEMENT
Build tests around architectural contracts rather than only individual source names.
Required coverage includes:
- Registry resolution.
- Capability verification and enforcement.
- Stale and unknown capabilities.
- Adapter contract versions.
- Typed source failures.
- Retryability.
- Timeouts and limits.
- Pagination.
- Partial results.
- Provenance and licensing.
- Stable source identity.
- Query and CSQ preservation.
- Source-family behavior.
- Home/Crop isolation.
- Results List and Viewer compatibility.
- Prevention of unverified activation.
For each increment:
1. Identify the relevant baseline tests.
2. Run only authorized tests supported by the current environment.
3. Record the exact command and result.
4. Compare the result with the baseline.
5. Fix regressions introduced by your changes.
6. Rerun the relevant tests.
7. Record remaining pre-existing failures separately.
Do not claim a test passed if it did not execute.
Do not hide test-framework exceptions inside source-failure outcomes.
Do not investigate unrelated pre-existing failures unless they block a necessary acceptance criterion.
If execution is blocked, document the exact reason and continue work that does not depend on the blocked test.
---
# 9. STRICT SECURITY AND CHANGE-CONTROL BOUNDARIES
The following operations are prohibited without separate explicit authorization:
- Git commits.
- Git pushes or pulls.
- Branch switching, rebasing, or history rewriting.
- Reset, clean, stash, destructive restore, or force operations.
- File deletion before approval of the exact manifest.
- Broad recursive or wildcard deletion.
- Database modification or migration.
- Docker execution.
- Dependency installation.
- Network access and live external-source probes.
- Deployment or production activation.
- Unrelated file modifications.
Do not stage files.
Do not discard user changes to make the working tree clean.
Do not overwrite historical reports, evidence, prompts, or architectural decisions.
## Required safety protocol
Before each modification:
1. Inspect the exact target.
2. Check the working-tree state and ownership.
3. Identify dependencies.
4. State the technical reason.
5. Define a recovery strategy.
6. Make the smallest coherent change.
7. Inspect the diff immediately.
8. Execute authorized relevant checks.
9. Verify that unrelated
