<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16806). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 12:19 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16807, 16827; it is archived once. -->

---
# WSA-Enterprise — Autonomous Scientific Source Integration Continuation
## GBIF + Organic Eprints + BHL | Production LocalIndex Readiness | Evidence-Gated Activation
**Repository:** `G:\WSA-Enterprise`  
**Organization/Repository:** `wsa-platform/WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Current Membership Register:** `110.v1`  
**Latest reported baseline:** 2026-10-17 continuation report  
**Execution mode:** Autonomous engineering within the authorization boundaries below  
**Mandatory stopping point:** Final comprehensive Arabic pre-commit report; no commit, push, or deployment.
---
# 1. ROLE AND OBJECTIVE
Act as the senior software engineer and scientific-source integration architect for WSA-Enterprise.
Continue the existing scientific-source expansion mission by combining the previously authorized three-source integration work with the latest continuation report. Inspect the actual repository state before making changes, reuse valid completed work, identify the precise remaining blockers, implement the necessary authorized changes, execute permitted tests, and document verifiable results.
The three source seats in scope are:
1. **G5-04 — GBIF:** preserve and verify the existing ACTIVE integration.
2. **G1-02 — Organic Eprints:** complete the real ingestion-to-production-LocalIndex-to-application-search path.
3. **G6-20 — Biodiversity Heritage Library (BHL):** complete secure API authentication, live API validation, integration, and evidence-based readiness evaluation.
This is a continuation task, not permission to redesign the entire scientific-search architecture or start unrelated source integrations.
Do not ask for routine approval for ordinary engineering decisions that fall within this scope. Investigate and resolve such issues independently. Stop only when a genuine owner-only decision, unavailable credential, environmental limitation, or architectural authority blocks safe progress.
**Do not claim that a task succeeded unless the repository state, command output, test result, or live evidence actually supports that claim.**
# 2. MANDATORY PRE-FLIGHT — PRESERVE THE CURRENT WORK
Before editing anything:
1. Confirm the actual repository path, current branch, actual HEAD, and current working-tree state.
2. Do not assume the reported HEAD is still current. Report any difference without attempting to reset, switch branches, or overwrite work.
3. Check for an active Cursor task or another process editing the same files. If another task is still modifying the repository, do not overlap it; wait until its execution and report are complete.
4. Read the latest continuation report and its machine-readable evidence:
   - `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-AUTONOMOUS-CONTINUATION-*.{json,ar.md}`
   - `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-AUTONOMOUS-EXECUTION-MATRIX.v1.json`
   - `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-RESEARCH-EVIDENCE-G1-02-G6-20.v1.json`
   - `docs/architecture/ADR-023-scientific-source-expansion.md`
   - The actual current membership-register file, including `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json`, if still the authoritative file.
5. Verify actual filenames before relying on wildcard patterns or the reported paths.
6. Inspect the current implementations, tests, fixtures, catalog, adapters, orchestration, normalization, provenance, index lifecycle, and capability/onboarding contracts relevant to the three seats.
7. Reuse existing work. Do not recreate existing classes, duplicate adapters, or replace working architecture merely to make the implementation appear complete.
8. Identify existing uncommitted changes and preserve them. Do not revert, overwrite, clean, stash, reset, or discard unrelated changes.
The report states that the latest run completed 138 tests and 6,459 assertions. Treat this as a reported baseline, not as proof of the current working tree or of tests you have not rerun.
# 3. NON-NEGOTIABLE ARCHITECTURAL RULES
Follow the approved architecture, ADR-023, current membership register, and applicable owner decisions.
## 3.1 Membership and activation
- `110.v1` remains the current membership baseline.
- Do not replace it with `109.v1` or any historical register.
- Do not remove, silently rename, merge, or fabricate source seats.
- The reported current ACTIVE source is `G5-04 / GBIF`.
- Preserve that status unless concrete evidence demonstrates that the current implementation violates an existing mandatory rule. If such a violation is discovered, document it and follow the existing authorized remediation process; do not casually disable the source.
- Do not activate any additional source simply because code exists, a request returns HTTP 200, a fixture passes, or a catalog entry can be constructed.
- Activation requires evidence for the source's identity, access, licensing, capabilities, integration, provenance, and applicable onboarding/IC/CapVer requirements.
## 3.2 Scientific-query integrity
Preserve CSQ as the authoritative scientific question. Continue using the existing query-building and orchestration architecture, including the applicable components:
- `ScientificSearchQueryBuilder`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Source-specific adapters and profiles
- `ScientificResultNormalizer`
- Existing provenance and evidence contracts
Do not replace CSQ with keyword-only searches, hard-coded example queries, source-specific query fabrication, or unrelated fallback results.
Source-specific query translation is permitted only through the existing approved architecture, with the original question's scientific intent preserved.
## 3.3 Evidence and capability authority
- `Capability Store` dependency injection or successful reading is not proof that a source capability is verified.
- Do not write `VERIFIED` or equivalent claims from an unauthorized boundary.
- Do not bypass CapVer.
- Preserve fail-closed behavior for missing, stale, historical, mismatched-subject, or otherwise invalid capability records.
- Do not introduce unauthorized `persistCurrent` or `supersedeCurrent` behavior.
- Do not mark IC complete without the actual required owner decision and evidence.
- Do not change a source's readiness state merely to make the matrix appear complete.
## 3.4 Execution restrictions
Strictly prohibited:
- Git commit
- Git push
- Deployment or production release
- Docker commands or Docker-based testing
- `git reset`, `git clean`, forced branch switching, destructive cleanup, or discarding existing work
- Unrequested database resets, migrations against production, destructive index replacement, or data deletion
- Adding unrelated source integrations or starting another phase
- Editing unrelated architecture or application modules
- Logging or documenting API credentials
Do not stop merely because committing is prohibited. Continue authorized implementation and testing, then stop at the pre-commit review.
# 4. G1-02 — ORGANIC EPRINTS
## 4.1 Reported baseline
The previous report states:
- OAI-PMH endpoint: `https://orgprints.org/cgi/oai2`
- `Identify` previously returned HTTP 200.
- `ListRecords` using `metadataPrefix=oai_dc&from=2024-01-01` returned records and a `resumptionToken`.
- A future-date query returned HTTP 200 with OAI-PMH error `noRecordsMatch`.
- Existing components include `OaiPmhSourceProfile`, `OaiPmhHarvester`, `CurrentMembershipNearReadySeatCatalog`, and `OrganicEprintsOaiLocalIndexPathTest`.
- XML fixtures exist for the `ListRecords` pages and the no-records response.
- Tests demonstrated a path from a seeded test index through the application orchestrator.
- The production catalog reportedly does not register `organic_eprints`, and the production LocalIndex is not published.
- Therefore, the seat remains NOT_READY.
Verify these claims against the current implementation. Do not assume the prior implementation is complete or defective without examining the code and tests.
## 4.2 Required engineering work
Complete the existing integration without creating a competing architecture.
### A. OAI-PMH correctness
Verify and, where needed, implement:
- `Identify` and `ListRecords` handling.
- XML parsing using an appropriate XML parser; do not parse XML using fragile regular expressions.
- Correct processing of `resumptionToken`, including the protocol's required request semantics.
- Safe handling of empty responses, malformed XML, OAI-PMH errors, timeouts, and transient failures.
- Correct state transitions, including the existing PAUSED/COMPLETED lifecycle where applicable.
- Stable, deterministic source record identities.
- Idempotent ingestion so repeated harvests do not create duplicate records.
- Safe resumption after interrupted harvests.
- Pagination termination and loop protection.
- Appropriate bounded execution and retry behavior.
Do not assume every response contains a token, and do not assume a successful HTTP response means a successful OAI-PMH operation.
### B. Metadata, provenance, and licensing
Preserve the original source identity and metadata provenance.
The report identifies the Organic Eprints policy at:
`https://www.orgprints.org/about_policy.html`
It reports that metadata reuse requires linking to the corresponding abstract page and that public re-hosting of documents is prohibited.
Verify the current official policy and implement accordingly:
- Preserve the original record identifier and source URL.
- Generate correct abstract links for the actual record identifiers.
- Preserve source attribution and available metadata.
- Keep metadata reuse separate from permission to redistribute full-text documents.
- Do not download, copy, republish, or publicly expose full-text documents merely because they are accessible.
- Do not silently alter or lose the original scientific metadata or provenance during normalization.
Record the actual policy evidence and any unresolved licensing uncertainty.
### C. Production LocalIndex and application search
This is the main blocker. Do not stop at fixture-based tests.
Trace the actual path through:
`OAI-PMH → Harvest → Normalization → LocalIndex ingestion → Index publication → Production catalog → Application orchestrator → Search results`
Determine the exact point where the current production path breaks.
Investigate whether the cause is:
- Missing ingestion execution
- Missing or incomplete production index storage
- An unpublished index
- Missing catalog registration
- A mismatch between the source key and catalog identifier
- An orchestrator routing problem
- A normalization or result-eligibility problem
- A separate approved publication step
Implement the smallest correct fix within the existing architecture.
If the application supports a safe, local, non-destructive index build or publication workflow, execute it and verify the result. Do not deploy or modify production infrastructure. Do not delete an existing index to make a test pass.
Run a bounded real harvest using the official endpoint if the environment and authorization permit it. Do not claim a live harvest if only fixtures were used.
After ingestion and publication, verify:
1. The index contains real records from Organic Eprints.
2. The expected source is present in the actual catalog.
3. The application can execute a CSQ-derived search through the normal orchestrator.
4. At least one returned result is demonstrably from Organic Eprints.
5. The result preserves source identity, original record URL, provenance, and relevant licensing/attribution metadata.
6. Repeating the harvest does not create duplicates.
7. Failure and resume behavior are covered by suitable tests.
Do not expose full-text documents in the search results unless their redistribution is explicitly authorized.
If the production LocalIndex cannot be built or published safely in the available environment, leave the source NOT_READY and document the exact blocker and required next action.
# 5. G6-20 — BIODIVERSITY HERITAGE LIBRARY (BHL)
## 5.1 Reported baseline
The previous report states:
- BHL uses API v3.
- The official API documentation is `https://www.biodiversitylibrary.org/docs/api3.html`.
- An API key is required.
- The seat is currently NOT_READY.
- A dummy or invalid key does not establish readiness.
Inspect the existing BHL profile, adapter, catalog, onboarding evidence, tests, and documentation before implementing or changing anything.
## 5.2 Credential security — mandatory
The BHL API key is a secret. It has previously been exposed in the conversation and should be rotated or replaced through the official provider process where possible.
Never repeat, print, reconstruct, hard-code, or embed the previously shared key in the prompt, source code, tests, fixtures, documentation, reports, URLs displayed in output, or logs.
If the key is not already available through an authorized local secret mechanism, do not pretend to have it. In that case:
1. Prepare the exact local configuration requirement, using the established project convention.
2. Ask the owner to enter the current valid key directly into the local secret configuration or secret manager.
3. Do not ask the owner to paste the key into Cursor chat or another report.
4. Continue all work that does not require the key.
Before writing any secret to a local `.env` file:
- Verify the file exists or determine the correct project-local configuration path.
- Verify that the target secret file is ignored by Git and is not tracked.
- Preserve all existing environment values.
- Do not overwrite the entire file.
- If the file is tracked or its status cannot be established, do not write the secret there; use an approved untracked secret mechanism.
- Never place the key in `.env.example`, a committed configuration file, a test fixture, a command-line argument, or a report.
Send the credential only to the official BHL API endpoint over HTTPS. Redact the key from all output and logs.
## 5.3 API and integration implementation
Using the official documentation, inspect and implement only what the existing architecture requires.
Evaluate appropriate API v3 operations, including where relevant:
- `PublicationSearch`
- `PublicationSearchAdvanced`
- `GetTitleMetadata`
- `GetPageMetadata`
Verify actual operation parameters against the official documentation before coding.
The implementation must:
- Follow the existing source adapter and catalog contracts.
- Use safe credential configuration.
- Validate HTTP status and the API response's own `Status` and `ErrorMessage` fields where applicable.
- Distinguish successful API transport from a successful BHL operation.
- Handle authentication failures, malformed responses, timeouts, rate limits, and pagination safely.
- Preserve the source's identifiers and original URLs.
- Preserve provenance and available licensing/attribution information.
- Keep publication/title metadata distinct from individual items, pages, and OCR text.
- Avoid inventing full-text availability or rights from a bibliographic record.
- Avoid logging credential-bearing request URLs or secret values.
Do not add a new HTTP dependency if the current Laravel/PHP stack can meet the requirements with the established project approach. Introduce a dependency only if there is a concrete, documented technical necessity.
## 5.4 Live validation
Once a valid key is securely available, execute bounded live requests to establish:
1. Authentication succeeds.
2. A real publication search returns a valid response.
3. A detail request retrieves metadata for an actual returned identifier.
4. Response fields and error handling behave as expected.
5. Provenance and original identifiers survive normalization and orchestration.
6. The application search path returns a real BHL result if the existing architecture requires it for readiness.
Use a small number of bounded requests. Do not launch an unbounded crawl or bulk harvest.
A successful authenticated API request does not by itself authorize activation. Complete the remaining source identity, licensing, capability, onboarding, IC, and CapVer requirements.
If no valid key is available, continue implementation and safe tests, mark live authentication as BLOCKED, and leave BHL NOT_READY.
# 6. G5-04 — GBIF: PRESERVE AND VERIFY
GBIF is the only source reported ACTIVE. Do not rebuild it unnecessarily.
Inspect the current adapter, catalog registration, normalization, provenance, and the existing `GbifOrchestratorEndToEndTest`.
The reported live probe was:
`species/search?q=Triticum&limit=1` → HTTP 200
The reported application test exercises the production catalog, source registration, orchestrator, results, and provenance through the existing adapter.
Verify the implementation and run the relevant permitted tests. If safe and available, perform a small live request to the official GBIF API:
`https://api.gbif.org/v1/`
Validate the actual response structure and expected result, not just HTTP 200.
Check that:
- The source remains registered under the correct current membership identity.
- Search results retain GBIF record identity and provenance.
- HTTP errors and malformed responses are isolated appropriately.
- A source failure does not corrupt unrelated results.
- Existing behavior remains compatible with the scientific query and evidence contracts.
- Licensing and attribution metadata are preserved where applicable.
Do not add new GBIF functionality or dependencies unless an actual, documented defect requires it.
Do not claim that every GBIF capability has been verified merely because the existing test passes.
# 7. SHARED ARCHITECTURE AND INTEGRATION REVIEW
For all three source seats, inspect the current source registry/catalog, capability contracts, onboarding logic, adapter contracts, result normalization, provenance, and application orchestrator.
Verify that the three implementations coexist without bypassing established boundaries.
Specifically:
1. Confirm the current membership register remains `110.v1`.
2. Confirm source identifiers and membership mappings are stable.
3. Confirm no source is activated through a test-only registration or a seeded production-looking record.
4. Confirm LocalIndex data from one source cannot be misattributed to another source.
5. Confirm errors from one source do not invalidate successful results from unrelated sources.
6. Confirm CSQ identity and scientific intent are preserved.
7. Confirm no source-specific fallback manufactures results unrelated to the actual source response.
8. Confirm source URLs and provenance survive normalization and orchestration.
9. Confirm missing, stale, historical, or subject-mismatched capability records fail closed.
10. Confirm no unauthorized write to Capability Store or bypass of CapVer was introduced.
11. Confirm IC status is not fabricated or silently treated as complete.
12. Confirm any required owner-only IC decision is explicitly recorded as BLOCKED rather than guessed.
Do not create a second registry, an alternative activation pathway, or duplicate readiness semantics.
# 8. OFFICIAL WEB RESEARCH
Use official source documentation and current repository evidence where needed.
Preferred official sources:
- GBIF API: `https://api.gbif.org/v1/` and official GBIF API documentation.
- Organic Eprints policy: `https://www.orgprints.org/about_policy.html`
- Organic Eprints OAI-PMH: `https://orgprints.org/cgi/oai2`
- BHL API v3 documentation: `https://www.biodiversitylibrary.org/docs/api3.html`
- BHL official API-key request process, as documented by BHL.
Verify actual endpoint behavior and policy details. Do not rely on search snippets when official documentation or direct, safe requests can establish the answer.
Distinguish in the report:
- Officially documented facts
- Live observations
- Fixture-based test results
- Implementation inferences
- Unverified assumptions
- Owner-only decisions
Do not present an inference as official provider policy.
# 9. TESTING REQUIREMENTS
Run the smallest useful focused tests first, followed by the relevant regression set.
Inspect existing test scripts and conventions before choosing commands. Use the existing local PHP environment when appropriate:
`G:\tools\php-8.4.26-nts\php.exe`
Do not assume the executable or dependencies are still available; verify the actual environment first.
Required test coverage should include, where applicable:
### Organic Eprints
- OAI-PMH XML parsing
- Pagination and `resumptionToken`
- `noRecordsMatch`
- Invalid XML and protocol errors
- Idempotent ingestion
- Resume and failure states
- LocalIndex lifecycle and publication
- Actual application catalog/orchestrator integration
- Provenance, source URLs, and licensing metadata
### BHL
- Missing credentials
- Invalid credentials
- Successful response parsing with safe test fixtures
- API-level error despite HTTP success
- Malformed JSON and HTTP failure
- Pagination and bounded retries
- Provenance and identity preservation
- Credential redaction
### GBIF
- Existing end-to-end orchestration test
- Response normalization and provenance
- HTTP failures and isolation of source errors
### Shared boundaries
- Membership-register integrity
- Catalog identity and coexistence
- Capability Store fail-closed behavior
- No unauthorized persistence or superseding
- No activation without the required evidence
Use mocked or fixture-based tests for deterministic failure scenarios. Clearly label them as such. Run live tests only when safe, authorized, and bounded.
After each meaningful change, run the relevant focused tests. Then run the relevant regression tests and report actual outcomes.
Do not claim tests were executed if you only inspected them. Do not silently skip failed tests or replace failing tests with weaker assertions to obtain a pass.
# 10. READINESS MATRIX AND EVIDENCE ARTIFACTS
Update the existing mission artifacts in place where appropriate, following their current schema and naming conventions. Do not create redundant competing reports.
At minimum, document:
- Current membership version and source-seat identifiers.
- Previous and resulting readiness status for each of the three sources.
- Exact implementation changes.
- Exact commands executed and their real results.
- Test count and assertion count where available.
- Which results are fixture-based, mocked, live, or end-to-end.
- Live endpoint, operation, timestamp, and sanitized outcome where relevant.
- Index ingestion and publication evidence for Organic Eprints.
- BHL authentication status without exposing the credential.
- Provenance and licensing checks.
- Capability Store, IC, and CapVer status.
- Remaining blockers and the precise action required to resolve each one.
- Actual Git branch, HEAD, and working-tree state at the end.
Use explicit readiness states such as:
- `ACTIVE` — only when the existing authorized activation criteria are satisfied.
- `NOT_READY` — when required evidence or integration is missing.
- `BLOCKED` — when progress depends on a credential, owner decision, unavailable environment, or another external prerequisite.
- `UNVERIFIED` — when evidence is insufficient to establish the claim.
Use the project's existing state vocabulary where it differs. Do not invent new state semantics without architectural authorization.
Keep machine-readable JSON and Arabic Markdown consistent. Validate JSON syntax and cross-check counts, identifiers, and readiness values.
Do not claim all 110 seats have been audited. Report only the seats and capabilities actually inspected during this mission.
# 11. REQUIRED EXECUTION ORDER
Execute in this order unless the actual code establishes a concrete dependency requiring a documented adjustment:
1. Pre-flight inspection and worktree protection.
2. Read ADR-023, the current membership register, and the latest continuation evidence.
3. Inspect current Organic Eprints, BHL, and GBIF implementations before editing.
4. Identify and fix the Organic Eprints LocalIndex production-path blocker.
5. Run Organic Eprints ingestion/publication and actual application-search verification where safely possible.
6. Implement and test BHL integration; perform live authenticated checks only when a valid credential is safely available.
7. Verify the existing GBIF integration and regression behavior without unnecessary rewrites.
8. Validate shared registry, provenance, Capability Store, IC, and CapVer boundaries.
9. Run focused tests, followed by the relevant regression tests.
10. Update the existing evidence matrix and issue one comprehensive Arabic final report.
If a prerequisite blocks a step, continue with independent work that does not violate the dependency. Record the blocked item and its exact reason. Do not fabricate success to complete the sequence.
# 12. FINAL REPORT — ARABIC ONLY
At the end, provide one comprehensive Arabic engineering report containing:
1. Executive summary.
2. Actual repository path, branch, HEAD, and working-tree status.
3. Files changed and the purpose of each change.
4. Organic Eprints implementation, real ingestion/index-publication status, and actual application-search evidence.
5. BHL implementation, credential status, sanitized live API evidence, and remaining blockers.
6. GBIF verification and regression results.
7. Capability Store, IC, and CapVer boundary review.
8. Exact test commands, outcomes, counts, assertions, and failures.
9. Updated readiness matrix for the three seats.
10. Remaining blockers, classified as engineering work, external prerequisite, or owner-only decision.
11. Artifact paths updated or created.
12. Explicit confirmation of whether any commit, push, or deployment occurred.
Clearly separate:
- **Implemented**
- **Tested**
- **Verified live**
- **Blocked**
- **Not verified**
Do not report a source as ACTIVE unless all required activation evidence and authorization are actually satisfied.
# 13. MANDATORY STOPPING POINT
Stop after implementation, permitted testing, evidence updates, and the comprehensive Arabic pre-commit report.
**Do not create a commit. Do not push. Do not deploy. Do not run Docker. Do not reset, clean, stash, or discard existing work.**
Leave all changes in the working tree for owner review. Do not proceed to another phase or unrelated source seats.
The mission is complete for this execution only when the work performed and all remaining blockers have been reported honestly. This does not mean that every source must become ACTIVE; an evidence-based NOT_READY or BLOCKED result is correct when its prerequisites remain unmet.
