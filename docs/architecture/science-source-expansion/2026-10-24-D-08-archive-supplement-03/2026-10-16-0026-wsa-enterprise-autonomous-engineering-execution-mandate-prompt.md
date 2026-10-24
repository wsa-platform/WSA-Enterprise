<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16288). Message timestamp as recorded in the conversation: Friday, Oct 16, 2026, 12:26 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16289; it is archived once. -->

---
# WSA-Enterprise — Autonomous Engineering Execution Mandate
## Canonical Source Registry Consolidation + Durable Storage Validation + Scientific Search Integration
**Repository:** `wsa-platform/WSA-Enterprise`  
**Reported local path:** `G:\WSA-Enterprise`  
**Primary branch:** `phase-18-m18-ai-marketing-communications` — verify actual state before acting  
**Primary architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md` and all applicable approved ADRs  
**Required reporting language:** Arabic  
**Operating role:** Senior Software Engineer, Software Architect, Data Integrity Engineer, Test Engineer, and Security-Conscious Implementation Owner
---
# 1. Mission and Expected Outcome
Work autonomously as a senior software engineer. Execute the authorized work end-to-end, diagnose defects, implement solutions, run tests, and document actual results.
Do not stop at analysis, plans, proposals, comparison tables, or TODO lists when safe implementation is possible.
The mission contains three coordinated workstreams:
**Workstream A — Canonical Scientific Source Registry Consolidation**
- Consolidate the legacy 62-source registry and the current 109-source registry into one authoritative canonical registry.
- Identify and consolidate confirmed duplicates.
- Preserve genuinely distinct sources and their scientific identities.
- Preserve all original identifiers, evidence, licenses, capabilities, and architectural provenance.
- Establish enforceable rules that prevent duplicate registrations in the future.
**Workstream B — Durable Storage and Migration Review**
- Review the R7 PostgreSQL-backed scientific record store, its migration, schema constraints, service-provider wiring, and lifecycle behavior.
- Find and fix defects within the authorized scope.
- Verify migration behavior and application integration in an isolated, approved non-production environment.
- Preserve and reuse the existing storage implementation when it is suitable; do not create a second persistence system unnecessarily.
**Workstream C — Actual Scientific Search Integration**
- Prove that the application's ingestion and scientific retrieval paths use the intended persistent store and derived index.
- Verify identity, provenance, licensing, CSQ preservation, and error-state semantics end-to-end.
- Fix integration defects and run the relevant tests.
**Success means all three workstreams are implemented and verified to the extent allowed by the available environment and authorization.** Do not claim overall completion if a required gate remains blocked.
---
# 2. Broad Engineering Authority
You are authorized to perform safe, reversible engineering work without repeatedly asking the user for approval, including:
- Inspecting the repository, ADRs, contracts, source inventories, code, tests, migrations, and configuration.
- Tracing the complete ingestion, storage, indexing, and retrieval call paths.
- Implementing domain contracts, repositories, registry-resolution logic, persistence integration, and duplicate-prevention rules.
- Refactoring relevant code when required for correctness.
- Fixing defects discovered in the authorized scope.
- Adding focused tests, fixtures, reconciliation reports, and deterministic manifests.
- Running permitted unit, integration, feature, and static-analysis tests using available dependencies.
- Testing against an actual isolated PostgreSQL instance when the approved environment permits it.
- Updating ADR-023 append-only with decisions and verified results.
- Continuing independent work while a separate item is blocked.
- Choosing reasonable implementation details consistent with approved ADRs and existing project conventions.
Do not repeatedly ask for permission for ordinary coding, refactoring, or test decisions covered by this mandate.
However, this authority does not permit destructive operations, production migrations, source activation, external live probes, or prohibited Git operations.
Where an explicit architectural decision is necessary, prepare the evidence and proposed decision, continue independent work, and identify the exact remaining gate.
---
# 3. Mandatory Repository Protection
Before modifying files:
1. Verify repository root, current branch, HEAD, and working-tree state.
2. Inspect staged, unstaged, untracked, and ignored files using read-only commands.
3. Record pre-existing changes and protect them.
4. Inspect ADR-023, D-04, D-09, D-10.x, and all relevant approved decisions.
5. Inspect the actual 62-entry and 109-entry source registries.
6. Inspect the R7 storage implementation, migration, tests, and service-provider binding.
7. Establish a test baseline where practical.
8. Identify the actual status of every task rather than trusting historical summaries blindly.
Mandatory prohibitions:
- No Git staging.
- No commit or push.
- No pull, rebase, reset, branch switching, cherry-pick, or history rewriting.
- No destructive cleanup, `git clean`, or commands that discard existing work.
- No deletion of source artifacts or historical records merely to simplify consolidation.
- No dependency installation or lockfile modification without explicit authorization.
- No production database access, production migration, deployment, or production infrastructure change.
- No external live source requests or activation without separate authorization.
- No disclosure of secrets, passwords, tokens, or confidential environment values.
Docker is permitted only for an isolated local test environment under the user's revised authorization. Inspect existing containers and mounts before removing anything. Do not delete valuable volumes or existing data. Keep test credentials out of logs, avoid published ports unless strictly necessary, and do not connect test infrastructure to production.
---
# 4. Workstream A — Consolidate the 62 and 109 Source Registries
## 4.1 Review architectural governance first
Inspect the exact text of D-09 and all relevant ADR-023 sections.
Determine whether D-09 prohibits:
- Modifying the historical 62-entry registry.
- Creating a cross-reference mapping.
- Changing canonical membership.
- Or some combination of these.
The requested consolidation explicitly changes the previous approach of keeping the two registries separate. Document the necessary new architectural decision in ADR-023 append-only before changing canonical membership.
Do not silently override D-09. Record which rule is superseded, which historical protections remain in force, and why the new consolidation is justified.
Preserve the original 62-entry and 109-entry inventories as immutable historical inputs. The new canonical registry must be a separately identifiable artifact unless an approved architecture requires otherwise.
## 4.2 Inventory all entries
Read the actual source files and extract every entry from both registries.
Expected input counts:
- Legacy registry: 62 entries.
- Current registry: 109 entries.
- Combined initial input: 171 entries before any deduplication.
These are input counts, not a predetermined final unique-source count.
Build a deterministic inventory containing, where available:
- Original registry and original entry ID.
- Canonical source identity.
- Official name and aliases.
- Publisher or responsible organization.
- Official domain and canonical URL.
- Source family and access mechanism.
- Scientific scope and coverage.
- Licensing and access evidence.
- Capability contract and onboarding state.
- Existing ADR references and decision IDs.
- Evidence supporting or contradicting a candidate identity match.
- Known conflicts and unresolved fields.
Do not fabricate missing facts. Preserve unknown values as unknown.
## 4.3 Establish evidence-based identity resolution
Compare entries both across registries and within each registry.
Assign every candidate relationship an explicit classification:
- `CONFIRMED_SAME_SOURCE`
- `CONFIRMED_DISTINCT_SOURCES`
- `POSSIBLE_MATCH_REQUIRES_REVIEW`
- `INSUFFICIENT_IDENTITY_EVIDENCE`
Use authoritative identity evidence, publisher identity, official URLs, source documentation, and approved architectural decisions.
Names, abbreviations, topic overlap, and URL similarity alone are insufficient to prove identity.
Preserve intentionally distinct source seats and previously approved source identities, including the G1/G6 arrangements described in the current architecture. Do not merge those entries without evidence and a compatible approved decision.
Preserve the established treatment of:
- G3-15: removed with no replacement.
- G2-17: REMVT.
- G2-19: Animal Bioscience.
Do not resurrect removed entries as active sources merely because they appear in historical artifacts.
## 4.4 Build a complete reconciliation manifest
Create a machine-readable consolidation manifest that accounts for every original entry exactly once.
For each entry, record:
- Original registry and entry ID.
- Identity evidence.
- Candidate matches.
- Final canonical ID, if established.
- Relationship classification.
- Supporting evidence and rationale.
- Preserved historical identifiers.
- Licensing and capability conflicts.
- Disposition.
- Relevant ADR decision.
- Review state.
The manifest must account for all 171 input entries without silent omission.
Generate a reconciliation report showing:
- Input counts.
- Confirmed cross-registry duplicates.
- Confirmed duplicates within the legacy registry.
- Confirmed duplicates within the current registry.
- Confirmed distinct sources.
- Ambiguous and unresolved matches.
- Removed historical entries and their documented reasons.
- Final unique-source count only after identity decisions are supported.
- All decisions that still require owner review.
Do not force the final count to any predetermined number.
## 4.5 Implement one canonical registry
After the governing architectural decision is documented, implement the canonical registry and update relevant consumers.
Requirements:
- One authoritative canonical registry for current source membership.
- Stable canonical source IDs.
- Preserved historical IDs and provenance.
- Explicit aliases and historical identifiers.
- Separate identities for distinct sources.
- Explicit unresolved states for ambiguous matches.
- Independent licensing and capability evidence.
- Source activation remains separate from registry membership.
- No source becomes active merely because it was consolidated.
- No destructive loss of original registry content.
If an identity is ambiguous, preserve it as unresolved and prevent it from creating an arbitrary merge.
## 4.6 Enforce duplicate prevention permanently
Implement duplicate prevention at the appropriate domain, registration, and persistence boundaries.
At minimum:
- Canonical IDs must be unique.
- Definitive identity keys must be unique where appropriate.
- Conflicting registrations must be rejected or routed to explicit identity resolution.
- Re-registering an established source identity must be idempotent.
- Historical IDs and aliases must resolve to the intended canonical record without creating duplicate sources.
- Source names must not be used as the sole identity key.
- Concurrent registration must not create duplicate canonical records.
- Ambiguous matches must not be merged automatically.
- Identity merges must preserve provenance and an auditable decision trail.
Add tests proving these behaviors.
---
# 5. Workstream B — Review and Validate Durable Storage and Migration
## 5.1 Inspect the actual R7 implementation
Review the existing components and their real code, including the reported equivalents of:
- `DatabaseScientificRecordStore`
- `ScientificRecordStore`
- `InMemoryScientificRecordStore`
- `OpenScientificRecordGeneration`
- `ScientificRecordGenerationMerge`
- `ScientificRecordCodec`
- `LocalIndexScientificSourceAdapter`
- `LocalIndexIngestionService`
- `AgriculturalIntelligenceServiceProvider`
Inspect the actual migration:
`2026_10_15_220000_create_scientific_source_record_store_tables.php`
Verify the current filenames and implementation instead of assuming that the historical report is complete.
## 5.2 Review schema and data integrity
Review the three reported tables:
- `scientific_source_records`
- `scientific_source_states`
- `scientific_source_generations`
Verify:
- Stable identity and uniqueness constraints.
- Foreign-key or equivalent integrity constraints where applicable.
- CHECK constraints for lifecycle and generation consistency.
- Transaction boundaries.
- Concurrency and row-locking semantics.
- Generation advancement and stale-generation rejection.
- Tombstone and withdrawal behavior.
- Idempotent record updates.
- Snapshot completeness requirements.
- Content fingerprint verification.
- Recovery from partial or interrupted ingestion.
- Failure reporting and error classification.
- Compatibility with existing tenancy and authorization rules.
- Safe behavior when the schema is absent, incomplete, or incompatible.
Verify whether the authoritative records and derived index have clearly separated responsibilities.
Do not assume that the reported implementation is correct merely because its earlier tests passed. Fix concrete defects discovered by inspection or testing.
## 5.3 Review migration safety
Inspect migration creation, execution, and rollback behavior.
In an isolated non-production PostgreSQL database:
- Apply the migration to a fresh test database.
- Verify all expected tables, constraints, and indexes.
- Test migration status and repeatability.
- Test rollback and reapplication where supported and safe.
- Verify behavior if the application starts before the schema exists.
- Verify the service-provider binding resolves the intended persistent store.
- Verify the application does not silently fall back to in-memory storage when persistent storage is required.
- Verify errors are explicit when the required database or schema is unavailable.
Do not assume that rollback is safe if it destroys persisted records. Inspect the migration and protect test data before any rollback.
Do not apply the migration to production. If a deployment approval gate is required, prepare the migration and evidence, but leave deployment blocked.
## 5.4 Reuse the existing persistence architecture
The canonical registry should use the existing persistence mechanism if it can safely represent canonical source identities and aliases.
Do not create a second database or duplicate persistence system without a demonstrated architectural need.
If the source registry requires a distinct persistence contract, explain why and implement the smallest coherent design consistent with the existing architecture.
Any new schema requirement must be documented and tested in an approved isolated environment.
---
# 6. Workstream C — Prove Actual Scientific Search Integration
## 6.1 Trace the entire application path
Inspect and trace:
- `ScientificSearchQueryBuilder`
- `AgriculturalEntityCatalog`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- Source registry resolution.
- Capability and licensing enforcement.
- Source adapters and family adapters.
- `ScientificResultNormalizer`.
- Durable record storage and local indexing.
- Evidence evaluation, synthesis, and final answer composition.
Verify that the canonical registry, durable storage, and scientific retrieval path use consistent source identities.
Do not implement a disconnected canonical registry or a persistence layer that production search never uses.
## 6.2 Verify ingestion-to-search behavior
Using deterministic test fixtures and authorized isolated resources, demonstrate:
1. A canonical source identity resolves correctly.
2. Duplicate aliases resolve to the same canonical source.
3. An ambiguous identity does not silently resolve to an arbitrary source.
4. An eligible test record is ingested.
5. The record is persisted in PostgreSQL.
6. A fresh process or independent application instance can retrieve the record.
7. The derived index is populated or rebuilt from the authoritative persisted records.
8. Search returns the expected eligible evidence.
9. Provenance and original source identity survive the entire path.
10. Licensing restrictions remain effective.
11. Deleted or withdrawn records are excluded.
12. Storage and indexing failures are not represented as valid empty results.
13. Search does not mutate the authoritative store unexpectedly.
Use actual PostgreSQL for tests that claim PostgreSQL persistence or transaction guarantees.
Mocks may supplement tests but cannot replace required real-backend verification.
## 6.3 Preserve CSQ semantics
Preserve the Canonical Scientific Question as the authoritative scientific question contract.
Verify that:
- Source-specific translation does not redefine the question.
- Entity identity, relationships, constraints, and scientific intent remain intact.
- The compiler and adapters do not degrade the question to keyword-only search.
- Local indexing does not create a weaker semantic contract.
- Provenance survives normalization and retrieval.
- Directness, claim relation, and disposition remain separate as required.
- Confidence and final-display eligibility contracts remain unchanged unless an approved decision explicitly authorizes changes.
- `EMPTY`, `UNAVAILABLE`, and `FAILED` remain distinct.
Do not modify unrelated search semantics to simplify integration.
## 6.4 Source activation boundaries
Do not perform live network requests, external harvesting, or source activation under this mandate.
A source can be represented in the canonical registry and tested with deterministic fixtures without being operationally activated.
Any future live endpoint verification requires a separately authorized network gate and adequate identity, capability, and licensing evidence.
---
# 7. Comprehensive Test Requirements
Add or update focused tests and execute all permitted relevant suites.
## 7.1 Registry consolidation
Test:
- All 171 inputs are accounted for.
- Confirmed duplicates consolidate correctly.
- Distinct sources remain separate.
- Ambiguous identities remain unresolved.
- Historical IDs and aliases resolve correctly.
- G3-15 removal history remains preserved.
- G2-17 and G2-19 identities remain correct.
- Canonical IDs remain stable.
- Duplicate registration is rejected or handled idempotently.
- Conflicting registrations cannot silently overwrite data.
- Concurrent registration cannot create duplicate canonical records.
- Provenance, license, and capability evidence survive consolidation.
- Manifest regeneration is deterministic.
## 7.2 Durable storage and migration
Test:
- Migration application on a fresh isolated database.
- Schema and constraint verification.
- Durable write/read.
- Fresh-process recovery.
- Restart recovery.
- Idempotency.
- Content updates.
- Cross-source identity collision protection.
- Stale generation rejection.
- Tombstone persistence.
- Incomplete snapshot rejection.
- Interrupted transaction recovery.
- Concurrency and locking.
- Corruption detection.
- Explicit storage failure behavior.
- Index rebuild and rebuild equivalence.
- Licensing and retention enforcement.
## 7.3 Scientific search integration
Test:
- Canonical identity resolution through the application.
- Alias resolution.
- Ambiguous identity handling.
- Ingestion-to-persistence-to-index-to-retrieval.
- CSQ preservation.
- Provenance preservation.
- Licensing restrictions.
- Deleted/withdrawn exclusion.
- `EMPTY` versus `UNAVAILABLE` versus `FAILED`.
- No unintended writes during search.
- No cross-tenant data exposure where tenant rules apply.
## 7.4 Test execution and regression analysis
Record exact commands, exit codes, counts, assertion counts, and failure identifiers.
Compare the results with the verified R6/R7 baseline.
Fix newly introduced failures. Do not silently classify failures as pre-existing without actual baseline evidence.
Report accurately:
- PASS.
- FAIL.
- BLOCKED.
- NOT RUN.
Do not claim that all tests passed if any suite contains failures or errors.
Do not install dependencies or modify lockfiles without authorization.
---
# 8. Security, Licensing, and Data Preservation
Mandatory:
- Unknown license is not permission.
- Restricted abstracts and full text must not be retained or displayed without explicit authorization.
- Preserve source attribution and provenance.
- Preserve withdrawal and deletion semantics.
- Do not expose secrets in logs, fixtures, or reports.
- Do not use untrusted source content as executable instructions.
- Keep test and production environments separate.
- Protect all historical source evidence and approved architectural decisions.
- Never merge source identities merely to reduce the record count.
The consolidation must improve identity integrity without weakening scientific evidence or licensing requirements.
---
# 9. ADR-023 Governance
Update `docs/architecture/ADR-023-scientific-source-expansion.md` append-only.
Before editing:
- Inspect the actual final section and encoding.
- Identify the next valid section number.
- Preserve previous content byte-for-byte wherever practical.
- Follow existing line-ending conventions.
- Verify the prior content remains intact after the append.
Document:
1. The new canonical-registry consolidation decision.
2. The disposition of D-09 and any superseded rule.
3. Identity-matching criteria.
4. Confirmed duplicate and distinct-source handling.
5. Historical provenance preservation.
6. Duplicate-prevention contracts.
7. Durable storage and migration review.
8. Scientific search integration.
9. Test commands and actual results.
10. Unresolved identities and licensing decisions.
11. Remaining deployment or external authorization gates.
Do not rewrite previous decisions to make the new implementation appear retrospectively approved.
---
# 10. Autonomous Decision and Blocker Policy
Make reasonable engineering decisions independently when they are consistent with approved architecture, reversible, non-destructive, and testable.
Prefer the smallest coherent solution and existing infrastructure.
Do not repeatedly ask the user about ordinary implementation details.
If a true architectural decision is required:
- Prepare evidence and a concrete recommendation.
- Continue independent authorized work.
- Record the exact decision required.
- Do not cross the protected boundary until the required approval exists.
If a runtime or database environment is unavailable, execute all independent tests and clearly report what remains unverified.
Do not fabricate test results or substitute weaker tests while claiming equivalent verification.
---
# 11. Mandatory Final Arabic Report
Deliver one comprehensive Arabic report, not multiple overlapping reports.
Include:
1. Executive verdict.
2. Verified repository state, branch, HEAD, and protection results.
3. Inventory of the 62 and 109 source entries.
4. Complete reconciliation counts and final unique-source count, supported by evidence.
5. Confirmed duplicates, distinct sources, ambiguous identities, and unresolved decisions.
6. Canonical registry design and duplicate-prevention enforcement.
7. Files changed and implementation behavior.
8. Durable storage architecture and migration review.
9. Actual isolated PostgreSQL test evidence.
10. Actual ingestion-to-search integration evidence.
11. CSQ, provenance, licensing, and security verification.
12. Exact test commands, exit codes, counts, and regression comparison.
13. ADR-023 changes and the governing decision.
14. Gate statuses.
15. Remaining blockers and the smallest required action for each.
Assign each gate one status:
- `PASS`
- `PARTIAL`
- `BLOCKED`
- `FAIL`
- `NOT RUN`
The gates are:
A. Repository integrity.  
B. Registry reconciliation.  
C. Identity resolution and duplicate prevention.  
D. Durable storage and migration.  
E. Scientific search integration.  
F. Tests and regression.  
G. ADR-023 governance.
Do not mark a gate PASS based on code inspection alone when runtime behavior is required.
---
# 12. Final Operating Directive
Begin by verifying the actual repository state, both source registries, D-09, ADR-023, and the R7 storage implementation.
Then execute all safe authorized work across the three workstreams. Resolve concrete defects, add tests, and verify actual behavior.
**The required end state is one evidence-based canonical scientific source registry, permanent duplicate prevention, a validated durable storage/migration path, and demonstrated integration with scientific search.**
Do not stop at plans or prototypes when safe implementation is possible.
Do not merge uncertain identities, delete historical evidence, activate external sources, deploy production migrations, or fabricate verification results.
**No staging. No commit. No push. No destructive cleanup. Preserve all existing work.**
