<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16891). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 1:42 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16892; it is archived once. -->

---
# WSA-Enterprise — FINAL FIVE-SOURCE REMAINING-GAPS EXECUTION
## Mission: Finish the Five Existing Scientific Integrations Only
**Repository:** `G:\WSA-Enterprise`  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Last reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Canonical membership:** CURRENT `110.v1`
**Latest report:**
`docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-FIVE-SOURCE-GAP-CLOSURE-REPORT.ar.md`
**Owner decisions evidence:**
`2026-10-18-FIVE-SOURCE-GAP-CLOSURE-OWNER-DECISIONS.v1.json`
## 1. Exclusive objective
Finish the remaining engineering work for exactly these five sources:
- `G5-04` — GBIF
- `G1-02` — Organic Eprints
- `G6-20` — Biodiversity Heritage Library (BHL)
- `G1-01` — AGRIS
- `G1-17` — Dr. Duke's Phytochemical and Ethnobotanical Databases
Do not expand the project scope or add sources.
Do not repeat completed work without a specific verification need.
Continue from the actual current repository state and the latest implementation, not from assumptions about the previously reported HEAD.
You have broad authority to investigate, design, implement, repair, test, and document all changes necessary to finish these five integrations within the approved architecture.
Do not stop at recommendations when an authorized code fix is possible.
## 2. Preserve the latest completed work
The latest report states:
- No new source was activated.
- GBIF remains the only ACTIVE source.
- Organic Eprints has a persistent `scientific_records` store, a 96-record harvest, and a successful search probe.
- BHL has its key configured and a CSQ orchestrator test using a fixture.
- AGRIS fixture ingestion into persistent storage works, but the ODS ZIP could not be obtained.
- Dr. Duke's archive contains 16 CSV files; 500 `FNFTAX` records were persisted and searched.
- The local PostgreSQL hostname `postgres` did not resolve, and `localhost:5432` was closed.
- SQLite was introduced as a persistent LocalIndex path.
- The reported test run passed 116 tests and 6,182 assertions.
- No commit, push, deployment, Docker execution, or reset occurred.
First inspect the actual implementation and evidence for these claims.
Treat them as prior results, not as permission to rebuild or repeat them.
Protect all current changes and uncommitted work.
If another Cursor task is actively modifying the repository, wait until it finishes before proceeding.
## 3. Critical SQLite/production boundary
SQLite is reported as a local persistent LocalIndex solution introduced because PostgreSQL is unavailable locally.
Determine exactly how this SQLite implementation is integrated into the existing architecture.
Verify:
1. Whether it is an approved LocalIndex storage backend or an experimental fallback.
2. Whether its schema, transactions, identifier uniqueness, indexing, idempotency, and query semantics satisfy the existing LocalIndex contract.
3. Whether its configuration is isolated from the production PostgreSQL configuration.
4. Whether it introduces source-specific behavior or silently changes the canonical storage contract.
5. Whether data persists across separate application/process invocations.
6. Whether harvest, publication, and search operate against the same persistent store.
7. Whether concurrent access and error handling are appropriate for the actual supported use case.
8. Whether existing PostgreSQL behavior and production configuration remain unchanged.
9. Whether the SQLite implementation can safely be used for local verification without being misrepresented as proof of PostgreSQL production readiness.
Do not remove the SQLite implementation simply because PostgreSQL is unavailable locally.
Do not promote SQLite to the production system of record without explicit architectural authorization.
Do not claim PostgreSQL production integration is verified when only SQLite has been tested.
Repair any confirmed defect in the SQLite LocalIndex path and add targeted tests. Preserve existing data and do not perform destructive resets.
If production PostgreSQL cannot be verified in this environment, complete all independent integration work and report the exact remaining production verification requirement.
## 4. Organic Eprints — finish the persistent search path
Preserve the successful 96-record harvest and search probe.
Do not re-harvest solely to reproduce the same result.
Inspect the actual implementation and verify the complete path:
OAI-PMH retrieval → parser → persistent `scientific_records` storage → LocalIndex/catalog visibility → approved capability resolution → CSQ-based search → normalized application result.
Verify idempotency, record identifiers, provenance, source URLs, metadata parsing, pagination/resumption handling, and error behavior.
Test persistence across separate invocations, not just within one process.
Investigate the previously reported PHP CA/TLS issue only if the latest evidence shows that it remains unresolved or a regression is suspected. Never disable certificate verification.
Complete all technically authorized onboarding work.
Do not fabricate IC or CapVer approval.
If onboarding or CapVer remains unresolved, identify the exact approved requirement that is pending and prepare the required evidence without bypassing it.
Do not claim full readiness solely because the local SQLite probe succeeds.
## 5. BHL — verify the live API-to-application path
The previous test used a fixture. Preserve it as a unit/integration test, but do not describe it as proof of live BHL search.
Use the official API documentation:
`https://www.biodiversitylibrary.org/docs/api3.html`
The owner has already configured `BHL_API_KEY` in `backend/.env`. Do not request or print the secret, add a duplicate entry, replace it, or write it into tracked files.
Safely verify the existing configuration and confirm that the application can use it.
Perform bounded live checks only as required to close a specific verification gap.
Verify the applicable path:
CSQ → capability resolution → BHL query translation → authenticated official API → response parsing → publication/page metadata mapping → normalization → application-visible result.
Verify HTTP status and API-level status/error fields, PartID/PartUrl mapping, stable source identifiers, provenance, licensing information when supplied, empty-result handling, and authentication/API errors.
Do not log secret-bearing URLs or authorization headers.
Then inspect the actual remaining IC, CapVer, onboarding, and registration requirements.
Complete all authorized technical work.
Do not write unauthorized Capability Store decisions, invent IC approval, or activate BHL without all required approved gates.
If the live API or a required approval is unavailable, complete independent work and state the precise remaining blocker.
## 6. AGRIS — resolve the unavailable ODS artifact without guessing
The latest report states that the ODS ZIP was unavailable, with an empty listing or 404.
Do not repeatedly request the same failing URL without a reason.
Inspect the previous requests and evidence first. Then investigate official AGRIS resources and identify a genuinely available, authoritative dataset or supported access mechanism.
Candidate official resources include:
- `https://www.fao.org/agris/services`
- `https://www.fao.org/agris/download`
- `https://www.fao.org/agris/agris-ods`
- `https://agris.fao.org/agris_ods/`
- `https://www.fao.org/agris/news/how-submit-data-agris-instructions-data-providers`
Verify the actual artifact, release, format, schema, identifiers, record coverage, download behavior, and applicable license.
Do not assume that an official public SPARQL endpoint or public OAI-PMH corpus endpoint exists.
Do not conflate OAI-PMH submission mechanisms for data providers with a public harvesting API for AGRIS records.
Resolve the reported CC BY 3.0 IGO versus CC BY 4.0 discrepancy using authoritative evidence applicable to the actual dataset.
If an alternative official distribution is verified and legally usable, implement the smallest compatible ingestion path using the existing architecture.
Preserve original AGRIS identifiers, provider/source links, bibliographic metadata, AGROVOC identifiers, provenance, and attribution where present.
Use safe, idempotent ingestion and verify persistent search through the canonical CSQ path.
Do not substitute fixture ingestion for actual official-data ingestion.
If no accessible official artifact is available, do not invent a URL, fabricate a download, or silently switch to an unauthorized third-party dataset. Finish all independent integration work and document the precise external dependency.
## 7. Dr. Duke — complete the 16-CSV data model and integration
The latest report states that the archive was acquired, contains 16 CSV files, and 500 `FNFTAX` records were persisted and searched.
Preserve that work.
Do not repeat the same download or insert the same 500 records again without a specific reason.
Inspect all 16 CSV files and the available data dictionary. Verify actual filenames, schema, field meanings, record counts, keys, relationships, encoding, missing values, and applicable rights.
Use the authoritative catalog as the starting point:
`https://catalog.data.gov/dataset/dr-dukes-phytochemical-and-ethnobotanical-databases`
Verify the applicable license/public-domain terms for the actual archived data. Do not assume CC0 without evidence.
Identify which files and tables contain taxonomic, chemical, phytochemical, and ethnobotanical data, but only use classifications supported by the actual schemas.
Map the actual relationships among plants, compounds, uses, and biological-activity records where supported by real identifiers and keys.
Do not infer relationships from coincidental text matches or invent missing foreign keys.
Do not transform reported ethnobotanical use into a proven efficacy claim.
Extend the existing ingestion implementation so the required datasets are processed safely and idempotently.
Preserve source/table/row identifiers, original values where required, provenance, source links, and applicable rights metadata.
Add schema and relationship validation tests.
Verify persistence across separate invocations and test retrieval through the actual canonical CSQ-based application path.
Clearly distinguish:
- Files inspected.
- Files successfully ingested.
- Records persisted.
- Relationships successfully mapped.
- Records actually searchable.
- Remaining files or relationships that could not be safely interpreted.
Do not claim complete chemical/ethnobotanical integration until the required relationships and search behavior are demonstrated.
## 8. GBIF — preserve the working source
GBIF remains the only reported ACTIVE source.
Inspect the current code and latest tests for regression only.
Do not rebuild or re-investigate the adapter without a concrete defect.
Verify the existing source identity, capability contract, query projection, provenance, and application search as needed to validate a specific change.
Do not alter ACTIVE status unless current evidence shows that its approved readiness requirements are no longer satisfied.
## 9. Shared architecture and scientific integrity
All five integrations must preserve:
- CURRENT `110.v1` membership.
- Canonical Scientific Question (CSQ) identity.
- `ScientificSearchQueryBuilder`.
- `AgriculturalEntityCatalog`.
- `ScientificQueryCompiler`.
- `MultiSourceScientificSearchOrchestrator`.
- Existing Source Registry and Capability Contract.
- Existing capability resolution and fail-closed behavior.
- `ScientificResultNormalizer`.
- Existing LocalIndex and catalog publication contracts.
- Source identifiers, provenance, and applicable licensing.
- Existing evidence evaluation and final-answer eligibility.
Inspect actual paths and contracts before editing. Reuse the existing architecture; do not create duplicate registries, adapters, or indexing pipelines.
Do not weaken evidence thresholds or change confidence rules to make tests pass.
A bibliographic record, taxonomic record, or reported traditional use is not automatically proof of scientific efficacy.
## 10. Tests and acceptance evidence
Run targeted tests for actual changes first, then relevant regressions.
Verify:
- Persistent storage across process invocations.
- Safe/idempotent ingestion.
- Correct source-specific query translation.
- CSQ preservation.
- Correct capability resolution.
- Provenance and license preservation.
- LocalIndex/catalog publication.
- Actual application search.
- Correct handling of empty results, malformed data, and source failures.
- Existing GBIF behavior remains intact.
Distinguish fixture tests, mocked tests, live HTTP checks, and persistent-data tests.
Do not report historical test results as newly executed tests.
The previous baseline was reported as 116 tests and 6,182 assertions. Record the actual current results; do not force the new suite to match the historical count.
Do not run Docker, destructive database operations, or resets.
## 11. Readiness and governance
Assess each source independently using the existing approved readiness contract.
- GBIF: preserve ACTIVE if its current gates pass.
- Organic Eprints: require persistent search and all applicable onboarding/CapVer gates.
- BHL: require the real application path and all applicable IC/CapVer/registration gates.
- AGRIS: require verified official data, applicable rights, actual ingestion, and application search.
- Dr. Duke: require verified data/schema/rights, required relationship mapping, and application search.
Do not activate a source merely because tests pass or data exists in SQLite.
Do not bypass governance to reach a target of five ACTIVE sources.
If a required approval is pending, finish all independent technical work and record the precise pending decision and evidence.
## 12. Change and execution restrictions
- No commit.
- No push or pull.
- No deployment.
- No Docker.
- No destructive Git operations.
- No destructive database operations or reset.
- No deletion of existing work.
- No exposure of secrets.
- No unauthorized Capability Store writes.
- No invented IC decisions.
- No unauthorized source activation.
Inspect the final diff and preserve all existing WIP.
## 13. Required final Arabic report
Produce one comprehensive report with:
1. Executive summary.
2. Actual repository state and final diff.
3. Previously completed work reused without duplication.
4. Root cause and fix for each remaining defect.
5. Organic Eprints persistent ingestion/search and PostgreSQL limitation.
6. SQLite LocalIndex design, durability evidence, scope, and production boundary.
7. BHL secret-safe configuration, live API status, application search, and governance.
8. AGRIS official artifact availability, licensing evidence, and ingestion outcome.
9. Dr. Duke's 16 CSV files, schemas, record counts, mapped relationships, and searchable results.
10. GBIF regression status.
11. Exact test commands and actual results.
12. Files changed.
13. Readiness and outstanding gates for each source.
14. Precise remaining external dependencies or owner decisions.
15. Confirmation that no commit, push, deployment, Docker, or destructive reset occurred.
## Final directive
Finish the five integrations only.
Reuse the successful work already present. Repair every verified, authorized remaining defect. Complete actual data ingestion and application search wherever the official data and environment permit. Do not substitute fixtures or local SQLite success for unverified production readiness.
Do not stop at recommendations when an authorized implementation is possible. Do not fabricate success when an external dependency or approval is genuinely unavailable.
Complete all independent work, test the changes, inspect the final diff, and stop at the pre-commit review. Deliver one evidence-backed Arabic report.
