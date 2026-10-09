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

---

## 8. Preservation Appendix — D-09 Governance Chain and Conversation Record

**Purpose:** Preserve the substantive prompts, forensic reports, governance reviews, corrections, approvals, and execution records produced during the recovery and formalization of the current 109-source membership SoT. This appendix is **append-only**. Existing ADR text, prior decisions, historical records, and earlier wording are not deleted or rewritten.

### 8.1 Preservation rule

The project conversation history for this phase is preserved as a **consolidated architectural record**, not as a claim of verbatim transcript completeness. Where a full prompt/report already exists under `science-source-expansion/` or in preserved ADR artifacts, that original artifact remains the detailed record. This ADR records the chain of authority, purpose, result, and disposition so the architectural decision remains self-contained.

No earlier decision is deleted because a later decision supersedes or clarifies it.

### 8.2 109 Enumeration Recovery — preserved prompt/report chain

1. **IU-10A Enumeration / Governance Recovery Gate — READ-ONLY**
   - Objective: recover the complete 109-seat enumeration from repository-local and preserved architectural artifacts.
   - Prohibitions: no new manifest, no runtime registry, no identity minting, no SAME_AS, no ADR mutation, no commit/push.
   - Required identity protections: historical 62 numbering remains `historical_62_xref`; G3-15 must not be resurrected; recovered names must not be promoted to canonical identities.
   - Result: the initial repository-only conclusion that 109 was not recoverable was later superseded by discovery of preserved ADR artifacts containing the enumeration.

2. **ADR-023 — 109 ENUMERATION EXTRACTION CROSS-VERSION FORENSIC RECONCILIATION REPORT**
   - Result: **COMPLETE 109 ENUMERATION RECOVERED — NO MATERIAL CONFLICT**.
   - G1=22, G2=25, G3=14, G4=7, G5=19, G6=22, TOTAL=109.
   - G3-15 = REMOVED; replacement = NONE.
   - G2-17 = REMVT; G2-19 = Animal Bioscience.
   - Intentional G1/G6 duplicate seats remain separate; no SAME_AS minted.
   - Historical 62 remains historical cross-reference only.
   - Latest preserved enumeration artifact: `ADR-023-Scientific-Source-Expansion-Full-Source-Architecture-PRESERVED-20261001-v4.docx`.
   - Governance gap identified: the 109 universe was accepted in preserved ADR material but was not yet formally established as the current numbered membership SoT in the tracked ADR.

### 8.3 D-09 Governance Acceptance and wording chain

3. **ADR-023 — 109 Membership SoT Governance Acceptance Gate**
   - Result: **ACCEPTABLE WITH EXPLICIT GOVERNANCE WORDING REQUIRED**.
   - Determination: enumeration complete, but formal current-membership SoT closure required an explicit ADR decision.
   - Required closure: establish 109 as current membership authority and establish the D-06 historical scope boundary without rewriting D-06's original wording.
   - IU-10A remained unauthorized.

4. **D-09 CURRENT 109 MEMBERSHIP SoT — FINAL GOVERNANCE WORDING REVISION GATE**
   - READ-ONLY; no mutation.
   - Three mandatory corrections were applied to the proposed wording:
     1. D-09, not D-06 retroactively, establishes the historical-62 scope boundary.
     2. D-09 establishes the current historical role of the 62-register; D-06's original history is preserved.
     3. Proposed status remained `PROPOSED — PENDING GOVERNANCE APPROVAL` until explicit user approval and actual ADR mutation.
   - All previously closed invariants remained unchanged.

5. **User governance approval**
   - Explicit approval was given to apply the revised D-09 wording because it was determined to be the appropriate project governance form.

6. **D-09 Governance Mutation & Forensic Verification**
   - Mutation boundary: ADR-023 only.
   - D-09 inserted exactly once with status `ACCEPTED`, date 2026-10-01.
   - 109 counts and required G2/G3 pins verified.
   - Dual seats preserved; no SAME_AS.
   - D-06 original body preserved; D-09 clarification added.
   - IU-10A not implemented and not authorized.
   - Runtime, database, Capability Store, and Source Registry unchanged.

### 8.4 D-09 commit/push execution record

7. **D-09 Commit & Push Forensic Report**
   - Branch: `phase-18-m18-ai-marketing-communications`.
   - Commit: `9de9ed874510dad1970abe14b2aadb4441e9137a`.
   - Subject: `docs(research): establish current 109 membership SoT`.
   - Exactly one file committed: `docs/architecture/ADR-023-scientific-source-expansion.md`.
   - Push succeeded.
   - Local and remote HEAD matched; ahead/behind = 0/0 at execution time.
   - Pre-existing WIP was preserved.
   - IU-10A remained separate and unauthorized.
   - Runtime and database remained unchanged.

### 8.5 Architectural conversation decisions preserved by this chain

The following conversation-level constraints remain part of the ADR record:

- The current 109 universe must never be silently merged with the historical 62 universe.
- Membership identity is distinct from canonical source identity.
- Duplicate-looking G1/G6 seats remain separate until an explicit SAME_AS identity decision exists.
- G3-15 is permanently removed from the current 109 membership universe unless a future explicit governance decision changes membership.
- G2-17 and G2-19 remain pinned as REMVT and Animal Bioscience respectively.
- Membership does not imply execution, capability verification, adapter availability, runtime registration, or source selection.
- D-09 is governance-only and does not authorize IU-10A implementation.
- IU-01 through IU-09 remain closed and unchanged by D-09.
- CSQ remains scientific semantic authority; source/provider syntax must never redefine scientific identity.
- No destructive Git operation, unrelated file mutation, or silent architecture change is authorized by this preservation record.
- Later decisions may supersede earlier decisions, but **earlier records must remain preserved**.

### 8.6 Preservation references

The detailed records consulted for this chain include, where present:

- `ADR-023-Scientific-Source-Expansion-Full-Source-Architecture-v2.docx`
- `ADR-023-Scientific-Source-Expansion-Full-Source-Architecture-PRESERVED-20261001-v4.docx`
- `Pasted text(20261001-162021).txt` — 109 enumeration forensic report
- `Pasted markdown(20261001-164702).md` — D-09 proposal/revision gate and governance wording
- Existing `science-source-expansion/` prompts, reports, assessments, and decision records referenced elsewhere in this ADR

**Preservation invariant:** this appendix supplements the existing ADR. It does not replace, delete, rewrite, or invalidate any earlier architectural record.

### 8.7 IU-10A Implementation, Integration, Push and Closure Record

**Purpose:** Preserve the chronological IU-10A lifecycle that occurred **after** the D-09 preservation chain (§8.2–§8.6). This subsection is **append-only**. It does not rewrite D-06, D-09, §8.2–§8.6, earlier GO decisions, or any historical statement.

**Chronology rule:** Statements in §8.3–§8.5 such as “IU-10A not implemented and not authorized,” “IU-10A remained unauthorized,” and “D-09 … does not authorize IU-10A implementation” remain **historical D-09-era snapshots** and **D-09 scope statements**. They stay exactly where they historically belong. They are **not** deleted. They are **superseded for present-tense IU-10A status** only by this later, separately authorized IU-10A chain—not by rewriting those earlier lines.

#### H — IU-10A implementation authorization / design

- D-09 did **not** authorize IU-10A implementation.
- Later, a **separate** IU-10A implementation authorization gate was opened.
- Design selected **Option E / Hybrid**: an authoritative JSON membership register with **no database** and **no runtime registry authority**.
- Final membership artifact path: `docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json`
- Contract test: `backend/tests/Unit/Agriculture/Research/Membership/Adr023109MembershipRegisterContractTest.php`
- **No PHP loader** was included in IU-10A.
- PHP loader was deferred as future design work and was **not** automatically established as **IU-10B**.

#### I — IU-10A design closure / enumeration integrity

- Full **109** enumeration recovered and verified.
- Counts: G1=22, G2=25, G3=14, G4=7, G5=19, G6=22; **TOTAL=109**.
- G3-15 **ABSENT** / **REMOVED**; replacement = **NONE**.
- G2-17 = **REMVT**; G2-19 = **Animal Bioscience**.
- All intentional G1/G6 dual seats preserved as separate membership seats.
- No SAME_AS; no canonical identity minting by IU-10A.
- Exact preserved labels verified (including G5-11: Egyptian Academic Journal of Biological Sciences A — Entomology).
- `historical_62_xref` omitted from v1.
- PHP loader deferred as separate future work.
- Stage-3 boundary unchanged.

#### J — IU-10A implementation

Exactly two files were implemented:

1. `docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json`
2. `backend/tests/Unit/Agriculture/Research/Membership/Adr023109MembershipRegisterContractTest.php`

The JSON register is **membership-only**. It does **not** become Source Registry, Capability Store, Stage-3 authority, `sourceKey` authority, adapter authority, provider authority, path authority, or canonical identity authority. Validation is **fail-closed**.

#### K — IU-10A verification

- Node fail-closed validation: **PASS** (0 errors).
- PHPUnit: **1 test**, **5243 assertions**, **0 failures**, **0 errors**, **0 skipped**.
- Register facts: `total_current_seats = 109`; group counts 22/25/14/7/19/22; 109 seats; 10 dual pairs; G3-15 absent; G2-17 = REMVT; G2-19 = Animal Bioscience; forbidden runtime/identity keys absent.

#### L — IU-10A scoped commit

- Original implementation commit: `0439142d0a214657e4a2e8c7201159bb7d502555`
- Subject: `fix(research): add ADR-023 109 membership register`
- Exactly the two IU-10A files above.
- No ADR-023 markdown; no ADR-021; no unrelated WIP in that commit.

#### M — Remote divergence

After `9de9ed874510dad1970abe14b2aadb4441e9137a`, two sibling children existed:

- **Remote:** `53c5a9caafc9940de244f1367a142e57a1c376b1` — D-09 Preservation Appendix.
- **Local:** `0439142d0a214657e4a2e8c7201159bb7d502555` — IU-10A.

The initial IU-10A push was rejected as non-fast-forward. No force push. No reset/rebase/merge used to destroy history.

#### N — Integration plan

- Divergence was **path-disjoint** (remote touched ADR-023 markdown only; IU-10A touched the two membership files only).
- Approved strategy: remote `53c5a9` → isolated worktree → cherry-pick `0439142` → new commit → fast-forward push.
- The original `0439142` SHA could not be preserved because its parent changed from `9de9ed8` to `53c5a9`.

#### O — Integration execution

- Temporary clean worktree: `G:\WSA-IU10A-integrate` at base `53c5a9caafc9940de244f1367a142e57a1c376b1`.
- Cherry-pick of `0439142` was clean.
- New integrated commit: `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`
- Parent: `53c5a9caafc9940de244f1367a142e57a1c376b1`
- The two IU-10A files were byte-equivalent to the original `0439142` implementation.
- The IU-10A commit did **not** modify ADR-023 markdown.

#### P — Final push

- Remote before push: `53c5a9caafc9940de244f1367a142e57a1c376b1`
- Push: **fast-forward** (no `--force`, no `--force-with-lease`)
- Remote after push: `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`
- Final: HEAD == upstream; ahead/behind = 0/0

#### Q — Final forensic verification

- D-09 present; §8 Preservation Appendix present.
- IU-10A JSON present; IU-10A contract test present.
- Pre-existing WIP before/after remained unchanged.
- IU-01…IU-09 remained untouched.
- No ADR-023 content was rewritten during IU-10A integration.

#### R — Worktree cleanup

- Temporary worktree `G:\WSA-IU10A-integrate` was removed successfully.
- `git worktree list` confirmed it was no longer listed.
- Main worktree remained unchanged except for its pre-existing WIP.

#### S — IU-10A final closure

| Item | Status |
|------|--------|
| IU-10A | **CLOSED** |
| Implementation | COMPLETE |
| Verification | COMPLETE |
| Commit | COMPLETE (`83b5b9b8…`, replay of `0439142d…`) |
| Push | COMPLETE |
| Worktree cleanup | COMPLETE |
| Remote synchronization | COMPLETE |
| Current HEAD at closure | `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b` |

#### Next-unit governance boundary (explicit)

- **No IU-10B** currently exists as an accepted implementation unit.
- The PHP loader was deferred during IU-10A design but was **not** formally accepted as IU-10B.
- **No next implementation unit is authorized by this appendix.**
- A separate design/discovery gate is required before any post-IU-10A implementation begins.
- This appendix does **not** create that next unit.


---

## 8.8 — Detailed Preservation Record: Unarchived IU-10A / D-09 Prompts, Reports, Gates and Conversation Decisions

**Purpose:** This section is an append-only preservation record for the substantive prompts, reports, gate outputs, user approvals, corrections, and execution messages that were produced during the D-09 → IU-10A transition and were not fully represented in the earlier ADR preservation sections. It supplements §8.2–§8.7 and does not delete, rewrite, reinterpret, or replace any earlier record.

**Preservation rule:** Existing ADR text remains immutable historical context. Where an earlier section records a historical status such as “IU-10A not implemented and not authorized,” that wording remains unchanged. The records below establish the later chronology under separate authorization.

### 8.8.1 — IU-10A Implementation Authorization Gate / Forensic Readiness

**Prompt purpose:** Read-only determination of whether IU-10A could proceed after D-09 governance closure.

**Required scope and prohibitions preserved:**
- Verify D-09 is ACCEPTED and establishes the current 109 membership SoT.
- Verify G1=22, G2=25, G3=14, G4=7, G5=19, G6=22, TOTAL=109.
- Verify G2-17 = REMVT, G2-19 = Animal Bioscience, G3-15 removed with no replacement.
- Preserve all intentional G1/G6 dual seats without SAME_AS.
- Inspect IU-01…IU-09 dependencies and verify that no machine-readable 109 membership artifact existed yet.
- Do not implement, mutate ADR-023, create runtime registry behavior, modify Capability Store, Stage-3, CSQ, Builder, Catalog, Compiler, Composer, gates, Results/Viewer, canonical identity, SAME_AS, or sourceKey behavior.
- Do not commit or push.

**Forensic result:** **READY WITH EXPLICIT DESIGN CONDITIONS.**

The gate established that D-09 closed membership governance but did not itself authorize implementation. A separate detailed IU-10A design was required.

### 8.8.2 — IU-10A Detailed Implementation Design Specification

**Design decision preserved:** IU-10A is a governance-owned, machine-readable **CURRENT MEMBERSHIP REGISTER** answering only which seats belong to the D-09 109-source universe.

**Selected representation:** **OPTION E — Hybrid**
- Authoritative: canonical JSON membership manifest.
- Optional derived PHP loader/DTO binder: non-authoritative and deferred.
- No database table.
- No Stage-3 wiring.
- No canonical identity minting.
- No SAME_AS inference.
- No Capability Store population.
- G3-15 must not appear.

**Authoritative artifact path:**
`docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json`

**Contract test:**
`backend/tests/Unit/Agriculture/Research/Membership/Adr023109MembershipRegisterContractTest.php`

**Schema closure preserved:**
- Root: schema_id, governance_reference, membership_revision, adr_baseline_commit, total_current_seats, group_counts, seats, dual_pairs, provenance.
- Per seat: adr_id, group_id, ordinal, display_name, membership_status=CURRENT.
- historical_62_xref omitted in v1.
- dual_peer_adr_id optional.
- canonical_identity_id, sourceKey, adapter, provider, capability, and path are forbidden as runtime-authority fields.
- Fail closed on duplicate/missing IDs, invalid group/ordinal, wrong total, missing dual pair, G3-15 present, forbidden authority fields, or corrupted provenance.
- No silent repair, dedupe, renumbering, inference, or invention of seats.

**Design closure:** implementation remained unauthorized until a separate implementation authorization gate.

### 8.8.3 — IU-10A Design Closure Gate

**Result:** **DESIGN CLOSED — READY FOR IMPLEMENTATION AUTHORIZATION.**

The closure verified:
- complete 109-seat enumeration;
- exact group counts 22/25/14/7/19/22;
- G2-17 = REMVT;
- G2-19 = Animal Bioscience;
- G3-15 absent;
- all 10 intentional G1/G6 dual pairs preserved;
- no SAME_AS;
- no canonical identity sharing;
- exact preserved labels, including G5-11 = “Egyptian Academic Journal of Biological Sciences A — Entomology”;
- historical_62_xref omitted from v1;
- Stage-3 boundary unchanged;
- JSON path fixed as above;
- no PHP loader included in IU-10A;
- no next implementation unit was thereby authorized.

### 8.8.4 — IU-10A Implementation Authorization Prompt and Execution

**Implementation authorization scope:** JSON membership register + fail-closed contract test only.

**Explicitly forbidden during implementation:**
- PHP loader;
- database/migration;
- Source Registry;
- Capability Store;
- Stage-3 wiring;
- AdapterRegistry;
- sourceKey/provider inference;
- canonical identity minting;
- SAME_AS;
- Builder/Catalog/Compiler/Composer/gate changes;
- Results List/Viewer changes;
- ADR-023 mutation;
- unrelated WIP staging;
- commit/push outside the separately scoped commit gate.

**Implementation result:**
Exactly two files:
1. `docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json`
2. `backend/tests/Unit/Agriculture/Research/Membership/Adr023109MembershipRegisterContractTest.php`

### 8.8.5 — IU-10A Verification Report

**Validation results preserved:**
- Node fail-closed validator: PASS.
- PHPUnit: **1 test / 5243 assertions / 0 failures / 0 errors / 0 skipped**.
- 109 seats verified.
- Group counts 22/25/14/7/19/22.
- 10 dual pairs verified.
- G3-15 absent.
- G2-17 = REMVT.
- G2-19 = Animal Bioscience.
- Forbidden runtime/identity authority keys absent.
- Membership artifact remained membership-only.

A separate `git diff --check` observation identified pre-existing trailing whitespace in `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md`; this was unrelated to IU-10A and was not repaired or included.

### 8.8.6 — IU-10A Scoped Commit Record

**Original implementation commit:**
- SHA: `0439142d0a214657e4a2e8c7201159bb7d502555`
- Subject: `fix(research): add ADR-023 109 membership register`
- Exactly the two IU-10A files were committed.
- ADR-023 markdown and ADR-021 were not committed.
- Pre-existing WIP remained untouched.

### 8.8.7 — Remote Divergence Forensic Report

After the D-09 commit chain, the expected remote and local histories diverged:

- Remote-only commit: `53c5a9caafc9940de244f1367a142e57a1c376b1`
  - D-09 Preservation Appendix.
- Local-only commit: `0439142d0a214657e4a2e8c7201159bb7d502555`
  - IU-10A membership register + contract test.
- Common ancestor: `9de9ed874510dad1970abe14b2aadb4441e9137a`.

The initial IU-10A push was correctly rejected as non-fast-forward.

**Safety decisions preserved:**
- no force push;
- no force-with-lease;
- no reset;
- no rebase;
- no destructive merge;
- no deletion of either branch history.

The forensic comparison established that the two commits were path-disjoint.

### 8.8.8 — IU-10A Integration Plan

**Approved strategy:**
`9de9ed8 → 53c5a9 → NEW-IU10A`

Execution model:
1. Use an isolated clean worktree.
2. Start at remote `53c5a9`.
3. Cherry-pick local `0439142`.
4. Verify clean replay.
5. Resulting commit necessarily receives a new SHA because its parent changes.
6. Move the working branch reference safely.
7. Synchronize ADR-023 working-tree state without touching protected WIP.
8. Push only after remote ancestry is reverified.

No merge commit was required.

### 8.8.9 — IU-10A Integration Execution Report

Temporary worktree:
`G:\WSA-IU10A-integrate`

Base:
`53c5a9caafc9940de244f1367a142e57a1c376b1`

The cherry-pick of `0439142` completed cleanly.

Resulting integrated commit:
`83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`

Parent:
`53c5a9caafc9940de244f1367a142e57a1c376b1`

The two IU-10A files were verified byte-equivalent to the original implementation. ADR-023 markdown was not changed by the IU-10A implementation commit.

### 8.8.10 — IU-10A Final Sync / Push / Cleanup

**Final push before Preservation Appendix:**
- Remote before push: `53c5a9caafc9940de244f1367a142e57a1c376b1`
- Remote after push: `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`
- Fast-forward only.
- HEAD == upstream.
- Ahead/behind = 0/0.
- No force operation.

The temporary worktree `G:\WSA-IU10A-integrate` was then removed and verified absent.

### 8.8.11 — Post-IU-10A Transition & Preservation Forensic Report

The post-IU-10A read-only audit established:
- HEAD/upstream = `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`.
- Ahead/behind = 0/0.
- Pre-existing dirty/untracked WIP remained untouched.
- IU-10A was CLOSED in Git reality.
- ADR-023 preservation narrative initially lagged behind the implementation history.
- D-09 statements remained valid as D-09-era scope/history and must not be rewritten.
- No accepted IU-10B existed.
- PHP loader remained deferred and was not IU-10B.
- The exact next gate was an append-only ADR-023 Preservation Appendix update before any next implementation unit.

### 8.8.12 — Preservation Appendix Mutation Forensic Report

**Mutation boundary:**
Only:
`docs/architecture/ADR-023-scientific-source-expansion.md`

**Mutation:**
`117 insertions / 0 deletions`

**Result:**
- §8.7 added.
- H–S records present.
- D-06, D-09, §8.2–§8.6 and earlier records untouched.
- Historical/current status distinction explicitly recorded.
- IU-10A CLOSED at `83b5b9b8...`.
- No IU-10B accepted.
- PHP loader deferred.
- No next implementation unit authorized.
- ADR-023 remained the only working-tree mutation for this gate.
- Nothing was staged at the time of this report.

### 8.8.13 — IU-10A Preservation Appendix Commit + Push Report

The preservation mutation was subsequently committed and pushed separately.

**Commit:**
- SHA: `924c80c1eb35d8fcaa8382d91f9df27d6af19639`
- Parent: `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`
- Subject: `docs(research): preserve IU-10A closure record`
- File: `docs/architecture/ADR-023-scientific-source-expansion.md`
- Change: +117 / -0.

**Push:**
- Remote before: `83b5b9b8b2c6c77bd53e8e9aa1303c047607775b`
- Remote after: `924c80c1eb35d8fcaa8382d91f9df27d6af19639`
- Fast-forward successful.
- HEAD == upstream.
- Ahead/behind = 0/0.
- No force push, rebase, reset, or merge.
- Pre-existing WIP remained uncommitted.

### 8.8.14 — Exact Commit + Push Prompt Preserved

The scoped commit/push gate required:
- inspect `git status --short`;
- inspect only ADR-023 diff;
- verify +117/-0 and `git diff --check`;
- stage only ADR-023 using `git add -- <exact path>`;
- reject `git add -A`, `git add .`, `git commit -am`;
- verify cached diff contains only §8.7;
- commit as `docs(research): preserve IU-10A closure record`;
- verify the commit contains only ADR-023;
- verify remote ancestry before push;
- push normally only if remote remained at `83b5b9b8...`;
- verify HEAD == upstream and 0/0;
- stop after the task without starting IU-10B.

### 8.8.15 — Conversation-Level Governance Messages Preserved

The following substantive conversation decisions are preserved as architectural messages rather than omitted chat context:

1. The user explicitly required that all unpreserved prompts, reports, and substantive messages be saved **inside ADR-023** and that **nothing old be deleted**.
2. The user confirmed approval of the final D-09 governance wording before mutation.
3. The project path was deliberately kept unchanged: governance → design → authorization → implementation → verification → forensic integration → preservation; no jump to accuracy fixes or unrelated architecture.
4. The user required preservation of historical records even when later status superseded them.
5. The user required strict separation between membership, source identity, capability, path, Stage-3 runtime identity, and canonical scientific identity.
6. The user required preservation of pre-existing WIP and prohibition of destructive Git operations.
7. The user required that no next implementation unit be inferred merely because a deferred PHP loader exists.
8. The current stopping point after the preservation commit is:
   - branch `phase-18-m18-ai-marketing-communications`;
   - HEAD/remote `924c80c1eb35d8fcaa8382d91f9df27d6af19639`;
   - IU-10A CLOSED;
   - no IU-10B authorized;
   - PHP loader deferred;
   - next unit requires a separate discovery/design/authorization gate.

### 8.8.16 — Preservation Source Index

The following preserved conversation artifacts were used to reconstruct the unarchived chain and remain available as supporting records:

- `Pasted markdown(20261001-170543).md` — IU-10A implementation authorization / forensic readiness.
- `Pasted text(20261001-171038).txt` — IU-10A detailed implementation design.
- `Pasted text(20261001-171525).txt` — IU-10A design closure / implementation preconditions.
- `Pasted markdown(20261001-184017).md` — POST-IU-10A transition and preservation forensic report.
- `ADR-023-Scientific-Source-Expansion-Full-Source-Architecture-PRESERVED-20261001-v4.docx` — preserved 109-source enumeration baseline.
- Earlier preserved ADR-023 v2/v3 artifacts and the existing `science-source-expansion/` decision/report archive.

**Important:** These supporting artifacts are references to the detailed source records. Their existence does not create a second architectural authority. ADR-023 remains the single authoritative architectural path.

### 8.8.17 — Final Append-Only Preservation Invariant

This section does not delete, rewrite, or replace:
- D-06;
- D-09;
- GO-0 through later accepted decisions;
- §8.2–§8.7;
- historical 62 records;
- current 109 membership governance;
- IU-01…IU-09 closure records.

Later records supersede earlier status only by explicit chronology and governance; they do not erase history.

**Current preservation status:** COMPLETE for the identified D-09/IU-10A post-closure prompt/report/message chain.

**Current architectural status remains:** IU-10A CLOSED. No IU-10B is accepted or authorized. PHP loader remains deferred. No next implementation unit is authorized by this appendix.

## 8.9 — Post-IU-10A Follow-on Necessity Decision and Current Stopping Point

This section is an append-only preservation record for the post-IU-10A discovery/design/decision chain. It preserves the substantive prompts, reports, governance messages, final architectural decision, and stopping point. It does not delete, rewrite, or replace any earlier ADR-023 record.

### 8.9.1 — Next-Unit Discovery Prompt

A read-only gate was issued to determine the actual architectural next step after IU-10A. It required forensic verification of Git and ADR state; verification of the 109 Membership Register; verification of IU-01 through IU-10A closure; inspection of Source Identity, Capability, Path, Projection/C9, Correlation, Disclosure, and Coexistence boundaries; investigation of the PHP Loader; search for existing next-unit references; identification of evidence-backed gaps; and determination of the next read-only/design gate.

The prompt explicitly prohibited file, code, ADR, database, migration, staging, commit, push, reset, rebase, merge, IU-10B creation, and PHP Loader implementation. It required a final report containing Git baseline, ADR preservation state, IU-10A verification, unit status, architecture boundaries, next-unit references, PHP Loader status, downstream dependency analysis, Scientific Source Expansion completion analysis, evidence-backed gaps, candidate gate classification, and one exact next action.

### 8.9.2 — Next-Unit Discovery Report

The resulting report established:

- IU-10A Membership JSON is complete within its designed scope and is a Membership Source of Truth only.
- IU-10A does not populate Source Identity, Capability, Path, Projection, Stage-3, sourceKey, adapter, provider, canonical identity, or SAME_AS state.
- IU-01 through IU-09 remain closed and are not reopened by IU-10A.
- No current backend runtime consumer of the Membership JSON was found.
- Current runtime is not blocked by the unread Membership JSON.
- PHP Loader has no accepted design, ADR authorization, implementation authorization, contract, or runtime consumer; it remains deferred and non-authoritative.
- Future Source Identity, CapVer, Path/Projection, and adapter work is not automatically required for all 109 seats.
- Future runtime work, if authorized later, must be selective or explicitly governed; ALL-109 activation is not implied by membership.
- No IU-10B exists as an accepted unit.
- The immediate state required an architectural decision before any next unit could be defined.

The report also recorded a Git preservation divergence: local HEAD was 924c80c1eb35d8fcaa8382d91f9df27d6af19639 while the actual remote tip was a05a73d2e1b0e6caae14089e9fb5c93fc024e21c. No synchronization was performed because pre-existing WIP was present and Git synchronization was intentionally a separate gate.

### 8.9.3 — Design / Discovery Gate Prompt

A second read-only Design/Discovery Gate was issued. Its purpose was to determine whether a real downstream architectural dependency existed after IU-10A, without assuming PHP Loader or IU-10B.

It required examination of:
- local, tracking, and actual remote Git state;
- D-06, D-09, §8.7 and §8.8 preservation state;
- the 109 Membership Register schema and forbidden runtime-authority fields;
- Source Identity dependency;
- Capability dependency;
- Path/Projection/C9 dependency;
- Stage-3/CGHIA dependency;
- PHP Loader consumer/design/authorization status;
- candidate future units;
- global versus selective scope;
- scientific identity preservation;
- current runtime blocking status.

It explicitly preserved the boundary:
Membership Register ≠ Source Identity ≠ Capability ≠ Path ≠ Projection ≠ Runtime Adapter/Provider ≠ Stage-3 ≠ Scientific Entity Identity.

No implementation, ADR mutation, Git synchronization, IU-10B creation, or PHP Loader coding was permitted.

### 8.9.4 — Design / Discovery Gate Report

The report confirmed:

- Local HEAD/tracking state was 924c80c at inspection.
- Actual remote tip was a05a73d, one commit ahead, containing the remote-only preservation appendix.
- Local §8.7 contained IU-10A closure; remote §8.8 contained the expanded preservation record.
- Membership Register is complete within its intended scope.
- Source Identity population for all 109 seats is not a mandatory consequence of membership.
- 109 membership does not imply 109 CapVer records.
- Path/Projection/C9 are not automatically required per membership seat.
- CGHIA/IU-09 does not require loading all 109 seats into Stage-3.
- PHP Loader has no current runtime consumer and is not architecturally required for current runtime.
- Current runtime is not blocked by unread Membership JSON.
- No accepted next implementation unit exists.
- The classification was CASE D — ARCHITECTURAL DECISION REQUIRED BEFORE A UNIT CAN BE DEFINED.

### 8.9.5 — Follow-on Necessity Decision Gate Prompt

A decision-only gate titled “WSA-Enterprise — POST-IU-10A FOLLOW-ON NECESSITY — ARCHITECTURAL DECISION GATE — READ ONLY” was issued.

Its primary question was whether the project should begin any new implementation after IU-10A.

The only allowed outcomes were:

DECISION A — NO NEXT IMPLEMENTATION UNIT NOW

or

DECISION B — OPEN DESIGN CLOSURE FOR ONE SPECIFIC FOLLOW-ON UNIT

The gate evaluated these evidence-backed possibilities without ranking:
A. Keep Membership Register as docs/config Source of Truth.
B. Membership Loader / read-only binder.
C. Runtime Source Identity population.
D. Capability Verification campaign.
E. Path/Projection/C9 campaign.
F. Stage-3 / Adapter integration.
G. Other evidence-backed architectural unit.
H. No new unit now.

The gate prohibited ranking, scoring, inventing IU-10B, implementing an option, modifying ADR-023, synchronizing Git, or treating a deferred idea as authorization.

### 8.9.6 — Final Architectural Decision

The final report reached:

DECISION A — NO NEXT IMPLEMENTATION UNIT NOW

Evidence:

1. IU-10A is complete within its Option E membership-only scope.
2. The 109 JSON is a Membership Source of Truth, not a runtime source registry.
3. No backend/app consumer of the Membership JSON exists.
4. Current runtime is not blocked by unread Membership JSON.
5. IU-01 through IU-09 do not mandate 109-to-runtime population.
6. PHP Loader is deferred and unauthorized.
7. No accepted IU-10B exists.
8. No non-deferrable dependency forces immediate implementation.
9. Future Identity, CapVer, Path/Projection, and adapter work can be opened selectively under separate design and authorization gates.
10. Leaving the system in the current state does not create an architectural correctness defect.

Decision B was not used because there is no current requirement, dependency, accepted design, or named unit boundary that forces a Design Closure now.

### 8.9.7 — Final Architectural Boundary

The following separation remains authoritative:

Membership Register
≠ Source Identity
≠ Canonical Identity
≠ Capability
≠ Path
≠ Projection
≠ Provider
≠ Adapter
≠ Stage-3
≠ sourceKey
≠ CSQ
≠ Scientific Entity Identity

The 109 seats are governed membership seats, not automatically executable runtime sources. No automatic activation of the 109 seats is authorized.

Any future runtime expansion must be opened by a separate architectural/design/authorization chain and must preserve scientific identity, CSQ identity, provenance, capability truth, and the existing Stage-3/CGHIA boundaries.

### 8.9.8 — CURRENT PROJECT STOPPING POINT

STOPPING POINT — 2026-10-01

- Scientific Source Expansion governance baseline: established.
- Current Membership Source of Truth: 109 seats, governed by D-09.
- Historical 62 universe: remains separate historical lineage and traceability domain.
- IU-01 through IU-09: CLOSED.
- IU-10A: CLOSED.
- IU-10A Membership Register: COMPLETE within scope.
- IU-10A JSON: Membership Source of Truth only; no runtime registry authority.
- PHP Loader: DEFERRED / NOT AUTHORIZED.
- IU-10B: NOT CREATED / NOT ACCEPTED / NOT AUTHORIZED.
- Runtime Source Identity population for 109: NOT AUTHORIZED.
- 109-seat CapVer campaign: NOT AUTHORIZED.
- 109-seat Path/Projection campaign: NOT AUTHORIZED.
- 109-seat Stage-3/Adapter activation: NOT AUTHORIZED.
- Current runtime blocker caused by Membership JSON: NONE.
- Next implementation unit: NONE AUTHORIZED NOW.
- Next action: STOP and wait for a future evidence-backed architectural need.

### 8.9.9 — Git State at Decision Time

The decision report recorded:

- Branch: phase-18-m18-ai-marketing-communications.
- Local HEAD: 924c80c1eb35d8fcaa8382d91f9df27d6af19639.
- Tracking HEAD: 924c80c.
- Actual remote at inspection: a05a73d2e1b0e6caae14089e9fb5c93fc024e21c.
- Remote was one commit ahead of local at that inspection.
- Pre-existing WIP remained untouched.
- No fetch, pull, merge, rebase, reset, staging, commit, or push was performed by the read-only decision gate.

This Git divergence is a preservation/synchronization issue separate from the architectural decision. It does not authorize implementation work.

### 8.9.10 — User Stop and Preservation Instruction

The user explicitly requested to stop at this point and preserve all relevant prompts, reports, substantive governance messages, the final architectural decision, and the exact stopping point inside ADR-023 without deleting any previous content.

This section fulfills that preservation requirement by appending the post-IU-10A decision chain to ADR-023 without deleting or rewriting earlier records.

Preservation invariant: future work must continue from this stopping point. No later action may silently treat PHP Loader as IU-10B, activate all 109 seats, or reopen IU-01 through IU-09 without an explicit new architectural gate and authorization.

CURRENT ARCHITECTURAL STOP:
IU-10A CLOSED → FOLLOW-ON NECESSITY DECISION A → NO NEXT IMPLEMENTATION UNIT NOW

No implementation is authorized from this stopping point.

---

### D-10 — Runtime Activation Architecture (Post-IU-10A)

**Status:** ACCEPTED WITH NON-BLOCKING CLARIFICATIONS  
**Date:** 2026-10-02  
**Scope:** Architectural governance for future selective runtime activation of D-09 / IU-10A membership seats. **Does not authorize implementation, population, adapters, loaders, migrations, Selector changes, Stage-3 changes, or any source activation.**

**Chronology note:** §8.9 records Follow-on Necessity **DECISION A — NO NEXT IMPLEMENTATION UNIT NOW**. D-10 does **not** revoke that stopping point for implementation units. D-10 records accepted **architecture** only. Architecture acceptance ≠ implementation authorization. No Implementation Unit is authorized by D-10 alone.

#### Decision

##### D-10.1 — Scope

1. D-10 governs future **selective** runtime activation of governed ADR membership seats.
2. D-10 does **not** activate any source.
3. D-10 does **not** authorize implementation.
4. D-10 does **not** authorize IU-10B.
5. D-10 does **not** authorize a PHP Loader.

##### D-10.2 — Activation Strategy

1. Runtime eligibility is **Capability-first**.
2. Onboarding is **source-by-source**.
3. The sole atomic runtime onboarding unit is a single **`adr_id`**.
4. Group / Wave labels are **governance packaging only**.
5. Group / Wave must **not** batch-promote Identity, Capability, Path, Projection, Binding, Activation, or Selector participation.
6. **All-109** activation as one operation is **FORBIDDEN**.
7. Membership enumeration alone **never** confers runtime eligibility.

##### D-10.3 — Identity Binding

1. The Membership JSON remains **membership authority only**.
2. Runtime binding requires **separate governed artifacts**.
3. Display-name matching alone is **insufficient** for binding.
4. `canonical_identity_id` remains **NULL** until explicit SAME_AS or resource-unification evidence is recorded.
5. No silent identity binding. No silent SAME_AS.
6. Distinct namespaces remain distinct: `adr_id`, `canonical_identity_id`, external identity, Stage-3 `sourceKey`, aggregator identity, provider identity, protocol endpoint, source resource, and article/record.
7. `Stage3SourceKeyIdentityBridge` consumes **explicit bindings only**. It must not mint identity. It must not mint SAME_AS.

##### D-10.4 — Dual Seats

1. G1 ↔ G6 dual seats remain **independent identities by default**.
2. Membership `dual_pairs` ≠ SAME_AS.
3. Shared adapter implementation, provider, or endpoint **may** be reused without merging seats.
4. Shared `canonical_identity_id` requires explicit SAME_AS / resource-unification evidence.
5. Shared Capability subject must **not** be assumed from dual membership.

##### D-10.5 — Capability Evidence Authority

1. Missing Cap record = **UNVERIFIED** (never UNAVAILABLE by absence alone).
2. STALE evidence must **not** auto-promote to VERIFIED.
3. Required capabilities use **AND** semantics.
4. License / access / reuse remain separate dimensions.
5. `content_about` ≠ `query_constrainable`.
6. EMPTY_RESULT ≠ CAP_UNAVAILABLE.
7. Automated probes and documentation are **supporting evidence inputs**, not CapVer.
8. Only the abstract **Governed Verification Authority (CapVer)** may assign Cap state **VERIFIED**.
9. Concrete organizational ownership of CapVer remains **DEFERRED**.
10. Cap state alone never equals Activation **ACTIVE**.

##### D-10.6 — Population Model

1. Membership Source of Truth remains the IU-10A **JSON** register.
2. Runtime binding / activation / capability require separate governed artifacts.
3. Future population may use onboarding manifests and/or persistent stores under later authorization.
4. PHP Loader remains **DEFERRED / NOT AUTHORIZED** and is **not** the default population mechanism.

##### D-10.7 — Stage-3 / Selector Entry

1. New ADR seats must **not** automatically enter Selector.
2. New ADR seats must **not** automatically enter Internet-First defaults.
3. Adapter / `sourceKey` registration ≠ activation.
4. Adapter registration ≠ Selector default membership.
5. Existing Stage-3 Internet-First behavior must **not** regress.
6. Runtime retrieval participation requires the governed chain: Identity → Capability → Path → Projection → Binding → Activation → coexistence allowance.

##### D-10.8 — Per-Seat Integration Classification

1. Every seat requires a **verified per-seat integration classification** artifact, separate from Membership JSON, before runtime binding.
2. No protocol, API, OAI-PMH, bulk, license, or aggregator class may be inferred from display name, website existence, membership metadata, similarity, or assumption.
3. The populated 109-seat integration inventory remains **OPEN**.
4. D-10 does **not** populate that inventory.

##### D-10.9 — Scientific Fidelity

1. CSQ remains **semantic authority**.
2. `ScientificSearchQueryBuilder` remains **scholarly lexical construction authority**.
3. `ScientificQueryCompiler` remains **metadata-only** (not HTTP execution authority).
4. `AgriculturalEntityCatalog` is **not** a Source Registry.
5. Projection + C9 enforce fidelity; unsupported / omitted / unresolved facets must remain explicit.
6. No silent scientific identity loss. No silent facet loss.

##### D-10.10 — Activation Safety Gate (conceptual)

1. Conceptual activation states include: NOT_READY, UNVERIFIED, CONDITIONALLY_ELIGIBLE, ELIGIBLE, ACTIVE.
2. Operational / governance side states include: SUSPENDED, DEFERRED.
3. Activation state is orthogonal to Cap v2 states, Path `eligibility_state`, and PathStatus.
4. Membership must **never** transition directly to ACTIVE.

##### D-10.11 — Onboarding Contract

1. An accepted **Onboarding Contract** is **mandatory** before the first runtime binding or activation of any ADR seat.
2. Atomic unit = single `adr_id`.
3. All-109 is **not** an atomic activation operation.
4. Group / Wave remain governance labels only.

##### D-10.12 — Persistence Direction

1. Membership remains file-backed JSON SoT.
2. Future durable domains may include Identity, Capability, Path, Projection, Binding, Activation, and Correlation when authorized.
3. Concrete schemas are **not** authorized by D-10.
4. Database migrations are **not** authorized by D-10.

##### D-10.13 — Deferred Items

- Concrete CapVer organizational ownership
- 109 per-seat integration inventory population
- PHP Loader
- Concrete DB schemas and migrations
- First-seat onboarding execution
- Runtime activation of any seat
- Second-lane Selector implementation detail
- Any Implementation Unit (including any IU-10B)

##### D-10.14 — Explicit Non-Goals

D-10 does **not** authorize: adapters; Selector changes; Stage-3 modification; Capability population; Identity population; Path population; Projection population; `sourceKey` creation; SAME_AS minting; runtime activation; database migration; PHP Loader; IU-10B; Onboarding Contract implementation; commit; or push.

##### D-10.15 — Future Implementation Boundary

Any implementation requires:

1. an **Onboarding Contract Design Gate**, then
2. a separate **Implementation Authorization Gate**.

D-10 itself does **not** grant implementation authorization.

#### Invariants (non-negotiable)

Membership ≠ Runtime; Membership ≠ Identity; Identity ≠ Scientific Entity; Capability ≠ Membership; Missing Cap = UNVERIFIED; UNVERIFIED ≠ UNAVAILABLE; EMPTY_RESULT ≠ CAP_UNAVAILABLE; no silent SAME_AS; dual_pairs ≠ SAME_AS; no automatic canonical identity minting; CSQ semantic authority; ScientificSearchQueryBuilder lexical authority; AgriculturalEntityCatalog ≠ Source Registry; ScientificQueryCompiler ≠ HTTP executor; no silent scientific facet loss; Projection remains source-native projection authority; C9 remains fidelity authority; no Path / Capability / Projection bypass; no Membership → ACTIVE; no Membership → Selector auto-registration; no new source auto-enters Internet-First; Stage-3 Internet-First must not regress; no silent ADR seat ↔ Stage-3 `sourceKey` binding; Historical 62 ≠ current 109; 109 Membership ≠ 109 Runtime; PHP Loader remains deferred unless separately authorized; architecture acceptance ≠ implementation authorization; D-10 acceptance ≠ source activation / Cap / Identity / Path / Projection population / adapter creation / Selector or Stage-3 modification; no Implementation Unit is authorized by D-10 alone.

#### Deferred

Concrete CapVer organizational ownership; 109 integration inventory population; PHP Loader; DB migrations / concrete store schemas; first-seat onboarding; any Implementation Unit; second-lane Selector detail.

#### Explicit non-goals

This decision does not authorize adapters, Selector changes, Stage-3 modification, Cap/Identity/Path/Projection population, `sourceKey` creation, SAME_AS minting, runtime activation of any seat, PHP Loader, IU-10B, or commits/pushes.

#### Future activation boundary

Any implementation requires a separate Implementation Authorization Gate after Onboarding Contract acceptance.

---

## 8.10 — D-10 Acceptance Chain Preservation Record (Post-§8.9)

**Purpose:** Append-only preservation of the post-§8.9 Runtime Activation Architecture chain that produced D-10. This subsection does **not** rewrite D-09, §8.7–§8.9, DECISION A’s historical stopping point, or any earlier record. It is **not** a second D-10 decision.

### 8.10.1 — Post-IU-10A Runtime Activation Discovery

A read-only forensic discovery established that IU-01…IU-09 contracts exist, IU-10A Membership Register is membership-only, Stage-3 runs a fixed small adapter set, 109 seats are not populated as runtime Identity/Cap/Path/Projection records, and no complete governed 109 activation lifecycle exists. Classification: CASE D — multiple architectural decisions required before implementation.

### 8.10.2 — Runtime Activation Architecture Decision Gate

A decision-only gate formulated Decisions A–L (strategy, binding, CapVer, population, Stage-3 entry, dual seats, persistence, adapter/protocol model, fidelity, activation gate, onboarding granularity, future boundaries) as **PROPOSED**, without implementation.

### 8.10.3 — Runtime Activation Architecture Acceptance Gate

Acceptance outcome: **ARCHITECTURE ACCEPTED WITH NON-BLOCKING CLARIFICATIONS**. Clarifications recorded for strategy packaging, CapVer organizational ownership (deferred), persistence direction without migration, per-seat integration inventory (OPEN), and activation-state orthogonality. **Implementation authorized: NO** for all decisions.

### 8.10.4 — Remote Divergence and Preservation Sync

D-10 mutation was initially blocked because live remote tip `d585e90…` was ahead of local `924c80c…` with append-only §8.8 + §8.9 (498 insertions / 0 deletions, ADR-only). After Remote Divergence Forensic (CASE A) and Sync Design, an Execution Gate fast-forwarded local HEAD to `d585e90…` without touching WIP, without new commit, and without push. D-10 remained absent until this mutation.

### 8.10.5 — D-10 Mutation Boundary

D-10 is appended as governance only. No Membership Register change. No runtime change. No IU-10B. No PHP Loader. No source activation. No commit/push authorized by the mutation gate alone.

**Preservation invariant:** §8.9 DECISION A remains the historical record that no next **implementation unit** was authorized at that stop. D-10 adds accepted **architecture** for future selective activation and still requires Onboarding Contract Design and Implementation Authorization before any unit may begin.

---

## 8.11 — Onboarding Contract Acceptance / ADR Preservation Record

**Purpose:** Append-only preservation of the Onboarding Contract Design that was reviewed under the Adversarial Acceptance Gate and recommended **ACCEPTED WITH NON-BLOCKING CLARIFICATIONS**.

**This subsection is a preservation record.** It does **not**:
- create a new Implementation Unit;
- authorize implementation;
- authorize runtime activation or source activation;
- create IU-10B;
- authorize a PHP Loader;
- alter D-09, D-10, IU-01 through IU-10A, or §8.7–§8.10;
- rewrite historical decisions;
- mutate Membership Register contents;
- authorize concrete persistence schemas or migrations.

**Chronology:** Follows D-10 and §8.10. §8.9 DECISION A (no next implementation unit at that stop) remains historical record. Design acceptance ≠ implementation authorization.

### 8.11.1 — Acceptance Result

| Item | Value |
|------|--------|
| Acceptance | **ACCEPTED WITH NON-BLOCKING CLARIFICATIONS** |
| Blocking issues | **NONE** |
| Adversarial tests | **38 PASS** / **2 PARTIAL** / **0 FAIL** |

**PARTIAL (not rewritten as PASS):**

1. **Test #38 — Future Implementability — PARTIAL** pending frozen Field-Ownership Matrix (this section).
2. **Test #39 — Field Completeness — PARTIAL** pending frozen Field-Ownership Matrix and explicit post-ACTIVE mutation rules (this section).

### 8.11.2 — Accepted Scope of the Onboarding Contract

The Onboarding Contract is **ORCHESTRATION + AUDIT** only.

It coordinates existing authorities. It does **not** replace:

Membership · Source Identity · Capability · Path · Projection · C9 · Coexistence · Selector · ScientificSearchQueryBuilder · AgriculturalEntityCatalog · ScientificQueryCompiler · Composer · R6 · B7.

It is the governed precondition for **individual** `adr_id` runtime binding/activation decisions when later implemented and authorized. It is **not** Membership authority and **not** a Runtime Registry.

### 8.11.3 — Field-Ownership Matrix (FROZEN)

Ownership classes used below: **AUTHORITATIVE** | **DERIVED** | **REFERENTIAL** | **GOVERNANCE-ONLY** | **RUNTIME-RELEVANT** | **AUDIT-ONLY**.

| Field | Ownership | Owning authority | Req / Opt / Null | Derived? | Runtime? | Persist later? | Audit? | Immutable? | Mutation / re-verification |
|-------|-----------|------------------|------------------|----------|----------|----------------|--------|------------|----------------------------|
| `onboarding_decision_id` | AUTHORITATIVE + AUDIT-ONLY | Onboarding decision record (orchestration id) | REQUIRED | No | Yes | Yes | Yes | **Immutable** after mint | Never rewrite; supersede via new decision id |
| `adr_id` | REFERENTIAL → Membership/Identity boundary | D-09 / IU-10A Membership; IU-01 identity subject | REQUIRED | No | Yes | Yes | Yes | **Immutable** in a decision | Seat change = new decision |
| `canonical_identity_id` | AUTHORITATIVE (Identity) / REFERENTIAL here | IU-01 Source Identity | OPTIONAL; **NULL default** | No | Yes | Yes | Yes | Mutable only via governed SAME_AS / identity decision | Re-verify identity; no silent fill |
| `identity_evidence_ref[]` | AUTHORITATIVE (Identity) / REFERENTIAL here | IU-01 | REQUIRED before binding | No | Yes | Yes | Yes | Historical refs immutable | New evidence = new/versioned decision |
| `integration_classification_ref` | AUTHORITATIVE for classification artifact / REFERENTIAL here | Per-seat integration classification (D-10.8) | REQUIRED before binding | No | Yes | Yes | Yes | Versioned | Material change → re-verify before ACTIVE |
| `capability_decision_identity` | AUTHORITATIVE (Capability) / REFERENTIAL here | IU-02 | REQUIRED for eligibility | No | Yes | Yes | Yes | Historical immutable | New Cap decision id on material Cap change |
| `capability_dimension_states` | AUTHORITATIVE (Capability) / REFERENTIAL here | IU-02 Cap v2 | REQUIRED (AND set) | No (asserted by Cap) | Yes | Yes | Yes | Current state may change; history preserved | Stale/material change → re-verification; no auto VERIFIED |
| `capability_evidence_ref[]` | AUTHORITATIVE (Capability) / REFERENTIAL here | IU-02 | REQUIRED | No | Yes | Yes | Yes | Historical immutable | Append/version; do not overwrite history |
| `access_methods[]` | AUTHORITATIVE (Capability access dims) / REFERENTIAL here | IU-02 `CapabilityAccessMethod` vocabulary | REQUIRED as claimed | No | Yes | Yes | Yes | Versioned | Claiming new method requires CapVer evidence |
| `license_state` | AUTHORITATIVE (license domain) / REFERENTIAL here | License evidence authority (governed) | REQUIRED for intended use | No | Yes | Yes | Yes | Versioned | Change may force SUSPENDED; ≠ access/reuse |
| `access_state` | AUTHORITATIVE (access domain) / REFERENTIAL here | Access evidence authority | REQUIRED | No | Yes | Yes | Yes | Versioned | Orthogonal to license/reuse |
| `reuse_state` | AUTHORITATIVE (reuse domain) / REFERENTIAL here | Reuse evidence authority | REQUIRED | No | Yes | Yes | Yes | Versioned | Orthogonal to license/access |
| `path_id` | AUTHORITATIVE (Path) / REFERENTIAL here | IU-03 | REQUIRED when ELIGIBLE+ | No | Yes | Yes | Yes | Historical immutable | Invalid path → new path decision |
| `path_decision_identity` | AUTHORITATIVE (Path) / REFERENTIAL here | IU-03 | REQUIRED when path selected | No | Yes | Yes | Yes | Historical immutable | Supersede; do not rewrite |
| `path_eligibility_state` | **DERIVED** from Cap AND (IU-03 mapper) | IU-03 Path eligibility | DERIVED | **Yes** | Yes | Optional | Yes | Recomputed | Recompute on Cap change; **not** D-10 activation_state |
| `path_status` | AUTHORITATIVE (Path selection status) / may be suggested | IU-03 PathStatus | Per Path policy | Partial | Yes | Optional | Yes | Selection mutable under Path rules | **Not** activation_state |
| `projection_identity` | AUTHORITATIVE (Projection) / REFERENTIAL here | IU-04 | REQUIRED when ELIGIBLE+ | No | Yes | Yes | Yes | Historical immutable | Material change → new projection identity |
| `fidelity_class` | AUTHORITATIVE (C9/Projection) / REFERENTIAL here | IU-04/05 C9 | REQUIRED with projection | Aggregated per C9 rules | Yes | Yes | Yes | Re-evaluate on projection change | No EXACT without valid C9 basis |
| `facet_accounting` | AUTHORITATIVE (Projection) / REFERENTIAL here | IU-04 | REQUIRED with projection | No | Yes | Yes | Yes | Versioned with projection | Identity-critical loss cannot be hidden |
| `stage3_relationship` | AUTHORITATIVE (Coexistence) / REFERENTIAL here | IU-09 | OPTIONAL | No | Yes | Yes | Yes | Versioned | Explicit Bridge bindings only; no silent ADR bind |
| `selector_eligibility` | GOVERNANCE-ONLY until separately authorized | Selector / Stage-3 governance | OPTIONAL | Policy | Conditional | Later | Yes | Policy-controlled | **Not** Membership-driven; no auto Internet-First |
| `activation_state` | AUTHORITATIVE (D-10 lifecycle) | D-10 activation lifecycle | REQUIRED | No | Yes | Yes | Yes | Transition-controlled only | Explicit governed transition; never Membership→ACTIVE |
| `group_wave_label` | GOVERNANCE-ONLY | Governance packaging | OPTIONAL | No | No | Optional | Yes | Mutable as label only | **Cannot** promote Cap/Path/Activation/Selector |
| `decision_actor` | AUDIT-ONLY | Onboarding audit | REQUIRED | No | No | Yes | Yes | **Immutable** after decision | Corrections via new decision |
| `decision_timestamp` | AUDIT-ONLY | Onboarding audit | REQUIRED | No | No | Yes | Yes | **Immutable** after decision | Never backdate |
| `rationale` | AUDIT-ONLY | Onboarding audit | REQUIRED | No | No | Yes | Yes | Immutable in record; supersede | New decision for revised rationale |
| `staleness_policy_ref` | REFERENTIAL / GOVERNANCE | Cap freshness policy (IU-02 B4 aligned) | REQUIRED | No | Yes | Yes | Yes | Versioned | STALE ≠ VERIFIED; triggers re-verify |
| `correlation_hooks` | REFERENTIAL → B7 | IU-06/07 Correlation | OPTIONAL | No | Yes | Later | Yes | Per B7 lifecycle | Must not invent competing correlation SoT |

**Authority ownership rule (frozen):** Onboarding Contract fields that are REFERENTIAL **point to** Membership, Identity, Capability, Path, Projection/C9, Coexistence, Selector, or B7. The Contract does **not** redefine those authorities.

### 8.11.4 — Namespace Separation (FROZEN)

Similar English words do **not** imply shared semantic authority.

| Namespace | Values (representative) | Authority | Must not be treated as |
|-----------|-------------------------|-----------|------------------------|
| **D-10 `activation_state`** | NOT_READY, UNVERIFIED, CONDITIONALLY_ELIGIBLE, ELIGIBLE, ACTIVE, SUSPENDED, DEFERRED | D-10 activation lifecycle | Path eligibility; Cap v2; FAOSTAT Stage-3 activation |
| **Path `eligibility_state`** | ELIGIBLE, CONDITIONAL, INELIGIBLE, DEFERRED | IU-03 Path Model | D-10 activation_state |
| **Path `PathStatus`** | SELECTED, CONDITIONALLY_SELECTED, DEFERRED_PENDING_CAPABILITY | IU-03 Path selection status | D-10 activation_state; Cap v2 |
| **Capability v2 `capability_state`** | VERIFIED, PARTIAL, UNVERIFIED, UNAVAILABLE, NOT_APPLICABLE | IU-02 Capability Store | Activation; PathStatus |
| **Stage-3 FAOSTAT `FaoStatActivationPolicy::activation_state`** | FAOSTAT/QCL policy-specific values in Stage-3 adapters | Stage-3 / FAOSTAT runtime policy only | Global D-10 activation_state; Membership eligibility |

**Rule:** Cap VERIFIED alone ≠ Path ELIGIBLE ≠ Path SELECTED ≠ D-10 ACTIVE ≠ FAOSTAT activation_state.

### 8.11.5 — Post-ACTIVE / Immutability Rules (FROZEN)

**Immutable after a decision is recorded:**
`onboarding_decision_id`, `adr_id`, `decision_timestamp`, `decision_actor`, original identity evidence references, original `capability_decision_identity`, original `path_decision_identity`, original `projection_identity` (as recorded for that decision).

**Mutable / re-verifiable (versioned; do not rewrite history):**
capability evidence and current Cap states; access / license / reuse states; endpoint/provider evidence; staleness evidence; runtime readiness; path validity; projection validity; fidelity assessment.

**Transition-controlled only:**
`activation_state` (explicit governed events only). Material triggers may require SUSPENDED or DEFERRED (capability stale, license change, endpoint/provider change, projection invalid, material runtime failure, identity ambiguity).

**Historical integrity:**
Do not rewrite past decisions to make later evidence appear contemporaneous. Corrections use new decision / supersession / versioning under a future persistence design (**not authorized here**).

**Identity:** `canonical_identity_id` remains NULL by default; no automatic SAME_AS.
**Capability:** STALE ≠ VERIFIED; no silent retention of VERIFIED under material change.
**Path:** Path authority remains IU-03.
**Projection/C9:** material change may require new projection identity; identity-critical loss cannot be silently cleared to EXACT.
**License / access / reuse:** remain separate; one does not rewrite the others.

### 8.11.6 — Safety Invariants Preserved

1. Membership Register = membership authority only (not Runtime / Cap / Provider / Activation / Onboarding / Selector registry).
2. No automatic 109 onboarding or activation.
3. Atomic unit = one `adr_id`. Group/Wave = GOVERNANCE-ONLY.
4. Dual seats independent by default; shared adapter/provider/endpoint ≠ shared identity/Cap/path/activation.
5. Stage-3 coexistence preserved; no automatic selector expansion.
6. B7 remains correlation authority; onboarding may reference existing keys only.
7. EMPTY_RESULT ≠ CAP_UNAVAILABLE. Fallback must not increase scientific fidelity. Aggregator = EXTERNAL_DEPENDENCY when applicable.
8. CSQ semantic authority; Builder lexical authority; Compiler metadata-only; Catalog ≠ Source Registry.

### 8.11.7 — Non-Blocking Clarifications (accepted)

1. Vocabulary collision risk among D-10 activation / Path eligibility / PathStatus / Cap v2 / FAOSTAT `activation_state` — resolved by §8.11.4 namespace freeze (no runtime rename in this gate).
2. CapVer organizational ownership remains **DEFERRED**.
3. 109 per-seat integration inventory remains **OPEN / DEFERRED** (population not performed here).
4. Design lived in gate reports until this preservation; this section freezes it in ADR-023.
5. Formal Field-Ownership Matrix and post-ACTIVE rules frozen herein (closes Tests #38/#39 PARTIAL for preservation purposes; does **not** authorize implementation).

### 8.11.8 — Deferred / Not Authorized

| Item | Status |
|------|--------|
| CapVer organizational ownership | DEFERRED |
| 109 integration inventory population | OPEN / DEFERRED |
| PHP Loader | DEFERRED / NOT AUTHORIZED |
| IU-10B / any IU number | NOT AUTHORIZED / NOT CREATED |
| Concrete persistence schema / migrations | DEFERRED |
| Selector second lane | DEFERRED |
| Runtime onboarding implementation | NOT AUTHORIZED |
| Source / runtime activation | NONE |
| Membership JSON mutation | NOT AUTHORIZED by this record |

### 8.11.9 — Acceptance ≠ Implementation / Activation

Architectural design acceptance with non-blocking clarifications means **design accepted**. It does **not** mean implementation authorized, IU assigned, PHP Loader authorized, Membership→runtime eligibility, or any seat ACTIVE.

Any future implementation requires separate **Implementation Design** and **Implementation Authorization** gates after this preservation is committed/pushed under later gates.

## 8.12 — FS-01-ID Implementation, Commit, Push, and Stopping-Point Preservation Record

**Record type:** PRESERVATION / TRACEABILITY RECORD

This section preserves the historical execution chain and does not create a new architectural authority or implementation authorization.

### 8.12.1 — Scope

Preserves the FS-01-ID (Identity Binding Persistence) chain from scope reconciliation through defect repair, verification, atomic commit, push, and intentional stop. Does **not** authorize CapVer, Path, Projection, Onboarding implementation, D-10 runtime implementation, IU-10B, PHP Loader, source activation, selector second lane, or protocol-family expansion.

### 8.12.2 — Discovery / Scope Reconciliation

A prior Commit Gate stopped because `IdentityBindingPersistenceTest.php` was **UNTRACKED**. The assumption of a tracked repair-only delta was therefore **invalid**.

Forensic finding:

- FS-01-ID consisted of exactly **12 untracked files**.
- No prior FS-01-ID commit existed in Git history.
- The persistence test belonged to the new FS-01-ID unit (not a post-baseline repair of a tracked file).

Sequencing decision: **CASE A** — complete untracked implementation unit; **one atomic FS-01-ID commit** was correct. A separate repair-only commit was rejected because no prior FS-01-ID baseline commit existed.

### 8.12.3 — Exact 12-File FS-01-ID Unit

1. `backend/database/migrations/2026_10_02_160000_create_cghia_identity_binding_records_table.php`
2. `backend/app/Models/CghiaIdentityBinding.php`
3. `backend/app/Services/Agriculture/Research/Identity/IdentityBindingStatus.php`
4. `backend/app/Services/Agriculture/Research/Identity/IdentityDecisionIdentity.php`
5. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingLifecycleState.php`
6. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingInvariantViolation.php`
7. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingRecordId.php`
8. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingPersistenceContract.php`
9. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingRecord.php`
10. `backend/app/Services/Agriculture/Research/Identity/Persistence/IdentityBindingRepository.php`
11. `backend/app/Services/Agriculture/Research/Identity/Persistence/EloquentIdentityBindingRepository.php`
12. `backend/tests/Unit/Agriculture/Research/Identity/Persistence/IdentityBindingPersistenceTest.php`

### 8.12.4 — Dependency Boundary

| Boundary | Status |
|----------|--------|
| IU-01 (`d62a082`) | EXTERNAL COMMITTED DEPENDENCY — not re-authored; `SourceIdentityDomainContract` remains authoritative for namespace distinctness |
| B7 / Correlation | SEPARATE — unchanged; isolation tested only |
| ADR-023 | SEPARATE until this preservation append |
| 36 pre-existing WIP paths | SEPARATE — unstaged / uncommitted |

FS-01-ID does **not** re-author namespace identity authority.

### 8.12.5 — Defect-1 — Exception Contract Alignment

**Root cause:** `IdentityBindingRecord::draftAttributes` correctly delegates namespace distinction to IU-01 `SourceIdentityDomainContract::assertDistinctNamespaces`, which throws authoritative `SourceIdentityInvariantViolation`. The original test expected `IdentityBindingInvariantViolation`.

**Repair:** test expectation aligned to `SourceIdentityInvariantViolation::class`.

**Safety preserved:** `adr_id == canonical_identity_id` remains rejected. No production exception translation, no duplicate identity authority, no silent normalize/mint/SAME_AS.

### 8.12.6 — Defect-2 — Supersession Named-Parameter Mismatch

**Root cause:** supersede call used invalid named parameter `evidenceRefs`. Authoritative API parameter is `$identityEvidenceRefs`.

**Repair:** call-site renamed to `identityEvidenceRefs:`. No alias. No dual vocabulary. No production API duplication.

### 8.12.7 — Verification Evidence

| Suite | Tests | Assertions | Failures | Errors | Skipped |
|-------|-------|------------|----------|--------|---------|
| FS-01-ID (`IdentityBindingPersistenceTest`) | 22 | 85 | 0 | 0 | 0 |
| IU-01 regression (`SourceIdentityDomainContractTest`) | 13 | 81 | 0 | 0 | 0 |

**Environment:** Docker `backend-test`; PHP **8.4.24**; PHPUnit **11.5.56**; SQLite `:memory:` via `phpunit.xml` force; `FORBIDDEN_TEST_DATABASES=wsa_enterprise`. Host PHP / Composer / winget **not** used.

B7 remained unchanged. 36 WIP fingerprint remained unchanged through repair / commit / push.

### 8.12.8 — Atomic Commit

| Item | Value |
|------|--------|
| Commit | `2e670096ebd2ee115d803db31440aa47a1613634` |
| Parent | `f9ff12dde306512c66d306a161bdba7cd0edfbb4` |
| Subject | `feat(research): add identity binding persistence` |
| Files | **12** (exact FS-01-ID set above) |
| Insertions | **1394** |
| Deletions | **0** |

Scope: EXACTLY the 12 FS-01-ID files. No WIP. No ADR. No B7. No IU-01.

### 8.12.9 — Push

| Item | Value |
|------|--------|
| Remote before | `f9ff12dde306512c66d306a161bdba7cd0edfbb4` |
| Remote race check | PASS |
| Push | fast-forward only (`f9ff12dd..2e67009`) |
| Force push | **NO** |
| Remote after | `2e670096ebd2ee115d803db31440aa47a1613634` |
| Local after | `2e670096ebd2ee115d803db31440aa47a1613634` |
| Local == Remote | YES |
| Ahead / Behind | 0 / 0 |

### 8.12.10 — Final FS-01-ID State

| Item | Status |
|------|--------|
| FS-01-ID | **IMPLEMENTED · COMMITTED · PUSHED · CLOSED** |
| 36 WIP paths | preserved |
| ADR-023 (pre-this-section) | unchanged by implementation / commit / push gates |
| B7 | unchanged |
| IU-01 | unchanged external dependency (`d62a082`) |

### 8.12.11 — Current Stopping Point

The project intentionally stopped immediately after the successful FS-01-ID Push Gate. No subsequent implementation unit was started.

After this mutation, a separate **FS-01-ID ADR PRESERVATION COMMIT GATE** is required, then a separate preservation push gate. Completing this preservation record does **not** authorize CapVer, Path, Projection, Onboarding implementation, D-10 runtime implementation, IU-10B, PHP Loader, or any source activation.

**Stopping SHA (implementation closed):** `2e670096ebd2ee115d803db31440aa47a1613634`

## 8.13 — Integration Classification Design and Acceptance Preservation Record

**Record type:** PRESERVATION / TRACEABILITY RECORD

This section preserves the historical Integration Classification (IC) Design and Acceptance chain. It does **not** create a new architectural authority, does **not** authorize implementation, does **not** create an IC package/inventory/persistence, and does **not** authorize runtime activation of any seat.

### 8.13.1 — Traceability Chain

1. **Post-FS-01-ID First Runtime Source Readiness** identified Integration Classification as the earliest missing pipeline dependency after Membership + FS-01-ID Identity Binding Persistence.
2. **Integration Classification Design Gate** produced: **DESIGN COMPLETE WITH NON-BLOCKING CLARIFICATIONS**.
3. **Integration Classification Design Acceptance Gate** produced: **ACCEPTED WITH NON-BLOCKING CLARIFICATIONS** (40/40 adversarial tests PASS).
4. **Implementation Authorization** remained **NO**.
5. Exact next gate after acceptance became this ADR preservation mutation (§8.13).

### 8.13.2 — Design Decision Preserved

**Design completeness:** DESIGN COMPLETE WITH NON-BLOCKING CLARIFICATIONS.

**IC answers:** For a given `adr_id`, based only on verified evidence, what integration/access mechanism(s) are actually available or supportable for *reaching* this source?

**IC does not answer:** scientific supportability (Capability); path eligibility/selection (Path); scientific fidelity (Projection/C9); runtime activation (D-10); Stage-3 default membership.

### 8.13.3 — IC Authority

**IC owns:** classification decision; classification status; access modality claims; integration nature; protocol family; source-specific requirement; integration boundary; IC evidence set; `classification_decision_identity`.

**IC does not own:** Identity; Capability; Path; Projection/C9; Onboarding; D-10; Stage-3 selection; Composer.

### 8.13.4 — Classification Dimensions

| Dimension | Accepted vocabulary / note |
|-----------|----------------------------|
| Access modality claims | Share *code strings* with IU-02 `CapabilityAccessMethod` where applicable (`web_ui_search`, `machine_access`, `api`, `oai_pmh`, `rss`, `atom`, `bulk_download`, `static_download`, `metadata_access`, `full_text_access`); **split ownership** — IC claims vs Cap-verified methods |
| Integration nature | `SOURCE_NATIVE` · `EXTERNAL_DEPENDENCY` · `MANUAL_ONLY` · `NATURE_UNVERIFIED` |
| Integration boundary | `PROTOCOL_FAMILY_ADAPTER` · `SOURCE_SPECIFIC_ADAPTER` · `STAGE3_ADAPTER` · `EXTERNAL_AGGREGATOR` · `MANUAL_STATIC` · `FUTURE_ADAPTER_PLANNING_ONLY` |
| Protocol family | Evidence-backed family label only; not inferred from name/URL alone |
| Source-specific requirement | Boolean + rationale (does **not** mean adapter exists) |
| Path family hint | Optional, **NON-AUTHORITATIVE**, Path-referential (`PathFamily` P01–P18 semantics remain Path-owned) |

`FUTURE_ADAPTER_PLANNING_ONLY` is **GOVERNANCE-ONLY** and cannot produce runtime eligibility, Cap verification, Path selection, or D-10 ACTIVE.

### 8.13.5 — Classification Status Vocabulary

| `classification_status` | Meaning |
|-------------------------|---------|
| `CLASSIFIED` | Claimed modalities/nature/boundary fully evidence-backed for this revision |
| `PARTIALLY_CLASSIFIED` | Some dimensions evidenced; material gaps remain |
| `UNCLASSIFIED` | Default when no IC decision exists (**missing record**) |
| `UNCLASSIFIABLE` | Evidence shows classification cannot be established |
| `NOT_APPLICABLE` | Rare governed exception only; must not skip D-10.8 for ordinary seats |

Missing IC record ⇒ `UNCLASSIFIED`. IC status does **not** automatically map to Cap `CapabilityState`, Path `eligibility_state`, `PathStatus`, D-10 `activation_state`, or FAOSTAT Stage-3 `activation_state`.

### 8.13.6 — Evidence Model

Evidence classes (distinct): Identity (referential); Access; Protocol; Endpoint; Machine-access; Source-native vs aggregator; License; Access terms; Reuse terms.

Evidence metadata: source; timestamp; snapshot/version; verification method; scope; strength; expiry/staleness. **UNKNOWN remains UNKNOWN.** Stale evidence does not silently become `CLASSIFIED`.

### 8.13.7 — Record Identity / History / Atomicity

Subject: one `adr_id` (REQUIRED). Opaque `classification_decision_identity` (REQUIRED). Revision/supersession; evidence fingerprint; status; verified_at; verifier/actor; rationale; license/access/reuse references (distinct); provenance; idempotency key.

One `adr_id` may have multiple historical decisions. Historical rows immutable. New material evidence ⇒ new decision/revision. Atomic unit = **one `adr_id`**. Group/Wave/protocol-family/batch = GOVERNANCE-ONLY (cannot create `CLASSIFIED` or runtime eligibility).

### 8.13.8 — 109 Inventory Boundary

Future IC inventory remains **SEPARATE** from `ADR-023-109-MEMBERSHIP-REGISTER.v1.json`. Membership remains membership-only. Inventory is **not** created by this preservation. Seats are **not** automatically classified. Population remains OPEN until a later Implementation Authorization + inventory gate.

### 8.13.9 — Boundary Preservations

| Boundary | Preservation |
|----------|--------------|
| Capability | IC = how reachable/integrated?; Cap = what scientifically supportable?; CLASSIFIED may coexist with Cap UNVERIFIED; IC never implies Cap VERIFIED |
| Path | `path_family_hint` optional/non-authoritative; Path owns eligibility/selection/fallback/PathStatus |
| Projection/C9 | Protocol availability ≠ fidelity; no EXACT claim, CSQ rewrite, facet drop, or C9 bypass |
| Aggregator | Aggregator-only ⇒ `EXTERNAL_DEPENDENCY` unless native evidence; no silent native upgrade |
| License/Access/Reuse | Three distinct refs; public search ≠ machine access ≠ reuse/redistribution/commercial |
| Stage-3 | Preserve `openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`; no 109→Stage-3 auto-map; registration ≠ CGHIA ACTIVE |
| FS-01-ID / IU-01 | IC may reference identity bindings; must not mint identity/SAME_AS/canonical or override IU-01 |
| B7 | `classification_decision_identity` IC-owned; B7 remains correlation SoT; no competing B7 schema |
| Onboarding | Consumes `integration_classification_ref`; does not invent IC; ORCHESTRATION+AUDIT only |
| D-10 / D-10.8 | Per-seat verified IC artifact required before runtime binding; no inference from display name/website/membership/similarity; no CLASSIFIED→ACTIVE auto-transition |

### 8.13.10 — Staleness / Idempotency (Design Only)

Reverification triggers include: endpoint/protocol/API retirement; license/access/reuse change; provider/source/aggregator move; material contradiction. Stale `CLASSIFIED` requires new decision; history immutable.

Conceptual idempotency key = hash(`adr_id` + evidence fingerprint + dimension claim set + actor policy version). Same key ⇒ replay same decision identity. Concurrent writers ⇒ single active head via governed supersession. **Implementation deferred.**

### 8.13.11 — Field Ownership (Accepted)

| Field | Ownership |
|-------|-----------|
| `adr_id` | REFERENTIAL → Membership/Identity |
| `canonical_identity_id` | REFERENTIAL → Identity (nullable) |
| `identity_binding_ref` | REFERENTIAL → FS-01-ID |
| `classification_decision_identity` | AUTHORITATIVE (IC) |
| `access_modality_claims[]` | AUTHORITATIVE (IC); Cap owns Cap-verified methods separately |
| `integration_nature` / `protocol_family` / `source_specific_requirement` / `integration_boundary` | AUTHORITATIVE (IC) |
| `existing_adapter_reference` | REFERENTIAL / AUDIT |
| `external_dependency_reference` | REFERENTIAL |
| `path_family_hint` | NON-AUTHORITATIVE REFERENTIAL → Path |
| `evidence_references[]` | AUTHORITATIVE (IC evidence set) |
| `classification_status` | AUTHORITATIVE (IC) |
| `license_reference` / `access_reference` / `reuse_reference` | REFERENTIAL (distinct) |
| `verified_at` / `verifier` / `rationale` / `revision` | AUTHORITATIVE / AUDIT metadata |
| `staleness` | DERIVED |
| Group / Wave | GOVERNANCE-ONLY |

### 8.13.12 — Namespace Separation

`classification_status` ≠ Cap `CapabilityState` ≠ Path `eligibility_state` ≠ `PathStatus` ≠ D-10 `activation_state` ≠ FAOSTAT Stage-3 `activation_state`. **No implicit automatic mapping.**

### 8.13.13 — Acceptance Result (40/40)

Acceptance Gate adversarial suite: **40 / 40 PASS**. Categories preserved: missing/unknown `adr_id`; display/URL/endpoint/provider/adapter identity abuse; auto SAME_AS/canonical mint; Membership/group/wave → CLASSIFIED; API/OAI → Cap VERIFIED; public UI → machine access; access → reuse; protocol inference; historical/stale evidence; aggregator → native; Stage-3 → identity/ACTIVE; IC → Path / D-10 / EXACT; CSQ rewrite; facet drop; C9 bypass; license/access/reuse conflation; EMPTY_RESULT → CAP_UNAVAILABLE; future adapter → runtime ready; source-specific flag → adapter exists; path hint → selection; IC state → Cap/D-10 state; 109 membership → activation; batch classification → eligibility.

No additional blocking attack surface found in repository reality check.

### 8.13.14 — Non-Blocking Clarifications (Preserved Unresolved)

1. PathFamily hint mapping tables remain Path-owned.
2. CapVer organizational ownership remains deferred.
3. Inventory serialization/persistence encoding remains implementation-design scope.
4. Whether IC persistence follows FS-01-ID/B7 row pattern remains implementation-design scope.
5. `CapabilityAccessMethod` code-string reuse is intentional vocabulary reuse with split ownership.

These clarifications are **not** resolved by this preservation record.

### 8.13.15 — Contradiction Matrix Result

| Authority | Result |
|-----------|--------|
| D-09 / IU-10A Membership | COMPATIBLE |
| D-10 / D-10.8 | COMPATIBLE |
| IU-01 / FS-01-ID | COMPATIBLE |
| IU-02 Capability | COMPATIBLE WITH CLARIFICATION |
| IU-03 Path | COMPATIBLE WITH CLARIFICATION |
| IU-04/05 Projection/C9 | COMPATIBLE |
| IU-06/07 B7 | COMPATIBLE |
| IU-08 Disclosure | COMPATIBLE |
| IU-09 Coexistence / Stage-3 | COMPATIBLE |
| Onboarding §8.11 | COMPATIBLE |
| §8.12 FS-01-ID preservation | COMPATIBLE |

**Final:** NO BLOCKING CONTRADICTION.

### 8.13.16 — Implementation Gap / Authorization

Repository reality at acceptance: IC package = ABSENT; IC persistence = ABSENT; IC inventory = ABSENT. Existing separately: Identity/FS-01-ID; Capability domain; Path domain; Projection/C9 domain; B7; Stage-3; Coexistence.

**Acceptance decision:** INTEGRATION CLASSIFICATION DESIGN — **ACCEPTED WITH NON-BLOCKING CLARIFICATIONS**.

Meaning: the design is sufficiently complete, bounded, scientifically safe, and compatible with ADR-023 as the authoritative foundation for a **FUTURE** implementation gate.

**IMPLEMENTATION AUTHORIZATION = NO.** Explicitly: no IC implementation; no IC inventory population; no 109 runtime classification; no source/group/all-109 activation; no IU-10B; no PHP Loader; no migration/schema created by this record.

### 8.13.17 — Preservation Invariants

Append-only history; no deletion; no rewrite; no retroactive correction; no authority migration; no implementation authorization; no runtime activation; no automatic 109 classification; no automatic 109 activation.

### 8.13.18 — Post-Preservation Stopping Point

This mutation closes the IC Design/Acceptance ADR preservation step only. After a separate **INTEGRATION CLASSIFICATION ADR PRESERVATION COMMIT GATE** (and later push if authorized), the next step must be a **NEW explicit** design/implementation gate. Completing §8.13 does **not** authorize IC implementation or inventory population.

## 8.14 — IC Persistence Design and Acceptance Preservation Record

**Record type:** PRESERVATION / TRACEABILITY RECORD

This section preserves the historical Integration Classification (IC) **Persistence** Design and Acceptance chain after §8.13. It is **append-only historical traceability**. It does **not** create a new architectural authority, does **not** authorize implementation, does **not** create migrations/models/repositories/services, does **not** create an IC inventory or populate 109 seats, and does **not** authorize runtime activation of any seat.

### 8.14.1 — Traceability Chain

1. **Integration Classification Design Gate** produced: **DESIGN COMPLETE WITH NON-BLOCKING CLARIFICATIONS** (preserved in §8.13).
2. **Integration Classification Design Acceptance Gate** produced: **ACCEPTED WITH NON-BLOCKING CLARIFICATIONS** (40/40; preserved in §8.13).
3. **Post-IC Preservation Readiness Discovery** identified **IC Persistence Design Gate** as the earliest next design gate (implementation authorization remained **NO**).
4. **IC Persistence Design Gate** produced: **B — DESIGN COMPLETE WITH NON-BLOCKING CLARIFICATIONS**.
5. **IC Persistence Design Acceptance Gate** produced: **B — ACCEPTED WITH NON-BLOCKING CLARIFICATIONS** (40/40 adversarial acceptance checks **PASS**).
6. **Implementation Authorization** remained **NO**. ADR mutation was **not** authorized during acceptance; this §8.14 mutation is the authorized ADR preservation step only.

### 8.14.2 — Design and Acceptance Decisions Preserved

| Decision | Result |
|----------|--------|
| IC Persistence Design | **B — DESIGN COMPLETE WITH NON-BLOCKING CLARIFICATIONS** |
| IC Persistence Design Acceptance | **B — ACCEPTED WITH NON-BLOCKING CLARIFICATIONS** |
| Adversarial acceptance | **40 / 40 PASS** |
| Implementation Authorization | **NO** |
| ADR Mutation Authorization (during acceptance) | **NO** (preservation is this separate gate) |

### 8.14.3 — Aggregate / Atomicity

Aggregate root = one Integration Classification Decision for one `adr_id`. Atomic unit = **one `adr_id`**. Explicitly **not** permitted as aggregate keys: group, wave, all-109, dual-seat pair, protocol-family batch. Group/Wave remain GOVERNANCE-ONLY and cannot create `CLASSIFIED` state or runtime eligibility.

### 8.14.4 — Persistence Identities

| Identity | Role |
|----------|------|
| `persistence_record_id` | Storage PK only (`bigint` `id()`, FS-01-ID/B7 convention) |
| `adr_id` | REQUIRED business subject (Membership/IU-01 namespace; referential) |
| `classification_decision_identity` | REQUIRED opaque decision identity; **IC-owned** |
| `canonical_identity_id` | OPTIONAL nullable referential; **never minted by IC** |
| `identity_binding_ref` | OPTIONAL nullable opaque FS-01-ID reference; **no domain FK** |
| `idempotency_key` | UNIQUE replay identity |

Forbidden: canonical minting; SAME_AS minting; display-name identity; sourceKey-as-canonical; adapter-class identity; domain FKs where bounded-context architecture forbids them.

### 8.14.5 — Orthogonal State Namespaces

**`classification_status` (IC semantic authority):** `CLASSIFIED` · `PARTIALLY_CLASSIFIED` · `UNCLASSIFIED` · `UNCLASSIFIABLE` · `NOT_APPLICABLE`.

**`lifecycle_state` (persistence control only):** `ACTIVE` · `SUPERSEDED` · `INVALIDATED`.

Rules: missing IC record ⇒ `UNCLASSIFIED`; `PARTIALLY_CLASSIFIED` is a state of **one** decision revision; **lifecycle `ACTIVE` ≠ D-10 `ACTIVE`**; no automatic mapping to Cap `CapabilityState`, Path `eligibility_state`, `PathStatus`, D-10 `activation_state`, or FAOSTAT Stage-3 `activation_state`.

### 8.14.6 — Revision / Supersession / Invalidation

Selected model: **append-only immutable decision rows** + lifecycle pointer (FS-01-ID/B7 Option C pattern).

- Domain payload immutable after insert.
- Mutable only: `lifecycle_state`, `superseded_by` (and storage `updated_at`).
- At most one `ACTIVE` head per `adr_id`.
- Supersession is **transactional**: `lockForUpdate` prior ACTIVE → insert new ACTIVE → mark prior `SUPERSEDED` + set `superseded_by`.
- Invalidation changes lifecycle only; historical rows retained.
- Concurrent create with different keys while ACTIVE exists is rejected unless supersede API is used.

### 8.14.7 — Hybrid Storage and Dimension Payload

Selected architecture: **HYBRID** — relational authoritative columns + JSON multi-value claims/references.

IC dimensions persisted under IC authority: `access_modality_claims`; `integration_nature`; `integration_boundary`; `protocol_family`; `source_specific_requirement`; `source_specific_rationale`; `path_family_hint`; `existing_adapter_reference`; `external_dependency_reference`; `rationale`.

`path_family_hint` is **NON-AUTHORITATIVE** (Path owns PathFamily P01–P18 semantics). `FUTURE_ADAPTER_PLANNING_ONLY` remains **GOVERNANCE-ONLY** and cannot produce runtime eligibility, Cap verification, Path selection, or D-10 ACTIVE. `CapabilityAccessMethod` code-string overlap remains intentional with **split ownership**.

### 8.14.8 — Evidence Model

Fields: `evidence_references` (opaque/structured refs); `evidence_fingerprint` (REQUIRED). Logical evidence classes include: access; protocol; endpoint; machine-access; native-vs-aggregator; license; access-terms; reuse. Identity evidence may be **referenced** (e.g. via `identity_binding_ref`) and is **not** duplicated as an IC-owned identity store. **No universal Evidence System** is introduced by this design.

### 8.14.9 — Provenance

`decision_actor`; `verified_at` / `decision_timestamp`; `schema_version` (start = 1); `metadata` (JSON nullable, non-authoritative diagnostics only). **Provenance ≠ scientific evidence.**

Forbidden metadata authority includes: Cap states; Path eligibility/status; D-10 activation; C9 fidelity/facets; CSQ/R6 keys; `same_as`; display_name-as-identity.

### 8.14.10 — Idempotency

Deterministic conceptual key: `SHA-256(canonical_json({ adr_id, evidence_fingerprint, normalized_dimension_claim_set, actor_policy_version }))`.

Normalized claim set includes: modalities; nature; boundary; protocol_family; source_specific_requirement; path_family_hint. Excluded by default: actor display name; wall-clock alone; persistence ID; lifecycle; free-text rationale. Unique `idempotency_key`; same key ⇒ deterministic replay (no duplicate row).

### 8.14.11 — Concurrency

Same idempotency key ⇒ replay. Different keys for same `adr_id` ⇒ at most one ACTIVE. Supersession ⇒ `lockForUpdate`. No cross-seat locking. State transitions are transactional.

### 8.14.12 — Staleness / UNKNOWN

Evidence timestamp/snapshot live in evidence refs; verification time in `verified_at` / `decision_timestamp`. Staleness is **DERIVED** at read/evaluation time. **UNKNOWN remains UNKNOWN.** Stale evidence does not auto-promote classification. Stale `CLASSIFIED` requires a **new decision via supersession**, not in-place rewrite. `snapshot_version = UNKNOWN` remains UNKNOWN.

### 8.14.13 — License / Access / Reuse

Distinct referential fields: `license_reference`; `access_reference`; `reuse_reference`. No boolean collapse. Public search ≠ machine access ≠ reuse. These remain evidence claims, not legal conclusions.

### 8.14.14 — Bounded-Context Boundaries

| Boundary | Preservation |
|----------|--------------|
| Identity / FS-01-ID | Referential only; no canonical/SAME_AS mint; dual seats independent |
| CapVer | No `CapabilityState` / Cap decision IDs / Cap evidence bodies; CLASSIFIED ⇏ Cap VERIFIED; CapVer org ownership remains **DEFERRED** |
| Path | `path_family_hint` non-authoritative; no eligibility / PathStatus / selected path / projection identity |
| Projection / C9 | No `source_query` / facets / `fidelity_class` / unsupported/omitted/unresolved facet sets |
| B7 | IC owns `classification_decision_identity`; B7 remains correlation SoT; **no second correlation table**; optional future B7 column **DEFERRED** |
| Onboarding | May later consume `integration_classification_ref`; IC does not implement onboarding |
| D-10 | CLASSIFIED ≠ ELIGIBLE ≠ ACTIVE; **no `activation_state` in IC** |
| Stage-3 / CGHIA | No Stage-3 writes / adapter registration / activation; `STAGE3_ADAPTER` is classification claim only |
| Aggregator | `EXTERNAL_DEPENDENCY` ≠ `SOURCE_NATIVE`; no silent native upgrade |
| Membership | Membership JSON separate; no 109 inventory population; no group/name/URL/protocol inference |

### 8.14.15 — Persistence Pattern Decision

Compatible with **FS-01-ID** and **B7**: bigint PK; append-only lifecycle; opaque references; idempotency; `lockForUpdate`; timestamps; evidence fingerprint; metadata firewall; no domain FKs. Clarification whether IC follows FS-01-ID/B7 row pattern is **RESOLVED: YES**.

Deliberate IC-specific differences: classification columns; `classification_status` vs `lifecycle_state`; license/access/reuse refs; non-authoritative `path_family_hint`.

### 8.14.16 — Conceptual Schema (DESIGN ONLY — NOT IMPLEMENTED)

Conceptual table: `cghia_integration_classification_records`.

Conceptual fields: `id`; `adr_id`; `canonical_identity_id`; `identity_binding_ref`; `classification_decision_identity`; `classification_status`; `access_modality_claims`; `integration_nature`; `integration_boundary`; `protocol_family`; `source_specific_requirement`; `source_specific_rationale`; `path_family_hint`; `existing_adapter_reference`; `external_dependency_reference`; `evidence_references`; `evidence_fingerprint`; `license_reference`; `access_reference`; `reuse_reference`; `rationale`; `decision_actor`; `verified_at`; `decision_timestamp`; `lifecycle_state`; `schema_version`; `idempotency_key`; `superseded_by`; `metadata`; timestamps.

Conceptual repository API only: `persist`; `findById`; `findByIdempotencyKey`; `findCurrentByAdrId`; `supersede`; `invalidate`.

**DESIGN ONLY — NOT IMPLEMENTED.** No migration/model/repository/service/enum/test/inventory was created by this preservation record.

### 8.14.17 — Security / Integrity Controls

Actor attribution; immutable history; idempotency + replay protection; `adr_id` validation; no cross-seat transactions; canonical null-by-default; metadata authority firewall; fingerprint + immutable rows + actor/timestamp provenance. CapVer organizational ownership remains **DEFERRED** (no invented tenant model).

### 8.14.18 — Adversarial Acceptance Result (40/40)

Acceptance Gate adversarial suite: **40 / 40 PASS**. Categories preserved include: missing → UNCLASSIFIED; `adr_id` validation; canonical nullability; no canonical/SAME_AS mint; dual-seat isolation; no group/109 aggregate; state/lifecycle separation; no Cap/Path/D-10/C9/CSQ/R6 leakage; no Stage-3 write; aggregator/native separation; UNKNOWN semantics; stale evidence; immutable payload; transactional supersession; single ACTIVE; concurrency; idempotency; evidence fingerprint; evidence source isolation; license/access/reuse separation; B7 separation; Membership separation; implementation remains unauthorized.

No blocking design defect found.

### 8.14.19 — Non-Blocking Clarifications (Preserved Unresolved for Implementation Encoding)

1. Inventory serialization / 109 population encoding remains **DEFERRED**.
2. CapVer organizational ownership remains **DEFERRED**.
3. Optional future B7 `classification_decision_identity` column remains **DEFERRED**.
4. Partial unique ACTIVE(`adr_id`) index remains **OPEN / optional** for v1 (repository invariant matches FS-01-ID).
5. Exact SHA-256 canonical JSON byte rules remain **NON-BLOCKING** (must be fixed at implementation).
6. Replay payload equality exactness remains **NON-BLOCKING** (recommended: verify fingerprint + claims + status on replay).
7. PathFamily hint mapping tables remain **Path-owned**.
8. `CapabilityAccessMethod` code-string overlap remains intentional **split ownership**.

These clarifications do **not** authorize implementation.

### 8.14.20 — Contradiction Matrix Result

| Authority | Result |
|-----------|--------|
| §8.13 IC Design/Acceptance | COMPATIBLE |
| FS-01-ID | COMPATIBLE |
| B7 | COMPATIBLE |
| IU-02 Cap | COMPATIBLE |
| IU-03 Path | COMPATIBLE |
| Projection/C9 | COMPATIBLE |
| Onboarding §8.11 | COMPATIBLE |
| D-10 / D-10.8 | COMPATIBLE |
| IU-09 / Stage-3 | COMPATIBLE |
| Membership IU-10A | COMPATIBLE |

**Final:** NO BLOCKING CONTRADICTION.

### 8.14.21 — Implementation Authorization

**IMPLEMENTATION AUTHORIZATION = NO.**

This preservation record does **not** authorize: migration; model; repository; service; enum; DTO; factory; seeder; test implementation; inventory; CapVer; Onboarding; D-10; activation; adapters; Stage-3 changes; PHP Loader; IU-10B.

### 8.14.22 — Preservation Invariants

Append-only history; no deletion; no rewrite of prior ADR bytes; no retroactive correction; no authority migration; no implementation authorization; no runtime activation; no automatic 109 classification; no automatic 109 activation.

### 8.14.23 — Post-Preservation Stopping Point

Current gate: **IC Persistence ADR Preservation Mutation Gate**.

After a separate **IC Persistence ADR Preservation Commit Gate** (and later Push Gate if authorized), the exact next gate is:

**IC Persistence Implementation Authorization Gate**

Completing §8.14 does **not** authorize IC persistence implementation or inventory population.


## 8.15 — Cumulative Conversation / Forensic Preservation Record — IC Persistence Implementation and Final Push — 2026-10-03

### 8.15.1 — Preservation Purpose

This append-only record preserves the substantive prompts, execution reports, corrections, Git/forensic decisions, implementation results, and stopping point produced after §8.14. It is part of the same ADR-023 architectural record. No previous ADR content is deleted, replaced, silently reconciled, or rewritten. This record does not create a new ADR and does not authorize work outside the decisions explicitly recorded below.

The user explicitly required that substantive prompts, forensic reports, reports, and conversation decisions that were not yet preserved be retained in ADR-023, with append-only history.

### 8.15.2 — IC Persistence Implementation Authorization Gate — Accepted Result

The IC Persistence Implementation Authorization Gate was executed after §8.14 preservation.

Authoritative Git baseline at the gate:

- Branch: `phase-18-m18-ai-marketing-communications`
- HEAD / remote: `097583a97954edaac812367577d8b48cafb7610a`
- Ahead / behind: 0 / 0
- Staged: EMPTY
- Protected WIP: 36
- ADR-023: CLEAN

Authorization result:

**A — IMPLEMENTATION AUTHORIZED**

Authorization was restricted to IC Persistence only:

1. migration
2. model / persistence representation
3. repository / persistence API
4. validation / contract layer if required
5. transactional supersession / invalidation
6. idempotency
7. persistence-contract tests

Explicitly forbidden by that gate:

- 109 IC inventory
- CapVer
- Path
- Projection/C9
- Onboarding runtime
- D-10
- Stage-3
- adapters
- selectors
- activation
- source registration
- IU-10B
- PHP Loader
- ADR mutation
- WIP modification.

The gate verified that the eight previously recorded non-blocking clarifications remained non-blocking and that there were no blocking dependencies.

### 8.15.3 — Implementation Execution Master Prompt — Preserved Operational Contract

The implementation prompt required the following sequence:

**DISCOVER → PLAN → IMPLEMENT → TEST → FORENSIC VERIFY → COMMIT → PUSH → POST-PUSH VERIFY → STOP**

It explicitly protected WIP=36 and forbade implementation outside IC Persistence.

The authorized persistence aggregate was:

`cghia_integration_classification_records`

with one `adr_id` as the atomic aggregate subject.

The implementation contract preserved:

- bigint persistence PK
- `adr_id`
- opaque `classification_decision_identity`
- nullable `canonical_identity_id`
- nullable `identity_binding_ref`
- `classification_status`
- `lifecycle_state`
- hybrid relational + JSON storage
- access modality claims
- integration nature
- integration boundary
- protocol family
- source-specific requirement/rationale
- non-authoritative `path_family_hint`
- adapter/external dependency references
- evidence references + fingerprint
- separate license/access/reuse references
- provenance
- metadata firewall
- idempotency
- supersession
- invalidation
- concurrency controls.

The prompt explicitly required the two orthogonal state namespaces:

`CLASSIFIED | PARTIALLY_CLASSIFIED | UNCLASSIFIED | UNCLASSIFIABLE | NOT_APPLICABLE`

and:

`ACTIVE | SUPERSEDED | INVALIDATED`

It required append-only decision payloads, transactional supersession with `lockForUpdate`, at most one ACTIVE decision per `adr_id`, deterministic SHA-256 idempotency, replay protection, evidence/provenance separation, and no authority leakage into CapVer, Path, Projection/C9, D-10, Stage-3, B7, Membership, CSQ, or Identity.

The prompt required targeted tests plus FS-01-ID/B7/relevant Research regression tests, forensic changed-file classification, path-scoped staging, a precise commit, remote safety verification, fast-forward push only, and final post-push verification.

### 8.15.4 — Cursor Pre-Execution Skip / Safety Correction

Cursor presented an execution path containing Git push commands before the implementation lifecycle had completed. The user was instructed to select **Skip**, not Run, because push must occur only after implementation, tests, forensic verification, and commit verification.

The user confirmed the Skip action.

No unauthorized implementation or push was accepted from that premature step.

### 8.15.5 — Post-Skip Execution Master Prompt — Preserved Operational Contract

After Skip, the execution prompt was corrected to enforce this exact order:

1. PHASE 1 — read-only Git baseline
2. PHASE 2 — read-only architecture/pattern discovery
3. PHASE 3 — implement IC Persistence
4. PHASE 4 — targeted tests
5. PHASE 5 — regression
6. PHASE 6 — forensic diff
7. PHASE 7 — commit
8. PHASE 8 — remote safety check
9. PHASE 9 — push
10. PHASE 10 — post-push forensic verification
11. PHASE 11 — final report
12. PHASE 12 — stop

The corrected prompt explicitly prohibited early push, early commit, force operations, WIP modification, ADR mutation, and all out-of-scope runtime work.

### 8.15.6 — IC Persistence Implementation Execution Result

Implementation completed successfully.

Files created:

**15 files**, consisting of:

- migration:
  `backend/database/migrations/2026_10_02_230000_create_cghia_integration_classification_records_table.php`
- model:
  `backend/app/Models/CghiaIntegrationClassification.php`
- IntegrationClassification enums/value object surface
- Persistence record/ID/contract/idempotency/invariant/repository/Eloquent persistence surface
- IC persistence unit test:
  `backend/tests/.../IntegrationClassificationPersistenceTest.php`

No previously tracked file was modified by the implementation report; the authorized implementation surface was new files only.

Implementation findings:

- hybrid schema implemented
- dual status/lifecycle axes implemented
- one ACTIVE / `adr_id` invariant implemented
- transactional supersession implemented
- SHA-256 idempotency implemented
- metadata firewall implemented
- Cap/Path/D-10 leakage blocked
- no 109 inventory
- no activation
- no Stage-3 mutation
- no CapVer/Path/Projection/D-10 runtime implementation.

### 8.15.7 — Test and Regression Results

IC Persistence:

**23 passed / 110 assertions / EC=0**

FS-01-ID:

**22 passed / 85 assertions / EC=0**

B7:

**15 passed / 239 assertions / EC=0**

Environment:

Docker `backend-test` + PostgreSQL.

Regression:

**PASS** — no IC-caused FS-01-ID/B7 persistence regression.

Static/syntax verification:

**PASS**

IC implementation diff-check:

**PASS**

Unauthorized staged paths:

**0**

### 8.15.8 — IC Persistence Commit

Commit created:

`814ae0661b1ab2d30392917a30f31bdc33802a4f`

Subject:

`feat(research): add IC persistence`

Parent:

`097583a97954edaac812367577d8b48cafb7610a`

Commit scope:

**15 authorized files only**

No CapVer / Path / Projection / Onboarding / D-10 / Stage-3 / B7 / FS-01-ID / Membership / CSQ / Composer / inventory / activation changes were included.

### 8.15.9 — Push-State Reconciliation

The first implementation execution report stated that push had not been executed because approval was skipped and recorded local ahead=1 / behind=0.

A subsequent final Push + Post-Push forensic check established that the remote had already advanced to the same implementation commit:

Local:
`814ae0661b1ab2d30392917a30f31bdc33802a4f`

Remote:
`814ae0661b1ab2d30392917a30f31bdc33802a4f`

Ahead / behind:
**0 / 0**

Therefore no additional push was required. The commit was already present remotely as:

`097583a..814ae06`

The final state was treated as:

**A — IC PERSISTENCE PUSH + POST-PUSH VERIFICATION SUCCESSFUL**

This is a reconciliation of the two reports: the earlier report was a pre-push snapshot; the later report verified that the remote had subsequently fast-forwarded. No force push or destructive Git operation was used.

### 8.15.10 — Final IC Persistence State

IC Persistence is now:

**IMPLEMENTED + TESTED + COMMITTED + PUSHED**

Final Git state:

- Branch: `phase-18-m18-ai-marketing-communications`
- Local HEAD: `814ae0661b1ab2d30392917a30f31bdc33802a4f`
- Remote HEAD: `814ae0661b1ab2d30392917a30f31bdc33802a4f`
- Ahead / behind: 0 / 0
- Staged: EMPTY
- Protected WIP: 36
- ADR-023: CLEAN

IC test evidence retained:

- IC: 23/23, 110 assertions
- FS-01-ID: 22/22, 85 assertions
- B7: 15/15, 239 assertions

### 8.15.11 — Architectural Non-Changes Preserved

The IC Persistence implementation did NOT implement or activate:

- CapVer
- Path
- Projection/C9
- Onboarding runtime
- D-10
- Stage-3
- source adapters
- selectors
- 109 runtime inventory
- source activation
- Membership changes
- B7 schema changes
- CSQ changes
- ScientificSearchQueryBuilder changes
- AgriculturalEntityCatalog changes
- ScientificQueryCompiler changes
- AnswerComposer changes
- R6 changes
- canonical identity minting
- SAME_AS.

IC classification remains a bounded persistence authority only.

In particular:

**IC CLASSIFIED ≠ Cap VERIFIED ≠ Path SELECTED ≠ D-10 ACTIVE**

and:

**Membership ≠ Runtime Eligibility**

### 8.15.12 — Final Push / Post-Push Master Prompt Preservation

The final push gate required:

1. verify local commit `814ae066`
2. fetch remote
3. verify no unexpected divergence
4. require fast-forward-only conditions
5. never use force / force-with-lease / no-verify
6. verify local=remote after push
7. verify ahead/behind=0/0
8. verify staged EMPTY
9. verify WIP=36
10. verify ADR clean
11. verify commit contains IC Persistence only
12. stop after verification.

The actual post-push result satisfied the required end state, and no second push was necessary because the remote already contained `814ae066`.

### 8.15.13 — Conversation / Correction Chain

The substantive conversation chain preserved here is:

1. IC Persistence Design Acceptance was accepted with non-blocking clarifications.
2. §8.14 ADR preservation mutation succeeded.
3. §8.14 preservation was committed and pushed as:
   `097583a97954edaac812367577d8b48cafb7610a`
4. Implementation Authorization Gate returned:
   **A — IMPLEMENTATION AUTHORIZED**
5. Cursor showed an unsafe/preliminary push execution path; user selected **Skip**.
6. A corrected post-Skip execution prompt enforced the safe execution order.
7. IC Persistence implementation completed.
8. IC 23/23, FS-01-ID 22/22, B7 15/15 passed.
9. Commit `814ae066` was created.
10. Final remote reconciliation verified `814ae066` was already pushed.
11. Local and remote became equal at `814ae066`.
12. The current stopping point became the Post-IC-Persistence Runtime Readiness / Next-Unit Discovery Gate.

### 8.15.14 — Exact Current Stopping Point

Current completed unit:

**IC Persistence**

Status:

**CLOSED — IMPLEMENTED + TESTED + COMMITTED + PUSHED**

Current branch/HEAD:

`phase-18-m18-ai-marketing-communications`
`814ae0661b1ab2d30392917a30f31bdc33802a4f`

Protected WIP:

**36**

Current Git state:

**local = remote; ahead/behind = 0/0; staged EMPTY**

Current architectural state:

- Membership Register remains membership authority only.
- IC Persistence exists as durable persistence only.
- CapVer remains separate.
- Path remains separate.
- Projection/C9 remains separate.
- Onboarding remains design-only.
- D-10 remains architecture-only.
- Stage-3 remains unchanged.
- No concrete source activation has occurred.

### 8.15.15 — Exact Next Gate

The exact next gate is:

**Post-IC-Persistence Runtime Readiness / Next-Unit Discovery Gate**

This gate must begin as a separate forensic/discovery gate.

It must NOT automatically implement:

- IC inventory
- CapVer
- Path
- Projection
- Onboarding
- D-10
- Stage-3
- source activation.

The next unit must be selected only after the runtime-readiness/dependency graph is inspected.

### 8.15.16 — Preservation Invariants

The following remain mandatory:

- append-only ADR history
- no deletion of previous decisions
- no silent reconciliation of historical records
- no implementation authorization inferred from preservation
- no runtime activation inferred from membership
- no capability inferred from classification
- no path inferred from classification
- no canonical identity inferred from name similarity
- no source-native capability inferred from aggregator evidence
- no CSQ semantic mutation to satisfy a provider
- no Git destructive operation without explicit authorization
- WIP protection remains mandatory
- each implementation unit requires its own authorization and forensic closure.

### 8.15.17 — Preservation Completion

This §8.15 record preserves the substantive post-§8.14 prompts, reports, corrections, execution results, Git decisions, implementation/test results, push reconciliation, and exact stopping point available in the current project conversation.

No implementation is authorized by this preservation record beyond the already-closed IC Persistence unit.

**FINAL CURRENT STATUS: IC PERSISTENCE CLOSED.**

**NEXT GATE: Post-IC-Persistence Runtime Readiness / Next-Unit Discovery Gate.**


## 8.16 — Capability Persistence Design Decisions (CPD-A–D)

**Record type:** PRESERVATION / DESIGN DECISION RECORD

This section preserves the Capability Persistence Design Decision Authorization Gate outcomes **CPD-A** through **CPD-D** after IC §8.15. It is **append-only architectural history**. It does **not** authorize Capability Store implementation, migrations, models, repositories, DI bindings, CapVer, population services, Runtime Source Registry, Stage-3 changes, CSQ/QueryBuilder changes, IC changes, Onboarding implementation, or runtime activation of any seat.

### 8.16.1 — Purpose

Preserve authorized architectural design decisions required before any future Capability Store Persistence Implementation Authorization, so that Cap cell persistence, Cap evidence references, seat-override layers, and CURRENT uniqueness can proceed later **without inventing** evidence semantics, lifecycle semantics, override identity, uniqueness rules, CapVer ownership, or population authority.

### 8.16.2 — Scope

| In scope | Out of scope |
|----------|--------------|
| Capability Evidence **reference** model for Persistence Acceptance | Cap Evidence body / artifact store |
| Cap cell CURRENT / HISTORICAL persistence semantics | Cap Store migration / repository / ORM / DI implementation |
| Seat-override persistence identity and coexistence | Shared canonical → seat inheritance (Population) |
| CURRENT natural uniqueness and duplicate / fail-closed policy | CapVer producer / CapVer organizational ownership |
| Write-authority boundary (storage vs assignment) | Runtime Source Registry; 109 onboarding; Stage-3; CSQ; QueryBuilder; IC |

### 8.16.3 — Authority and Traceability

1. Cap v2 domain under `backend/app/Services/Agriculture/Research/Capability/` (committed).
2. ADR-023 D-10.5 (Capability Evidence Authority) and §8.11 Onboarding Cap field ownership (`capability_evidence_ref[]`, Cap dimension states).
3. ADR-023 §8.14.8 (IC evidence model; **no universal Evidence System**; Cap evidence bodies out of IC).
4. Capability Persistence forensic reconciliation and Design Decision Authorization Gate (CPD-A–D).
5. WIP `backend/e2e-tmp/_cap_store_design_extract.md` remains **historical / non-authoritative** and is **not** promoted by this section.

**Authority:** IU-02 Capability Store (scientific supportability). Cap Store ≠ CapVer. Cap Store ≠ IC. Cap Store ≠ Path / Projection / CSQ / Stage-3 / Membership.

### 8.16.4 — Decision Summary

| Decision ID | Title | Result |
|-------------|-------|--------|
| **CPD-A** | Capability Evidence Model | Opaque Cap-owned evidence reference strings; no Cap Evidence body entity; no CapVer-owned evidence store |
| **CPD-B** | Cap Cell Current / History | Append-only immutable Cap cell facts; Cap-native CURRENT / HISTORICAL |
| **CPD-C** | Seat Override Persistence Identity | Layer = `seat_override_applied`; shared + override CURRENT coexistence |
| **CPD-D** | Natural Uniqueness / Duplicates | ≤1 CURRENT per (subject, dimension, override layer); CapRecordId unique; PG NULL-safe CURRENT uniqueness |
| Implementation Authorization | — | **NO** (this section is preservation only) |

### 8.16.5 — CPD-A — Capability Evidence Model

For Persistence Acceptance, Capability Evidence is modeled as **opaque immutable evidence reference strings** owned by **Capability / Cap Store**.

This decision does **not** introduce:

- a separate Capability Evidence body entity;
- a CapVer-owned evidence storage subsystem;
- a universal Evidence System (reaffirm §8.14.8).

#### Ownership split

| Concern | Owner |
|---------|-------|
| Evidence **reference list** | Capability / Cap Store |
| Universal evidence body / artifact store | **NOT INTRODUCED** |
| VERIFIED **assignment** authority | CapVer (abstract) |
| CapVer organizational ownership | **DEFERRED** |
| IC evidence | IC-owned; orthogonal |
| Identity evidence | Identity-owned; orthogonal |

Cap Store ≠ CapVer. CapVer ≠ universal evidence store.

#### Evidence identity

References are opaque non-empty strings. They are **not**:

- `CapabilityRecordId`
- `CapabilityDecisionIdentity`
- Stage-3 `sourceKey`
- IC `classification_decision_identity`
- `identity_binding_ref`

Cap Persistence does **not** resolve evidence bodies and does **not** define a Cap Evidence body identity scheme.

#### Cardinality

One Cap cell **fact** → **N** opaque references (`capability_evidence_ref[]`).

- Where the Cap state requires evidence: **N ≥ 1**.
- Where evidence is optional: **N may be 0**.
- The same opaque string **may** appear on multiple Cap cell facts (reference-level reuse). That does **not** create a shared Cap Evidence entity.
- Ordering has **no** evaluation semantics (semantically a set).
- Duplicate identical strings within one fact: **REJECT** (fail-closed).

#### Evidence lifecycle

References attached to an immutable Cap cell fact are **immutable with that fact**. Historical evidence is preserved; evidence history is **not overwritten**. A later Cap cell fact may carry different references.

Do **not** equate evidence lifecycle with Cap cell CURRENT/HISTORICAL lifecycle, `CapabilityDecisionIdentity`, or IC ACTIVE/SUPERSEDE.

### 8.16.6 — `capability_evidence_ref[]`

Meaning: opaque Capability-owned references to external/supporting evidence artifacts that justify a Cap cell claim.

| Attribute | Decision |
|-----------|----------|
| Owner | Capability (IU-02) |
| Persist association | Cap cell persistence **fact** (CPD-A Option A) |
| Mutability | Immutable once recorded on a fact |
| Historical | Frozen on HISTORICAL facts; new CURRENT facts may carry new refs |
| Current `CapabilityRecord` PHP shape | **Does not yet contain this field** |

This ADR section does **not** modify `CapabilityRecord`. Domain / test alignment is deferred to a later Implementation Authorization / implementation unit.

### 8.16.7 — State → Evidence Matrix (normative for Persist writes)

| Cap state | Evidence | `limitation_text` | Notes |
|-----------|----------|---------------------|-------|
| **VERIFIED** | **Required** (≥1 opaque ref) | Optional | CapVer-quality claim |
| **PARTIAL** | **Required** (≥1 opaque ref) | **Required** (domain) | Documented limited support |
| **UNAVAILABLE** | **Required** (≥1 opaque ref) | Optional | Supports negative claim |
| **UNVERIFIED** | Optional / not required | Optional | Includes insufficient evidence |
| **NOT_APPLICABLE** | Optional / not required | Optional | Structural N/A |
| **MISSING** | Not persisted | — | Evaluator may derive UNVERIFIED; do **not** auto-insert UNVERIFIED |

**Known domain/test alignment issue (deferred):** current Cap domain tests may construct VERIFIED CapRecords without evidence objects. Architectural Persist write policy still requires evidence for VERIFIED / PARTIAL / UNAVAILABLE. Do **not** modify tests in this preservation gate.

**MISSING** remains a non-persisted evaluation condition (missing → UNVERIFIED). Explicit UNVERIFIED may be persisted only if later authorized; missing ≠ auto-persisted UNVERIFIED.

### 8.16.8 — CPD-B — Cap Cell Current / History

Capability cell persistence is **append-only immutable facts**.

- A Cap state change creates a **new** Cap cell fact.
- The previous CURRENT fact becomes **HISTORICAL**.
- Cap claim fields are **not** mutated in place.
- This fits the readonly `CapabilityRecord` domain model.

#### Cap-native vocabulary

Use:

- **CURRENT**
- **HISTORICAL**

Do **not** use IC `ACTIVE` / `SUPERSEDED` terminology for Cap cells. Do **not** copy IC lifecycle enums or IC FK semantics into Cap.

Exact database column names remain **implementation-deferred**. Representation may be an explicit CURRENT/HISTORICAL marker or equivalent.

#### Current determinism

CURRENT is **explicit**. Do **not** use `created_at`, `observed_at`, `verified_at`, or `snapshot_version` alone as a current pointer. Two reads of identical persisted state MUST produce the same CURRENT result. No "latest wins."

#### History and evaluation

HISTORICAL Cap facts are immutable and retained. They are **not** selected by default on Cap Store current-load. Current-load feeds `CapabilityRequirementEvaluator`. Historical facts are not silently mixed into ordinary evaluation.

#### Supersession

Insert new CURRENT fact; prior CURRENT → HISTORICAL. Transition must be **atomic** at implementation time. Exact SQL is not defined here.

#### `CapabilityRecordId`

`CapabilityRecordId` identifies the immutable Cap cell **fact** (stable identity of that fact). It is **not** DecisionIdentity, evidence identity, `sourceKey`, or IC classification decision identity. Logical continuity across time is the natural CURRENT uniqueness tuple (CPD-D), not DecisionIdentity.

`CapabilityDecisionIdentity` remains an **evaluation snapshot** identity (Path/B7/Correlation consumers). It is **not** Cap cell PK, Cap version, current pointer, or evidence identity. `durable_correlation_records.capability_decision_identity` remains Correlation concern.

### 8.16.9 — CPD-C — Seat Override Persistence Identity

Override layer identity for persistence is:

`CapabilitySubject` + `CapabilityDimension` + `seat_override_applied`

| `seat_override_applied` | Meaning |
|---------------------------|---------|
| `false` | Shared / base layer for that subject |
| `true` | Seat-override layer for that **same** subject |

No additional `override_id`, `seat_id`, or `dossier_id` is introduced (not present in committed Cap domain).

#### Shared + override coexistence

Persistence **allows** simultaneously:

- one CURRENT shared/base cell (`seat_override_applied = false`), and
- one CURRENT override cell (`seat_override_applied = true`),

for the same `CapabilitySubject` + `CapabilityDimension`.

Evaluator retains **restrictive-wins** when both are supplied. Persistence does **not** collapse the two rows. Evaluator restrictive-wins is evaluation behavior only and does **not** authorize arbitrary database duplicates.

#### Shared dossier inheritance

**DEFERRED** to Capability Population Design.

Committed evaluator uses exact `CapabilitySubject` matching and does **not** define canonical-shared → seat-scoped inheritance. Persistence identity is closed without inheritance.

### 8.16.10 — CPD-D — Natural Uniqueness

At most **one CURRENT** Cap cell fact exists for:

`(adr_id, canonical_identity_id, dimension_family, dimension_code, seat_override_applied)`

Historical facts may share the same natural tuple; they are distinguished by CURRENT/HISTORICAL lifecycle and by `CapabilityRecordId`.

`CapabilityRecordId` is **globally unique** among Cap cell facts. Duplicate `CapabilityRecordId` is invalid.

`capability_evidence_ref[]` does **not** participate in Cap cell uniqueness. Changing evidence for a new claim produces a **new** Cap cell fact.

### 8.16.11 — PostgreSQL NULL Semantics

`canonical_identity_id` may be NULL. Naive PostgreSQL UNIQUE treats NULLs as distinct, so CURRENT uniqueness must later use a dialect-safe strategy, including for example:

- partial unique indexes separating NULL / non-NULL cases; or
- `UNIQUE … NULLS NOT DISTINCT` where supported; or
- an equivalent dialect-safe mechanism,

plus application fail-closed on read if more than one CURRENT exists for the natural key.

Exact DDL is **not** part of this preservation. Do **not** treat this section as a migration.

### 8.16.12 — Duplicate Policy

| Scenario | Policy |
|----------|--------|
| Duplicate `CapabilityRecordId` | **REJECT** |
| Two CURRENT cells with the same natural tuple | **REJECT** / invariant violation |
| Same subject + dimension + different override layers (both CURRENT) | **VALID** coexistence |
| Historical rows with the same natural tuple | **VALID** |
| Conflicting CURRENT same layer | **REJECT** |
| Duplicate identical evidence ref in one fact | **REJECT** |
| Same opaque evidence ref across multiple Cap facts | **VALID** |
| Evaluator restrictive-wins | Evaluation only — not DB duplicate permission |

### 8.16.13 — Fail-Closed Policy

| Condition | Invariant |
|-----------|-----------|
| Ambiguous CURRENT (>1 same natural key) | **FAIL CLOSED** (no silent pick) |
| Duplicate CURRENT same layer | Reject write / fail closed |
| Malformed Cap cell | Fail closed |
| PARTIAL without `limitation_text` | `CapabilityInvariantViolation` (domain) |
| Invalid / empty evidence ref | Reject |
| Missing required evidence for VERIFIED / PARTIAL / UNAVAILABLE | Reject Persist write |
| Invalid override layer | Reject |
| Invalid history transition | Reject |
| Concrete exception class mapping | **DEFERRED** to implementation |

### 8.16.14 — Write Authority

| Concern | Decision |
|---------|----------|
| Cap Store may persist Cap cell facts + opaque refs | Yes (when Implementation Authorization later grants Persist) |
| VERIFIED **assignment** | CapVer only |
| CapVer organizational ownership | **DEFERRED** |
| Cap Store may persist an already-authorized VERIFIED fact | Yes — without becoming CapVer |
| PARTIAL / UNVERIFIED / UNAVAILABLE / NOT_APPLICABLE producers | **POPULATION AUTHORITY OPEN** — do not invent producers |
| Persist until Population Design | **Writer-neutral** (empty store + current-load allowed under later Persist Implementation Authorization) |

Enforcement of CapVer-only VERIFIED assignment belongs above repository and/or as defensive Persist write checks; exact enforcement placement is deferred to Implementation Authorization.

### 8.16.15 — CapVer Boundary

| Closed | Deferred |
|--------|----------|
| Cap Store ≠ CapVer | CapVer organizational ownership |
| CapVer-only assignment of VERIFIED | CapVer producer implementation |
| Cap Store stores opaque refs + Cap cell facts | CapVer-owned universal evidence body store (**not introduced**) |
| IC must not hold Cap evidence bodies (§8.14) | — |

### 8.16.16 — Domain Alignment (deferred)

The current `CapabilityRecord` domain does not yet contain `capability_evidence_ref[]`. Current tests may construct states that would fail Persist write requirements under §8.16.7. That is a **deferred implementation/test alignment** item, not an error to fix in this preservation gate.

### 8.16.17 — Deferred Items

- CapVer organizational ownership
- CapVer producer
- Non-VERIFIED population services
- Shared canonical → seat inheritance (Population Design)
- `CapabilityRecord` evidence-field alignment
- Test alignment for Persist write evidence requirements
- Cap Store migration / repository / ORM / DI implementation
- Exact exception classes
- Cap Evidence body artifact schemes
- Runtime Source Registry
- 109 source onboarding
- Stage-3 changes
- CSQ / QueryBuilder / IC changes

### 8.16.18 — Explicit Non-Goals / Non-Authorization

This §8.16 record does **not** authorize:

- Cap Store implementation
- migration
- repository
- Eloquent model
- DI binding
- CapVer
- population services
- Runtime Source Registry
- Stage-3 expansion
- CSQ / QueryBuilder / IC changes
- Onboarding / D-10 runtime implementation

### 8.16.19 — WIP Disposition

| WIP proposal (Cap Store Design extract) | Disposition |
|-----------------------------------------|-------------|
| Cap purpose / Cap v2 / subject / AND / B3–B6 | Already accepted in Cap domain (not reopened here) |
| Restrictive-wins | Accepted (evaluation + Persist coexistence under CPD-C) |
| Capability Evidence **entity** | **REJECTED** for Persistence Acceptance (CPD-A Option A) |
| Shared dossier + seat inheritance | **DEFERRED** to Population |
| Runtime Cap Store / migration authorization claims | **REJECTED** |
| Capability Snapshot / Verification Event entities | **DEFERRED** / historical |
| "Cap Store Design COMPLETE → Persist ready" | **HISTORICAL ONLY** |

The WIP file itself remains untouched and non-authoritative.

### 8.16.20 — Implementation Authorization Boundary

**IMPLEMENTATION AUTHORIZATION = NO.**

Completing this §8.16 preservation does **not** authorize Cap Store Persistence Implementation. After commit/push of this preservation record (when separately authorized), the exact next unit is:

**Capability Store Persistence Implementation Authorization Gate**

That gate must be explicitly requested and authorized later. It must not automatically invent CapVer, population services, Runtime Registry, Stage-3, CSQ, QueryBuilder, or IC changes.

### 8.16.21 — Preservation Invariants

- Append-only ADR history; no deletion or silent rewrite of §8.11 / D-10.5 / §8.14 / §8.15.
- CPD-A–D identifiers remain stable; do not rename or merge.
- Cap Store ≠ CapVer ≠ IC ≠ Path ≠ Membership ≠ Stage-3.
- Missing Cap ≠ auto-persisted UNVERIFIED.
- STALE ≠ auto VERIFIED (D-10.5 / Cap domain B4 remain).
- WIP protection remains mandatory.
- Each implementation unit requires its own authorization and forensic closure.

### 8.16.22 — Preservation Completion

This §8.16 record preserves the authorized Capability Persistence Design Decisions **CPD-A** through **CPD-D**.

**FINAL STATUS FOR THIS RECORD: CAPABILITY PERSISTENCE DESIGN DECISIONS PRESERVED.**

**NEXT GATE: Capability Store Persistence Implementation Authorization Gate (separate; not performed by this preservation).**


---

## 8.17 — Continuity Archive — Unexecuted Prompt, Reports, and Current Stop Point

### 8.17.1 — Purpose

This section is an **append-only continuity archive** for the architectural work immediately following §8.16.

It records the relevant prompt, forensic reports, decisions, and conversation-derived stopping point that had not yet been persisted in ADR-023 at the time of archival.

**No previous ADR section is deleted, rewritten, merged, or replaced.**

This section is archival/continuity material. It does not silently authorize work that was previously marked as unauthorized.

### 8.17.2 — Current Repository Stop Point

At the time of this archive:

- Repository: `wsa-platform/WSA-Enterprise`
- Branch: `phase-18-m18-ai-marketing-communications`
- Expected/current HEAD: `12f70590052325da8caf274b6f7e7576cf72339d`
- Last committed architectural change: preservation of §8.16.
- §8.16 CPD-A through CPD-D: preserved successfully.
- Capability Store Persistence implementation: **NOT YET EXECUTED**.
- The previously prepared Capability Store Persistence Implementation Authorization Gate prompt was **not executed because the Cursor session expired before execution**.
- Existing WIP remains protected and must not be discarded, reset, stashed, cleaned, or rewritten.
- Closed units remain closed and must not be reopened merely because this archive exists.

### 8.17.3 — Exact Next Architectural Unit

The exact next unit remains:

**Capability Store Persistence Implementation Authorization Gate**

This is a gate before implementation, not an implementation task by itself.

The gate must:

1. verify the expected HEAD and branch;
2. verify ADR-023 §8.16;
3. verify the existing WIP state;
4. reconcile Capability domain/tests with CPD-A–D;
5. determine the exact persistence representation;
6. determine evidence-reference storage from repository/database evidence;
7. freeze CURRENT/HISTORICAL semantics;
8. freeze NULL-safe uniqueness;
9. freeze repository API;
10. freeze mapping boundary;
11. freeze DI binding;
12. freeze write-authority enforcement;
13. freeze the exact implementation file allowlist;
14. return **READY FOR IMPLEMENTATION** or **NOT READY**.

No implementation is permitted if the gate is NOT READY.

### 8.17.4 — Consolidated Continuity Findings

The preceding forensic sequence established:

- Capability domain exists and is tested.
- Capability Store production persistence was not yet implemented.
- CapVer producer/authority implementation was not present and must not be invented.
- Runtime Source Registry is separate and not part of this unit.
- The 109-source Membership Register is architectural membership, not a runtime registry or Capability Store.
- IC persistence is closed and may be used only as a structural Laravel/Eloquent pattern reference.
- Capability persistence must not copy IC ACTIVE/SUPERSEDE lifecycle semantics.
- Capability evidence is an opaque reference list, not a new Evidence entity.
- Capability facts are append-only.
- CURRENT/HISTORICAL is Cap-native.
- `seat_override_applied` is the persistence layer distinction.
- Shared and override CURRENT rows may coexist for the same subject/dimension.
- Current uniqueness must be PostgreSQL NULL-safe.
- Ambiguous CURRENT state must fail closed.
- There is no latest-wins rule.
- Missing Cap must not automatically create a persisted UNVERIFIED row.
- VERIFIED assignment remains CapVer-only.
- Persistence may store an already-authorized VERIFIED fact without becoming CapVer.
- Population authority remains open and must not be invented.
- Shared canonical → seat inheritance remains deferred.
- CapabilityRecord evidence-field/test alignment remains an implementation concern and must not be silently changed before the authorization gate resolves the boundary.

### 8.17.5 — Previously Prepared Master Prompt

The following is the authoritative prompt prepared for the next Cursor execution. It is archived here so the next session can continue without reconstructing the task from chat history.

```text
# WSA-Enterprise — MASTER PROMPT
# Capability Store Persistence Implementation Authorization Gate
# + Conditional Bounded Implementation + Verification + Commit/Push
#
# IMPORTANT:
# This is an architecture-first, forensic, fail-closed task.
# Do NOT begin implementation before completing the Authorization Gate.
# The Authorization Gate is the authority for deciding whether implementation
# may proceed.
#
# Current expected HEAD:
# 12f70590052325da8caf274b6f7e7576cf72339d
#
# Current branch:
# phase-18-m18-ai-marketing-communications
#
# Previous architectural state:
# ADR-023 §8.16 — Capability Persistence Design Decisions (CPD-A–D)
# has already been preserved in the ADR.
#
# The exact next authorized unit is:
# Capability Store Persistence Implementation Authorization Gate
#
# Do not jump to unrelated accuracy fixes, Source Expansion onboarding,
# Runtime Source Registry, Stage-3 work, CSQ, QueryBuilder, AnswerComposer,
# AccuracyGate, RelevanceGate, Results List, Viewer, or IC redesign.

## PRIMARY OBJECTIVE

Perform a final forensic authorization gate for implementing Capability Store
Persistence based strictly on the current repository state and ADR-023 §8.16.

Two outcomes are permitted:

A) NOT READY
   Stop before implementation.
   Explain every blocking contradiction/open issue.
   Do not modify production code, tests, migrations, ADRs, or WIP.

B) READY FOR IMPLEMENTATION
   Freeze the exact implementation scope and file allowlist.
   Implement ONLY the authorized Capability Store persistence unit.
   Verify all required invariants and tests.
   Commit ONLY the authorized changes.
   Push only after all verification passes.
   Confirm local/remote HEAD synchronization.

## HARD SAFETY RULES

1. Never reset, revert, stash, clean, checkout unrelated changes, or discard WIP.

2. Before touching anything record:
   git status --short
   git branch --show-current
   git rev-parse HEAD
   git rev-parse origin/phase-18-m18-ai-marketing-communications
   git diff --stat
   git diff --name-only
   git diff --cached --name-only

3. Expected HEAD:
   12f70590052325da8caf274b6f7e7576cf72339d

4. If HEAD differs, STOP and report.

5. If branch differs, STOP and report.

6. Existing WIP is protected. Modify it only if it falls inside the exact
   authorized Capability Store persistence allowlist.

7. No broad formatting, automated refactoring, or unrelated lint fixes.

8. Do not modify ADR-023 during implementation unless a genuine architecture
   contradiction makes implementation impossible. In that case STOP rather
   than silently rewriting architecture.

9. Do not create:
   - CapVer service
   - CapVer evidence store
   - universal evidence store
   - population service
   - runtime source registry
   - source adapter
   - new capability onboarding mechanism
   - inheritance mechanism
   - IC replacement
   - new generic evidence abstraction
   - unrelated resolver/service

10. No silent semantic changes.

## FORENSIC SNAPSHOT

Inspect:
- Git state
- ADR-023
- Capability domain
- Capability tests
- IC persistence as pattern reference only
- Laravel/Eloquent conventions
- PostgreSQL migration conventions
- Service provider DI conventions
- existing repositories/interfaces
- existing Capability migrations/models
- existing persistence DTO/mapping conventions
- existing exception/invariant conventions

Search systematically for:
CapabilityRecord
CapabilityRecordId
CapabilityState
CapabilityDimension
CapabilitySubject
CapabilityEvidenceFreshness
CapabilityRequirementEvaluator
CapabilityDecisionIdentity
CapabilityStoreDomainContract
HistoricalGo1CapabilityState
CapabilityRepository
CapabilityStore
capability_evidence_ref
seat_override_applied
CURRENT
HISTORICAL

## ADR-023 §8.16 VERIFICATION

Verify CPD-A–D in full.

CPD-A:
- evidence is opaque immutable reference strings;
- no Cap Evidence body entity;
- no CapVer evidence store;
- no universal Evidence System;
- evidence identity is not CapRecordId, DecisionIdentity, sourceKey,
  IC classification decision identity, or identity_binding_ref;
- one Cap fact may have N refs;
- same ref may appear across facts;
- order is semantically irrelevant;
- duplicate identical refs inside one array are invalid;
- VERIFIED requires >=1 ref;
- PARTIAL requires >=1 ref + limitation;
- UNAVAILABLE requires >=1 ref;
- UNVERIFIED optional;
- NOT_APPLICABLE optional;
- MISSING is not persisted.

CPD-B:
- append-only immutable facts;
- state change creates a new fact;
- previous CURRENT becomes HISTORICAL;
- no in-place semantic mutation;
- Cap-native CURRENT/HISTORICAL;
- no IC ACTIVE/SUPERSEDE;
- CURRENT is explicit, not latest timestamp;
- historical facts are retained but excluded from ordinary current evaluation;
- supersession is atomic;
- CapabilityRecordId identifies the immutable fact;
- DecisionIdentity remains separate.

CPD-C:
- persistence identity = CapabilitySubject + CapabilityDimension +
  seat_override_applied;
- false = shared/base;
- true = seat override;
- no override_id, seat_id, dossier_id;
- shared + override CURRENT coexistence is valid;
- evaluator restrictive-wins;
- no shared-to-seat inheritance.

CPD-D:
- <=1 CURRENT for:
  (adr_id, canonical_identity_id, dimension_family, dimension_code,
   seat_override_applied)
- CapabilityRecordId globally unique;
- evidence refs are not part of natural uniqueness;
- historical rows may share natural tuple;
- PostgreSQL nullable uniqueness must be dialect-safe;
- application must fail closed on >1 CURRENT.

## DOMAIN VS ADR RECONCILIATION

Current CapabilityRecord does not contain capability_evidence_ref[].

Current tests may construct VERIFIED records without evidence.

Do not silently mutate the domain.

Determine the correct boundary from repository evidence:
- persistence representation/DTO,
- bounded domain evolution if explicitly justified,
- or existing mapping convention.

If unresolved without guessing:
NOT READY.

## EVIDENCE REFERENCE STORAGE

Determine from actual PostgreSQL/Laravel conventions whether refs belong in:
- JSON/JSONB array,
- text array,
- child table,
- or another existing supported representation.

Do not choose by preference.

Preserve:
- opaque identity,
- exact string,
- immutability,
- duplicate detection,
- historical correctness,
- semantic order irrelevance.

Do not create an evidence body table.

## PERSISTENCE MODEL

Represent only approved facts, including where applicable:
- CapabilityRecordId
- existing identity fields
- canonical identity
- dimension family/code
- capability state
- limitation
- freshness where already part of domain
- seat_override_applied
- CURRENT/HISTORICAL
- evidence refs
- timestamps only if existing conventions require them

Do not invent semantic fields.

## WRITE AUTHORITY

Capability Store != CapVer.

Persistence may store an already-authorized VERIFIED fact but may not:
- assign scientific verification authority,
- perform CapVer,
- create verification events,
- create CapVer evidence,
- decide scientific truth,
- become organizational authority.

CapVer-only VERIFIED assignment remains closed.

Do not invent population workflows.

## REPOSITORY API

Design the smallest repository contract actually required.

No generic CRUD.
No delete for immutable facts.
No in-place semantic update.
No timestamp-based latest.
No speculative methods.

Determine current-load, append, and atomic supersession needs from actual
consumers and conventions.

## CURRENT/HISTORICAL

Enforce:
- immutable identity;
- no semantic update;
- state change = new fact;
- previous current -> historical;
- <=1 current per natural tuple/layer;
- shared + override may coexist;
- ambiguous current fails closed;
- historical excluded from ordinary evaluation.

## NULL-SAFE UNIQUENESS

Inspect nullable natural-key fields.

Do not rely on naive PostgreSQL UNIQUE.

Use a proven project-compatible strategy such as:
- partial unique indexes,
- UNIQUE NULLS NOT DISTINCT where supported,
- or equivalent dialect-safe enforcement.

Also add application-level fail-closed behavior.

## DUPLICATE / INVALID DATA

Reject:
- duplicate CapabilityRecordId;
- duplicate current same layer;
- invalid/empty evidence refs;
- duplicate identical ref in one fact;
- VERIFIED/PARTIAL/UNAVAILABLE without required refs;
- PARTIAL without limitation;
- malformed override;
- invalid history transition;
- illegal immutable fact mutation.

Allow:
- same evidence ref across facts;
- shared + override current coexistence;
- historical duplicate natural tuple;
- optional evidence for UNVERIFIED/NOT_APPLICABLE.

## IC COMPARISON

Inspect IC persistence only for:
- DI,
- repository structure,
- Eloquent,
- transactions,
- tests,
- provider registration.

Do not copy IC:
- ACTIVE/SUPERSEDE,
- classification decision identity,
- evidence semantics,
- duplicate rules,
- write authority,
- FK assumptions,
- idempotency semantics.

## MIGRATION / ELOQUENT / MAPPING / DI

Inspect actual project conventions before deciding:
- table name,
- columns,
- types,
- nullability,
- indexes,
- unique constraints,
- lifecycle representation,
- evidence representation,
- model,
- casts,
- mapping,
- provider binding.

Do not invent foreign keys to conceptual/nonexistent authorities.

Persistence model is not the domain entity.

## TEST PLAN

Freeze tests before implementation.

Minimum categories:
- repository contract
- round-trip
- evidence refs
- evidence validation
- state/evidence rules
- current/history
- append-only
- duplicate current
- NULL-safe uniqueness
- shared/override coexistence
- same-layer rejection
- ambiguous-current fail closed
- duplicate record ID
- historical coexistence
- transaction
- DI
- mapping round-trip

## AUTHORIZATION DECISION

Produce:

CAPABILITY STORE PERSISTENCE IMPLEMENTATION GATE

HEAD:
BRANCH:
WORKTREE:
WIP STATUS:

ADR-023 §8.16:
- present
- CPD-A verified
- CPD-B verified
- CPD-C verified
- CPD-D verified

DOMAIN:
DATABASE:
WRITE AUTHORITY:
REPOSITORY:
TESTS:

DECISION:
[READY FOR IMPLEMENTATION]
or
[NOT READY]

BLOCKERS:
...

AUTHORIZED SCOPE:
...

AUTHORIZED FILE ALLOWLIST:
...

FORBIDDEN SCOPE:
...

## NOT READY RULE

If any of these is unresolved:
- ADR conflict
- evidence ownership
- evidence storage representation
- domain boundary
- CURRENT/HISTORICAL
- NULL-safe uniqueness
- write authority
- repository contract
- migration dependencies
- WIP isolation
- branch/HEAD mismatch
- architecture contradiction
- need to reopen unrelated closed unit

then STOP, do not modify, do not commit, do not push.

## READY RULE

Only if all gates pass:
- freeze architecture;
- freeze schema;
- freeze repository API;
- freeze mapping;
- freeze DI;
- freeze tests;
- freeze file allowlist.

Then implementation may begin.

## BOUNDED IMPLEMENTATION

Implement only the frozen Capability Store persistence unit.

Potential categories:
- repository interface
- repository implementation
- Eloquent model if justified
- migration(s)
- mapper/DTO if justified
- DI binding
- tests

Do not reopen:
- AnswerComposer
- AccuracyGate
- RelevanceGate
- Units A/B/C
- Results List
- Viewer
- IC
- Stage 3
- CSQ
- QueryBuilder
- Source Registry
- 109-source onboarding

Do not modify ADR-023 during normal implementation.
Do not modify pre-existing WIP.

## IMPLEMENTATION INVARIANTS

Continuously verify:
A. opaque evidence refs
B. immutable refs
C. duplicate ref rejection inside fact
D. same ref across facts allowed
E. VERIFIED evidence required
F. PARTIAL evidence + limitation required
G. UNAVAILABLE evidence required
H. UNVERIFIED optional
I. NOT_APPLICABLE optional
J. explicit CURRENT
K. explicit HISTORICAL
L. append-only
M. no latest-wins
N. shared + override coexistence
O. same-layer duplicate rejection
P. NULL-safe uniqueness
Q. ambiguous current fail closed
R. persistence is not CapVer
S. no CapVer evidence store
T. no population service
U. no inheritance
V. no IC lifecycle leakage

## TEST EXECUTION

Run focused tests first, then the narrowest relevant broader suite.

Do not fix unrelated pre-existing failures.

Newly introduced failures must be fixed only within authorized scope.

## STATIC VERIFICATION

Search the diff for:
latest
orderBy
first
last
update
delete
ACTIVE
SUPERSEDE
CapVer
Evidence
sourceKey
seat_id
override_id
dossier_id

Every occurrence must be justified.

## GIT DIFF FORENSICS

Before commit:
git status --short
git diff --name-only
git diff --stat
git diff --check
git diff

Every changed file must belong to the frozen allowlist.

If unrelated files changed:
STOP and preserve them; do not revert automatically.

## COMMIT

Only after:
- tests pass,
- schema verified,
- diff clean,
- no unauthorized files,
- no WIP contamination,
- CPD-A–D verified.

Suggested commit:
feat(research): implement capability store persistence

## PUSH

Push only after commit verification.

Then:
git status --short
git rev-parse HEAD
git rev-parse origin/phase-18-m18-ai-marketing-communications

Require local HEAD == origin HEAD.
No force push.
No rebase.
No unrelated amend.

## FINAL REPORT

Return:
1. gate result
2. git baseline
3. ADR §8.16 verification
4. forensic findings
5. persistence design
6. exact file allowlist
7. implementation result
8. test results
9. static verification
10. commit/push state
11. final status
12. next exact architectural unit

## ABSOLUTE FINAL RULE

Architecture authority order:

1. ADR-023 §8.16
2. approved Capability domain contracts
3. repository/database conventions
4. test contracts
5. IC persistence only as structural pattern
6. implementation inference only where all above agree

If evidence is insufficient:
STOP and report NOT READY.

If sufficient:
freeze the boundary, implement only that boundary, verify, commit, push,
and report the exact final state.
```

### 8.17.6 — Conversation-Derived Operational Report

The conversation immediately preceding this archive established the following:

- The user requested preservation of the current stopping point because Cursor's session expired before executing the prepared prompt.
- The prepared prompt was explicitly identified as the next step.
- The user then requested that all relevant prompts, reports, and unpersisted conversation-derived architectural material be preserved in the architectural decision without deleting old content.
- This section is the durable record of that request and its resulting continuity state.
- The intent is continuity across a new Cursor session/chat without requiring reconstruction of the architectural path.

### 8.17.7 — Non-Deletion / Append-Only Rule

This archive does not replace:

- §8.11
- D-10.5
- §8.14
- §8.15
- §8.16
- any earlier ADR material.

Future archival additions must be appended as new subsections.

Do not delete historical prompts or reports merely because a later prompt supersedes them. If a later decision changes an earlier proposal, preserve the earlier proposal and explicitly record the superseding decision.

### 8.17.8 — Continuation Rule

When work resumes, the first action is to execute the archived **Capability Store Persistence Implementation Authorization Gate** against the actual repository state.

Do not assume that expiration of Cursor changes authorization status.

Do not assume that archival of this prompt equals implementation authorization.

**FINAL STATUS: CONTINUITY MATERIAL PRESERVED; IMPLEMENTATION NOT YET EXECUTED.**

**NEXT GATE: Capability Store Persistence Implementation Authorization Gate.**


---

## 8.18 — Capability Store Persistence Implementation and Post-Persistence Readiness Record

**Status:** APPEND-ONLY CONTINUITY RECORD

**Date:** 2026-10-09

**Scope:** Records (1) the implementation, commit, and push of Capability Store Persistence and (2) the read-only Post-Persistence Runtime Readiness discovery that followed. **Does not authorize any implementation unit.**

**Chronology:** Follows §8.17. §8.17.2 and §8.17.8 recorded Capability Store Persistence as **NOT YET EXECUTED** at the time of that archive (archive HEAD `12f70590052325da8caf274b6f7e7576cf72339d`; archive committed as `0b463955669fe117a318898059683a62d1ecb63d`). That statement is preserved unchanged as historical record. This section records the later implementation as a new historical event and does not rewrite §8.17.

Evidence labels used in this section:

| Label | Meaning |
|-------|---------|
| **[V]** | Verified directly in the repository on 2026-10-09 |
| **[R]** | Verified against the live remote (`git ls-remote`) on 2026-10-09 |
| **[P]** | Reported by an earlier Cursor run on 2026-10-09; not re-run for this record |
| **[I]** | Inferred from the cited evidence |
| **[U]** | Not verified |

### 8.18.1 — Implementation Record

#### Authorization and delivery

- Authorization: the §8.17.8 Capability Store Persistence Implementation Authorization Gate was first run and returned NOT READY because no test runtime was available [P]. According to the 2026-10-09 conversation record (user instructions, as relayed in earlier Cursor run reports; not repository artifacts), implementation then proceeded under a later user-issued Capability Store Persistence implementation prompt, followed by a user-issued final safe-completion prompt, and the push was authorized separately by the user [P]. These user instructions authorized only the bounded eight-file unit and its delivery; they are not ADR architectural decisions [I].
- Implementation commit: `31a6ee3984c800ff771508f8e496ea75e2cd0938` — `feat(research): persist capability store facts` [V]
- Parent: `0b463955669fe117a318898059683a62d1ecb63d` [V]
- Scope: 8 files, 1139 insertions, 0 deletions [V]:
  - `backend/app/Models/CghiaCapabilityCell.php`
  - `backend/app/Providers/AgriculturalIntelligenceServiceProvider.php` (container binding only, +7/−0)
  - `backend/app/Services/Agriculture/Research/Capability/Persistence/CapabilityCellFact.php`
  - `backend/app/Services/Agriculture/Research/Capability/Persistence/CapabilityCellLifecycle.php`
  - `backend/app/Services/Agriculture/Research/Capability/Persistence/CapabilityStoreRepository.php`
  - `backend/app/Services/Agriculture/Research/Capability/Persistence/EloquentCapabilityStoreRepository.php`
  - `backend/database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php`
  - `backend/tests/Unit/Agriculture/Research/Capability/Persistence/CapabilityStorePersistenceTest.php`
- Push: normal fast-forward `0b46395..31a6ee3` to `origin/phase-18-m18-ai-marketing-communications` on 2026-10-09. Live remote branch verified at `31a6ee3984c800ff771508f8e496ea75e2cd0938`; local / remote ahead-behind 0/0 [R].
- Protected WIP was not included in the commit [V].

#### CPD-A–D implementation boundaries

| Decision | Implementation | Limitation |
|----------|----------------|------------|
| CPD-A evidence | `CapabilityCellFact` stores opaque `capability_evidence_ref[]`; rejects non-string, blank, whitespace-padded, and duplicate refs; requires ≥1 ref for VERIFIED / PARTIAL / UNAVAILABLE; set semantics (sorted) [V] | No evidence artifact resolver; refs are neither resolved nor checked for sufficiency (consistent with CPD-A) [V] |
| CPD-B current / history | Explicit `cell_lifecycle` CURRENT / HISTORICAL; `supersedeCurrent()` is atomic (transaction, row lock, natural-key and CURRENT-head checks); repository exposes no update or delete operation [V] | Append-only and immutability are enforced at repository level [V]. The inspected migration defines no database trigger or privilege restriction enforcing append-only immutability [V]; operational database permissions and external database-level controls were not independently verified [U] |
| CPD-C override layer | `seat_override_applied` is part of the natural key; shared and seat-override CURRENT facts coexist [V] | Shared → seat inheritance not implemented (deferred to Population Design, §8.16.9) [V] |
| CPD-D uniqueness | `capability_record_id` unique; two partial unique indexes (canonical NULL / non-NULL) for single CURRENT; fail-closed on ambiguous CURRENT in `findCurrentFact()` and `loadCurrentForSubject()` [V] | PostgreSQL behaviour of the partial indexes is prior-run evidence [P] |

Additional boundaries:

- **CapVer-only assignment of VERIFIED is NOT enforced in code.** Any caller of `CapabilityStoreRepository::persistCurrent()` can persist a VERIFIED fact, and no column records the assigning authority. Enforcement placement remains deferred under §8.16.14 [V].
- Missing cells are not auto-inserted as UNVERIFIED [V].
- Restrictive-wins is applied by `CapabilityRequirementEvaluator::evaluate()`. The pre-existing `CapabilityRequirementEvaluator::resolveState()` returns the first matching record and does not apply restrictive-wins; it has no production caller and was not changed by this commit [V].
- The commit does not modify CSQ, `ScientificSearchQueryBuilder`, `AgriculturalEntityCatalog`, Selector, Stage-3, or the orchestrator [V].

#### Test evidence

| Evidence | Result | Label |
|----------|--------|-------|
| Capability + Integration Classification + Identity unit tests, SQLite `:memory:` | 112 passed / 486 assertions (2026-10-09) | [V] |
| `CapabilityStorePersistenceTest` | 27 test methods, included in the 112 | [V] |
| PostgreSQL 16 on an isolated ephemeral container: migration up/down, partial unique indexes, repository behaviour | PASS | [P] — not re-run |
| Full `tests/Unit/Agriculture/Research` suite | 899 passed / 21 failed | [P] — not re-run |

The 21 failures (5 test classes) have **no verified pre-commit baseline** and are **not** recorded as proven pre-existing. None of the five classes references a file in this commit [V]; four depend on `CanonicalScientificQuestion` and/or `QueryUnderstandingService`, which carry uncommitted WIP [V]. Two of the classes (`EntitySpecificityPreservationContractTest`, `ScientificStatisticalClaimAlignerTest`) extend `Tests\TestCase` and therefore boot the application, including the provider modified by this commit [V]; whether that affects their results was not established [U]. Attribution of the failures to that WIP is an inference only [I].

### 8.18.2 — Post-Persistence Runtime Readiness Discovery

Read-only discovery on 2026-10-09. No code, schema, database, registry, or configuration was changed by the discovery.

#### Findings

- Membership register `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` (`membership_revision` `109.v1`): 109 unique CURRENT seats; G1 22, G2 25, G3 14, G4 7, G5 19, G6 22; G3-15 absent by decision; 10 `dual_pairs` [V]. It remains **membership authority only** (D-10.3.1). Membership ≠ identity binding ≠ Capability facts ≠ adapter ≠ activation ≠ runtime readiness.
- Seats with an ADR identity binding to a Stage-3 `sourceKey`, a Capability fact written by production code, an adapter, or a seat activation state: **none found** in the inspected scope [V]. No D-10.10 seat activation-state model was identified in the inspected implementation [I]. FAOSTAT-specific domain activation constructs (`FaoStatDomainActivationState`, `FaoStatActivationPolicy`) exist for FAOSTAT domains used by the Stage-3 `fao_stat` adapter [V]; they do not establish implementation of the general ADR seat activation-state model and do not activate any of the 109 seats [I].
- Capability Store: **no production writer or reader** found in application code [V]. A search of `backend/` (excluding `vendor/`, `e2e-tmp/`, and `tests/`) for `CapabilityStoreRepository`, `EloquentCapabilityStoreRepository`, `CghiaCapabilityCell`, and `cghia_capability_cell_records` found, outside the Capability Store implementation files (persistence package, model, and migration), only the container binding in `AgriculturalIntelligenceServiceProvider` [V]. Contents of any non-test database: **not verified** [U].
- CapVer: no class, service, or policy acting as CapVer was identified in `backend/app` (class / enum / interface name search for CapVer-equivalent authorities, plus inspection of the repository write path) [V].
- Runtime Source Registry: **none identified** in the inspected implementation [I]. `ScientificSourceAdapterRegistry` is a fixed registry of five Stage-3 adapters (`openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`) and is not a registry of the 109 seats [V].
- The only production call site of `CapabilityRequirementEvaluator::evaluate()` found in `backend/app` is the private `Stage3CghiaCoexistenceBoundary::planAdrBoundSource()`, reached from `plan()` only in `cghia_attached` mode for `ADR_BOUND` keys [V]. `MultiSourceScientificSearchOrchestrator` calls `plan()` without Capability records [V].
- Coexistence mode defaults to `legacy_only` (`config/agricultural_intelligence.php`, `CGHIA_COEXISTENCE_MODE`) [V]. `MultiSourceScientificSearchOrchestrator` is resolved through the Laravel container (constructor injection into `AgriculturalScientificSearchService`) [V]. The orchestrator declares `new Stage3CghiaCoexistenceBoundary()` and the boundary declares `new Stage3SourceKeyIdentityBridge()` as constructor defaults; the bridge's `array $adrBindingsBySourceKey` defaults to `[]` [V]. No explicit or contextual container binding for the orchestrator, boundary, or bridge, and no code supplying `adrBindingsBySourceKey`, was found in `backend/app`, `backend/config`, `backend/bootstrap`, or `backend/routes` [V]. Whether the container autowires the boundary and bridge or uses the declared defaults was not verified against framework source; on either path the inspected code supplies no ADR bindings, so `Stage3SourceKeyIdentityBridge::resolve()` classifies the five Stage-3 keys as `EXTERNAL_ONLY` in both coexistence modes [I]. In `legacy_only`, `Stage3CghiaCoexistenceBoundary::plan()` records that classification on each plan entry only; retrieval proceeds on the legacy path, no Capability/Path/Projection artifacts are attached, and the only event is `LEGACY_COMPATIBLE` [V]. In `cghia_attached`, an `EXTERNAL_ONLY` entry carries the events `EXTERNAL_ONLY_NO_ADR_BINDING`, `CAP_UNVERIFIED`, `PATH_DEFERRED`, and `LEGACY_COMPATIBLE`; retrieval remains permitted, `CapabilityRequirementEvaluator` is not invoked, and no Capability/Path/Projection artifacts are attached [V]. `EXTERNAL_ONLY` is an identity classification only; it does not indicate source readiness, Capability Store population, retrieval success, or activation [I]. The configured mode of any deployed environment was not verified [U].
- IU-07 Durable Correlation Persistence (`3a14c76`), built on the IU-06 B7 Correlation Domain Contract (`d0a4dca`), is a separate track from Capability Store persistence [V]. The IU-07 repository is not bound in `backend/app/Providers` and is optional (default `null`) in `Stage3CghiaCoexistenceBoundary` [V]. Capability Store Persistence is not evidence about IU-06 / IU-07, and vice versa [I].
- Ordering: D-10.2.1 (Capability-first runtime eligibility) and D-10.8.1 (verified per-seat IC artifact before runtime binding) both apply. No explicit decision fixing the order of Capability population versus IC inventory population was identified in the reviewed scope (D-10, ADR-023 §8.13–§8.16, and a keyword search of ADR-023) [I].

#### Result

**DISCOVERY COMPLETE. NO NEXT IMPLEMENTATION UNIT IS AUTHORIZED.**

**Recommended next gate (recommendation only — NOT an accepted or authorized decision):** CapVer Authority & Capability Population Design Gate (design / governance only). Opening it requires an explicit user decision. Questions it would need to address include CapVer organizational ownership, auditability of the assigning authority, evidence sufficiency, STALE re-verification, shared → seat inheritance, and the order of Capability population versus IC inventory population.

#### Non-authorization

This §8.18 record does **not** authorize:

- CapVer implementation or Capability population;
- IC inventory population;
- Runtime Source Registry;
- Path or Projection population;
- Onboarding;
- adapters;
- Selector, Stage-3, or orchestrator changes;
- activation of any seat;
- implementation of any other unit.

Runtime readiness discovery does not authorize onboarding or activation. No earlier section is deleted or rewritten by this record.

**FINAL STATUS: CAPABILITY STORE PERSISTENCE IMPLEMENTED AND PUSHED; NO NEXT IMPLEMENTATION UNIT AUTHORIZED.**

**RECOMMENDED NEXT GATE (NOT AUTHORIZED): CapVer Authority & Capability Population Design Gate.**

## 8.19 — IC Persistence Integrity (D1–D6) Implementation Record

**Status:** APPEND-ONLY CONTINUITY RECORD

**Date:** 2026-10-09 (status reconciled 2026-10-10)

**Scope:** Records the implementation and verification of the user-approved IC Persistence Integrity decisions D1–D6 for Integration Classification (IC) Persistence (§8.13–§8.15). The implementation is **committed and pushed** as `2b856a2fd235c677e5511320ee11d47687806364` (`fix(research): normalize IC evidence reference replay`; parent `f7b9b7105e4dd36ef9b9c97692bab39d91e19585`) on `phase-18-m18-ai-marketing-communications` [V]. This §8.19 record itself is still an **uncommitted** ADR-023 working-tree change; committing it requires separate user authorization. No earlier section is rewritten.

Evidence labels: **[V]** verified by a run or inspection in this session (2026-10-09 to 2026-10-10); **[I]** inferred from cited evidence; **[U]** not verified.

### 8.19.1 — Approved decisions and how they were applied

| Decision | Applied as |
|----------|-----------|
| D1 = C | New migration `2026_10_09_190000_add_single_active_index_to_cghia_integration_classification_records.php` creates partial unique index `cghia_ic_records_single_active_uq` on `(adr_id) WHERE lifecycle_state = 'ACTIVE'`. The committed migration `2026_10_02_230000_…` is unchanged. Zero ACTIVE rows per `adr_id` stay valid; no anchor table or active pointer [V] |
| D2 = C | `persist()` checks for an existing ACTIVE and inserts in one `DB::transaction`; a unique violation is resolved only after that transaction (or its savepoint, when nested) has rolled back, so no statement runs inside an aborted PostgreSQL transaction. `supersede()` runs lock → checks → transition → insert → pointer in one transaction without manual restore; the original exception propagates. `invalidate()` uses a transaction plus `lockForUpdate()`. Unique violations are classified by SQLSTATE (`23505`; SQLite `23000` + `UNIQUE constraint failed`) and by constraint name taken from the driver message, not from the SQL text. Other errors are rethrown unchanged. No write is retried; the only recovery after a unique violation is one read-only idempotency lookup [V] |
| D3 = B | Replay requires equality on `IntegrationClassificationPersistenceContract::REPLAY_CONTRACT_FIELDS` (18 fields), compared after the normalization in `IntegrationClassificationRecord::draftAttributes()`: modality claims are a sorted set, optional references are trimmed with blank treated as null, null equals null, and the evidence-reference list (entries trimmed, null stored as empty) is compared as an unordered multiset: the repository compares sorted copies (`SORT_STRING`), so a different order alone is not a mismatch, while a changed, added, or removed entry is, and duplicates are kept and counted (whether duplicates carry meaning is not decided; see §8.19.5); the stored order is never rewritten (commit `2b856a2`). The compared fields are the 11 audit-candidate fields plus the IC-authoritative structured references (`identity_binding_ref`, `existing_adapter_reference`, `external_dependency_reference`, `evidence_references`, `license_reference`, `access_reference`, `reuse_reference`). Excluded fields: free text (`rationale`, `source_specific_rationale`), provenance and wall-clock fields (`decision_actor`, `verified_at`, `decision_timestamp`), non-authoritative `metadata`, and lifecycle/storage columns. Mismatch ⇒ `IntegrationClassificationIdempotencyConflict` listing field names only. A matching replay of a SUPERSEDED or INVALIDATED record returns that record as stored, with its `lifecycleState` visible; nothing is written or reactivated, and `findCurrentByAdrId()` remains the only current-head read. `persist()` signature and `IntegrationClassificationIdempotency::computeKey()` are unchanged; caller-supplied keys remain authoritative [V] |
| D4 = B | `supersede()` order: validate the new payload → lock the prior → prior exists and shares `adr_id` → look up the new key. If the key exists, the stored replacement is returned only if it is not the prior itself, its contract matches (including `adr_id`), and `prior.superseded_by` points to it; otherwise `IntegrationClassificationIdempotencyConflict` with no write. If the key is new, the prior must be ACTIVE. A replacement is always a newly inserted row, so `superseded_by` cannot be self-referencing, cross-ADR, or cyclic. `invalidate()` and `supersede()` serialize on the prior's row lock; the loser is rejected as not ACTIVE. No new FK or CHECK constraint [V] |
| D5 = A | Opt-in PostgreSQL path: `backend/phpunit.pgsql.xml` + `backend/tests/bootstrap-pgsql.php` on connection `pgsql_testing`. It requires explicit `DB_TEST_HOST`, `DB_TEST_PORT`, `DB_TEST_DATABASE`, `DB_TEST_USERNAME`, and `DB_TEST_PASSWORD`, plus `DB_TEST_ALLOW_DESTRUCTIVE_RESET` repeating the database name exactly. It refuses a database not ending in `_test` or listed in `FORBIDDEN_TEST_DATABASES`, a non-loopback host, a non-empty `DB_URL`/`DATABASE_URL`, and conflicting connection variables; before any test runs it checks, read-only, that the server holds no database other than the target and the PostgreSQL system databases (`tests/Support/PostgresTestDatabaseGuard.php`). It pins all `DB_*` variables to the test server, and never falls back to SQLite. The default `phpunit.xml` / `tests/bootstrap.php` SQLite path is unchanged [V] |
| D6 = A+B | Before creating the index, the migration runs a read-only preflight. If any `adr_id` holds more than one ACTIVE row, it fails, naming each such `adr_id` with its count (at most 50 listed), and changes nothing. It has no repair, winner selection, or lifecycle rewrite. Unsupported drivers (anything other than pgsql or sqlite) fail explicitly. `down()` drops the index [V] |

### 8.19.2 — Changed files (implementation commit `2b856a2`)

Modified [V]:

- `EloquentIntegrationClassificationRepository.php`: D2–D4 behaviour, including the order-insensitive `evidence_references` replay comparison (`replayComparable()`).
- `IntegrationClassificationRepository.php`: PHPDoc contract only; signatures unchanged.
- `IntegrationClassificationPersistenceContract.php`: adds `SINGLE_ACTIVE_INDEX` and `REPLAY_CONTRACT_FIELDS`.
- `IntegrationClassificationInvariantViolation.php`: `final` removed so the two conflicts can subclass it; existing callers that catch it are unaffected.
- `IntegrationClassificationPersistenceTest.php`: `test_multiple_active_fail_closed_does_not_return_latest` first drops the new index to model legacy pre-constraint corruption; its assertions are unchanged.

Added [V]:

- `IntegrationClassificationIdempotencyConflict.php`
- `IntegrationClassificationActiveConflict.php`
- The D1/D6 migration above
- `phpunit.pgsql.xml`
- `tests/bootstrap-pgsql.php`
- `tests/Support/IntegrationClassificationFixtures.php`
- `tests/Support/PostgresTestDatabaseGuard.php`: the D5 guard called by `tests/bootstrap-pgsql.php`.
- `tests/Unit/Support/PostgresTestDatabaseGuardTest.php`: guard rejection and acceptance tests; no database connection.
- `IntegrationClassificationPersistenceIntegrityTest.php`: engine-agnostic.
- `tests/Pgsql/IntegrationClassificationPostgresConcurrencyTest.php`
- `tests/Pgsql/bin/ic-invalidate-worker.php`: runs `invalidate()` in a separate process for the concurrency test.

Commit `2b856a2` contains exactly these 16 paths (5 modified, 11 added) [V]. FS-01-ID, Capability Store, providers, `config/`, Docker, scripts, CI, and this ADR are not part of it [V].

### 8.19.3 — Test evidence (2026-10-09)

| Suite | Engine | Result | Label |
|-------|--------|--------|-------|
| IC persistence (30) + Identity Binding persistence (22) + `SourceIdentityDomainContractTest` (13), before the change (baseline) | SQLite `:memory:` | 65 passed / 299 assertions | [V] |
| Earlier working-tree state, superseded by the commit-content rows below: IC (legacy 30 + integrity 51) + Identity Binding + Capability Store persistence | SQLite `:memory:` | 163 passed / 676 assertions | [V] |
| Earlier working-tree state, superseded by the commit-content rows below: `phpunit.pgsql.xml`: IC legacy 30, IC integrity 51 (constraint, transaction, idempotency, lifecycle, migration preflight), 11 cross-connection tests | PostgreSQL 16.14 | 92 passed / 422 assertions | [V] |
| PostgreSQL bootstrap refusal (missing `DB_TEST_*`, name `wsa_enterprise`, name without `_test`) | none (refused before autoload) | 3 of 3 refused | [V] |
| Commit content: `phpunit.xml` over `tests/Unit/Agriculture/Research/IntegrationClassification`, `…/Identity`, `…/Capability`, and `tests/Unit/Support` (includes the guard tests and 3 unrelated `ScientificHttpRateLimitBudgetTest` tests) | SQLite `:memory:` | 204 passed / 800 assertions | [V] |
| Commit content: full `phpunit.pgsql.xml` (`tests/Unit/Agriculture/Research/IntegrationClassification` + `tests/Pgsql`) | PostgreSQL 16.14 | 108 passed / 639 assertions | [V] |
| New evidence-reference and supersede-recovery tests, against the code before `replayComparable()` (fail-first) | SQLite `:memory:` | 11 tests / 29 assertions; 4 errors, all `IntegrationClassificationIdempotencyConflict … [evidence_references]` on reordered references | [V] |
| The same new tests plus the committed-competitor concurrency test | PostgreSQL 16.14 | 12 passed / 64 assertions | [V] |
| `tests/bootstrap-pgsql.php` without `DB_TEST_ALLOW_DESTRUCTIVE_RESET` | none (configuration check) | refused, exit 1 | [V] |
| Same PostgreSQL suite against the pre-change repository (counter-factual) | n/a | not executed | [U] |

"Commit content" rows ran on 2026-10-09 against working-tree files whose diff for the 16 committed paths is byte-identical to commit `2b856a2` (SHA-256 `0B3CFEEBEC2143E130AECFD6C62A11C3A0068CFEF7D1967B37E8B46F45E13419`); they were not rerun after the commit [V].

The PostgreSQL runs used a throwaway `postgres:16-alpine` container with tmpfs storage, no volume, and no published port, and the server held no database other than the test database and system databases [V]. Correction to the earlier wording of this paragraph: the 92-test run attached the test containers to Docker's default bridge network, where the compose `postgres` service publishes `0.0.0.0:5432`, so that database was reachable from the test container; it was not contacted [I]. The 108- and 12-test runs started the server with `--network none`, and the PHP container shared only that container's loopback-only network namespace [V]. No production, Render, or shared database was migrated or inspected [I].

The cross-connection tests inject the competing request at the exact interleaving point, using an Eloquent model event in test code only, on a second independent PostgreSQL connection; one test instead runs `invalidate()` in a separate PHP process and polls `pg_blocking_pids` at 10 ms intervals until PostgreSQL reports it blocked. Lock waits are bounded by `lock_timeout` (SQLSTATE `55P03`); no fixed sleep is used to create an interleaving. They demonstrate:

- a concurrent first write leaves exactly one ACTIVE, and the loser receives `IntegrationClassificationActiveConflict` caused by `23505`;
- a concurrent same-key, same-contract request replays the committed record;
- a concurrent same-key, different-contract request raises `IntegrationClassificationIdempotencyConflict`;
- a unique violation inside a caller's transaction leaves that transaction usable;
- an SQL error during supersede surfaces its original SQLSTATE (`22012`) and leaves the prior ACTIVE;
- supersede vs supersede and supersede vs invalidate serialize without lost update, including an `invalidate()` that PostgreSQL reports blocked by an open supersede and that is rejected as not ACTIVE after it commits;
- a `persist()` racing an open `supersede()` on the same `adr_id` receives `IntegrationClassificationActiveConflict` at each tested interleaving;
- a supersede whose new key was already committed by another `adr_id` raises `IntegrationClassificationIdempotencyConflict` and leaves the prior ACTIVE (commit `2b856a2`).

### 8.19.4 — PostgreSQL vs SQLite and guarantee limits

- Concurrency guarantees are demonstrated on PostgreSQL only. SQLite `:memory:` cannot model cross-connection races. On SQLite the same partial index rejects duplicates and the logic tests pass [V].
- The standard compose `backend-test` service still runs on SQLite because `tests/bootstrap.php` forces it. PostgreSQL verification requires invoking `phpunit.pgsql.xml` explicitly against a disposable `*_test` database [V].
- Classifying a single-ACTIVE violation depends on the PostgreSQL constraint name in the driver message, or on the SQLite column list `<table>.adr_id`. Both were verified on the engines above [V]. Other engines are unsupported by the migration [V].
- The preflight and `CREATE UNIQUE INDEX` run in the migration transaction. A duplicate committed between them makes index creation fail with a database error instead of the preflight message, which is still a safe failure [I].
- Whether any deployed database holds duplicate ACTIVE IC rows was not checked. The deploy start script runs `migrate --force`, so such data would stop the migration with the preflight message until a reviewed manual repair is made [U]/[I].
- `scripts/deploy-production.sh` starts the new containers (`up -d`, line 45) before `migrate --force` (line 48), so new code can serve before the single-active index exists [V]/[I].
- Unique-violation recovery assumes READ COMMITTED, as the repository interface PHPDoc states. Under a caller transaction at REPEATABLE READ or SERIALIZABLE it is expected to fail closed; this is not tested [I]/[U].
- In `supersede()`, the recovery sub-branch that returns a legitimate replacement after a unique violation is not exercised by any test; under READ COMMITTED the prior's row lock is expected to make it unreachable [I]. The `IntegrationClassificationActiveConflict` translation and the rethrow branch are tested on both engines, and the committed-competitor `IntegrationClassificationIdempotencyConflict` branch on PostgreSQL [V].
- Two concurrent `persist()` calls that are both still uncommitted when they collide are not covered by a dedicated test [U].

### 8.19.5 — Open items and non-authorization

Still open:

- committing this ADR record (separate authorization); the implementation itself is committed in `2b856a2`;
- running the PostgreSQL suite in CI;
- the counter-factual run against the pre-change repository;
- a reviewed preflight on any real environment before deployment, and the deployment ordering in §8.19.4;
- test coverage for REPEATABLE READ / SERIALIZABLE callers, for two uncommitted concurrent `persist()` calls, and for the legitimate-replacement recovery sub-branch (§8.19.4);
- whether duplicate evidence references carry meaning; they are currently kept and counted, which is not a decision;
- reconciling §8.14.6, which lists supersession as insert new ACTIVE → mark prior SUPERSEDED, with D2/D4, which mark the prior SUPERSEDED before inserting the replacement and look up the new key before requiring an ACTIVE prior. This is a documentation decision on an earlier section and is not made here.

This record does **not** authorize:

- an IC production writer or consumer, IC inventory population, or IC activation (D7);
- CapVer or Capability population;
- a Runtime Source Registry;
- activation of any of the 109 seats;
- changes to FS-01-ID.

**FINAL STATUS: IC PERSISTENCE INTEGRITY D1–D6 IMPLEMENTED IN COMMIT `2b856a2` (PUSHED) AND VERIFIED ON POSTGRESQL 16 AND SQLITE; THIS ADR RECORD UNCOMMITTED; IC NOT ACTIVATED.**


---

## 8.20 — Stop Point: CI Investigation Interrupted by Local Disk Exhaustion (2026-10-10)

### 8.20.1 — Purpose and preservation rule

This section is append-only. It preserves the latest user-provided CI investigation report, the IC Persistence Integrity Remediation execution prompt, and the current stopping point. It does not replace, rewrite, or delete any earlier ADR content. Historical reports remain historical evidence and must not be treated as a fresh Git/WIP verification.

### 8.20.2 — Local environment failure

The investigation stopped because the local machine ran out of disk space. Shell commands failed with `ENOSPC: no space left on device`, including a minimal liveness command. The shell session appeared stuck; attempting to retrieve GitHub Actions logs did not return exit statuses. The investigator did not free disk space or delete files because that would have been a mutating operation and was outside the read-only investigation scope.

The likely affected disk was suspected to be the Windows system disk (possibly C:), but this was not confirmed. The cause of the disk exhaustion and the files consuming the space remain unknown.

### 8.20.3 — CI findings preserved from the interrupted investigation

Four GitHub Actions runs were reported as failed:

| Run | Commit | Reported state |
|---|---|---|
| [37932365430](https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37932365430) | `31a6ee3` | Completed / failure |
| [37944382988](https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37944382988) | `f7b9b71` | Completed / failure |
| [37990122413](https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37990122413) | `2b856a2` | Completed / failure |
| [37993458720](https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37993458720) | `ffea580` | Completed / failure |

Confirmed from the available log evidence for run `37993458720`:
- The backend, security, and stage10 jobs failed at container initialization because pulling `pgvector/pgvector:pg16` hit the unauthenticated Docker Hub pull rate limit; token requests to `auth.docker.io` also timed out. Three attempts ended with Docker pull exit code 1. Those jobs did not reach project tests in that run.
- `openapi` and `docker-validate` passed in all four runs.
- In the three earlier runs, stage10 reportedly passed. In the latest run it failed at image initialization, not at its tests.
- The frontend `npm run build` and mobile `flutter test` steps failed in all four runs according to the workflow summary, but their actual logs were not retrieved. Their causes remain unknown.
- The backend `php artisan test` and security `php artisan test --group=security` steps failed in the three earlier runs according to the workflow summary, but actual failing test names and errors were not retrieved. Their causes remain unknown.
- The current run did not provide evidence that backend/security tests pass or fail once PostgreSQL is available, because those steps never executed.
- The documentation-only nature of commit `ffea580` and earlier failures on commits predating it make attribution to that documentation change unlikely, but this is not a substitute for the missing logs.

Run links:
- https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37993458720
- https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37990122413
- https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37944382988
- https://github.com/wsa-platform/WSA-Enterprise/actions/runs/37932365430

### 8.20.4 — CI diagnosis: confidence boundaries

**Confirmed:** the Docker Hub rate limit / token timeout blocked container initialization for backend, security, and stage10 in run `37993458720`.

**Not diagnosed:** frontend build failures, mobile tests, backend tests in the three earlier runs, and security tests in the three earlier runs. The log retrieval was interrupted before their actual errors could be captured. Do not infer root causes from exit codes alone.

**Not verified:** whether the latest commit's tests pass after the Docker pull problem is resolved; whether the four runs have the same underlying test failure; and the complete end of the auth token error line.

### 8.20.5 — Last known Git/WIP state (not re-verified at stop)

Last verified immediately after the push:
- Branch: `phase-18-m18-ai-marketing-communications`
- HEAD: `ffea580a484861853f9c3786cf4d862d91e91cdf`
- Local branch and `origin` were reported synchronized.
- No staged changes were reported.
- WIP inventory: 580 entries (14 modified files and 566 untracked files).
- The latest push included commit `ffea580`, subject `docs(architecture): reconcile ADR-023 section 8.19`.

**Important:** after disk exhaustion, Git status and WIP were not re-checked. Treat the branch/HEAD/WIP information above as the last known snapshot, not the current verified state. No local files, staging, commits, pushes, tests, migrations, or CI reruns were performed during the interrupted read-only investigation.

### 8.20.6 — Safe resumption sequence

1. Recover disk capacity using a careful Windows storage inspection; do not delete project files, untracked WIP, or repository data indiscriminately.
2. Restart Cursor's terminal/shell environment and verify that basic commands complete and return exit statuses.
3. Re-check branch, HEAD, upstream, staged changes, modified files, untracked files, and WIP fingerprint before any repository operation. Preserve all existing WIP.
4. Resume read-only retrieval of the missing logs from the four recorded runs. Record exact failed steps, error text, and test names; do not rerun CI without separate approval.
5. Keep CI diagnosis separate from IC Persistence remediation. Do not change workflows, authenticate Docker pulls, use a mirror, or rerun GitHub Actions without separate authorization.
6. Resume the IC Persistence Integrity Remediation gate only after the local environment is stable and the current Git/WIP state is verified. The CI report alone does not close that gate.

### 8.20.7 — Preserved IC Persistence Integrity Remediation master prompt

The following execution brief was provided to Cursor. It is preserved here so the work can resume without reconstructing the prompt. Its restrictions remain part of the intended task.

**Mission:** Complete IC Persistence Integrity Remediation, resolve verified integrity defects in scope, test on the actual supported database engine, and return evidence-backed gate status. This is continuation of existing work, not a new implementation or a repeat of the completed forensic audit.

**Git/WIP safety:** Establish a read-only baseline before changes; preserve every modified, staged, and untracked file; use a path-level ownership map. Prohibited: reset, clean, stash, checkout, switch, restore, rebase, merge, cherry-pick, revert, deleting/overwriting unrelated WIP, staging unrelated files, commit, push, source activation, or unrelated CI changes. If a required file overlaps protected WIP and safe isolation is uncertain, stop that edit and report the conflict.

**In-scope investigation and remediation:**
- ACTIVE-record concurrency and uniqueness, with database-level enforcement where required by the contract; pre-existing duplicate ACTIVE rows require an explicit auditable repair rule.
- Transaction boundaries, supersede ordering, rollback, and failure transitions; do not invent policy when the ADR is ambiguous.
- Idempotency/replay: compare `evidence_references` order-insensitively using a sorted local copy; preserve duplicates and persisted ordering; genuinely different evidence must not match; preserve `computeKey()`/`persist()` signatures and the 18-field contract unless an approved change is indispensable.
- Isolation and persist-vs-persist races, including uncommitted concurrent writers, unique violations, deadlocks, serialization failures, and bounded safe retries.
- PostgreSQL-specific proof on PostgreSQL itself; SQLite is not an acceptable substitute for PostgreSQL guarantees. If PostgreSQL cannot run, mark the gate blocked.
- Migration/deployment order, existing-data preflight, recovery paths, and stale verification behavior.
- Only implement minimal justified changes for confirmed defects; never weaken tests or assertions merely to get a green result.

**Execution phases:** (1) baseline and defect-to-file-to-test map; (2) minimal in-scope remediation; (3) focused, concurrency, idempotency, rollback, recovery, PostgreSQL, and regression tests; (4) acceptance audit mapping every finding to a fix and actual test evidence. Report exact commands, exit codes, test counts, database engine, and failures. Tests not executed must be marked NOT RUN.

**Architectural constraints:** Inspect ADR-023 and existing IC decisions. Do not edit ADR during the implementation task; provide a proposed ADR closure delta separately. Do not activate IC, populate capabilities, create Runtime Source Registry, activate any of the 109 source seats, or change FS-01-ID. Do not begin Capability Verification or onboarding in this task.

**Required final disposition:** Use exactly one:
- `READY_FOR_FORMAL_CLOSURE`: all technical criteria are evidenced, PostgreSQL verification passed, and only separately authorized formal ADR closure remains.
- `BLOCKED`: a required test, environment, architectural decision, or safety prerequisite remains unresolved.
- `NOT_CLOSED`: an in-scope integrity defect remains.

**Final report:** baseline Git/WIP, findings and root causes, files and migrations changed, test matrix with actual engine and evidence, concurrency/idempotency/supersede/rollback/recovery results, PostgreSQL proof, final diff and WIP preservation audit, proposed ADR delta, disposition, and one next action. No commit, push, source activation, or new workstream.

### 8.20.8 — Explicit stopping point

The user instructed to stop at this point and preserve the current state. No further shell/Git/CI investigation is authorized until the environment is recovered and the task is resumed.

Current immediate blocker: local disk exhaustion (`ENOSPC`) and unresponsive shell.

Next action: recover local disk capacity safely, reopen the shell, verify Git/WIP state, then finish read-only CI log collection. Afterward, return to IC Persistence Integrity Remediation without conflating it with the 109-source integration plan.

No claim is made here that IC Persistence Integrity Remediation is closed. No claim is made that the current local WIP inventory remains exactly 580 entries.
