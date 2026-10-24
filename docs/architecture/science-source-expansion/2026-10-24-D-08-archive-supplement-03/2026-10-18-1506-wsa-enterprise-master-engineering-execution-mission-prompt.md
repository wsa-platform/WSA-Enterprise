<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16935). Message timestamp as recorded in the conversation: Sunday, Oct 18, 2026, 3:06 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16936; it is archived once. -->

---
# WSA-Enterprise — MASTER ENGINEERING EXECUTION MISSION
## Five Scientific Sources Integration & Activation + Unified Artifact Archive

**Mission date:** 2026-10-18  
**Repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Previously reported HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`  
**Scientific source membership version:** `110.v1`

---

# PART I — MISSION AUTHORITY AND EXPECTED OUTCOME

## 1. Your role

Act as the Lead Software Engineer, Scientific Search Integration Engineer, Data-Pipeline Engineer, Persistence Engineer, Archive-System Engineer, and Test Engineer responsible for executing this mission.

This is an implementation and verification assignment, not a request for a plan, a list of suggestions, or a theoretical design.

You have broad engineering authority to:

- Investigate the actual repository, architecture, runtime behavior, existing contracts, persistence, and tests.
- Read the latest reports, relevant ADRs, approved owner decisions, and previous implementation evidence.
- Modify and create the code, tests, documentation, migrations, commands, and narrowly scoped architectural records required by this mission.
- Reuse existing components, data, checkpoints, manifests, archives, and test results whenever they remain valid.
- Run authorized tests and bounded live integration probes.
- Repair actual defects, incomplete dependency wiring, missing source integration, indexing failures, persistence defects, and archive-retrieval problems.
- Complete all independent engineering work without requesting confirmation for every ordinary action.
- Make reasonable implementation decisions consistent with the actual architecture and approved ADRs.
- Deliver one comprehensive Arabic final engineering report supported by real evidence.

**Do not stop after presenting an investigation or implementation plan.** Continue through implementation, testing, defect correction, regression verification, documentation, and final review until the authorized work is completed or a genuine blocker prevents further safe progress.

Do not ask repetitive questions. Investigate related areas together, execute the independent work, and consolidate the findings into one final report.

## 2. Two mandatory workstreams

Complete both workstreams within this mission:

### Workstream A — Scientific source integration and activation readiness

Complete the maximum legitimate level of integration for:

1. GBIF — `G5-04`
2. Organic Eprints — `G1-02`
3. BHL / Biodiversity Heritage Library — `G6-20`
4. AGRIS — `G1-01`
5. Dr. Duke — `G1-17`

### Workstream B — Unified archive, indexing, retrieval, and continuity

Implement or extend a general-purpose durable archive for:

- JSON files and artifacts.
- AHO artifacts, after identifying their actual format and meaning.
- Cursor prompts and execution instructions.
- Cursor execution reports, engineering reports, test reports, and audit reports.
- Mission specifications, architectural proposals, owner-decision proposals, checkpoints, and stopping points.
- Other explicitly supported task artifacts discovered during repository inspection.

The archive must support original-content preservation, stable identities, provenance, version history, searchable metadata, reliable retrieval, integrity verification, and continuity across future Cursor sessions and new conversations.

These workstreams are related but must remain architecturally distinct. Scientific source records and evidence must not be confused with mission prompts, engineering reports, or archived artifacts.

## 3. Current baseline — verify before relying on it

The latest reported baseline states:

- GBIF is `ACTIVE`.
- Organic Eprints has a persistent SQLite-backed store at generation 2 with 189 active records.
- BHL has a working live PublicationSearch probe returning 52 results for `Triticum`.
- AGRIS has a bounded ODS harvest covering five providers, 581 projected records, and LocalIndex generation 3; the corpus harvest is incomplete.
- Dr. Duke has a 16-table reconciliation report and a limited search projection of 1,500 records.
- A broad regression run reported 214 tests and 6,902 assertions, with five additional focused tests.
- PostgreSQL production readiness and the full governed application path for the four non-GBIF sources remain unverified or incomplete.
- A universal mission-artifact archive has not yet been established by the available report; its existence and coverage must be investigated rather than assumed.
- The repository worktree contains uncommitted work and local artifacts that must be preserved.

These are reported facts, not proof of the current checkout's state. Verify them before making decisions.

Inspect the actual files corresponding to:

- `2026-10-18-MASTER-FIVE-SOURCE-FINAL-REPORT.ar.md`
- `2026-10-18-MASTER-ACTIVATION-READINESS.v1.json`
- `2026-10-18-MASTER-OWNER-DECISIONS-PROPOSAL.v1.json`
- Relevant sections of ADR-023 and other applicable ADRs.
- Existing archive-related implementation, schemas, reports, prompts, and manifests.

If a file is missing or located elsewhere, search the repository safely and report the actual finding. Do not invent files or historical evidence.

---

# PART II — NON-NEGOTIABLE PROTECTION RULES

## 4. Preserve all completed work

Before modifying anything:

1. Inspect `git status --short`.
2. Confirm the current branch and actual HEAD.
3. Inventory tracked modifications, untracked files, and relevant local data artifacts.
4. Identify pre-existing WIP separately from changes introduced during this mission.
5. Record the initial state sufficiently to perform a final before/after comparison.

Mandatory rules:

- Never discard, reset, clean, overwrite, or silently replace pre-existing work.
- Never delete historical reports, prompts, data artifacts, or previous versions to make the repository appear clean.
- Do not rebuild a working component without a demonstrated defect or requirement.
- Do not repeat a successful harvest, large download, extraction, or broad regression suite without a specific technical reason.
- Reuse valid manifests, checkpoints, SQLite generations, reconciliation results, archives, and existing tests.
- Prefer idempotent, resumable operations and targeted probes.
- Keep changes scoped to the two workstreams and their necessary integration.
- Do not change completed GBIF behavior unless a concrete gap or regression justifies a minimal correction.
- Preserve existing ADR content and decision history.

If a proposed change conflicts with existing WIP, inspect and reconcile it carefully. Never replace a whole file simply to simplify editing when a narrower modification is sufficient.

## 5. Prohibited operations

The following restrictions are mandatory:

- No `git commit`.
- No `git push`.
- No deployment or production mutation.
- No Docker usage.
- No `git reset`, `git clean`, destructive cleanup, or other operations that discard existing work.
- No automatic credential rotation.
- No weakening of scientific evidence requirements, security, licensing, or activation gates.
- No fabricated test results, approvals, provenance, source coverage, archive coverage, or production readiness.
- No indiscriminate recursive archiving of the entire repository.
- No unrelated refactoring or uncontrolled scope expansion.

Keep the branch and history intact. Leave all changes uncommitted for review.

---

# PART III — WORKSTREAM A: FIVE SCIENTIFIC SOURCES

## 6. Required integration path

For each source, investigate and implement the appropriate end-to-end path through the existing architecture:

1. Stable source identity.
2. Source registry and capability contract.
3. Capability verification and governance-controlled registration.
4. Source-specific query construction.
5. CSQ semantic authority and query compilation.
6. Adapter execution.
7. Result normalization.
8. Provenance and record identity.
9. Evidence and claim-relationship semantics.
10. Persistent storage and LocalIndex ingestion/query where appropriate.
11. Orchestration and failure isolation.
12. Governed application-facing search.
13. Relevant tests and operational verification.
14. Legitimate activation readiness.

Do not create duplicate adapters, duplicate source identities, parallel registries, or redundant persistence layers if the existing architecture already provides the required behavior.

Inspect actual contracts and implementations before introducing new abstractions.

## 7. GBIF — G5-04

Preserve the existing working `ACTIVE` source.

Verify:

- Source identity and registry consistency.
- Capability and query integration.
- Adapter behavior and normalization.
- Provenance and stable result identity.
- Application-facing search.
- Error handling and coexistence with the other sources.
- Existing regression coverage.

Do not add an unnecessary LocalIndex snapshot for a source whose intended path is live. Do not rebuild or rewrite working functionality merely to align code stylistically.

Make only necessary, evidence-backed changes and demonstrate that existing behavior remains intact.

## 8. Organic Eprints — G1-02

Build on the existing persistent `scientific_records` / SQLite implementation and reported generation-2 data.

Verify and complete the integration among:

- Source identity and capability declaration.
- Scientific query construction and adapter execution.
- Result normalization and provenance.
- Persistent record storage.
- `LocalScientificIndex` and `LocalIndexIngestionService`.
- Container dependency injection.
- Governed application-facing search.
- Source-specific error handling.
- Relevant regression tests.

Confirm persistence across a new process or independent repository instance where appropriate. Do not rely on an in-memory collection or test-only state.

Reuse the existing dataset unless it is demonstrably corrupt, stale for the required behavior, or missing necessary records.

Repair real wiring or registration defects where authorized by existing approved architecture.

Do not treat a successful local SQLite query as proof of production PostgreSQL readiness.

## 9. BHL — G6-20: mandatory use of the saved API key

**Use the existing BHL API key already saved in the project environment. Do not ask me to provide it again.**

The latest report states that the key exists in the ignored local file `backend/.env` under `BHL_API_KEY`, and that its presence was checked without printing its value.

Execute the following:

1. Verify the environment variable is available to the actual Laravel runtime.
2. Verify that the existing configuration path passes the credential securely to the BHL client.
3. Use the saved key for a genuine live BHL API request.
4. Test the real application integration path, not just the standalone adapter or a probe script.
5. Verify authentication, response parsing, normalization, PartID/PartUrl preservation, stable source identity, provenance, supported continuation/pagination, timeout handling, and source-specific failure behavior.
6. Verify that BHL is actually wired into the governed application search path.
7. Repair missing integration wiring and implementation defects.
8. Add focused regression tests and run them.
9. Verify BHL failures do not corrupt the shared store or invalidate unrelated source results.

### BHL security restrictions

- Never print, log, echo, commit, or include the API key in code, fixtures, test output, reports, screenshots, or archive metadata.
- Do not disclose the key value in the final report.
- Do not duplicate the key into another file or hardcode it.
- Do not rotate the key automatically.
- If evidence suggests exposure, document the evidence and recommend rotation without interrupting the other work.
- Do not equate HTTP 200 with full integration or activation readiness.

Use only the existing secure configuration mechanism.

## 10. AGRIS — G1-01

Build on the existing ODS harvester, manifest, checkpoint, and LocalIndex generation 3.

Inspect the current state before executing more work.

Then:

1. Verify the official ODS artifact and provider-download URLs using the existing HTTPS and host/path validation rules.
2. Inspect the actual provider inventory and existing checkpoint.
3. Resume from the checkpoint rather than restarting the previously successful five-provider harvest.
4. Continue bounded, resumable harvesting within safe operational limits.
5. Preserve provider-specific licensing evidence and provenance for every ingested record.
6. Do not generalize the license of a subset of providers to the entire AGRIS corpus.
7. Reject or quarantine records lacking required licensing or provenance evidence according to approved contracts.
8. Verify idempotent ingestion and actual query visibility.
9. Verify the governed application-facing search path.
10. Test source-specific failures and their isolation.

Do not claim full corpus completion unless the manifest, checkpoints, and provider inventory prove it.

If additional providers cannot be processed because of external availability, licensing uncertainty, resource limits, or missing approval, complete all independent work and document the precise limitation.

Do not activate AGRIS merely because more records were harvested.

## 11. Dr. Duke — G1-17

Build on the existing extracted archive, 16-table reconciliation report, relationship measurements, and limited search projection.

Inspect existing schemas, source artifacts, and reports before changing anything.

Complete the remaining justified work:

- Source identity and capability wiring.
- Taxonomic and chemical identifiers.
- Safe searchable projections.
- Correct relationship handling.
- Query and index integration.
- Record-level provenance.
- Focused projection and relationship tests.
- Governed application-facing search.

Mandatory semantic safeguards:

- Keep `FARMACY` and `FARMACY_NEW` distinct unless an approved, evidence-backed mapping explicitly supports a particular relationship.
- Do not merge overlapping tables blindly.
- Do not represent traditional ethnobotanical use as proof of clinical efficacy.
- Do not fabricate taxonomic matches.
- Preserve unmatched and ambiguous relationships as auditable data.
- Use bounded-memory, idempotent processing.

Reuse existing extracted files and reconciliation results. Do not repeat full extraction or reconciliation without a specific reason.

If additional projection is justified, implement it, test it, and report exactly which tables and records are covered. Do not claim completeness for tables or records that remain unprojected.

## 12. Scientific architecture and evidence integrity

Follow the existing architecture and approved ADRs.

Maintain the separation among:

- Source identity.
- Capability contracts and verified capability state.
- Source-specific adapters and query builders.
- Scientific Search Query / CSQ.
- Query compilation and orchestration.
- Result normalization.
- Evidence and provenance.
- LocalIndex and persistent scientific record storage.
- Catalog, onboarding, and governed application registration.

### CSQ semantics

CSQ remains the semantic authority for the scientific question.

Do not replace it with keyword-only search, silently drop unsupported constraints, or change the question's scientific meaning.

Source-specific query builders may translate supported portions of the query into provider-native requests, but unsupported constraints must be handled according to the existing contracts.

A keyword match alone is not sufficient evidence to support a scientific claim.

### Provenance and evidence

Preserve applicable:

- Stable source identity.
- Provider record ID.
- Original source URL.
- Provider-specific identifiers and metadata.
- Retrieval method and relevant timestamps when known.
- Licensing evidence.
- Evidence directness, claim relationships, and dispositions.
- Original source material where required.

Do not collapse distinct records from different sources simply because they look similar.

Do not lower confidence thresholds or evidence requirements to make integration appear successful.

---

# PART IV — WORKSTREAM B: UNIFIED ARCHIVE AND RETRIEVAL

## 13. Archive mission

Investigate, design, implement, test, and document a durable, general-purpose archive layer for mission and engineering artifacts.

The archive must support:

1. JSON files and artifacts.
2. AHO artifacts after their actual format and semantics have been discovered.
3. Cursor prompts, including master prompts, execution prompts, and corrective prompts.
4. Cursor execution reports.
5. Engineering, audit, test, and final reports.
6. Mission specifications and task records.
7. Architectural proposals and decision records.
8. Source manifests, reconciliation reports, readiness matrices, and owner-decision proposals.
9. Checkpoints and saved stopping points.
10. Other supported mission artifacts identified during inspection.

The archive must allow a future engineer or Cursor session to retrieve the correct original prompt, report, decision, version, and stopping point without depending exclusively on conversational memory.

**Do not assume that a general-purpose archive already exists. Verify the actual implementation.**

## 14. Discovery before archive implementation

Inspect:

- ADR-023 and applicable historical sections.
- Existing archive, artifact, document, mission, prompt, report, checkpoint, and retrieval code.
- Actual JSON and AHO schemas and representative files.
- Existing file storage abstractions and database contracts.
- Existing search/indexing services.
- Existing identifiers, content hashes, versioning, provenance, and idempotency patterns.
- Existing migrations, commands, tests, and operational workflows.
- Existing scientific record storage, Capability Store, IC Persistence, and ADR history to understand their actual boundaries.

Do not assume that any one of those existing systems is a universal artifact archive merely because it stores structured data.

Reuse compatible existing infrastructure where justified. Extend an existing archive rather than creating a competing system if it already satisfies the requirements.

Do not create duplicate registries, stores, indexes, or services unnecessarily.

## 15. Archive domain contract

Implement or extend a coherent archive model and service API using the project's existing naming and architecture conventions.

Names such as `MissionArtifactArchive`, `MissionArtifactRepository`, and `MissionArtifactRetrievalService` are conceptual examples only. Inspect the repository before selecting names.

The archive must support the following.

### 15.1 Stable identity

Every artifact must have a stable unique archive identifier independent of its file path.

### 15.2 Exact original preservation

Preserve the original bytes or exact original text.

Do not silently normalize line endings, reformat JSON, rewrite prompts, translate reports, or replace originals with summaries.

If an extracted representation or redacted derivative is produced, identify it as a derivative and link it to the original where safe and authorized.

### 15.3 Content integrity

Calculate and store a cryptographic content hash, such as SHA-256.

Provide a way to verify that the retrieved content matches the stored hash and detect tampering.

### 15.4 Metadata and classification

Record appropriate metadata:

- Artifact type and format.
- Title or searchable description.
- Original path or opaque source reference.
- Related mission/task identifier.
- Related source or seat where applicable.
- Capture/import method.
- Creation, capture, and ingestion timestamps when known.
- Version and supersession relationships.
- Relevant provenance and processing status.

Unknown values must remain unknown; do not fabricate them.

### 15.5 Version history

Store successive versions as separate auditable records.

Never erase a previous prompt, report, or decision when a new version supersedes it.

Distinguish:

- Original artifact.
- Revised artifact.
- Summary.
- Extracted metadata.
- Derived search index.
- Exported copy.

A summary is not the original source.

### 15.6 Relationships

Support traceable links among:

- Initiating prompts.
- Corrective prompts.
- Execution reports.
- Test evidence.
- Final reports.
- Architectural decisions.
- Owner-decision proposals.
- Source manifests.
- Checkpoints.
- Saved stopping points.

Do not collapse distinct artifacts into one record.

### 15.7 Idempotency

Repeated ingestion of the same artifact using the same ingestion identity must not create uncontrolled duplicates.

At the same time, preserve distinct artifacts when their provenance or archival identity legitimately differs, even if their content hashes are identical.

### 15.8 Durability

The archive must survive process restarts and be accessible from a new process.

Do not rely on in-memory arrays or ephemeral session state.

### 15.9 Search and retrieval

Support retrieval by:

- Stable archive ID.
- Artifact type and format.
- Mission/task.
- Related source or seat.
- Date where available.
- Version and supersession relationship.
- Related artifact.
- Searchable metadata.
- Relevant content for supported text-based formats.

Search results must provide enough metadata to identify the exact original and its version.

### 15.10 Safe import and export

Import existing artifacts without modifying the originals.

Provide a safe way to retrieve or export originals with their identifiers and integrity metadata.

Prevent path traversal and unintended overwrite.

### 15.11 Partial-failure handling

Interrupted ingestion must not mark an incomplete artifact as successfully archived.

Avoid inconsistent states between stored content and metadata. Use transactions or a recoverable staging process according to the chosen storage architecture.

## 16. Append-only historical continuity

The archive must preserve the cumulative history of prompts, reports, decisions, and stopping points.

Mandatory rules:

1. Never delete historical archive entries as a cleanup strategy.
2. Never overwrite an original prompt or report with a newer version.
3. Archive new versions as new entries with explicit relationships.
4. Mark superseded artifacts without deleting them.
5. Preserve original language and wording.
6. Do not invent missing conversations, prompts, timestamps, approvals, or provenance.
7. Keep summaries and extracted metadata separate from originals.
8. Preserve ADR-023's existing history.
9. Add a new dated, clearly scoped ADR section or a separate linked ADR according to existing conventions.
10. Do not rewrite an old decision to make it appear newly approved.
11. Do not automatically archive secrets or sensitive runtime configuration.

## 17. Controlled discovery and historical import

Implement a controlled archival workflow for existing repository artifacts.

Before importing:

1. Build an inventory of candidate files.
2. Classify them by type and format.
3. Identify source paths and provenance.
4. Detect duplicates and versions.
5. Identify sensitive files and excluded artifacts.
6. Record parse failures and unsupported formats.
7. Define idempotency and restart behavior.
8. Produce a reviewable manifest before bulk ingestion.

Do not indiscriminately archive the entire repository.

Do not delete, move, or rewrite source files as part of archival.

Import eligible files in a bounded, resumable manner. If full import cannot safely finish in one run, record completed coverage, checkpoint state, and remaining work.

Do not claim that all historical artifacts have been archived unless the inventory and ingestion manifest prove it.

## 18. JSON support

For JSON artifacts:

- Preserve the exact original content.
- Validate syntax where feasible.
- Extract searchable metadata separately.
- Record parse failures without discarding the original.
- Preserve schema/version indicators where present.
- Avoid silently normalizing or reserializing the original.
- Make the extracted representation traceable to the exact original and content hash.

## 19. AHO support

First determine what AHO means in this project.

Inspect actual AHO files, schemas, consumers, documentation, and existing architectural decisions.

Then:

- Implement format-specific extraction only when supported by the discovered schema.
- Preserve unknown fields in the original.
- Do not invent an AHO schema.
- Do not silently coerce invalid data.
- Record unsupported or malformed artifacts while retaining the original where safe.
- Add format-specific tests based on real representative files.

If the meaning or format of AHO cannot be established, complete the generic archive layer and report the exact unresolved dependency. Do not falsely claim full AHO support.

## 20. Cursor prompts and engineering reports

Preserve exact original wording and language for Cursor prompts and reports.

Where the data supports it, extract:

- Mission and task identifiers.
- Prompt/report type.
- Source names.
- Branch and commit/HEAD references.
- Report status.
- Test summaries.
- Referenced architectural decisions.
- Associated prompts and corrective prompts.
- Final report relationships.
- Stop-point metadata.

Keep extracted metadata distinct from original content.

Implement retrieval workflows for:

1. The latest valid report for a named mission.
2. The original prompt that initiated a task.
3. All associated corrective prompts and execution reports.
4. The latest saved stopping point and its supporting evidence.
5. Previous artifact versions and superseded decisions.
6. All artifacts associated with a source, task, or mission.
7. JSON and AHO artifacts by stable ID and metadata.
8. Integrity verification of a retrieved original.

Do not assume the latest file by modification time is necessarily the authoritative report. Use explicit version, status, provenance, and relationship rules where available.

## 21. Search indexing

Use an existing compatible search facility where possible.

If a full-text index is introduced:

- Make it rebuildable from the archived originals.
- Keep the archive as the authoritative content store.
- Preserve the link between indexed content and original archive ID/hash/version.
- Do not treat an index entry as the original artifact.
- Support incremental indexing and recovery after interrupted indexing.
- Avoid duplicate indexing on repeated ingestion.
- Ensure search returns the correct version and provenance.

Do not repurpose the scientific LocalIndex as the general archive index unless a verified architectural contract explicitly supports that use.

## 22. CLI, service API, and continuation workflows

Provide usable operations consistent with existing project conventions.

Possible operations include:

- Archive/import an artifact.
- Search artifacts.
- Show an artifact by stable ID.
- Retrieve the original content.
- Inspect artifact history.
- List artifacts for a mission/task/source.
- Verify content integrity.
- Export an original safely.
- Inspect import status and remaining checkpoints.
- Rebuild a derived index.

These names are examples, not a mandate to create redundant interfaces. Reuse existing interfaces where appropriate.

The result must be usable by future engineering work, not merely testable through private implementation details.

## 23. Persistence, migrations, and environment

Use the narrowest compatible durable design.

- Inspect existing persistence contracts and migration conventions.
- Use additive migrations where needed.
- Preserve existing data.
- Maintain transaction integrity.
- Avoid partial states between content and metadata.
- Use safe filesystem paths.
- Avoid loading arbitrarily large files fully into memory when streaming is appropriate.
- Keep indexing rebuildable.
- Do not change production database configuration automatically.
- Do not claim PostgreSQL readiness based on SQLite tests.
- Do not repurpose scientific records or capability storage for unrelated archive artifacts without explicit architectural justification.
- Avoid introducing new external dependencies unless necessary and justified.

If PostgreSQL is unavailable locally, use an already supported persistent local backend where possible and document the verification boundary.

## 24. Privacy and secret filtering

The archive must not become a new credential leakage surface.

Before importing:

- Identify `.env` files, private keys, tokens, credentials, and sensitive runtime configuration.
- Exclude secrets and unauthorized sensitive artifacts.
- Do not place credentials in extracted metadata, search indexes, logs, manifests, reports, or test output.
- Do not expose the saved BHL API key during discovery or ingestion.
- Do not copy credentials into the archive merely to preserve a reference.
- Where a prompt or report includes a secret, do not claim that a redacted copy is byte-identical to the original.
- Clearly distinguish safe redacted derivatives from exact originals.
- Prefer excluding secret-bearing originals over creating another copy of the secret.
- Do not print secret values while performing hash, file inventory, or classification operations.

Tests must verify that the archive process does not leak excluded secrets.

## 25. Archive acceptance tests

Add focused tests for:

1. Stable archive identity.
2. Exact original preservation.
3. SHA-256 verification.
4. Tampering detection.
5. Versioning and supersession without deletion.
6. Idempotent ingestion.
7. Distinct provenance for legitimately distinct artifacts with identical content.
8. Durability across a new process or independent repository instance.
9. Retrieval by ID, type, mission/task, and metadata.
10. Content search and correct version selection.
11. Relationships among prompts, reports, tests, and stopping points.
12. JSON extraction and malformed JSON handling.
13. AHO extraction using the actual discovered schema, if supported.
14. Safe export and path traversal protection.
15. Secret exclusion.
16. Interrupted/failed ingestion recovery.
17. Search-index rebuildability.
18. Preservation of original source files.
19. Migration safety and compatibility with existing persistence.
20. Regression protection for existing scientific search and source integrations.

Run the focused tests and relevant broader regression tests.

Record actual commands, exit codes, test counts, assertions, and outcomes. If a test cannot run, explain why and do not claim it passed.

## 26. Archive documentation and architectural record

Document:

- Archive architecture.
- Domain model and identifiers.
- Content and metadata storage.
- Original preservation and hashes.
- Versioning and supersession.
- Provenance and relationships.
- Search and retrieval.
- Import/export operations.
- Secret filtering.
- Recovery and index rebuild.
- Historical import coverage.
- Known limitations.
- Production and local verification boundaries.

Update ADR-023 or add a separate linked ADR according to the repository's conventions.

The record must distinguish:

- Previously existing and verified functionality.
- Newly implemented functionality.
- Actual test evidence.
- Architectural rationale.
- Deferred work.
- External dependencies.
- Owner decisions still required.

Do not convert an architectural proposal into an approved decision without the required approval.

---

# PART V — SHARED GOVERNANCE, TESTING, AND ACTIVATION

## 27. Governance gates

Investigate the actual requirements for:

- Onboarding approval.
- Capability verification (`CapVer`) and required verification seats.
- Source-specific licensing approval.
- Source registry and catalog registration.
- Governed application integration.
- Production persistence.
- Any other mandatory requirements in approved ADRs.

Verify the current status of these reported pending decisions:

- `OD-G1-02-ONBOARDING-ACTIVE`
- `OD-G6-20-ONBOARDING-ACTIVE`
- `OD-G1-01-ODS-ARTIFACT-AND-LICENSE`
- `OD-G1-17-FULL-PROJECTION`
- `OD-CAPVER-FOUR-SEATS`
- `OD-PGSQL-PRODUCTION-LOCALINDEX`

Do not assume these gates are still open without checking the current repository and evidence.

For every gate:

1. Find the authoritative requirement.
2. Identify exact acceptance criteria.
3. Verify which criteria are already satisfied.
4. Implement all engineering work that does not require owner approval.
5. Collect supporting evidence.
6. Record the remaining decision or external dependency precisely.

**Never fabricate owner approval, write false `VERIFIED` capability decisions, invent licensing approval, or change a source to `ACTIVE` merely to finish the task.**

A proposal is not an approval. A successful test is not a governance decision. A populated local index is not production readiness. An archived report is not proof that the reported work actually passed.

Complete independent work without waiting for owner decisions.

## 28. Shared test strategy

For the five-source workstream, cover as applicable:

- Adapter and contract tests.
- Query construction and compilation.
- CSQ semantic integrity.
- Result normalization and provenance.
- Persistent store and cross-process behavior.
- Ingestion idempotency.
- Failure isolation across sources.
- Registry, catalog, onboarding, and capability contracts.
- Governed application integration.
- Licensing and unsupported constraints.
- Existing GBIF regression behavior.
- BHL credential safety and live API behavior.

For the archive workstream, cover all applicable acceptance tests in Section 25.

Distinguish explicitly between:

1. Live provider/API verification.
2. Fixture-based unit tests.
3. Persistent local-store verification.
4. Governed application-path verification.
5. Production infrastructure verification.
6. Archive integrity and retrieval verification.
7. Historical artifact import coverage.

Never describe one category as proof of another.

Do not repeat expensive test suites unnecessarily. If code changes invalidate previous results, rerun the relevant tests.

If PHP or PostgreSQL is unavailable, do not pretend tests ran. Execute permissible alternatives and document the limitation.

---

# PART VI — EXECUTION METHOD

## 29. Work continuously through implementation

Execute this workflow without requesting confirmation between ordinary engineering steps.

### Stage A — Establish the baseline

Inspect the current checkout, branch, HEAD, worktree, latest reports, approved ADRs, source adapters, persistence, tests, archive-related code, and available local artifacts.

### Stage B — Build two concise internal gap maps

Create a source-by-source integration gap map and an archive implementation/import gap map.

Separate:

- Already complete and verified.
- Existing but not adequately integrated.
- Missing implementation.
- Missing tests.
- External dependencies.
- Governance-only gates.
- Unsafe or unsupported assumptions.

Do not present the maps as the final deliverable and stop.

### Stage C — Implement both workstreams

Reuse completed work and existing abstractions.

Make the smallest complete changes needed to close real gaps. Keep archive artifacts separate from scientific source records.

### Stage D — Execute and repair

Run focused tests and bounded live probes.

Repair defects introduced or exposed by the changes and rerun affected tests.

Do not repeatedly rerun unrelated suites.

### Stage E — Perform the final review

Inspect:

- Final diff.
- Source identity and duplicate registrations.
- Scientific semantics and provenance.
- Licensing and activation state.
- Archive IDs, hashes, version history, and relationships.
- Secret exposure.
- Accidental local-data additions.
- Migration safety.
- Worktree changes.
- Any regression in existing behavior.

### Stage F — Update documentation and architectural history

Document actual implementation, test evidence, remaining gates, and archive/import coverage. Preserve all historical content.

### Stage G — Deliver one comprehensive Arabic report

Do not send repetitive interim analyses. Do not describe planned work as completed work.

Continue until the maximum safe and authorized work has been completed.

---

# PART VII — FINAL REPORT REQUIREMENTS

## 30. Required Arabic report structure

Produce one comprehensive report with the following sections.

### 1. Executive summary

State what was actually completed, what remains incomplete, whether any source readiness status legitimately changed, and whether the unified archive was implemented or extended.

### 2. Five-source integration matrix

For each of the five sources, report:

- Previous and verified current status.
- Source identity and adapter.
- Capability and query integration.
- Governed application-path integration.
- Persistence and ingestion, where applicable.
- Provenance and licensing.
- Exact tests and results.
- Remaining technical blockers.
- Remaining owner decisions.
- Final legitimate readiness classification.

### 3. BHL live API evidence

Report:

- Whether the saved `BHL_API_KEY` was used successfully.
- Sanitized API operation and result count.
- Application-path integration findings.
- Identifier and provenance preservation.
- Failure-isolation evidence.
- Remaining blockers.

Never reveal the key itself.

### 4. AGRIS corpus and licensing

Report:

- Actual provider inventory and processed count.
- Checkpoint/cursor position.
- Whether the harvest is complete.
- Ingestion generations and record counts.
- Provider-specific license evidence.
- Remaining corpus, licensing, and governance limitations.

### 5. Dr. Duke reconciliation and projection

Report:

- Tables inspected.
- Tables projected.
- Actual record counts.
- Excluded or partially processed tables.
- Relationship quality and unmatched records.
- Semantic safeguards.
- Remaining work.

### 6. Persistence and production readiness

Separate:

- SQLite/local verification.
- Cross-process durability.
- Application integration.
- PostgreSQL/production verification.

Do not change production configuration and do not claim unverified production readiness.

### 7. Unified archive architecture

Explain the actual implemented design:

- Archive domain model and stable IDs.
- Original content storage.
- Metadata and provenance.
- Content hashes and integrity verification.
- Versioning and supersession.
- Relationships among prompts, reports, tests, decisions, and stop points.
- Search and retrieval.
- Import/export.
- Secret filtering.
- Durability and recovery.
- Relationship to existing persistence and scientific search.

Clearly distinguish existing functionality from newly implemented functionality.

### 8. Historical archive inventory and coverage

Provide a reviewable inventory summary with actual counts for:

- JSON artifacts discovered, eligible, archived, verified, excluded, and pending.
- AHO artifacts discovered, supported, archived, verified, unsupported, and pending.
- Cursor prompts discovered, archived, and linked to reports.
- Cursor reports discovered, archived, and verified.
- Engineering/test/audit reports discovered and archived.
- Mission records, checkpoints, and stopping points discovered and archived.
- Other artifact classes, if supported.

Include:

- Import manifest location.
- Checkpoint location.
- Duplicate and version handling.
- Exclusion reasons.
- Parse failures and unsupported formats.
- Any incomplete import and its exact remaining inventory.

Do not invent counts. If complete historical discovery was impossible, state the measured coverage and the precise limitation.

### 9. Retrieval demonstrations

Demonstrate actual retrieval results, using safe non-sensitive artifacts, for:

- Retrieving an original prompt by archive ID.
- Finding the latest report for a mission.
- Retrieving the history of a superseded artifact.
- Retrieving a stopping point and related evidence.
- Searching a JSON or AHO artifact where supported.
- Verifying an artifact's content hash.

Report exact commands or API operations and actual outcomes. Do not expose credentials or sensitive content.

### 10. Test results

List the actual commands, exit codes, test counts, assertion counts, and material results for both workstreams.

Separate newly executed tests from previous test results.

### 11. Changed files and generated artifacts

List changed, added, and generated files, with a brief explanation of each material code or documentation change.

Identify local datasets, SQLite databases, downloaded archives, manifests, checkpoints, and temporary files that must remain uncommitted.

### 12. Governance and owner decisions

List each unresolved source gate and exact owner action required.

Do not represent proposals as approvals.

### 13. Git and safety status

Report:

- Actual branch.
- Actual HEAD.
- Worktree status before and after.
- Preservation of pre-existing WIP.
- Confirmation that no commit, push, deployment, Docker, reset, or destructive cleanup occurred.

### 14. Final classification

Classify each source using the project's existing approved vocabulary, or clearly map to:

- `ACTIVE` — only when all applicable technical and governance requirements are satisfied.
- `TECHNICALLY_INTEGRATED / NOT_READY` — technical integration is demonstrated, but mandatory gates remain open.
- `PARTIALLY_INTEGRATED` — meaningful work is complete but required integration behavior remains missing.
- `BLOCKED` — a specific technical or external dependency prevents further completion.

Classify the archive separately:

- `IMPLEMENTED_AND_VERIFIED`
- `IMPLEMENTED_WITH_LIMITED_IMPORT`
- `PARTIALLY_IMPLEMENTED`
- `BLOCKED`

Use these labels only when supported by actual evidence. Explain any mapping to existing project vocabulary.

### 15. Exact remaining actions

Provide the smallest concrete set of remaining owner decisions, infrastructure actions, or technical tasks needed to complete the mission.

Do not ask me to repeat information already available in the repository or reports.

---

# FINAL EXECUTION DIRECTIVE

Start by inspecting the current checkout and the latest five-source report.

Then execute both workstreams:

1. Complete the maximum legitimate integration and activation readiness of GBIF, Organic Eprints, BHL, AGRIS, and Dr. Duke.
2. Implement or extend the unified archive for JSON, AHO, Cursor prompts, reports, and mission continuity.
3. Reuse existing work and valid evidence.
4. Use the saved BHL API key securely.
5. Preserve scientific identity, provenance, licensing, and approved governance gates.
6. Preserve original artifacts, versions, and historical records.
7. Execute and document real tests.
8. Review the diff and protect pre-existing work.
9. Deliver one comprehensive Arabic report.

Do not stop at planning. Do not repeat completed work without a reason. Do not fabricate approvals or results. Do not expose secrets. Do not commit or push.

**Complete the authorized engineering work, preserve the repository's existing history and uncommitted changes, and stop before commit/push for final review.**
