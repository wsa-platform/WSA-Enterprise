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
