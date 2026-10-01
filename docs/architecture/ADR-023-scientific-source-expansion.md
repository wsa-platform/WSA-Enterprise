# ADR-023 — Scientific Source Expansion

**Status:** ACCEPTED (governance + master-register freeze; implementation not started)  
**Date:** 2026-09-29 (ACCEPTED governance evolved through 2026-09-30)  
**Branch baseline:** `phase-18-m18-ai-marketing-communications` @ `505f3e6f5d29d087b1303794b224236784563d43`  
**Related:**
- [`ADR-internet-first-agricultural-ai-research-agent.md`](./ADR-internet-first-agricultural-ai-research-agent.md)
- [`../adr/ADR-001-provider-adapter-architecture.md`](../adr/ADR-001-provider-adapter-architecture.md)
- [`ADR-022-canonical-scientific-question-v1.md`](./ADR-022-canonical-scientific-question-v1.md) (**do not modify**; CSQ semantic authority)
- [`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`](./SCIENCE-SOURCE-EXPANSION-62-REGISTER.md) (**authoritative 62-source identity register**)

**Archive:** [`science-source-expansion/`](./science-source-expansion/)

---

## Provenance (reconciliation)

This document is the **single authoritative ADR-023 path**. It was reconstructed after merging remote history with local IU-01…IU-08 commits, combining two historical generations of ADR-023 content without silent deletion.

| Source | Identity | Role |
|--------|----------|------|
| Remote GO-0 archive commit | `3d16a7a6077647f7ab06f56a70b56b5cdcfe5db6` | Original GO-0 / STEP-1 governance archive introduction |
| Remote ADR blob (Git) | `707bfb2694f08b43a7ec997c513533a23e16144b` | Exact historical ADR-023 body as committed on remote |
| Pre-reconciliation local WIP | SHA-256 `23829F9CEA08F699A38DE8E8888519A498566DF0CC7C1AA131FD3C87C564BE22` | Later evolved ACCEPTED governance (D-01…D-08 + GO log through GO-ID-G3-01) |
| Merge commit preserving both histories | `ce907424351926a9d1bf12b8ff1f0bc26e9f1cf6` | Parents: `820bb763…` (local IU tip) + `3d16a7a…` (remote) |

**Authority rule:** Content labeled **HISTORICAL** below records GO-0 / STEP-1 as **PROPOSED at the time** and is **SUPERSEDED as current architectural authority**. Content under **Current Accepted Decisions** and the chronological GO decision log (from GO-0-A onward as ACCEPTED evolution) is the **current authoritative governance state**.

Later decisions **SUPERSEDE** earlier ones without deleting them (D-08).

---

## Historical GO-0 / STEP-1 Record

> **HISTORICAL — PROPOSED AT THE TIME — SUPERSEDED AS CURRENT AUTHORITY**  
> Verbatim body from commit `3d16a7a6077647f7ab06f56a70b56b5cdcfe5db6`  
> (blob `707bfb2694f08b43a7ec997c513533a23e16144b`).  
> Do not treat the historical **PROPOSED** status line as the current ADR status.

# ADR-023 — Scientific Source Expansion Governance and GO-0 / STEP-1 Record

**Status:** PROPOSED — Source Expansion governance record  
**Scope:** Scientific Source Expansion / 62-source expansion phase  
**Date:** 2026-09-29  
**Branch at audit:** `phase-18-m18-ai-marketing-communications`  
**Audit HEAD:** `505f3e6f5d29d087b1303794b224236784563d43`  
**Origin at audit:** `3946e4fe574382472228f524dd87d9f62defb18a`

> This record archives the first source-expansion prompt and the resulting Cursor forensic report. It is append-only in intent: later prompts/reports/decisions should be added chronologically and superseded decisions must remain visible.

## 1. Architectural invariants for source expansion

1. **Scientific Question Identity Preservation:** the Canonical Scientific Question (CSQ) remains the semantic authority. A source-specific query is an execution representation and must not redefine the scientific question.
2. **Scientific Answer Identity Preservation:** source transport, ranking, wording, or metadata must not redefine the scientific answer. Evidence, validation, and Composer contracts remain authoritative.
3. **Strong Query Construction:** source expansion must preserve all scientifically material constraints while allowing source-appropriate query syntax, variants, filters, and capabilities.
4. New transport-source integration must not require changes to CSQ, RSC, or AnswerComposer unless a separately proven semantic requirement exists.
5. The current extension boundary is Adapter + Registry, plus Selector when a source is auto-selected.
6. The 62-source list is authoritative for this expansion phase and must not be silently renamed, merged, split, reordered, or replaced by inferred identities.
7. No first source is selected by this record.

## 2. Archived Prompt — GO-0 / STEP-1 Read-Only Architectural Discovery

```text
WSA-Enterprise — Scientific Source Expansion
GO-0 / STEP-1 — READ-ONLY ARCHITECTURAL DISCOVERY

OBJECTIVE

We are beginning the Scientific Source Expansion phase of WSA-Enterprise.

The purpose of this task is NOT to implement a new source.

The purpose is to perform a strict READ-ONLY forensic inspection of the existing repository and determine:

1. Where the official architectural decision / documentation for Scientific Source Expansion should live.
2. Where the authoritative 62-source master list should be stored in the repository.
3. How the repository currently represents architectural decisions, source contracts, ADRs, research contracts, and scientific-search documentation.
4. Whether an existing canonical document already exists that should be extended instead of creating a new competing document.
5. What documentation structure can preserve the complete history of:
   - source-expansion decisions
   - Cursor prompts
   - Cursor reports
   - GO decisions
   - source capability assessments
   - implementation findings
   - validation results
   - risks
   - failure criteria
6. Verify that the future source-expansion architecture can preserve the scientific identity of both:
   - the user's scientific question
   - the final scientific answer
7. Verify that the architecture can support strong, source-appropriate scientific query construction without changing the meaning of the scientific question.

THIS IS A READ-ONLY TASK.

DO NOT MODIFY ANY FILE.
DO NOT CREATE ANY FILE.
DO NOT DELETE ANY FILE.
DO NOT RENAME ANY FILE.
DO NOT FORMAT OR AUTO-FIX ANY FILE.
DO NOT RUN MIGRATIONS.
DO NOT CHANGE DATABASE STATE.
DO NOT MODIFY GIT STATE.

Do not modify protected WIP.

==================================================
BASELINE / DOCUMENTATION / 62-SOURCE LIST
==================================================

Inspect the repository for existing ADRs, architecture documents, scientific-search contracts, source integration documentation, canonical contracts, source registries/profiles, query compilation/construction, evidence identity, relevance, answer synthesis, and related tests.

Determine the canonical documentation location for Source Expansion and whether a new dedicated ADR is preferable without competing with ADR-022/CSQ or ADR-021 remediation history.

Use the established authoritative 62-source list from the Scientific Source Expansion audit. Do not invent, rename, merge, split, or reorder identities. Verify repository presence and distinguish dedicated integration, indirect aggregator coverage, documentation-only mention, inactive profile, unrelated token match, and unknown.

==================================================
SCIENTIFIC IDENTITY
==================================================

Inspect CSQ, Query Understanding, ScientificSearchQueryBuilder, ScientificQueryCompiler, source adapters, selection, normalization, evidence identity, relevance, AnswerComposer, AccuracyGate, RelevanceGate, final-answer confidence, Results List, and Viewer.

Verify:
- original scientific intent remains authoritative
- source cannot silently redefine the question
- source syntax may vary while scientific meaning remains unchanged
- query rewriting cannot introduce unsupported scientific claims
- essential constraints cannot be silently dropped
- adapter cannot redefine the ask
- final answer remains grounded in evidence answering the same question
- new source integration does not require CSQ semantic changes merely for transport/API differences

==================================================
STRONG SCIENTIFIC QUERY CONSTRUCTION
==================================================

Determine:
1. semantic authority
2. HTTP/query execution authority
3. ScientificQueryCompiler role
4. ScientificSearchQueryBuilder role
5. whether source-appropriate queries can be produced without CSQ change
6. whether multiple query variants preserve one scientific intent
7. capability-aware adaptation
8. information that must never be lost during compilation

Do not describe the compiler as HTTP authority if the repository shows ScientificSearchQueryBuilder remains the executing authority. Do not invent a dual-authority conflict without runtime evidence.

==================================================
ANSWER IDENTITY / SOURCE CAPABILITY / EXTENSION BOUNDARY
==================================================

Inspect answer identity protection, source capabilities, adapter/registry/selector/profile/compiler/normalizer/configuration/bridge/tests, and Home/Crop isolation.

Determine the minimum required integration for auto-selected and optional-only sources. Do not assume a fixed number of files per source.

==================================================
FAILURE CRITERIA
==================================================

Assess FC-1 through FC-7:
FC-1 question identity changes
FC-2 essential constraint lost
FC-3 source query overrides CSQ
FC-4 evidence traceability failure
FC-5 Composer semantics change
FC-6 Home/Crop regression
FC-7 bypass of required gates

For each, report current protection, existing tests, missing expansion test, and evidence. Do not implement tests.

==================================================
DOCUMENTATION GOVERNANCE
==================================================

Determine the safest repository location for the canonical Scientific Source Expansion decision record.

The structure must support automatic chronological accumulation of:
- architectural decisions
- prompts sent to Cursor
- Cursor reports
- forensic findings
- GO-0 through GO-5 decisions
- source assessments
- implementation notes
- validation results
- failures
- corrections
- superseded decisions

Historical information must not be silently erased.

==================================================
NO SOURCE SELECTION
==================================================

Do not select, rank, score, or recommend a first source. Do not implement any source.

==================================================
FINAL REPORT
==================================================

Return:
1. EXECUTIVE SUMMARY
2. BASELINE
3. CANONICAL DOCUMENTATION LOCATION
4. 62-SOURCE REPOSITORY PRESENCE
5. SCIENTIFIC QUESTION IDENTITY
6. SCIENTIFIC ANSWER IDENTITY
7. STRONG QUERY CONSTRUCTION
8. SOURCE EXTENSION BOUNDARY
9. HOME / CROP ISOLATION
10. FAILURE CRITERIA FC-1..FC-7
11. DOCUMENTATION GOVERNANCE
12. GO-0 READINESS
13. OPEN QUESTIONS
14. RECOMMENDED NEXT READ-ONLY STEP

End with:
READ-ONLY FORENSIC AUDIT COMPLETE.
NO FILES MODIFIED.
NO GIT MUTATION PERFORMED.
```

## 3. Archived Cursor Report — GO-0 / STEP-1

### Executive findings

- No committed canonical document currently existed for Scientific Source Expansion or the 62-source master list.
- CSQ is the semantic authority.
- `ScientificSearchQueryBuilder` is the HTTP/query execution authority.
- `ScientificQueryCompiler` is metadata-only in the current implementation.
- Answer identity is protected by validation, Composer, accuracy, and directness contracts.
- No source was selected and no implementation was performed.

### Baseline

- Branch: `phase-18-m18-ai-marketing-communications`
- HEAD: `505f3e6f5d29d087b1303794b224236784563d43`
- Origin: `3946e4fe574382472228f524dd87d9f62defb18a`
- Ahead: 1
- Behind: 0
- Working tree: dirty with protected WIP
- Index: empty
- Git mutation during audit: none

### Documentation location identified

The report identified these relevant authorities:

- `docs/architecture/ADR-internet-first-agricultural-ai-research-agent.md`
- `docs/adr/ADR-001-provider-adapter-architecture.md`
- `docs/architecture/ADR-022-canonical-scientific-question-v1.md`
- `docs/architecture/CSQ-V1-ARCHITECTURE-DECISION-CLOSURE.md`
- `docs/architecture/WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md`
- `docs/architecture/ADR-021-…`
- `docs/adr/README.md`

The report recommended a dedicated source-expansion ADR and a separate authoritative 62-source register, with an append-only expansion record area. It explicitly recommended not folding source-expansion logistics into ADR-022 or ADR-021.

### 62-source repository presence

- Dedicated Stage-3 integration among the 62: none.
- Inactive profiles: AGRIS (#1), AGRICOLA (#55), GBIF (#56).
- Docs-only mention: AGRIS and PubMed outside the 62 list.
- Indirect aggregator coverage: inconclusive.
- Remaining 59: no dedicated repository integration identified.
- Inactive profiles outside the list: PubMed and Europe PMC.

### Scientific Question Identity

CSQ remains authoritative from QUS through planning, RSC, query variants, orchestration, and adapters.

The report confirmed:
- intent authority: confirmed
- source syntax variation without semantic change: confirmed
- adapter redefinition of the ask: forbidden contractually
- new-source expansion without CSQ semantic change: confirmed intent

Expansion gaps identified:
- no dedicated per-source question-identity regression test
- legacy paths without CSQ could still misuse `originalQuestion`
- adapter filter omission remains a risk

### Scientific Answer Identity

Confirmed protections include:
- claim_relation / directness
- AnswerExpressionAccuracyGate
- confidence / expressible-claim rules
- separation of ranking from truth
- existing Results List / Viewer contracts

The report noted a remaining risk that a weak new-source normalizer could inject bad metadata.

### Strong Query Construction

The report confirmed:

- Semantic authority: CSQ + RetrievalSpecification
- HTTP execution authority: `ScientificSearchQueryBuilder`
- Compiler: `ScientificQueryCompiler` → options metadata
- Builder: CSQ-first query variant construction
- Multiple variants: supported
- Capability-aware adaptation: partial
- No proven dual HTTP authority conflict

The information that must remain invariant includes entity/target/process/property/relation, required geography/time, evidence requirements, and resolution state.

### Source extension boundary

For auto-selected sources:
- Adapter: required
- Registry: required
- Selector: required
- Profile: optional
- Compiler change: not required
- Normalizer/client: as needed
- Config flag: recommended
- Intelligence bridge: optional
- Tests: recommended
- CSQ/RSC/AnswerComposer: not required

For optional-only sources:
- Adapter: required
- Registry: required
- Selector: not required
- Profile: optional
- Compiler change: not required
- Normalizer/client: as needed
- Config flag: optional
- Intelligence bridge: optional
- Tests: recommended
- CSQ/RSC/AnswerComposer: not required

No separate Source Integration Layer is required.

### Home / Crop isolation

Selector and Registry are shared; orchestrator is shared. Crop has an intentional scholarly-overlap concurrency policy. No isolation defect was confirmed.

### Failure criteria

FC-1 through FC-7 were mapped to existing protections and missing expansion-specific tests. The report recommended per-source identity, filter-drop, evidence-identity, Home/Crop enablement, and gate-bypass regression coverage as the expansion proceeds.

### GO-0 readiness

The report marked:
- extension boundary understood: YES
- question/answer identity invariants mapped: YES
- Builder vs Compiler clarified: YES
- documentation home strategy defined: YES
- 62 list authoritative in audit/chat: YES
- 62 list in Git: NO
- first source chosen: NO
- implementation: NO

Therefore GO-0 is ready for the human documentation-placement decision and subsequent authorized registration of the 62-source list.

## 4. Current decision after STEP-1

1. Preserve CSQ as the immutable semantic authority.
2. Preserve scientific answer identity through evidence/validation/Composer contracts.
3. Preserve strong query construction by compiling one scientific intent into source-appropriate representations without redefining the question.
4. Do not introduce a separate Source Integration Layer.
5. Use Adapter + Registry (+ Selector for auto-selected sources) as the source-extension boundary.
6. Keep ScientificQueryCompiler metadata-only unless a later, separately authorized architectural decision changes this.
7. Do not modify CSQ, RSC, or AnswerComposer merely to add ordinary transport adapters.
8. Do not select a first source in this record.
9. Proceed next to GO-0-A: formally register and freeze the 62-source master list in the repository.
10. Future Cursor prompts and Cursor reports for this phase are to be archived chronologically in this decision record / expansion documentation without silently deleting historical material.

## 5. Next step

**GO-0-A — تثبيت الـ62-Source Master List**

Scope: documentation-only registration of the already-established 62 identities. No source selection and no implementation.

---

## Current Accepted Decisions

The following sections preserve the later local ACCEPTED ADR-023 generation (pre-reconciliation WIP SHA-256 `23829F9C…`). This is the **current authoritative architectural state**.

## 1. Context

WSA-Enterprise Stage-3 scientific search executes OpenAlex, Crossref, Semantic Scholar, FAOSTAT (when enabled), and optional Consensus via `ScientificSourceAdapterInterface` + `ScientificSourceAdapterRegistry` + `ScientificSourceSelector`.

A separate expansion set of **62** scientific source identities was audited and must be frozen as a repository reference before GO-1 capability assessment or any adapter work.

There is **no** dedicated named “Source Integration Layer.” The official extension boundary remains Adapter + Registry (+ Selector for auto-select). Commit `505f3e6` added profile/compiler **metadata** only.

---

## 2. Decision

### D-01 — No new Source Integration Layer (this phase)

Do not create a parallel integration framework. Extend via existing Stage-3 adapter/registry boundary.

### D-02 — CSQ remains semantic authority

Canonical Scientific Question (ADR-022) remains the meaning of the user’s scientific question. Source queries are execution representations only.

### D-03 — Answer identity remains protected

Evidence, claim relation, directness, relevance/accuracy contracts, AnswerComposer, and confidence rules remain answer authority. Source ranking/API wording must not replace evidence.

### D-04 — Strong scientific query construction

`ScientificSearchQueryBuilder` is HTTP query execution authority. `ScientificQueryCompiler` is metadata/compilation support only unless a later GO supersedes this with explicit adapter consumption.

### D-05 — Hardcoded Registry

Known technical debt; **not** a blocker for documenting or assessing expansion sources. Not fixed in GO-0-A.

### D-06 — Authoritative 62-source register

[`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`](./SCIENCE-SOURCE-EXPANSION-62-REGISTER.md) is the **sole** authoritative numbered identity register (IDs 1–62). Membership ≠ execution.

**D-09 scope clarification:** D-09 hereby limits the applicability of D-06’s “sole authoritative numbered identity register” statement to the historical 62-register domain. This limitation is established by D-09; D-06’s original wording is not retroactively rewritten. Current ADR-023 membership authority is established by D-09 (Current 109 Membership SoT).

### D-07 — Documentation split

| Document | Role |
|----------|------|
| This ADR-023 | Governance, invariants, GO log, supersession |
| 62-REGISTER | Source identities + status fields |
| `science-source-expansion/` | Append-only prompts, reports, assessments |

Do **not** move CSQ semantics into the register. Do **not** modify ADR-021 or ADR-022 for expansion logistics.

### D-08 — Archive rule

Every Cursor prompt and report for this phase must be archived under `science-source-expansion/` with date/phase labels. Later decisions **SUPERSEDE** earlier ones without deleting them.

### D-09 — Current 109 Membership Source of Truth — D-06 Historical Scope Clarification

**Status:** ACCEPTED  
**Date:** 2026-10-01  
**Scope:** Current ADR-023 membership authority only. No implementation.

#### Decision

1. **Current membership.** The current ADR-023 membership universe is exactly **109** seats:
   - G1-01 … G1-22 (22)
   - G2-01 … G2-25 (25)
   - G3-01 … G3-14 (14)
   - G4-01 … G4-07 (7)
   - G5-01 … G5-19 (19)
   - G6-01 … G6-22 (22)
   - **TOTAL = 109**

2. **CURRENT MEMBERSHIP SoT.** The recovered 109 enumeration, preserved in the ADR-023 corpus and represented by the latest preserved decision record, is the **CURRENT MEMBERSHIP Source of Truth** and is **authoritative for current ADR-023 membership**.

3. **D-06 scope boundary (established by D-09).** D-09 hereby limits the applicability of D-06’s “sole authoritative numbered identity register” statement to the historical 62-register domain. This limitation is established by **D-09**; it is not a claim that D-06 originally contained a historical-only scope. D-06’s original text remains unchanged as historical governance record. Under this D-09 boundary, D-06 does **not** override D-09’s authority over **current 109 membership**.

4. **Historical 62 under D-09.** Under D-09, the 62-register (`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`) is retained as the historical numbered register for historical identity traceability, `historical_62_xref`, and historical source lineage. It does **not** define current membership, current seat creation/deletion/renumbering/replacement, or the current enumeration.

5. **G3-15.** G3-15 remains **REMOVED**. Replacement = **NONE**. No recreation, renumbering, or placeholder.

6. **G2 pins.** G2-17 = **REMVT**. G2-19 = **Animal Bioscience**. No reallocation.

7. **Dual seats.** Intentional dual G1/G6 memberships remain **separate ADR seats**, including:
   - G1-12/G6-01 FlowerBase
   - G1-13/G6-03 HortDB
   - G1-14/G6-02 Tropicals.cn
   - G1-15/G6-04 sCentInDB
   - G1-16/G6-05 AromaDb
   - G1-17/G6-06 Dr. Duke's
   - G1-18/G6-07 MPNS
   - G1-19/G6-08 FNCD
   - G1-20/G6-09 FEAtl
   - G1-21/G6-10 CRFG

   No automatic deduplication.  
   No SAME_AS minting by this decision.

8. **Change control.** Any future addition, removal, replacement, renumbering, merge, split, or other membership change to the 109-source universe requires an **explicit ADR governance decision**. Implementation must never silently mutate membership.

9. **Implementation boundary.** This decision does **NOT** authorize:
   - IU-10A implementation
   - runtime Source Registry population
   - Capability Store population
   - adapters
   - projections
   - migrations
   - database changes
   - runtime wiring
   - source integrations
   - creation/population of a machine-readable 109 register

   It establishes **governance authority only**.

---

## 3. Frozen scientific invariants

### Invariant 1 — Scientific Question Identity

ENTITY, TARGET, PROCESS, PROPERTY, RELATION, required geography/time/material constraints, evidence requirements, and resolution state must be preserved. Source-specific syntax may differ; scientific meaning must not.

### Invariant 2 — Scientific Answer Identity

Adding a source must not redefine the scientific answer via ranking, metadata, or API semantics.

### Invariant 3 — Strong Scientific Query Construction

Adapt the same scientific intent to source capabilities without changing CSQ, without storing provider strings as semantic authority, and without weakening constraints merely to make a source searchable.

---

## 4. Extension boundary (reference)

| Component | Auto-selected source | Optional source |
|-----------|----------------------|-----------------|
| Adapter | REQUIRED | REQUIRED |
| Registry | REQUIRED | REQUIRED |
| Selector | REQUIRED | NOT REQUIRED |
| Profile / Compiler change | OPTIONAL | OPTIONAL |
| CSQ / RSC / AnswerComposer | NOT REQUIRED | NOT REQUIRED |

---

## 5. GO decision log

### GO-0 / STEP-1 — Architectural discovery (2026-09-29)

**Status:** COMPLETE (read-only; no files created at time of discovery)  
**Preserved summary:**

- No committed Document of Record for expansion existed before GO-0-A.
- Recommended ADR-023 + 62-register + archive folder (now created in GO-0-A).
- Semantic authority = CSQ; HTTP authority = Builder; Compiler = metadata.
- Extension boundary = Adapter + Registry (+ Selector if auto).
- 62-list existed in audit history, not in git tree — freeze deferred to GO-0-A.
- No source selected.

Full discovery report: [`science-source-expansion/2026-09-29-GO-0-STEP-1-architectural-discovery-report.md`](./science-source-expansion/2026-09-29-GO-0-STEP-1-architectural-discovery-report.md)

### GO-0-A — Freeze and register 62-source master list (2026-09-29)

**Status:** COMPLETE (documentation only)  
**Actions:**

- Created this ADR-023 (first commit to tree; embeds STEP-1 preservation).
- Created authoritative [`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`](./SCIENCE-SOURCE-EXPANSION-62-REGISTER.md).
- Archived GO-0-A prompt + report under `science-source-expansion/`.
- Indexed ADR-023 in `docs/adr/README.md`.

**Not done:** source selection, adapters, registry/selector/runtime changes, push.

### GO-1 — Source Capability Contract / First-Source Assessment (2026-09-29)

**Status:** COMPLETE (documentation + forensic assessment; no runtime changes)  
**Actions:**

- Established [`science-source-expansion/SOURCE-CAPABILITY-CONTRACT.md`](./science-source-expansion/SOURCE-CAPABILITY-CONTRACT.md).
- Archived GO-1 prompt + report under `science-source-expansion/`.
- Assessed 62-register candidates against repository evidence only.
- **FIRST-SOURCE DECISION: NOT YET DETERMINED** (insufficient factual API/capability evidence; inactive profiles ≠ selection).

**Not done:** source selection, adapters, registry/selector/runtime changes, commit/push.

Full report: [`science-source-expansion/2026-09-29-GO-1-report.md`](./science-source-expansion/2026-09-29-GO-1-report.md)

### GO-1b — External Capability Investigation (2026-09-30)

**Status:** COMPLETE (documentation + external official-docs verification; no runtime changes)  
**Actions:**

- Archived GO-1b prompt + report under `science-source-expansion/`.
- Verified external capabilities for the 62 identities using official documentation priority (deep pass on AGRIS, AGRICOLA, GBIF, Organic Eprints, CIRAD composite, InsectBrainDatabase; category boundaries for journal/institutional cohorts without identity merge).
- Built factual capability matrix + Query/Evidence fidelity classes A–D/U (classifications, not rankings).
- Confirmed inactive profiles ≠ capability proof; GBIF structured API externally confirmed; AGRIS UI/ODS documented but public search API unproven; AGRICOLA successor SEARCH UI only.
- Recorded aggregator coverage as INDIRECT only.
- Checked FC-1…FC-7; no source cleared for implementation planning as literature first-source.
- **FIRST-SOURCE DECISION: NOT AUTOMATICALLY SELECTED**
- **FIRST-SOURCE READINESS: NOT ESTABLISHED**

**Not done:** source selection, adapters, registry/selector/runtime changes, commit/push.

Full report: [`science-source-expansion/2026-09-30-GO-1b-report.md`](./science-source-expansion/2026-09-30-GO-1b-report.md)

### GO-1c — AGRIS Full Integration Path Discovery (2026-09-30)

**Status:** COMPLETE (read-only discovery; no path selection; no runtime changes)  
**Target:** AGRIS (`master_id=1`) only.

**Actions:**

- Archived GO-1c prompt + full path-discovery report under `science-source-expansion/`.
- Investigated categories A–Y without anchoring on ODS as the assumed architecture.
- Confirmed official machine-use channel = AGRIS ODS (AP + RDF; CC BY 3.0 IGO; opt-in subset).
- Confirmed no official public bibliographic REST/GraphQL search API found.
- Confirmed OAI-PMH is inbound (providers → AGRIS), not outbound consumer harvest.
- Observed undocumented SSR HTML search surface (`/search/{lang}?q=&…`); client JS does not expose a JSON API; HTTP 429 under automated access — recorded as observation only, not a production path.
- Separated AGROVOC REST/SPARQL (vocabulary enrichment) from AGRIS literature evidence.
- Historical AGRIS SPARQL/Solr/OpenAGRIS marked third-party/historical — requires official verification.
- Produced neutral comparison matrix; **no winner / no recommendation / no first-source selection**.

**Not done:** path selection, adapters, registry/selector/runtime changes, commit/push, GO-2.

Full report: [`science-source-expansion/2026-09-30-GO-1c-AGRIS-all-integration-paths-report.md`](./science-source-expansion/2026-09-30-GO-1c-AGRIS-all-integration-paths-report.md)

### GO-ID-G3-01 — APIS Identity Governance Pin (2026-09-30)

**Status:** COMPLETE (governance / identity documentation only; no runtime changes)  
**Scope:** ADR-023 membership **G3-01** / label **APIS** only (IB-01).

**Governance decision (accepted):**

| Field | Value |
|-------|--------|
| ADR membership | G3-01 |
| Source label | APIS |
| Canonical source | **Apis** (scientific journal) |
| ISSN | 3058-0382 |
| Official URL | https://ojs.mtak.hu/index.php/Apis/index |
| Operator / publisher | Hungarian Apitherapy Society |
| External identity status | **VERIFIED** |
| ADR membership status | **PINNED** |
| IB-01 | **CLOSED / PINNED** |
| Runtime register | **NOT_PRESENT** (not implemented) |

**Historical boundary:** 62-REGISTER **#35** remains historical label-only evidence and is **not** rewritten by this pin.  
**Universe boundary:** 109 membership count unchanged (G3=14; G3-15 remains removed).

**Not done:** Capability Contract Alignment execution, Capability Verification, API/OAI/RSS, adapters, projection, Builder/CSQ/Catalog, runtime register population, commit/push.

Full record: [`science-source-expansion/2026-09-30-G3-01-APIS-identity-governance-pin.md`](./science-source-expansion/2026-09-30-G3-01-APIS-identity-governance-pin.md)

---

## 6. Out of scope (GO-0-A)

- Implementing any of the 62 sources
- Selecting or ranking a first source
- Changing CSQ, RSC, Builder, Compiler execution, Composer, gates, Results List, Viewer
- Modifying ADR-021 or ADR-022

---

## 7. Consequences

- Expansion work has a stable identity baseline.
- Capability assessment (GO-1) can fill UNKNOWN fields without renumbering.
- Runtime Stage-3 behavior is unchanged by GO-0-A documentation.
