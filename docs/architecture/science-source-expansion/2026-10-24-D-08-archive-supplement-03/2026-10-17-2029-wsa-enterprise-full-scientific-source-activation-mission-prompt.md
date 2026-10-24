<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16641). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 8:29 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16642, 16648; it is archived once. -->

---
# WSA-Enterprise — FULL SCIENTIFIC SOURCE ACTIVATION MISSION
## 17 Sources | Engineering Design | Implementation | Integration | Testing | Automatic Activation
### Broad Engineering Authority — Security and Provenance Preserved
**Repository:** `wsa-platform/WSA-Enterprise`  
**Working directory:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`
**Latest reported status:** PHP 8.4.26 restored; Composer dependencies installed from the lockfile; focused PHPUnit tests passed; 67 Research tests passed with 5,894 assertions; offline retrieval evaluation completed for 18 provisional cases. These are reported results and must be verified where relevant before relying on them.
**Mission objective:** Implement, integrate, verify, and activate all 17 designated scientific sources wherever activation is legally and technically permissible. Do not stop at scaffolding, readiness matrices, or documentation.
---
## 1. YOUR ROLE AND DECISION AUTHORITY
Act as the senior software engineer, software architect, scientific-search integration engineer, test engineer, and technical auditor responsible for completing this mission.
You have broad authority to:
- Design the implementation architecture within the approved project architecture.
- Write and refactor production code.
- Implement source adapters, query projections, capability contracts, configuration, validation, normalization, provenance, error handling, and integration tests.
- Repair defects discovered during implementation.
- Restore and use the existing permitted PHP/PHPUnit test environment.
- Improve offline evaluation and regression coverage.
- Resolve engineering blockers independently.
- Verify official source documentation and existing licensing evidence.
- Update the source registry, readiness records, ADR-023, and mission audit artifacts.
- Activate sources automatically when the eligibility criteria in this prompt are satisfied.
- Make ordinary engineering decisions without asking the user to approve each one.
**Your responsibility is to deliver operational integrations, not merely to describe what remains to be done.**
Minimize interaction. Execute all independent work in one continuous mission and provide one comprehensive Arabic report at the end.
Do not stop because a task requires investigation, a new class, an adapter, a test, or a refactor. Investigate and implement the best justified solution.
If a true external authorization or legal restriction remains, continue all independent work and isolate only the affected operation.
## 2. EXACT SCOPE — ALL 17 SOURCES
Process every candidate below individually:
`G1-01, G1-13, G1-15, G1-17, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G5-04, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16`
The target is not merely 17 source records. The target is 17 correctly identified, integrated, tested, and activated sources, subject to the actual permission and capability gates.
For every source, establish:
1. Canonical identity and official provider.
2. Official endpoint and supported access method.
3. Verified license and applicable terms of use.
4. Actual supported scientific search operations.
5. Available filters, fields, pagination, and constraints.
6. Authentication and credential requirements.
7. Rate limits, timeout behavior, and service restrictions.
8. Required query projection and source-specific translation.
9. Normalization and provenance requirements.
10. Adapter implementation and error handling.
11. Unit and integration tests.
12. Activation eligibility and final operational status.
Do not treat a homepage, web interface, dataset download, RSS feed, metadata dump, OAI-PMH endpoint, or reachable HTTP URL as proof of a functional scientific-search API.
If a source does not support the required search operation, determine whether an officially supported alternative can provide that capability without violating its terms. Implement the best justified integration where feasible; otherwise document the exact technical limitation rather than falsely declaring the source active.
## 3. MANDATORY ARCHITECTURAL CONTRACTS
Inspect the current implementation and approved ADRs before changing code.
Preserve the established pipeline and responsibilities:
`User Question → CSQ → Source Identity → Capability Matching → Source Query Projection → Retrieval → Normalization → Identity and Provenance → Scientific Evidence`
Respect the approved responsibilities of:
- `ScientificSearchQueryBuilder`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- `ScientificSourceAdapterRegistry`
- `ScientificSourceSelector`
- `ScientificSourceRegistry`
- Capability contracts and capability decisions
- `ScientificResultNormalizer`
- Existing scientific evidence and answer-generation pipeline
CSQ remains the semantic authority. Source-specific projection may adapt a query to a provider's real capabilities but must not silently alter the scientific meaning.
Mandatory rules:
- Preserve canonical query identity and provenance.
- Preserve original source identity even when retrieving through an aggregator.
- Distinguish content availability from queryability.
- Distinguish source identity, technical capability, license, readiness, and activation.
- Do not silently drop species, cultivar, breed, strain, dose, treatment, geography, time, experimental conditions, or other identity-critical facets.
- Explicit unsupported constraints must remain explicit and must not be silently weakened.
- Do not introduce a parallel semantic authority or bypass existing architectural contracts to make an adapter appear functional.
- Do not modify unrelated application behavior without a justified need and regression coverage.
You may design and implement new internal abstractions where necessary, provided they satisfy the established contracts and are tested.
## 4. AUTOMATIC ACTIVATION AUTHORIZATION
The user explicitly authorizes automatic activation of the designated sources without separate per-source approval.
You may activate a source immediately when the following conditions are all satisfied:
- The intended source identity is sufficiently verified.
- Its intended access and project use are permitted by the applicable license and terms.
- Required credentials and access permissions are available through authorized configuration.
- Its capabilities are accurately declared and supported by evidence.
- Its adapter and source-specific query behavior work as intended.
- Required normalization and provenance behavior is implemented.
- Applicable tests pass.
- Rate limits, timeouts, retries, and failures are handled safely.
- It does not depend on an unresolved mandatory owner decision or other external approval.
- Activation follows the existing configuration and deployment model without silently changing production systems.
When these conditions pass, **do not ask the user for another approval for that source**. Update the appropriate source registry and activation configuration, execute the required verification, and record the evidence.
Do not mistake the user's authorization to activate eligible sources for a waiver of a provider's license, access restrictions, required credentials, or legal conditions.
### If a source fails an eligibility gate
Do not falsely activate it. Instead:
1. Identify the exact failed condition.
2. Determine whether the blocker is an engineering defect that you can resolve.
3. Fix in-scope engineering defects and rerun the relevant tests.
4. Search authoritative documentation for missing technical or licensing evidence.
5. Complete all safe, independent implementation work.
6. Keep only the genuinely blocked activation disabled.
7. State exactly what external decision, permission, or evidence remains necessary.
Do not stop the entire mission because one source is blocked. Continue through all 17 sources.
**Success target:** activate every eligible source and eliminate every resolvable engineering blocker. Do not claim all 17 are active unless the actual registry and runtime verification prove it.
## 5. IMPLEMENTATION REQUIREMENTS FOR EACH SOURCE
For each of the 17 candidates:
### A. Source adapter
Implement or complete the provider-specific adapter using the project's existing interface and conventions.
Include:
- Input validation.
- Query translation.
- Pagination where supported.
- Timeout and failure handling.
- Safe retry behavior where justified.
- Rate-limit compliance.
- Result parsing and normalization.
- Stable source identifiers.
- Provenance and canonical query linkage.
- Honest capability declarations.
- Useful diagnostics without exposing credentials.
Avoid unnecessary generic frameworks or duplicated adapter code. Reuse common infrastructure only where it preserves source-specific behavior.
### B. Query semantics
Map supported CSQ facets to the provider's actual query interface.
For unsupported facets:
- Preserve their status.
- Do not silently convert unsupported facets into accepted constraints.
- Do not report exact matching when the provider supports only approximate matching.
- Record any lossy projection according to existing architecture.
- Prevent false claims that the source supports capabilities it does not possess.
### C. Result integrity
Verify:
- Stable source identity.
- Original provider attribution.
- Canonical query association.
- Result identifiers and deduplication behavior.
- Publication or dataset metadata where available.
- Provenance and source URLs.
- No fabricated metadata.
- No silent identity substitution through aggregators.
- Safe handling of malformed and partial responses.
### D. Tests
Add or extend tests for:
- Correct query projection.
- Supported and unsupported facets.
- Empty results.
- Valid results.
- Malformed responses.
- HTTP errors and timeouts.
- Pagination.
- Rate limiting.
- Duplicate records.
- Missing or conflicting identifiers.
- Provenance preservation.
- License/capability/readiness/activation separation.
- Registry behavior.
- Failure isolation between sources.
- Activation eligibility.
Use deterministic fixtures for routine CI and offline testing. Do not make the core unit test suite depend on an unstable external service.
## 6. IDENTITY AND LICENSE BLOCKERS
The last report lists these 16 ambiguous identities:
`G1-04, G1-16, G1-20, G2-03, G2-20, G2-24, G2-25, G3-01, G3-13, G3-14, G4-03, G4-04, G6-05, G6-09, G6-21, G6-22`
The owner-decision package reportedly contains 37 open items.
These records may affect the broader source registry and source-expansion gates. Inspect the current artifacts and determine which blockers actually affect the 17 target candidates.
- Resolve identity questions when authoritative evidence supports the exact identity.
- Resolve technical issues through implementation when possible.
- Reuse already documented owner decisions where applicable.
- Do not merge sources by name similarity.
- Do not invent legal permission or treat an unresolved license as permission.
- Do not assume all 37 owner decisions are prerequisites for all 17 target sources.
- Do not leave a source blocked merely because an unrelated identity decision remains open.
- Keep genuinely dependent sources inactive and explain the dependency.
For each remaining owner decision, prepare a concise item containing the source ID, evidence, proposed use, issue, risk, and exact decision needed.
## 7. SOURCE-SPECIFIC ACTIVATION AND OPERATIONAL VERIFICATION
After implementation, verify activation using the actual application registry and the project's established runtime/configuration path.
For every activated source, demonstrate as far as the available environment permits:
1. The source is registered.
2. Its activation state is enabled.
3. Its declared capabilities are loaded.
4. Its adapter is selected by the intended orchestration path.
5. The query projection follows the canonical contract.
6. The adapter produces correctly normalized results from a deterministic fixture.
7. Provenance survives the complete path.
8. Failures are isolated and reported correctly.
9. Explicit unsupported constraints remain protected.
10. The source does not appear active only because of a hard-coded test fixture.
If live probes are permitted by the source's verified terms, available authorization, and rate limits, use the smallest reasonable request to verify the real endpoint. Do not perform broad crawling, full-text ingestion, bulk harvesting, or unrestricted requests.
If live verification is unavailable or not authorized, report that the adapter is implemented and fixture-tested but live operation remains unverified. Do not represent fixture success as proof of live connectivity.
Do not silently change production settings or deploy to production. Follow the repository's established activation model and make the exact target environment explicit.
## 8. PHP, COMPOSER, AND TEST ENVIRONMENT
The latest report says PHP 8.4.26 was restored, dependencies were installed from the lockfile, and 67 Research tests passed with 5,894 assertions. Verify this state before changing it.
Required actions:
1. Confirm the installed PHP runtime and extensions.
2. Confirm Composer and vendor dependencies.
3. Preserve `composer.lock`.
4. Do not run an unrestricted dependency update.
5. Execute focused tests for each changed adapter and contract.
6. Execute the relevant Research PHPUnit suite.
7. Run the offline scientific retrieval evaluation.
8. Run other permitted relevant tests if practical.
9. Investigate failures and fix in-scope defects.
10. Re-run tests affected by each fix.
11. Record exact commands, exit codes, test counts, and assertions.
Review the reported `CatalogContractTest` fix that disables `agricultural_intelligence.gbif.enabled` in the catalog-isolation test. Ensure the change is test-scoped and that GBIF's enabled behavior remains covered by appropriate tests.
Do not weaken assertions, silently skip failures, or claim tests passed when they were not executed.
## 9. EVALUATION AND ACCEPTANCE
Preserve the historical R9 baseline.
The reported 18-case provisional evaluation was:
- Precision: 0.588
- Recall: 0.944
- MRR: 0.685
- nDCG: 0.861
- Facet preservation: 0.926
These values are provisional until independently reproduced.
Improve and run the offline evaluation to test the integrated sources' contracts, query semantics, result normalization, source isolation, and provenance.
Keep provisional judgments separate from expert-approved judgments. Do not invent relevance labels.
Report fixture performance separately from live-network latency.
Do not claim that source activation improved scientific relevance merely because additional adapters were enabled. Evaluate the actual observable behavior and identify limitations.
## 10. GIT, WIP, AND SYSTEM SAFETY
Before changing anything, record the actual repository root, branch, HEAD, upstream, staged files, unstaged files, and untracked files.
The reported branch is:
`phase-18-m18-ai-marketing-communications`
The reported HEAD is:
`f2025315d8990f52f3c658c968a3ec8252ac18b0`
The reported `git status` output had 131 lines. Verify rather than assume these values remain current.
Mandatory protections:
- Preserve all existing WIP.
- Never run `git reset`, `git clean`, `git restore`, `git stash`, destructive rebase, or force-push.
- Do not switch branches for convenience.
- Do not delete or overwrite backups or historical source records.
- Do not stage the entire repository indiscriminately.
- Do not expose secrets or credentials.
- Do not modify production systems.
- Do not use Docker; it is not authorized.
- Do not perform unapproved migrations or destructive data operations.
- Do not modify dependency lockfiles without a documented and necessary reason.
- Do not commit, amend, push, or deploy during this mission.
- Inspect every diff and compare the initial and final Git state.
- Ensure that generated `vendor/` files remain uncommitted if they were previously untracked or intentionally excluded.
If a command might discard user work or affect unrelated data, do not run it. Find a safe alternative and document the limitation.
## 11. AUDIT TRAIL
Use the existing mission directory:
`docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/`
Update existing artifacts when appropriate instead of creating duplicate reports.
Update ADR-023 append-only, preserving historical decisions, formatting, and previous records. Add a new dated section only when supported by actual findings or implementation.
Maintain a source-by-source activation eligibility artifact and a comprehensive final report.
For every source, record:
- Identity evidence.
- License and terms status.
- Access method and official endpoint.
- Implemented adapter.
- Supported and unsupported capabilities.
- Test evidence.
- Readiness status.
- Activation state.
- Live verification status.
- Remaining blocker, if any.
Clearly distinguish:
`IMPLEMENTED` ≠ `TESTED` ≠ `VERIFIED` ≠ `LICENSE-CLEARED` ≠ `READY` ≠ `ACTIVE`.
## 12. FINAL ACCEPTANCE CRITERIA
The target outcome is:
- All 17 sources individually reconciled.
- Every resolvable engineering blocker fixed.
- Every source with verified identity, permitted use, working integration, truthful capability declarations, and passing required tests automatically activated without per-source user approval.
- All remaining sources have precise blocker records and complete independent safe implementation.
- All activated sources have runtime/configuration verification.
- Offline tests and the relevant PHPUnit suite pass, or actual failures are precisely documented.
- The historical R9 baseline remains preserved.
- Source provenance and scientific-query semantics remain intact.
- Existing WIP is preserved.
- No commit, push, or deployment occurs.
Report each source's final state honestly. The numerical goal of 17 does not justify false activation or fabricated evidence.
## 13. ONE FINAL ARABIC REPORT
At the end, provide a single comprehensive Arabic report including:
1. Executive summary and overall status.
2. Initial and final branch, HEAD, and Git state.
3. A table for all 17 sources with identity, license, implementation, tests, readiness, activation, and live-verification state.
4. Exact list of newly activated sources.
5. Exact list of sources that remain inactive and why.
6. Implemented files and their purpose.
7. Test commands, exit codes, test counts, and assertions.
8. Offline evaluation results and limitations.
9. Verification of registry and orchestration behavior.
10. Outstanding owner decisions.
11. Security and provenance review.
12. Confirmation that no commit, push, deployment, or prohibited destructive Git operation occurred.
13. A criterion-by-criterion acceptance matrix.
Do not report `PASS` if any mandatory acceptance criterion remains unmet.
# BEGIN EXECUTION
Verify the real repository state and inspect the current activation eligibility artifact, source registry, adapters, tests, ADR-023, and latest engineering report.
Then implement, integrate, test, repair, and automatically activate all sources that satisfy the explicit criteria. Do not stop at documentation or scaffolding. Continue through all 17 sources and finish with one evidence-based Arabic report.
Use broad engineering judgment. Do not ask for routine per-source approval. Stop only for genuinely indispensable external permission or safety gates, and continue all independent safe work.
**Final boundary: no commit, no push, no production deployment.**
