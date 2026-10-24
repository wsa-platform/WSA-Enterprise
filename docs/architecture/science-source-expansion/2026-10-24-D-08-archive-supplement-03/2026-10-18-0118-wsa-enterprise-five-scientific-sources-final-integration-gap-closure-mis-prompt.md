<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16857). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 1:18 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16858; it is archived once. -->

---
# WSA-Enterprise — FIVE SCIENTIFIC SOURCES: FINAL INTEGRATION & GAP CLOSURE MISSION
## Exclusive Scope — Complete Implementation, Integration, Defect Remediation, and Verification
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local path:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Canonical source membership:** CURRENT `110.v1`  
**Primary architecture record:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Latest reported five-source report:** `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-FIVE-SOURCE-MASTER-REPORT.ar.md`  
**Latest reported machine evidence:** `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-FIVE-SOURCE-MASTER-EXECUTION.v1.json`
**Mission type:** Autonomous implementation and final integration of five existing scientific-source memberships.
**Mission boundary:** These five sources and only the engineering work necessary to integrate, test, and establish their readiness.
**Required outcome:** Close every remaining source-specific and shared integration defect that can be safely and lawfully resolved under the existing approved architecture. Verify actual end-to-end application search. Leave no avoidable implementation gaps, duplicate investigations, or vague TODOs.
**Required final report:** One comprehensive Arabic report grounded in actual repository changes and executed tests.
---
# 1. EXCLUSIVE MISSION SCOPE
This mission is exclusively for completing these five scientific sources:
| Source ID | Source | Required final engineering outcome |
|---|---|---|
| `G5-04` | GBIF | Preserve and verify the existing working integration; repair only actual defects or regressions. |
| `G1-02` | Organic Eprints | Complete persistent ingestion, LocalIndex publication, CSQ search, and applicable readiness requirements. |
| `G6-20` | Biodiversity Heritage Library (BHL) | Complete the official API-to-application path, metadata retrieval, provenance, tests, and applicable readiness requirements. |
| `G1-01` | AGRIS | Complete verified official ODS acquisition, licensing-aware ingestion, indexing, publication, and application search. |
| `G1-17` | Dr. Duke's Phytochemical and Ethnobotanical Databases | Complete verified archive/schema processing, rights-aware ingestion, indexing, publication, and application search. |
You have broad engineering authority to investigate, design, implement, refactor, test, and document changes required to finish these integrations.
Do not treat this mission as a general project audit, a new scientific-source discovery project, a redesign of the whole research platform, or a request to add more sources.
## Explicitly out of scope
Do not:
- Add a sixth source or expand the canonical source membership.
- Replace CURRENT `110.v1` with a historical registry.
- Redesign unrelated platform modules.
- Rebuild already working source integrations without a verified defect.
- Rewrite the scientific evidence model.
- Change CSQ semantics, final-answer confidence thresholds, or final-answer eligibility to make source tests pass.
- Add unrelated UI features, business features, or new research capabilities.
- Introduce new third-party services when the existing official source and approved architecture are sufficient.
- Repeat the earlier full forensic audit without a specific new reason.
- Repeat completed tests and live checks solely to reproduce old output.
Any change outside these boundaries is permitted only when it is a direct, necessary dependency of completing one or more of the five integrations. If such a dependency exists, make the smallest justified change and document the causal connection.
---
# 2. EXECUTION AUTHORITY AND SAFETY
Act as the autonomous senior engineer responsible for finishing this work—not merely producing a plan.
You are authorized to:
- Diagnose root causes.
- Design the missing source-specific integration components.
- Correct existing adapter and configuration defects.
- Complete source-specific parsing and query projection.
- Implement missing ingestion, indexing, publication, and retrieval steps.
- Correct LocalIndex and PostgreSQL integration defects that block these five sources.
- Repair shared infrastructure configuration when the defect is demonstrated and the correction is safe.
- Complete BHL authentication/configuration wiring without exposing or replacing the owner's secret.
- Add and run regression tests.
- Improve error handling, idempotency, retry behavior, pagination, validation, provenance preservation, and observability as needed for these integrations.
- Update source-specific documentation and mission evidence.
- Continue through independent tasks when another source has a separate blocker.
Do not ask the owner to approve routine coding decisions that are already covered by the approved ADRs and existing contracts.
When a required action is blocked by an external dependency, unavailable service, missing legal evidence, or unresolved governance approval, complete every independent authorized task first.
## Mandatory restrictions
- No commit.
- No push or pull.
- No deployment.
- No Docker.
- No destructive Git operations.
- No destructive database operations or resets.
- No deleting or overwriting unrelated work.
- No unauthorized Capability Store writes.
- No bypassing CapVer.
- No fabricated IC approvals.
- No fabricated test results.
- No source activation without all applicable approved readiness gates.
Stop at the final pre-commit review.
---
# 3. START FROM THE EXISTING COMPLETED WORK
Before changing anything, determine whether another Cursor task is actively editing the repository.
If another task is still running or modifying relevant files, wait for it to finish and inspect its final report. Do not create competing edits.
Then inspect:
1. Actual branch and HEAD.
2. Actual working-tree status, including untracked files.
3. Existing modifications and WIP.
4. The latest five-source report and machine evidence.
5. The relevant approved ADR-023 sections.
6. Current membership, adapter, capability, ingestion, and test implementations for the five sources.
7. Any newer reports or evidence that supersede the previously reported files.
Preserve all existing work. Never reset the repository to reproduce the earlier reported state.
## Required reuse rule
Classify each previously reported item as:
- `COMPLETE_AND_VERIFIED`
- `IMPLEMENTED_BUT_UNVERIFIED`
- `PARTIALLY_COMPLETE`
- `BLOCKED`
- `DEFECT_OR_REGRESSION`
Only revisit completed work when necessary to validate a concrete dependency, test a repair, resolve contradictory evidence, or investigate a regression.
Do not recreate components that already satisfy the approved contract.
Do not rerun the previous 211-test/6,888-assertion suite as a ritual before understanding the actual gaps. Use targeted tests first, then relevant regression tests.
The previously reported HEAD is a reference point only. Record the actual current HEAD.
---
# 4. DEFINITION OF COMPLETE INTEGRATION
For this mission, an integration is not complete merely because an API returns HTTP 200 or an adapter returns SUCCESS.
Each source must have the applicable complete path through the existing architecture:
1. Verified source identity and canonical membership.
2. Verified official access mechanism or official dataset.
3. Correct authentication where required.
4. Correct source-specific query projection or ingestion mechanism.
5. Correct parsing and schema validation.
6. Preserved original source identifiers and provenance.
7. Applicable license and attribution preserved.
8. Safe, idempotent ingestion when local indexing is required.
9. Successful indexing and catalog publication when required.
10. Correct capability resolution under existing governance.
11. Actual canonical CSQ-based retrieval.
12. Correct orchestration and result normalization.
13. Application-visible scientific search results.
14. Correct handling of empty results, source failures, and partial availability.
15. Passing relevant tests and regression checks.
16. All required onboarding, IC, CapVer, and other approved readiness gates satisfied.
For sources that use remote retrieval rather than local ingestion, satisfy the corresponding remote-search path and do not invent a local-ingestion requirement.
**Do not impose a uniform integration method on all sources. Complete each source using the correct method established by its official access contract and the existing architecture.**
---
# 5. SHARED ARCHITECTURE — FIX ONLY WHAT BLOCKS THESE SOURCES
Inspect the existing implementation and extend it rather than duplicating it.
Relevant inspection targets include:
- `ScientificSearchQueryBuilder`
- `AgriculturalEntityCatalog`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Canonical Source Registry and membership
- Source Capability Contract and resolution
- Source adapters and source-specific profiles
- `ScientificResultNormalizer`
- LocalIndex ingestion, indexing, publication, and query paths
- Evidence evaluation and claim relationships
- Final-answer eligibility
- Existing IC and CapVer governance
Confirm actual class names and file paths before editing.
## 5.1 Preserve CSQ
The Canonical Scientific Question remains the semantic authority.
Source-specific query translation may adapt syntax, filters, and supported parameters. It must not silently change the original scientific question, discard essential constraints, or pretend a source can express a constraint it does not support.
Do not substitute keyword-only search for the approved CSQ path.
Add or repair tests for CSQ preservation when a genuine defect is found.
## 5.2 Preserve source identity and capability
Use the existing canonical registry and CURRENT `110.v1`.
Do not create duplicate source memberships, alter IDs, or introduce a parallel registry.
A source's membership, adapter existence, capability, operational availability, and readiness are distinct concerns. Preserve those distinctions.
Do not claim unsupported full-text, bulk-download, incremental-harvest, semantic-search, or other capabilities.
Keep the existing fail-closed behavior where required capabilities or decisions are unresolved.
## 5.3 Preserve scientific evidence rules
A retrieved record is not automatically proof of a scientific claim.
A taxonomic record, bibliographic entry, reported traditional use, or source metadata match must not be upgraded to direct scientific evidence without the required basis.
Preserve existing distinctions among directness, claim relationship, and disposition.
Do not weaken evidence thresholds, confidence thresholds, or final-answer eligibility.
## 5.4 Preserve provenance and licensing
Keep source identifiers, URLs, publisher/provider information, dates, applicable license metadata, and other original source fields when present.
Do not invent missing values.
Do not erase source-specific metadata during normalization.
Do not redistribute full text or archived datasets unless the applicable rights permit it.
---
# 6. COMPLETE GBIF `G5-04` WITHOUT REBUILDING IT
## Current reported state
GBIF is the only reported ACTIVE source.
The previous report states that the existing integration was preserved, capability-related test alignment was completed, and live species/search returned HTTP 200 with a Triticum sample.
## Required finalization
1. Inspect the existing adapter, source profile, capability mapping, registry entry, and orchestration path.
2. Inspect the relevant existing end-to-end and capability tests.
3. Determine whether any real defect or regression remains.
4. Verify that the canonical query reaches GBIF through the existing orchestrator.
5. Confirm result normalization preserves original GBIF identifiers, dataset/publisher metadata, URLs, and applicable licensing information.
6. Verify the existing handling of parameters, pagination, timeouts, errors, and empty results.
7. Correct only demonstrated defects.
8. Run targeted regression tests for the changes.
Do not rebuild a working adapter, duplicate an existing query path, or rerun historical checks without a specific reason.
Preserve ACTIVE status if the existing approved readiness gates remain satisfied.
**GBIF completion criterion:** Existing integration remains functional, architecturally compliant, and regression-free. No new activation is required.
---
# 7. COMPLETE ORGANIC EPRINTS `G1-02`
## Current reported state
The previous report states:
- OAI-PMH Identify/ListRecords returned HTTP 200.
- The in-memory harvest produced 96 records.
- PHP CA/TLS remediation was performed.
- PostgreSQL and incomplete onboarding/governance remain blockers.
Treat the 96 in-memory records as evidence of that reported harvest only, not proof of persistent ingestion or application search.
## 7.1 Complete the actual OAI-PMH path
Inspect the current OAI-PMH implementation, source profile, parser, harvester, tests, and evidence.
Verify the applicable operations:
- `Identify`
- `ListMetadataFormats`
- `ListIdentifiers`
- `ListRecords`
- `GetRecord`
Check the source's actual supported metadata formats and verify XML parsing, namespaces, identifiers, resumption tokens, pagination, error responses, and idempotency as applicable.
Do not repeat the same harvest merely to reproduce 96 records.
Repair only gaps that remain unverified or defective.
Use verified HTTPS and proper certificate validation. Never disable TLS verification.
## 7.2 Close the PostgreSQL blocker
Diagnose the actual failure rather than guessing its cause.
Inspect:
- Current Laravel database configuration.
- Environment-variable wiring without printing secrets.
- Hostname resolution and network reachability where permitted.
- PostgreSQL service availability.
- Authentication and connection errors.
- Database/schema existence through read-only inspection.
- Migration state and LocalIndex requirements.
- Differences between CLI, test, and application runtimes.
- Any stale configuration cache that demonstrably affects this integration.
The hostname `postgres` may be valid in one runtime and invalid in another. Establish the actual runtime before changing it.
If the cause is a configuration defect, implement the smallest correct fix.
Do not reset the database, drop tables, recreate the database, erase records, or run destructive migrations.
If external infrastructure is unavailable, complete all independent parser, ingestion-contract, and query work, then identify the exact remaining infrastructure action.
## 7.3 Complete persistent ingestion and application search
Verify and implement the missing portions of:
OAI-PMH → parser → validated records → persistent ingestion → LocalIndex → catalog publication → approved capability resolution → canonical CSQ query → orchestrator → normalizer → application-visible results.
Ensure repeated ingestion is idempotent and does not create duplicate records.
Preserve record IDs, abstract/source links, available metadata, and applicable licensing restrictions.
Do not assume that REST, RSS, or SWORD needs to be added. Add another access path only when a verified requirement makes it necessary.
## 7.4 Complete onboarding and readiness
Inspect the actual onboarding status and the precise remaining criteria.
Complete all technically authorized requirements.
If a remaining IC or CapVer decision requires separate approval, do not fabricate it or bypass it. Prepare the required evidence and finish all independent implementation work.
**Organic Eprints completion criterion:** Persistent records can be found through the actual application search, required source metadata and rights are preserved, and all approved readiness gates pass. Otherwise retain NOT_READY.
---
# 8. COMPLETE BHL `G6-20`
## Current reported state
The previous report states:
- The malformed dotenv entry was corrected.
- `BHL_API_KEY` is configured in `backend/.env`.
- The file is gitignored and untracked.
- `PublicationSearch` returned `Status=ok`.
- The adapter reported SUCCESS.
- PartID/PartUrl handling was addressed.
- IC/CapVer/governed registration remains unresolved.
## 8.1 Protect the existing API key
The owner manually added and saved `BHL_API_KEY` in `backend/.env`.
Do not ask for the key again.
Do not add a duplicate entry or change its value.
Do not print it, log it, copy it to documentation, expose it in a test, or place it in a tracked file.
Verify safely that:
- The variable exists and is non-empty.
- The environment file remains ignored and untracked.
- The application's actual configuration reads the variable.
- The runtime can use the configured key without exposing it.
If the secret appears to have been exposed, recommend owner-managed rotation through BHL. Do not rotate it yourself.
## 8.2 Complete the official API integration
Use the official documentation:
`https://www.biodiversitylibrary.org/docs/api3.html`
Verify the current API contract for the operations required by the existing integration, including publication search and relevant metadata retrieval.
The previous `Status=ok` response is not proof that the entire application path works.
Verify:
1. Correct authentication.
2. HTTP and API-level status/error handling.
3. Publication search with a meaningful CSQ-derived query.
4. Pagination and bounded result handling.
5. Metadata retrieval where required.
6. PartID and PartUrl mapping.
7. Source identifiers, stable URLs, publication/page metadata, and applicable rights.
8. Empty results, malformed data, authentication failures, API errors, and timeouts.
9. Canonical query projection and orchestrator integration.
10. Normalized application-visible results.
11. Regression tests.
Do not add a duplicate BHL adapter or an unapproved paid intermediary.
## 8.3 Resolve the actual governance gap
Inspect the existing IC, CapVer, onboarding, and registration requirements.
Determine precisely which decision or gate remains unresolved.
Complete all approved technical work and generate the evidence required by the existing process.
Do not write unauthorized capability decisions into the Capability Store. Do not invent IC approval or bypass CapVer.
If a required owner decision is still pending, provide the exact decision request and its supporting evidence.
**BHL completion criterion:** The secret-safe official API integration works through the actual application path, tests pass, provenance is preserved, and the existing approved governance gates are satisfied. Otherwise retain NOT_READY.
---
# 9. COMPLETE AGRIS `G1-01`
## Current reported state
The previous report states that the official ODS strategy was confirmed and the download page and ODS endpoint returned successful HEAD responses.
A HEAD response does not prove that the actual dataset can be downloaded, parsed, ingested, licensed for the intended use, or searched.
## 9.1 Verify the actual official dataset
Inspect the existing evidence and the official resources:
- `https://www.fao.org/agris/services`
- `https://www.fao.org/agris/download`
- `https://www.fao.org/agris/agris-ods`
- `https://agris.fao.org/agris_ods/`
- `https://www.fao.org/agris/news/how-submit-data-agris-instructions-data-providers`
Verify the actual artifact used by the implementation:
- Download URL and redirects.
- Actual file names and formats.
- Release/version and retrieval date.
- Archive structure and size.
- Schema and encoding.
- Record identifiers and metadata fields.
- Snapshot versus incremental-update behavior.
- Applicable license and attribution terms.
If a valid artifact was already downloaded, inspect and reuse it instead of downloading a duplicate.
Do not assume that AP, RDF, public SPARQL, or a public OAI-PMH harvesting endpoint exists without direct verification.
Distinguish data-provider submission facilities from public access to AGRIS's own records.
## 9.2 Establish applicable rights
Resolve the previously reported CC BY 3.0 IGO versus CC BY 4.0 discrepancy for the actual artifact being integrated.
Do not select a license by assumption.
Preserve required attribution and source metadata.
If the applicable reuse terms cannot be established, do not publish or redistribute the affected data under invented rights.
## 9.3 Complete ingestion and search
Use the existing LocalIndex architecture.
Complete any missing steps in:
Verified official dataset → safe acquisition → schema validation → parsing → idempotent ingestion → provenance/license preservation → indexing → publication → capability resolution → CSQ-based search → normalized application results.
Preserve original AGRIS IDs, provider identifiers, URLs, bibliographic metadata, dates, abstracts, and AGROVOC identifiers where present.
Use bounded or streaming processing when necessary.
Ensure repeated ingestion does not duplicate records.
Add tests based on the actual verified schema and representative records.
Verify that a real application query retrieves AGRIS records through the existing search path.
**AGRIS completion criterion:** The actual dataset, schema, and rights are verified; ingestion and indexing work safely; records are searchable; and all approved readiness gates pass. Otherwise retain NOT_READY.
---
# 10. COMPLETE DR. DUKE `G1-17`
## Current reported state
The previous report states that a CSV/CC0 strategy was identified and that the source page returned HTTP 200 with an archive appearing available.
These observations do not establish the archive's complete contents, schema, applicable license, or operational integration.
## 10.1 Verify the actual archive and schema
Official catalog candidate:
`https://catalog.data.gov/dataset/dr-dukes-phytochemical-and-ethnobotanical-databases`
Previously reported candidate artifacts:
- `Duke-Source-CSV.zip`
- `DrDukesDatabaseDataDictionary-prelim.csv`
Verify the actual archive and data dictionary through authoritative catalog metadata and download information.
Inspect the existing downloaded artifact first if available.
Verify:
- Actual files and archive structure.
- Release/snapshot date.
- CSV encoding, delimiters, headers, and types.
- Table relationships and key semantics.
- Data dictionary.
- Record identifiers, missing values, duplicates, and invalid references.
- Applicable license/public-domain terms and attribution requirements.
- Whether the
