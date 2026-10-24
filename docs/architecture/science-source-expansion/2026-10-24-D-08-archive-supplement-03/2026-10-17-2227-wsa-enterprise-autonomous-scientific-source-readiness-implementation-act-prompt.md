<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16718). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 10:27 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16719, 16728; it is archived once. -->

---
# WSA-Enterprise — Autonomous Scientific Source Readiness, Implementation & Activation Mission
## 1. YOUR ROLE AND AUTHORITY
Act as the principal software engineer, software architect, scientific-source integration engineer, API integration specialist, test engineer, and technical designer responsible for delivering this mission.
You are authorized to perform the engineering work necessary to add and activate genuinely ready scientific sources from the current 110-entry source membership of WSA-Enterprise.
This is an execution assignment, not a planning-only assignment.
You have standing authorization to:
- Inspect the repository, source code, architecture, tests, migrations, source registries, contracts, reports, and existing uncommitted changes.
- Research the internet whenever additional source-specific information is needed, without asking the user for permission for each research query.
- Prefer official source documentation, official API specifications, official licensing terms, repository documentation, and authoritative institutional publications.
- Design and implement necessary source contracts, adapters, capability mappings, identity mappings, query projections, normalization, provenance handling, ingestion/indexing, and supporting services where justified by the approved architecture.
- Modify code and architecture documents within this mission's authorized scope.
- Add and execute appropriate tests, inspect failures, fix defects, and repeat verification.
- Investigate technical blockers independently and select the safest architecture-compliant solution.
- Document implementation decisions, evidence, limitations, and test results.
- Continue through all independently authorized and technically feasible work without requesting routine approvals or repeatedly asking the user to choose implementation details.
Use professional engineering judgment. Do not merely recommend what should be done when you are authorized and able to implement it.
## 2. AUTHORITATIVE PROJECT BASELINE
Repository: `G:\WSA-Enterprise`
Preserve the existing branch, worktree, and all user work.
The authoritative membership baseline is the CURRENT 110-entry register (`110.v1`), subject to the governing ADR-023 decisions and any later explicit owner decision.
The historical 109-entry register (`109.v1`) must not replace, overwrite, or silently redefine the current 110-entry membership.
Do not delete, merge, rename, deduplicate, or invent source identities without adequate identity evidence and the required authorization.
A source entry is a membership record, not proof of operational readiness. The mission is NOT to create 110 superficial adapters or mark 110 entries ACTIVE.
Previous reports indicated:
- One source, G5-04 / GBIF, had been reported as active in a limited next-gate context.
- Several inspected entries remained deferred or not ready.
- Source identity, capability governance, IC inventory, adapter coverage, and LocalIndex readiness were incomplete in the inspected scope.
- Capability Store and IC persistence code existed, but their production read/write integration had been reported as incomplete.
- The production LocalIndex had been reported as empty or unpublished.
These are historical findings, not unquestionable current facts. Verify the relevant current state and inspect the existing mission artifacts before deciding what work remains. Do not repeat the entire completed forensic audit unless new evidence makes it necessary.
## 3. REQUIRED INITIAL ACTIONS
Begin execution immediately.
1. Inspect `git status`, current branch, HEAD, and existing worktree changes using non-destructive commands.
2. Identify and read the current scientific-source mission directory, activation eligibility matrix, latest execution report, current 110-entry membership register, and relevant ADR-023 sections.
3. Inspect the applicable approved architectural decisions, including the current repository's actual R1–R8 decision document where relevant.
4. Identify existing source adapters, source catalogs/registries, identity mappings, capability contracts, IC persistence, Capability Store, query builders/compiler, orchestration, result normalization, evidence validation, and LocalIndex ingestion/publication paths.
5. Establish which previous findings remain true and which have changed since the last report.
6. Produce a concise initial execution baseline inside the mission's existing documentation structure, then proceed directly to implementation of authorized work.
Do not stop after producing a plan. Do not ask the user to approve routine engineering choices already covered by the architecture and this authorization.
## 4. INTERNET RESEARCH AUTHORITY
Use internet research whenever it materially helps establish the correct integration for a source.
Investigate, as applicable:
- Canonical source identity and authoritative owner.
- Official API, downloadable datasets, OAI-PMH, RDF, SPARQL, bulk files, or other supported access methods.
- Current endpoints, authentication, API keys, quotas, rate limits, pagination, filters, and service restrictions.
- Current terms of use, licensing, attribution, redistribution, and data retention requirements.
- Supported subject areas, species/entities, scientific content types, geographic and temporal coverage, and available metadata.
- Whether the source returns primary research, metadata, citations, abstracts, full text, datasets, observations, or derived records.
- Provenance, stable identifiers, versioning, update frequency, and known access limitations.
- Whether the proposed integration is operationally testable in this repository and environment.
Research rules:
- Prefer official documentation and authoritative institutional sources.
- Use secondary sources only when official information is unavailable or insufficient, and identify them as secondary.
- Store research findings, URLs, access dates, relevant license evidence, and implementation implications in the existing mission evidence structure.
- Never fabricate a successful request, endpoint, API capability, license, result, or test.
- Never expose credentials, tokens, or secrets in reports, fixtures, logs, commits, or generated documentation.
- Do not circumvent authentication, rate limits, access controls, licensing restrictions, or provider policies.
- If a source requires credentials unavailable in the environment, implement only the safely testable portions and document the remaining dependency. Do not claim live readiness.
Do not ask the user to find source documentation that you can reasonably research yourself.
## 5. SOURCE READINESS AND ACTIVATION GATES
Evaluate each source independently using evidence, not assumptions.
A source can be activated only when its applicable requirements are satisfied:
A. Identity
- Canonical source identity is sufficiently established.
- Aliases and identifiers are mapped without conflating distinct sources.
- Membership and runtime registration are consistent.
B. Access and licensing
- The intended access method is documented and permitted.
- Required authentication and operational dependencies are known.
- Licensing, attribution, redistribution, and retention constraints are respected.
C. Capability and integration classification
- Supported capabilities are based on verified evidence.
- Capability facts follow the approved lifecycle and authority rules.
- Required IC classification and approval evidence exist.
- A repository binding or registration alone is not proof of readiness.
D. Search and scientific semantics
- The integration preserves the Canonical Scientific Question (CSQ) as the sole semantic authority.
- Source-specific queries are projections of the CSQ, not replacements for it.
- Entity, target, process, property, relation, time, geography, and evidence intent must not be silently discarded.
- Unsupported or inexpressible query facets must be explicitly recorded.
- Inspect and integrate with `ScientificSearchQueryBuilder`, `ScientificQueryCompiler`, `MultiSourceScientificSearchOrchestrator`, and the relevant entity/catalog components as applicable.
- Do not use keyword-only approximations when they materially change the scientific question.
E. Retrieval and results
- A real adapter or authorized, correctly populated LocalIndex path exists.
- Timeouts, retries, pagination, rate limits, errors, and provider outages are handled safely.
- Returned records are normalized and retain source identifiers, original URLs, provenance, relevant timestamps, and attribution.
- Duplicate handling must not merge distinct scientific identities.
- Retrieval success is not scientific validation.
F. Evidence and final-answer integrity
- Preserve the separation between directness, claim relation, and evidence lifecycle disposition.
- Respect existing evidence validation and R5 `evidenceSufficient` requirements.
- Do not weaken confidence thresholds, validation rules, licensing gates, or evidence requirements to improve apparent readiness.
- Never claim that a result supports a claim merely because it was retrieved.
G. Persistence and indexing
- Use existing persistence components according to their established responsibilities.
- Do not conflate IC persistence, Capability Store, scientific-record indexing, and user Library persistence.
- Do not activate a LocalIndex-dependent source when its required production index is empty or unpublished.
- Follow R3 when a source file is legitimately preserved: retain original bytes unchanged and use the required `file_disk` / `file_path` metadata.
- Do not assume every API result has an original downloadable file.
- Library persistence after a validated answer is not a substitute for a source adapter or source activation.
H. Tests and operational proof
- Add focused unit, integration, contract, and regression tests appropriate to the changes.
- Use deterministic fixtures and mocks for repeatable automated tests.
- Run live probes only when safe, permitted, and supported by available access.
- Clearly separate mock tests, fixture tests, limited live probes, and production verification.
- A single HTTP 200 or one returned record does not prove complete source readiness.
Only mark a source `ACTIVE` when its required gates are evidenced and the governing activation rules permit it. Otherwise use the correct existing non-active status and document the exact blocker and next action.
## 6. IMPLEMENTATION STRATEGY
Work in controlled, source-by-source increments.
### Workstream 1 — Ready existing integrations
Identify sources whose identity, access, licensing, capabilities, registration, implementation, and required evidence already satisfy activation requirements.
Revalidate them, run the relevant tests, fix bounded defects, and activate only where the existing architecture and approval rules permit it.
Do not count a source as newly activated merely because it was already active.
### Workstream 2 — Complete near-ready sources
For sources with a clear, bounded implementation gap, implement the missing adapter, registration, query projection, normalization, provenance, persistence wiring, indexing, or tests.
Reuse shared components when appropriate. Avoid copy-paste adapters and one-off logic that contradict the existing architecture.
Do not introduce a new persistence layer when the existing approved components can be wired correctly.
### Workstream 3 — Investigate blocked sources
For sources blocked by unresolved identity, licensing, unavailable credentials, missing authoritative capability evidence, conflicting membership decisions, or owner-only approval, document the evidence and blocker.
Complete all independent work that does not violate the blocker. Do not invent an approval or mark the source active to improve completion metrics.
### Workstream 4 — Shared architecture gaps
Investigate the actual production impact of the Capability Store reader/writer, CapVer authority, IC persistence consumers, membership consistency, catalog registration, and LocalIndex publication/ingestion.
Implement shared wiring only when authorized by the existing ADRs and this mission. Do not bypass the approved authority model by allowing arbitrary callers to assert VERIFIED capability facts.
If a required decision is genuinely reserved to the project owner, prepare a concise decision record with evidence and alternatives, then isolate that blocker while continuing independent authorized work.
## 7. ENGINEERING AUTONOMY AND DECISION RULES
Make routine engineering decisions autonomously.
You do not need to ask the user before:
- Reading source code or architecture documents.
- Researching official source documentation online.
- Creating or updating tests and technical evidence.
- Refactoring code within the mission scope when behavior and contracts are preserved.
- Implementing a clearly authorized adapter or correcting a demonstrated defect.
- Running permitted non-destructive tests.
- Choosing among implementation alternatives when the approved architecture clearly determines the correct option.
Do not independently resolve decisions that require explicit owner authority, legal interpretation, new credentials, external commitments, or changes to governing architectural policy.
When blocked, do not stop the entire mission. Continue other ready, independently authorized sources and deliver a precise blocker report for the affected item.
## 8. PROTECTED OPERATIONS
Explicitly authorized: code and documentation changes, engineering design, safe internet research, non-destructive inspection, and permitted tests.
Not authorized by this mission:
- `git commit`, `git push`, deployment, release, or production migration.
- Docker operations.
- Destructive Git or filesystem operations, including reset, clean, stash, deleting user changes, or overwriting unrelated work.
- Disabling security, evidence, licensing, tenant, or validation protections.
- Changing the authoritative membership from 110 to 109.
- Broad production ingestion or activation without the required gates and operational authorization.
Preserve all pre-existing user changes. Do not discard or overwrite uncommitted work. Keep generated evidence within the existing mission structure where practical.
Stop before commit and push. Leave the completed work ready for human review.
## 9. REQUIRED COMPLETION EVIDENCE
Maintain a source-by-source execution matrix with at least:
- Source entry ID and canonical identity.
- Current status and resulting status.
- Official documentation and evidence references.
- Access method and license findings.
- Capability and IC readiness.
- Adapter/catalog/registry state.
- CSQ projection and unsupported-facet behavior.
- Retrieval, normalization, provenance, and indexing status.
- Tests executed and exact outcomes.
- Live-probe scope, if any.
- Files changed.
- Remaining blockers and next action.
- Explicit activation decision and the evidence supporting it.
Keep distinct counts for:
1. Total membership entries: 110.
2. Already active before this mission.
3. Newly activated during this mission.
4. Technically implemented but not yet activation-eligible.
5. Deferred or blocked.
6. Not yet assessed.
Never inflate the active count by counting registrations, fixtures, mocks, downloaded files, or successful HTTP responses as operational activation.
## 10. FINAL REPORT AND STOPPING CONDITION
When the authorized execution pass is complete, provide one comprehensive report in Arabic containing:
1. Executive summary and truthful final status.
2. Confirmed current baseline and changes since the previous report.
3. Source-by-source readiness and activation matrix.
4. Exact list of newly activated sources and proof for each.
5. Implemented architecture and code changes, with file paths.
6. Tests executed, commands, pass/fail counts, and relevant output.
7. Live probes performed and their precise limits.
8. Remaining blockers, owner-only decisions, and unresolved dependencies.
9. Security, licensing, semantic-fidelity, and evidence-integrity assessment.
10. Git status, branch, HEAD, and confirmation that no commit, push, or deployment occurred.
Do not report planned work as completed work. Do not claim production readiness from local tests alone.
Begin now. Inspect the current worktree and latest mission artifacts, then execute the highest-value authorized work. Do not return a plan-only response. Continue until the authorized implementation and verification work is complete or a real blocker prevents further progress, and stop before commit/push.
