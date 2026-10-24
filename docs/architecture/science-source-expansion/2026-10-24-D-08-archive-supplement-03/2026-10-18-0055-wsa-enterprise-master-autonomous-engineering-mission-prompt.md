<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16830). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 12:55 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16831; it is archived once. -->

---
# WSA-Enterprise — MASTER AUTONOMOUS ENGINEERING MISSION
## Five Scientific Sources — Architecture, Integration, Implementation, Production Search, Evidence-Gated Readiness
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local project path:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Scientific source membership authority:** CURRENT `110.v1`  
**Architecture authority:** Approved ADRs, especially `ADR-023-scientific-source-expansion.md`, its latest approved sections, and the existing source-expansion evidence archive.
**Mission type:** Evidence-driven engineering investigation, source-specific integration, implementation, testing, and readiness assessment.
**Required output:** One comprehensive Arabic engineering report based on actual repository inspection and executed evidence.
---
# 0. EXECUTION PRECONDITION — DO NOT OVERLAP ACTIVE WORK
Before modifying anything, determine whether another Cursor task or agent is currently editing the same repository or any of the files this mission would touch.
- If a previous task is still actively executing or modifying the repository, do not start a competing implementation.
- Wait for the previous task to finish and produce its final report before proceeding.
- Do not overwrite, discard, revert, or silently absorb another agent's in-progress changes.
- If the previous task has finished, inspect its final report and current repository state before continuing.
- Do not repeat completed forensic audits unless new evidence or a specific unresolved issue requires them.
- Do not assume that a previously reported implementation, test, endpoint, or capability remains valid without checking the relevant current state.
This mission must preserve all existing work and establish a reliable starting state before any change.
# 1. MISSION OBJECTIVE
Engineer and assess the five scientific sources listed below through the existing WSA-Enterprise scientific research architecture.
The objective is not merely to add URLs, adapters, source names, or HTTP requests.
The objective is to establish, for each source, an evidence-backed path from the authoritative source and its actual access mechanism to the application's scientific search results, while preserving:
1. Scientific question identity and the original scientific intent.
2. The existing Canonical Scientific Question (CSQ) contract.
3. The established source membership and registry authority.
4. Source-specific capabilities and capability resolution.
5. Original source identifiers, provenance, licensing, and metadata.
6. Existing evidence evaluation, claim relationships, and final-answer gates.
7. The distinction between code existence, technical reachability, usable ingestion, searchable records, and production readiness.
8. All previously approved architectural decisions and protections.
The five sources are:
| Source ID | Source | Required outcome |
|---|---|---|
| `G5-04` | GBIF | Validate and preserve the existing working integration; correct only evidenced defects. |
| `G1-02` | Organic Eprints | Resolve the remaining integration and LocalIndex readiness blockers. |
| `G6-20` | Biodiversity Heritage Library (BHL) | Verify the API key configuration, validate API v3 access, and establish a genuine search path. |
| `G1-01` | AGRIS | Verify the official distribution/access mechanisms and establish an evidence-backed integration strategy. |
| `G1-17` | Dr. Duke's Phytochemical and Ethnobotanical Databases | Verify the official archived data, schemas, and license before implementing a local-data integration. |
These IDs are identifiers to verify against the current canonical registry. Do not invent, renumber, or silently replace membership entries.
# 2. NON-NEGOTIABLE EXECUTION AND SAFETY RULES
## 2.1 No unauthorized Git or deployment operations
During this mission:
- Do not create a commit.
- Do not push or pull.
- Do not deploy.
- Do not run Docker.
- Do not reset, clean, rebase, or otherwise rewrite Git history.
- Do not discard, revert, overwrite, or automatically format unrelated working-tree changes.
- Do not execute destructive database operations.
- Do not run migrations or ingestion operations that could damage existing data without explicit authorization and an established safe procedure.
- Do not alter environment configuration beyond the minimum necessary, and do not change the BHL secret value.
- Do not enable a source in production merely because an API responds successfully.
Stop before the commit stage. Deliver a complete pre-commit report and leave all changes uncommitted.
## 2.2 Preserve the working tree
Before implementation:
1. Inspect the current branch and HEAD.
2. Inspect Git status, including untracked files.
3. Identify existing changes and protect them.
4. Record the actual starting state in the mission report.
5. Review the latest source-expansion report and evidence archive.
6. Inspect the relevant approved ADR decisions and existing implementation before changing code.
Do not assume the reported HEAD is still current. Report the actual value.
Never use a destructive command to make the repository appear clean.
## 2.3 Existing architecture has priority
Use the existing project abstractions and conventions wherever appropriate, including the actual implementations and contracts corresponding to:
- `ScientificSearchQueryBuilder`
- `AgriculturalEntityCatalog`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Source Registry and source membership contracts
- Source Capability Contract and capability resolution
- Source adapters and source-specific profiles
- `ScientificResultNormalizer`
- LocalIndex ingestion, indexing, publication, and query paths
- Evidence evaluation and final-answer eligibility
- CSQ identity and scientific query semantics
- Provenance, licensing, and source attribution
- Existing test fixtures, contract tests, integration tests, and readiness gates
Inspect the real paths and class names before editing. The list above is an architectural inspection checklist, not permission to create duplicate abstractions.
Do not introduce a second registry, parallel orchestrator, duplicate indexing pipeline, or alternative scientific search route when the existing architecture already provides the correct extension point.
Any proposed architectural change must be justified against the approved ADRs and current code.
# 3. CURRENT REPORTED BASELINE — VERIFY, DO NOT ASSUME
The latest reported status as of 2026-10-18 is:
- Current source membership authority: `110.v1`.
- `G5-04` GBIF: reported ACTIVE.
- `G1-02` Organic Eprints: reported NOT_READY.
- `G6-20` BHL: reported NOT_READY.
- `G1-01` AGRIS: readiness requires verification.
- `G1-17` Dr. Duke: readiness requires verification.
- A previous test run reported 118 tests and 6,161 assertions passing.
- Live curl checks previously reported HTTP 200 for GBIF species/search and Organic Eprints OAI-PMH `ListRecords`.
- PHP HTTP access to Organic Eprints previously failed with SSL error 60.
- A PostgreSQL blocker was reported.
- Organic Eprints onboarding was reported ACTIVE but incomplete.
- BHL API key configuration was previously reported missing.
**Important updated fact:** The owner has now manually added and saved `BHL_API_KEY` in `backend/.env`.
Therefore, the old statement that the BHL key is missing is no longer an acceptable assumption. Verify the current file and runtime configuration safely.
Previously reported evidence files include:
`docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-THREE-SOURCE-CONTINUATION-REPORT.ar.md`
`docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-THREE-SOURCE-CONTINUATION-EXECUTION.v1.json`
Inspect the current files and their contents. Follow any newer report or evidence file that supersedes these paths.
Do not rerun every historical test simply to repeat old results. Determine what has changed, what remains unverified, and which targeted tests are necessary.
# 4. BHL SECRET — UPDATED OWNER CONFIGURATION
The owner has already added `BHL_API_KEY` to `backend/.env` and saved the file.
Your job is to **verify and use the existing configuration safely**, not to add the key again.
## Required procedure
1. Check whether `backend/.env` exists and contains a non-empty `BHL_API_KEY` assignment.
2. Never print the key value to the terminal, report, test output, screenshots, logs, exception messages, or generated files.
3. Never ask the owner to paste the key again.
4. Never invent, reconstruct, replace, rotate, or overwrite the key.
5. Never copy the key into source code, test fixtures, `.env.example`, ADRs, JSON evidence, or other tracked files.
6. Verify whether `backend/.env` is ignored by Git and whether it is tracked. Use read-only Git inspection.
7. If the file is not safely ignored or is already tracked, stop before taking any action that could expose the secret. Report the finding and propose a safe remediation without deleting or rewriting user data.
8. Inspect how Laravel and the existing application configuration read the environment variable.
9. Determine whether the application expects `BHL_API_KEY`, another variable name, or a configuration key derived from it.
10. If a configuration cache or process lifecycle affects visibility, document the precise issue and use only a safe, existing, non-destructive procedure. Do not clear caches or restart services blindly.
11. Test whether the existing application can access the configuration without revealing its value.
12. Perform a bounded, read-only BHL API request only if the existing code and official API contract establish the correct authentication method.
13. Check both HTTP status and the BHL API's own response status/error fields.
14. Distinguish configuration presence, successful authentication, successful publication search, and usable application integration.
If the key is absent, empty, invalid, or inaccessible to the application, report the precise blocker. Do not attempt to fix it by printing or recreating the secret.
The owner has completed the manual configuration step. Do not report that step as outstanding unless direct evidence shows that the configuration is absent or unusable.
# 5. SOURCE-SPECIFIC ENGINEERING REQUIREMENTS
## 5.1 G5-04 — GBIF
### Official access route
Primary API base:
`https://api.gbif.org/v1/`
Investigate the actual endpoints needed by the application's current source adapter, including:
- `/occurrence/search`
- `/species/match`
- `/dataset/search`
Verify endpoint semantics and parameter requirements against current official documentation.
GBIF's public search endpoints generally support unauthenticated queries. Bulk download is a different workflow and must not be confused with ordinary search.
### Required engineering checks
1. Inspect the existing GBIF adapter, orchestrator integration, source profile, registry entry, and capability contract.
2. Inspect the existing `GbifOrchestratorEndToEndTest` and related tests.
3. Run targeted tests where permitted.
4. Verify a bounded live request using a reproducible, scientifically meaningful query.
5. Confirm that the application's actual scientific query reaches the GBIF integration through the canonical orchestration path.
6. Preserve original GBIF record, occurrence, dataset, taxon, publisher, and license information whenever present and applicable.
7. Do not invent missing identifiers or metadata.
8. Check pagination, error handling, timeouts, result limits, and normalization.
9. Verify that a provider response is not incorrectly treated as scientific evidence for a claim merely because it contains a taxon or occurrence record.
10. Check whether existing ACTIVE status remains justified by current evidence.
### Scope restriction
Do not rewrite a valid working GBIF integration merely to make it look uniform with the other sources. Change it only when a specific defect, contract gap, or approved architectural requirement is demonstrated.
Preserve ACTIVE status only if the existing readiness contract and current evidence continue to support it.
## 5.2 G1-02 — Organic Eprints
### Candidate access routes requiring verification
Inspect the current implementation and verify the following candidate endpoints independently:
- Existing HTTPS OAI-PMH candidate: `https://orgprints.org/cgi/oai2`
- Alternative supplied endpoint: `http://orgprints.org/perl/oai2`
- REST candidate: `https://orgprints.org/rest/`
- RSS candidate: `http://orgprints.org/cgi/latest_tool?output=RSS2`
- Policy page: `https://www.orgprints.org/about_policy.html`
Do not assume that every candidate is currently supported or that all of them expose equivalent data.
Prefer a verified official HTTPS endpoint when available. Do not downgrade TLS security to work around a certificate problem.
### OAI-PMH requirements
Verify the actual behavior of the relevant OAI-PMH verbs:
- `Identify`
- `ListMetadataFormats`
- `ListIdentifiers`
- `ListRecords`
- `GetRecord`
Inspect metadata formats, identifiers, date fields, deleted-record behavior, resumption tokens, pagination, and error responses.
Verify XML parsing, encoding, namespaces, malformed records, retry behavior, rate limits, and idempotent harvesting.
Do not claim full harvesting capability merely because one `ListRecords` request returns HTTP 200.
### REST and RSS
Verify REST and RSS independently before using them.
- Confirm the actual response structure and documented behavior.
- Determine whether REST provides useful record-level metadata or search functionality.
- Determine whether RSS is useful for incremental updates rather than full ingestion.
- Avoid duplicate ingestion paths or redundant polling pipelines.
- Do not implement SWORD deposit functionality unless the current product requirement explicitly needs submission/deposit and the owner authorizes it. This mission concerns scientific discovery and retrieval, not unsolicited record deposit.
### Licensing and provenance
Inspect the official policy and any source-specific reuse conditions.
Preserve record identifiers, original record URLs, abstract-page links, metadata provenance, source dates, and available subject concepts.
Do not assume that document full text can be downloaded, cached, redistributed, or rehosted merely because metadata is accessible.
Where the applicable policy requires links to the abstract or source page, preserve that link in normalized results.
Preserve AGROVOC concepts, concept identifiers, and OpenAIRE metadata only when they are actually supplied by the source and their semantics are established.
### Existing application path and blockers
Inspect the existing:
- `OaiPmhSourceProfile`
- `OaiPmhHarvester`
- `CurrentMembershipNearReadySeatCatalog`
- `OrganicEprintsOaiLocalIndexPathTest`
Use the actual current repository paths; do not assume the listed names are unchanged.
Distinguish fixture-only tests from tests against actual parser behavior and the application's live ingestion path.
Investigate the previously reported PHP SSL error 60.
- Diagnose the actual CA bundle, certificate chain, PHP runtime, and HTTP client configuration.
- Do not disable certificate verification.
- Do not treat curl success as proof that PHP's HTTP client works.
- Do not switch to insecure HTTP simply to bypass TLS validation.
Investigate the PostgreSQL blocker without destructive resets or database changes outside the authorized scope.
Verify the complete path from OAI-PMH retrieval to validated LocalIndex ingestion, publication, catalog/capability resolution, CSQ-based search, and normalized results.
Organic Eprints must remain NOT_READY until the required gates are met with real evidence.
## 5.3 G6-20 — Biodiversity Heritage Library (BHL)
### Official documentation
Use the official API v3 documentation:
`https://www.biodiversitylibrary.org/docs/api3.html`
Inspect the documented API operations and determine which are necessary for the actual application's search and retrieval use case.
Candidate operations include:
- `PublicationSearch`
- `PublicationSearchAdvanced`
- `GetTitleMetadata`
- `GetPageMetadata`
Confirm the exact operation names, required parameters, response structures, authentication requirements, and current availability from the official documentation before implementation.
Do not assume that every candidate operation is necessary. Build the smallest correct integration that meets the approved scientific search requirements.
### Authentication
Use the existing `BHL_API_KEY` in `backend/.env`, which the owner has already added and saved.
Follow Section 4. Never reveal or reproduce the key.
Do not add a second environment variable with a different name unless the existing approved configuration explicitly requires it and the reason is documented.
### Required live and application-level checks
1. Verify the current API v3 authentication method from official documentation.
2. Verify that the existing application configuration reads the secret correctly without exposing it.
3. Execute a bounded read-only authenticated request.
4. Verify HTTP status and API-level error/status fields.
5. Execute a bounded publication search using a relevant scientific query.
6. Where appropriate, retrieve metadata for a returned publication or page using the documented identifier.
7. Verify whether the response includes usable titles, authors, dates, publication identifiers, page identifiers, stable source URLs, and other metadata actually supplied by BHL.
8. Preserve BHL identifiers and source links.
9. Preserve license and reuse information where available; do not assume that all content has identical reuse rights.
10. Test pagination, limits, malformed responses, API errors, timeouts, and empty-result behavior.
11. Verify that the existing CSQ and orchestration path reaches BHL and produces normalized results.
12. Confirm that result normalization does
