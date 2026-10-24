<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16143). Message timestamp as recorded in the conversation: Thursday, Oct 15, 2026, 9:42 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16144; it is archived once. -->

---
# WSA-Enterprise — Full Autonomous Engineering Execution Mandate
## End-to-End Implementation, Durable Persistence, Real Testing, Scientific Search Integration, and Strict Safety Controls
**Project:** `wsa-platform/WSA-Enterprise`  
**Reported local path:** `G:\WSA-Enterprise`  
**Architecture authority:** Approved ADRs, especially `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Current workstream:** Scientific Source Expansion — R1–R6 reconciliation and end-to-end implementation  
**Operating mode:** Autonomous senior software engineer, architect, test engineer, and security-conscious implementation owner  
**Required reporting language:** Arabic  
**Git policy:** No staging, committing, pushing, pulling, rebasing, resetting, branch switching, or history rewriting.
---
## 1. Mission: Finish the Authorized Work, Not Just Describe It
Act as a senior software engineer responsible for completing the Scientific Source Expansion workstream through real implementation and verification.
Do not stop at analysis, recommendations, architectural proposals, TODO lists, or code scaffolding when implementation can safely proceed.
Your mission is to:
1. Verify the actual repository state and approved architectural contracts.
2. Audit the reported R6 implementation and resolve the documented discrepancies.
3. Complete the source onboarding and reconciliation contracts for all 109 source entries.
4. Implement the missing source-family functionality that is authorized and can be completed safely.
5. Implement a **real durable persistent storage solution** for normalized scientific source records and their lifecycle state.
6. Integrate persistent storage with the local scientific index and scientific search pipeline.
7. Execute real persistence, lifecycle, recovery, consistency, and integration tests.
8. Preserve the scientific question's identity and meaning throughout compilation, source-specific translation, retrieval, normalization, evidence evaluation, and answer composition.
9. Enforce capability, licensing, provenance, tenant-isolation, and source-activation boundaries.
10. Update ADR-023 append-only with actual implementation decisions and test evidence.
11. Resolve all defects introduced by this work and run all permitted tests.
12. Deliver a comprehensive Arabic engineering report distinguishing verified completion, partial completion, blockers, and unverified claims.
**Definition of success:** The authorized implementation works in code and has been tested against observable behavior. A design document, in-memory prototype, mocked persistence layer, or passing unit tests alone does not establish end-to-end completion.
Do not claim that all 109 sources are operational merely because their onboarding contracts exist. Do not claim improved scientific accuracy or performance without measured evidence.
---
## 2. Broad Engineering Authority
You are authorized to act autonomously within the repository for reversible, non-destructive engineering work, including:
- Inspecting relevant code, ADRs, tests, configuration, dependency declarations, and existing storage/indexing mechanisms.
- Tracing call graphs and data flows.
- Implementing missing domain contracts and production code.
- Refactoring existing code when necessary to achieve correct, cohesive behavior.
- Fixing defects uncovered in the authorized scope.
- Adding and updating focused tests and fixtures.
- Running permitted local tests and static checks using already available tools.
- Implementing durable storage and integrating it with the existing application architecture.
- Implementing schema-independent persistence adapters or repositories when appropriate.
- Adding recovery, reconciliation, idempotency, integrity validation, and concurrency protections.
- Updating ADR-023 append-only with decisions, contracts, limitations, and verified results.
- Making safe engineering decisions when the approved architecture clearly determines the correct solution.
- Continuing through independent work instead of stopping whenever one item is blocked.
Do not repeatedly ask for approval for ordinary, reversible code changes already covered by this mandate.
However, broad engineering authority does **not** override the explicit safety boundaries below or grant permission to activate sources, modify production infrastructure, or perform prohibited Git operations.
If a required operation crosses a protected boundary, continue all independent authorized work and report the exact unresolved authorization requirement.
---
## 3. Mandatory Repository-State Verification
Before editing anything:
1. Confirm the repository root and current branch.
2. Record the current HEAD.
3. Inspect tracked, staged, unstaged, untracked, and ignored repository state using read-only Git commands.
4. Identify pre-existing modifications and untracked files.
5. Inspect relevant ADR-023 sections, approved architectural decisions, implementation files, tests, and configuration.
6. Establish which files are already modified and which files are genuinely new.
7. Identify the actual implementation state of R1–R6 rather than assuming the previous report is fully accurate.
8. Preserve the current working tree exactly except for authorized edits.
9. Establish a test baseline before modifying behavior wherever practical.
Do not treat the previously reported HEAD, branch, test results, or file counts as current facts until verified.
Never overwrite existing work merely because it appears unrelated or incomplete.
If the repository is dirty, protect every pre-existing change. Do not use destructive cleanup to obtain a clean baseline.
---
## 4. Absolute Safety and Git Contract
The following restrictions are mandatory:
- No `git add` or staging.
- No commit.
- No push or pull.
- No rebase, reset, cherry-pick, branch switch, or history rewriting.
- No deletion of files or directories.
- No `git clean`, `git restore`, or equivalent operation that could discard existing work.
- No overwriting or removing untracked or ignored files.
- No dependency installation or dependency/lockfile changes without explicit authorization.
- No production database access, production migrations, deployment, or infrastructure changes without the required authorization.
- No live external source probes, scraping, harvesting, API activation, or credential-dependent requests without explicit authorization.
- No printing, logging, or exposing secrets, tokens, passwords, private URLs, or sensitive environment values.
- No claiming that an operation succeeded unless its result was actually observed.
- No claiming tests passed unless they were executed and their results inspected.
Do not silently broaden the scope to unrelated modules.
When an action is prohibited, explain the precise boundary, continue independent work, and identify the smallest safe path to completion.
---
## 5. Reconstruct the Actual R6 State and Resolve All Reported Discrepancies
Inspect the latest R6 implementation and the actual artifacts. Verify the reported results instead of treating them as established facts.
Investigate and resolve these five discrepancies:
### 5.1 Source-family reconciliation
The reported candidate family counts were:
- REST_API: 13
- STATIC_DATASET: 19
- BULK_DATASET: 9
- RSS_ATOM: 3
- OAI_PMH: 2
These total 46, not 109.
Produce a reproducible reconciliation for all 109 entries. Determine whether the remaining 63 are unclassified, assigned to other families, excluded by an explicit rule, or affected by a defect.
Do not fabricate classifications. Every entry must have an explicit, explainable state.
### 5.2 Evidence terminology
Reconcile claims that some source identities are partially evidenced while the report also describes identity as unresolved for all entries.
Separate at least:
- Identity evidence.
- Access evidence.
- Licensing evidence.
- Capability evidence.
- Onboarding readiness.
- Operational activation status.
Use precise definitions and avoid treating partial evidence as verification.
### 5.3 Persistent-index blockers
Reconcile the reported 21 permanent-local-index blockers with the reported 30 static, bulk, and OAI-PMH candidates.
List the affected source IDs, applicable rules, and reasons. Establish which sources can proceed with the current implementation and which remain blocked by a genuine architectural or operational dependency.
### 5.4 Integrity warnings
Identify the five reported integrity warnings individually. For each warning, record:
- Source or component.
- Evidence.
- Root cause.
- Impact.
- Corrective action.
- Test or other verification evidence.
- Remaining limitation, if any.
### 5.5 Baseline test comparison
Compare the actual R5 and R6 test evidence using available JUnit reports, test discovery, failure/error identifiers, and relevant stack traces.
Do not establish “no regression” merely because failure messages appear similar. Normalize only demonstrably irrelevant differences, such as line numbers, and preserve substantive differences.
Clearly separate pre-existing failures from newly introduced failures.
Continue with all safe implementation work while resolving these discrepancies. Do not use the audit as a reason to stop before implementing the authorized work.
---
## 6. Complete the 109-Entry Source Onboarding Contract
Inspect and reconcile the approved source inventory and the current 109-entry registry.
For every entry:
1. Preserve its identity and decision provenance.
2. Preserve its source-specific access and licensing evidence.
3. Record the supported access family and implementation state.
4. Record capabilities, limitations, and unresolved requirements.
5. Record the readiness decision and its rationale.
6. Preserve independent entries and decision IDs where the architecture requires them.
7. Do not merge duplicate-looking entries unless an approved identity-resolution rule authorizes it.
8. Do not mark a source VERIFIED, READY, or ACTIVE without the required evidence.
9. Distinguish a technically implemented adapter from an externally verified source.
10. Generate deterministic, machine-readable reconciliation output and validate it against the canonical registry.
Do not delete or silently rewrite the legacy 62-entry register. Preserve all established provenance and reconcile the legacy inventory through explicit mappings or decisions.
Do not activate all 109 sources simply to make the inventory appear complete.
---
## 7. Mandatory Durable Persistent Storage Implementation
**This section is an explicit implementation requirement, not an optional architectural investigation.**
Implement a genuine persistent storage mechanism suitable for the application's scientific source records and local indexing lifecycle.
Do not finish this task with an in-memory store, a mock repository, a proposed schema, a JSON object held in process memory, or a document that merely recommends persistence.
### 7.1 Inspect existing storage before choosing a solution
Inspect the current application and infrastructure for reusable mechanisms, including:
- Existing database connections and supported database technology.
- Existing repository and persistence abstractions.
- Existing tenant and data-isolation contracts.
- Existing scientific record, provenance, and indexing models.
- Existing local or durable storage services.
- Existing transaction, locking, retry, and recovery patterns.
- Existing schema and migration conventions.
- Existing testing infrastructure.
Determine whether an existing database or persistence mechanism can satisfy the requirements without creating unnecessary infrastructure.
Do not assume that `knowledge_embeddings` is suitable merely because it already exists. Verify whether it can preserve source identity, source-native IDs, provenance, record versions, lifecycle state, and deletion/withdrawal evidence.
Do not add a new database or search engine merely because it seems convenient.
### 7.2 Select and document a concrete implementation
Choose the safest architecture that meets the requirements using the existing approved infrastructure where possible.
Implement the required production code, not just interfaces.
The implementation must clearly separate:
1. **Durable source-record storage:** authoritative normalized records and their provenance/lifecycle state.
2. **Search index:** a derived structure that supports retrieval and can be rebuilt from authoritative persisted records.
3. **Ingestion and reconciliation:** responsible for applying new, changed, missing, withdrawn, and deleted records safely.
4. **Scientific search integration:** responsible for retrieving indexed evidence without changing CSQ meaning.
Document the chosen storage architecture, the reasons for the choice, and its consistency and recovery guarantees in ADR-023.
### 7.3 Required durable record contract
Persist the fields required by the approved architecture, including as applicable:
- Stable source key and source-native record ID.
- Composite identity or an equivalent collision-resistant identity.
- Normalized scientific content.
- Source and record provenance.
- Source-specific identifiers and canonical URLs where supported.
- Licensing and display permissions.
- Record fingerprint or version.
- Ingestion/update timestamps.
- Source generation or snapshot identity where needed.
- Current lifecycle state.
- Deletion or withdrawal tombstones.
- Any additional metadata required to reproduce, validate, or rebuild the index.
Do not persist full text, abstracts, or other restricted content unless the applicable license and approved retention policy explicitly permit it.
Do not invent missing metadata. Represent unknown or unresolved values explicitly.
### 7.4 Required persistence behavior
Implement and verify all applicable behaviors:
- Save a new record.
- Retrieve a saved record by its stable identity.
- Update a record without creating duplicates.
- Enforce uniqueness and collision protection.
- Handle identical repeated ingestion idempotently.
- Detect content changes using a deterministic fingerprint or equivalent mechanism.
- Prevent stale records or older generations from overwriting newer valid data.
- Represent deletion and withdrawal using safe lifecycle semantics and tombstones where required.
- Prevent deleted or withdrawn records from appearing in search results.
- Reconcile complete snapshots without treating incomplete or failed snapshots as authoritative.
- Apply transactions or an equivalent consistency strategy when a multi-step update could otherwise leave partial state.
- Recover correctly after interruption or restart.
- Report failures explicitly instead of converting them into successful empty results.
- Preserve tenant isolation and existing authorization rules.
- Avoid cross-source identity collisions.
- Provide a deterministic path to rebuild the derived index from durable authoritative records.
A record must not be reported as durably saved until the selected persistence layer has confirmed the write according to its actual guarantees.
### 7.5 Durable storage must survive process restart
**A successful in-process write/read test is insufficient.**
Demonstrate that:
1. The application or test process writes representative records to the actual selected persistent backend.
2. The writing process or storage instance is closed.
3. A fresh process or genuinely independent storage instance reopens the same durable backend.
4. The saved records, identities, fingerprints, provenance, and lifecycle states are recovered correctly.
5. The index can be rebuilt from the persisted records.
6. The rebuilt index returns the expected eligible records and excludes deleted, withdrawn, or unauthorized records.
If the backend is a database, use a real isolated test database of the same supported database technology wherever the environment permits. Do not substitute SQLite for PostgreSQL and then claim PostgreSQL behavior was verified.
If the selected implementation uses durable files, use actual filesystem writes and fresh-instance/process recovery tests, including atomic-write and interrupted-update behavior where applicable.
Mocks may supplement these tests but cannot replace them.
### 7.6 Persistent storage and indexing integration
Wire the actual persistent store into ingestion, reconciliation, index maintenance, and the scientific retrieval path.
Do not implement persistence as a disconnected component that production search never uses.
Verify that:
- Successful ingestion produces durable records.
- Search consumes the intended index derived from persisted records.
- Restart does not silently erase the index or authoritative records.
- Index rebuild produces equivalent eligible retrieval results.
- Storage failure cannot be misrepresented as a valid empty search result.
- Incomplete ingestion cannot advance a snapshot or generation marker prematurely.
- Deletion and withdrawal propagate into search visibility.
- Licensing and display eligibility remain enforced at the appropriate boundaries.
- CSQ semantics remain intact through the integrated path.
Where the approved architecture requires a durable index as well as durable source records, implement and test both. If the index is intentionally rebuildable rather than durable, document and test that contract explicitly; do not confuse it with persistence of the authoritative records.
---
## 8. Persistent Storage Authorization Gate: Complete Everything That Can Be Completed Safely
The requirement to implement durable storage is mandatory. However, this mandate does not authorize prohibited production or infrastructure operations.
Apply the following decision process:
### Case A — Existing approved infrastructure is sufficient
If the existing approved database, filesystem, or persistence infrastructure can satisfy the requirements without prohibited changes:
- Implement the concrete storage mechanism.
- Integrate it with ingestion and search.
- Run the real persistence and restart-recovery tests.
- Fix implementation defects.
- Document the verified results.
Do not stop at a proposal.
### Case B — A schema change or migration is required
If persistent storage requires a new database table or a schema change:
1. Inspect existing schema conventions and migration practices.
2. Implement all schema-independent contracts, repositories, adapters, integration code, and tests that can safely be prepared.
3. Prepare the migration artifact only if repository policy permits authoring an unapplied migration.
4. Do not execute production migrations or modify production data.
5. Do not claim the persistent backend is operational until the required isolated real-backend tests have passed.
6. Identify the precise authorization needed to apply or test the migration in an approved environment.
Do not silently bypass the migration requirement or invent an existing table.
### Case C — New infrastructure is genuinely necessary
If the requirements cannot be met safely with existing infrastructure:
1. Prove why the existing mechanisms are insufficient.
2. Compare the smallest viable alternatives.
3. Identify operational, security, durability, and maintenance consequences.
4. Continue all independent code and test preparation.
5. Do not provision infrastructure, install new services, or alter deployment configuration without the required authorization.
6. State exactly which permission or environment is missing and what specific action it would authorize.
**Do not label a blocked design as a completed implementation.**
### Case D — The current environment cannot run the required backend
If the environment lacks the required database engine, PHP runtime, driver, or other necessary capability:
- Run all tests that are actually possible.
- Do not fabricate test results.
- Do not replace the required backend with a weaker substitute and claim equivalent validation.
- Record the exact missing runtime or infrastructure dependency.
- Provide the concrete test command and setup requirements for the approved environment.
- Mark the relevant completion criterion as BLOCKED or PARTIALLY VERIFIED.
Continue the rest of the authorized implementation.
---
## 9. Required Real Persistence Test Suite
Create a dedicated, executable test suite covering the concrete persistent implementation.
Use the project's existing testing conventions and avoid adding dependencies unless explicitly authorized.
At minimum, test the following scenarios where applicable:
| Test | Required observable result |
|---|---|
| Durable write/read | A saved record is recovered with the expected content and identity. |
| Restart recovery | A fresh process or independent instance reads records written before restart. |
| Idempotency | Re-ingesting an identical record does not create duplicates or unintended changes. |
| Content update | A changed record updates the correct identity and fingerprint. |
| Collision resistance | Two sources with the same native record ID remain separate records. |
| Stale generation | An older record or snapshot cannot overwrite newer valid data. |
| Tombstone persistence | Deletion/withdrawal state survives restart. |
| Search exclusion | Deleted, withdrawn, or otherwise ineligible records are not returned. |
| Incomplete snapshot | Failed or incomplete ingestion cannot cause false deletions or advance authoritative generation state. |
| Recovery after interruption | Partial work is rolled back or recovered according to the documented consistency contract. |
| Index rebuild | Rebuilding from durable records produces the expected searchable results. |
| Rebuild equivalence | Incremental indexing and a clean rebuild produce equivalent eligible results. |
| Licensing | Restricted content is not retained or displayed without the required permission. |
| Provenance | Source attribution and original identity survive persistence and index rebuild. |
| Tenant isolation | Records cannot cross tenant or authorization boundaries. |
| Failure reporting | Persistence/index failure is distinguishable from a valid empty result. |
| Concurrency | Competing updates do not silently corrupt data or resurrect stale records. |
Use real isolated resources for the tests that claim to validate database or filesystem guarantees.
Include tests for any additional invariants discovered during implementation.
Do not mark a test as passing based only on test discovery or code inspection. Execute it and inspect the actual result.
---
## 10. Source-Family Adapters and Capability Enforcement
Inspect the existing REST/JSON, static-dataset, bulk-dataset, OAI-PMH, and RSS/Atom implementation.
For each family:
1. Verify the actual implementation.
2. Implement missing behavior that is authorized and can be tested offline.
3. Preserve family-specific constraints and source identity.
4. Handle pagination, resumable checkpoints, and completeness where applicable.
5. Validate content before committing a snapshot or advancing a high-water mark.
6. Use explicit error states for unsupported or unavailable capabilities.
7. Prevent partial ingestion from masquerading as a complete snapshot.
8. Ensure adapters do not bypass capability decisions or licensing rules.
9. Ensure sources remain inactive unless separately authorized and properly verified.
For OAI-PMH, verify resumable harvesting, page/token handling, snapshot completeness, and safe checkpoint advancement.
For bulk and static datasets, verify file integrity, validation, complete-snapshot handling, idempotent re-ingestion, and safe replacement behavior.
For RSS/Atom, distinguish an implemented parser from a fully supported production source family.
Do not perform live harvesting or external probes without explicit authorization.
---
## 11. Preserve CSQ and Scientific Search Semantics
Preserving the Canonical Scientific Question (CSQ) is a mandatory invariant.
Inspect the end-to-end flow through:
- `ScientificSearchQueryBuilder`
- `AgriculturalEntityCatalog`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Source adapters and family adapters
- `ScientificResultNormalizer`
- Evidence evaluation, synthesis, and final answer composition
Verify that:
1. CSQ remains the authoritative representation of the scientific question.
2. Source-specific query translation does not redefine the scientific question.
3. Query compilation preserves relevant entities, relationships, constraints, time scope, and scientific intent.
4. Local indexing does not introduce a separate, weaker semantic contract.
5. All-term matching or other retrieval rules are explicit and tested rather than assumed to represent semantic equivalence.
6. Missing or unsupported capabilities produce explicit states.
7. A failed source, unavailable index, and valid zero-result search remain distinguishable.
8. Evidence identity and provenance survive normalization and persistence.
9. Directness, claim relation, and evidence disposition remain separate axes as required by the approved architecture.
10. Confidence and final-display eligibility rules remain unchanged unless a separately approved decision authorizes a change.
Do not weaken the scientific question into keyword-only search as a shortcut.
Do not introduce FAOSTAT-specific behavior into a generic local index unless the approved architecture explicitly requires it.
---
## 12. Licensing, Provenance, Privacy, and Security
Enforce the approved licensing and retention contract at ingestion, persistence, indexing, display, and export boundaries.
Requirements:
- Unknown licensing is not permission.
- Conditional licenses must be evaluated against the relevant conditions.
- Do not retain or display abstracts or full text without explicit permission.
- Keep attribution and source provenance intact.
- Respect withdrawal and deletion semantics.
- Do not store unnecessary personal or sensitive data.
- Preserve tenant isolation and existing authorization.
- Prevent untrusted source content from being interpreted as executable instructions.
- Avoid logging source credentials or confidential content.
- Do not expose secrets in reports, fixtures, test output, or ADRs.
- Do not activate a source merely because its adapter can parse the source format.
Tests must cover applicable licensing and authorization boundaries.
---
## 13. Test Strategy and Actual Verification
Use a staged verification process:
1. Run focused tests for every changed domain contract.
2. Run focused persistent storage tests against the real selected backend.
3. Run restart-recovery and index-rebuild tests.
4. Run source-family and reconciliation tests.
5. Run search integration and CSQ preservation tests.
6. Run the relevant Unit and Feature test suites where the environment supports them.
7. Run available static analysis, formatting checks, and relevant frontend tests where appropriate.
8. Compare failures with the verified pre-change baseline.
9. Fix new failures introduced by the implementation.
10. Repeat relevant tests after fixes.
Record exact commands, exit codes, test counts, assertion counts when available, and actual failure identifiers.
Never report “all tests passed” when a suite has failures or errors.
Clearly separate:
- Passed.
- Failed.
- Blocked by environment.
- Not run.
- Not applicable.
If a suite has pre-existing failures, demonstrate that conclusion with actual evidence rather than assertion.
Do not install dependencies, change lockfiles, or introduce infrastructure merely to make the test process work without the required authorization.
---
## 14. Performance and Retrieval Quality
Do not claim that persistence or indexing improves retrieval quality simply because it works.
Where valid fixtures and an appropriate baseline exist, measure:
- Record ingestion throughput.
- Persistence latency.
- Index update and rebuild time.
- Search latency.
- Memory/resource usage where measurable.
- Retrieval precision/recall or other approved relevance measures.
- Consistency between incremental updates and clean rebuilds.
Use reproducible test data and document methodology.
If no defensible relevance baseline exists, report the absence of evidence and avoid unsupported quality claims.
Do not run live external benchmarks or load tests against production sources without authorization.
---
## 15. ADR-023: Append-Only Architectural Record
Update:
`docs/architecture/ADR-023-scientific-source-expansion.md`
Follow the existing ADR format and append-only rules.
Before editing:
- Inspect the complete relevant document and its current final section.
- Verify its actual line endings and encoding.
- Identify the next valid section number from the actual file.
- Preserve all previous content byte-for-byte wherever possible.
Add a new section documenting the actual implementation, including:
- Reconciliation findings and resolved discrepancies.
- The chosen durable storage architecture.
- Why the chosen mechanism fits the existing infrastructure.
- The authoritative record-store versus derived-index boundary.
- Record identity and collision protection.
- Transaction and consistency guarantees.
- Restart recovery and index-rebuild contract.
- Tombstone and snapshot reconciliation semantics.
- Licensing and retention rules.
- Failure handling and capability boundaries.
- Test commands and actual outcomes.
- Remaining blockers and required external authorization.
Do not describe proposed behavior as implemented behavior.
Do not alter earlier approved decisions merely to make the new implementation easier. If a genuine architectural conflict exists, document it and identify the decision needed.
Validate that the old content remains intact and that the appended section is correctly encoded and formatted.
---
## 16. Autonomous Decision Policy
Make reasonable engineering decisions independently when they are:
- Consistent with approved ADRs.
- Reversible and non-destructive.
- Within the authorized scope.
- Testable using available resources.
- Not dependent on prohibited infrastructure or external access.
Prefer the smallest coherent implementation that satisfies the actual requirements.
Reuse existing infrastructure when it is demonstrably suitable. Do not introduce unnecessary abstractions, duplicate persistence systems, or parallel search engines.
When evidence is incomplete, represent uncertainty explicitly rather than guessing.
When an issue blocks one implementation path, investigate alternatives and continue independent work.
Do not repeatedly ask the user to choose between ordinary implementation details. Escalate only genuine architectural decisions or explicit authorization gates that cannot be resolved from the approved contracts.
Never resolve a blocker by weakening a requirement, falsifying a test, bypassing a capability decision, or silently changing an approved contract.
---
## 17. Required Completion Gates
The workstream is not complete merely because code has been written.
Evaluate each gate separately.
### Gate A — Repository integrity
- Existing work preserved.
- No prohibited Git operations performed.
- No unauthorized files deleted or overwritten.
- No unauthorized external or production operations performed.
### Gate B — Source reconciliation
- All 109 entries accounted for.
- Family-count discrepancy resolved.
- Evidence terminology unambiguous.
- Persistent-index blocker list explained.
- Five integrity warnings resolved or individually documented.
- R5/R6 baseline comparison supported by actual evidence.
### Gate C — Durable persistence
- Concrete persistent implementation exists.
- The production application path uses it.
- Records survive process restart.
- Identity, provenance, licensing, fingerprints, and lifecycle state survive recovery.
- Updates and ingestion are idempotent.
- Stale generations and incomplete snapshots are handled safely.
- Deletion and withdrawal are respected.
- Persistence failures are explicit.
- Index rebuild from authoritative persisted records is verified.
### Gate D — Real persistence tests
- Tests use the actual selected persistence technology for the guarantees they claim to verify.
- Fresh-process or equivalent independent-instance recovery is tested.
- Index rebuild and retrieval are tested.
- Failure and lifecycle cases are tested.
- Actual test results are recorded.
### Gate E — Scientific search integrity
- CSQ semantics preserved.
- Provenance survives ingestion, persistence, indexing, and retrieval.
- Capability and licensing gates are enforced.
- Empty, unavailable, failed, and unsupported outcomes are distinguishable.
- No unauthorized source activation occurred.
### Gate F — Regression and code quality
- Relevant focused tests executed.
- Relevant broader suites executed where permitted.
- Newly introduced failures fixed.
- Remaining failures compared with the actual baseline.
- Available static checks reported honestly.
### Gate G — Architecture documentation
- ADR-023 updated append-only.
- Previous approved content preserved.
- Implementation decisions and test evidence documented.
- Unresolved blockers clearly identified.
For each gate, assign exactly one status:
- **PASS** — required behavior implemented and supported by actual verification.
- **PARTIAL** — some requirements implemented, but verification or functionality remains incomplete.
- **BLOCKED** — a specific external authorization or environment requirement prevents completion.
- **FAIL** — an implemented requirement demonstrably violates its contract.
- **NOT RUN** — required verification has not been executed.
Do not assign PASS based on code inspection alone when runtime behavior is required.
---
## 18. Final Review Before Reporting
Before delivering the report:
1. Inspect the complete diff.
2. Confirm every changed file belongs to the authorized work.
3. Verify that no pre-existing modifications were accidentally removed.
4. Verify that no file was deleted.
5. Verify that no Git staging or history operation occurred.
6. Recheck ADR-023 append-only integrity.
7. Recheck persistent storage and search integration.
8. Recheck the actual persistence-test results.
9. Confirm whether restart recovery was truly tested.
10. Confirm whether a real database or filesystem backend was used.
11. Identify any remaining discrepancy between the implementation and the report.
12. Make no unsupported claim about source activation, production readiness, performance, or scientific accuracy.
Do not stage, commit, or push.
---
## 19. Mandatory Final Arabic Engineering Report
Deliver one comprehensive Arabic report rather than multiple overlapping analysis messages.
The report must contain:
1. **Executive verdict:** what is genuinely complete and what is not.
2. **Verified repository state:** branch, HEAD, and working-tree protection results.
3. **R6 reconciliation:** all 109 entries, family counts, evidence categories, and the five discrepancies.
4. **Implementation inventory:** files changed and the behavior implemented in each logical component.
5. **Durable storage architecture:** selected backend, authoritative store, derived index, identity strategy, transactions/consistency, tombstones, and recovery behavior.
6. **Actual persistence proof:** exact test commands, actual backend used, restart/recovery evidence, and results.
7. **Search integration:** proof that the application retrieval path uses persisted records and that CSQ semantics remain intact.
8. **Source-family status:** implemented behavior versus verified external source readiness.
9. **Test results:** exact commands, exit codes, counts, failures, and baseline comparisons.
10. **Security and licensing:** verified enforcement and remaining risks.
11. **ADR-023 changes:** appended section number and the decisions recorded.
12. **Gate status table:** PASS / PARTIAL / BLOCKED / FAIL / NOT RUN for Gates A–G.
13. **Remaining blockers:** exact cause, evidence, impact, and the smallest action required to resolve each blocker.
14. **Next authorized action:** only if a real unresolved blocker remains.
Clearly distinguish code that was implemented, tests that actually ran, tests that passed, and behavior that remains unverified.
Do not claim completion when durable persistence is only proposed, mocked, or tested solely in process memory.
Do not conceal a blocked real-backend test behind passing unit tests.
---
## 20. Final Operating Directive
Begin by verifying the repository, current ADR-023, and the actual R6 implementation.
Then proceed autonomously through the authorized work in the most efficient safe order.
**Implement the durable persistent store, connect it to ingestion and scientific search, and test its behavior against the actual selected backend. Do not stop after creating a design, interface, or in-memory prototype.**
Resolve implementation defects, run all permitted tests, preserve existing work, and document actual evidence.
If a protected database migration, new infrastructure, production action, or external source operation is required, complete everything independent of that boundary and report the exact authorization needed. Never falsely label that blocked portion as complete.
**No staging. No commit. No push. No destructive cleanup. No fabricated test results. Preserve all existing work.**
The final deliverable is a verified implementation and a single comprehensive Arabic report—not merely a plan for someone else to implement.
