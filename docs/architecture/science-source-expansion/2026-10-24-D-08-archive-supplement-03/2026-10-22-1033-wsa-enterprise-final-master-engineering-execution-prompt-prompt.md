<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 17887). Message timestamp as recorded in the conversation: Thursday, Oct 22, 2026, 10:33 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 17888, 17890; it is archived once. -->

---
# WSA-Enterprise — FINAL MASTER ENGINEERING EXECUTION PROMPT
## Five-Source Completion, Existing SQLite CapVer Verification, Governed AGRIS Expansion, and Pre-Commit Review
**Mission date:** 2026-10-22  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local repository:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Primary architectural authority:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Scope:** GBIF, Organic Eprints, BHL, AGRIS, and Dr. Duke  
**Execution mode:** Actual engineering, implementation, testing, evidence collection, and final pre-commit review  
**Mandatory stop:** Before staging, commit, push, deployment, or any other prohibited operation.
---
# 1. EXECUTION MANDATE
Act as the Lead Software Engineer, Software Architect, Scientific Search Integration Engineer, Data Ingestion Engineer, Capability Verification Engineer, Test Engineer, and Independent Pre-Commit Reviewer.
This is an execution assignment, not a request for another plan or another report-only audit.
You are authorized to perform the necessary, narrowly scoped engineering work to complete the five-source integration, correct demonstrated defects, verify capability persistence, improve permitted AGRIS coverage, validate real application-facing search, preserve the historical engineering record, and prepare a complete pre-commit report.
Do not ask for repeated general authorization. Do not repeat completed audits unless new evidence, a regression, or a specific unresolved requirement makes the work necessary.
Use the actual repository state, current ADR text, source code, tests, manifests, runtime configuration, and available evidence. Historical reports are useful evidence, but they do not substitute for verification of the current state.
Complete all independent work that can be performed safely. If a genuine external dependency or mandatory owner decision blocks a specific item, finish unrelated work and document the exact blocker and acceptance criteria.
**Do not claim that work passed, data was persisted, a source was activated, or a test ran unless actual evidence supports the claim.**
# 2. ABSOLUTE ARCHITECTURAL AND OPERATIONAL RESTRICTIONS
## 2.1 Existing SQLite and CapVer architecture
The project already has the required SQLite capability-persistence table and existing storage implementation.
**Mandatory instructions:**
1. Use the existing SQLite CapVer persistence table and its established repository, service, and contract.
2. Inspect and reuse the existing implementation. Do not create a competing persistence path.
3. Do not create a new SQLite table for CapVer.
4. Do not create a new table, schema, or migration to duplicate existing capability persistence.
5. Do not propose or implement a new SQLite migration for this task.
6. Do not move CapVer to PostgreSQL.
7. Do not connect to PostgreSQL or write CapVer records to PostgreSQL as part of this task.
8. Do not revive a previously cancelled PostgreSQL-based capability-persistence path.
9. Historical PostgreSQL rows, if any, are historical evidence only. They do not prove that the corresponding records are current in SQLite.
10. Execute the authorized CapVer workflow through the existing SQLite-backed implementation and verify the actual persisted state through the supported read path.
If the existing implementation reveals a concrete defect, repair the smallest relevant part of the existing code without creating a second table, schema, migration, or competing store.
Do not interpret uncertainty in an old report as evidence that the existing SQLite table is absent.
## 2.2 Scientific record store
Preserve the approved SQLite scientific record store under Option A / ADR-023 §8.62.
- Do not migrate the scientific record store to PostgreSQL.
- Do not create another scientific store.
- Do not rebuild or mutate the operational scientific database merely to make testing convenient.
- Do not run destructive experiments against the operational database.
- Use approved isolated copies for audits and tests that might otherwise mutate persistent state.
## 2.3 Git, Docker, and environment restrictions
The following operations are prohibited:
- `git add` / staging files.
- `git commit`.
- `git push`.
- Deployment.
- Docker or Docker-based execution.
- `git reset`.
- `git clean`.
- Destructive cleanup.
- Deleting or overwriting pre-existing work.
- Removing or rewriting historical ADR content.
- Unapproved production configuration changes.
- Printing, copying into reports, or exposing secrets.
- Destructive database migration or experimentation.
- Replacing unavailable services with fake production state.
Preserve all pre-existing working-tree changes, untracked files, local datasets, databases, and worktrees.
Do not stage files. Do not commit. Do not push.
The final stopping point is the completed pre-commit review with all changes available for owner review.
## 2.4 SQLite runtime failure — mandatory stop condition
If the approved host PHP runtime cannot access the existing SQLite capability-persistence table because of a verified environment limitation, such as a missing `pdo_sqlite` extension, file permissions, or an inaccessible approved path:
1. Capture the exact error and evidence identifying the cause.
2. Do not modify the existing database schema or create a table or migration.
3. Do not switch to PostgreSQL or write capability records to another store.
4. Do not enable Docker or Docker-based execution as a fallback.
5. Complete independent work that does not depend on the blocked operation.
6. Classify CapVer as `BLOCKED — ENVIRONMENT` until the existing approved SQLite path can be used.
7. Report the precise dependency and the smallest safe next action.
Do not claim capability persistence or successful readback when the operation could not be executed and verified.
# 3. REQUIRED SOURCE SCOPE
Complete the following five-source workstream:
| Scientific source | Seat | Integration type |
|---|---|---|
| GBIF | `G5-04` | REST |
| Organic Eprints | `G1-02` | OAI-PMH / LocalIndex |
| BHL | `G6-20` | REST |
| AGRIS | `G1-01` | Official ODS/bulk input / LocalIndex |
| Dr. Duke | `G1-17` | Static dataset / LocalIndex |
Keep source identities, seat identities, adapter identities, and existing registry keys consistent with the actual repository.
Terminology must remain consistent throughout implementation and reporting:
- **Source:** the scientific provider.
- **Seat:** the governed source identity in the capability model.
- **Source key:** the stable integration/registry key.
- **Adapter:** the code component integrating the provider.
- **Endpoint:** a specific URL or interface exposed by the provider.
- **Capability record:** a persisted governed capability result.
- **LocalIndex:** the existing local scientific search/indexing implementation.
Do not use “source,” “endpoint,” “adapter,” and “seat” interchangeably.
# 4. EXECUTION PRIORITIES
Execute the priorities in dependency-aware order. Independent work may run in parallel when safe and when it does not create conflicting edits.
## Priority 1 — Establish the real baseline
1. Verify repository root, branch, HEAD, upstream relationship, and `git status --short`.
2. Record existing tracked changes, untracked files, and relevant pre-existing WIP.
3. Inspect the latest relevant engineering report and applicable ADR-023 sections.
4. Reuse completed evidence where still valid.
5. Identify only the remaining implementation and verification gaps.
6. Inspect the current CapVer SQLite storage contract and existing table through the approved code path. Do not create database objects.
Do not assume the historical HEAD is current. Do not reset the repository to a previously reported commit.
## Priority 2 — Capability persistence and governance
For all five sources:
1. Verify the current source and seat identity.
2. Verify the existing capability contract and authorized workflow.
3. Verify the applicable onboarding decision and status.
4. Verify the existing CapVer persistence path.
5. Execute authorized CapVer using the existing SQLite table.
6. Verify persisted rows through the supported read path.
7. Verify record identity, status, freshness, and provenance.
8. Verify idempotency by repeating the appropriate safe operation and checking that no duplicate or unintended state change occurs.
9. Distinguish successful execution, persisted state, and verified readback.
10. Do not promote a source to a stronger governance state merely because its adapter or endpoint works.
Previously reported PostgreSQL rows for Organic Eprints, BHL, AGRIS, and Dr. Duke must not be treated as proof of current SQLite persistence. Re-establish the required state using the current approved SQLite workflow.
GBIF is explicitly included in the current task authorization. The earlier exclusion in ADR-023 §8.58.5 must not be treated as an unresolved ambiguity that automatically blocks the authorized scope.
Inspect the current ADR and append a dated, narrowly scoped amendment recording the authorization and its boundaries. Preserve the original section text. Use the next actually available ADR section number; do not assume that §8.63 is available.
The amendment must specify:
- The authority for the scoped GBIF CapVer action.
- The affected seat (`G5-04`).
- The precise superseded restriction and the scope of supersession.
- The evidence required to establish completion.
- The explicit limitations of the authorization.
Do not generalize this authorization to other seats or unrelated policy decisions.
If a genuinely independent legal, security, or licensing prohibition is discovered, stop only the affected operation, preserve the evidence, and report the exact issue. Do not use the previously identified GBIF exclusion itself as a circular reason to ignore the current explicit authorization.
## Priority 3 — Source integration and real application-facing search
Verify and repair the complete path for each source:
`Source registration → query construction → adapter/orchestrator → provider or LocalIndex → normalization → identity and provenance → governed result path`
Check:
- Source registry and capability/catalog consistency.
- Actual production wiring, not just class existence.
- CSQ identity and scientific-query semantics.
- `ScientificSearchQueryBuilder`.
- `ScientificQueryCompiler`.
- `MultiSourceScientificSearchOrchestrator`.
- Source-specific adapters.
- `ScientificResultNormalizer`.
- Stable result identity and deduplication.
- Provenance and source metadata.
- Error isolation and timeouts.
- Result visibility in the real application-facing search path.
- Regression behavior for other sources.
Preserve the approved D-04 contract: `ScientificSearchQueryBuilder` owns HTTP search text. `ScientificQueryCompiler` may add compilation metadata, including `scientific_query_compilation`, without replacing the HTTP query text. Do not report this separation as a defect unless current approved architecture establishes otherwise.
Do not replace scientific question identity with keyword-only search. Do not fabricate results, identifiers, citations, or provenance.
## Priority 4 — AGRIS coverage expansion
Determine whether further AGRIS expansion is technically necessary and legally permitted. Do not confuse a bounded integration milestone with full corpus completion.
### 4.1 Establish the authoritative inventory
Locate the actual official ODS artifact, provider inventory, current manifest, checkpoint, and existing ingestion implementation.
Use a supported read path or an isolated copy for audits. Do not run direct queries or destructive operations against the operational scientific database.
Measure and report:
- Total providers in the authoritative inventory.
- Providers already processed.
- Providers eligible for ingestion.
- Providers excluded by license or policy.
- Providers inaccessible or unavailable.
- Providers with failed processing.
- Providers remaining unprocessed.
- Current indexed record count.
- Generation and checkpoint state.
- Measurement timestamp and method.
Do not invent the denominator or infer provider coverage from record counts.
If the authoritative inventory cannot be found, or the license and access requirements cannot be established, do not fabricate coverage. Complete the independent work and report the exact blocker.
### 4.2 License and access restrictions
Only ingest provider material when the applicable license and access terms are verified. Apply the existing approved rule requiring `CC-BY-4.0` or another explicitly approved equivalent where applicable.
Do not generalize one provider’s license to other providers. Do not bypass provider-specific restrictions. Do not interpret access to an ODS artifact as blanket permission to ingest every underlying record.
Preserve provider-level provenance and license evidence.
### 4.3 Bounded and resumable batches
Use the existing supported ingestion command and checkpoint mechanism.
- Default batch size: **50 providers**.
- Maximum batch size: **200 providers**.
- Adjust batch size only through existing supported command parameters.
- Maximum: **30 batches per AGRIS expansion run**.
- Do not create a new ingestion mechanism merely to implement these limits.
- Do not restart a completed or partially completed harvest from zero.
- Preserve resumability and generation safety.
Stop AGRIS expansion if any of these conditions occurs:
1. Two consecutive batches fail with the same error class.
2. Five total batch failures occur during the run.
3. More than 20% of attempts fail after at least five attempts.
4. A generation conflict or checkpoint inconsistency threatens data integrity.
5. License or provider-access eligibility cannot be established.
For each failed batch, record the batch identifier, provider subset, error class, checkpoint/generation before and after, whether the operation is safe to retry, and the corrective action.
A retry is justified only when the failure mechanism and retry safety are understood.
### 4.4 Compare counts with the historical baseline
Use **87,984 active AGRIS records** as the historical comparison baseline from the prior report, not as an assumed current count.
- If the verified current count is lower, investigate before claiming expansion.
- If it is equal, do not claim that record coverage expanded.
- If it is higher, report the absolute delta, percentage delta where meaningful, generation, and explanation.
- Explain the effects of withdrawn, deleted, reclassified, or deduplicated records.
- Do not assume that an increase in records proves that all eligible providers were processed.
Classify the outcome as one of:
- `FULLY EXPANDED — ELIGIBLE INVENTORY PROCESSED`
- `EXPANDED WITH EXPLICIT EXTERNAL LIMITATIONS`
- `PARTIALLY EXPANDED — ENGINEERING WORK REMAINS`
- `BLOCKED — INVENTORY / LICENSE / ENVIRONMENT`
A percentage such as 95% is not a universal completion rule unless the current authoritative contract explicitly requires it. State the denominator and exclusions for every percentage reported.
## Priority 5 — Dr. Duke bounded projection and memory safety
Verify the existing projection, dataset structure, current ingestion/indexing behavior, and search integration.
Check:
- Existing dataset tables and relationships.
- `FARMACY` versus `FARMACY_NEW` distinctions.
- Taxonomic and chemical identifiers.
- Ambiguous and unmatched relationships.
- ETHNOBOT traditional-use semantics.
- Provenance and applicable licenses.
- Query visibility through LocalIndex and the application.
- Idempotency, generation safety, and resumability.
- Bounded-memory behavior.
Do not blindly merge schemas, fabricate relationships, or claim full dataset projection while only a subset has been processed.
Respect existing per-prefix memory/record caps, including the previously reported 60,000-record caps. Do not increase caps without a separate explicit authorization.
`G6-06` / `dr_duke_g6` remains out of scope unless the current authoritative ADR explicitly establishes a different owner-approved state. Do not invent an owner decision or silently enable this seat.
## Priority 6 — Archive preservation only; bulk migration is out of scope
Archive integrity and historical preservation are important, but bulk historical migration is not part of this five-source completion mission.
For this mission:
1. Preserve all existing archive content, manifests, checkpoints, historical reports, ADRs, prompts, and pre-existing working-tree changes.
2. Inspect the existing archive only as needed to verify evidence directly relevant to the five-source work.
3. Do not perform bulk migration of historical prompts, reports, conversations, ADRs, or other artifacts.
4. Do not start a repository-wide historical-artifact inventory or migration campaign.
5. Do not delete, rewrite, or retroactively supersede historical reports.
6. If a relevant archive defect directly blocks this mission, make only the smallest authorized repair needed to protect or verify the current task’s evidence. Document the defect and change.
7. Record any broader archive migration as a separate follow-up task, with its own inventory, scope, deduplication rules, safety controls, and acceptance criteria.
The report dated `2026-10-22` remains unchanged and valid for the scope it originally verified. The new report must accurately state which prior evidence it reuses and which findings it revalidates.
Do not claim that historical archive migration was completed during this mission.
# 5. BHL — SECURE CONFIGURATION AND TEST EXPECTATIONS
Use the existing secure `BHL_API_KEY` configuration path. Do not ask the owner to provide the key again if it is already configured.
Verify:
- Whether the key is available to the actual runtime.
- Live API behavior.
- Adapter and orchestrator behavior.
- Preservation of `PartID`, `PartUrl`, and provenance.
- Source registration and capability state.
- Error isolation and safe failure behavior.
- Actual application-facing search integration.
- Relevant license and terms evidence.
Never print, expose, or copy the API key into logs, reports, manifests, or archived artifacts. Do not rotate it automatically.
If the key is unavailable, still execute the meaningful safe integration path and report the limitation. Do not claim successful authenticated retrieval.
A Tier 2 test may verify the real BHL integration’s safe failure path when the key is unavailable. It does not establish successful live retrieval.
# 6. REQUIRED END-TO-END TESTING
For each of the five sources, seek the strongest safe test tier supported by the environment.
### Tier 1 — Full HTTP/application path
A real request passes through the actual application-facing HTTP path, orchestration, source integration, normalization, and returned result.
### Tier 2 — Real orchestrator path
The real source registration and orchestrator invoke the actual adapter and actual source interface or LocalIndex. Validate normalized results and provenance. This is the required minimum target for each source where its external dependency is available.
### Tier 3 — Controlled fixtures
Use fixtures or mocks only for unavailable external dependencies, while retaining the real source registration, orchestration, normalization, and relevant contracts. Clearly identify the mocked boundary.
### Tier 4 — Unit-only
Use unit tests where integration execution is not possible. Unit-only evidence must not be reported as end-to-end integration.
Requirements:
- Target Tier 2 for GBIF, Organic Eprints, BHL, AGRIS, and Dr. Duke.
- Achieve Tier 1 where practical and safe.
- For BHL without a usable key, verify the real integration’s safe failure path and mark successful authenticated retrieval as unverified.
- Use isolated test data where persistent mutation is possible.
- Run source-specific regression tests and relevant shared orchestration/normalization tests.
- Do not weaken assertions or remove tests to achieve a green result.
- Do not report a test as passed unless its command exited successfully and the result was inspected.
- Record exact test commands, counts, assertion totals if available, exit codes, and limitations.
## 6.1 Test runner selection
Inspect the available environment first.
- Prefer an available, supported host PHP/PHPUnit runner when it safely executes the relevant tests.
- Use another approved, supported runner only if it genuinely exercises the relevant contract.
- Docker is prohibited.
- Do not modify production credentials or rotate passwords to make a test runner work.
- If a required runtime or dependency is unavailable, record the exact blocker and run all independent permitted tests.
If host PHP cannot access the existing SQLite capability-persistence table because of a verified environment limitation, follow §2.4 exactly. Do not silently enable Docker or substitute PostgreSQL.
Never claim production readiness based solely on local tests.
## 6.2 Database safety regression guard
Preserve the existing `DatabaseSafety` and test bootstrap safeguards. Do not weaken them.
Where relevant, retain or verify tests that reject:
- Operational database paths.
- Path traversal such as `..`.
- Paths outside the approved test root.
- Non-empty connection URLs when prohibited by the existing contract.
- `:memory:` for tests that require durable persistence.
Run destructive-behavior tests only against isolated test data. Never test destructive behavior on a live database.
# 7. ADR-023 UPDATE REQUIREMENTS
Append narrowly scoped amendments to the end of `docs/architecture/ADR-023-scientific-source-expansion.md` when needed to document current authorized decisions and actual implementation.
Before choosing a section number, inspect the end of the file and identify the next available number.
Every amendment must contain:
- Section number.
- Date.
- Title.
- Status and scope.
- Authority.
- Rationale.
- Relevant evidence/report reference.
- Explicit constraints and exclusions.
Preserve all original ADR text. Do not rewrite old decisions to hide a conflict. Where the current authorization supersedes a prior decision only for a specific scope, document that limited supersession explicitly and retain the original historical decision.
Do not create a new ADR decision for a matter already resolved by an existing authoritative contract unless a real conflict or new authorization requires it.
In particular:
- Record the scoped GBIF authorization accurately.
- Preserve Option A / SQLite scientific-store architecture.
- Do not authorize a PostgreSQL migration.
- Do not create a CapVer table or migration.
- Do not declare `G6-06` approved without explicit evidence.
# 8. FAILURE HYPOTHESES AND BOUNDED REPAIR
For a demonstrated defect, formulate a distinct root-cause hypothesis before making a repair.
A distinct hypothesis must result in at least one of:
- A code change addressing a specific root cause.
- A test change that isolates a previously untested failure mechanism.
- A narrowly scoped configuration change that is explicitly authorized.
Repeating a command, adding logging alone, or rerunning the same test is not a distinct hypothesis.
Limit attempts to **three distinct hypotheses per defect**. If all fail, preserve the evidence, report what was ruled out, and continue independent work.
Do not make speculative broad refactors. Do not turn an isolated failure into an unrelated rewrite.
# 9. PRE-EXISTING WORK AND CHANGE ISOLATION
Before changing files:
1. Capture the actual Git baseline.
2. Identify pre-existing modifications and untracked files.
3. Determine which changes belong to this task.
4. Preserve existing WIP.
5. Avoid overwriting files with unrelated or unreviewed changes.
Do not use broad formatting or line-ending conversions that create unnecessary diffs.
If a report or ADR already exists, update it additively only where authorized. Do not create competing authoritative artifacts merely to avoid understanding the existing structure.
Keep changes small and coherent. If unrelated pre-existing modifications are present, do not include them in the task’s proposed commit breakdown unless they are independently reviewed and explicitly part of the authorized scope.
# 10. EXACTLY-ONCE AND IDEMPOTENCY SEMANTICS
Do not make unverifiable claims such as “the adapter ran exactly once over its lifetime.”
For source registration and CapVer, interpret exactly-once requirements in terms that can be verified:
- One logical catalog registration for each source key.
- No unintended duplicate registration entries.
- No unintended duplicate current capability records for the same identity/version contract.
- Repeated authorized operations return the project’s expected `ALREADY_CURRENT` or equivalent state when appropriate.
- Repeated ingestion does not duplicate logical records.
- Historical superseded records remain preserved where the architecture requires history.
Record the actual idempotency result and the supported evidence. Do not claim idempotency from a single successful execution.
# 11. PROPOSED COMMIT BREAKDOWN — PLAN ONLY
Prepare a sensible commit breakdown based on the actual final diff, but **do not stage, commit, or push anything**.
Possible logical groups, to be adjusted to the real changes:
1. `fix(research): enforce existing SQLite capability persistence`
2. `feat(research): authorize GBIF capability registration`
3. `feat(research): expand governed AGRIS coverage`
4. `fix(research): complete five-source search integration`
5. `docs(architecture): document five-source closure`
Do not create empty or artificial commits. If changes are too interdependent for these groups, propose a smaller, coherent grouping and explain why.
Do not include unrelated pre-existing changes in the proposed groups.
The final report must clearly state that the proposed commits were not created.
# 12. FINAL ACCEPTANCE MATRIX
Provide a matrix with one row per source and, at minimum, the following columns:
| Dimension | Required evidence |
|---|---|
| Identity | Source key, seat, and registry consistency |
| Scope | Approved and actually implemented scope |
| Capability contract | Existing authoritative contract verified |
| CapVer persistence | Existing SQLite table and supported persistence path |
| Readback | Persisted record independently verified |
| Idempotency | Repeat-operation result and duplicate check |
| Endpoint/provider | Actual interface and outcome, where applicable |
| Query semantics | CSQ preserved; builder/compiler behavior consistent |
| Adapter/orchestrator | Actual registered integration path |
| Normalization | Stable identity and provenance |
| LocalIndex | Applicable ingestion and query evidence |
| E2E tier | Highest tier actually achieved |
| License/access | Evidence and explicit restrictions |
| Data coverage | Counts, inventory, generation, and limitations |
| Tests | Commands, results, and exit codes |
| Activation state | Highest state supported by all applicable gates |
| Remaining work | Exact blocker, action, owner/dependency if known, and closure criteria |
Use the repository’s actual status vocabulary. Where useful, distinguish:
- `NOT_READY`
- `PARTIALLY_INTEGRATED`
- `TECHNICALLY_INTEGRATED`
- `ONBOARDED`
- `CAPABILITY_VERIFIED`
- `CAPABILITY_PERSISTED`
- `GOVERNED_REGISTERED`
- `ACTIVE`
- `PRODUCTION_READY`
- `BLOCKED`
Do not assume that all these labels are interchangeable or that they are the project’s canonical vocabulary. Map them to the actual repository contract.
Do not promote a source to a higher state because an earlier lifecycle step passed. A passing contract test is not proof of persistence; a registry entry is not proof of end-to-end search; a local test is not proof of production readiness.
# 13. COMPLETION CRITERIA
The mission is complete when:
1. All authorized independent engineering work is implemented.
2. The five sources have been assessed against their actual authoritative contracts.
3. Existing SQLite CapVer persistence is exercised and verified without creating a table or migration.
4. GBIF authorization is documented at the correct ADR location without rewriting historical content.
5. AGRIS expansion, if permitted, is bounded, resumable, license-aware, and accurately measured.
6. Dr. Duke projection remains within the approved bounded scope.
7. Each source has the strongest safe integration-test evidence available.
8. Existing sources and unrelated application paths have regression coverage.
9. Relevant archive evidence is preserved, with bulk historical migration explicitly deferred.
10. All changed files pass the applicable checks.
11. The final diff and working-tree state are reviewed.
12. No prohibited operation was performed.
If any criterion cannot be completed, finish all independent work and classify the remaining item precisely. Do not misrepresent partial completion as overall completion.
# 14. REQUIRED FINAL REPORT — ARABIC ONLY
Produce one comprehensive, evidence-backed report in Arabic. Do not send multiple competing analyses of the same execution.
Include:
1. **الملخص التنفيذي:** What was actually implemented and verified.
2. **خط الأساس:** Actual branch, HEAD, working-tree status, and pre-existing WIP protections.
3. **المصادر الخمسة:** Individual status and evidence for GBIF, Organic Eprints, BHL, AGRIS, and Dr. Duke.
4. **CapVer وSQLite:** Existing persistence mechanism used, actual records verified, readback, idempotency, and any precise remaining blocker. Explicitly confirm that no new CapVer table or migration was created.
5. **AGRIS:** Authoritative inventory, eligible/excluded/failed providers, batch counts, stop conditions, record-count comparison against 87,984, generation, license evidence, and coverage classification.
6. **Dr. Duke:** Projection coverage, memory bounds, relationship quality, and the reason `G6-06` remained excluded if applicable.
7. **البحث العلمي:** Real query/orchestration/normalization/application path and CSQ preservation.
8. **الاختبارات:** Exact commands, runner, results, counts, exit codes, E2E tier, and unexecuted tests with reasons.
9. **الأرشيف:** Confirm historical evidence was preserved and that bulk archive migration was not performed as part of this mission.
10. **ADR-023:** Exact sections added or changed and how historical decisions were preserved.
11. **مصفوفة القبول:** Completed source-by-source acceptance matrix.
12. **الفجوات المتبقية:** Each blocker, responsible dependency where known, next action, and objective closure criteria.
13. **مراجعة Git:** `git diff --check`, final status, reviewed diff, and confirmation that no pre-existing WIP was discarded.
14. **تقسيم الالتزامات المقترح:** Suggested logical commits only; explicitly confirm no staging, commit, or push occurred.
15. **نقطة التوقف:** Exact current state and safest next authorized step.
Label each material conclusion as one of:
- `CONFIRMED` — directly verified.
- `SUPPORTED` — supported by available evidence but not fully exercised.
- `NOT VERIFIED` — not established.
- `BLOCKED` — an explicit dependency prevents completion.
- `NOT APPLICABLE` — the requirement does not apply, with reason.
Keep observed facts separate from inferences and recommendations.
# 15. FINAL SAFETY CHECKLIST
Before returning the report, verify all of the following:
- [ ] Correct repository, branch, and current HEAD.
- [ ] Existing WIP preserved.
- [ ] Existing SQLite CapVer table reused.
- [ ] No new CapVer table or SQLite migration created or requested.
- [ ] No PostgreSQL CapVer write or PostgreSQL migration performed.
- [ ] Scientific LocalIndex remains on the approved SQLite architecture.
- [ ] GBIF authorization is documented with precise scope.
- [ ] No unsupported owner decision was invented.
- [ ] `G6-06` was not enabled without explicit authority.
- [ ] AGRIS expansion obeyed license, inventory, batch, and failure limits.
- [ ] Dr. Duke caps were not increased without authorization.
- [ ] BHL credentials were never exposed.
- [ ] E2E tiers accurately reflect what ran.
- [ ] Historical archive content and previous reports remain intact.
- [ ] No bulk archive migration was performed.
- [ ] No secrets, database files, or generated datasets were accidentally included in the diff.
- [ ] `git diff --check` was executed and its real result recorded.
- [ ] No Docker, reset, clean, destructive cleanup, staging, commit, push, or deployment occurred.
**Begin execution now. Do not return only a plan. Implement and verify the authorized work, preserve all existing changes, and stop at the final pre-commit review.**
