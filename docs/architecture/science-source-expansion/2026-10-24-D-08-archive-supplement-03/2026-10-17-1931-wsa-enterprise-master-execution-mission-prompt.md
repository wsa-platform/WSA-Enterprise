<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16583). Message timestamp as recorded in the conversation: Saturday, Oct 17, 2026, 7:31 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16584; it is archived once. -->

---
# WSA-Enterprise — MASTER EXECUTION MISSION
## Scientific Source Expansion — Workstreams A, B, C
### Full Engineering Authority | Strict Safety | Evidence-Based Completion
**Mission date:** 2026-10-17  
**Repository:** `wsa-platform/WSA-Enterprise`  
**Expected working directory:** `G:\WSA-Enterprise`  
**Expected branch:** `phase-18-m18-ai-marketing-communications`  
**Reported starting HEAD:** `f2025315d8990f52f3c658c968a3ec8252ac18b0`
The reported HEAD and all repository facts must be verified locally before execution. Do not assume this baseline is still current.
---
## 1. MISSION AND AUTHORITY
Act as the project's senior software engineer, software architect, scientific-search engineer, test engineer, and technical auditor.
You have broad authority to investigate, design, implement, refactor, write code, create tests, repair defects, improve internal abstractions, and update documentation necessary to complete the three agreed workstreams.
**Your job is to deliver working, verified engineering outcomes—not merely plans, recommendations, matrices, or reports.**
Use professional engineering judgment to select the best implementation. You may choose appropriate class boundaries, interfaces, value objects, factories, validation rules, test architecture, evaluation methods, and internal refactoring when justified by repository evidence.
You may implement all changes necessary within the authorized mission scope without asking for approval for each ordinary coding decision.
Do not repeatedly ask the user questions that can be answered by inspecting the repository, existing ADRs, source records, tests, official documentation, or available local artifacts.
When a genuinely external owner decision, legal authorization, credential, permission, or safety approval is indispensable, document the precise blocker and continue all independent work that remains safe.
## 2. THREE REQUIRED OUTCOMES
### WORKSTREAM A — Scientific source expansion and readiness
Complete the engineering work needed to evaluate and prepare the 17 source candidates:
`G1-01, G1-13, G1-15, G1-17, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G5-04, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16`
Required actions:
1. Inspect the current Source Registry, Capability Contract, source adapters, query-building pipeline, normalizers, licensing records, provenance model, and existing source tests.
2. Verify each candidate's identity, official endpoint, supported search capabilities, access method, documented license, attribution requirements, rate limits, authentication requirements, and technical feasibility using available authoritative evidence.
3. Distinguish verified facts from assumptions, inferred capabilities, historical observations, and unresolved owner decisions.
4. Implement missing source-specific adapters, capability declarations, validation, normalization, error handling, and tests where evidence and authorization support them.
5. Preserve scientific-question identity and provenance throughout the pipeline. Do not reduce CSQ to keyword-only search, bypass the canonical query architecture, or silently weaken query semantics.
6. Keep source availability, technical capability, licensing permission, identity verification, readiness, and activation as distinct states.
7. Do not mark a source ready merely because its endpoint exists or a request returns HTTP 200.
8. Preserve existing source IDs and historical accounting. Do not merge, delete, rename, or consolidate ambiguous identities based on similar names alone.
9. Reconcile the 17-candidate readiness matrix against the actual implementation and test evidence.
10. For candidates that cannot be completed safely, provide an exact reason, the missing evidence or decision, and the smallest remaining action required.
The current report identifies `G5-04` as ACTIVE, previous successful connections for `G5-04` and `G1-01`, and a previous failed connection for `G2-15`. Treat these as historical report claims until verified. Do not assume that a previous connection proves current readiness or authorization.
**Completion criterion:** Every candidate has an evidence-backed disposition, and every technically and legally authorized candidate within scope is implemented and tested to the fullest safe extent. Any candidate blocked by external approval remains explicitly blocked rather than falsely reported as complete.
### WORKSTREAM B — Resolve source identity, licensing, and registry blockers
Address the unresolved identity and licensing issues without corrupting the historical registry or making unauthorized legal decisions.
The currently reported ambiguous identity set is:
`G1-04, G1-16, G1-20, G2-03, G2-20, G2-24, G2-25, G3-01, G3-13, G3-14, G4-03, G4-04, G6-05, G6-09, G6-21, G6-22`
Required actions:
1. Re-examine the original evidence and the latest identity-verification matrix.
2. Search repository records and authoritative source documentation for identity, ownership, endpoint, and historical provenance evidence.
3. Resolve an identity only when evidence supports that exact identity. Otherwise preserve the unresolved state and record what evidence is missing.
4. Do not merge records solely because names, domains, or subject areas resemble one another.
5. Review licensing and terms for each intended access and use model, including search requests, metadata, abstracts, full text, caching, storage, indexing, redistribution, and attribution where applicable.
6. Separate public accessibility from legal permission. An unresolved license must never be treated as permission.
7. Where legal interpretation or organizational acceptance is required, prepare a concise decision record identifying the source, proposed use, supporting evidence, risk, and exact owner decision needed. Do not invent or approve that decision on the owner's behalf.
8. Investigate the reported RSS/Atom candidates `G1-05`, `G1-10`, and `G2-05`. Do not promote feeds to primary scientific-search sources or initiate ingestion unless the architecture, intended use, evidence, and authorization explicitly support it.
9. Preserve the distinction between the frozen D-09 count of 109 and CURRENT v2 count of 110, including the reported Wiley `G2-26` entry. Do not force counts to match by silently deleting, merging, or manufacturing records.
10. Reconcile all 62 historical source records against the relevant accounting and registry artifacts, preserving original identifiers, provenance, and historical states.
11. Review the reported statuses for B1, B2, B3, and B4 and update them only when actual evidence justifies the change.
**Completion criterion:** Every blocker that can be resolved from available evidence is resolved with traceable proof. Remaining identity or licensing decisions are isolated, actionable, and explicitly owned by the appropriate human authority. Do not declare Workstream B fully closed while a required external decision remains outstanding.
### WORKSTREAM C — Scientific retrieval evaluation and performance
Turn the current provisional offline evaluation into a reliable, repeatable evaluation system, and execute the permitted tests.
The previous report gives the following provisional offline metrics:
- Precision: `0.583`
- Recall: `1.0`
- MRR: `0.8`
- nDCG: `0.9`
- Facet preservation: `1.0`
- Unsupported explicit constraints: `1.0`
- Median fixture latency: `7.65 ms`
- p95/p99 latency: unavailable
- Evaluation cases: 10, with a PROVISIONAL rather than expert-approved golden baseline.
Treat these values as previously reported results, not independently verified facts or a final acceptance baseline.
Required actions:
1. Inspect the evaluation harness, cases, loaders, result models, generators, test suites, ADR-023 §8.47, and prior R9 baseline.
2. Preserve the historical R9 baseline and its provenance. Do not overwrite or silently redefine it.
3. Validate the correctness of metric definitions, relevance judgments, ranking calculations, facet-preservation checks, explicit-constraint checks, and latency measurements.
4. Improve the test corpus where justified, including negative cases, empty results, duplicate results, source failures, malformed responses, pagination, unsupported constraints, conflicting evidence, and source-specific normalization.
5. Keep provisional cases distinct from an expert-approved golden set. Do not fabricate expert labels or present provisional metrics as expert validation.
6. Run all relevant offline evaluation scripts and permitted automated tests.
7. Restore or establish the required test environment only through safe, documented means. Inspect available PHP, Composer dependencies, Python, and test tooling before choosing an approach.
8. If dependencies are missing, determine whether they can be installed safely without modifying protected files or lockfiles unexpectedly. Do not use Docker or modify the environment in a way prohibited by the existing project instructions.
9. If PHPUnit cannot run, report the exact missing prerequisites and continue independent tests that are executable.
10. Measure latency with enough observations to produce meaningful distributions. Report sample size, measurement conditions, median, p95, and p99 where statistically supportable. Clearly distinguish fixture latency from actual source/network latency.
11. Do not issue live network probes until the applicable source-level authorization, licensing conditions, rate limits, and safety requirements have been verified.
12. Compare the new evaluation against the prior baseline using compatible definitions. Explain any changes in corpus, methodology, or metrics.
13. Add regression tests for defects discovered during implementation.
**Completion criterion:** The offline evaluation is reproducible, the relevant executable tests pass, metric definitions and limitations are documented, and performance claims are supported by measured evidence. Live-network performance must remain explicitly unverified if live testing is not authorized or cannot be performed safely.
---
## 3. ARCHITECTURAL RULES THAT MUST NOT BE VIOLATED
Inspect the actual approved ADRs and current implementation before changing architecture.
The following established project principles remain binding:
- CSQ remains the semantic authority for the scientific question.
- `ScientificSearchQueryBuilder` must preserve the intended scientific query semantics.
- `ScientificQueryCompiler` must retain its approved responsibilities and must not silently become an alternative semantic authority.
- `MultiSourceScientificSearchOrchestrator` must preserve the intended orchestration contract.
- Source adapters must expose truthful, evidence-backed capabilities.
- `ScientificResultNormalizer` must preserve source identity, provenance, and scientific meaning.
- Capability, membership, licensing, identity, readiness, and activation are distinct concepts.
- Do not bypass established contracts to make a test pass.
- Do not weaken explicit constraints or silently broaden a scientific query to manufacture results.
- Do not delete historical evidence, source records, audit trails, or existing work to simplify implementation.
- Do not change unrelated application behavior merely because a refactor is convenient.
You may refactor or redesign internal code where necessary, but explain the reason, maintain compatibility where required, and add regression tests.
If two architectural records conflict, identify the exact conflict and apply the project's established authority and supersession rules. Do not silently invent a resolution.
## 4. BROAD IMPLEMENTATION AUTHORITY
Within these three workstreams, you are authorized to:
- Write and modify production code and test code.
- Design and implement missing interfaces, contracts, factories, value objects, adapters, validators, evaluation tools, and supporting abstractions.
- Repair implementation defects and add regression coverage.
- Refactor code where necessary to satisfy established architectural contracts.
- Improve error handling, observability, deterministic evaluation, provenance recording, and diagnostics.
- Add or update scoped documentation, technical reports, evidence matrices, and ADR-023 records.
- Use local scripts and permitted test tools.
- Inspect official public documentation and available local evidence to verify source capabilities and licensing facts.
- Create a chronological audit trail and a machine-readable summary of the final state.
- Make reasonable internal design decisions without seeking approval for each one.
Use the simplest robust design that fits the existing architecture. Avoid speculative frameworks, unnecessary dependencies, broad rewrites, and code that has no
