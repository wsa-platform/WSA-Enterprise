<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16526). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 4:00 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16527, 16530; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION PROMPT
# Scientific Source Expansion: Identity Verification, Blocker Resolution & Retrieval Quality Engineering
**Repository:** `wsa-platform/WSA-Enterprise`  
**Local repository path:** `G:\WSA-Enterprise`  
**Primary architectural record:** `docs/architecture/ADR-023-scientific-source-expansion.md`  
**Mission:** Execute the three source-expansion workstreams as one coordinated engineering mission, with broad implementation authority inside the defined scope, complete preservation of prior work, evidence-based verification, and a comprehensive Arabic final report.
---
# 1. ROLE AND LEVEL OF AUTHORITY
Act as the principal software engineer, senior scientific-search architect, research-data integration engineer, test engineer, and technical investigator responsible for completing the authorized Scientific Source Expansion work.
You are expected to perform actual repository work, not merely recommend a plan.
Within the authorized scope, you may independently:
- Investigate existing architecture, source inventories, registries, contracts, adapters, query builders, retrieval orchestration, normalization, provenance, evidence handling, evaluation tools, and tests.
- Design and document contracts, data models, interfaces, capability definitions, source identity records, query projections, verification procedures, evaluation protocols, and implementation plans.
- Write and modify production code and tests when necessary and when permitted by the applicable architectural gates.
- Implement approved source-verification tooling, capability assessment, source-specific retrieval evaluation, performance instrumentation, and reporting.
- Refactor code narrowly when required for the authorized objectives, provided existing contracts and protected work remain intact.
- Run all permitted tests, static analysis, type checks, linting, and deterministic evaluation scripts available in the existing environment.
- Diagnose failures, fix defects introduced by your authorized changes, and rerun the relevant checks.
- Update ADR-023 and related documentation with factual execution records, evidence, limitations, and decisions that are already authorized.
- Make reasonable implementation decisions without asking the owner about every routine engineering detail.
- Continue independent workstreams when another workstream is blocked, rather than stopping the entire mission.
Do not return a plan as the final deliverable if implementation and verification are possible.
**Authority boundary:** You have broad engineering authority within this mission. You do not have authority to override an existing owner decision, silently change the meaning of a scientific contract, activate sources without authorization, or mark an unverified gate as passed.
---
# 2. MISSION OBJECTIVES — ALL THREE WORKSTREAMS
Execute these three workstreams as one coordinated mission. Work may proceed in parallel where dependencies permit, but preserve explicit dependencies and gate conditions.
## Workstream A — Verify the 17 Eligible Source Seats
The previously reported inventory identifies 17 seats as candidates eligible for the next verification gate. Reconfirm that count and the exact identities from the current authoritative records before acting. Do not invent or reconstruct their identities from memory.
For every eligible seat:
1. Establish the exact source identity.
2. Identify the canonical source name, official domain, responsible organization or publisher, source type, and authoritative identity evidence.
3. Distinguish source identity from its provider, API, publisher, journal, database, portal, aggregator, and dataset.
4. Identify the official machine-access interface, if one exists.
5. Record whether the interface is publicly documented, requires registration, requires credentials, requires owner authorization, or has restrictions.
6. Verify licensing, terms of use, attribution, redistribution, storage, caching, and applicable rate limits.
7. Identify supported query mechanisms, filters, facets, result formats, pagination, language coverage, and relevant metadata.
8. Design and implement a controlled connectivity verification procedure when authorized.
9. Execute a connectivity test only when the endpoint, credentials, access method, and network use are authorized.
10. Record actual results, including timestamp, HTTP or protocol status when available, response format, errors, rate-limit behavior, and provenance.
11. Classify the outcome using explicit states such as:
   - `IDENTITY_UNVERIFIED`
   - `IDENTITY_VERIFIED`
   - `ACCESS_UNDOCUMENTED`
   - `AUTHORIZATION_REQUIRED`
   - `CONNECTIVITY_NOT_TESTED`
   - `CONNECTIVITY_PASSED`
   - `CONNECTIVITY_FAILED`
   - `LICENSE_REVIEW_REQUIRED`
   - `ELIGIBLE_FOR_NEXT_GATE`
   - `ACTIVATION_NOT_AUTHORIZED`
12. Preserve the distinction between evidence gathered, identity verified, connectivity tested, capability confirmed, and source activation approved.
These are proposed operational labels, not permission to change an existing canonical enum. Reuse established project enums when available; otherwise document and implement a compatible contract within the authorized scope.
### Required output
Produce a seat-by-seat verification matrix with:
- Seat ID and group.
- Canonical identity and source classification.
- Official identity evidence.
- Official access documentation.
- License and usage restrictions.
- Declared capabilities.
- Tested capabilities.
- Connectivity test status and evidence.
- Unresolved risks.
- Next required decision.
- Activation status, which must remain unchanged unless separately authorized.
Do not treat a successful HTTP response as proof that a source returns scientifically appropriate evidence.
## Workstream B — Resolve the Remaining Identity, License, Feed, and Inventory Blockers
Investigate and resolve all blockers that can be resolved within the existing architectural decisions and available evidence.
### B1. The 16 ambiguous identities
1. Retrieve the exact list of 16 ambiguous identities from the authoritative inventory or prior investigation.
2. Preserve each original identifier and its history.
3. Investigate official sources and authoritative documentation.
4. Distinguish aliases, duplicate representations, publishers, repositories, aggregators, datasets, and independent scientific sources.
5. Record the evidence supporting each resolution.
6. Resolve an identity only when evidence supports the conclusion.
7. If multiple interpretations remain plausible, retain the ambiguity and record the precise owner decision required.
Never fabricate a canonical identity or merge entries merely because their names are similar.
### B2. Licensing and permitted usage
For each affected source, establish, where evidence permits:
- Governing license and version.
- API or website terms of use.
- Attribution obligations.
- Restrictions on bulk downloads, redistribution, caching, and commercial use.
- Authentication requirements and rate limits.
- Whether full-text storage, metadata storage, and derived evidence have different conditions.
- Whether the source is suitable for the intended deployment and usage model.
Distinguish verified license text from assumptions, summaries, and unresolved legal questions.
Do not treat an undocumented or unclear license as unrestricted permission. Record legal-review requirements where necessary.
### B3. RSS/Atom decision
Investigate RSS/Atom as a possible discovery or update mechanism without assuming that feeds are a substitute for an official API or primary scientific evidence.
Assess:
- Official feed availability.
- Feed ownership and canonical identity.
- Update frequency and observed freshness.
- Metadata quality and stable identifiers.
- Pagination, retention, and historical coverage.
- Duplicate handling and incremental updates.
- Licensing and access conditions.
- Relationship between feed entries and the source's canonical records.
- Whether the feed supports discovery only, retrieval, or both.
Document a source-specific decision: use, do not use, investigate further, or owner decision required.
Do not activate feed ingestion merely because a feed URL exists.
### B4. Historical 62-source inventory versus the current 109-seat inventory
The authoritative current inventory baseline is:
- G1 — 22 seats
- G2 — 25 seats
- G3 — 14 seats
- G4 — 7 seats
- G5 — 19 seats
- G6 — 22 seats
- **Current total: 109 seats**
The historical 62-source record is a separate historical/governance layer. Do not silently reconcile it with the current 109-seat inventory.
Preserve the following decisions:
- `G3-15` is removed; no replacement is authorized.
- `G2-17` is `REMVT`.
- `G2-19` is Animal Bioscience.
- Repeated G1/G6 memberships remain separate seat memberships unless an explicit decision changes them.
- Do not silently deduplicate entries.
- Do not infer that 109 seats represent 109 unique organizations or endpoints.
Create an auditable mapping between the historical 62 records and current seats, using only supported relationships such as:
- Exact identity match.
- Confirmed successor or replacement.
- Alias or alternate representation.
- One historical record mapped to multiple current seats.
- Multiple historical records mapped to one current identity, if evidence supports it.
- Historical-only record.
- Current-only seat.
- Ambiguous or unresolved relationship.
Every mapping must include evidence, confidence or certainty classification, rationale, and resolution status.
Do not change the authoritative inventory count simply to make the two lists reconcile.
### Required output
Produce an issue register covering all four blocker categories, with a unique ID, evidence, severity, impact, proposed resolution, implementation status, owner decision required, and closure criteria.
Resolve what is evidence-resolvable and authorized. Do not manufacture closure for legal or governance questions requiring the owner's decision.
## Workstream C — Retrieval Quality, Performance, and Scalability Evaluation
Build an evidence-based evaluation system before increasing source activation or production scope.
### C1. Establish a scientific retrieval benchmark
Investigate existing evaluation datasets and tests first. Reuse them where valid; do not overwrite or silently re-baseline existing expectations.
Design a benchmark containing representative agricultural and scientific questions across the supported domain.
Include, where applicable:
- Plant and crop identity.
- Animal, livestock, poultry, and fish identity.
- Cultivar, breed, strain, species, and life stage.
- Disease and pathogen distinctions.
- Treatment, dose, duration, comparator, and outcome.
- Units, measurement, environment, geography, and study context.
- Scientific question types, evidence types, and language variants.
- Questions with multiple required constraints.
- Questions for which sources lack the requested capability.
- Negative cases where similar terminology must not count as an identity match.
Use approved reference answers or expert-validated relevance judgments when available. If gold-standard judgments do not exist, explicitly label the benchmark as provisional and define the review process needed for approval.
Do not fabricate ground truth or label model-generated judgments as expert-verified.
### C2. Preserve the scientific identity of questions and answers
The Canonical Scientific Query (CSQ) remains the semantic authority.
The original user question must remain available unchanged for provenance and auditability.
A source-specific query is a projection or encoding of CSQ, never a replacement for the original scientific meaning.
Maintain the intended architecture:
User Question → CSQ → Source Identity → Capability Matching → Source-Specific Query Projection → Retrieval → Normalization → Identity and Provenance → Scientific Evidence → Answer Synthesis.
Preserve the distinctions between:
- Question identity.
- Retrieval expression.
- Source capability.
- Evidence identity.
- Answer identity.
Do not silently broaden taxa, replace a cultivar with its parent species, substitute a pathogen for a disease, discard unsupported facets, or turn related terminology into identity-equivalent terminology.
Reuse the existing `ScientificSearchQueryBuilder` as the scholarly lexical/query-construction authority. Do not turn it into the registry or source-specific projection layer.
Reuse `AgriculturalEntityCatalog` for its established lexical and semantic responsibilities. Do not turn it into a source registry, capability registry, or source identity matcher.
Unsupported facets must be explicitly represented as limitations. They must not silently disappear from the CSQ.
### C3. Measure retrieval quality
Implement or extend repeatable evaluation metrics appropriate to the available ground truth, such as:
- Precision@k.
- Recall@k.
- MRR.
- nDCG@k.
- Correct source identity and provenance.
- Facet/constraint preservation.
- Scientific relevance and answer alignment.
- Duplicate-result rate.
- Stale-result rate when timestamps are available.
- Coverage of requested source capabilities.
- Unsupported-facet handling.
- Rate of empty, failed, or malformed responses.
Define each metric and its calculation. Do not report metrics for which the necessary judgments or data are absent.
Report results per source and query class where sample sizes permit. Avoid hiding weak sources inside aggregate averages.
### C4. Measure performance
Measure, where the existing environment permits:
- Query planning and CSQ construction latency.
- Per-source request latency.
- End-to-end retrieval latency.
- Time to first result.
- Throughput under controlled concurrency.
- Timeout and failure rates.
- Rate-limit behavior.
- Result normalization time.
- Indexing or ingestion duration.
- Index rebuild duration.
- Incremental update duration.
- Memory and storage consumption, when safely measurable.
- Duplicate processing and retry overhead.
Use repeatable workloads and record hardware/runtime conditions, sample sizes, warm-up policy, concurrency, timeout configuration, and measurement methodology.
Report median, p95, and p99 latency only when sample sizes and measurement methods make them meaningful.
Do not invent performance baselines, claim improvements without before/after measurements, or use a synthetic mock as evidence of live endpoint performance.
### C5. Establish the baseline before expansion
Run the evaluation against the current permitted configuration before introducing additional runtime activation.
Compare changes against that baseline using the same benchmark and test conditions.
Document:
- Existing baseline.
- Changes introduced.
- Before/after metrics.
- Regressions and improvements.
- Statistical or sample-size limitations.
- Acceptance thresholds and their source of authority.
- Whether owner approval is needed to establish a new threshold.
Do not silently change existing acceptance thresholds or weaken tests to make results pass.
### Required output
Deliver a reproducible evaluation harness, benchmark definition, results schema, execution instructions, and an Arabic performance/retrieval report. Implement the harness and tests where permitted and practical.
---
# 3. ARCHITECTURAL INVARIANTS — MUST REMAIN INTACT
Preserve the approved architectural decisions and closed work, including:
- R1–R7.
- CSQ V1 and canonical scientific identity.
- AccuracyGate and RelevanceGate.
- RC-1 and RC-2.
- Foreign-binomial repair.
- Stage-4 Results List and Viewer.
- Final Answer Confidence policy.
- Composer Units A, B, and C.
- Existing source orchestration, normalization, evidence, and provenance contracts, except for changes explicitly authorized through the applicable gates.
Do not reopen a closed architectural decision merely because another implementation appears easier.
If a genuine conflict is discovered, record the evidence and propose an explicit decision. Do not silently change the architecture.
The source-expansion architecture must keep the following roles distinct:
1. Source Identity Register.
2. Capability Contract.
3. Source Query Projection.
4. Provider/API access implementation.
5. Retrieval orchestration.
6. Result normalization.
7. Identity and provenance.
8. Scientific evidence evaluation.
9. Answer synthesis.
Do not collapse these responsibilities into one class or use a generic keyword search as a substitute for source-specific capability-aware retrieval.
---
# 4. REPOSITORY PRESERVATION AND GIT SAFETY
The repository has historically contained substantial protected scientific WIP, including modified and untracked files. Treat every pre-existing change as user-owned and protected unless proven otherwise.
Before editing:
1. Record the actual branch and HEAD.
2. Inspect `git status --short`.
3. Inspect relevant diffs and untracked files.
4. Identify pre-existing changes.
5. Record hashes for the files directly in scope when practical.
6. Determine the authorized write scope from ADR-023 and current owner decisions.
Never assume an old reported HEAD is still current. Never reset the repository to a historical baseline.
Absolutely prohibited:
- `git reset`
- `git restore`
- `git clean`
- `git stash`
- `git rebase`
- `git checkout` or `git switch` to change branches
- Force push
- Unrequested merge, revert, or cherry-pick
- Broad formatting that rewrites unrelated files
- Deleting, overwriting, or regenerating protected WIP
- Replacing existing source identities or records without evidence and authorization
- Committing or pushing without a separate explicit owner instruction
Do not use broad staging commands such as `git add .` or `git add -A`.
Make narrow, traceable changes. Keep unrelated modifications untouched. At the end, report the exact files changed and the final Git state.
---
# 5. EXECUTION GATES AND PHASE 1 STATUS
The saved Phase 1 record identifies unresolved closure conditions, including:
- B-1 tests previously recorded as NOT RUN.
- `P1-EXIT-01(c)` previously recorded as NOT CLOSED.
- Phase 1 previously recorded as BLOCKED.
- Gate A/B authorization for additional scopes is limited.
- Other exit conditions depend on owner decisions or external evidence.
These are historical records that must be rechecked against the actual repository and latest approved ADR. Do not assume they have changed, and do not assume they remain unchanged without checking.
The current task grants implementation authority for the three workstreams described in this prompt. It does not automatically authorize every previously blocked Phase 1 scope.
Before touching a gated area:
1. Identify the applicable ADR section and gate record.
2. Confirm the requested change is covered by the existing authorization.
3. Proceed when authorized.
4. If the change crosses an authorization boundary, isolate it and continue independent work.
5. Record the exact missing decision and the smallest scope requiring it.
Do not mark Phase 1 closed, change an exit status to PASS, or begin a blocked downstream phase without the required evidence and approval.
Do not let an unrelated Phase 1 blocker prevent independent, explicitly authorized source research, documentation, benchmark design, or isolated implementation work.
---
# 6. EXECUTION ENVIRONMENT AND TEST POLICY
Use the existing repository environment and available approved tooling.
The saved context states that local PHP was unavailable, `backend/vendor` was empty, and Docker/container execution had not been authorized at the time of the earlier report. Verify the present environment before deciding what can run.
Do not:
- Install dependencies or download runtimes without authorization.
- Use Docker or WSL for diagnostics or execution unless separately authorized.
- Access external networks, private APIs, or authenticated endpoints without authorization.
- expose credentials, tokens, or secrets in logs or reports.
- Run tests against production databases or real tenants.
- Execute destructive migrations or writes as part of source verification.
- claim that an unexecuted test passed.
When tests cannot run, perform permitted static checks, inspect test definitions, and report the execution blocker. Static inspection is not a substitute for runtime verification.
For external connectivity tests, use approved public endpoints or authorized credentials, respect source terms, and keep tests read-only unless a specific write operation is explicitly authorized.
Use deterministic fixtures and isolated test state. Record exact commands, exit codes, environment versions, and outputs.
---
# 7. IMPLEMENTATION STRATEGY
Work in the following coordinated sequence.
## Stage 0 — Current-state reconciliation
- Inspect the actual repository and ADR-023.
- Locate the authoritative 109-seat inventory.
- Locate the exact 17 eligible seats and 16 ambiguous identities.
- Locate the historical 62-source records and prior mapping evidence.
- Identify existing licensing, feed, registry, capability, and evaluation work.
- Build a compact evidence-based task matrix.
- Do not recreate existing artifacts if suitable ones already exist.
## Stage 1 — Source identity and evidence
- Verify the 17 eligible seats.
- Investigate the 16 ambiguous identities.
- Review licensing and access restrictions.
- Assess RSS/Atom.
- Build the 62-to-109 traceability mapping.
- Record unresolved decisions explicitly.
- Continue independent work when a specific source is blocked.
## Stage 2 — Engineering and contracts
- Reuse existing contracts and types.
- Design only the missing interfaces and models.
- Implement approved source verification and capability assessment.
- Add narrowly scoped tests.
- Keep identity, capability, projection, retrieval, provenance, and evidence responsibilities separate.
- Update documentation and ADR execution records only with verified facts.
- Do not activate sources or make production runtime changes merely to complete a checklist.
## Stage 3 — Evaluation and performance
- Build or extend the benchmark and evaluation harness.
- Capture the current baseline before changes where the environment permits.
- Measure source-specific retrieval quality and latency.
- Test facet preservation and scientific identity.
- Identify regressions.
- Implement authorized fixes and rerun affected tests.
- Record missing gold labels, missing runtime access, and other measurement limitations.
## Stage 4 — Integrated gate review
Produce a single consolidated matrix showing:
- Workstream and requirement.
- Architectural authority.
- Implementation status.
- Evidence.
- Test status.
- External dependency.
- Risk.
- Gate status.
- Required owner decision.
- Next action.
Do not confuse implementation completion with evidence completion, or evidence completion with activation authorization.
---
# 8. QUALITY AND ACCEPTANCE CRITERIA
A workstream may be declared complete only when its applicable criteria are satisfied.
### Identity and connectivity
- The canonical identity is supported by authoritative evidence.
- The endpoint is documented and authorized.
- Connectivity results are recorded from an actual permitted test.
- Capability declarations are distinguished from capabilities demonstrated by testing.
- License and access limitations are documented.
- No unauthorized source activation occurred.
### Inventory reconciliation
- Every historical and current record is accounted for or explicitly unresolved.
- The mapping preserves historical provenance.
- The current total remains 109 unless an explicit owner decision changes it.
- `G3-15` remains removed with no replacement unless an explicit decision changes it.
- Duplicate group memberships are preserved.
- Ambiguous identities are not silently resolved.
### Retrieval quality
- The benchmark and its provenance are documented.
- Metrics have explicit definitions.
- Reference judgments are distinguished from provisional or automated judgments.
- The evaluation can be reproduced.
- Scientific question identity and provenance are preserved.
- Missing capabilities are represented explicitly.
- No fabricated baseline or unsupported performance claim is reported.
### Engineering quality
- Changes are narrow and traceable.
- Tests are added for new behavior and relevant edge cases.
- Existing tests are not weakened merely to obtain a passing result.
- Executed tests and unexecuted tests are clearly distinguished.
- Protected WIP remains preserved.
- No unauthorized Git mutation occurred.
If a criterion cannot be satisfied, classify it as BLOCKED or NOT VERIFIED, explain why, and continue other work that does not depend on it.
---
# 9. REQUIRED DELIVERABLES
Create or update artifacts only where needed; reuse existing project structure and naming conventions.
Deliver:
1. Verified source-seat matrix for the 17 eligible seats.
2. Evidence-based resolution register for the 16 ambiguous identities.
3. License and access assessment register.
4. RSS/Atom decision and evidence record.
5. Auditable historical-62-to-current-109 mapping.
6. Retrieval benchmark specification and data provenance.
7. Reproducible evaluation harness and tests where authorized.
8. Performance measurement methodology and actual results where executable.
9. Updated capability and source-integration documentation, where approved.
10. ADR-023 execution records reflecting actual changes and evidence.
11. One consolidated blocker, risk, and decision register.
12. A comprehensive Arabic engineering report.
Do not create redundant documents when an existing authoritative artifact should be extended. Do not mark proposals as approved decisions.
---
# 10. FINAL REPORT FORMAT — ARABIC
Return one comprehensive Arabic report, not multiple overlapping analyses.
Use this structure:
1. **الملخص التنفيذي:** what was actually completed.
2. **حالة المستودع:** actual branch, HEAD, Git status, and preservation results.
3. **المقاعد الـ17:** identity evidence, connectivity, capability, license, and gate status.
4. **الهويات الـ16:** each resolution, evidence, or unresolved question.
5. **التراخيص وRSS/Atom:** conclusions, evidence, restrictions, and owner decisions.
6. **مطابقة 62 إلى 109:** counts, mapping categories, exceptions, and unresolved records.
7. **تصميم وتنفيذ الكود:** exact files changed and reasons.
8. **الاختبارات:** exact commands, exit codes, pass/fail counts, and unexecuted tests.
9. **جودة الاسترجاع والأداء:** benchmark design, baseline, measurements, results, and limitations.
10. **ADR-023 والبوابات:** what was updated, what remains blocked, and why.
11. **حماية العمل السابق:** proof that unrelated WIP was preserved.
12. **القرارات المطلوبة من المالك:** only decisions that genuinely cannot be made within existing authority.
13. **الخطوة التالية:** the smallest actionable continuation, without asking the owner to repeat already-known information.
For every significant claim, label its evidence status:
- `[EXECUTED]` — command or test actually ran.
- `[STATIC]` — source or configuration inspected without runtime execution.
- `[DOCUMENTED]` — supported by an authoritative project record.
- `[INFERRED]` — reasoned but not directly verified.
- `[NOT RUN]` — execution did not occur.
- `[BLOCKED]` — a specific dependency or authorization is missing.
Never present inference as an executed result.
---
# 11. START NOW
Begin by reconciling the actual current repository state and the latest approved ADR-023 records. Then execute the three workstreams as one coordinated mission, within the gates and preservation rules above.
Do not respond with a request for the owner to manually prepare the entire plan. Perform the investigation, design, implementation, tests, documentation, and reporting that are possible under the existing authorization.
Do not stop the entire mission because one endpoint, license, test environment, or owner decision is unavailable.
Do not commit or push. Stop after producing the comprehensive Arabic pre-commit report and wait for explicit authorization for any Git mutation or any activation that requires separate approval.
**Mission objective: maximize verified engineering progress across all three workstreams while preserving prior work, scientific identity, architectural integrity, and a complete audit trail
