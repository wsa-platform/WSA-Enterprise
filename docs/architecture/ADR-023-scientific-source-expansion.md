# ADR-023 — Scientific Source Expansion

**Status:** ACCEPTED (governance + master-register freeze; implementation not started)  
**Date:** 2026-09-29 (ACCEPTED governance evolved through 2026-09-30)  
**Branch baseline:** `phase-18-m18-ai-marketing-communications` @ `505f3e6f5d29d087b1303794b224236784563d43`  
**Related:**
- [`ADR-internet-first-agricultural-ai-research-agent.md`](./ADR-internet-first-agricultural-ai-research-agent.md)
- [`../adr/ADR-001-provider-adapter-architecture.md`](../adr/ADR-001-provider-adapter-architecture.md)
- [`ADR-022-canonical-scientific-question-v1.md`](./ADR-022-canonical-scientific-question-v1.md) (**do not modify**; CSQ semantic authority)
- [`science-source-expansion/ADR-023-LEGACY-62-PROVENANCE-SNAPSHOT.v1.json`](./science-source-expansion/ADR-023-LEGACY-62-PROVENANCE-SNAPSHOT.v1.json) (**frozen historical-62 provenance after §8.45 retirement**; markdown register deleted)
- [`science-source-expansion/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json`](./science-source-expansion/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json) (**CURRENT membership SoT — 110 seats after §8.45**; D-09 109 snapshot retained)

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


---

## 8.21 — Conversation, Audit Reports, and Two-Phase Source Expansion Plan (2026-10-12)

### 8.21.1 — Preservation and authority

This section is an append-only continuation record. It preserves the material conclusions from the 2026-10-12 conversation, the available environment/IC audit reports, and the consolidated two-phase execution prompt for the 109-source expansion. It does not delete, replace, or retroactively rewrite §8.20 or any earlier decision/report/prompt.

The user's latest planning decision is to finish Scientific Source Expansion in **two implementation phases only**. This supersedes the earlier three-phase planning proposal for future execution planning; the earlier proposal remains preserved as historical context. The design gate is embedded at the start of Phase 1 and is not a third phase.

This documentation append does not itself claim that IC is closed, that sources are implemented or activated, that tests have run, or that the saved prompt has been executed.

### 8.21.2 — Sources and reports preserved

Reports consulted for this continuation:

- `Pasted text(20261012-164511).txt` — forensic Git/ADR/WIP and IC status audit.
- `Pasted markdown(20261012-173151).md` — `Environment Readiness + IC Persistence Integrity Closure Audit`, including the E1–E9 matrix, CI failure matrix, environment constraints, and the recommended next steps.
- The earlier IC remediation brief archived in §8.20.7 remains preserved verbatim in its existing location.

These report files remain in the conversation/library archive; this ADR section records their relevant conclusions so the project decision history remains understandable without treating a report summary as a replacement for the source reports.

### 8.21.3 — Latest reported baseline and findings (not a new live verification)

The latest report available for this record reported:

- Repository: `wsa-platform/WSA-Enterprise`.
- Branch: `phase-18-m18-ai-marketing-communications`.
- Reported HEAD and origin: `ba24abda21f40359014b3e2b43927c059c3a397a`, ahead/behind `0/0` at the time of that audit.
- Worktree: 580 entries (14 modified, 566 untracked, none staged/deleted/renamed); the report recorded content fingerprints as matching its then-current comparison baseline. This is historical evidence, not a claim about a future/current worktree.
- `backend/composer.lock` and `docs/architecture/science-source-expansion/SOURCE-CAPABILITY-CONTRACT.md` were untracked and explicitly required preservation.
- The current production runtime source registry was reported to contain five adapters: `openalex`, `crossref`, `semantic_scholar`, `consensus`, and `fao_stat`. The 109-seat membership register is not equivalent to runtime activation.
- IC Persistence Integrity disposition: `BLOCKED`, because current-state PostgreSQL evidence was absent, E6 and E8 remained unverified, and the supersede ordering decision remained unresolved.
- E1 and E2a were reported as PASS on historical PostgreSQL evidence, not rerun in that audit. E3–E5 had historical test/source evidence. E7 was historical PostgreSQL evidence, while the standard CI path forced SQLite. E9 current execution evidence was not verified.
- E2b: §8.14.6 describes a supersede ordering inconsistent with the implementation/accepted D2/D4 behavior. This requires an explicit architectural reconciliation; do not silently choose a behavior.
- E6: missing required PostgreSQL tests for concurrent uncommitted `persist()` calls and applicable higher isolation/recovery paths; define retry policy only through the existing approved decision or a separately approved decision.
- E8: deployment ordering and migration preflight/recovery were not fully verified. Do not run migrations against a production/shared database.
- The report noted unrelated CI failures: frontend build, mobile, backend, and security. These are separate from IC and must not be attributed to IC or local WIP without evidence. The backend failure count blocks final 109-source acceptance, but does not itself prove an IC defect.
- Docker/PostgreSQL constraints recorded in the report: a compose PostgreSQL service was exposed on port 5432 and must not be touched; a disposable test PostgreSQL must be isolated (network none or loopback-only) and use the existing test guard. Docker storage/environment and PHP extension availability must be verified before execution.
- Git synchronization was previously marked `FAST_FORWARD_CANDIDATE — NOT EXECUTED` in the earlier audit. The later report records a newer remote-synchronized baseline; every new execution must re-check Git before acting and must not rely on either historic baseline without verification.

### 8.21.4 — Two-phase plan (current planning decision)

#### Phase 1 — Close the foundation and prepare source integration

**Mandatory no-code design gate at the beginning of this same phase:** decide and document CapVer authority; Capability population rules; Runtime Source Registry contract; the 109 membership-seat identity bindings; whether `CGHIA T10+T8+T2` is an approved architectural path; and the dependency/order between Capability and IC inventory. Do not implement code until this design gate is accepted and implementation is separately authorized.

Then close IC Persistence Integrity on actual isolated PostgreSQL evidence:

1. Re-verify repository branch, HEAD, upstream, index, worktree, and all WIP fingerprints before any mutation. Preserve all modified and untracked files.
2. Reconcile E2b (supersede ordering) with the ADR through an explicit approved decision.
3. Add/execute tests for E6 concurrency, applicable transaction isolation and failure/recovery paths; do not retry writes unless authorized policy allows it.
4. Verify E8 migration preflight, ordering, failure behavior, and documented recovery without touching a production/shared database.
5. Run the dedicated PostgreSQL suite on the exact verified code. SQLite is supplemental and cannot prove PostgreSQL concurrency.
6. Record exact commands, exit codes, engine/version, test counts, assertions, and failures. Tests not run must be marked `NOT RUN`.
7. Append a formal IC closure delta to this ADR only after the required evidence and decisions exist. Do not call IC closed if a blocking item remains.
8. After the design gate is accepted and implementation authorization is granted, implement the approved Runtime Source Registry, identity bindings, and adapters grouped by access/protocol/path family (not one adapter per source by assumption). Preserve CSQ semantics, the existing five-source runtime path, and the 109-seat membership register. Do not bulk-populate Capability facts or activate all 109 seats.

#### Phase 2 — Comprehensive acceptance and controlled activation

1. Establish a reproducible CI baseline and diagnose the known frontend, mobile, backend, and security failures as separate clusters. Do not misattribute them to IC or WIP without evidence.
2. Test each source and adapter family for access, source identity/provenance, capability claims, source-specific query projection, normalization, reference integrity, timeout/error behavior, and regression protection.
3. Preserve CSQ as the semantic authority; `ScientificSearchQueryBuilder` remains the source-query execution authority and `ScientificQueryCompiler` remains metadata-only unless a separately approved ADR changes that contract.
4. Verify the existing five-source path and Home/Crop isolation; do not reopen AnswerComposer, AccuracyGate, RelevanceGate, Units A/B/C, Results List, or Viewer without direct evidence of a defect.
5. Activate only sources that pass their own identity, capability, access, integration, and test gates. Membership in the 109-seat register is not activation.
6. Publish a final source-by-source acceptance matrix with evidence, activation status, limitations, and explicit `PASS` / `FAIL` / `BLOCKED` / `NOT RUN` statuses.

**Completion rule:** The expansion is complete only when both phases pass their required gates and the final report distinguishes implemented, tested, and activated sources from sources still blocked or unavailable. Two phases are a planning structure, not permission to bypass architectural approvals or safety checks.

### 8.21.5 — Consolidated master prompt preserved for Cursor

The following prompt is saved for future execution. It has not been executed by this ADR update.

```text
WSA-Enterprise — SCIENTIFIC SOURCE EXPANSION
CONSOLIDATED TWO-PHASE MASTER PROMPT
Repository: wsa-platform/WSA-Enterprise
Branch expected: phase-18-m18-ai-marketing-communications
Canonical decision: docs/architecture/ADR-023-scientific-source-expansion.md

MISSION
Complete the 109-source expansion in EXACTLY TWO IMPLEMENTATION PHASES.
Do not create a third phase. The no-code design gate is the opening gate of Phase 1, not a separate phase. Preserve the existing ADR history and append new decisions/reports only; never delete or silently rewrite historical content.

GLOBAL SAFETY
- First inspect the current branch, HEAD, upstream, index, staged/modified/untracked paths, active operations, and WIP fingerprints.
- Do not assume any historical SHA or WIP inventory is current.
- Preserve every pre-existing WIP file, including backend/e2e-tmp, admin-mobile/e2e-tmp, docs/architecture, tests, backend/composer.lock, and SOURCE-CAPABILITY-CONTRACT.md.
- Never use reset, clean, stash, checkout/switch, restore, rebase, merge, cherry-pick, revert, history rewrite, force push, broad staging, or deletion/overwriting of unrelated work.
- Do not fetch/merge/pull, commit, or push unless the user has explicitly authorized that exact Git operation. If a required remote-only ADR section is absent locally, report the dependency and stop before making an unsafe sync.
- Do not run a migration or connect to a production/shared database. Do not touch the running Compose PostgreSQL exposed on port 5432. Use only a disposable PostgreSQL test instance with network none or loopback-only and the existing destructive-test guard.
- Do not assume Render is an active deployment target.
- Never claim a test passed unless it actually ran; include command, exit code, engine/version, test count, assertions, and failures. Mark skipped work NOT RUN.
- No source activation or Capability population by default. No commits/pushes unless separately authorized.

PHASE 1 — FOUNDATION, DESIGN GATE, IC CLOSURE, AND APPROVED INTEGRATION
A. At the start, run ONE consolidated NO-CODE architectural design gate. Decide and document:
   1. CapVer authority and the conditions for VERIFIED, PARTIAL, UNAVAILABLE, and NOT_APPLICABLE.
   2. Capability population policy and write authority; do not invent persisted UNVERIFIED facts or bulk-populate 109 seats.
   3. Runtime Source Registry contract and ownership.
   4. Identity binding contract for all 109 membership seats, keeping membership distinct from activation.
   5. Whether CGHIA T10+T8+T2 is an approved architectural path; it is not approved merely because it appears in WIP extracts.
   6. Capability-versus-IC inventory ordering and the existing approved C+A boundary: collectors collect evidence only; evaluators recommend only; Capability Store persists only already-authorized decisions; CapVer retains the approved central authority for VERIFIED.
   7. Adapter grouping by protocol/access/path family and preservation of the existing five-source path.
   Produce a decision matrix, evidence, unresolved decisions, and implementation plan. Do not write implementation code until this gate is accepted and separately authorized.

B. IC Persistence Integrity:
   1. Reconcile the latest authoritative §8.20.7 instructions with the actual current code and approved D1–D6 decisions.
   2. Resolve E2b supersede-order mismatch through explicit architectural reconciliation; do not silently choose between ADR and code.
   3. Add and run E6 tests for uncommitted persist-vs-persist races, applicable isolation levels, unique violation, deadlock/serialization failure and recovery branches. Retry only under an approved policy; current policy says no write is retried unless explicitly superseded.
   4. Verify E8 migration preflight, deployment/migration ordering, failure handling, and documented recovery. Do not inspect or mutate production data.
   5. Execute the dedicated PostgreSQL suite on a disposable isolated database. SQLite cannot substitute for PostgreSQL concurrency proof. Run counter-factual tests only if the old code can be tested safely without altering the worktree/history.
   6. Report E1–E9 individually and use READY_FOR_FORMAL_CLOSURE only when all technical criteria are evidenced and only separately authorized formal closure remains. Otherwise report BLOCKED or NOT_CLOSED with exact reasons.
   7. Append the closure delta to ADR-023 only after evidence exists; never rewrite old sections.

C. After the design gate is accepted and implementation authorization is granted:
   1. Implement the approved Runtime Source Registry and 109 identity bindings.
   2. Implement adapters grouped by path/protocol/access family rather than assuming 109 separate adapters.
   3. Preserve CSQ as semantic authority, ScientificSearchQueryBuilder as query-execution authority, ScientificQueryCompiler as metadata-only, RSC and the production orchestrator/normalizer contracts, evidence provenance, answer identity, and Home/Crop isolation.
   4. Keep the existing five-source path working.
   5. Do not activate all sources or create capability facts without source-specific evidence and authority.

PHASE 2 — ACCEPTANCE, REGRESSION, AND CONTROLLED ACTIVATION
1. Establish a reproducible CI baseline. Diagnose frontend, mobile, backend, and security failure clusters separately; do not assume local WIP caused committed-code CI failures.
2. For every source and adapter family, test availability/access, identity/provenance, capabilities, source-specific query projection, preserved scientific constraints, normalization, references, timeouts/errors, and failure isolation.
3. Test the existing five-source path, CSQ identity, answer/evidence contracts, Home/Crop isolation, and all relevant regression tests.
4. Do not reopen AnswerComposer, AccuracyGate, RelevanceGate, Units A/B/C, Results List, or Viewer without direct evidence of a defect.
5. Activate only sources whose identity binding, access, capability evidence, adapter path, normalization, and tests all pass. Keep blocked/unavailable sources inactive and document why.
6. Produce a 109-seat acceptance matrix with group, source identity, binding, access mode, adapter family, capability evidence/status, tests, activation status, and limitations.
7. Append the final acceptance report to ADR-023 without deleting or replacing prior history.

FINAL REPORT — ONE CONSOLIDATED REPORT
Include:
- Verified baseline (branch, HEAD, upstream, WIP inventory and fingerprints).
- Design-gate decisions, evidence, and any unresolved approval.
- IC E1–E9 matrix, exact PostgreSQL evidence, and closure disposition.
- Changed files grouped by purpose, migrations (if any; must not run against production), exact test commands/results.
- Registry, identity bindings, adapter-family matrix, and source-by-source status for all 109 seats.
- Existing five-source regression results and CI failure clusters.
- Final diff and proof that unrelated WIP was preserved.
- ADR append-only sections added.
- Explicitly distinguish implemented, tested, activated, blocked, unavailable, and NOT RUN.
- Exactly one final project disposition: COMPLETE, BLOCKED, or NOT_CLOSED.
- State one next action only.

STOP CONDITIONS
Stop without risky mutation if the branch/base cannot be reconciled, an ADR decision is ambiguous, WIP ownership overlaps, PostgreSQL cannot be isolated, a production/shared database could be reached, a test exposes an unresolved architectural choice, or the requested action requires separate user authorization. Do not fabricate closure or proceed to activation.
```

### 8.21.6 — Final status of this record

- Conversation/decision summary: recorded above.
- Latest available audit conclusions: recorded above as historical report evidence, not a fresh execution.
- Two-phase plan: recorded as the current planning decision.
- Consolidated master prompt: archived above for future Cursor use; not executed by this documentation update.
- Older ADR content: retained; no historical section intentionally removed or rewritten.
- IC Persistence Integrity: remains `BLOCKED` until the required technical and architectural evidence is produced.
- Source expansion: not claimed complete; no claim that the 109 sources are implemented or activated.


---

## 8.22 — IC Persistence Integrity: Post-Commit Verification and Corrections (2026-10-12)

**Status:** APPEND-ONLY CONTINUITY RECORD

**Scope:** Records the IC Persistence Integrity verification resumed under §8.20.6–§8.20.7 after the local environment recovered. It corrects stale statements in §8.19 and reconciles §8.14.6 without rewriting either section. Evidence labels follow §8.19: **[V]** verified by a run or inspection on 2026-10-12; **[I]** inferred; **[U]** not verified.

### 8.22.1 — Repository state and status correction

- Branch `phase-18-m18-ai-marketing-communications`, HEAD `ba24abda21f40359014b3e2b43927c059c3a397a`, equal to `origin`, nothing staged [V]. The WIP inventory was 580 entries (14 modified, 566 untracked) and was unchanged by this verification [V].
- §8.19 was committed in `ffea580` and pushed [V]. The §8.19 statements "This §8.19 record itself is still an uncommitted…" and "THIS ADR RECORD UNCOMMITTED" are superseded by this fact.
- Commit `2b856a2` contains the 16 IC paths listed in §8.19.2, and none of them changed afterwards [V]. The earlier proposed diff kept outside the repository (`wsa-ic-commit-proposed.diff`, 2026-10-09 22:16) lists the same 16 paths but predates the commit and differs from it; it is a historical artifact only [V].

### 8.22.2 — Post-commit verification

All runs used a snapshot of `ba24abda` produced by `git archive` outside the repository (95 in-scope files checked against Git, 0 mismatches), the image `wsa-enterprise-backend-test:latest` (PHP 8.4.24, PHPUnit 11.5.56, `pdo_pgsql`, `pdo_sqlite`), and the existing vendor volume mounted read-only [V].

| Run | Engine | Result | Label |
|-----|--------|--------|-------|
| `phpunit.xml` over `tests/Unit/Agriculture/Research/IntegrationClassification`, `…/Identity`, `…/Capability`, `tests/Unit/Support` | SQLite `:memory:` | 204 passed / 800 assertions | [V] |
| `phpunit.pgsql.xml` (full) | PostgreSQL 16.14 | 108 passed / 639 assertions | [V] |
| `tests/bootstrap-pgsql.php`: no destructive opt-in; opt-in naming another database; non-loopback host; server also hosting `wsa_enterprise` | none / PostgreSQL 16.14 | 4 of 4 refused, exit 1; the valid configuration accepted | [V] |
| Counter-factual: `f7b9b71` production code with the `2b856a2` tests, full `phpunit.pgsql.xml` | PostgreSQL 16.14 | 108 tests, 18 errors, 36 failures, including `25P02` (statement inside an aborted transaction) | [V] |

These counts equal the "commit content" rows of §8.19.3, which were not rerun after the commit; they now are. The counter-factual closes the §8.19.3 [U] row: the suite discriminates between the pre-change and committed repository [V].

The PostgreSQL servers were throwaway `postgres:16-alpine` containers with `--network none` (interface `lo` only, no routes), no published port, no mount, and tmpfs storage; the PHP container shared only that loopback namespace, and the server held only the test database and system databases [V]. No compose, shared, production, or legacy deployment database was contacted [V].

### 8.22.3 — SQL-level concurrency probes

Run with `psql` on the same disposable server against the migrated table [V]:

- The §8.14.6 order (insert the new ACTIVE before marking the prior SUPERSEDED) fails immediately with `23505` on `cghia_ic_records_single_active_uq`.
- Two writers both uncommitted, same `adr_id`, different keys: the second blocks until the first ends; it receives `23505` if the first commits and succeeds if the first rolls back. Exactly one ACTIVE row remains.
- Same idempotency key from two uncommitted writers: the second receives `23505` on the idempotency-key unique index after the first commits.
- A waiter at REPEATABLE READ or SERIALIZABLE receives `23505` (fail-closed); no `40001` was observed in these probes.

These probes establish database behaviour only. Repository-level behaviour against an uncommitted competitor is covered by the test-only files `backend/tests/Pgsql/IntegrationClassificationPostgresIsolationTest.php` and `backend/tests/Pgsql/bin/ic-persist-worker.php` (working tree, not committed). The competitor runs in a worker process, and the test commits or rolls back only after PostgreSQL reports the worker blocked by the test connection. On the `ba24abda` snapshot plus these two files the new class passed 13 tests / 165 assertions in three consecutive runs, and the full `phpunit.pgsql.xml` passed 121 tests / 804 assertions on PostgreSQL 16.14 [V]. They show:

- uncommitted competitor commits → the waiting `persist()` raises `IntegrationClassificationActiveConflict` caused by `23505`; it rolls back → the waiter's row becomes the single ACTIVE;
- same key from an uncommitted competitor → replay of the committed record when the contract matches (including reordered evidence references), `IntegrationClassificationIdempotencyConflict` when it does not (including a duplicated evidence reference and a changed fingerprint);
- REPEATABLE READ / SERIALIZABLE caller → fail-closed: `IntegrationClassificationActiveConflict` for a different key; for the same key the raw `UniqueConstraintViolationException` (`23505`) surfaces, because the replay lookup runs in the caller's snapshot — consistent with the documented READ COMMITTED assumption;
- a `supersede()` waiting on the same prior with the same new key replays the committed replacement without any INSERT attempt, so the unique-violation recovery sub-branch is not reached under READ COMMITTED;
- opposite-order supersede composition across two ADRs deadlocks; PostgreSQL aborts exactly one side (`40P01`), whose whole transaction rolls back, and each ADR ends with one ACTIVE pointed to by its prior;
- a reader during an open supersede sees exactly the committed prior as current.

### 8.22.4 — Reconciliation of §8.14.6

The authoritative supersession order is the one recorded in §8.19.1 (D2/D4) and implemented in commit `2b856a2`: lock the prior → validate → mark the prior SUPERSEDED → insert the replacement as ACTIVE → set `superseded_by`, in one transaction. The §8.14.6 order ("insert new ACTIVE → mark prior SUPERSEDED") cannot execute under the non-deferrable partial unique index of D1 (§8.22.3). §8.14.6 is not rewritten; this section governs where they differ.

### 8.22.5 — Duplicate evidence references

User decision: `evidence_references` are compared order-insensitively, multiplicity is significant (`[a, b, b]` ≠ `[a, b]`), and the stored order is preserved. This matches the committed behaviour and its test (`duplicate reference is not merged`) and closes the corresponding §8.19.5 item.

### 8.22.6 — IC-AUDIT findings

- Closed with evidence: IC-AUDIT-001 (lost-update test is discriminating), IC-AUDIT-004 (destructive-run guard), IC-AUDIT-007 (persist after invalidate; persist racing supersede) [V].
- IC-AUDIT-005 (§8.19 wording): resolved by `ffea580` and §8.22.1 [V].
- IC-AUDIT-006 (READ COMMITTED assumption): documented in the repository interface; database and repository behaviour verified fail-closed (§8.22.3), with tests not yet committed.
- IC-AUDIT-002 (PostgreSQL suite not in CI) and IC-AUDIT-003 (duplicate ACTIVE rows in a deployed database would stop `migrate --force`): open.

### 8.22.7 — Remaining open items and disposition

Open:

- committing the two test-only files of §8.22.3 (separate authorization);
- running the PostgreSQL suite in CI;
- a reviewed duplicate-ACTIVE preflight on any real environment before deployment;
- deployment ordering: `scripts/deploy-production.sh` starts new containers (line 45) before `migrate --force` (line 48).

No in-scope integrity defect was found in the committed code. Disposition under §8.20.7: **BLOCKED** — the new tests are uncommitted, and the CI and real-environment prerequisites above remain unresolved.

This record does **not** authorize an IC production writer or consumer, IC inventory population, IC activation, CapVer implementation, Capability population, a Runtime Source Registry, activation of any of the 109 seats, or changes to FS-01-ID.

**FINAL STATUS: IC PERSISTENCE INTEGRITY VERIFIED POST-COMMIT ON POSTGRESQL 16.14 AND SQLITE; COUNTER-FACTUAL DISCRIMINATES; §8.14.6 RECONCILED; DISPOSITION BLOCKED; IC NOT ACTIVATED.**

### 8.22.8 — Update from the Phase 1 execution (2026-10-12, later the same day)

The statements above remain the record of the first verification. This subsection adds evidence produced later the same day under the Phase 1 directive (§8.24.1). All items are **[V]** unless marked.

- **Worker hardening.** `finishWorker()` in `IntegrationClassificationPostgresIsolationTest` now reads worker output with a 60-second deadline, terminates the worker and fails the test when the deadline passes, and asserts that the worker exited with status 0 and printed a `returned` or `threw` outcome. A crashed, hung, or silent worker therefore fails the test instead of being read as a result. Assertions were added; none was removed or relaxed.
- **Rerun.** On a `ba24abda` snapshot plus the hardened files, against a fresh `postgres:16-alpine` server (`--network none`, tmpfs storage, database `wsa_ic_ci_test`): the isolation class passed 13 tests / 201 assertions in three consecutive runs, and the full `phpunit.pgsql.xml` passed 121 tests / 840 assertions. `tests/bootstrap-pgsql.php` without `DB_TEST_ALLOW_DESTRUCTIVE_RESET` refused with exit 1. The server held only `wsa_ic_ci_test` and the system databases before and after. The 165 / 804 assertion counts in §8.22.3 belong to the pre-hardening file; the difference (36) is the new worker-exit assertions.
- **Disposable-server disappearance.** During the first verification attempt the throwaway server vanished during setup. Docker events show an orderly client-issued stop (`kill`, `stop`, `die` with exit code 0, `destroy`) one to three seconds after a successful `psql` exec; there was no `oom` event and no non-zero exit. Classification: **lifecycle event, not a crash or resource exhaustion**. The issuing client was not identified because the event buffer had aged out; the most probable cause **[I]** is an overlapping teardown from another command in the same session that reused session-wide container-name variables. Mitigation: each run uses its own locally scoped container name. No recurrence in the later runs. Issuer: **UNKNOWN**.
- **Snapshot artifact, not a defect.** On this Windows checkout (`core.autocrlf=true`) `git archive` writes the membership register with CRLF line endings, and `Adr023109MembershipRegisterContractTest` (which requires the deterministic LF serialization) failed once on such a snapshot. With the raw blob (`git cat-file`, object `af67418c1c070c6e53ffb3ab1eef62890149c4b7`, equal to HEAD) the SQLite subset passed 205 tests / 6043 assertions. The working-tree register is LF-only.
- **IC-AUDIT-002.** A PostgreSQL CI job was added to the working tree (§8.25.5).

The open items of §8.22.7 are re-evaluated in §8.25.

---

## 8.23 — CapVer and C+A Governance: Design Record (2026-10-12)

**Status:** DESIGN RECORD — PENDING ARCHITECTURE-OWNER ACCEPTANCE. **Implementation authorized: NO.**

**Scope:** Consolidates the CapVer authority model already decided in D-10.5, §8.16.7, and §8.16.13–§8.16.17 and confirmed against the code in §8.18, and states the evidence, lifecycle, rejection, conflict, audit, failure, and test requirements that a future implementation must satisfy. It does not change those decisions. Where an earlier section deferred a question, it stays deferred here.

### 8.23.1 — Term and current implementation

- "C+A" does not occur in any tracked ADR, contract, or code file, or in the working-tree extracts [V]. In this section it denotes the **Collector + Authority** separation named in the Phase 1 directive: collectors gather evidence and the authority assigns VERIFIED, with the evaluator and the Capability Store as adjacent roles. If a different meaning was intended, this section must be amended before acceptance.
- No CapVer component, CapVer producer, or CapVer evidence store exists [V] (§8.18.2).
- Present in `backend/app/Services/Agriculture/Research/Capability` [V]:
  - `CapabilityState` (VERIFIED, PARTIAL, UNVERIFIED, UNAVAILABLE, NOT_APPLICABLE);
  - `CapabilityRequirementEvaluator`, which applies restrictive-wins. Its only production call site is `Stage3CghiaCoexistenceBoundary::planAdrBoundSource()`, reached only in `cghia_attached` mode; the default mode is `legacy_only` (§8.18.2);
  - `CapabilityStoreDomainContract`, which forbids promoting STALE evidence to VERIFIED;
  - `CapabilityStoreRepository` and its Eloquent implementation, which are container-bound and have no production caller.
- `FaoStatDomainVerifier` is an adapter-internal check of the Stage-3 FAOSTAT adapter. It is not CapVer and assigns no Cap state [V].

### 8.23.2 — Roles and authority boundaries

| Role | May | Must not |
|------|-----|----------|
| **Collector** (probe, documentation review, manual observation) | Gather evidence inputs and emit opaque evidence references with provenance | Assign or persist any Cap state; mark anything VERIFIED (D-10.5.7) |
| **Evaluator** (`CapabilityRequirementEvaluator`) | Evaluate requirement satisfaction from CURRENT facts (missing → UNVERIFIED, AND semantics, restrictive-wins); produce a recommendation | Persist; promote; grant VERIFIED; treat restrictive-wins as permission for duplicate facts (§8.16.12) |
| **CapVer** (abstract Governed Verification Authority) | Assign VERIFIED to one capability cell of one seat on CURRENT evidence | Activate a seat; bind identity; classify integration (IC); change membership; hold evidence bodies |
| **Capability Store** | Persist already-authorized Cap facts and opaque evidence references; keep CURRENT and HISTORICAL facts | Decide or promote a state; auto-insert UNVERIFIED; become CapVer (§8.16.14) |
| IC, Identity, Membership, Activation | Their own decisions (§8.14, D-10.3, D-09, D-10.10) | Imply a Cap state; CLASSIFIED ⇏ VERIFIED |

CapVer organizational ownership, the CapVer producer, and the enforcement location of CapVer-only VERIFIED assignment remain **DEFERRED** (D-10.5.9, §8.16.14). Producers of PARTIAL, UNVERIFIED, UNAVAILABLE, and NOT_APPLICABLE facts remain **POPULATION AUTHORITY OPEN** (§8.16.14).

### 8.23.3 — Evidence requirements

- §8.16.7 is normative: VERIFIED, PARTIAL, and UNAVAILABLE require at least one opaque evidence reference; PARTIAL also requires limitation text; MISSING is never persisted.
- VERIFIED requires CURRENT evidence. STALE or UNKNOWN freshness never yields VERIFIED (D-10.5.2).
- Each decision covers one dimension of one seat. There is no source-wide VERIFIED. `content_about` ≠ `query_constrainable`, and license, access, and reuse are separate dimensions (D-10.5.4–5).
- The subject is the `adr_id` seat. A dual-membership pair does not share a Capability subject (D-10.4.5).
- A source existing, an endpoint answering, a mock passing, or an adapter class existing is not evidence of any capability.

### 8.23.4 — Decision lifecycle and state transitions

1. Collectors produce evidence references.
2. The evaluator may produce a recommendation.
3. CapVer decides VERIFIED, or declines.
4. The Capability Store persists the authorized fact as a new CURRENT fact, and the previous CURRENT fact of the same cell becomes HISTORICAL (CPD-B, §8.16.8). Cap fields are never mutated in place.

| Transition | Rule |
|------------|------|
| any → VERIFIED | CapVer only, with CURRENT evidence |
| MISSING → any persisted state | Only through an authorized producer; MISSING itself is never written |
| VERIFIED → another state | A new fact from an authorized producer. A downgrade producer is not yet designated (POPULATION AUTHORITY OPEN) |
| evidence becomes STALE | No automatic promotion. Automatic demotion is not decided (OPEN) |
| empty probe result | Not UNAVAILABLE (EMPTY_RESULT ≠ CAP_UNAVAILABLE, D-10.5.6) |

### 8.23.5 — Validation and rejection

A write is rejected, not repaired, when:
- the required evidence is missing;
- the override layer is invalid;
- the history transition is invalid (§8.16.13);
- VERIFIED is asserted without CapVer authorization;
- STALE evidence is promoted (`CapabilityStoreDomainContract`).

Concrete exception mapping is deferred to implementation (§8.16.13).

### 8.23.6 — Persistence, provenance, conflicts, versioning, audit

- **Persistence and provenance.** Facts are append-only and immutable, and evidence references are immutable once recorded (§8.16.6). Each decision must carry the deciding authority, a timestamp, and its evidence references. The identifier format for CapVer decisions is **DEFERRED**.
- **Conflicts.** At most one CURRENT fact may exist per cell and override layer (§8.16.10–§8.16.12). A second concurrent writer must fail closed at the database, following the pattern verified for IC in §8.22.3. Conflicting evidence is decided by CapVer and never merged automatically.
- **Versioning.** The Cap v2 vocabulary (`CapabilityState`) governs. The GO-1 vocabulary of `SOURCE-CAPABILITY-CONTRACT.md` (SUPPORTED / PARTIALLY_SUPPORTED / UNSUPPORTED / UNKNOWN) is historical (`HistoricalGo1CapabilityState`) and is not mapped automatically. Vocabulary changes require an ADR amendment.
- **Audit.** HISTORICAL facts are retained and not loaded by default (§8.16.8). The history of a cell must be reconstructible from facts alone.

### 8.23.7 — Failure modes

| Failure | Required behaviour |
|---------|--------------------|
| CapVer unavailable or undecided | No promotion; existing facts unchanged; missing remains UNVERIFIED |
| Evidence reference unresolvable | No VERIFIED |
| Store write fails | No partial fact; the previous CURRENT fact stays CURRENT |
| Probe error or empty result | Neither UNAVAILABLE nor VERIFIED |
| Concurrent decisions on one cell | Exactly one succeeds; the other fails closed |

### 8.23.8 — Test requirements for a future implementation

- CapVer-only VERIFIED: every other role is rejected.
- Evidence and freshness rules of §8.23.3.
- CURRENT → HISTORICAL on supersession; no in-place mutation.
- Single CURRENT per cell under concurrency, on PostgreSQL.
- Rejection cases of §8.23.5.
- No automatic population: an empty store evaluates to UNVERIFIED.
- Seat isolation: no cross-seat or dual-pair inheritance.
- Cap ≠ IC ≠ Identity ≠ Activation.

### 8.23.9 — Prohibited shortcuts

- Bulk or automatic VERIFIED for any group of seats, including all 109.
- Deriving VERIFIED from membership, display name, IC classification, endpoint reachability, mocks, adapter existence, or documentation alone.
- Auto-inserting UNVERIFIED, or treating absence as UNAVAILABLE.
- Mapping GO-1 historical states to Cap v2 automatically.
- Letting the Capability Store, an evaluator, a collector, or an adapter assign VERIFIED.
- Activating a seat because of a Cap state (D-10.5.10).

This section does **not** authorize a CapVer implementation, a CapVer producer or service, Capability population, CapVer organizational ownership, or activation of any seat.

---

## 8.24 — Phase 1 Design Gate (2026-10-12)

**Status:** DESIGN GATE OUTCOME. **Implementation of the source-expansion foundation: BLOCKED.**

### 8.24.1 — Two-phase plan

User directive (2026-10-12): the Scientific Source Expansion has exactly two implementation phases.

- **Phase 1:** architectural foundation, design decisions, IC Persistence Integrity closure, the approved source-integration implementation, and Phase 1 verification. The design gate opens Phase 1; it is not a separate phase.
- **Phase 2:** comprehensive source acceptance, regression validation, controlled activation, and final source-by-source acceptance.

There is no Phase 3. Earlier ADR sections do not mention this plan; it is recorded here and remains in force. §8.23–§8.25 are Phase 1 artifacts. Phase 2 has not started.

### 8.24.2 — Runtime Source Registry

| Question | Finding [V] |
|----------|-------------|
| Does a Runtime Source Registry exist? | **No** (§8.18.2) |
| What registry runs today? | `ScientificSourceAdapterRegistry`: a hard-coded set of the five Stage-3 adapters (`openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`), queried through `registeredSourceKeys()`, `get()`, and `resolveMany()`; known debt (D-05) |
| How are sources identified? | By Stage-3 source-key strings. `Stage3SourceKeyIdentityBridge` receives no ADR bindings, so the five keys resolve as EXTERNAL and none is bound to an `adr_id` (§8.18.2) |
| Are capabilities represented at the registry? | No |
| Configuration-driven or hard-coded? | Hard-coded |
| Seat literals in runtime code? | None in `backend/app`, `config`, `database`, `routes`, or `bootstrap` |

**Decision.** Under D-01, any future Runtime Source Registry must extend the existing adapter + registry structure and must not become a parallel source-integration layer or a second source of truth beside the membership register (D-09).

The minimum viable contract **proposed for later authorization** is:
- entries keyed by `adr_id`;
- each entry references, without copying, its IC record, identity binding, and Capability facts;
- an activation state per D-10.10, plus explicit DISABLED, UNAVAILABLE, UNSUPPORTED, and FAILED execution outcomes;
- adapter resolution by verified access family;
- source identity and provenance carried into normalized results.

Not implemented: no Implementation Authorization exists (D-10.15; §8.9.8 and §8.18, "NO NEXT IMPLEMENTATION UNIT IS AUTHORIZED").

### 8.24.3 — Capability Contract

- **Defined and implemented** [V]:
  - Cap v2 states, `CapabilityDimensionFamily`, `CapabilityAccessMethod`, freshness, and cell lifecycle;
  - the evaluator, the domain contract, and the store with its repository.

  These are validated by unit tests and, for persistence, by the PostgreSQL suite.
- **Documented only:** the per-source states in `docs/architecture/science-source-expansion/SOURCE-CAPABILITY-CONTRACT.md`. That file is untracked working-tree content, uses the historical GO-1 vocabulary, and is not runtime authority.
- **Source-specific capabilities:** none is persisted or written by production code.
- **Discrepancies:**
  - the two vocabularies above are not mapped automatically (§8.23.6);
  - `CapabilityRecord` still lacks the evidence-reference field required by §8.16.7 (§8.16.16).

  Resolving either changes production code and requires Implementation Authorization. **Not resolved in Phase 1.**

### 8.24.4 — The 109 seats and identity bindings

- **Membership.** The register `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` (`109.v1`) is authoritative under D-09. It holds 109 CURRENT seats in groups G1 22, G2 25, G3 14, G4 7, G5 19, and G6 22, with ten dual pairs. `Adr023109MembershipRegisterContractTest` passed [V].
- **Identity.** `adr_id` values are unique and stable [V]. Display names are not identifiers (D-10.3.3), and dual pairs ≠ SAME_AS (D-10.4.2).
- **Bindings.** Zero identity bindings are persisted. `IdentityBindingRepository` has no production writer [V]. `canonical_identity_id` stays NULL until SAME_AS evidence exists (D-10.3.4). G3-01 has a pinned external identity (GO-ID-G3-01) but no persisted binding.
- **Policy.** Missing and ambiguous mappings remain explicit blockers.
- **State separation.** Membership, identity binding, adapter implementation, capability evidence, acceptance, and activation are separate states (D-10). The source matrix (§8.25.8) records each of them per seat.

### 8.24.5 — Adapter and protocol-family strategy

- No seat can be assigned an access family. A family requires a verified per-seat integration classification (D-10.8), and none exists: the IC inventory is OPEN and IC persistence is BLOCKED (§8.25).
- Families must not be inferred from names or websites (D-10.8.2).
- **Strategy for later authorization:**
  - one adapter per verified access family, with per-seat query constraints where needed;
  - existing Stage-3 adapters are reused only for a seat whose identity binding proves the same resource;
  - no 109 speculative adapters.

**Implemented: none.**

### 8.24.6 — `CGHIA T10+T8+T2`

- **Evidence [V].** The combination appears only in two untracked working-tree extracts, `backend/e2e-tmp/_path_model_design_extract.md` and `backend/e2e-tmp/_projection_c9_design_extract.md`, as a "frozen input". It does not appear in this ADR or in any code.
- **Conflict.** In those extracts, T2 is a family adapter plus a per-source Projection in which "Projection owns query". That conflicts with D-04, D-10.9.2, and the Phase 1 boundary, under which `ScientificSearchQueryBuilder` constructs source queries.
- **Decision: BLOCKED.** It is not an approved design and is not implemented. Approval would require an explicit decision that reconciles T2 with D-04.

### 8.24.7 — Gate outcome

The design gate is documented. The foundation implementation of the Phase 1 directive §7 (registry, capability alignment, identity bindings, adapters, query projection, activation states) was **not started**, because each part depends on an unresolved decision:
- Implementation Authorization (D-10.15);
- CapVer ownership (§8.23);
- per-seat IC (D-10.8);
- IC closure (§8.25);
- `T10+T8+T2` (§8.24.6).

Work that does not prejudice those decisions was completed: IC verification and test hardening, the PostgreSQL CI job, §8.23, and the source matrix.

### 8.24.8 — Open decisions and required authorization

All of the following belong to the user or architecture owner:
1. Accept or amend §8.23 and this section.
2. Issue an Implementation Authorization Gate for the Runtime Source Registry contract of §8.24.2.
3. Designate CapVer organizational ownership and its enforcement location.
4. Authorize IC inventory population (per-seat integration classification).
5. Approve, amend, or reject `T10+T8+T2`.
6. Map, or explicitly not map, the GO-1 capability vocabulary.
7. Decide the deployment order (§8.25.6).
8. Decide whether same-key replay at REPEATABLE READ / SERIALIZABLE is a product requirement (§8.25.4).

### 8.24.9 — Acceptance criteria for the rest of Phase 1

1. Decisions 1–5 of §8.24.8 are recorded.
2. IC is CLOSED under §8.25.9.
3. The Runtime Source Registry and the Capability alignment are implemented under authorization, with focused tests.
4. Each seat is either classified and bound with evidence, or listed as blocked with its reason.
5. Family adapters are implemented only for verified families.
6. The source matrix is updated from evidence only.
7. All mandatory tests pass on SQLite and on isolated PostgreSQL.

---

## 8.25 — Phase 1 Implementation and Verification Report; IC Closure Delta (2026-10-12)

**Status:** PHASE 1 BLOCKED. IC disposition: **BLOCKED**. Phase 2: **NOT STARTED**. The two-phase plan of §8.24.1 remains in force.

### 8.25.1 — Baseline and changes

- **Baseline [V].** Branch `phase-18-m18-ai-marketing-communications`, HEAD `ba24abda21f40359014b3e2b43927c059c3a397a`, equal to `origin` (0 ahead, 0 behind), nothing staged, 583 working-tree entries. Status and content fingerprints of the 580 pre-existing entries were taken before this execution and compared after it.
- **Changed by this execution (uncommitted):**
  - this ADR (§8.22.8, §8.23–§8.25, appended after §8.22);
  - `.github/workflows/ci.yml` (new job only);
  - `backend/tests/Pgsql/IntegrationClassificationPostgresIsolationTest.php` (hardening of §8.22.8);
  - `docs/architecture/science-source-expansion/2026-10-12-PHASE-1-SOURCE-MATRIX.md` (new);
  - `backend/tests/Pgsql/bin/ic-persist-worker.php`, which is unchanged since §8.22 and remains uncommitted.
- **Not done.** No commit, push, deployment, migration against a real database, or source activation took place.

### 8.25.2 — E2b: supersede ordering — **PASS**

- **Contract.** Reconciled in §8.22.4. The governing order is D2/D4 as implemented in `2b856a2`, in one transaction:
  1. lock the prior row `FOR UPDATE`;
  2. validate;
  3. mark the prior SUPERSEDED;
  4. insert the replacement as ACTIVE;
  5. set `superseded_by`.

  Concurrent supersedes of one prior serialize on that row lock, and the partial unique index `cghia_ic_records_single_active_uq` enforces a single ACTIVE row per `adr_id`.
- **Verified by the isolation class:**
  - a waiting same-key supersede replays the committed replacement with zero INSERT attempts;
  - a competitor rollback leaves the waiter's record ACTIVE.

### 8.25.3 — E6: concurrent persist and isolation — **PASS for the documented contract**

All of the following were verified on PostgreSQL 16.14 against committed database state (active-row counts, keys, pointers), not only exceptions:
- uncommitted competitor commits / rolls back;
- same-key replay, including reordered evidence;
- same-key conflict on a duplicated reference or a changed fingerprint;
- different-key conflict;
- READ COMMITTED;
- REPEATABLE READ and SERIALIZABLE;
- deadlock.

| Case | Result |
|------|--------|
| Application exceptions | `IntegrationClassificationActiveConflict`, `IntegrationClassificationIdempotencyConflict` |
| Raw database error at REPEATABLE READ / SERIALIZABLE, same key | `UniqueConstraintViolationException` (`23505`) |
| Deadlock | `40P01` (`DeadlockException`) on exactly one side, chosen by PostgreSQL. The test does not depend on which side loses. The loser's transaction rolls back completely, and each ADR keeps exactly one ACTIVE row |

The repository interface documents READ COMMITTED as its isolation assumption (§8.22.6). The raw `23505` at stricter isolation is fail-closed: no duplicate ACTIVE row and no partial write. Same-key replay at stricter isolation is not part of the documented contract, and promising it would be a product decision (§8.24.8 item 8).

### 8.25.4 — Isolation and test-infrastructure safety (Phase 1 directive §6.1) — **PASS**

- The suite runs only through `phpunit.pgsql.xml` and `tests/bootstrap-pgsql.php`. The guard refuses unless:
  - every `DB_TEST_*` variable is set and the database name ends in `_test`;
  - the host is loopback and `DB_URL` / `DATABASE_URL` are empty;
  - the destructive opt-in names the target database;
  - the server holds nothing but the target and system databases.
- The test file creates no containers. Its destructive operations (one `migrate:fresh`, then deletion of IC rows between tests) run only on that guarded connection.
- Workers have a deadline and checked exit status (§8.22.8).
- The disposable servers used here had no network and no published port. The Compose PostgreSQL service on port 5432 was not contacted.

### 8.25.5 — IC-AUDIT-002: PostgreSQL in CI — **BLOCKED (external)**

- `tests/bootstrap.php` still forces SQLite for the default suite [V].
- A new job `backend-pgsql` in `.github/workflows/ci.yml` (+43 lines; existing jobs byte-identical; the file parses as YAML):
  - provisions a job-scoped `postgres:16-alpine` service whose credential is derived from the run ID (no secret, no hard-coded password);
  - installs `pdo_pgsql`;
  - first asserts that `tests/bootstrap-pgsql.php` refuses without the destructive opt-in;
  - then runs `phpunit.pgsql.xml` with the opt-in naming the service database.
- The job fails if PostgreSQL is unreachable or the guard rejects the server, so it cannot fall back to SQLite. Its local equivalent passed (§8.22.8).
- **Blocked on:** the job has not run on GitHub, because that requires commit and push authorization. Docker Hub pull limits (§8.20.3) remain an external risk. `actionlint` was not run.

### 8.25.6 — E8: migration and deployment ordering — **BLOCKED (operational decision)**

- **Current order [V].** `scripts/deploy-production.sh` pulls images, starts the new containers (`up -d --no-build --wait`, line 45), and only then runs `php artisan migrate --force` (line 48). `docs/deploy-production.md` documents the same order. `scripts/rollback-production.sh` runs no migrations. No other path runs migrations.
- **Preflight [V].** Migration `2026_10_09_190000_add_single_active_index_to_cghia_integration_classification_records` is itself a read-only preflight: it refuses and names the `adr_id` values that hold several ACTIVE rows, changes nothing, and never picks a winner.
- **Risk.**
  - If the preflight fails, or until the migration completes, the new application code runs against a schema without the single-ACTIVE index.
  - IC currently has no production writer, so the exposure is latent.
  - No ADR section defines an approved deployment order.
- **Proposed order, not implemented:**
  1. Start the database only.
  2. Run the read-only diagnostic of §8.25.7.
  3. Run `migrate --force` in a one-off container (`run --rm --no-deps backend`).
  4. Start the application containers.
  5. Run the smoke checks.
- **Blocked on.** Changing the production script is an operational decision, and it cannot be validated without staging or production execution, which this task does not authorize. **Requires:** an order decision, then validation in a staging environment.

### 8.25.7 — IC-AUDIT-003: duplicate ACTIVE records — **PROCEDURE DEFINED; real environments UNKNOWN**

- **Invariant.** Verified on isolated PostgreSQL (§8.22.3, §8.25.3). No real or shared database was inspected.
- **Procedure for an authorized operator:**
  - Run on a read-only connection (or `BEGIN READ ONLY`) of the target environment, under a role with `SELECT` on `cghia_integration_classification_records` only:

    `SELECT adr_id, COUNT(*) AS active_count FROM cghia_integration_classification_records WHERE lifecycle_state = 'ACTIVE' GROUP BY adr_id HAVING COUNT(*) > 1 ORDER BY adr_id;`

    This is the same predicate as the migration preflight.
  - Expected output: zero rows. If the table is absent, the IC migrations have not run and there is nothing to check.
  - Escalation: if any rows are returned, stop the deployment, do not run the migration, and do not modify data. Hand the listed `adr_id` values to the architecture owner for a reviewed manual repair; the migration header forbids automatic winner selection.

### 8.25.8 — Source matrix (Phase 1 directive §8)

- **Location:** [`science-source-expansion/2026-10-12-PHASE-1-SOURCE-MATRIX.md`](./science-source-expansion/2026-10-12-PHASE-1-SOURCE-MATRIX.md).
- **Contents.** 109 rows, generated in register order from blob `af67418c` of `109.v1`. Display names were checked against the UTF-8 register (0 mismatches).

| State | Seats |
|-------|-------|
| Membership CURRENT | 109 |
| Verified access family | 0 |
| Persisted identity binding | 0 |
| Runtime Registry entry | 0 |
| Capability fact | 0 |
| Adapter IMPLEMENTED | 0 |
| VERIFIED | 0 |
| ACTIVE | 0 |
| ACCEPTED | 0 |
| BLOCKED | 109 |

### 8.25.9 — Test evidence (this execution)

Common environment for every row:
- image `wsa-enterprise-backend-test:latest` (PHP 8.4.24, PHPUnit 11.5.56);
- source: a `ba24abda` snapshot plus the working-tree test files, mounted read-only;
- the existing vendor volume, mounted read-only;
- `--network none`, or the network namespace of the disposable server only.

| Command (inside the container) | Driver | Result | Exit |
|--------------------------------|--------|--------|------|
| `vendor/bin/phpunit -c phpunit.xml …/IntegrationClassification …/Identity …/Capability …/Membership tests/Unit/Support` | SQLite `:memory:` | 205 tests, 6043 assertions, 0 failures | 0 |
| `php tests/bootstrap-pgsql.php` without the destructive opt-in | PostgreSQL 16 (`postgres:16-alpine`) | refused | 1 |
| `vendor/bin/phpunit -c phpunit.pgsql.xml tests/Pgsql/IntegrationClassificationPostgresIsolationTest.php` (three consecutive runs) | PostgreSQL 16 | 13 tests, 201 assertions each | 0, 0, 0 |
| `vendor/bin/phpunit -c phpunit.pgsql.xml` | PostgreSQL 16 | 121 tests, 840 assertions | 0 |

Logs are kept outside the repository. No test was skipped, deleted, or weakened.

### 8.25.10 — IC closure delta

**IC may be CLOSED only when all of the following hold:**
1. The test files of §8.22.3 are committed.
2. The `backend-pgsql` job has passed on GitHub.
3. The deployment order is decided and validated in staging.
4. An authorized operator has run §8.25.7 on each real environment before deployment and recorded zero rows.

| Item | Status |
|------|--------|
| E2b | PASS |
| E6 / supersede race / deadlock | PASS |
| Test-infrastructure safety | PASS |
| Disposable-server disappearance | Classified (lifecycle); issuer UNKNOWN |
| IC-AUDIT-002 | BLOCKED (commit and push authorization; GitHub run) |
| E8 | BLOCKED (operational decision; staging validation) |
| IC-AUDIT-003 | Procedure defined; real environments UNKNOWN |

**IC disposition: BLOCKED.** No defect was found in the committed IC code.

### 8.25.11 — Phase 1 status and authorizations required

**Status: PHASE 1 BLOCKED.**

Unmet mandatory gates:
- Runtime Source Registry;
- Capability alignment;
- identity bindings;
- family adapters;
- IC closure;
- `T10+T8+T2`.

The decisions needed are listed in §8.24.8. Phase 2 must not start until Phase 1 passes its exit gate and the user authorizes it.

Separate authorizations are still required:
- a commit for each artifact of §8.25.1;
- the push;
- each decision of §8.24.8.

This section does **not** authorize any of the above, any Implementation Unit, CapVer, Capability population, identity binding, a Runtime Source Registry, IC activation, deployment, or activation of any of the 109 seats.

**FINAL STATUS: PHASE 1 BLOCKED; DESIGN GATE RECORDED (§8.23–§8.24), FOUNDATION IMPLEMENTATION NOT AUTHORIZED; IC VERIFIED ON ISOLATED POSTGRESQL, DISPOSITION BLOCKED; PHASE 2 NOT STARTED; NOTHING COMMITTED OR PUSHED.**

---

## 8.26 — Corrections, Remaining Design-Gate Items, and Consolidated Phase 1 Decision Package (2026-10-12)

**Status:** APPEND-ONLY CORRECTION AND DECISION RECORD. No earlier section is edited. **[V]** means verified on 2026-10-12 by inspection or tooling; tests were not rerun because the code, tests, and configuration are byte-identical to those measured in §8.22.8 (same SHA-256 for both test files and for `ci.yml`).

### 8.26.1 — Corrections

**C-1 — Section-number collision with the published branch (verification result).**
- **Finding [V].** `git ls-remote` shows the remote branch at `4dbf547811a09de47248e8a2e4a45c5a547d8632`. The local HEAD and the local remote-tracking ref are still `ba24abda`. The GitHub compare API shows that `4dbf547` is one commit ahead of `ba24abda` (0 behind). It changes only this ADR, adding 160 lines: a section numbered 8.21, "Conversation, Audit Reports, and Two-Phase Source Expansion Plan". The `ba24abda` copy of this ADR is a byte prefix of the `4dbf547` copy (blob `96140b9d57093fea65973eea9359e0600fb93e3f`).
- **Effect.** As first written in the `ba24abda` working tree, the local sections numbered 8.21 to 8.25 were uncommitted and duplicated that published number. They must not be committed under their current numbers. On integration with `4dbf547`, the local sections 8.21, 8.22, 8.23, 8.24 and 8.25 become 8.22, 8.23, 8.24, 8.25 and 8.26, and every internal reference moves with them. The published section 8.21 stays unchanged and precedes them. The source matrix's references are renumbered in the same way. No local section has been renumbered yet, because integration requires a `fetch` that is not authorized.
- **Type:** verification result.

**C-2 — §8.24.1, the two-phase plan.**
- **Old statement.** "Earlier ADR sections do not mention this plan."
- **Correction.** That is true of `ba24abda` only. The published section 8.21.4 (`4dbf547`) already records the two-phase plan as the current planning decision. §8.24.1 restates that decision and is not its first record.
- **Type:** verification result. It does not change the plan.

**C-3 — §8.23, the C+A decision.**
- **Old statement.** §8.23.1 expands "C+A" as "Collector + Authority", and §8.23's header marks the section "PENDING ARCHITECTURE-OWNER ACCEPTANCE".
- **Correction.** C+A is an approved user decision, recorded here as given:
  1. Evidence collection, evidence evaluation, and state assignment are separate.
  2. Collectors gather evidence and references; they neither assign nor persist states.
  3. The evaluator recommends a state; it has no authority to assign it or to store it as a final decision.
  4. CapVer holds the central decision authority, within the approved design.
  5. The Capability Store stores decisions that were already authorized; it does not create architectural authority.
  6. The existing CapVer-only VERIFIED authority is not open to renegotiation.
  7. UNVERIFIED must not be persisted explicitly without a separate authorization (consistent with §8.16.7).
  8. No Capability population of the 109 seats is performed under this decision.
- **Effect.**
  - The "Collector + Authority" expansion in §8.23.1 is withdrawn. Tracked text does not expand the label; the decision is defined by the eight points above.
  - The acceptance status in §8.23's header is superseded for the C+A separation.
  - Items that §8.23 marks DEFERRED or OPEN remain so: CapVer organizational ownership, the producer, enforcement placement, the producers of non-VERIFIED states, and automatic demotion.
  - In §8.23.4, the row "MISSING → any persisted state" also requires the separate authorization of point 7 whenever the target state is UNVERIFIED.
  - Approval of the design is not implementation, activation, or Git authorization. The published section 8.21.5 refers to the same boundary as "the existing approved C+A boundary".
- **Type:** approved user decision.

**C-4 — §8.24.3, evidence references.**
- **Old statement.** "`CapabilityRecord` still lacks the evidence-reference field required by §8.16.7 … Resolving either changes production code."
- **Correction [V].** The §8.16.7 evidence requirement is implemented in the persistence layer. `CapabilityCellFact` (introduced in `31a6ee3984c800ff771508f8e496ea75e2cd0938`, recorded in §8.18.1) wraps `CapabilityRecord` with opaque `capability_evidence_ref[]`. It rejects blank, padded, and duplicate references, and it requires at least one reference for VERIFIED, PARTIAL, and UNAVAILABLE. The domain `CapabilityRecord` carries no references, which matches CPD-A.
- **Effect.** This item is removed from the Capability Contract gaps. The only remaining gap is the unmapped GO-1 vocabulary (§8.26.4).
- **Type:** verification result.

**C-5 — §8.25.5, `actionlint`.**
- **Old statement.** "`actionlint` was not run."
- **Correction [V].** `actionlint` 1.7.12 (Windows release; SHA-256 checked against the release checksums) reported 0 findings, with exit 0, for both the working-tree `ci.yml` and the HEAD copy (blob `baf6327a`). `shellcheck` was not installed, so the `run:` scripts were not shell-linted. No GitHub run exists.
- **Type:** verification result.

**C-6 — §8.24.4, membership verification detail [V].**
- **Register.** It holds 109 CURRENT seats with 109 unique `adr_id` values, all of the form `G<group>-<ordinal>` and consistent with their group and ordinal fields. Group counts equal the declared `group_counts`. The ten dual pairs reference only existing seats.
- **Repeated names.** Five display names repeat (FlowerBase, HortDB V1.0, Tropicals.cn, sCentInDB, AromaDb). Each repeat is exactly one declared dual pair, so there are no undeclared aliases. Dual pairs ≠ SAME_AS (D-10.4.2).
- **Matrix.** It holds 109 unique rows in register order, with 0 mismatches in `adr_id`, display name, group, or dual flag, and a uniform column count. Identity is NONE for 108 seats. For G3-01 it is "PINNED external identity (GO-ID-G3-01); no persisted binding". No seat has a registry entry or activation.
- **Type:** verification result; it changes nothing.

### 8.26.2 — Remaining design-gate item: Capability versus IC ordering

Published section 8.21.4 lists "the dependency/order between Capability and IC inventory" as a design-gate item; §8.24 did not decide it.

**Evidence.** Tracked text constrains but does not fix the order:
- the IC artifact comes before runtime binding (D-10.8.1);
- the Onboarding Contract comes before the first binding or activation (D-10.11.1);
- Cap ≠ IC (§8.16.3);
- a Cap subject is one seat (D-10.4.5).

**Proposal, not decided — per seat:**
1. IC classification (access family and evidence);
2. identity binding;
3. Capability evidence collection and CapVer decisions per dimension;
4. Onboarding Contract;
5. Runtime Source Registry entry;
6. activation gate.

Under this proposal, no Capability fact is written for a seat without an IC record. This needs a decision (§8.26.5, item 10).

### 8.26.3 — `CGHIA T10+T8+T2` decision memo

- **Tracked text.** In tracked text the combination appears only in published section 8.21.4/8.21.5 (`4dbf547`), as an open question that "is not approved merely because it appears in WIP extracts". No tracked file defines it; the only definition is in untracked working-tree extracts, which are not architectural authority.
- **Proposed definition (untracked).**
  - T10: Capability-gated multi-layer primary path selection;
  - T8: layered fallback with no upgrade of evidence class;
  - T2: a family adapter plus a per-source Projection, with "Projection owns query".
- **Conflicts.** T2 conflicts with D-04 and D-10.9.2, under which `ScientificSearchQueryBuilder` constructs source queries. D-10.9.5 gives Projection a fidelity-enforcement role, not query ownership.
- **Options.**
  - A: reject.
  - B: accept T10 and T8 as design candidates, and amend T2 so that Projection emits per-source constraint metadata that the Builder consumes, with the Builder keeping construction.
  - C: accept as written and explicitly supersede D-04 and D-10.9.2.
- **Recommendation.** B, once the Path Model design is committed as tracked text. B keeps the approved query authority, and C would reopen a settled boundary without evidence of a defect. Until then: **BLOCKED**.

### 8.26.4 — Capability vocabulary mapping proposal

`SOURCE-CAPABILITY-CONTRACT.md` (untracked; preserved unchanged) uses SUPPORTED, PARTIALLY_SUPPORTED, UNSUPPORTED, and UNKNOWN. The code uses the Cap v2 `CapabilityState`. The GO-1 values are kept as `HistoricalGo1CapabilityState`.

| Option | Description |
|--------|-------------|
| **1 (recommended)** | No state mapping. GO-1 states are historical collector inputs only. Each Cap v2 state is decided afresh under C+A |
| 2 | Advisory correspondence for review only: SUPPORTED → evidence toward VERIFIED or PARTIAL; PARTIALLY_SUPPORTED → PARTIAL candidate; UNSUPPORTED → UNAVAILABLE candidate (evidence required); UNKNOWN → nothing persisted (missing = UNVERIFIED). Every candidate still needs CapVer or an authorized producer |
| 3 | Automatic mapping. Prohibited by §8.23.9 and C-3 |

### 8.26.5 — E8 staging evidence required

**Status.** The order proposed in §8.25.6 is not decided.

**Evidence required in a staging environment before the order can be accepted:**
1. The §8.25.7 diagnostic returns zero rows on a staging copy.
2. A deploy with the proposed order shows that `migrate --force` finished and that `cghia_ic_records_single_active_uq` exists in `pg_indexes` before any new application container starts (compose event timestamps).
3. A drill with a seeded duplicate ACTIVE row shows that the migration refuses, no new application container starts, the previous release keeps serving, and no row changes.
4. `scripts/rollback-production.sh` restores the previous release.
5. Smoke checks pass.

`scripts/deploy-production.sh` is unchanged.

### 8.26.6 — Consolidated Phase 1 decision package (supersedes the list in §8.24.8)

| # | Decision | Current state | Recommendation | Blocks | Kind |
|---|----------|---------------|----------------|--------|------|
| 1 | Accept or amend §8.24 (registry contract, identity policy, family strategy) | Recorded, not accepted | Accept | Phase 1 | Design |
| 2 | Implementation Authorization for the Runtime Source Registry (§8.24.2) | None | Authorize after item 1, extending `ScientificSourceAdapterRegistry` | Phase 1 | Implementation |
| 3 | CapVer organizational ownership and enforcement placement | DEFERRED | Name an owner; enforce above the repository plus a defensive write check | Phase 1 | Design / organization |
| 4 | Authorize per-seat integration classification (IC inventory) | OPEN | Authorize seat by seat, starting with seats that already have evidence | Phase 1 | Implementation + external evidence |
| 5 | `T10+T8+T2` | BLOCKED | Option B (§8.26.3) | Phase 1 | Design |
| 6 | Capability vocabulary mapping | None | Option 1 (§8.26.4) | Phase 1 | Design |
| 7 | Deployment order and staging validation | Not decided | Order of §8.25.6, with the evidence of §8.26.5 | IC | Design + external |
| 8 | Same-key replay at REPEATABLE READ / SERIALIZABLE | Not in contract; fail-closed `23505` | Keep the READ COMMITTED contract | Neither | Design |
| 9 | Git: integrate `4dbf547`, commit the separated artifacts, push, and let GitHub run `backend-pgsql` | Not authorized | Authorize the sequence of the final report | IC and Phase 1 | Git / external |
| 10 | Capability versus IC ordering | Not decided | §8.26.2 proposal | Phase 1 | Design |
| 11 | Operator run of §8.25.7 on each real environment | Not run | Run before the next deployment | IC | External access |

C+A is not in this package: it was decided (C-3).

### 8.26.7 — Status

| Area | Status |
|------|--------|
| IC | **BLOCKED — EXTERNAL DEPENDENCY** (items 7, 9, 11). The technical items (E2b, E6, isolation) pass |
| Phase 1 | **BLOCKED** pending items 1–6 and 10 |
| Phase 2 | Not started |
| Git | Nothing committed or pushed. The local working tree has not been integrated with `4dbf547` |

This section does not authorize any implementation, population, activation, deployment, or Git operation.

**FINAL STATUS: CORRECTIONS C-1…C-6 RECORDED; C+A RECORDED AS APPROVED USER DECISION; LOCAL SECTION NUMBERS COLLIDE WITH PUBLISHED SECTION 8.21 AND REQUIRE RENUMBERING ON INTEGRATION; IC BLOCKED — EXTERNAL DEPENDENCY; PHASE 1 BLOCKED; PHASE 2 NOT STARTED.**

### 8.26.8 — Integration record

- **Placement.** §8.22–§8.26 were written in the `ba24abda` working tree, numbered 8.21 to 8.25, before the published §8.21 (`4dbf547`) was present locally. They now follow the published §8.21, renumbered as stated in C-1 (§8.26.1): the headings and the `§` references to these sections were shifted by one. The published §8.21 is unchanged. The six mentions of "section 8.21" without `§` refer to the published §8.21 and were not changed. No other text in §8.22–§8.26 was changed; reversing the renumbering reproduces the original text exactly.
- **What the integration commits.** Two files enter the branch history, each in its own commit: this ADR, and the Phase 1 source matrix `docs/architecture/science-source-expansion/2026-10-12-PHASE-1-SOURCE-MATRIX.md` cited in §8.25.8. The matrix changes only in its seven references to this ADR, which now use the final numbers (§8.22–§8.25). Its rows, counts, status codes, and blockers are unchanged.
- **What stays uncommitted.** These files cited in §8.22–§8.26 are not committed by the integration and remain working-tree files: `backend/tests/Pgsql/IntegrationClassificationPostgresIsolationTest.php`, `backend/tests/Pgsql/bin/ic-persist-worker.php`, the `backend-pgsql` job in `.github/workflows/ci.yml`, `docs/architecture/science-source-expansion/SOURCE-CAPABILITY-CONTRACT.md`, and the `backend/e2e-tmp` design extracts. Statements in §8.22–§8.26 that these files are uncommitted or untracked remain true.
- **Statements that describe the pre-integration state.** These statements describe this ADR and the source matrix before the integration: the "uncommitted" label of the §8.25.1 change list as it applies to these two files, the last sentence of C-1 (§8.26.1), the final-status line of §8.25.11 ("NOTHING COMMITTED OR PUSHED"), and the Git row and final-status line of §8.26.7.
- **Tests and CI.** The test results in §8.22–§8.25 come from the runs recorded there. No PHP or database test was rerun for the integration. GitHub has not run the `backend-pgsql` job, because that job is not committed. IC-AUDIT-002 remains BLOCKED.
- **Status.** The integration changes no decision or status. IC disposition remains BLOCKED, Phase 1 remains BLOCKED, and Phase 2 has not started. No source seat is activated. The matrix remains an accountability artifact, not a runtime registry, membership authority, or acceptance record.

---

> **Numbering note (2026-10-22).** The three sections that follow, §8.27, §8.28, and §8.29, are the published records of 2026-10-14: the continuity record, the automatic archiving policy, and the archived reconciliation report. Their numbers and text are unchanged. They do not replace the local 2026-10-13 records, which remain below as §8.63, §8.64, and §8.65.

## 8.27 — Cumulative Conversation / Continuity Record — General Scientific Search Engine and 109-Source Expansion (2026-10-14)

### 8.27.1 — Purpose and append-only preservation rule

This section records the conversation and working direction established on 2026-10-14 so a future ChatGPT conversation or engineering session can recover the objective, constraints, findings, prompt intent, and next action without relying on transient chat context. It is appended after §8.26.8. All earlier ADR text is retained; this section does not supersede, rewrite, or delete earlier records. This is a continuity record, not authorization to implement any proposal.

### 8.27.2 — Owner's governing objective

The user explicitly confirmed the governing objective:

> Build a general-purpose scientific search engine capable of integrating the 109 approved scientific source entries and expanding in the future, without requiring separate fixes for individual sources or individual cases.

The user explicitly rejected the direction of carrying out a dedicated FAOSTAT repair for the observed “wheat production” versus “wheat yield” issue. That observation may be used only as diagnostic evidence about a possible general contract or architecture weakness. It is not a standalone repair task in the current work path. The same rule applies to other isolated cases: investigate the general mechanism, not a growing collection of exceptions.

The intended general architecture is to preserve CSQ as the semantic authority for the scientific question; distinguish question understanding, retrieval specification, source capability requirements, capability-driven source selection, source-specific query translation, retrieval, normalization, provenance preservation, evidence evaluation, and answer composition; and permit heterogeneous sources to integrate through stable contracts without forcing every source to support the same operations.

### 8.27.3 — Conversation decisions and clarifications

1. The user asked to resume the scientific-source expansion work.
2. The user requested one large task with high agent autonomy and strict security procedures, reducing repetitive user prompts and routine clarification.
3. A read-only, multi-stage autonomous investigation prompt was prepared. It covers repository baseline preservation; production search-path tracing; Source Registry investigation; Source Capability Contract investigation; reconciliation of all 109 approved entries; query and capability-driven selection architecture; contract-testing inventory; general gap analysis; implementation roadmap; and final preservation verification.
4. The user corrected the direction to make clear that the mission is not to fix individual cases. The accepted goal is general architecture and scalable integration of the 109 sources.
5. The user clarified that this information must be saved in the existing ADR-023 conversation/continuity log so a new conversation can read it and understand what happened. The required operation is an append-only historical record, not a replacement summary and not a new ADR.
6. The user requires that all prior material remain intact. No historical section may be deleted, replaced, shortened, or silently reconciled.

### 8.27.4 — Autonomous investigation prompt: required scope

The prepared autonomous mission prompt is titled:

“WSA-Enterprise — Autonomous Scientific Search Architecture Mission — 109-Source Expansion | Capability Architecture | Strict Security Protocol”.

Its required investigation stages are:

- **Stage 0 — Baseline:** verify current branch, HEAD, tracked/staged/untracked/ignored state, stashes, worktrees, and evidence preservation.
- **Stage 1 — Production architecture:** trace CSQ, ScientificSearchQueryBuilder, ScientificQueryCompiler, RSC, MultiSourceScientificSearchOrchestrator, Registry, capability contracts, adapters, ScientificResultNormalizer, evidence gates, AnswerComposer, Results List/Viewer, and Home/Crop isolation.
- **Stage 2 — Source Registry:** establish canonical identity, membership, ownership, duplicate handling, verification evidence, registration/activation distinctions, and completeness validation.
- **Stage 3 — Source Capability Contract:** inspect declaration, consumption, enforcement, query capabilities, access requirements, licensing, pagination, rate limits, provenance, error handling, and supported/unsupported/unknown distinctions.
- **Stage 4 — All 109 entries:** reconcile every approved entry without omissions, with stable identity, specialization, registry and capability status, verified access/query mechanisms, adapter/integration status, normalization/provenance, runtime verification, restrictions, gaps, and evidence needed.
- **Stage 5 — General query architecture:** assess separation of semantic understanding, retrieval specification, capability requirements, selection, query construction, retrieval, normalization, evidence evaluation, and answer presentation.
- **Stage 6 — Contract testing:** inventory existing tests and design reusable conformance tests that scale with new sources rather than encoding individual incidents.
- **Stage 7 — Gap analysis:** rank confirmed defects, missing functionality, design ambiguity, missing evidence, environmental limitations, owner decisions, and external dependencies separately.
- **Stage 8 — Roadmap:** propose a dependency-ordered implementation plan without implementing it.
- **Stage 9 — Preservation:** verify no unauthorized repository mutation and accurately report execution limitations.

Required deliverables: one consolidated report; a verified architecture diagram/trace; complete 109-entry reconciliation with matching totals; Registry and Capability Contract findings; ranked general architectural gaps; reusable test strategy; staged roadmap; unresolved owner decisions; evidence limitations; preservation report; and one recommended next action.

### 8.27.5 — Security and authorization boundaries

The autonomous investigation prompt expressly forbids, unless separately authorized:

- staging, committing, pushing, pulling, fetching, switching branches, resetting, cleaning, stashing, rebasing, cherry-picking, rewriting history, or modifying Git metadata;
- deleting, renaming, overwriting, restoring, or normalizing existing files;
- modifying user changes, untracked/ignored files, temporary evidence, or worktrees;
- Docker/containers, network access, source API calls, database operations, migrations, dependency installation/download, deployments, or test execution;
- modifying source code, tests, configuration, ADR-023, source registries, capability declarations, selectors, adapters, CSQ, query builders, normalizers, or answer synthesis;
- source activation, Phase 2 start, or declaring Phase 1 closed.

The mission must stop after its consolidated report and wait for explicit authorization for subsequent implementation or runtime work. Historical approvals for narrower tasks do not authorize unrelated changes.

### 8.27.6 — Latest Cursor report available in the conversation

The latest supplied Cursor report is identified in the conversation as `Pasted markdown(20261014-182944).md`. The report's own verdict is **PARTIALLY COMPLETE — BLOCKED**. The following are report-derived findings, not new runtime verification by this record:

- No repository edits, staging, Git write commands, Docker, network, database, installation, or test execution occurred during that reported campaign.
- The B-1 static contract checks were reported as passing; runtime tests were not run.
- A mismatch was identified between an aligner test and later production behavior, with the test last changed at `2cfcd03` and a later rule change at `0ea1c7e`; the owner contract is unresolved.
- The report describes a FAOSTAT measure-detection path that can conflate “production” and “yield”. Per the user's subsequent direction, this is diagnostic evidence only and is not to become a separate repair task.
- A selector policy conflict remains unresolved: historical test expectations versus a later capability-flag behavior.
- CSQ independent-role behavior, bare “quantity”, Arabic “إنتاجية”, year-only measure inference, and MultiAnswer missing-role expectations were reported as unresolved contract/design questions, not authorized implementation tasks.
- Membership-register test execution was blocked by line-ending/environment differences; the report did not establish a valid current runtime result.
- A group of committed Feature test failures was described as historical; no re-baseline or test execution was authorized.
- Deployment workflow/script risks and required staging/operator evidence were reported; no deployment modifications or staging actions were authorized.
- The report says the proposed §8.33.9 preservation record was not applied in that campaign. This current section is a new continuity entry and does not claim to be that proposed section.
- The report's baseline references HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`; treat this as a report-stated baseline, not as proof of the current GitHub branch HEAD. The actual ADR blob read immediately before this entry was `6845f732dc97d7dccfc419909387fa535ad03e8d`.

### 8.27.7 — Source membership and activation boundary

The approved source membership baseline is 109 entries, with the earlier architecture record reporting group distribution of Group 1 — Plants: 22; Group 2 — Animal/Livestock/Poultry/Fish: 25; the remaining groups as defined in the authoritative register. Do not infer operational status from membership. The report and prior records indicate identity/registry/capability verification and activation are incomplete; the exact status of each entry must be derived from the current authoritative register and verified evidence. No source activation is authorized by this continuity entry.

### 8.27.8 — Current status and next action

- **Governing objective:** general-purpose, extensible scientific search architecture for all 109 approved source entries.
- **Current work type:** read-only forensic/architectural investigation and reconciliation; not individual bug fixing.
- **Next action:** run the consolidated autonomous read-only investigation prompt against the verified current repository state, produce one evidence-backed report reconciling all 109 entries and the Registry/Capability Contract, then stop for review and explicit authorization.
- **Phase 1:** remains blocked unless its actual exit criteria are independently satisfied with evidence.
- **Phase 2:** not started.
- **ADR-023:** this record is appended; previous content is preserved.

### 8.27.9 — Continuity and evidence rules for future conversations

A future conversation should read this section and the cited earlier ADR sections before proposing the next action. It must distinguish owner decisions from proposals, report-derived findings from fresh verification, and membership from runtime integration/activation. It must not reopen the rejected case-by-case repair direction. It must not claim tests passed if they were not executed, claim a source is operational without evidence, or infer that Phase 1/Phase 2 status changed without new verification. The next session should continue from §8.27.8 and preserve the append-only history.


## 8.28 — Automatic Conversation Artifact Archiving Policy (2026-10-14)

### 8.28.1 — Owner instruction

The user requests that prompts and reports related to the WSA-Enterprise scientific-search/source-expansion work be saved in ADR-023 automatically, without requiring a separate reminder each time, so future conversations can recover the work and continue from the correct stopping point.

### 8.28.2 — Automatic archiving rule

For each future work session in which a relevant prompt, Cursor report, audit report, implementation report, verification report, or material owner decision becomes available in the conversation or working context:

1. Append it to this existing ADR-023 in a new, uniquely numbered section or subsection.
2. Preserve the full original prompt/report text whenever the full text is available and its inclusion is practical. Do not replace full text with a summary merely for convenience.
3. For long reports, preserve the full report as an append-only archival entry or an explicitly referenced artifact whose actual path and availability have been verified; include a concise index entry in ADR-023. Never claim a report was archived unless its content or verified artifact reference is present.
4. Include a short chronological index stating date, artifact type, exact title/name, scope, outcome, unresolved issues, and next stopping point.
5. Distinguish verbatim source material from summaries and analysis. Mark unavailable original text as unavailable; never reconstruct it as if verbatim.
6. Preserve all existing ADR text, prior entries, reports, prompts, decisions, and historical statuses. No deletion, replacement, silent rewriting, or renumbering of prior entries is allowed.
7. Preserve source terminology, status distinctions, evidence boundaries, and security constraints. Do not convert a proposal into an approved decision or a historical test result into a current result.
8. Keep the archive in this ADR-023; do not create a replacement ADR as a substitute for the conversation record.
9. At the end of each work session, append a current stopping-point record when meaningful progress, a new report, a prompt, or an owner decision occurred. It must state the next action and any required authorization.
10. If a write to ADR-023 is not authorized or technically available in a future session, do not claim automatic archival succeeded. Preserve the material in the current response and explicitly report that ADR-023 still needs the append.

### 8.28.3 — Security and Git boundary

This archiving instruction authorizes the append-only documentation update to ADR-023 when the user has requested archiving, but it does not grant blanket permission for code changes, source activation, runtime tests, Docker, network access, database operations, deployment, or unrelated Git operations. Before each archival write, inspect the current file and branch, use an append-only change, verify the old content remains byte-for-byte as the prefix of the new content, and report the exact resulting commit and section. Do not stage or commit through a local Git workflow or push separately; if a GitHub contents update is used, disclose that it creates a documentation commit.

### 8.28.4 — Scope

This policy applies to the scientific search engine, Scientific Source Expansion, the 109 approved source entries, Source Registry, Source Capability Contract, CSQ, source selection and query construction, normalization/provenance, related Cursor prompts/reports, and decisions or constraints that materially affect this work. It does not require archiving unrelated personal conversations.

### 8.28.5 — Acceptance check

An archival action is complete only when the new entry is visible in the actual ADR-023 on the intended branch, the previous content is verified unchanged, the new section is confirmed present, and the result is reported to the user. The archival policy is prospective; it does not imply that every historical prompt or report has already been captured verbatim.


## 8.29 — Archived Cursor Report: Scientific Search Architecture and 109-Source Reconciliation (2026-10-14)

### 8.29.1 — Source artifact and archival status

- **Original uploaded report:** `Pasted text(20261014-185122).txt`
- **Report title:** `WSA-Enterprise: Scientific Search Architecture and 109-Source Reconciliation (Consolidated Report)`
- **Source received:** 2026-10-14 in the conversation.
- **Report size:** 1,725 lines; 47,700 characters in the available attachment.
- **Archival form in this section:** a structured continuity record of the report's findings and exact reported baseline. The original attachment remains the verbatim source for the full report text; this section does not falsely claim that all 47,700 characters have been reproduced verbatim here.
- **Instruction:** retain the original report artifact when available. Future archival work should add the full report text or a verified repository artifact path if full-text archival is requested/available. Do not treat this section as a substitute for the original report.

### 8.29.2 — Executive findings as reported by Cursor

The report concludes that the current codebase cannot yet integrate all 109 approved scientific source entries through a general mechanism. Its central finding is architectural, not a list of isolated source defects:

1. No canonical runtime source registry exists. At least eight runtime structures independently hard-code keys for the five currently live sources, and application code does not connect those source keys to the 109 register IDs (`G1-01` through `G6-22`).
2. Capability v2 is typed and persisted as a model, but no application path writes capability facts and its only runtime consumer is observational; retrieval is still permitted.
3. Production selection uses a fixed list and feature flags. The capability-driven selector exists off the production route.
4. `ScientificQueryCompiler` output is attached as metadata that no adapter consumes. Adapters receive free-text keyword variants instead of a compiled per-source query bundle.
5. Source-specific branches remain in shared orchestration, ranking, verification, validation, and presentation logic.
6. All 109 entries exist in the authoritative membership register, but none is reported as verified, registered, capability-backed, adapted, or runtime-tested.

### 8.29.3 — Reported 109-entry reconciliation

| Status | Count | Meaning in the report |
|---|---:|---|
| PARTIAL | 65 | Candidate identity and documented access channel; not verified |
| UNVERIFIED | 44 | Ambiguous/unestablished identity or no documented access |
| VERIFIED | 0 | No seat meets the report's verification definition |
| MISSING | 0 | All seats are present in the authoritative membership register |
| Runtime checks | 109 NOT_EXECUTED | No endpoint or integration runtime checks were run |

Additional report-reported cross-checks:

- Identity: 90 CAND, 16 AMB, 3 NEST.
- Access: 36 machine/bulk, 41 web-only, 32 with no documented access.
- Authentication: YES 5, NO 32, UNKNOWN 72.
- Licence: documented for 31, UNKNOWN for 78.
- Membership: 109 CURRENT seats, unique `adr_id` values, and 10 cross-group dual pairs.
- Group counts: G1 22; G2 25; G3 14; G4 7; G5 19; G6 22; total 109. Group-specialization labels in the report are explicitly identified as inference from member names, not register facts.

These are report-derived findings from the supplied static audit and evidence review. They are not new runtime verification or independent external validation by this archival entry.

### 8.29.4 — Production path and architectural findings

The report traces the main Home/Crop retrieval path through query planning, CSQ freezing in `QueryUnderstandingService`, `AgriculturalScientificSearchService`, `RetrievalSemanticContract`, `MultiSourceScientificSearchOrchestrator`, `ScientificSearchQueryBuilder`, `ScientificSourceSelector`, `ScientificSourceAdapterRegistry`, source adapters, normalizers, ranking/relevance, validation, `AnswerComposer`, scientific presentation, Results List, and Viewer.

Reported findings include:

- CSQ is frozen as the semantic authority, but downstream components re-derive meaning from legacy constraints or planner-derived labels.
- The query builder and query compiler have duplicated/incomplete responsibilities; compiled queries are not consumed by adapters.
- The production selector is not capability-driven.
- The capability-driven selector is reachable only through an unrouted path.
- Provenance is incomplete in the final presentation: no seat attribution, and reported gaps include retrieval-time timestamp handling, licence/reuse fields, raw metadata omission from payloads, and missing source identity in citations.
- Evidence evaluation is duplicated across stages; relevance/directness rules can disagree.
- Home and Crop have separate paths, but isolation predicates are duplicated, and the Home endpoint also accepts crop-related fields.
- The report marks some findings as direct verification [V], some as read-only agent findings not individually re-read [A], and some as inference [INF]. Future readers must preserve those evidence labels rather than treat all claims as equally verified.

### 8.29.5 — Registry and capability-contract findings

The report identifies competing structures:

- `ScientificSourceAdapterRegistry`: five hard-wired adapters.
- `AgriculturalProviderRegistry` and `ScientificAdapterBridgeProvider`: a separate four-source path that reportedly omits Consensus.
- `ScientificSourceProfileCatalog`: ten profiles, including inactive stubs.
- Selector constants and orchestrator key lists.
- `Stage3SourceKeyIdentityBridge`, `Stage3CghiaCoexistenceBoundary`, FAOSTAT sub-registries, and `ScientificSourceRegistry` category structures.
- The tracked 109-seat membership register, plus an incompatible 62-entry register and untracked matrices/evidence artifacts.
- Persisted identity/IC/capability tables are reported to lack seeders and runtime writers/readers.

The documentary `SOURCE-CAPABILITY-CONTRACT.md` reportedly defines SUPPORTED, PARTIALLY_SUPPORTED, UNSUPPORTED, UNKNOWN, and dimensions A–G. Cap v2 uses VERIFIED, PARTIAL, UNVERIFIED, UNAVAILABLE, NOT_APPLICABLE and evidence freshness. The model can represent several important states, but the report says it is not enforced for production retrieval, rate limits are not represented, and the adapter interface lacks capability declaration, typed query, limit, and generic error contracts.

### 8.29.6 — Ranked general architecture gaps

The report's dependency-ranked gap list is:

1. **GAP-REG:** no canonical runtime registry and no source-key-to-`adr_id` binding.
2. **GAP-CAP:** no capability-fact producer or enforcing selection consumer.
3. **GAP-ADAPT-IF:** no versioned shared adapter contract with declared capabilities, typed request, typed outcome, limits, and error taxonomy.
4. **GAP-SEL:** competing selectors and fixed-list selection instead of one capability-driven policy.
5. **GAP-QUERY:** compiled CSQ bundle is not used as the adapter input.
6. **GAP-SEM:** question meaning is re-derived downstream and semantic logic is duplicated.
7. **GAP-PROV:** incomplete provenance and licence fields through citations/presentation.
8. **GAP-EVID:** duplicated/conflicting evidence and directness policy.
9. **GAP-ERR:** no generic error taxonomy/retryable flag.
10. **GAP-FAMILY:** missing family adapters for verified access types such as OAI-PMH, RSS, REST, bulk/static.
11. **GAP-TEST:** no shared adapter-conformance suite or new-source-without-regression test.
12. **GAP-ISO:** duplicated Home/Crop isolation logic and Home endpoint accepting crop fields.

These are the report's architectural findings and proposals, not authorization to implement them.

### 8.29.7 — Reported roadmap and owner decisions

The report proposes a planning-only sequence:

- R0: evidence integrity and authority for documentary artifacts.
- R1: confirm canonical membership and identity.
- R2: canonical runtime registry and capability contract.
- R3: shared adapter conformance.
- R4: capability-driven selection and compiled-query boundary.
- R5: normalization and provenance invariants.
- R6: validate the 109 entries using separately authorized probes.
- R7: activate sources only with per-seat evidence and explicit approval.
- R8: repeatable onboarding with zero shared-code edits for a new source.

Owner decisions recorded by the report include identity verification, dual-pair binding policy, how the five currently live sources relate to the 109 seats, selection policy, P1-EXIT-01(c)/Gate A/B authorization, aggregator-mediated access, licence/display/redistribution policy, web-only/no-access handling, the incompatible 62-entry register, and authorization for container tests/network probes.

### 8.29.8 — Preservation and execution limits reported

The supplied report says the audit was read-only, with no repository file modifications, staging, source activation, tests, Docker, network, database, installation, or deployment. It reports a branch/HEAD of `phase-18-m18-ai-marketing-communications / f2025315d8990f52f3c658c968a3ec8252ac18b0`, 0 staged, 17 tracked modifications, 1,118 untracked entries, 92 ignored entries, 0 stashes, 8 worktrees, and 40/40 baseline hashes unchanged. It says Phase 1 was not closed and Phase 2 had not started.

This is the baseline stated in the report at its recorded time, not a guarantee of the current repository state.

### 8.29.9 — Owner's controlling direction and next action

The user's controlling direction, confirmed after this report was produced, is to pursue a general-purpose scientific search architecture for all 109 entries and future expansion—not separate repairs for individual questions or sources. The observed FAOSTAT measure-selection chain may inform GAP-SEM/GAP-QUERY analysis only; it is not an independent repair task.

**Next action:** review the report's proposed first architectural step—Gate A design for one Runtime Source Registry and a versioned Source Adapter Contract (GAP-REG and GAP-ADAPT-IF; roadmap R2/R3)—as a design-only proposal. Do not implement, activate sources, execute tests, access the network, use Docker, or modify any code unless separately authorized. Phase 1 remains unclosed and Phase 2 remains not started unless newly verified evidence and explicit decisions change that status.

> **Numbering note (2026-10-22).** These three sections were headed §8.27, §8.28, and §8.29 in the local working tree on 2026-10-13. Those headings are now §8.63, §8.64, and §8.65. They stay in this position, before §8.30, in their original order. §8.30–§8.62 keep their numbers. Navigational citations now use §8.63–§8.65. Historical sentences that still say §8.27–§8.29 are the 2026-10-13 audit range, the uncommitted-blob sentence, the uncommitted-inventory row, the commit-authorization sentence, and the in-place correction logs; they mean these local records, not the published sections above. Two later owner quotations keep the spoken numbers §8.27.8 and §8.28.4. No decision, report, prompt, or table was deleted.

## 8.63 — Conditional Acceptance of §8.24 — Documentation Decision (2026-10-13)

**Status:** USER DECISION — §8.24 CONDITIONALLY ACCEPTED AS A DESIGN BASIS. **Implementation authorized: NO.** Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Scope:** Records the user's decision of 2026-10-13 on item 1 of the decision package (§8.26.6): §8.24 is accepted conditionally, as a design basis, within the scope and conditions stated in this section. **Source of the decision:** the user's documentation instruction of 2026-10-13, which states "The user has approved the conditional-acceptance decision presented in the conversation" and authorizes only this documentation. It is a conversation record, not a repository artifact, and it is not archived under `science-source-expansion/` as D-08 requires. Append-only: no earlier section is edited or renumbered. **[V]** means verified on 2026-10-13 by inspection of the repository or by a read-only query; **[U]** means not verified for this record.

### 8.63.1 — Decision and status

- **Decision.** §8.24 is accepted **conditionally** as the design basis for the rest of Phase 1. The acceptance covers only §8.63.3–§8.63.9 and holds only under the conditions of §8.63.10. It is not an unconditional acceptance.
- **Effect on §8.26.6.** Item 1 moves from "Recorded, not accepted" to "Conditionally accepted (§8.63)". Formal acceptance is still required (§8.63.10, condition 1). Every other item of §8.26.6 keeps the state recorded there; §8.63.6 adds later evidence relevant to item 9 without deciding it.
- **What this decision does not do.** It does not close any open decision of §8.26.6, does not issue an Implementation Authorization Gate (D-10.15), and does not accept §8.23 beyond the C+A decision already recorded in C-3 (§8.26.1). Acceptance of the rest of §8.23 remains open.
- **Status relative to other decisions.**

| Decision | Status |
|---|---|
| D-01, D-02, D-04, D-09, D-10 (including D-10.8, D-10.10, D-10.11.1, D-10.15) | ACCEPTED; unchanged and not superseded by this section |
| C+A (§8.26.1 C-3) | Approved user decision; unchanged |
| Onboarding Contract design (§8.11) | ACCEPTED WITH NON-BLOCKING CLARIFICATIONS (design only); unchanged |
| §8.24 | CONDITIONALLY ACCEPTED as a design basis (this section) |
| §8.23 outside C+A | Not accepted; unchanged |
| §8.26.2 order proposal (six steps), §8.26.3 Option B, §8.26.4 Option 1, §8.25.6 deployment order | PROPOSED; unchanged. The seven-step order of §8.63.8 is recorded only as a conditional design requirement; its confirmation (§8.26.6 item 10) is OPEN |
| `T10+T8+T2` | BLOCKED; unchanged |
| Implementation Authorization (D-10.15, step 2) | Not issued |

### 8.63.2 — Two-phase plan

The two-phase plan of §8.21.4 and §8.24.1 is unchanged:
- **Phase 1:** architectural foundation, design decisions, IC Persistence Integrity closure, separately authorized source-integration implementation, and Phase 1 verification.
- **Phase 2:** comprehensive source acceptance, regression validation, controlled activation, and final source-by-source acceptance.

The design gate opens Phase 1 and is not a phase. There is no Phase 3. Neither phase is complete.

### 8.63.3 — Membership and source identity

- The membership authority remains `docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json` (D-09). On 2026-10-13 it holds `membership_revision` `109.v1`, 109 CURRENT seats with 109 unique `adr_id` values, groups G1 22, G2 25, G3 14, G4 7, G5 19, and G6 22, and 10 dual pairs; the file equals blob `af67418c1c070c6e53ffb3ab1eef62890149c4b7` at HEAD [V]. This section does not change the register, its identifiers, counts, groups, or order.
- `adr_id` remains the membership identifier. Display names are not identity keys (D-10.3.3). Similar names and declared dual pairs do not establish SAME_AS (D-10.4.2). `canonical_identity_id` remains unbound until valid SAME_AS or resource-unification evidence is recorded (D-10.3.4).
- Membership, identity binding, IC, Capability, adapter implementation, acceptance, and activation remain separate states (D-10).
- Activating all 109 seats as one operation is forbidden (D-10.2.6). Onboarding remains atomic per `adr_id` (D-10.2.3, D-10.11.2), under the accepted Onboarding Contract and the required authorization.

### 8.63.4 — Runtime Source Registry (design direction)

- **Direction (conditionally accepted).** Under D-01, a future Runtime Source Registry for ADR membership seats extends the existing Stage-3 adapter + registry boundary. It is not a parallel integration framework and not a second source of truth beside the register (D-09). The minimum contract of §8.24.2 is the design basis:
  - entries keyed by `adr_id`;
  - references, not copies, to the seat's IC record, identity binding, and Capability facts;
  - a per-seat activation state per D-10.10, plus explicit DISABLED, UNAVAILABLE, UNSUPPORTED, and FAILED outcomes;
  - adapter resolution by verified access family;
  - source identity and provenance carried into normalized results.
- **What exists today [V]:**

| Component | State |
|---|---|
| `ScientificSourceAdapterRegistry` | Production registry of five hard-coded Stage-3 adapters (`openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`); not a registry of seats |
| Runtime Source Registry for ADR seats | Does not exist |
| `ScientificSourceProfileCatalog` | Profiles `openalex`, `crossref`, `semantic_scholar`, and `fao_stat` are active; `consensus` and the stubs `agris`, `gbif`, `pubmed`, `europe_pmc`, and `agricola` are inactive. Profiles and stubs are not seats, bindings, or activation |
| Capability and Identity domain and persistence (`Research/Capability`, `Research/Identity`) | Defined and tested; no production writer. The Capability Store repository is container-bound; the identity binding repository is not |
| Seat wiring and per-seat onboarding | None; no seat literal in `backend/app` |

- This direction is a design basis only. It authorizes no implementation, wiring, or registration.

### 8.63.5 — Capability verification and CapVer authority

- Before any production binding that depends on Capability state, the following must be decided and documented:
  - the responsible CapVer authority and its organizational owner;
  - the enforcement boundary, including write-side safeguards (§8.16.14: "above repository and/or as defensive Persist write checks");
  - evidence requirements and lifecycle rules (§8.16.7, §8.23.3–§8.23.5);
  - the relationship between IC classification, identity binding, Capability verification, onboarding, registry registration, and activation (§8.63.8);
  - the treatment of the GO-1 vocabulary.
- Only CapVer may assign VERIFIED (D-10.5.8). CapVer ownership remains DEFERRED (D-10.5.9; §8.26.6 item 3).
- GO-1 states (`SOURCE-CAPABILITY-CONTRACT.md`, `HistoricalGo1CapabilityState`) are not mapped automatically to Cap v2 states (§8.23.6, §8.26.4). The choice between Options 1 and 2 of §8.26.4 is open.
- **Evidence references.** §8.26.1 C-4 records evidence-reference persistence as implemented through `CapabilityCellFact`. On 2026-10-13, `CapabilityCellFact` carries `evidenceRefs` and rejects blank, padded, and duplicate references [V]. The evidence-reference discrepancy listed in §8.24.3 therefore stays withdrawn as recorded in C-4; the unmapped GO-1 vocabulary remains the open Capability Contract gap.

### 8.63.6 — IC evidence and closure

- Verified per-seat IC evidence remains required before runtime binding (D-10.8.1). The IC inventory remains OPEN (§8.26.6 item 4).
- The levels of closure are distinct:

| Level | State on 2026-10-13 | Evidence |
|---|---|---|
| Audit item IC-AUDIT-002 (PostgreSQL suite in CI) | The blockers recorded in §8.25.5 (commit and push authorization; a GitHub run) no longer apply. Closed for this item only | Commit `f2025315d8990f52f3c658c968a3ec8252ac18b0` (parent `626bffc`) adds `backend/tests/Pgsql/IntegrationClassificationPostgresIsolationTest.php`, `backend/tests/Pgsql/bin/ic-persist-worker.php`, and the `backend-pgsql` job in `.github/workflows/ci.yml`; it is HEAD and equals the local `origin` tracking ref [V]. In GitHub Actions run `38315050442` for that commit, job `backend-pgsql` completed with conclusion `success` [V, read-only query]. The PHPUnit summary in that job's log ("OK (121 tests, 840 assertions)") was read when the commit was published and was not re-read for this record [U]. The run's overall conclusion is `failure` because of the `backend`, `frontend`, `security`, and `mobile` jobs. Their failing steps and first reported errors are [V, read-only log query]: `backend`, `php artisan test --no-ansi`: 155 failed, 4 skipped, 2797 passed in 39 test classes, the first reported failure being in `FaoStatPhase3AGeneralRemediationTest` (line 57); `frontend`, `npm run build`: TypeScript error TS2345 in `src/public/FieldCropCanonicalAnswerView.tsx` (28,53), reproduced locally on 2026-10-13 with a non-emitting type-check; `security`, `php artisan test --group=security --no-ansi`: 4 failed, 205 passed (`M19SecurityHardeningTest`, `Phase14M14EnterpriseAdminTest`, `ServiceOwnershipArchitectureTest`); `mobile`, `flutter test`: 1 of 76 tests failed (`test/stage8_screens_test.dart`, "research agent shows backend answer"). The same four jobs also failed in runs `38311970424` (`626bffc`), `38293395393` (`4dbf547`), and `37932365430` (`31a6ee3`) [V]. The causes have not been analysed beyond the first reported errors [U], and they are not attributed to IC. §8.21.3 reports failures of the same four jobs for the earlier baseline `ba24abda`; it is historical and does not describe this run |
| IC Persistence Integrity (technical) | E2b, E6, and test-infrastructure safety PASS; no in-scope defect found | §8.22, §8.25.2–§8.25.4 |
| Overall IC gate | **BLOCKED** | §8.25.10 items 3 and 4 are unmet: the deployment order decided and validated in staging (§8.25.6, §8.26.5, §8.26.6 item 7), and the operator check of §8.25.7 on each real environment (§8.26.6 item 11) |

- A successful `backend-pgsql` job does not by itself close IC.
- This section records the later state. It does not rewrite §8.25.5, §8.25.10, §8.26.6 item 9, §8.26.7, or §8.26.8; their statements that these files and the job are uncommitted, that GitHub has not run the job, and that IC-AUDIT-002 is BLOCKED describe the state when they were written.

### 8.63.7 — `T10+T8+T2` and query authority

- §8.26.3 Option B is recorded as the **recommended design direction**: T10 and T8 as design candidates, and T2 amended so that a per-source Projection emits constraint metadata that `ScientificSearchQueryBuilder` consumes, with the Builder keeping query construction. It is not an approved implementation specification. `T10+T8+T2` remains BLOCKED until a separate decision (§8.26.6 item 5).
- Option C is not accepted. D-04 and D-10.9.2 are not superseded.
- Preserved:
  - CSQ is the semantic authority for the scientific question (D-02, D-10.9.1);
  - `ScientificSearchQueryBuilder` constructs source-specific HTTP queries (D-04, D-10.9.2); D-04 names it the "HTTP query execution authority" and D-10.9.2 the "scholarly lexical construction authority";
  - `ScientificQueryCompiler` is metadata and compilation support, not an execution authority (D-04, D-10.9.3);
  - CSQ is not replaced by keywords alone, and no scientific question element, identity, or facet is dropped silently (D-10.9.5–6).

### 8.63.8 — Per-seat onboarding sequence (design requirement)

Subject to the accepted Onboarding Contract (§8.11, D-10.11), the order for each `adr_id` is:
1. per-seat IC classification and verified access evidence;
2. identity binding where valid evidence supports it;
3. Capability evidence and an authorized CapVer assessment;
4. the approved Onboarding Contract for the seat;
5. registry binding;
6. the per-seat activation gate;
7. verification of source identity, provenance, failure behaviour, and acceptance.

This order extends the §8.26.2 proposal with step 7 and is recorded as a conditional design requirement. §8.26.6 item 10 stays open until it is confirmed (§8.63.10, condition 7). The list does not permit executing any step.

### 8.63.9 — Preserved search architecture

Unchanged by this decision:
- CSQ as semantic identity;
- `ScientificSearchQueryBuilder` as query-execution authority;
- `ScientificQueryCompiler` as metadata and compilation support;
- `RetrievalSpecification` (RSC) and `MultiSourceScientificSearchOrchestrator`;
- the separation between question semantics and execution representations (D-02);
- provenance and original-source identity requirements (§8.9.7, D-10.3.6, §8.24.2); D-03 keeps answer authority with evidence, so that source ranking or API wording does not replace evidence.

No source code, runtime behaviour, interface, test, or query semantics is changed by this section.

### 8.63.10 — Conditions for Phase 1 to leave BLOCKED

| # | Condition | State on 2026-10-13 |
|---|---|---|
| 1 | Formal acceptance of the conditionally accepted design (§8.24 under this section) | Open |
| 2 | Approval of the Onboarding Contract and of the Runtime Source Registry contract | Onboarding Contract design accepted with non-blocking clarifications (§8.11.1); Registry contract conditionally accepted only (this section). Open |
| 3 | Explicit designation of CapVer ownership, authority, and enforcement | Open (DEFERRED) |
| 4 | Explicit authorization of the per-seat IC inventory and evidence collection | Open |
| 5 | Resolution and documentation of `T10+T8+T2`, preserving the Builder's query authority | Open (BLOCKED) |
| 6 | An explicit decision on the GO-1 vocabulary, without automatic mapping | Open |
| 7 | Resolution of the IC / Capability / registry / activation order, and of the outstanding staging and operator checks | Order recorded as a conditional design requirement (§8.63.8); staging and operator checks not done. Open |
| 8 | All applicable IC closure requirements (§8.25.10) | Items 1 and 2 met; items 3 and 4 open (§8.63.6). Open |
| 9 | A separate, explicit Implementation Authorization Gate with a defined scope (D-10.15) | Not issued |
| 10 | Focused acceptance tests and evidence, on SQLite and on isolated PostgreSQL where applicable (§8.24.9 item 7) | No implementation to test yet. Open |

Phase 1 remains BLOCKED while any condition is open. Phase 2 may begin only after documented Phase 1 acceptance and an explicit transition decision.

### 8.63.11 — Cross-reference notes (not resolved here)

Recorded for a separate decision; no earlier text is changed:
- §8.24.9 item 2 reads "IC is CLOSED under §8.25.9"; §8.25.9 is "Test evidence (this execution)", and the IC closure conditions are in §8.25.10.
- §8.24.8 item 8 refers to §8.25.4; the same-key replay discussion is in §8.25.3.
- §8.24.9 item 1 refers to "Decisions 1–5 of §8.24.8"; §8.26.6 supersedes that list with a different numbering.
- §8.24.6 states that `T10+T8+T2` "does not appear in this ADR"; §8.26.3 records that it appears in §8.21.4 and §8.21.5 as an open question.
- §8.25.5, §8.26.6 item 9, §8.26.7, and §8.26.8 describe the state before commit `f2025315` (§8.63.6).

### 8.63.12 — Authorization boundary

This conditional design acceptance is not an implementation, onboarding, capability-verification, identity-binding, activation, deployment, commit, or push authorization. Each activity remains subject to its own required approval and gate.

This section does not activate or bind any seat, does not write Capability, Identity, or IC records, does not change the membership register or the source matrix, and does not supersede D-04, D-09, D-10.8, D-10.10, D-10.11.1, or D-10.15.

**FINAL STATUS: §8.24 CONDITIONALLY ACCEPTED AS A DESIGN BASIS; NO IMPLEMENTATION AUTHORIZATION; IC GATE BLOCKED; PHASE 1 BLOCKED; PHASE 2 NOT STARTED; NO SEAT ACTIVATED.**

---

## 8.64 — Decision-Record Closure: Conditional Acceptance, Implementation Gates, Phase 1 Exit Checklist, §8.23 Boundary, and `T10+T8+T2` Options (2026-10-13)

**Status:** USER DECISION RECORD — CLARIFICATION AND CONSOLIDATION. **Implementation authorized: NO.** Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Scope:** Append-only. No earlier section is edited or renumbered. **[V]** means verified on 2026-10-13 by inspection of the repository or by a read-only query; **[P]** means a user instruction or earlier report relayed in conversation records, not a repository artifact; **[U]** means not verified; **[I]** means an inference that no earlier text states explicitly; **[M]** means a required record that does not exist in the repository.

### 8.64.1 — Purpose and authority

This section resolves five decision-record ambiguities found by a read-only consistency audit of §8.23–§8.27 on 2026-10-13 (the audit report is a conversation record [P]). Each is defined here so that this section can be read on its own:

| ID | Ambiguity |
|---|---|
| AC-20 | §8.63.1 says the conditional acceptance of §8.24 "holds only under the conditions of §8.63.10", but those are Phase 1 exit conditions, all open, and condition 1 is "formal acceptance" itself |
| AC-24 | §8.11.9 requires separate Implementation Design and Implementation Authorization gates; the exit lists of §8.24.9, §8.26.6, and §8.63.10 name only the second |
| AC-10 | Four overlapping Phase 1 exit lists exist: §8.24.9, §8.26.6, §8.26.7, and §8.63.10 |
| AC-01 | §8.23 is marked pending acceptance, its C+A decision is approved (C-3), and §8.26.6 has no item for the rest of §8.23 |
| AC-22 | §8.63.7 calls Option B a "recommended design direction" inside the conditionally accepted scope, while §8.63.1 lists it as PROPOSED; "Option C is not accepted" does not say whether Option C is rejected |

**Authority.** The dispositions are recorded on the user's decision-closure instruction of 2026-10-13 [P]; that instruction is not archived under `science-source-expansion/` as D-08 requires [M]. Where this section restates an earlier decision, the earlier section remains the authority. The new elements are the clarification of §8.63.1 (§8.64.3), the gate definitions (§8.64.4), the classifications of §8.64.5 and §8.64.6, and the consolidated checklist (§8.64.7). No open proposal is approved and no implementation is authorized.

### 8.64.2 — Evidence baseline and limitations

- **Repository [V].** Branch `phase-18-m18-ai-marketing-communications`; HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0` (parent `626bffc`), equal to the local `origin` tracking ref and to the remote branch (`git ls-remote` and the GitHub API, read-only). History: `4dbf547` (published §8.21) → `d1840af` (integration of §8.22–§8.26) → `626bffc` (source matrix) → `f2025315` (IC PostgreSQL tests and CI job).
- **ADR-023 [V].** The remote copy and the HEAD copy are the same blob, `6845f732`, which ends at §8.26.8. §8.27 and this section exist only in the local working tree and are uncommitted. No remote section conflicts with them.
- **Membership and matrix [V].** Register blob `af67418c` (`109.v1`, 109 CURRENT seats with unique `adr_id`); matrix blob `cf8a31a1`, 109 rows in register order. Neither is changed by this section.
- **CI [V, read-only query].** GitHub Actions run `38315050442` for `f2025315`: `backend-pgsql`, `openapi`, `stage10`, and `docker-validate` succeeded; `backend`, `frontend`, `security`, and `mobile` failed; the run conclusion is `failure`.
- **Limitations.** No test, database, Docker, or deployment action was run for this section. The PHPUnit summary in the `backend-pgsql` log was not re-read [U]. Staging and real-environment states are unknown [U].

### 8.64.3 — AC-20: meaning of the conditional acceptance of §8.24

**Disposition: CLOSED (clarification).** §8.24 remains **CONDITIONALLY ACCEPTED** as a design basis.

1. **Validity.** The conditional acceptance recorded in §8.63 is in force from 2026-10-13. Its validity does not depend on Phase 1 being complete or on any condition of §8.63.10 being met. It changes or ends only through a later explicit decision recorded in this ADR. The words "holds only under the conditions of §8.63.10" in §8.63.1 are read as: the acceptance is limited to the scope of item 2 below, and the conditions govern progression, not validity.
2. **Accepted scope.** The design basis of §8.63.3–§8.63.9: the separation of membership and identity; the Runtime Source Registry direction (an extension under D-01, with the minimum contract of §8.24.2); the Capability and CapVer constraints of §8.63.5; the IC levels of §8.63.6; the preserved query authorities of §8.63.7 and §8.63.9; and the per-seat onboarding order of §8.63.8 as a conditional design requirement.
3. **Not approved by it.** Any proposal, recommendation, or open question in §8.24 or §8.26 that is not separately decided, in particular: `T10+T8+T2` and its options (§8.64.6); CapVer ownership and enforcement placement; Capability population; the GO-1 mapping choice; the deployment order; the IC operational requirements; and any implementation.
4. **Conditions.** The outstanding conditions are the items of the consolidated checklist (§8.64.7) and, until that checklist is confirmed, the earlier criteria named in its confirmation status. They govern completion of the Implementation Design Gate, the Implementation Authorization Gate, and Phase 1 exit.
5. **Formal acceptance** (§8.63.10 condition 1) means an explicit, recorded decision of the user or architecture owner that completes the Implementation Design Gate (§8.64.4) for a stated scope and confirms the §8.24 basis for that scope, stating any amendment. Until such a record exists, §8.24 stays CONDITIONALLY ACCEPTED. Formal acceptance is checklist item P1-EXIT-02. This definition was drafted for this record to resolve AC-20 [I]: the user's instruction fixed the reading of item 1, not this definition, and its confirmation is OPEN. This ADR does not state whether §8.24 must also be formally accepted as a whole; that question is OPEN. The set of implementation scopes is not defined, so P1-EXIT-02 cannot be shown complete until that set is recorded.
6. **Decision owner:** the user or architecture owner (§8.24.8). **Evidence required:** the Implementation Design Gate record and the decisions it depends on (P1-EXIT-01).
7. **Terms.** These are distinct and must not be substituted for one another:

| Term | Meaning | State on 2026-10-13 |
|---|---|---|
| Design acceptance | An explicit decision accepting a design basis | §8.24: CONDITIONALLY ACCEPTED |
| Design-gate completion | A recorded Implementation Design Gate (Gate A, §8.64.4) | NOT COMPLETED for any scope |
| Implementation authorization | A recorded Implementation Authorization Gate (Gate B, §8.64.4) | NOT AUTHORIZED |
| Phase 1 exit | Every item of §8.64.7 is PASS, the earlier criteria that remain applicable (§8.64.7, confirmation status) are met, and the exit is recorded (P1-EXIT-11) | BLOCKED |
| Phase 2 start | A separate explicit transition decision after Phase 1 exit | NOT STARTED |

### 8.64.4 — AC-24: Implementation Design Gate and Implementation Authorization Gate

**Disposition: CLOSED (gates defined).** Gate A: **NOT COMPLETED**. Gate B: **NOT AUTHORIZED**.

**Sequence.** D-10.15 requires an Onboarding Contract Design Gate followed by a separate Implementation Authorization Gate. §8.11.9 requires separate Implementation Design and Implementation Authorization gates. Read together:
1. **Onboarding Contract Design Gate** (D-10.15, step 1). The Onboarding Contract design is ACCEPTED WITH NON-BLOCKING CLARIFICATIONS (§8.11.1). This is design acceptance only. Treating §8.11 as the gate of D-10.15 step 1 is an inference [I] from §8.10.5 ("still requires Onboarding Contract Design and Implementation Authorization") and §8.11.1; no earlier text states it, and outside this subsection the phrase "Onboarding Contract Design Gate" appears only in D-10.15. Its confirmation is OPEN.
2. **Gate A — Implementation Design Gate** (§8.11.9).
3. **Gate B — Implementation Authorization Gate** (D-10.15, step 2; §8.11.9).

Each gate is a separate, explicit, recorded decision. Completing this documentation section satisfies neither gate.

**Gate A** establishes an approved, coherent implementation design for a stated scope. A Gate A record must decide each of the following items, or state that the item is outside its scope:

| Gate A item | State on 2026-10-13 | Reference |
|---|---|---|
| Runtime Source Registry contract and ownership | Direction CONDITIONALLY ACCEPTED (minimum contract of §8.24.2); contract details and ownership OPEN | §8.24.2, §8.63.4 |
| CapVer organizational ownership and enforcement placement | DEFERRED | D-10.5.9, §8.16.14, §8.26.6 item 3 |
| Capability contract and permitted population rules | Cap v2 contract defined and implemented; producers of non-VERIFIED states OPEN; explicit UNVERIFIED persistence NOT AUTHORIZED; automatic demotion OPEN | §8.23.2, §8.23.4, §8.64.5 |
| Separation of membership, identity, IC, Capability, registry, and activation | ACCEPTED | D-09, D-10.3, D-10.8, D-10.10, §8.11.4 |
| Ordering of IC and Capability | OPEN: the six-step proposal of §8.26.2 is PROPOSED; the seven-step order of §8.63.8 is a conditional design requirement; the order is not confirmed. The wider IC, Capability, registry, and activation order is P1-EXIT-01 (e) | §8.26.2, §8.26.6 item 10, §8.63.8, §8.63.10 condition 7 |
| `T10+T8+T2` and its relation to D-04 and D-10.9.2 | BLOCKED | §8.64.6 |
| GO-1 vocabulary handling | Choice OPEN (Option 1 recommended); automatic mapping prohibited | §8.26.4, §8.64.5 |
| Source-specific integration strategy and verified access families | Strategy CONDITIONALLY ACCEPTED; no verified access family | §8.24.5 |
| Onboarding Contract implementation design | OPEN | §8.11.9 |
| Deployment order and staging validation, where the scope needs them | OPEN | §8.25.6, §8.26.5 |

A Gate A item is resolved only by an explicit recorded decision. A recommendation does not resolve it.

**Gate B** is a separate, explicit, scope-limited authorization. A Gate B record must state:
- the authorized scope (components, files, and any seats by `adr_id`);
- the prohibited scope;
- the Gate A record or records it relies on;
- the acceptance criteria and the checklist items of §8.64.7 it addresses;
- the required tests and evidence, including database engines;
- the permitted repository and Git operations, including whether commit or push is allowed;
- operational restrictions (databases, Docker, deployment, real environments);
- the conditions that suspend or invalidate it.

A Gate B authorization is suspended at least when: the work would exceed its scope; a Gate A decision it relies on is amended or withdrawn; protected work in progress would be modified; a production, shared, or real database could be reached; PostgreSQL isolation cannot be ensured; or a test exposes an unresolved architectural choice. An authorization for one scope does not permit adjacent work.

**Gate B lifecycle not defined (OPEN).** This ADR does not define: who may grant a Gate B authorization (§8.24.8 assigns issuing the Implementation Authorization Gate for the Runtime Source Registry to the user or architecture owner; P1-EXIT-03 names the user); who may suspend or invalidate it; who may clear a suspension; the evidence required before work resumes; how a suspension, clearance, or resumption is recorded; or whether an invalidated authorization needs a fresh Gate B record. Each needs an owner decision. Until then, this ADR provides no basis for resuming suspended or invalidated work without a new explicit, recorded decision.

**Current state [V].** No Gate A record and no Gate B record exist in this ADR. The statement of §8.18.2, "NO NEXT IMPLEMENTATION UNIT IS AUTHORIZED", remains in force.

### 8.64.5 — AC-01: acceptance boundary of §8.23

**Disposition: CLOSED (boundary recorded).** §8.23 is **not accepted as a whole**. C+A is **ACCEPTED** within the scope recorded in C-3 (§8.26.1), unchanged:
- collectors collect evidence and references; they neither assign nor persist Cap states;
- the evaluator recommends a state and has no authority to finalize or persist it;
- CapVer holds the central decision authority within the approved design; only CapVer may assign VERIFIED (D-10.5.8), and that authority is not open to renegotiation;
- the Capability Store stores already-authorized decisions and creates no authority;
- explicit UNVERIFIED persistence requires a separate authorization;
- C+A authorizes no Capability population of the 109 seats.

| §8.23 provision | Classification | Evidence |
|---|---|---|
| C+A separation (C-3, points 1–8) | ACCEPTED | §8.26.1 C-3 (approved user decision) |
| "Collector + Authority" expansion of C+A (§8.23.1) | NOT APPROVED (withdrawn) | C-3 |
| Role boundaries of Collector, Evaluator, CapVer, and Capability Store (§8.23.2) | ACCEPTED, as restatements of C-3 and D-10.5.7–8 | C-3, D-10.5 |
| Capability separate from IC, identity, membership, and activation (§8.23.2, §8.23.8) | ACCEPTED | D-10, D-10.5.10 |
| CapVer organizational ownership | DEFERRED | D-10.5.9, §8.16.14 |
| CapVer producer, and enforcement placement of CapVer-only VERIFIED | DEFERRED; not enforced in code (§8.18.1) | §8.16.14, §8.23.2 |
| Producers of PARTIAL, UNVERIFIED, UNAVAILABLE, and NOT_APPLICABLE facts | OPEN (population authority) | §8.16.14, §8.23.2 |
| Explicit persistence of UNVERIFIED | NOT AUTHORIZED | §8.16.7, C-3 point 7 |
| Evidence requirements (§8.23.3) | ACCEPTED, as restatements of §8.16.7 and D-10.5 | §8.16.7, D-10.4.5, D-10.5.2–5 |
| Lifecycle steps 1–4 and CURRENT → HISTORICAL supersession (§8.23.4) | ACCEPTED, as restatements of C-3 and CPD-B | C-3, §8.16.8 |
| MISSING → persisted state through an authorized producer (§8.23.4) | ACCEPTED WITH CONDITIONS: the producer is OPEN, and an UNVERIFIED target needs the separate authorization of C-3 point 7 | C-3 |
| Downgrade producer for VERIFIED → another state; automatic demotion on STALE evidence (§8.23.4) | OPEN | §8.23.4 |
| Rejection rules (§8.23.5) | ACCEPTED as far as they restate §8.16.13, D-10.5.8, and `CapabilityStoreDomainContract`; exception mapping DEFERRED | §8.16.13 |
| Decision provenance fields; CapVer decision identifier (§8.23.6) | OPEN (proposed in §8.23 only); identifier format DEFERRED | §8.23.6 |
| Database fail-closed for concurrent Cap writers on the IC pattern; CapVer arbitration of conflicting evidence (§8.23.6) | OPEN (proposed in §8.23 only) | §8.23.6 |
| No automatic mapping of GO-1 states to Cap v2 | ACCEPTED on the user's instruction of 2026-10-13 [P] ("No automatic mapping from historical GO-1 capability states to Cap v2 is allowed"), which is not archived in the repository [M]; consistent with §8.23.6 and §8.23.9, whose own classifications are given in this table, and with §8.63.5. In code [V], `HistoricalGo1CapabilityState` has no conversion to Cap v2 and `CapabilityStoreDomainContract::assertGo1NotEquatedToCapV2` maps nothing (commit `5a54491`; test `test_go1_historical_vocabulary_is_separate`); a code guard is not a decision record | this section |
| Choice between Options 1 and 2 of §8.26.4 | OPEN | §8.26.4, §8.26.6 item 6 |
| Failure modes (§8.23.7) and test requirements (§8.23.8) | OPEN: proposed requirements for a future Gate A covering Capability | §8.23.7, §8.23.8 |
| Prohibited shortcuts (§8.23.9) | ACCEPTED, as restatements of D-10.2.6–7, D-10.5, §8.16.7, and C-3; the GO-1 item as above | D-10.2, D-10.5 |
| CapVer implementation, producer or service, Capability population, seat activation (closing statement of §8.23) | NOT AUTHORIZED | §8.23, C-3 point 8 |

**Evidence references.** C-4 stands and is not reopened. On 2026-10-13 [V], `CapabilityCellFact` requires at least one opaque evidence reference for VERIFIED, PARTIAL, and UNAVAILABLE and rejects blank, padded, and duplicate references, and `CapabilityRecord` rejects PARTIAL without `limitation_text`. Two known gaps are documented, not claimed as defects: the persistence layer enforces neither CapVer-only VERIFIED (§8.18.1) nor the prohibition on explicit UNVERIFIED writes [V]. Both are latent, because the store has no production writer, and both belong to the DEFERRED enforcement placement decided under Gate A.

The text of §8.23 is unchanged. Acceptance of any OPEN or DEFERRED provision requires an explicit decision, normally within Gate A.

### 8.64.6 — AC-22: `T10+T8+T2` options

**Disposition: CLOSED (statuses fixed).** No option is approved.

| Option (§8.26.3) | Status |
|---|---|
| A — reject `T10+T8+T2` | NOT SELECTED (neither approved nor rejected) |
| B — T10 and T8 as design candidates; T2 amended so that a per-source Projection emits constraint metadata consumed by `ScientificSearchQueryBuilder`, which keeps query construction | **RECOMMENDED — NOT APPROVED** |
| C — accept as written and supersede D-04 and D-10.9.2 | **NOT SELECTED — NOT APPROVED** (not formally rejected) |
| `T10+T8+T2` as a whole | **BLOCKED — NO IMPLEMENTATION AUTHORIZED** |

- In §8.63.7, "recommended design direction" means RECOMMENDED. The conditional acceptance of §8.24 does not approve Option B; within §8.63.7 it covers only the preserved authorities. "Option C is not accepted" means NOT APPROVED; it is not a rejection.
- Approving Option B, or any other option, requires a separate explicit decision recorded in this ADR.
- **Preserved and in force:** D-04 and D-10.9.2 (`ScientificSearchQueryBuilder` constructs source-specific HTTP queries; D-04: "HTTP query execution authority"; D-10.9.2: "scholarly lexical construction authority"); D-02 and D-10.9.1 (CSQ is the semantic authority); D-10.9.3 (`ScientificQueryCompiler` is metadata-only); D-10.9.5–6 (no silent loss of identity or facets). Option C cannot take effect implicitly; it would need an explicit decision that supersedes D-04 and D-10.9.2 and states why.
- **The question can be closed only when:**
  1. T10, T8, and T2 are defined in tracked design text, or a tracked definition replaces them (today they exist only in untracked working-tree extracts; §8.24.6, §8.26.3);
  2. the user or architecture owner records an explicit selection of Option A, B, or C;
  3. for Option B, the record defines the constraint metadata that Projection emits and the Builder consumes, keeps query construction in the Builder, and is consistent with D-10.9.5–6 and with T8's rule that fallback never upgrades the evidence class;
  4. for Option C, the record explicitly supersedes D-04 and D-10.9.2 and gives the reasons;
  5. the decision is taken as part of Gate A (§8.64.4).
- No option may be implemented under this section. Implementing any option requires Gate A and Gate B.

### 8.64.7 — AC-10: consolidated Phase 1 exit checklist (P1-EXIT)

**Disposition: single consolidated list recorded on the user's instruction of 2026-10-13 [P] (AC-10: "Establish exactly one canonical checklist for Phase 1 exit"). Status: PROPOSED CONSOLIDATED CHECKLIST — NOT OWNER-APPROVED.** This subsection is intended to become the only Phase 1 exit checklist, replacing as exit lists §8.24.9, the "Blocks" column of §8.26.6, the Phase 1 row of §8.26.7, and §8.63.10, which are kept unchanged as history. §8.25.10 remains the definition of IC closure and is used by P1-EXIT-04 and P1-EXIT-05.

**Confirmation status.** The instruction is not archived in the repository [M]. Some conditions below were drafted for this record and are not taken from an earlier decision [I]: a Gate A record for each implementation scope (P1-EXIT-02); the note on P1-EXIT-05 and Gate B; the exclusion of the blanket blocker B1 (P1-EXIT-07); the reference to §8.23.8, which §8.64.5 classifies as OPEN (P1-EXIT-09); and the rule that every matrix state cites an artifact (P1-EXIT-10). Until an owner decision confirms the list and these conditions, the earlier criteria of §8.21.4 (Phase 1), §8.24.9, §8.25.10, §8.25.11, the Phase 1 row of §8.26.7, and §8.63.10 remain applicable together with this list, and none of them is treated as superseded.

States: PASS, FAIL, BLOCKED, DEFERRED, NOT RUN. In the "Blocks" column, **B** means Gate B (implementation authorization), **X** means Phase 1 exit, and **IC** means IC closure.

| ID | Item | Acceptance condition | Evidence required | State on 2026-10-13 | Blocks | Authority |
|---|---|---|---|---|---|---|
| P1-EXIT-01 | Prerequisite architectural decisions | Each recorded as an explicit decision: (a) CapVer ownership and enforcement placement; (b) Capability population and write authority (producers of non-VERIFIED states, explicit UNVERIFIED persistence, automatic demotion); (c) `T10+T8+T2` (§8.64.6); (d) GO-1 vocabulary handling; (e) IC, Capability, registry, and activation order (§8.63.8); (f) deployment order | Decision records appended to this ADR | **BLOCKED**: (a) DEFERRED; (b)–(f) OPEN | B, X; (f) also IC | User or architecture owner |
| P1-EXIT-02 | Design-gate acceptance (Gate A), including formal acceptance of §8.24 | A Gate A record (§8.64.4) for each implementation scope, confirming the §8.24 basis (§8.64.3) and deciding the §8.23 provisions that scope needs (§8.64.5) | Gate A record in this ADR | **BLOCKED**: no Gate A record; depends on P1-EXIT-01 | B, X | User or architecture owner |
| P1-EXIT-03 | Implementation authorization (Gate B) | A separate, scope-limited Gate B record (§8.64.4) for each implementation scope, including the per-seat IC inventory and evidence collection | Gate B record | **BLOCKED**: NOT AUTHORIZED | All implementation; X | User |
| P1-EXIT-04 | IC technical persistence evidence | §8.25.10 items 1 and 2 met; E2b, E6, and test-infrastructure safety pass on isolated PostgreSQL | §8.22, §8.25.2–§8.25.4, §8.25.9 (isolated runs, not rerun); `f2025315` tracks the four `backend/tests/Pgsql` files [V]; run `38315050442`, job `backend-pgsql` succeeded [V] | **PASS** (qualified: the isolated-PostgreSQL results are those recorded in §8.25.9; the CI job is the only later run) | IC, X | Executor evidence; GitHub Actions |
| P1-EXIT-05 | IC operational closure | §8.25.10 items 3 and 4: deployment order decided (P1-EXIT-01 f) and validated in staging with the evidence of §8.26.5; an authorized operator has run §8.25.7 on each real environment before deployment and recorded zero rows | Staging evidence; operator records | **BLOCKED**: not decided, not validated, not run | IC, X; B (see note) | User or architecture owner (order); authorized operator (checks) |
| P1-EXIT-06 | Runtime Source Registry and Capability integration | Implemented only under Gate B; extends `ScientificSourceAdapterRegistry` (D-01); entries keyed by `adr_id` that reference IC, identity, and Cap records (§8.24.2); Cap writes only through authorized producers under the enforcement decided in P1-EXIT-01 (a) and (b); the existing five-adapter path preserved | Commits under Gate B; results of P1-EXIT-09 | **BLOCKED**: not authorized, not started | X | User (authorization); executor (evidence) |
| P1-EXIT-07 | Per-seat classification and identity | For each of the 109 `adr_id` values, one seat at a time: a verified per-seat IC artifact (D-10.8.1) and, where evidence supports it, a persisted identity binding (D-10.3); or a per-seat blocker with its reason, recorded after a per-seat evidence review under the authorized IC inventory. The blanket blocker "no authorization" (matrix B1) does not satisfy this item | IC records, identity-binding records, matrix rows that cite them | **BLOCKED**: IC inventory not authorized; 0 IC artifacts and 0 bindings (§8.25.8) | X | User (authorization); IC and identity evidence owners |
| P1-EXIT-08 | Access-family adapters | Adapters only for verified access families, one per family with per-seat constraints; Stage-3 adapters reused only where an identity binding proves the same resource; no speculative adapters (§8.24.5); query construction stays with the Builder (D-04) | Adapter code under Gate B; family evidence; tests | **BLOCKED**: no verified family; 0 adapters | X | User (authorization); executor |
| P1-EXIT-09 | Regression and database-specific tests | For each implemented scope: focused tests on SQLite and, for persistence and concurrency, on isolated PostgreSQL through the guarded suite (including single CURRENT Cap fact under concurrency, §8.23.8); regression of the existing five-source path, CSQ identity, and Home/Crop isolation; commands, exit codes, and counts recorded; skipped work marked NOT RUN | Test logs | **NOT RUN**: nothing implemented to test; the PostgreSQL suite covers IC only (§8.64.8, Q-07) | X | Executor; GitHub Actions |
| P1-EXIT-10 | Source-matrix evidence integrity | Matrix updated from evidence only; 109 rows in register order; every state backed by a cited artifact; the register unchanged | Matrix-to-register comparison | **BLOCKED**: current integrity verified (109 rows in register order [V]); the final update depends on P1-EXIT-06 to P1-EXIT-08 | X | Executor; user review |
| P1-EXIT-11 | Formal Phase 1 verification and disposition | A Phase 1 verification report appended to this ADR that re-verifies P1-EXIT-01 to P1-EXIT-10 on current evidence, followed by an explicit decision recording Phase 1 exit | Report and decision record | **NOT RUN** | X | User or architecture owner |

**Note on P1-EXIT-05 and Gate B.** §8.24.7 lists IC closure (§8.25) among the dependencies of the foundation implementation. Each Gate B record must state how that dependency is met for its scope, or why the scope does not depend on it.

**Rule.** Phase 1 leaves BLOCKED only when every item is PASS, every earlier criterion that remains applicable under the confirmation status above is met, and the exit is recorded under P1-EXIT-11. A DEFERRED item does not satisfy exit. Phase 2 starts only through a separate explicit transition decision.

**IC levels** (unchanged from §8.63.6): IC-AUDIT-002 is CLOSED for that item only; IC technical persistence is PASS (P1-EXIT-04); the overall IC gate is BLOCKED (P1-EXIT-05). A successful CI job does not close IC.

**Mapping of the earlier lists** (intended to drop no criterion; the traceability notes after the table record where that is not yet shown):

| Earlier list | Mapping to P1-EXIT items |
|---|---|
| §8.24.9 items 1–7 | 1 → 01, 02, 03; 2 → 04, 05; 3 → 03, 06; 4 → 07; 5 → 08; 6 → 10; 7 → 09 |
| §8.26.6 items 1–11 | 1 → 02; 2 → 03, 06; 3 → 01; 4 → 03, 07; 5 → 01; 6 → 01; 7 → 01, 05; 8 → not an exit item: §8.26.6, which supersedes the list in §8.24.8, records "Blocks: Neither", consistent with §8.24.9 item 1 and the Phase 1 row of §8.26.7; §8.25.11, written before §8.26.6, refers to the §8.24.8 list; the same-key replay decision itself is not made (§8.26.6: "Not in contract"); 9 → executed; authorization unverified from the repository (§8.64.8, Q-12); 10 → 01; 11 → 05 |
| §8.63.10 conditions 1–10 | 1 → 02; 2 → 02; 3 → 01; 4 → 03, 07; 5 → 01; 6 → 01; 7 → 01, 05; 8 → 04, 05; 9 → 03; 10 → 09 |
| §8.25.10 items 1–4 | 1, 2 → 04; 3, 4 → 05 |
| §8.25.11 | Runtime Source Registry → 06; Capability alignment → 01 (b), 06; identity bindings → 07; family adapters → 08; IC closure → 04, 05; `T10+T8+T2` → 01 (c); "each decision of §8.24.8" → 01, 02, 03, except §8.24.8 item 8 (see the §8.26.6 row); commits and push of the §8.25.1 artifacts → executed; authorization unverified from the repository (Q-12); "Phase 2 must not start until Phase 1 passes its exit gate and the user authorizes it" → Rule above |
| §8.21.4, Phase 1 | Design-gate items: CapVer authority → 01 (a); Capability population rules → 01 (b); Runtime Source Registry contract → 02; seat identity bindings → 02, 07; `T10+T8+T2` → 01 (c); Capability and IC order → 01 (e). IC step 1 → procedural precondition (§8.64.11 item 5); steps 2–6 → 04, 05 (E2b and E6 per §8.25.2–§8.25.3; E8 → 05); step 7, "Append a formal IC closure delta to this ADR" → no separate item (covered only implicitly by 05 and 11); step 8 → 06, 07, 08, 09, 10 |

**Traceability notes** (recorded, not resolved):
- §8.24.9 item 4 requires each seat to be "classified and bound with evidence, or listed as blocked with its reason". P1-EXIT-07 adds the exclusion of the blanket blocker B1 and requires a binding only "where evidence supports it"; whether a classified but unbound seat without a blocker meets the exit is OPEN. Today the source matrix lists blockers B1–B4 for every seat [V].
- §8.24.9 item 7 requires all mandatory tests to pass "on SQLite and on isolated PostgreSQL"; P1-EXIT-09 requires isolated PostgreSQL "for persistence and concurrency". Until confirmed, the wording of §8.24.9 item 7 also applies.
- §8.21.4 step 7 (formal IC closure delta) has no separate item.
- §8.26.6 item 8 is not an exit item (see the table); the same-key replay decision itself remains open.

### 8.64.8 — Cross-reference corrections and historical qualifications

Earlier text is unchanged. Each row reads: original statement → evidence → correction or qualification → reason.

| # | Original statement | Evidence | Correction or qualification | Reason |
|---|---|---|---|---|
| Q-01 | §8.24.9 item 2: "IC is CLOSED under §8.25.9" | §8.25.9 is "Test evidence (this execution)"; the closure conditions are in §8.25.10 | Read as §8.25.10. Mapped to P1-EXIT-04 and P1-EXIT-05; superseded as an exit criterion only once §8.64.7 is confirmed | Wrong subsection number; when it arose is not determined [U] |
| Q-02 | §8.24.8 item 8: "(§8.25.4)" | Same-key replay at stricter isolation is discussed in §8.25.3 | Read as §8.25.3 | Wrong subsection number |
| Q-03 | §8.24.9 item 1: "Decisions 1–5 of §8.24.8" | §8.26.6 supersedes the list of §8.24.8 | Mapped to P1-EXIT-01 to P1-EXIT-03 (§8.64.7); superseded as an exit criterion only once §8.64.7 is confirmed | Obsolete list reference |
| Q-04 | §8.24.6: `T10+T8+T2` "does not appear in this ADR" | The published §8.21.4 (design-gate list) and §8.21.5 (prompt item A.5) mention it | True of the `ba24abda` copy only (C-1); already noted in §8.26.3. Status unchanged: BLOCKED | Written before the published §8.21 was integrated |
| Q-05 | §8.24.1: "Earlier ADR sections do not mention this plan" | §8.21.4 | Already corrected by C-2; no further action | — |
| Q-06 | §8.23 header "PENDING ARCHITECTURE-OWNER ACCEPTANCE"; §8.24.8 item 1 "Accept or amend §8.23 and this section"; §8.26.6 item 1 names §8.24 only | C-3; §8.63.1 | Boundary recorded in §8.64.5: C+A ACCEPTED; §8.23 not accepted as a whole; the remaining provisions are tracked by P1-EXIT-01 and P1-EXIT-02 | Conflicting descriptions of the acceptance of §8.23 |
| Q-07 | §8.24.3: Capability persistence is validated "for persistence, by the PostgreSQL suite" | `backend/phpunit.pgsql.xml` runs `tests/Unit/Agriculture/Research/IntegrationClassification` and `tests/Pgsql`, which contain IC tests only [V]; the Capability PostgreSQL evidence is the earlier isolated run of §8.18.1 [P], not rerun and not in CI | Qualified: Capability persistence has historical PostgreSQL evidence only. PostgreSQL coverage of any Capability scope is required by P1-EXIT-09 | Claim broader than the evidence |
| Q-08 | §8.24.3: `CapabilityRecord` lacks the evidence-reference field | C-4; re-verified on 2026-10-13 (§8.64.5) | No further action; not reopened | — |
| Q-09 | §8.24.5: "IC persistence is BLOCKED (§8.25)" | The IC levels of §8.63.6 | Read as "the overall IC gate is BLOCKED"; IC technical persistence is PASS (P1-EXIT-04) | Terminology |
| Q-10 | §8.25.5 heading "BLOCKED (external)" and "the job has not run on GitHub"; the IC-AUDIT-002 row of §8.25.10; §8.26.8 "GitHub has not run … IC-AUDIT-002 remains BLOCKED" | `f2025315`; run `38315050442` | Accurate when written. Current state per §8.63.6: IC-AUDIT-002 is CLOSED for that item only | State changed later |
| Q-11 | §8.25.5: a job of "+43 lines" that "provisions a job-scoped `postgres:16-alpine` service"; C-5 ran `actionlint` on that version | The committed job (`f2025315`, `ci.yml` +52/−0) starts `postgres:16-alpine` with `docker run … --network host … -c listen_addresses=127.0.0.1` on port 55432; its credential is `ic-${{ github.run_id }}-${{ github.run_attempt }}` [V] | §8.25.5 and C-5 describe a version that was not committed. The committed job binds PostgreSQL to loopback on the runner's host network, and its credential is still derived from the run. `actionlint` on the committed version: NOT VERIFIED. When and why the job changed is not recorded [U] | Description of an uncommitted version |
| Q-12 | §8.26.6 item 9: "Not authorized"; §8.63.1: "Every other item of §8.26.6 keeps the state recorded there" | `d1840af` integrates the local records after `4dbf547`; `626bffc` adds the matrix; `f2025315` adds the IC tests and CI job; the remote branch is `f2025315`; run `38315050442` executed [V]. These Git operations were carried out on user instructions [P] | Item 9: EXECUTED [V]; AUTHORIZATION: UNVERIFIED FROM REPOSITORY [P] (the instructions for the commits and the push are conversation records, not repository artifacts [M]). The sentence of §8.63.1 applies to the other items | Stale status |
| Q-13 | §8.26.7 Git row; §8.26.8 "What stays uncommitted"; §8.25.11 final status "NOTHING COMMITTED OR PUSHED" | As Q-12 | Historical. Still uncommitted [V]: `SOURCE-CAPABILITY-CONTRACT.md`, the `backend/e2e-tmp` design extracts, and §8.27 and §8.28 of this ADR | State changed later |
| Q-14 | §8.26.2: "the Onboarding Contract comes before the first binding or activation (D-10.11.1)" | D-10.11.1: "before the first runtime binding or activation"; §8.11.3 requires identity evidence and the IC reference "before binding" | Read with "runtime". An identity-binding record (step 2 of §8.63.8) is not a runtime binding, so the order of §8.63.8 is consistent with D-10.11.1 | The paraphrase dropped a qualifier |
| Q-15 | §8.63.1 table: D-01, D-02, D-04, D-09, D-10 "ACCEPTED" | D-10: "ACCEPTED WITH NON-BLOCKING CLARIFICATIONS"; D-09: "ACCEPTED"; D-01, D-02, and D-04 have no status of their own and fall under the ADR status "ACCEPTED (governance + master-register freeze; implementation not started)" | Read with those statuses; no decision changes | Shortened status |
| Q-16 | §8.63.1: the acceptance "holds only under the conditions of §8.63.10"; §8.63.10 condition 1 | — | Clarified by §8.64.3; §8.63.10 is to be superseded as an exit list by §8.64.7 once that list is confirmed | Circularity (AC-20) |
| Q-17 | §8.63.8 step 4: "the approved Onboarding Contract for the seat" | §8.11.3: one accepted contract, with one `onboarding_decision_id` per `adr_id` | Read as "a per-seat onboarding decision under the accepted Onboarding Contract (§8.11)" | Wording |
| Q-18 | §8.63.1 table: Option B "PROPOSED"; §8.63.7: "recommended design direction" and "Option C is not accepted" | — | Statuses fixed by §8.64.6 | Ambiguity (AC-22) |

### 8.64.9 — Status matrix

| Item | Status on 2026-10-13 |
|---|---|
| §8.24 design basis | CONDITIONALLY ACCEPTED (§8.64.3) |
| Formal acceptance of §8.24 | OPEN (P1-EXIT-02) |
| C+A (§8.26.1 C-3) | ACCEPTED |
| §8.23 outside C+A | Not accepted as a whole; classified in §8.64.5 |
| Onboarding Contract design (§8.11) | ACCEPTED WITH NON-BLOCKING CLARIFICATIONS (design only) |
| Gate A — Implementation Design Gate | NOT COMPLETED for any scope |
| Gate B — Implementation Authorization Gate | NOT AUTHORIZED |
| Option A / Option B / Option C (§8.26.3) | NOT SELECTED / RECOMMENDED — NOT APPROVED / NOT SELECTED — NOT APPROVED |
| `T10+T8+T2` | BLOCKED — NO IMPLEMENTATION AUTHORIZED |
| D-02, D-04, D-09, D-10 (including D-10.9.2, D-10.11.1, D-10.15) | In force; unchanged |
| IC-AUDIT-002 | CLOSED for that item only |
| IC technical persistence | PASS (P1-EXIT-04) |
| Overall IC gate | BLOCKED (P1-EXIT-05) |
| Phase 1 | BLOCKED (§8.64.7) |
| Phase 2 | NOT STARTED |
| The 109 seats | 109 CURRENT members; 0 bound, 0 populated, 0 activated |
| AC-20, AC-24, AC-10, AC-01, AC-22 | Decision-record ambiguity addressed by §8.64.3–§8.64.7; the underlying items keep the states above. AC-10: PROPOSED CONSOLIDATED CHECKLIST — NOT OWNER-APPROVED (§8.64.7). Open points of AC-20 and AC-24 are recorded in §8.64.3 item 5 and §8.64.4 |

### 8.64.10 — Not authorized

This section authorizes none of the following, and no other section of this ADR authorizes them:
- implementation of the Runtime Source Registry, Capability integration, CapVer, the Onboarding Contract, adapters, or any option of `T10+T8+T2`;
- Capability, IC, or identity-binding population; the per-seat IC inventory or evidence collection; source probes;
- binding, registration, or activation of any seat, and in particular activation of all 109 seats as one operation (D-10.2.6);
- changes to the membership register, the source matrix, CSQ, `ScientificSearchQueryBuilder`, `ScientificQueryCompiler`, the orchestrator, or the five Stage-3 adapters;
- automatic GO-1 mapping, or explicit UNVERIFIED persistence;
- database operations, migrations, Docker or service starts, deployment, or changes to `scripts/deploy-production.sh`;
- changes to code, tests, CI workflows, or runtime configuration;
- staging, commit, push, fetch, merge, rebase, or any other Git history operation;
- the start of Phase 2.

### 8.64.11 — Required approval and evidence for later implementation

Before any implementation in Phase 1:
1. the decisions of P1-EXIT-01 that the scope needs, recorded explicitly;
2. a Gate A record for the scope (P1-EXIT-02), including formal acceptance of the §8.24 basis for that scope;
3. a Gate B record for the scope (P1-EXIT-03) with the contents required by §8.64.4;
4. for a scope that depends on IC closure (§8.24.7), P1-EXIT-05 met or explicitly addressed in the Gate B record;
5. a fresh repository baseline and work-in-progress preservation check at execution time;
6. the evidence required by P1-EXIT-09.

Committing or pushing §8.27 and this section requires its own Git authorization.

### 8.64.12 — Decision statement

The conditional acceptance of §8.24 is a valid design basis; its conditions govern progression, not validity (AC-20). Implementation requires a recorded Implementation Design Gate and then a separate, scope-limited Implementation Authorization Gate; neither exists (AC-24). §8.64.7 is recorded as the single consolidated Phase 1 exit checklist, pending owner confirmation; until then the earlier criteria also apply (AC-10). C+A is accepted; the rest of §8.23 is classified in §8.64.5 and is not accepted as a whole (AC-01). Option B is recommended and not approved, Option C is not selected and not approved, and `T10+T8+T2` remains blocked (AC-22).

**FINAL STATUS: AC-20, AC-24, AC-10, AC-01, AC-22 RECORDED WITH EXPLICIT BLOCKERS; §8.24 CONDITIONALLY ACCEPTED; GATE A NOT COMPLETED; GATE B NOT AUTHORIZED; IC GATE BLOCKED; PHASE 1 BLOCKED; PHASE 2 NOT STARTED; NO SEAT BOUND, POPULATED, OR ACTIVATED.**

---

## 8.65 — Phase 1 Interim Verification Record (2026-10-13)

**Status:** EXECUTOR VERIFICATION RECORD. **Decision recorded: NONE. Implementation authorized: NO.** Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Scope:** Records a verification pass of 2026-10-13 and the documentation corrections it made. Labels as in §8.64. This section is not the report or the decision of P1-EXIT-11. It records no approval, no Gate A or Gate B record, and no change to any state of §8.64.7.

### 8.65.1 — Corrections made in place

§8.27 and §8.28 were uncommitted when this pass ran. They were corrected in place, and the "Append-only" statement of the §8.28 Scope describes §8.28 as first written. No line before §8.27 was changed. The corrections:
- record that the user instructions behind §8.27 and §8.28 are conversation records not archived under `science-source-expansion/` (D-08) [M] (§8.27 Scope, §8.28.1, the GO-1 row of §8.28.5, Q-12);
- separate the six-step proposal of §8.26.2 from the seven-step conditional requirement of §8.27.8 (§8.27.1, §8.28.4);
- give the failing steps and first reported errors of run `38315050442` (§8.27.6);
- align the citations of D-04, D-10.9.2, §8.9.7, D-10.3.6, and D-03 with their text (§8.27.7, §8.27.9, §8.28.6);
- mark inferences and drafted definitions [I], and record the open questions on formal acceptance, the §8.11 mapping, and the Gate B lifecycle (§8.28.3, §8.28.4);
- record §8.28.7 as PROPOSED CONSOLIDATED CHECKLIST — NOT OWNER-APPROVED, keep the earlier criteria applicable until it is confirmed, and add traceability rows and notes (§8.28.7, §8.28.9, §8.28.12).

### 8.65.2 — Evidence

| Check | Result |
|---|---|
| Repository | HEAD `f2025315` equals the local `origin` tracking ref and the remote branch head [V]; nothing staged; no commit or push in this pass |
| Runtime path | `ScientificSourceAdapterRegistry` holds the five Stage-3 adapters; no file under `backend/app`, `backend/config`, `backend/database`, `backend/routes`, or `backend/bootstrap` contains a seat ID; `IdentityBindingRepository` has no container binding and no runtime consumer, and its Eloquent implementation is used only by a unit test; `CapabilityStoreRepository` has no runtime consumer and is only bound in `AgriculturalIntelligenceServiceProvider`, apart from unit tests [V] |
| Register and source matrix | 109 seats, all CURRENT, in register order; every matrix row: IC ABSENT, Registry ABSENT, Capability UNVERIFIED, adapter not implemented, not activated, blockers B1–B4 [V] |
| CI run `38315050442` | `backend-pgsql`, `openapi`, `stage10`, `docker-validate` succeeded; `backend`, `frontend`, `security`, `mobile` failed (§8.63.6) [V] |
| Frontend type-check | `tsc -p tsconfig.app.json --noEmit` in `frontend/`: exit 2, the same TS2345 error as CI [V] |
| Backend, security, Capability, IC, and PostgreSQL tests | NOT RUN locally: PHP and Composer are not installed, and `backend/vendor` is an empty directory with no installed dependencies |
| Mobile tests | NOT RUN locally: Flutter is not installed |
| Workflow lint | NOT RUN: `actionlint` is not installed |

### 8.65.3 — Exit state

The states of §8.64.7 are unchanged. P1-EXIT-04 is PASS (qualified). P1-EXIT-01, 02, 03, 05, 06, 07, 08, and 10 are BLOCKED. P1-EXIT-09 and P1-EXIT-11 are NOT RUN. Each remaining item needs an owner decision, an implementation authorization, operator evidence, or work that depends on them (§8.64.11).

### 8.65.4 — Review corrections

A later review on 2026-10-13 checked §8.27–§8.29 against the cited decisions, the user instructions [P], the code, and CI, and corrected the following in place:
- §8.27.7 and §8.28.6: the statement that `ScientificSearchQueryBuilder` constructs source-specific HTTP queries (D-04, D-10.9.2), which the user's instructions use [P], is restored; the exact terms of D-04 and D-10.9.2 are kept beside it;
- §8.28.4: the phrase "Onboarding Contract Design Gate" also appears in that subsection;
- §8.28.5: §8.23.9 is classified ACCEPTED in that table and is no longer described as pending acceptance;
- §8.28.7: item 8 of §8.26.6 is mapped as not an exit item, because §8.26.6 supersedes the list in §8.24.8; the heading, §8.28.1, Q-01, Q-03, and Q-16 no longer describe the earlier lists as superseded before §8.28.7 is confirmed;
- §8.29.2: the consumers of the two repositories and the state of `backend/vendor` are stated precisely.

Additional evidence [V, read-only query]: the `backend`, `frontend`, `security`, and `mobile` jobs failed in each of the twelve CI runs from `d604b7b` (2026-10-02) to `f2025315`. Runs `38311970424` and `37065911218` show the same TS2345 error, the same mobile result (75 passed, 1 failed), and the same security result (4 failed, 205 passed); their backend results are 155 failed and 156 failed. A fresh non-emitting type-check reproduced TS2345 with exit 2. No state of §8.64.7 changes.

**FINAL STATUS: PHASE 1 BLOCKED; NO DECISION OR AUTHORIZATION RECORDED; PHASE 2 NOT STARTED.**

---

## 8.30 — Owner decisions of 2026-10-14; Gate A and Gate B for the IC inventory scope

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 (about 00:05 UTC+3), during execution of the user's master execution prompt of 2026-10-13 23:58, the executor presented a structured decision request and the user answered it [P]. Every option offered was taken from the options documented in this ADR, or (for P1-EXIT-01 (b)) drafted by the executor from the constraints recorded in §8.64.5 [I]. The text of the chosen option is quoted below. The request and the answers are conversation records and are not archived in the repository [M] (§8.30.6). Earlier sections are unchanged; labels as in §8.64.

### 8.30.1 — Decisions

| Item | Decision | Chosen option (quoted) | Effect |
|---|---|---|---|
| Confirmation of §8.64.7 (AC-10) | **NOT CONFIRMED** | "Do not confirm yet; the earlier lists remain applicable alongside it" | §8.64.7 stays PROPOSED CONSOLIDATED CHECKLIST — NOT OWNER-APPROVED; the earlier criteria named in its confirmation status remain applicable |
| P1-EXIT-01 (a) CapVer ownership and enforcement placement | **DEFERRED** (retained) | "Keep DEFERRED (Phase 1 cannot exit while DEFERRED)" | (a) is not satisfied; under the Rule of §8.64.7 Phase 1 cannot exit while it stays DEFERRED |
| P1-EXIT-01 (b) Capability population and write authority | **DECIDED** | "Conservative: only CapVer-authorized assessments write any state; UNVERIFIED is never persisted (missing = UNVERIFIED); no automatic demotion" | The only producer of any Capability state is a CapVer-authorized assessment; explicit UNVERIFIED persistence stays prohibited; no automatic demotion. Because CapVer is DEFERRED, no authorized producer exists, so no Capability state can be written today |
| P1-EXIT-01 (c) `T10+T8+T2` | **BLOCKED** (retained) | "Keep BLOCKED" | Options A / B / C stay NOT SELECTED / RECOMMENDED — NOT APPROVED / NOT SELECTED — NOT APPROVED (§8.64.6); D-04 and D-10.9.2 unchanged |
| P1-EXIT-01 (d) GO-1 vocabulary | **DECIDED: Option 1** (§8.26.4) | "Option 1: no mapping; GO-1 states are historical inputs only" | GO-1, GO-1b, and GO-1c states may be cited as historical inputs; they are never mapped to Cap v2 |
| P1-EXIT-01 (e) IC, Capability, registry, and activation order | **OPEN** (retained) | "Keep OPEN" | §8.26.2 stays PROPOSED; §8.63.8 stays a conditional design requirement |
| P1-EXIT-01 (f) deployment order | **DECIDED, subject to staging validation** | "Accept the §8.25.6 order, subject to staging validation" | The five steps of §8.25.6 are the deployment order. P1-EXIT-05 still requires staging evidence (§8.26.5) and the operator runs of §8.25.7 |
| Gate A and Gate B scopes | **IC inventory only** | "IC inventory: per-seat evidence review and IC records/blockers for the 109 seats" | Gate A §8.30.3 and Gate B §8.30.4. No Gate A or Gate B exists for the Runtime Source Registry, Capability integration, or adapters |
| CI defects outside the exit list (SEC-1, FE-1, MOB-1, SEC-2, SEC-3, backend identity) | **Not in this execution** | "None in this task" | No production code, test, or CI change |
| D-08 archival of the 2026-10-12 and 2026-10-13 prompts and reports | **Authorized**, uncommitted | "Yes, create them (no commit)" | Outcome in §8.30.6 |
| Local backend and mobile test environment | **Not authorized** | "No; record backend and mobile tests as NOT RUN" | Docker Desktop not started; PHP, Composer, and Flutter not installed |

### 8.30.2 — P1-EXIT-01 after these decisions

(a) DEFERRED; (b) DECIDED; (c) BLOCKED; (d) DECIDED; (e) OPEN; (f) DECIDED subject to staging validation. **P1-EXIT-01: BLOCKED.** Because (a) is DEFERRED, the Rule of §8.64.7 prevents Phase 1 exit regardless of the other items.

### 8.30.3 — Gate A record GA-IC-01: IC inventory scope

**Decision.** The user selected Gate A for the IC inventory scope (§8.30.1). The item-by-item table below applies the user's decisions to that scope. It was drafted by the executor [I], and the user may amend it.

**Scope.** A per-seat review of public evidence for each of the 109 `adr_id` values, producing one documentary IC evidence-review record per seat with either proposed classification facts or a seat-specific blocker. The scope covers no persistence, no runtime component, and no identity binding.

**Formal acceptance of the §8.24 basis for this scope** (§8.64.3 item 5): §8.24.4 (the 109 seats and identity bindings) and §8.24.5 (adapter and protocol-family strategy), as far as they govern classification, are accepted for this scope without amendment, together with D-10.8 and §8.13. Whether §8.24 must also be formally accepted as a whole stays OPEN (§8.64.3 item 5).

| Gate A item (§8.64.4) | For GA-IC-01 |
|---|---|
| Runtime Source Registry contract and ownership | Outside the scope |
| CapVer ownership and enforcement placement | Outside the scope (DEFERRED); the scope writes no Capability fact |
| Capability contract and population rules | Decided in §8.30.1 (b); outside the scope, since nothing is written |
| Separation of membership, identity, IC, Capability, registry, and activation | ACCEPTED (unchanged). The review records are separate from the membership register (§8.13.8) and bind no identity |
| Ordering of IC and Capability | Outside the scope: the scope performs no step after IC classification. Both candidate orders (§8.26.2, §8.63.8) put IC first; P1-EXIT-01 (e) is not decided here |
| `T10+T8+T2` | Outside the scope (BLOCKED) |
| GO-1 vocabulary | Option 1 applies: GO-1 reports are cited as historical inputs only |
| Source-specific integration strategy and verified access families | Applies: a family is recorded only from evidence on fetched public pages, never from a name or a website alone (D-10.8.2) |
| Onboarding Contract implementation design | Outside the scope |
| Deployment order and staging validation | Outside the scope (no database work) |

### 8.30.4 — Gate B record GB-IC-01: IC inventory scope

| Field (§8.64.4) | Content |
|---|---|
| Authorized scope | (1) Review of public, human-readable pages (home, about, journal information, documentation, API documentation, policies) for each of the 109 seats, in register order. (2) One new artifact, `docs/architecture/science-source-expansion/2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json`, holding one record per seat. (3) This section. |
| Prohibited scope | Persisting IC rows (`cghia_integration_classification_records`) or identity bindings; SAME_AS or any canonical identity; Capability facts; the Runtime Source Registry; adapters; changes to runtime code, tests, CI, or configuration; requests to machine endpoints (APIs, OAI-PMH verbs, SPARQL, query feeds), that is, source probes; changes to the membership register, the source matrix, `SOURCE-CAPABILITY-CONTRACT.md`, CSQ, QUS, or `backend/composer.lock`; database work, Docker, deployment; staging, commit, or push |
| Gate A relied on | GA-IC-01 (§8.30.3) |
| Checklist items addressed | P1-EXIT-03 for this scope; P1-EXIT-07 (per-seat evidence review with a seat-specific blocker where classification is not possible); input to P1-EXIT-10 |
| Acceptance criteria | 109 records in register order with `adr_id` and display name identical to the register; every non-null claim cites a page fetched during the review, with a short quotation; no record claims an identity binding; a seat known only by its display name is not classified, because classification may not rest on a name (D-10.8.2) |
| Tests and evidence | A structural integrity check of the artifact against the register (§8.30.5). Database engines: none, because nothing is persisted |
| Repository and Git operations | The two files above only; no staging, commit, or push |
| Operational restrictions | No database, Docker, deployment, or real environment |
| Dependency on IC closure (§8.64.7, note on P1-EXIT-05) | None: no IC record is persisted. Persisting IC records is a later scope that requires P1-EXIT-05 |
| Suspension | The conditions of §8.64.4, and any seat whose evidence would require a probe, an account, or authentication |

The Gate B lifecycle questions of §8.64.4 remain OPEN.

### 8.30.5 — IC evidence review: execution

#### 8.30.5.1 — Results

**Artifact.** `docs/architecture/science-source-expansion/2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json` (schema `adr023.ic_evidence_review.v1`; SHA-256 `877c1604ccde7d02b9f8e37bba089353a5dc5535be2eebdee61942e09ac0ce2a`; uncommitted) [V]. It records the SHA-256 of the membership register it was checked against. The register, the source matrix, and `SOURCE-CAPABILITY-CONTRACT.md` were not changed [V].

**Method.** Public, human-readable pages were fetched on 2026-10-14 by the executor and by review subagents working under the GB-IC-01 rules: no request to any machine endpoint, at most three fetches per seat, and every claim backed by a page fetched in that session plus a short verbatim quotation. Search-result snippets were not accepted as evidence. G6-01 … G6-10 carry the evidence of their dual peers G1-12 … G1-21 by reference; they remain separate seats and no SAME_AS was minted (D-09 item 7). GO-1, GO-1b, and GO-1c were treated as historical inputs only (Option 1).

**Structural check** (§8.30.4 acceptance criteria) [V]: 109 records in register order; `adr_id` and display name identical to the register for every record; no duplicate `adr_id`; every non-null claim cites an `http(s)` evidence URL, while values recorded as `UNKNOWN` are not claims; no record has an identity binding or SAME_AS; every endpoint test and integration test is `NOT RUN`. The build script ran the check before writing, and an independent read of the written file repeated it. Both passed.

| Fact (kept separate) | Count of 109 |
|---|---|
| IDENTITY VERIFIED | 0 (owner verification pending; no identity binding) |
| Identity evidence: candidate resource with at least one cited anchor | 90 |
| Identity evidence: ambiguous (G1-04, G1-16, G1-20, G2-03, G2-20, G2-24, G2-25, G3-01, G3-13, G3-14, G4-03, G4-04, G6-05, G6-09, G6-21, G6-22) | 16 |
| Identity evidence: not established (G1-06, G1-12, G6-01; every page failed to load) | 3 |
| ACCESS DOCUMENTED: machine-readable or bulk channel cited (API, OAI-PMH, RSS, bulk or static download) | 36 |
| of which an API or OAI-PMH is cited (G1-14, G2-01, G2-02, G2-03, G2-05, G2-08, G2-11, G2-15, G2-21, G4-03, G5-01, G5-04, G6-02, G6-20) | 14 |
| ACCESS DOCUMENTED: web search or content access only | 41 |
| No access method documented | 32 |
| Seats with at least one page that failed to load | 32 |
| ENDPOINT TESTED | 0 (`NOT RUN`: probes prohibited by GB-IC-01) |
| INTEGRATION VERIFIED | 0 (`NOT RUN`: no adapter exists) |
| Proposed `classification_status` | `UNCLASSIFIED` for all 109 |

**Proposed classification.** Every record proposes `UNCLASSIFIED`, with `integration_nature` and `integration_boundary` left null. No seat has an identity binding, no endpoint was tested, and under D-10.8.2 classification may not rest on a name or on a website alone. The proposals are documentary, not persisted, and pending owner verification. Every record carries a seat-specific blocker and a next action.

**Notable findings** [V, from the cited pages]: Elsevier-published journals in G2 document only an entitlement-gated, platform-level API. G2-13's site showed injected spam content. Several seats resolve to a tool, an institution, a course page, or a non-literature data resource rather than a literature source (for example G5-03, G6-22, and several G3 entries). For G3-01, the review found that the ISSN Portal record for 3058-0382 is consistent with the governance pin (`2026-09-30-G3-01-APIS-identity-governance-pin.md`). It still marked the seat as ambiguous because other resources share the name "APIS". The review does not override the pin.

**Reconciliation of "110 source entries"** [V]. No repository document records a count of 110 sources. In this ADR, "110" appears only as an assertion count in IC test results (§8.15.7 and §8.15.10). The current membership is 109 (D-09). The removed seat G3-15 (D-09 item 5) would account for a historical count of 110, but no record states this, so it remains an inference [I]. No count was changed.

**Not done in this scope:** IC persistence, identity binding, Capability facts, the Runtime Source Registry, adapters, endpoint tests, and changes to the source matrix. Updating the matrix from this artifact (P1-EXIT-10) needs its own authorization.

### 8.30.6 — D-08 archival

Archival was authorized (§8.30.1). Producing the files verbatim required a script that reads the conversation record outside the repository. The user skipped the approval request for that script on 2026-10-14, so it was not run, and copying the 36 prompts and their reports by hand would not guarantee verbatim text. **No archive file was created.** D-08 remains unmet for the prompts and reports of 2026-10-01 to 2026-10-14. Nothing in this section implies that they were archived.

### 8.30.7 — Status matrix

| Item | Status on 2026-10-14 |
|---|---|
| §8.64.7 | PROPOSED — NOT CONFIRMED (§8.30.1) |
| P1-EXIT-01 | BLOCKED: (a) DEFERRED, (c) BLOCKED, (e) OPEN; (b), (d), (f) DECIDED |
| Gate A | Completed for the IC inventory scope only (GA-IC-01); NOT COMPLETED for every other scope |
| Gate B | Authorized for the IC inventory scope only (GB-IC-01); NOT AUTHORIZED for every other scope |
| Runtime Source Registry, Capability integration, adapters | NOT AUTHORIZED; not started |
| IC evidence review (GB-IC-01) | EXECUTED: 109 documentary records, all proposed `UNCLASSIFIED`; IDENTITY VERIFIED 0, ENDPOINT TESTED 0, INTEGRATION VERIFIED 0; owner verification pending (§8.30.5.1) |
| D-08 for 2026-10-01 to 2026-10-14 | NOT MET (§8.30.6) |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.31 — Owner decisions of 2026-10-14 (second set); FE-1 fix; D-08 archive; isolated test runs

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 (about 15:00 UTC+3), during execution of the user's master engineering execution prompt of 2026-10-14 14:57, the executor presented a structured decision request and the user answered it [P]. The design options were taken from this ADR (§8.26.6, §8.63.8, §8.64.6, §8.64.7); the execution approvals were drafted by the executor [I]. The text of each chosen option is quoted below. The prompt is archived under D-08 (§8.31.4); the decision request and answers are conversation records [M]. Earlier sections are unchanged. Where this section changes a state recorded in §8.30, this section supersedes it (D-08). Labels as in §8.64.

### 8.31.1 — Decisions

| Item | Decision | Chosen option (quoted) | Effect |
|---|---|---|---|
| P1-EXIT-01 (a) CapVer ownership and enforcement placement | **DECIDED (design only)**; supersedes "Keep DEFERRED" (§8.30.1) | "Adopt the §8.26.6 recommendation: the architecture owner (you) owns CapVer; enforcement sits above the repository, plus a defensive write check. Design decision only, no implementation" | The user, as architecture owner, owns CapVer. Enforcement is placed above the repository, with a defensive check on the write path. The concrete enforcement design is not specified and needs Gate A and Gate B for its scope. Nothing is implemented, no CapVer assessment exists, and no Capability state is written or VERIFIED |
| P1-EXIT-01 (c) `T10+T8+T2` | **Option B SELECTED; question NOT CLOSED**; supersedes "Keep BLOCKED" (§8.30.1) | "Select Option B (Builder keeps query construction; Projection emits constraint metadata). Still requires a tracked definition and Gate A/B" | §8.64.6 condition 2 is met for Option B. Conditions 1 (tracked definitions of T10, T8, and T2), 3 (definition of the constraint metadata that Projection emits and the Builder consumes, consistent with D-10.9.5–6 and T8), and 5 (decision within Gate A) are not met. Option A stays NOT SELECTED; Option C stays NOT SELECTED — NOT APPROVED. D-04, D-10.9.2, D-02, D-10.9.1, D-10.9.3, and D-10.9.5–6 remain in force. No implementation is authorized |
| P1-EXIT-01 (e) IC, Capability, registry, and activation order | **DECIDED: §8.63.8**; supersedes "Keep OPEN" (§8.30.1) | "Confirm the 7-step per-seat order of §8.27.8 (IC → identity → Capability/CapVer → Onboarding Contract → registry → activation gate → verification)" | §8.63.8 is the per-seat order; §8.26.6 item 10 is decided by it. The order permits no step to be executed. The staging and operator checks in §8.63.10 condition 7 remain open |
| Confirmation of §8.64.7 (AC-10) | **NOT CONFIRMED** (retained) | "Keep NOT CONFIRMED (earlier lists stay applicable alongside it)" | Unchanged from §8.30.1 |
| FE-1 (CI type error) | **Authorized and executed**; supersedes the FE-1 part of "None in this task" (§8.30.1) | "Authorize a minimal type fix plus a targeted test, then run tsc, lint and vitest (no commit)" | §8.31.3 |
| D-08 archive script | **Authorized and executed** | "Approve running the script (files are created only if absent; no commit)" | §8.31.4 |
| Local test environment | **Authorized**; supersedes "No; record backend and mobile tests as NOT RUN" (§8.30.1) | "Authorize isolated Docker test runs as in §8.25.9 (no shared or real database, no Compose PG on 5432)" | §8.31.5 |
| Versioned source matrix | **Authorized; not produced** | "Authorize a versioned matrix (new file, original preserved) that cites the IC evidence-review states per seat" | §8.31.6 |

Unchanged from §8.30.1: (b) DECIDED; (d) Option 1; (f) DECIDED subject to staging validation; Gate A and Gate B exist for the IC inventory scope only. Same-key replay (§8.26.6 item 8) is not decided and the fail-closed `23505` behaviour is unchanged. SEC-1, MOB-1, SEC-2, SEC-3, and the backend identity defects remain outside this execution.

### 8.31.2 — P1-EXIT-01 after these decisions

(a) DECIDED (design only); (b) DECIDED; (c) Option B SELECTED, NOT CLOSED; (d) DECIDED; (e) DECIDED; (f) DECIDED subject to staging validation. **P1-EXIT-01: BLOCKED** by (c), because §8.64.7 refers (c) to §8.64.6 and §8.64.6 conditions 1, 3, and 5 are unmet. No item of P1-EXIT-01 is DEFERRED any longer.

### 8.31.3 — FE-1: type fix

**Defect** [V]: `tsc` error TS2345 at `frontend/src/public/FieldCropCanonicalAnswerView.tsx(28,53)`. A crop profile whose `message` is `string | null` was passed to `toScientificUserPresentation`, whose parameter type was `ResearchAgentQueryResponse` (`message?: string`). The call was introduced by commit `994a400`.

**Change** (uncommitted):
- `frontend/src/public/scientificUserPresentation.ts`: the parameter type is narrowed to the exported type `ScientificUserPresentationSource = Pick<ResearchAgentQueryResponse, 'user_presentation' | 'language' | 'citations' | 'answer_candidates'>`, the four fields the function reads. Runtime behaviour is unchanged.
- `frontend/src/public/fieldCropCanonicalAnswer.test.ts`: one test maps a crop profile with `message: null` to the user presentation.

**Results** on 2026-10-14, in `frontend/` [V]: `npx tsc -b` exit 0; `npx oxlint` exit 0, with no finding on the two changed files (warnings elsewhere are pre-existing); `npx vitest run` exit 0, 48 files and 362 tests passed. A production build (`vite build`) succeeded with its output directed to a temporary directory outside the repository, so that the ignored `frontend/dist` was not overwritten. Git status was identical before and after the checks.

### 8.31.4 — D-08 archive

**Location:** `docs/architecture/science-source-expansion/2026-10-14-D-08-archive-2026-10-01-to-2026-10-14/` (uncommitted) [V]. It holds 436 files: 218 `-prompt.md`, 217 `-report.md`, and `MANIFEST.md`.

**Method.** A script outside the repository read the Cursor conversation record of this project and wrote each owner prompt and each executor final report verbatim into a new file. It created files only if absent. Each file starts with a comment header stating the archive date, the authority (this section), the source line, the message time (UTC+3), and the redaction count. Owner prompts in the window: 233. Identical re-sent prompts were merged (218 archived), and 15 system-generated messages were excluded; the manifest records both.

**Redaction.** Two credential-like values (`password:` entries) in each of three reports (2026-10-09 22:20, 22:39, and 23:05) are replaced by `[REDACTED]`. Nothing else was altered.

**Gaps** [M]: (1) the executor's final report for the prompt of 2026-10-14 14:57 did not exist when the archive was written and is not archived; (2) the decision request and answers of §8.31.1 are conversation records outside the window's prompt files; (3) prompts and reports of this phase before 2026-10-01 were not assessed: `science-source-expansion/` holds archive files dated 2026-09-29 only, and whether D-08 requires archiving earlier prompts is not decided here. §8.30.6 ("No archive file was created") was correct when written. For 2026-10-01 to 2026-10-14, it is superseded by this section, subject to gap (1).

### 8.31.5 — Isolated test runs

All runs used local Docker containers without network access (`--network none`), the image `wsa-enterprise-backend-test:latest`, and the existing `wsa-enterprise_backend_vendor` volume mounted read-only. No shared or real database was used, and the Compose PostgreSQL on port 5432 was not contacted. Logs are kept outside the repository.

| Run | Command (abridged) | Result [V] |
|---|---|---|
| SQLite Unit, working tree | `phpunit --testsuite Unit` (`backend` mounted read-only, `memory_limit=2G`) | Exit 2: 1218 tests, 16 errors, 14 failures. One failure (`Adr023109MembershipRegisterContractTest`) was environmental, because `docs` was not mounted; with `docs` mounted it passes (1 test, 5243 assertions) |
| SQLite Unit, HEAD | Same, on a `git archive` snapshot of HEAD outside the repository | Exit 2: 1205 tests, 16 errors, 18 failures. No failure occurs on the working tree that does not also occur at HEAD |
| SQLite Feature, working tree and HEAD, identical environment | `phpunit --testsuite Feature`; source and `docs` read-only; in-memory `storage` and `bootstrap/cache`; the same throwaway `APP_KEY` | Exit 1 for both. Working tree: 950 tests, 79 failures, 12 skipped. HEAD: 880 tests, 79 failures, 12 skipped. 52 failing tests are common to both. 11 fail only on the working tree, all in the scientific-research answer and search path (`ScientificEvidenceAnswerPipelineContractTest` 4, `ScientificResearchSearchFlowTest` 5, `SemanticScholarProviderIntegrationTest` 1, `SemanticTargetAndSupportedValueContractTest` 1). 18 fail only at HEAD |
| PostgreSQL (guarded IC suite) | `phpunit -c phpunit.pgsql.xml` against a disposable `postgres:16-alpine` container (no network, in-memory data directory, database `ic_persistence_test`, random password not recorded); the PHP container shared only that container's loopback; the container was removed afterwards | **Exit 0: 121 tests, 840 assertions, OK** |

**Interpretation.** No failure is in the IC, identity, Capability, or membership tests. The backend failures exist at HEAD or come from earlier uncommitted changes to CSQ, QUS, and backend tests. None comes from a change made in this execution, which changed no backend file. They are not fixed here: fixing them is outside the authorized scope, and tests were not altered to suppress failures. The PostgreSQL result is a new isolated run on current evidence for P1-EXIT-04; it does not run the concurrency test of a single CURRENT Cap fact (§8.23.8), because no Capability persistence exists. Mobile tests were not run (Flutter is not installed).

**Side effect** [V]: starting Docker Desktop (approved by the user on 2026-10-14) also started the existing Compose containers `wsa-enterprise-backend-1`, `wsa-enterprise-postgres-1` (port 5432), and `wsa-enterprise-redis-1` through their restart policy. The executor did not connect to them, stop them, or change them.

### 8.31.6 — Versioned source matrix

The matrix was authorized (§8.31.1). The executor prepared a generator script outside the repository that would write a new versioned file citing the IC evidence-review state of each seat. Running it required approval, and the user skipped that approval request on 2026-10-14. The script was not run. **No new matrix exists**, and `2026-10-12-PHASE-1-SOURCE-MATRIX.md` is unchanged. P1-EXIT-10 stays BLOCKED, and its final update still depends on P1-EXIT-06 to P1-EXIT-08.

### 8.31.7 — Second review of ambiguous seats

A second review of the 16 seats with ambiguous identity evidence, the 3 seats whose identity was not established, and the G2-13 injected-content observation was started on 2026-10-14. It produced no results during this execution. The states recorded in §8.30.5.1 and in the v1 artifact stand unchanged. The artifact was not modified, and no compromise of any site is asserted: G2-13 remains an observation of injected spam content on a fetched page.

### 8.31.8 — Status matrix

| Item | Status on 2026-10-14 |
|---|---|
| §8.64.7 | PROPOSED — NOT CONFIRMED |
| P1-EXIT-01 | BLOCKED by (c): Option B selected, §8.64.6 conditions 1, 3, and 5 unmet; (a), (b), (d), and (e) DECIDED; (f) DECIDED subject to staging validation |
| P1-EXIT-02, P1-EXIT-03 | BLOCKED: Gate A and Gate B exist for the IC inventory scope only |
| P1-EXIT-04 | PASS (qualified; isolated PostgreSQL suite rerun on 2026-10-14: 121 tests OK) |
| P1-EXIT-05 | BLOCKED: no staging evidence (§8.26.5); operator runs of §8.25.7 not done |
| P1-EXIT-06, P1-EXIT-08 | BLOCKED: not authorized, not started |
| P1-EXIT-07 | BLOCKED: 109 documentary records, all proposed `UNCLASSIFIED`, owner verification pending; IDENTITY VERIFIED 0, ENDPOINT TESTED 0, INTEGRATION VERIFIED 0 |
| P1-EXIT-09 | NOT RUN for implemented scopes (none); the regression results of §8.31.5 are recorded |
| P1-EXIT-10 | BLOCKED (§8.31.6) |
| P1-EXIT-11 | NOT RUN |
| FE-1 | FIXED in the working tree (uncommitted) |
| D-08 for 2026-10-01 to 2026-10-14 | MET except for the gaps of §8.31.4 (uncommitted) |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.32 — Phase 1 blocker resolution of 2026-10-14: `T10+T8+T2` transcription, versioned source matrix, IC re-review, D-08 supplement, test investigation

**Status:** OWNER AUTHORIZATION RECORD AND EXECUTION RECORD. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 (about 16:45 UTC+3), during execution of the user's prompt of 2026-10-14 16:29 ("Phase 1 Blocker Resolution and Safe Continuation"), the executor presented a structured request and the user answered it [P]. The options were drafted by the executor [I] and are quoted below. The 16:29 prompt is archived under D-08 (§8.32.6). Earlier sections are unchanged. No owner decision of §8.30 or §8.31 is changed. Labels as in §8.64.

### 8.32.1 — Authorizations

| Item | Answer | Chosen option (quoted) | Outcome |
|---|---|---|---|
| D-08 supplemental archive | Authorized | "Yes, create the supplemental archive (inline, visible PowerShell; no temp script)" | §8.32.6 |
| Versioned source matrix | Authorized | "Create the new versioned file 2026-10-14-PHASE-1-SOURCE-MATRIX.md using the same logic as an inline, visible command (create only if absent; validated against the register)" | §8.32.4 |
| `T10+T8+T2` transcription | Authorized | "Yes, append the cited transcription as a proposal" | §8.32.3 |
| IC re-review fetches | Authorized | "Yes, authorize these fetches" (up to three public-page fetches per seat for AgroPortal, FlowerBase, AgroSpace, and the MDPI Aquaculture Journal; no machine endpoints; findings in this ADR only) | §8.32.5 |

Before these answers, the user skipped two approval requests for running the matrix generator as a temporary script (2026-10-14 at about 15:19, and again during this execution). The matrix was produced only after the authorization above, with the generator logic written out in the command.

### 8.32.2 — Verification of completed work

On 2026-10-14, before any change in this execution [V]: branch `phase-18-m18-ai-marketing-communications`, HEAD `f202531`, nothing staged, 8 worktrees, and 92 ignored entries, all unchanged. SHA-256 prefixes: this ADR `1B83FD08613D51FD` (after §8.31); `backend/composer.lock` `D6EE17A75BF1DA5B`; IC-ER v1 `877C1604CCDE7D02`; source matrix of 2026-10-12 `587801A702D13ADD`; membership register `783035186579AA55`; `SOURCE-CAPABILITY-CONTRACT.md` `E842E757A2C06977`; the FE-1 files `43FE59335AE373C5` and `F11C14BA2600206E`; the D-08 manifest `862074044A229138`, with 436 archive files. The isolated PostgreSQL log still reads `OK (121 tests, 840 assertions)`. Nothing of §8.31 was recreated.

### 8.32.3 — `T10+T8+T2`: tracked transcription (PROPOSED — NOT APPROVED)

**Purpose.** §8.64.6 condition 1 requires T10, T8, and T2 to be defined in tracked design text, or a tracked definition to replace them. This subsection transcribes the only existing definitions verbatim so that the owner can accept, amend, or replace them. **It is not a decision.** It does not define the constraint metadata (condition 3), does not record a Gate A (condition 5), and authorizes nothing. D-04, D-10.9.2, D-02, D-10.9.1, D-10.9.3, and D-10.9.5–6 remain in force.

**Sources** [V]:
- `backend/e2e-tmp/_path_model_design_extract.md` (untracked), titled "ADR-023 — Path Model Design Specification", with "Inputs frozen: … CGHIA T10+T8+T2". Its baseline HEAD is `505f3e6`.
- `backend/e2e-tmp/_projection_c9_design_extract.md` (untracked), titled "ADR-023 — Projection + C9 Scientific Fidelity Design Specification". Same baseline.
- The prompt of 2026-09-30 22:37 ("Path Model Design") and the reports for the prompts of 2026-09-30 22:37 and 22:42 are now archived verbatim in the D-08 supplement (§8.32.6) and contain the same acceptance lines. Each extract calls itself "ACCEPTED WITH NON-BLOCKING OPEN DECISIONS". Under §8.26.3, that acceptance is not architectural authority, because it was never recorded in this ADR.

**Verbatim definitions** (Path Model extract, §10 "CGHIA Mapping", lines 149–155):

| Topology | Path Model role |
|---|---|
| T10 | Cap-gated multi-layer primary path selection among P01–P17 |
| T8 | L1→L2 EXTERNAL→L3 P18 fallback layers; non-upgrade |
| T2 | P17 family adapter + **per-source Projection** (Projection owns query) |

**Verbatim supporting text** (same extract):
- §11 "Fallback Model (B5 / C4)", lines 162–167 (the three layer lines joined): "L1 Cap-gated primary (source-native/protocol/bulk/web as Cap allows) → L2 EXTERNAL aggregator (P11–P13 / P16 agg leg) → L3 Manual P18". Invariant: "`final_fidelity ≤ evidence-supported fidelity`. No silent aggregator→native, metadata→FT, related→requested species, pathogen→disease, etc."
- §9, P17 row, line 142: "P17 Protocol-family + Projection | family Cap + T2 | CGHIA default shape | No* | **YES required** | No".
- §6, line 75: "Verified automation requires: **ELIGIBLE + SELECTED** or **CONDITIONAL + CONDITIONALLY_SELECTED** (PARTIAL limits explicit)."

**Conflicts with Option B** (§8.64.6; the user selected B in §8.31.1):
1. T2 says "Projection owns query" (Path Model extract, line 155). Under Option B, the Builder keeps query construction, as D-04 and D-10.9.2 require.
2. The Projection extract states (line 12): "Projection is the sole authority for **source-native representation** of an immutable CSQ, under Cap + Path constraints". It lists as a Projection responsibility (line 52) "source-native query representation (`source_query` / structured params envelope)", and in its data flow (lines 109–110) "→ Projection Envelope" followed by "→ Adapter executes source_query as transport". Under Option B, Projection would emit constraint metadata and would not produce the executed query.
3. The same extract also preserves the Builder. Line 65 states "Does **not** own: CSQ creation · Builder scholarly lexical authority · …". Line 300 states "Builder | KEEP lexical authority", and line 302 states that the Compiler "must not replace Builder HTTP query today". The extract therefore is not internally aligned with either Option B or Option C.

**Candidate inputs for the constraint metadata** [I]. The Projection extract's envelope (§6, lines 69–89) lists fields marked PROPOSED, among them `mapped_facets`, `expanded_terms`, `unsupported_facets`, `omitted_facets`, `unresolved_facets`, `source_native_identifiers`, `retrieval_method`, `fidelity_class`, and `projection_warnings`, together with identities such as `path_decision_identity`. These are recorded only as material for the owner. **No subset is selected here.** Which fields form the constraint metadata, what the Builder must do with each, and how T8's non-upgrade rule and D-10.9.5–6 are enforced are decisions for the owner.

**Decisions still required to close P1-EXIT-01 (c)** (owner-controlled):
1. Condition 1: accept this transcription, with or without amendment, as the tracked definition of T10 and T8, and replace T2's "Projection owns query" with the Option B wording. Alternatively, supply a different tracked definition.
2. Condition 3: define the constraint metadata that Projection emits and the Builder consumes. The definition must keep query construction in the Builder and be consistent with D-10.9.5–6 and with T8's non-upgrade rule.
3. Condition 5: record the decision within a Gate A record (§8.64.4) for this scope.

### 8.32.4 — Versioned source matrix

**File:** `docs/architecture/science-source-expansion/2026-10-14-PHASE-1-SOURCE-MATRIX.md` (new, uncommitted; SHA-256 `3e88b1fdcaa59513d5b9412e5968808b964bde8da969c0ff7e61a0d36c364111`; 163 lines, CRLF) [V].

**Method.** The logic of the generator prepared earlier (outside the repository) was inspected in full and then run as an inline, visible command. Before writing, the command checked the following:
- 109 register seats and 109 IC-ER records.
- Every `adr_id` present, in register order, with display names identical to the register.
- Every identity and access state is a known value.
- Every record has `identity_verified` false, `same_as` `NONE`, and `identity_binding` `NONE`.
- The output file was absent. The write used the create-only flag.

One check failed on the first run because it misread the value `NONE`; nothing was written. The check was corrected, and the second run passed and wrote the file.

**Content.** One row per seat, generated from the register. The access-evidence column cites the IC-ER identity state (`CAND` 90, `AMB` 16, `NEST` 3) and access state (`DOC-MB` 36, `DOC-WEB` 41, `NONE-DOC` 32). IDENTITY VERIFIED, ENDPOINT TESTED, persisted binding, registry entry, Capability fact, adapter, and VERIFIED/ACTIVE/ACCEPTED are all 0. Blockers B1–B5 apply to all 109 seats. No seat is promoted. The matrix of 2026-10-12 is unchanged (`587801A702D13ADD`).

### 8.32.5 — IC re-review and MAFIS

Every fetch was of a public, human-readable page; no machine endpoint was requested [V]. The IC-ER v1 artifact was not modified.

| Seat | Fetches (2026-10-14) | Result | State |
|---|---|---|---|
| G1-06 AgroPortal | `agroportal.lirmm.fr/` (timeout), `/about` (timeout), `doc.agroportal.lirmm.fr/` (500) | No page loaded | NOT ESTABLISHED (unchanged) |
| G1-12 / G6-01 FlowerBase | `…/flowerbase/static/index.html` (502 page), `…/flowerbase/` (502 page), Plant Communications article (timeout) | No content | NOT ESTABLISHED (unchanged) |
| G1-04 AgroSpace | `aspace.agrif.bg.ac.rs` over http and https (403 both); Faculty of Agriculture repository page (loaded) | Repeats the existing anchor: "AgroSpace is the institutional digital repository of the University of Belgrade, Faculty of Agriculture." The page also states: "You may use the external application Authors, Projects, Publications (APP) to browse and search authors and funding information. APP also enables metadata export". The platform is not named | AMBIGUOUS (unchanged; the name clash with unrelated platforms is not resolved by this page) |
| G2-20 Aquaculture Journal | `mdpi.com/journal/aquacj`, `/about`, `/instructions` (403 all) | No content | AMBIGUOUS (unchanged) |

The other 12 ambiguous seats were not re-fetched, because their ambiguity is about which resource the seat name refers to. Examples: "CIRAD Portals" (plural), NYBG (an institution with several data resources), and "PG Syllabus" (course page versus entrance-exam syllabus). Web evidence cannot settle what the register intended. Resolving them requires the owner to state, for each seat, which resource the seat name refers to. No SAME_AS was created and no mapping was forced.

**MAFIS (G2-13), security observation** [V]. On 2026-10-14 the page `https://ojs.inidep.edu.ar/index.php/mafis/about` still contains injected third-party text ("Warung Top Up: Tempat Andalan Buat Isi Diamond Game Favorit", promoting game top-ups). That text also fills the "Licencing" section. The page still carries the publisher's copyright statement ("Authors of articles published in Marine and Fishery Sciences retain copyright on their articles …"). The fetch tool may return cached content. This is an observation of injected content on a public page. It is not proof of how the content got there, and no compromise is asserted. The licence statement of the journal cannot be read from that page, so the licence remains unresolved. The seat is not activated, and nothing may activate it while its licence is unresolved.

### 8.32.6 — D-08 supplemental archive

**Folder:** `docs/architecture/science-source-expansion/2026-10-14-D-08-archive-supplement-01/` (new, uncommitted). It holds 95 files (1,949,037 bytes): 94 records and `MANIFEST.md` [V]. The archive `2026-10-14-D-08-archive-2026-10-01-to-2026-10-14/` is unchanged (436 files; manifest `862074044A229138`).

**Missing period, identified** [V]. A read-only scan of the project's conversation transcripts found the first Scientific Source Expansion prompt at 2026-09-29 23:40. The first prompt naming ADR-023 is at 2026-09-30 00:00. No transcript before 2026-09-29 mentions either. Before this supplement, that period was archived only as ten formatted records:
- GO-0-A prompt and report;
- GO-0 STEP-1 report;
- GO-1 prompt and report;
- GO-1b prompt and report;
- GO-1c prompt and report;
- the G3-01 governance pin.

**Contents:**
- **Group A:** 83 records (46 owner prompts from 2026-09-29 23:40 to 2026-09-30, each with its report). Items already present as formatted records were not duplicated.
- **Group B:** the final report for the prompt of 2026-10-14 14:57.
- **Group C:** the prompt of 2026-10-14 16:29.
- **Group D:** 9 full reports (see the defect below).

No credential-like value was found, so nothing was redacted. Every file header states its source line and its timestamp as recorded in the conversation.

**Defect found in the first archive** [V]. The first archive selected as "report" the last long executor message before the next owner prompt. When a system notification ("Briefly inform the user about the task result") arrived in between, the brief reply to that notification was archived instead of the final report. This affects the entries for:
- 2026-10-01 16:19 and 18:18;
- 2026-10-02 23:54;
- 2026-10-09 13:54, 15:15, 15:28, and 17:34;
- 2026-10-13 00:44 and 01:08.

For example, the 2026-10-01 16:19 entry holds a 314-character note instead of the 5,544-character report. The first archive was not changed. Group D adds the full reports as `-report-full.md` files. The supplement uses the corrected rule: the last executor message before the next owner prompt or the first system notification.

**Remaining gaps** [M]:
1. The report for the 16:29 prompt did not exist when the supplement was written.
2. Whether the ten pre-existing formatted records are verbatim was not verified.
3. Decision requests and answers given through structured questions are conversation records and are not archived as prompts.

### 8.32.7 — Test investigation (read-only; no new test runs)

The results of §8.31.5 stand and are not rerun. The 11 Feature tests that fail only on the working tree fall into two groups [V]:
- **Ten are tests that do not exist at HEAD:** four added to the modified `ScientificEvidenceAnswerPipelineContractTest.php`, five in the untracked `ScientificResearchSearchFlowTest.php`, and one in the untracked `SemanticTargetAndSupportedValueContractTest.php`. They are unfinished earlier work, not regressions.
- **One is a regression:** `SemanticScholarProviderIntegrationTest::test_selector_uses_semantic_scholar_not_consensus`. The test file is tracked and unchanged, and the test passes at HEAD. On the working tree the selected sources are `fao_stat, openalex, crossref, semantic_scholar` instead of `openalex, crossref, semantic_scholar`. The only working-tree changes to backend application code are the earlier uncommitted edits to `QueryUnderstandingService.php` (+105 lines) and `CanonicalScientificQuestion.php`; no untracked application code exists. The regression is therefore attributable to those edits [I]. It was not fixed: those edits are unrelated pre-existing work.

The 52 failures shared by HEAD and the working tree are concentrated in the scientific-research pipeline:
- `ScientificUpstreamRetrievalFixTest`: 13 failures; search variants contain Arabic terms or lack required terms.
- `ScientificResearchLanguageContractTest`: 7 failures; answer language.
- `ScientificResearchAnswerAccuracyTest`: 6 failures.
- `Phase5UnitB3AnswerAccuracyGatesTest`: 5 failures.
- Several entity-family and relevance tests.

Two (`WsaEnterpriseStage10ProductionTest`: "Unable to locate repository root for compose/CI fixtures") depend on the test environment, because only `backend` was mounted. The rest are single failures in unrelated feature areas, such as billing, the trusted proxy, and the queue foundation. All of them exist in committed code. They are outside the Phase 1 scope, but they mean the regression baseline that P1-EXIT-09 will need ("regression of the existing five-source path, CSQ identity, and Home/Crop isolation") is not green today.

Flutter tests: NOT RUN (Flutter is not installed). No new database or Docker run was made in this execution. The Compose containers were not contacted, stopped, or changed.

### 8.32.8 — Exit evaluation on 2026-10-14

The earlier criteria stay applicable alongside §8.64.7, because §8.64.7 is NOT CONFIRMED.

| Item | Status |
|---|---|
| P1-EXIT-01 (a), (b), (d), (e) | Decided (§8.30.1, §8.31.1) |
| P1-EXIT-01 (f) | Decided, subject to staging validation |
| P1-EXIT-01 (c) | BLOCKED. Condition 1 is PREPARED (§8.32.3) and awaits owner acceptance and a commit. Conditions 3 and 5 are not met |
| P1-EXIT-02, P1-EXIT-03 | BLOCKED: Gate A and Gate B exist for the IC inventory scope only |
| P1-EXIT-04 | PASS (qualified) |
| P1-EXIT-05 | BLOCKED: no staging evidence, no operator record |
| P1-EXIT-06, P1-EXIT-08 | BLOCKED: not authorized |
| P1-EXIT-07 | BLOCKED: 109 documentary records, all `UNCLASSIFIED`; no identity verified |
| P1-EXIT-09 | NOT RUN for implemented scopes (none). The regression baseline is not green (§8.32.7) |
| P1-EXIT-10 | BLOCKED. The matrix now cites an artifact for every seat (§8.32.4), but its final update depends on P1-EXIT-06 to P1-EXIT-08 |
| P1-EXIT-11 | NOT RUN |
| §8.26.6 | Items 3, 6, and 10 decided; item 4 executed for the documentary scope; items 1, 2, 5, 7, and 11 open; item 8 not an exit item and undecided; item 9 unverified (Q-12) |
| §8.63.10 | Conditions 3 and 6 met as decisions; condition 4 met for the documentary scope; conditions 1, 2, 5, 7, 8, and 9 open; condition 10 not run |
| §8.25.10 | Items 1 and 2 met; items 3 and 4 open |
| D-08 | Met from 2026-09-29 23:40 to the 16:29 prompt of 2026-10-14, except the gaps of §8.32.6 |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.33 — Owner decisions of 2026-10-14 (third set): Builder/Projection boundary, constraint contract v1, Gate A and Gate B for step B-1, B-1 execution

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 at 18:22 UTC+3 the user issued the prompt "WSA-Enterprise — Phase 1 / Strictly Authorized Execution: ADR-023 §8.33 + B-1 Only" [P]. It states the binding decisions quoted in §8.33.1 and authorizes exactly two changes: this section, and step B-1 of the resolution package `docs/architecture/science-source-expansion/2026-10-14-PHASE-1-RESOLUTION-PACKAGE-P1-EXIT-01c.md` (uncommitted; SHA-256 `880e34b97201bbf89906a9ab9e04ecbd0a901404c30e42fadc76d37357d0325d`). The prompt text as received ends mid-sentence in its test rules; every rule received is applied. During execution the executor presented one structured decision request (DR-4), and the user answered it (§8.33.1). The prompt and the answer are conversation records and are not archived in the repository [M]; D-08 archival was not part of this authorization. Earlier sections are unchanged. No decision of §8.30, §8.31, or §8.32 is changed. Labels as in §8.64.

### 8.33.1 — Decisions

| Item | Decision (owner text, translated and condensed) | Effect |
|---|---|---|
| Builder / Projection boundary (resolution package DR-1) | `ScientificSearchQueryBuilder` is the final authority for building the textual query, including lexical expansions. Projection does not build or rewrite the query. Projection records facet constraints, representation information, and expansions as accounting and disclosure outputs. No two final authorities for the same query | Confirms D-04 and D-10.9.2 without amending them. [INFERENCE] For query construction it replaces the phrase "Projection owns query" in the untracked T2 definition (§8.32.3); this reading of T2 is the executor's, not owner text, and its confirmation is [UNRESOLVED] (§8.33.8). Consistent with the approved IU-04 §16 and the OD-06 authority matrix ("Source query (lexical) … **Builder**"; "No dual final authority on any row") |
| `expanded_terms` | Production of expansions stays with the Builder under the approved contract. Projection records expansions and does not generate them. Existing expansion rules are not changed in B-1 | `expanded_terms` stays a bucket of the existing `ProjectionEnvelope` facet accounting. It is not part of the constraint contract |
| Mapping of Capability state to constraint (DR-2) | `VERIFIED` → `CONSTRAINABLE`; `UNAVAILABLE`, `NOT_APPLICABLE` → `NOT_CONSTRAINABLE`; `UNVERIFIED`, `MISSING` → `UNKNOWN`; `PARTIAL` → `UNKNOWN` | §8.33.2. Consistent with the invariant "Missing Cap = UNVERIFIED": both map to `UNKNOWN`. Refined by the owner on 2026-10-14 at 18:49 (M-1, §8.33.8): `VERIFIED` maps to `CONSTRAINABLE` only when evidence freshness is established as `CURRENT` |
| `NOT_CONSTRAINABLE` facet used only as text (DR-3) | It must not be presented as structurally applied. The classification is `APPROXIMATED` with a stated reason. No query-execution logic for this behaviour in B-1 | Recorded as a rule for the facet accounting of a later step. B-1 contains no code for it |
| D-10.15 step 1 for B-1 (DR-4) | Chosen option (quoted): "Not applicable to B-1 because it binds no seat; D-10.15 step 1 stays in force for B-2 and any seat binding, and its general confirmation (§8.28.4) stays OPEN" | D-10.15 is not amended. The inference of §8.64.4 (that §8.11 is the gate of D-10.15 step 1) stays OPEN |
| Gate A and Gate B for B-1 (DR-5) | Record Gate A and Gate B for B-1 in this section | §8.33.3 and §8.33.4 |
| Architectural limits | Do not change D-04, D-10.9.2, or any other approved decision. No database schema or migration. No Capability writes; no CapVer change. No SAME_AS; no source activation. This approval is not closure of the rest of Phase 1 and does not authorize B-2 | Applied throughout |

### 8.33.2 — Constraint contract `projection_query_constraints.v1`

**Purpose.** Per-facet metadata telling the Builder whether one seat's source can constrain each facet of the canonical question in its query. Projection produces it; the Builder will consume it in a later, separately authorized step. It contains no query text, no terms, no expansions, and no fidelity class.

| Field | Type | Source | Validation |
|---|---|---|---|
| `schema_version` | string constant `projection_query_constraints.v1` | This contract | Fixed |
| `canonical_query_id` | `CanonicalQueryId` | CSQ | Non-empty (existing invariant); never minted by Projection |
| `adr_id` | `AdrMembershipId` | Membership register, via the Path decision | Non-empty (existing invariant) |
| `canonical_identity_id` | `CanonicalSourceIdentityId` or null | Identity, via the Path decision | Never inferred; null when the subject is seat-scoped |
| `capability_decision_id` | string | Capability decision (IU-02) | `ProjectionDomainContract::assertSubjectAlignment()`: same `adr_id`, same canonical identity, and the Capability decision id equals the Path decision's `capability_decision_identity` |
| `path_decision_id`, `path_id` | string, `PathId` | Path decision (IU-03) | As above; existing Path invariants keep them distinct from `adr_id` and the Capability decision |
| `facet_constraints` | list of `{facet_code, identity_critical, capability_state, constraint}` | Caller-supplied facet codes × Capability `SCIENTIFIC_FACET_QUERY_CONSTRAINABLE` records | Codes normalized (trim, lower case), non-empty, unique; sorted by `facet_code`; `identity_critical` derived from `ProjectionIdentityCriticalFacets`; `capability_state` is a Cap v2 state or `MISSING`; `constraint` derived by the mapping of §8.33.1. *Source, matching, and mapping superseded by §8.33.8 (R-1, R-2, M-1)* |
| `retrieval_method` | `ProjectionRetrievalMethod` | Caller | Defaults to `UNKNOWN`. [UNRESOLVED] The resolution package (section 3.2) names the Path decision as the source; `PathDecisionIdentity` has no such field (§8.33.8) |
| `warnings` | list of `ProjectionWarning` | Caller, then Projection | Caller warnings first, then one `QUERY_CONSTRAINT_UNKNOWN` warning for each `UNKNOWN` facet, in facet order. *Produced by `ProjectionQueryConstraints::create()`; identical warnings kept once (§8.33.8)* |

**Not in the contract:** `source_query`, `expanded_terms`, `fidelity_class`, any other facet-accounting bucket, and any source-native field name or query syntax. These stay in `ProjectionEnvelope`, which is unchanged.

**Open point.** No approved record defines how facet codes are extracted from a CSQ. In B-1 they are explicit caller input. The extraction must be defined and approved in the scope that wires the contract (B-2 or later).

**Serialization.** `toArray()` returns snake_case keys in the order of the table above. The output is deterministic for identical inputs, regardless of the order of the facet codes or of the Capability records. It passes `ProjectionDomainContract::assertProjectionPayloadClean()`. It is in-memory only: no persistence, schema, or migration.

**Choices made by the executor within these decisions** [I] (the owner may amend them; choice 3 is superseded and choice 5 is amended by §8.33.8):
1. `MISSING` is a disclosure value of `capability_state`. It is not a Cap v2 state and is never written.
2. Only `SCIENTIFIC_FACET_QUERY_CONSTRAINABLE` records are read. A `SCIENTIFIC_FACET_CONTENT_ABOUT` record never makes a facet constrainable (CAP-INV-009).
3. A Capability record whose subject differs from the Capability decision's subject is rejected. So are two `query_constrainable` records for the same facet: resolving them (for example by seat override) belongs to Capability, not Projection.
4. A facet code outside `ProjectionIdentityCriticalFacets::CODES` is accepted with `identity_critical` false.
5. The automatic `QUERY_CONSTRAINT_UNKNOWN` warning follows the producer rule of the resolution package (section 3.4) and D-10.9.6 (no silent facet loss).

### 8.33.3 — Gate A record GA-B1-01: constraint contract (step B-1)

**Classification (§8.33.8).** Recording a Gate A for B-1 is [OWNER DECISION] (DR-5). The record identifier and the content of each item below were drafted by the executor from the draft records of the resolution package (section 5) [INFERENCE] and were not separately approved by the owner.

**Scope.** The seat-independent domain contract of §8.33.2 and its factory. No seat (`adr_id`) is bound, onboarded, or activated. Nothing is wired into runtime.

| Gate A item (§8.64.4) | For GA-B1-01 |
|---|---|
| `T10+T8+T2` and its relation to D-04 and D-10.9.2 | Option B (selected in §8.31.1), with the boundary of §8.33.1 and the contract of §8.33.2. D-04 and D-10.9.2 are preserved. Option C is not adopted |
| Runtime Source Registry contract and ownership | Outside the scope |
| CapVer ownership and enforcement placement | Outside the scope: the contract only reads Capability records |
| Capability contract and population rules | Outside the scope: no population, no write |
| Separation of membership, identity, IC, Capability, registry, and activation | ACCEPTED (unchanged) |
| Ordering of IC and Capability | Outside the scope (decided in §8.31.1; not exercised) |
| GO-1 vocabulary | Outside the scope |
| Source-specific integration strategy and verified access families | Outside the scope; required for B-2 |
| Onboarding Contract implementation design | Outside the scope; D-10.15 step 1 does not apply to B-1 (§8.33.1, DR-4) and applies to B-2 |
| Deployment order and staging validation | Not needed: no schema, no runtime change |

### 8.33.4 — Gate B record GB-B1-01: step B-1

**Classification (§8.33.8).** Recording a Gate B for B-1 is [OWNER DECISION] (DR-5). The authorized and prohibited scopes restate the 18:22 prompt [OWNER DECISION]. The record identifier and the wording of the other fields were drafted by the executor [INFERENCE].

| Field (§8.64.4) | Content |
|---|---|
| Granted by | The user, recorded as architecture owner in §8.31.1 (compare §8.24.8 and P1-EXIT-03), on 2026-10-14 at 18:22 UTC+3 |
| Authorized scope | (1) New files `backend/app/Services/Agriculture/Research/Projection/ProjectionFacetConstraintState.php`, `ProjectionFacetConstraint.php`, `ProjectionQueryConstraints.php`, `ProjectionQueryConstraintsFactory.php`, and `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php`. (2) This section. No seat is in scope |
| Prohibited scope | B-2; Phase 2 and 3; CSQ/QUS, `CanonicalScientificQuestion`, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, the orchestrator, the Coexistence Boundary, the Selector, the Registry, source adapters, `AnswerComposer`, CapVer; Capability writes; identity bindings; SAME_AS; activation; schema, migrations, persistence; `backend/.env`, `phpunit.xml`, FAOSTAT settings, `composer.lock`, dependencies; other files in the repository; helper files in the repository |
| Gate A relied on | GA-B1-01 (§8.33.3) |
| Checklist items addressed | Input to P1-EXIT-01 (c) conditions 3 and 5 for this scope. It closes no P1-EXIT item |
| Acceptance criteria | The eight test groups of §8.33.5 pass in a permitted environment. No existing contract of the Projection, Capability, Path, Identity, Coexistence, or Disclosure packages changes. Git shows only the five new files and the change to this ADR |
| Tests and evidence | PHPUnit unit tests, using only an existing, isolated local means proven not to modify protected files or shared environments. No Docker, Compose, PostgreSQL, shared services, or network. Database engines: none, because nothing is persisted |
| Repository and Git operations | Create the five files (create-only) and append this section. No staging, commit, push, branch change, or history rewrite |
| Operational restrictions | No Docker, Compose, containers, databases, network, or environment changes |
| Suspension | The conditions of §8.64.4; any field whose type, source, or validation would need invented semantics; any test that needs Docker, an environment change, or a dependency change (it is recorded NOT RUN instead) |

The Gate B lifecycle questions of §8.64.4 remain OPEN.

### 8.33.5 — B-1 execution

**Before any change** [V]: repository `G:/WSA-Enterprise`; branch `phase-18-m18-ai-marketing-communications`; HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`; 1,113 untracked, 17 changed, 0 staged, 92 ignored entries (`git status --ignored=matching`; 88 in the default `traditional` mode); 8 worktrees. This matched the previous report. All five target files were absent. This ADR had SHA-256 `4db55a261e12b439b6a6a8ddbb14d9e5245a482a4494f7b8f07e05772fc780fa` (392,454 bytes, CRLF) and no §8.33.

**Files created** (create-only, CRLF, no BOM, uncommitted) [V]:

| File | SHA-256 |
|---|---|
| `…/Projection/ProjectionFacetConstraintState.php` | `0db108d63df7d0c031384e7e56fe79329f2504521e8235f8fd4ad355c99e3196` |
| `…/Projection/ProjectionFacetConstraint.php` | `684b600090e68ab138751296b8607e894716601b7f747b0fd7f3d54cdab87fd7` |
| `…/Projection/ProjectionQueryConstraints.php` | `9e6fc206fc6186461f9ecfdce243b9e311538a3ef9194d5f8e8a4302911422e4` |
| `…/Projection/ProjectionQueryConstraintsFactory.php` | `662c09674020fc7fe5b6d8538906bd0bef36f3b6bf1b4c276b0580bdd1c432ab` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `d9331468752a5e6ddd262cb1403f64dcbc579c1f140dca8783682bef6e445dba` |

No existing file other than this ADR was changed. No class outside the four new ones references the contract, so runtime behaviour is unchanged.

**Tests written** (14 test methods; 19 cases including the six mapping cases):

| Required test | Test methods |
|---|---|
| 1. Every mapping, including `PARTIAL` and `MISSING` | `test_capability_state_maps_to_constraint`, `test_mapping_is_total_over_frozen_capability_states`, `test_only_query_constrainable_dimension_is_read` |
| 2. `identity_critical` derived, not caller-chosen | `test_identity_critical_is_derived_not_caller_supplied` |
| 3. Empty values and duplicate codes rejected | `test_rejects_empty_and_duplicate_values`, `test_rejects_capability_record_of_another_subject` |
| 4. Capability and Path decision consistency | `test_carries_aligned_decision_identifiers`, `test_rejects_inconsistent_capability_and_path_decisions` |
| 5. Stable, deterministic serialization order | `test_serialization_is_sorted_and_deterministic`, `test_unknown_constraints_are_disclosed_as_warnings_in_facet_order` |
| 6. snake_case keys; `toArray()` passes `assertProjectionPayloadClean` | `test_payload_keys_are_snake_case_and_clean` |
| 7. No write to Capability or any repository | `test_does_not_write_capability_or_use_any_repository` |
| 8. Existing package contracts unchanged | `test_existing_contracts_are_unchanged`, `test_contract_is_not_wired_into_runtime`; plus the Git evidence above |

Rejection tests assert the exact exception message, not only that an exception occurs.

**Test status: NOT RUN** [V]. PHP is not installed on the host: no `php` on the PATH or in the usual install locations. `backend/vendor` does not exist on the host. The only WSL distribution is `docker-desktop`. The only isolated test means used earlier (§8.31.5) is a Docker container, which this authorization prohibits. No PHP syntax check was possible either. This is an environment limitation, not a code result. **No test is claimed to pass.** The required next step is to run `tests/Unit/Agriculture/Research/Projection` and the Capability, Path, Identity, Coexistence, and Disclosure unit suites in an environment the owner permits.

### 8.33.6 — P1-EXIT-01 (c) after these decisions

| Closure condition (§8.64.6) | Status |
|---|---|
| 1. Tracked definitions of T10, T8, and T2 | PARTIALLY MET [INFERENCE: executor assessment, not owner-confirmed]. The boundary of §8.33.1 replaces "Projection owns query" for query construction. The T10 and T8 transcriptions of §8.32.3 were neither accepted nor amended |
| 2. Explicit selection of an option | MET (§8.31.1: Option B) [OWNER DECISION] |
| 3. Constraint metadata defined, construction kept in the Builder, consistent with D-10.9.5–6 and T8 | MET FOR THE B-1 SCOPE [INFERENCE: executor assessment, not owner-confirmed] (§8.33.2). The contract carries no fidelity class and is tied to one Path decision, so it cannot raise fidelity on a fallback path. The fallback rule itself is exercised only when the contract is wired (B-2) |
| 4. Option C supersession | Not applicable |
| 5. Decision within Gate A | MET FOR THE B-1 SCOPE [INFERENCE: executor assessment, not owner-confirmed] (GA-B1-01) |

**P1-EXIT-01 (c): NOT CLOSED.** Under the 18:22 instruction it is not closed automatically. Closing it needs an explicit owner decision, after condition 1 is completed for T10 and T8.

### 8.33.7 — Status matrix

| Item | Status on 2026-10-14 |
|---|---|
| Builder/Projection boundary, `expanded_terms` authority, mapping including `PARTIAL`, `APPROXIMATED` rule | DECIDED (§8.33.1); freshness condition for `VERIFIED` DECIDED (§8.33.8, M-1) |
| D-04, D-10.9.2, D-10.15, and every other approved decision | Unchanged |
| Gate A | GA-IC-01 (§8.30.3) and GA-B1-01 (§8.33.3) only |
| Gate B | GB-IC-01 (§8.30.4) and GB-B1-01 (§8.33.4) only |
| B-1 | IMPLEMENTED, uncommitted, revised by §8.33.8; tests NOT RUN (§8.33.5, §8.33.8) |
| B-2 | NOT AUTHORIZED; needs facet extraction, D-10.15 step 1, its own Gate A and Gate B, and a seat with a verified access family |
| P1-EXIT-01 | BLOCKED: (c) NOT CLOSED; (f) still subject to staging validation |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |
### 8.33.8 — Post-review corrections (owner decisions of 18:49)

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** At 18:40 UTC+3 the user requested an independent static review of B-1 without changes. It found one blocker (M-1: `VERIFIED` mapped to `CONSTRAINABLE` without regard to evidence freshness) and three risks (R-1: the factory resolved raw Capability records itself; R-2: it lowercased Capability codes; R-3: decisions and inferences were not distinguished in this section). At 18:49 UTC+3 the prompt "B-1 Engineering Completion, Contract Integrity & Forensic Verification" decided M-1 and authorized the correction of R-1, R-2, and R-3 in this ADR and the five B-1 files only. Both prompts are conversation records, not archived in the repository [M]. The earlier text of §8.33 is kept: superseded statements are marked, not deleted.

**Tags.** This subsection and the tags added to §8.33.1-§8.33.7 use:

| Tag | Meaning | Nearest §8.64 label |
|---|---|---|
| [OWNER DECISION] | Decided by the owner in a prompt or in an answer to a decision request | [P] |
| [APPROVED ARCHITECTURE] | Approved ADR text before §8.33 | - |
| [IMPLEMENTATION FACT] | Verified in the repository or the code on 2026-10-14 | [V] |
| [INFERENCE] | Executor reading or drafting not confirmed by the owner | [I] |
| [PROPOSED] | Proposal waiting for a decision, including the resolution package | - |
| [UNRESOLVED] | Open; needs an owner decision | OPEN |

**Decisions of 18:49:**

| ID | Decision (owner text, translated and condensed) | Tag | Effect |
|---|---|---|---|
| M-1 | If the Capability state is `VERIFIED` and evidence freshness is established as `CURRENT`, the constraint may be `CONSTRAINABLE` when the other conditions hold. If the state is `VERIFIED` and freshness is not established as `CURRENT`, the constraint is `UNKNOWN` with a clear warning. Freshness that cannot be established from the actual contract counts as not established. No new field; `CURRENT` is never inferred from a timestamp, the age of a record, or any other unapproved indicator | [OWNER DECISION]; consistent with D-10.5 item 2 and §8.23.3 ("VERIFIED requires CURRENT evidence") [APPROVED ARCHITECTURE] | Refines the DR-2 mapping of §8.33.1 for `VERIFIED`. Every other state keeps its mapping |
| R-1 | Use the resolved Capability decision results instead of raw records. Do not re-implement `CapabilityRequirementEvaluator` or create a parallel resolution. Do not change the duplicate, seat/shared, or alignment rules | [OWNER DECISION] | Supersedes executor choice 3 and the factory input of §8.33.2 |
| R-2 | Keep identifier semantics. No case folding of Capability codes without an approved contract. No silent merge of identities | [OWNER DECISION] | Supersedes the code matching of the first implementation |
| R-3 | Distinguish owner decisions, approved architecture, implementation facts, inferences, proposals, and open points in §8.33, without deleting decision history | [OWNER DECISION] | The tags in §8.33.1-§8.33.7 and the table "Statements of §8.33" below |

**Implementation changes** [IMPLEMENTATION FACT]:
1. Freshness source: `CapabilityRecord::$evidenceFreshness`, which `CapabilityRequirementEvaluator` copies into each required cell outcome as `evidence_freshness_class`. A missing or invalid value, including another letter case, counts as not established. No field was added.
2. `ProjectionFacetConstraintState::fromCapability(state, freshness)`: `VERIFIED` + `CURRENT` gives `CONSTRAINABLE`; `VERIFIED` + `STALE`, `UNKNOWN`, or not established gives `UNKNOWN`; `UNAVAILABLE` and `NOT_APPLICABLE` give `NOT_CONSTRAINABLE`; `UNVERIFIED`, `PARTIAL`, and `MISSING` give `UNKNOWN`, whatever the freshness.
3. `ProjectionQueryConstraintsFactory::derive()` no longer takes Capability records. It reads `CapabilityDecisionIdentity::$perCellOutcomes`: the cells the evaluator resolved after its subject filter and its restrictive-wins rule for duplicate cells (seat override and shared). A facet matches the cell whose `dimension_key` equals `CapabilityDimension::scientificFacetQueryConstrainable(code)->key()`. A cell with `missing_record` true, and a facet without a cell, give `MISSING`. A relevant cell without a valid state or a boolean `missing_record`, an outcome without a string `dimension_key`, and conflicting cells for one facet are rejected; identical cells (a dimension listed twice in the requirement set) are accepted. Subject and decision alignment is still checked first, so freshness bypasses no check.
4. Capability codes are compared exactly. The Projection facet code keeps the existing Projection normalization (trim and lower case, as in `ProjectionFacetRecord`). A Capability cell coded `Species` therefore does not match the facet `species`, which becomes `MISSING` and `UNKNOWN`.
5. `ProjectionQueryConstraints::create()` now produces the `QUERY_CONSTRAINT_UNKNOWN` warnings, so a direct call discloses the same warnings as the factory. Identical warnings (same code and message) are kept once. The warning states the reason: `MISSING`; `VERIFIED` with freshness `STALE`, `UNKNOWN`, or not established; or the other state. The constant moved to `ProjectionQueryConstraints`. Facets are sorted by string comparison, so numeric codes stay strings. The unused `isCapabilityMissing()` was removed.
6. The serialized output keeps the same keys: ten top-level keys and four per facet. Freshness is not serialized; it appears only in the warning text.

**Consequence** [IMPLEMENTATION FACT]: The evaluator resolves only required dimensions; optional enrichments carry keys, not states. A facet can be `CONSTRAINABLE` only if the Capability decision bound to the Path decision lists the facet's `query_constrainable` dimension as required. The only runtime caller of the evaluator, `Stage3CghiaCoexistenceBoundary`, requires `access_method:api` only, so if the contract were wired there unchanged every facet would be `MISSING` and `UNKNOWN`. That is fail-closed. Whether `query_constrainable` dimensions belong in the AND set, which would change Path eligibility, is [UNRESOLVED] and belongs to B-2.

**Statements of §8.33:**

| Statement | Tag |
|---|---|
| Decision texts in column 2 of §8.33.1, and the DR-4 answer | [OWNER DECISION], translated and condensed by the executor |
| Assignment of the resolution package IDs DR-1 to DR-5 to those texts | [INFERENCE] |
| "Projection owns query" replaced for query construction in the T2 definition (§8.33.1, §8.33.6 condition 1) | [INFERENCE]; owner confirmation [UNRESOLVED] |
| IU-04 §16 and the OD-06 authority matrix as cited | [APPROVED ARCHITECTURE]; the statement that the boundary is consistent with them is [INFERENCE] |
| Contract details of §8.33.2 not fixed by the owner: field names, types, validation, order, serialization | [INFERENCE]: executor design within the decisions, based on the resolution package section 3 [PROPOSED] |
| Executor choices 1, 2, and 4 | [INFERENCE] |
| Executor choice 3 | Superseded by R-1 |
| Executor choice 5 | [INFERENCE], based on the resolution package section 3.4 [PROPOSED] and D-10.9.6 [APPROVED ARCHITECTURE]; amended by item 5 above |
| Recording GA-B1-01 and GB-B1-01 | [OWNER DECISION] (DR-5) |
| Identifiers and item wording of GA-B1-01 and GB-B1-01 | [INFERENCE] |
| Repository and file evidence of §8.33.5 | [IMPLEMENTATION FACT] for 18:22; the file hashes are superseded below |
| §8.33.6 condition 2, MET | [OWNER DECISION] (§8.31.1) |
| §8.33.6 conditions 1, 3, and 5 (PARTIALLY MET; MET FOR THE B-1 SCOPE) | [INFERENCE]: executor assessments. "MET FOR THE B-1 SCOPE" is not a status defined in §8.64.6 |
| T10 and T8 transcriptions of §8.32.3 | Not approved; [UNRESOLVED] |
| P1-EXIT-01 (c) | NOT CLOSED |
| `retrieval_method` supplied by the caller | [UNRESOLVED]: the resolution package (section 3.2) names the Path decision as the source, and `PathDecisionIdentity` has no retrieval-method field |
| Extraction of facet codes from CSQ | [UNRESOLVED] (open point of §8.33.2) |
| Correspondence between Projection facet codes (lower case) and Capability facet codes (case-sensitive) | [UNRESOLVED]; until it is decided only exact equality matches |

**Files after correction** (CRLF, no BOM, untracked, uncommitted) [IMPLEMENTATION FACT]:

| File | SHA-256 |
|---|---|
| `./Projection/ProjectionFacetConstraintState.php` | `f60536e53a20ae3d1cdb9f8c8b1d605424da29d8e320ee9104b3c414efcd0f8a` |
| `./Projection/ProjectionFacetConstraint.php` | `58e327f961fa09b21f154e8c062e663e4d9238a01c331686b76946580f13cdc3` |
| `./Projection/ProjectionQueryConstraints.php` | `45f3f4756e9a2a760673b6275f19e257f7440fe147cc5a55f9ee120ddd530277` |
| `./Projection/ProjectionQueryConstraintsFactory.php` | `ccfef84670992616f5730269416b1f37e5dba4b4d22f4b70eec559ecc8977a15` |
| `backend/tests/Unit/Agriculture/Research/Projection/ProjectionQueryConstraintsTest.php` | `19427fb61df7ba7824f04e56b52f391582c4bbb59f142cc52ab5bf7b7bc31f6f` |

No other file changed. Earlier sections of this ADR, before §8.33, are byte-identical.

**Tests written** (15 test methods; 23 cases including the nine mapping cases). Capability decisions come from the real `CapabilityRequirementEvaluator`, except where a malformed or hand-built decision is the subject of the test:

| Requirement | Test methods |
|---|---|
| `VERIFIED` + `CURRENT` gives `CONSTRAINABLE`; + `STALE` or `UNKNOWN` gives `UNKNOWN` with a warning; other states unchanged | `test_capability_state_and_freshness_map_to_constraint`, `test_mapping_is_total_over_frozen_states_and_freshness` |
| `VERIFIED` with missing or unprovable freshness gives `UNKNOWN` | `test_verified_without_established_current_freshness_is_unknown` |
| Freshness does not bypass the subject and decision checks | `test_freshness_does_not_bypass_decision_alignment` |
| Resolved decision only; restrictive-wins, foreign subjects, `content_about`, and cells not required | `test_reads_only_cells_resolved_by_the_capability_decision` |
| No case folding of Capability codes | `test_capability_codes_are_not_case_folded` |
| Rejections, malformed and conflicting cells | `test_rejects_empty_duplicate_and_malformed_values` |
| Identifiers, ordering with numeric codes, warnings from a direct `create()`, warning deduplication | `test_carries_aligned_decision_identifiers`, `test_serialization_is_sorted_and_deterministic`, `test_unknown_constraints_are_disclosed_once_in_facet_order` |
| Earlier groups 2, 6, 7, and 8 of §8.33.5 | `test_identity_critical_is_derived_not_caller_supplied`, `test_payload_keys_are_snake_case_and_clean`, `test_does_not_write_capability_or_use_any_repository`, `test_existing_contracts_are_unchanged`, `test_contract_is_not_wired_into_runtime` |

**Test status: NOT RUN** [IMPLEMENTATION FACT]. The environment is unchanged since §8.33.5: no PHP on the host, no `backend/vendor`, and the only isolated means is Docker, which the 18:49 prompt prohibits. No PHP syntax check was possible. **No test is claimed to pass.**

## 8.34 — Owner decisions of 2026-10-14 (fourth set): Gate A audit of the scientific search architecture, legacy-62 disposition, Gate A and Gate B for P1-EXIT-06 increment R1, R1 execution

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. P1-EXIT-06: **NOT CLOSED** (increment R1 only). P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 at 22:05 local time the user issued the prompt "WSA-Enterprise — Autonomous Software Engineering Mission: Scientific Search Architecture Completion, 109-Source Integration, Legacy Registry Forensic Cleanup, and Strict Security Governance" (sent twice, identical) [OWNER DECISION]. It requires a forensic audit (Gate A), owner approval of any implementation scope (Gate B and Gate C), deletion of legacy-62 artifacts only after approval of an exact manifest, and this continuity record (Gate F). It prohibits commits, pushes, pulls, staging, branch changes, history rewriting, reset, clean, stash, database changes, dependency installation, network access, live source probes, deployment, and changes to unrelated files; Docker execution is prohibited unless separately authorized. The executor presented one structured decision request; the owner's answers are quoted in §8.34.1. The prompt and the answers are conversation records, not archived in the repository [M]. Earlier sections are unchanged. No decision of §8.30 to §8.33 is changed. Tags as in §8.33.8.

### 8.34.1 — Decisions

| Item | Decision (owner answer, condensed) | Tag | Effect |
|---|---|---|---|
| R1 | Approve P1-EXIT-06 increment R1: a typed catalog holding the five existing Stage 3 sources as `LEGACY_STAGE3` external registrations, with `adr_id` rejected until an Onboarding Contract exists; registry, selector, and orchestrator key lists resolve through it with identical behaviour; a sequential failure-isolation fix; a conformance suite and drift tests. No seat binding or activation, no Capability writes, no database, no change to CSQ, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, or `AnswerComposer` | [OWNER DECISION] | §8.34.4, §8.34.5 |
| Docker | Isolated Docker test runs as in §8.31.5 are permitted: image `wsa-enterprise-backend-test:latest`, `--network none`, vendor volume and source read-only, no shared database, no Compose PostgreSQL | [OWNER DECISION] | Used for every test of §8.34.6. No other Docker use is authorized |
| Legacy 62 | Keep the 62-register as historical. The deletion manifest is empty | [OWNER DECISION]; consistent with D-09 item 4 [APPROVED ARCHITECTURE] | §8.34.3. Nothing was deleted |
| GAP-QUERY | Keep D-04. The semantic handoff to sources goes through the Builder and, later, the B-2 constraint contract; no adapter-level query handoff | [OWNER DECISION]; confirms D-04 and D-10.9.2 without amending them | No adapter receives the compiled query bundle. R1 contains no query-handoff change |

### 8.34.2 — Gate A record GA-R1-01: audit of the scientific search path (gap matrix)

**Production path traced** [IMPLEMENTATION FACT]: `MultiSourceScientificSearchOrchestrator::execute()` → `ScientificSourceSelector::selectionTrace()` → `filterEnabledSources()` and `prioritizeSources()` → `ScientificSourceAdapterRegistry::resolveMany()` → `collectProviderExecutions()` (sequential, or a concurrent process pool for the independent scholarly sources of Home `generic_research`; FAOSTAT has its own canonical-query loop) → deduplication, ranking, and filtering. `Stage3CghiaCoexistenceBoundary` runs in default mode `legacy_only` and always permits retrieval; `Stage3SourceKeyIdentityBridge` maps Stage 3 keys to identities.

| Gap | Finding before R1 | Tag | Status after R1 |
|---|---|---|---|
| GAP-REG: canonical runtime source registry | None. At least six hard-coded key lists: the registry constructor, `ScientificSourceSelector::DEFAULT_INTERNET_FIRST_SOURCES` and `OPTIONAL_SOURCES`, the orchestrator's `INDEPENDENT_SCHOLARLY_SOURCE_KEYS` and its enablement `match`, `Stage3SourceKeyIdentityBridge::KNOWN_STAGE3_SOURCE_KEYS`, and the Path-family `match` of the Coexistence Boundary. Known debt per D-05 | [IMPLEMENTATION FACT] | PARTIALLY ADDRESSED for the five Stage 3 sources: `ScientificSourceCatalog` is the single runtime list. The 109 seats are not registered (§8.34.7) |
| GAP-CAP: Capability production and enforcement | Capability v2 is evaluated only in the Coexistence Boundary for `access_method:api`, whose result never blocks retrieval. No Capability record exists for any Stage 3 source, and selection does not read Capability | [IMPLEMENTATION FACT] | OPEN. Outside R1 |
| GAP-ADAPT-IF: versioned adapter contract | `ScientificSourceAdapterInterface` had no version | [IMPLEMENTATION FACT] | PARTIALLY ADDRESSED: `CONTRACT_VERSION = 'scientific_source_adapter.v1'` names the existing contract; registrations must declare it. No method changed |
| GAP-SEL: duplicate registries and fixed selection | As GAP-REG; off-path registries (`AgriculturalProviderRegistry` and its bridge, `ScientificSourceProfileCatalog`) keep their own lists | [IMPLEMENTATION FACT] | PARTIALLY ADDRESSED: selector, registry, orchestrator, identity bridge, and Path-family mapping read the catalog. Off-path registries are unchanged |
| GAP-QUERY: compiled query bundle not consumed by adapters | Confirmed. The compiler output is metadata only, as approved by D-04 and D-10.9.3 | [IMPLEMENTATION FACT]; [OWNER DECISION] §8.34.1 | NOT A DEFECT under D-04. Handoff path: Builder and B-2 |
| GAP-SEM: scientific meaning re-derived downstream | Partially evidenced only | [INFERENCE] | OPEN; not re-verified in R1 |
| GAP-PROV: provenance and licensing | `ScientificSearchResult` has no licence, retrieval-time, or access-terms field | [IMPLEMENTATION FACT] | OPEN |
| GAP-EVID: duplicated or conflicting evidence rules | Not re-verified in this record | [UNRESOLVED] | OPEN |
| GAP-ERR: error classification and retryability | `ScientificSourceSearchOutcome` has four statuses and a free-text `error`; no taxonomy and no retryability field. An exception thrown by an adapter on the sequential path aborted Stage 3 for every provider; the concurrent path already contained exceptions | [IMPLEMENTATION FACT] | PARTIALLY ADDRESSED: the sequential path now contains adapter exceptions (§8.34.5). Taxonomy and retryability OPEN |
| GAP-FAMILY: reusable source-family adapters | None. The five adapters are source-specific REST adapters; OAI-PMH, RSS/Atom, and bulk appear only as Capability and Path vocabulary | [IMPLEMENTATION FACT] | OPEN |
| GAP-TEST: shared adapter conformance tests | None | [IMPLEMENTATION FACT] | ADDRESSED for the registered sources: `ScientificSourceAdapterConformanceTest` runs over every catalog key |
| GAP-ISO: duplicated Home/Crop isolation logic | Partially evidenced only | [INFERENCE] | OPEN; not re-verified in R1 |

### 8.34.3 — Legacy-62 dependency matrix and deletion manifest

| Artifact | Type and Git state | References | Runtime, build, test impact | Historical value | Status | Action |
|---|---|---|---|---|---|---|
| `docs/architecture/SCIENCE-SOURCE-EXPANSION-62-REGISTER.md` (SHA-256 `9170257a5fea2d1ffd05c5eec5c4e8c88713a0ca7f6b3d78405e7b274ab102c2`) | Markdown; untracked | D-06, D-09 (items 3 and 4) of this ADR; `docs/adr/README.md` line 21 | None found in `backend/app`, `backend/config`, `backend/database`, or `frontend/src`. `Adr023109MembershipRegisterContractTest` only asserts that the 109 register does not carry `historical_62_xref` | Required: D-09 item 4 retains it for historical identity traceability and source lineage | HISTORICAL | RETAIN |

**Deletion manifest: EMPTY** [OWNER DECISION]. No file was deleted. The 109 register `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` is unchanged (SHA-256 `783035186579aa558d637f581c11c6701ae7bb39951cdc085fdb83cad59a6897`).

### 8.34.4 — Gate B record GB-R1-01: increment R1 of P1-EXIT-06

**Classification.** Recording R1 and its scope is [OWNER DECISION] (§8.34.1). The record identifier and the wording below were drafted by the executor [INFERENCE].

| Field (§8.64.4) | Content |
|---|---|
| Granted by | The user, as architecture owner (§8.31.1), on 2026-10-14, in the answer to the Gate C decision request |
| Authorized scope | The typed catalog and its value types under `backend/app/Services/Agriculture/Research/Search/Registry/`; `ScientificSourceAdapterInterface::CONTRACT_VERSION`; catalog-driven `ScientificSourceAdapterRegistry`, `ScientificSourceSelector`, `MultiSourceScientificSearchOrchestrator`, `Stage3SourceKeyIdentityBridge`, and the Path-family mapping of `Stage3CghiaCoexistenceBoundary`; sequential failure isolation; conformance and drift tests; this section |
| Prohibited scope | Seat binding, onboarding, or activation; Capability writes; database, schema, migrations; CSQ, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, `AnswerComposer`; query handoff to adapters; source adapters; configuration, `.env`, `phpunit.xml`, `composer.lock`, dependencies; deletion; staging, commits, pushes, branch changes |
| Gate A relied on | GA-R1-01 (§8.34.2) |
| Acceptance criteria | Identical selection, resolution, and enablement for the five sources; ADR seats rejected; one catalog entry suffices to add a source; a throwing adapter cannot abort other providers; no regression against a pre-change baseline in the Unit and Feature suites |
| Tests and evidence | Isolated Docker runs as authorized in §8.34.1, compared per test case with a baseline snapshot (§8.34.6) |
| Suspension | Any change that would need a Capability write, a seat binding, or a change to a prohibited file |

### 8.34.5 — R1 execution

**Repository** [IMPLEMENTATION FACT]: `G:/WSA-Enterprise`, branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`, nothing staged. Remote state UNVERIFIED (no network access). Before any change this ADR had SHA-256 `d3771aa07673afe746f9b2cecfc3199b4ba44aecdd0f8a7361eca52efc678e51` (421,556 bytes); the bytes before this section are unchanged.

**Design** [IMPLEMENTATION FACT]; the field set and names are executor design within §8.34.1 [INFERENCE]:
1. `ScientificSourceRegistration` (readonly): `sourceKey`, `adapterClass`, `adapterContractVersion`, `lifecycle`, `accessFamily`, `selectionRole`, `executionGroup`, `enablement`, `pathFamily`, `adrId`. It rejects a key that is not lower-case snake_case, an adapter class that does not implement the interface, an unsupported contract version, and any `adrId` ("an accepted Onboarding Contract is required first (ADR-023 D-10.11)").
2. `ScientificSourceCatalog`: production order `openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`; all `LEGACY_STAGE3` and `rest_api`. Roles: the first three `internet_first_default` (execution group `independent_scholarly`), `consensus` `optional_explicit_only` (`sequential`), `fao_stat` `statistical_consideration` (`statistical`). Enablement: the existing `agricultural_intelligence.*.enabled` flags for the three scholarly sources, always enabled for Consensus, `FaoStatRuntimePolicy::isEnabled()` for FAOSTAT. Path families P12, P11, P13, P12, P01, as before. Duplicate keys are rejected.
3. The registry resolves adapters from the container in catalog order and rejects an adapter whose `sourceKey()` differs from its registration. The selector returns enabled `statistical_consideration` keys, then enabled `internet_first_default` keys, as before. `DEFAULT_INTERNET_FIRST_SOURCES`, `OPTIONAL_SOURCES`, and `KNOWN_STAGE3_SOURCE_KEYS` remain as deprecated compatibility projections, pinned to the catalog by drift tests.
4. Sequential failure isolation: an exception thrown by `search()` becomes one `failed` outcome with error `adapter_exception` and `observability.exception_class`. Only the exception class is logged, never the message. The remaining variants of that provider are skipped; other providers still run. This matches the existing concurrent path, which already catches `\Throwable`.

**Behaviour differences** [IMPLEMENTATION FACT]:
1. The orchestrator's enablement filter used `(bool) config(...)`; it now uses `FILTER_VALIDATE_BOOL`, as the selector already did. The configuration file produces booleans, so production behaviour is identical. Only a runtime override with a string such as `'false'` is now read as disabled.
2. Because adapter exceptions are contained, an assertion failing inside an `Http::fake` callback, or a `preventStrayRequests` exception, on the sequential path now produces a `failed` outcome instead of propagating. The concurrent path already behaved this way. Observed effect: one already-failing test fails at a later assertion (§8.34.6). Whether test-framework exceptions should be re-thrown is [UNRESOLVED].

**Files** (uncommitted, unstaged) [IMPLEMENTATION FACT]:

| File | State | Line endings | SHA-256 |
|---|---|---|---|
| `backend/app/Contracts/ScientificSourceAdapterInterface.php` | modified | LF | `2260559bdd60a97fd9cc935d28acb07229f1d669c466d8bf2a8aeac892d4888c` |
| `…/Research/Coexistence/Stage3CghiaCoexistenceBoundary.php` | modified | CRLF | `022dfde4d89fc7e8fc1f764c6b850feb6c6d89a3b06def284f9a1c5519a94dcc` |
| `…/Research/Coexistence/Stage3SourceKeyIdentityBridge.php` | modified | CRLF | `f028aacebf7229a824572bc921b5d4847c92474066042b0dc5ea8870eba41416` |
| `…/Research/Search/MultiSourceScientificSearchOrchestrator.php` | modified | LF | `9423a086f02570475107aa80c97c8a8b185b749941003c84ca60f6d87752baa2` |
| `…/Research/Search/ScientificSourceAdapterRegistry.php` | modified | LF | `517698a7b28ad2d44dc1496af4fa4997f4f97c1a0a48cd35e18a94ebae7e23dd` |
| `…/Research/Search/ScientificSourceSelector.php` | modified | LF | `23f9ae47c3f782f20a528748317a469460d32ec7a634ebe35932ab19e5b9c54d` |
| `…/Search/Registry/AlwaysEnabledSourceEnablement.php` | new | LF | `bec33c6290833fca9d5fcb0217868b42a56d84d03d0c9a2d26dd54e243de97ba` |
| `…/Search/Registry/ConfigFlagSourceEnablement.php` | new | LF | `86f512299cf7a046daea849809898845238368af83d96302de2f00b5ff59d1c9` |
| `…/Search/Registry/FaoStatRuntimeSourceEnablement.php` | new | LF | `d1dd7673908be983dce0b21db6f8fd5876f8e39df42e9c6ed2d0cee3722d22c6` |
| `…/Search/Registry/ScientificSourceAccessFamily.php` | new | LF | `795f6e53cb1dfe85677ade89ecc747820bdf066acfae84454a8360b5bae48776` |
| `…/Search/Registry/ScientificSourceCatalog.php` | new | LF | `6456aea5de847a0afcb11e94371c7a5c4d6ea65b11fa02817758bc2cf48bb772` |
| `…/Search/Registry/ScientificSourceEnablement.php` | new | LF | `e432fe3180cecb77007821c7e31748ebc27daebe9b982e4f36886e5f90501fab` |
| `…/Search/Registry/ScientificSourceExecutionGroup.php` | new | LF | `fd98344b4dce1f6871fb72b0f9f35d1a84298d1d58556111e9864e1541cbf4ae` |
| `…/Search/Registry/ScientificSourceLifecycle.php` | new | LF | `3a8044d0d8f191dfe0f23fb6a701b0d79ff896b1e10e3030226103deb8f14da6` |
| `…/Search/Registry/ScientificSourceRegistration.php` | new | LF | `85f8c9e781818b4e73a47e2a010e94734113ff4e4f5555ec1b09c0fa91286657` |
| `…/Search/Registry/ScientificSourceRegistryInvariantViolation.php` | new | LF | `9b0c3bd95fa8f565aa44cc3ffccf7c3147db8a51511c01b4dea0f4aab36da3e6` |
| `…/Search/Registry/ScientificSourceSelectionRole.php` | new | LF | `cb0c1e2d908bcb85ffaca0b8bcaa8b8b45667dfbc914b485b7ec16684855f241` |
| `backend/tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCatalogContractTest.php` | new | CRLF | `75899117cb78a34543d0fa92c9699921effdf68d293dbfee903224be4e179ed9` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificSourceAdapterConformanceTest.php` | new | CRLF | `1a2044a45b73686cd7631aa5f44e76133f6a0f20115d784dd3c1d4f56b407bf1` |

`…` is `backend/app/Services/Agriculture`. No other file changed: `git status` matches the pre-work capture except for these 19 paths and this ADR, and the 1,204 other files of `backend/` (excluding `vendor`, `node_modules`, `storage`, `e2e-tmp`, `.env`) are byte-identical to a pre-change snapshot. The hash prefixes of `composer.lock` (`D6EE17A75BF1DA5B`), `phpunit.xml` (`1EC076C544341F95`), and `backend/.env` (`16B96F1E9230C8DA`) are unchanged.

### 8.34.6 — Tests

**Means** [IMPLEMENTATION FACT]: image `wsa-enterprise-backend-test:latest` (`sha256:a63876622be9823ece235197c4f2fba77b39efdfd0c07b5412d1e4d0da2ab259`), `docker run --rm --network none`, source and the `wsa-enterprise_backend_vendor` volume mounted read-only, an empty `.env` mounted over the real one, `storage` and `bootstrap/cache` on tmpfs, a throwaway `APP_KEY`, `APP_ENV=testing`, SQLite in memory as configured by `phpunit.xml`, `--do-not-cache-result`, JUnit output outside the repository. The baseline ran the same way on a copy of `backend/` taken before the R1 changes and kept outside the repository.

| Run | Tests | Failures | Errors | Skipped | Comparison per test case |
|---|---|---|---|---|---|
| Unit, baseline | 1,243 | 13 | 16 | 0 | — |
| Unit, R1 | 1,276 | 13 | 16 | 0 | 0 regressions, 0 newly passing, 0 removed; 33 added, all passing |
| Feature, baseline | 950 | 84 | 0 | 12 | — |
| Feature, R1 | 950 | 84 | 0 | 12 | 0 regressions, 0 newly passing; identical failure messages |
| `tests/Unit/Agriculture/Intelligence/FaoStat`, baseline | 136 | 7 | 0 | 0 | Same seven failures as in R1 |

**New tests** (all passing): `ScientificSourceCatalogContractTest` (12 cases: production order, legacy registrations without `adr_id`, ADR-seat rejection, invalid key, class, and version with exact messages, duplicates, registry order and key mismatch, selector roles and flags, not-ready plan, compatibility-projection drift, identity bridge and Path family, one entry adds a source) and `ScientificSourceAdapterConformanceTest` (21 cases: structural conformance, 503 classification, connection failure containment, empty response never reported as success, no credential in any output, for every catalog key, and isolation of a throwing adapter on the sequential path).

**Pre-existing failures** [IMPLEMENTATION FACT]: the 29 failing or erroring Unit cases and 84 failing Feature cases fail identically on the baseline. They are not caused by R1 and were not investigated or fixed. One failure message changed: `FaoStatPhase3FixTest::test_openalex_agri_domain_does_not_reach_faostat_and_qcl_is_used` fails on the baseline inside its `Http::fake` callback (`element` `'2413'` instead of `'2510'`) and under R1 at the later assertion that `fao_stat` succeeded, because the callback's assertion error is now contained (§8.34.5, behaviour difference 2). The root cause is the same.

**Evidence for §8.33** [IMPLEMENTATION FACT]: the R1 Unit run includes `ProjectionQueryConstraintsTest`, recorded NOT RUN in §8.33.5 and §8.33.8. JUnit reports 25 cases, all passing (§8.33.8 states 23 cases; the difference was not investigated). The Capability (20), Path (17), Identity (13), Coexistence (28), Disclosure (17), and Projection (51 in total) unit suites also pass. This changes no status of §8.33.

### 8.34.7 — 109 reconciliation

The 109 membership register is unchanged. No seat is bound to a runtime registration: the catalog rejects `adr_id` until an Onboarding Contract is accepted (D-10.11). The five registrations are legacy Stage 3 sources, not membership seats; their correspondence to seats stays governed by `Stage3SourceKeyIdentityBridge` and is not changed. The runtime integration of every one of the 109 seats is **NOT EXECUTED**. Membership still does not imply runtime (D-10).

### 8.34.8 — Status matrix

| Item | Status on 2026-10-14 |
|---|---|
| D-04, D-05, D-09, D-10, D-10.9.2, D-10.9.3, and every other approved decision | Unchanged |
| Gate A | GA-IC-01, GA-B1-01, GA-R1-01 |
| Gate B | GB-IC-01, GB-B1-01, GB-R1-01 |
| R1 (P1-EXIT-06 increment) | IMPLEMENTED, uncommitted; tests RUN as in §8.34.6 |
| P1-EXIT-06 | NOT CLOSED. Open: Capability enforcement in selection and for seats; adapter contract v2 with an error taxonomy and retryability; provenance and licensing fields; source-family adapters; off-path registries; registration of seats after Onboarding Contracts |
| Legacy-62 register | RETAINED as historical; deletion manifest empty |
| GAP-QUERY | Closed as not-a-defect under D-04; semantic handoff deferred to the Builder and B-2 |
| Pre-existing test failures (29 Unit, 84 Feature) | Recorded, not investigated |
| B-2 | NOT AUTHORIZED |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.35 — Owner decisions of 2026-10-14 (fifth set): R1 stabilization (exception boundary), R2 capability lifecycle and production enforcement

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. P1-EXIT-06: **NOT CLOSED** (increments R1 and R2 only). P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-14 at 23:31 UTC+3 the user issued the prompt "WSA-Enterprise — Autonomous Engineering Continuation Mission: Architecture Completion, Production Implementation, Verification, and Secure 109-Source Expansion" (sent twice, identical) [OWNER DECISION]. It orders increments R1 to R8, automatic continuation between increments, one status record in this ADR, and the change-control rules of its §9 (no commits, pushes, pulls, staging, branch changes, reset, clean, stash, deletion, database changes, dependency installation, network access, deployment; Docker only with separate authorization). The prompt as received ends mid-sentence in §9, item 9 of its safety protocol ("Verify that unrelated"); every rule received is applied. The executor presented one structured decision request; the answers are in §8.35.1. The prompt and the answers are conversation records, not archived in the repository [M]. Earlier sections are unchanged. No decision of §8.30 to §8.34 is changed. Tags as in §8.33.8.

### 8.35.1 — Decisions

| Item | Decision (owner answer, condensed) | Tag | Effect |
|---|---|---|---|
| Docker | Isolated test runs only, with the protocol of §8.34.6, for every increment of this mission | [OWNER DECISION] | Used for every test of §8.35.6 |
| Capability enforcement for the five legacy sources | Enforce for every registration. The five legacy Stage 3 sources get an explicit, disclosed legacy operational basis that authorizes the search operation only and is never shown as VERIFIED. Seats and new sources need VERIFIED evidence that is current | [OWNER DECISION] | §8.35.4. Search behaviour of the five sources is unchanged |
| Location of capability evidence | Reviewed, versioned in-code declarations read by the selector. The database store is untouched | [OWNER DECISION] | `EloquentCapabilityStoreRepository` and table `cghia_capability_cell_records` are neither read nor written |

### 8.35.2 — State recovery

[IMPLEMENTATION FACT] Branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`, 0 staged, 0 stashes, 8 worktrees. Before this increment `git status` matched §8.34.5 (the pre-work capture plus the 19 R1 paths), the R1 file hashes matched §8.34.5, and this ADR had SHA-256 `cbb80de37c122672020f268a59816767fb93b70f64bf7094bc36db78a9fb150b` (442,164 bytes). Every statement of the R1 report listed in the prompt was confirmed against the code, with the corrections of §8.35.3.

### 8.35.3 — R1 stabilization

**Registry baseline** [IMPLEMENTATION FACT]: the catalog governs the adapter registry, the selector, the orchestrator's enablement filter and execution groups, the identity bridge, and the Path-family mapping, as stated in §8.34.5. Resolution follows catalog order; duplicate keys and adapter-key mismatches are rejected; unregistered keys still pass the orchestrator filter and are dropped by `resolveMany()`, as before R1. The deprecated constants are pinned by drift tests.

**Exception boundary: finding** [IMPLEMENTATION FACT]: the R1 isolation of §8.34.5 caught `\Throwable`. It therefore also caught programming defects (`\Error`, `\LogicException`), PHPUnit `AssertionFailedError` (a `RuntimeException`), and Laravel `StrayRequestException` (a `RuntimeException`, not an `HttpClientException`), and turned them into ordinary source failures. That caused the changed failure message recorded in §8.34.6.

**Exception taxonomy** (executor design within the mission's requirement that test-framework exceptions and internal defects are not hidden) [INFERENCE]:

| Class | Examples | Sequential path |
|---|---|---|
| Expected transport failure | `Illuminate\Http\Client\HttpClientException`: `ConnectionException`, `RequestException` | Contained: one `failed` outcome, error `adapter_exception`, `observability.failure_category = transport`; the remaining variants of that provider are skipped |
| Declared source failure | New `App\Services\Agriculture\Research\Search\ScientificSourceFailure`, thrown by an adapter for an expected upstream failure | Contained, `failure_category = source` |
| Defect or test-framework signal | Anything else: `\Error`, `\LogicException`, other `RuntimeException`s, `AssertionFailedError`, `StrayRequestException` | Propagates, as before R1 |

Only the exception class and the category are logged or returned, never the message.

**Sequential and concurrent paths** [IMPLEMENTATION FACT]: the concurrent scholarly path (Home `generic_research`, process pool) is unchanged. It still catches `\Throwable` per provider inside the worker and for the pool as a whole, and its two log calls include the exception message. An exception cannot cross the process boundary as the original object, so a test-framework signal raised in a worker cannot reach the test there either. In unit tests the concurrent path runs only when `agricultural_intelligence.stage3_home_scholarly_concurrency` is set. Applying the taxonomy to that path, and removing messages from its logs, is left to R3 [UNRESOLVED].

**FAOSTAT message** [IMPLEMENTATION FACT]: with the taxonomy in place, `FaoStatPhase3FixTest::test_openalex_agri_domain_does_not_reach_faostat_and_qcl_is_used` again fails inside its `Http::fake` callback (`'2413'` instead of `'2510'`), exactly as before R1. Every failure message of the Unit and Feature suites is now identical to the pre-R1 baseline (§8.35.6). The pre-existing defect itself was not investigated.

**`ProjectionQueryConstraintsTest`, 23 or 25 cases** [IMPLEMENTATION FACT]: §8.33.8 records 15 test methods and 23 cases. The file now has 17 test methods and 25 cases: the methods `test_direct_create_accepts_only_constraints_the_capability_decision_resolves` and `test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid` are not in §8.33.8. Three B-1 files differ from the hashes recorded in §8.33.8: the test file (now `651d6d5068c2c1ff084381b8a94d364271e9b00034f09c5ea675b69ffa379726`, written 2026-10-14 20:28 local time), `ProjectionQueryConstraints.php` (now `8810decd323bcefef2a41a0a9b0a27d3fcc8a7385c2a5a8e1b64de411b587998`, 20:00), and `ProjectionQueryConstraintsFactory.php` (now `515e432e193b8ff115a8e590f4cf5da439fdab50e5817d9d038556e83793fa7f`, 19:59). These changes predate the work of §8.34 and §8.35, which did not touch them, and no section of this ADR records them [UNRESOLVED]. The 25 passing cases reported in §8.34.6 belong to this unrecorded revision, not to the revision described in §8.33.8.

### 8.35.4 — R2: capability lifecycle and production enforcement

**Existing Capability v2** [IMPLEMENTATION FACT]: records are keyed by `CapabilitySubject`, which requires a membership seat (`adr_id`). Production supplies no records: `Stage3CghiaCoexistenceBoundary` evaluates `access_method:api` against an empty list and always permits retrieval. The database store is bound in the container, but nothing in the production path reads it. The five legacy sources have no seat (§8.34.5), so Capability v2 cannot express their capabilities.

**Design** [OWNER DECISION] for the basis and storage (§8.35.1); [INFERENCE] for names and rules:

| Element | Content |
|---|---|
| Operations | `search`, `pagination`, `filtering`, `full_text_retrieval`, `bulk_access`. Stage 3 retrieval requires `search` only (`ScientificSourceSelector::REQUIRED_OPERATION`) |
| Statuses | `verified_supported`, `verified_unsupported`, `not_verified`, `not_tested`, `temporarily_unavailable`, `access_denied`, `authentication_required`, `licence_unresolved`, `legacy_operational_basis`. These are runtime-registration statuses, not Cap v2 states |
| Declaration | Operation, status, non-empty evidence reference, `verifiedAt` (required when verified), `expiresAt` (required for `verified_supported`, later than `verifiedAt`). Timestamps must be ISO-8601 with offset, checked by round trip. The legacy basis is permitted for `search` only and only on `LEGACY_STAGE3` registrations. At most one declaration per operation |
| Policy (`ScientificSourceCapabilityPolicy`) | Deny by default. No declaration: `capability_not_declared`. `verified_supported` before `expiresAt`: allowed, `verified_current`; at or after it: `capability_evidence_stale`. `legacy_operational_basis` on a legacy registration: allowed, `legacy_operational_basis`. Every other status: denied, with the status as reason |
| Enforcement | `ScientificSourceSelector::selectSources()` keeps enabled sources only if the policy allows `search`. `MultiSourceScientificSearchOrchestrator` applies the same check to explicitly requested registered sources. Unregistered keys behave as before |
| Safe degradation | Denied sources are skipped and the others run. If none remain, the existing `no_sources_selected` report is returned |
| Observability and auditing | `selectionTrace()['capability_denied']`; `search_observability.source_capability` = `{required_operation, basis per selected source, denied decisions}`; a `Log::notice` that lists source key and reason for every denial |
| Production declarations | Each of the five sources declares `search` on the legacy basis with evidence reference `ADR-023 §8.35.1 owner decision legacy_basis (2026-10-14)`. No other operation is declared, so all other operations are denied |

**Lifecycle coverage** [IMPLEMENTATION FACT]:

| Stage | Status |
|---|---|
| Declaration | IMPLEMENTED |
| Evidence collection | NOT EXECUTED: it needs live probes, and network access is not authorized |
| Verification | NOT EXECUTED: no source holds `verified_supported` |
| Authoritative registration | IMPLEMENTED in code, per the owner decision |
| Freshness and expiry | IMPLEMENTED (`expiresAt`) |
| Production consumption and enforcement | IMPLEMENTED in the selector and the orchestrator |
| Auditing and failure reporting | IMPLEMENTED (observability and log) |

Runtime health signals for temporary unavailability, such as a circuit breaker, do not exist: `temporarily_unavailable` is only a declared status [UNRESOLVED]. The mapping from Cap v2 records to runtime declarations for seat-bound registrations is not implemented, because registration still rejects `adr_id`. Proposed rule [PROPOSED]: `VERIFIED` with freshness `CURRENT` maps to `verified_supported`; `VERIFIED` with any other freshness denies as stale; `UNAVAILABLE` maps to `verified_unsupported`; `UNVERIFIED`, `PARTIAL`, and missing records map to `not_verified`.

**Gate B record GB-R2-01** [INFERENCE] for identifier and wording; scope [OWNER DECISION] (§8.35.1 and the prompt's R1 and R2):

| Field | Content |
|---|---|
| Authorized scope | The exception-boundary correction; the capability types, policy, and declarations under `Search/Registry/`; enforcement in `ScientificSourceSelector` and `MultiSourceScientificSearchOrchestrator`; tests; this section |
| Prohibited scope | Activation of any source; seat binding; Cap v2 records, the store, or its table; database, migrations, configuration, `.env`, `phpunit.xml`, `composer.lock`, dependencies; CSQ, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, `AnswerComposer`, gates, source adapters; network; deletion; staging and commits |
| Acceptance criteria | Selection and explicit requests use capability decisions; unknown, stale, and every other denying status deny; the five sources keep their behaviour; no regression against the pre-R1 baseline |

### 8.35.5 — Files

**This increment** (uncommitted, unstaged) [IMPLEMENTATION FACT]. `…` is `backend/app/Services/Agriculture/Research/Search`; app files are LF, test files CRLF, none has a BOM.

| File | State | SHA-256 |
|---|---|---|
| `…/MultiSourceScientificSearchOrchestrator.php` | modified (R1 file) | `6121737722cc4d61b41bb1662d4e17baee60eb5ac357df6712a26184691bd809` |
| `…/ScientificSourceSelector.php` | modified (R1 file) | `662ebcc231c84d13c13b97d4db987acf222f667517412564b0c38599b58c2173` |
| `…/ScientificSourceFailure.php` | new | `1704116c23c8ab8b6db3b92be9bef787f81001c8aae76176fa0d9cb8ddfa7ef4` |
| `…/Registry/ScientificSourceCatalog.php` | modified (R1 file) | `4d1b8d93f37e70aaf65de57c03f5e3bc8081198ac27243c514466a5073533b40` |
| `…/Registry/ScientificSourceRegistration.php` | modified (R1 file) | `16ef71dc733f9d2c2d43bc605ee26c7fc237d35b4cb8c96df41c276fd1eb3d04` |
| `…/Registry/ScientificSourceOperation.php` | new | `c549d004aa0a0188c23f3a74dbd64ec1e4d1b605f0e5d54622ce8fd06a75a485` |
| `…/Registry/ScientificSourceCapabilityStatus.php` | new | `e80882a135cee86e9401ffd907a0283a1e795d514a635676764fdd4e77049c4b` |
| `…/Registry/ScientificSourceCapabilityDeclaration.php` | new | `a865ac564cef9885452dd12cbc7bddefc030c558248c2c9a83278ef302d1e99f` |
| `…/Registry/ScientificSourceCapabilityDecision.php` | new | `16dd54482030a6d9c553023774ccfab4409c3f0ebfbe6e372a3df9925a925ce6` |
| `…/Registry/ScientificSourceCapabilityPolicy.php` | new | `2aea8f2d04a8c2b3a1b9398aa2eec21506de1b440160143ef4a87320135cc6d0` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificSourceAdapterConformanceTest.php` | modified (R1 file) | `de8141a3ef43031e939e08d2d26d9385af45ef357bebac8e5d489aeb0f4b4033` |
| `backend/tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCatalogContractTest.php` | modified (R1 file) | `c03042bd4b13ba74188a815e01debbe3165c8ac557163267bac8ef621a29f3f0` |
| `backend/tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCapabilityPolicyTest.php` | new | `daad607f7e3aa8e948f5899619b94c6a383af5ad0eb9968a9bb6aa6768430f0e` |

The other R1 files keep the hashes of §8.34.5. `git status` differs from the pre-work capture of §8.34.5 only by the R1 paths and the seven new paths above. The hash prefixes of `composer.lock`, `phpunit.xml`, and `backend/.env` are unchanged (§8.34.5). This ADR is changed only by appending this section.

### 8.35.6 — Tests

**Means** [IMPLEMENTATION FACT]: as in §8.34.6 (isolated container, `--network none`, read-only source and vendor, SQLite in memory). Comparison per test case against the pre-R1 baseline of §8.34.6.

| Run | Tests | Failures | Errors | Skipped | Against the pre-R1 baseline |
|---|---|---|---|---|---|
| `tests/Unit/Agriculture/Research/Search` + `Coexistence` | 222 | 0 | 0 | 0 | — |
| Unit | 1,304 | 13 | 16 | 0 | 0 regressions, 0 newly passing, 0 removed; 61 added, all passing; failure messages identical |
| Feature | 950 | 84 | 0 | 12 | 0 regressions, 0 newly passing; failure messages identical |

**New and changed tests** (all passing): `ScientificSourceCapabilityPolicyTest` (21 cases: legacy basis for search only on the production sources, every other operation denied; undeclared operation denied; each of the seven denying statuses; verified support allowed until expiry and stale at and after it, including with a travelled clock; seven invalid declarations with exact messages; duplicate and foreign entries; selector selection and denial reasons, including staleness; orchestrator enforcement for explicit requests, with observability; nothing searched when every source is denied). `ScientificSourceCatalogContractTest` (13 cases; new: only the five legacy sources hold the legacy basis, for search only; the new-source example now declares verified search support). `ScientificSourceAdapterConformanceTest` (27 cases; the throwing-adapter test is replaced by two cases for contained failures and five for propagated defects and test signals: generic `RuntimeException`, `LogicException`, `TypeError`, `AssertionFailedError`, `StrayRequestException`).

**Pre-existing failures**: the 29 Unit and 84 Feature cases of §8.34.6, unchanged and not investigated.

### 8.35.7 — Legacy 62 and 109 reconciliation

The legacy-62 matrix of §8.34.3 is unchanged: one untracked historical file, retained. Deletion manifest: **EMPTY**. The 109 register is unchanged; no seat is bound or activated; runtime integration of every seat stays **NOT EXECUTED**.

### 8.35.8 — Status matrix

| Item | Status on 2026-10-14 |
|---|---|
| Approved decisions (D-04, D-05, D-09, D-10, D-10.9.2, D-10.9.3, and others) | Unchanged |
| Gate B | GB-IC-01, GB-B1-01, GB-R1-01, GB-R2-01 |
| R1 | IMPLEMENTED and stabilized (exception taxonomy), uncommitted; tests RUN |
| R2 | IMPLEMENTED for declaration, freshness, enforcement, and auditing; evidence collection and verification NOT EXECUTED (no network authorization); uncommitted; tests RUN |
| Concurrent-path exception handling | Unchanged; deferred to R3 [UNRESOLVED] |
| Unrecorded B-1 revision (§8.35.3) | [UNRESOLVED] |
| R3 (adapter contract v2), R4 (provenance and licensing), R5 (source-family adapters), R6 (Onboarding Contract and 109 reconciliation), R7 (shared logic and isolation), R8 (onboarding automation) | NOT STARTED |
| P1-EXIT-06 | NOT CLOSED |
| Legacy-62 register | RETAINED; manifest empty |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.36 — Owner decisions of 2026-10-15: B-1 revision evidence, R3 source adapter contract v2

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. P1-EXIT-06: **NOT CLOSED** (increments R1 to R3 only). P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** On 2026-10-15 at 00:10 UTC+3 the user issued the prompt "Continue autonomously with R3 — Source Adapter Contract v2" (sent twice, identical) [OWNER DECISION]. It contains the two decisions of §8.36.1 and these instructions: implement the versioned adapter contract v2 incrementally from the actual repository state and §8.35; keep v1 compatible during migration; convert only expected source failures into failed source outcomes; let unexpected application errors, programming defects, and test-framework exceptions propagate in both the sequential and the concurrent path; run only the isolated Docker protocol; no staging, commits, pushes, deletion, network, or database access; append the actual results here without changing historical content; then continue with the next safe, authorized increment. The prompt is a conversation record, not archived in the repository [M]. Earlier sections are unchanged. No decision of §8.30 to §8.35 is changed. Tags as in §8.33.8.

### 8.36.1 — Decisions

| Item | Decision (owner answer, condensed) | Tag | Effect |
|---|---|---|---|
| B-1 Projection revision (§8.35.3) | Keep it OPEN and UNAPPROVED. Investigate the changes made before R1, identify the two added test methods, verify their rationale and compatibility, and record the evidence here. Do not revert, accept, or modify them without sufficient evidence. R3 may continue if it does not depend on them | [OWNER DECISION] | §8.36.2. No B-1 file was changed |
| Live probes | Not authorized. No network access, live source probes, or source activation until the adapter contract, typed error categories, retry policy, provenance and licensing requirements, and conformance tests are complete and verified | [OWNER DECISION] | No network access; every test ran with `--network none` |

### 8.36.2 — B-1 revision: evidence

**Baseline identity** [IMPLEMENTATION FACT]: before the revision, the executor copied four B-1 files on 2026-10-14 at 19:59:02 local time to `%TEMP%\wsa-b1-backup-20261014-1954`, outside the repository. The four SHA-256 values of that copy equal those recorded in §8.33.8: `ProjectionFacetConstraint.php` `58e327f961fa09b21f154e8c062e663e4d9238a01c331686b76946580f13cdc3`, `ProjectionQueryConstraints.php` `45f3f4756e9a2a760673b6275f19e257f7440fe147cc5a55f9ee120ddd530277`, `ProjectionQueryConstraintsFactory.php` `ccfef84670992616f5730269416b1f37e5dba4b4d22f4b70eec559ecc8977a15`, `ProjectionQueryConstraintsTest.php` `19427fb61df7ba7824f04e56b52f391582c4bbb59f142cc52ab5bf7b7bc31f6f`. The unrecorded revision is therefore exactly the difference between that copy and the working tree.

**Correction to §8.35.3** [IMPLEMENTATION FACT]: four B-1 files differ from §8.33.8, not three. `ProjectionFacetConstraint.php` also differs; it is now `c4fbe16508e445d51ef1588649353ff49a08d8c5282bcf1f90749f6b105ef52f` (written 19:59:30). `ProjectionFacetConstraintState.php` still matches §8.33.8.

**Provenance** [IMPLEMENTATION FACT] from the conversation record [M]: the executor made the revision in this conversation, in response to three owner prompts of 2026-10-14 (UTC+3):

| Time | Prompt | Resulting change |
|---|---|---|
| 19:54 | "Continue the existing WSA-Enterprise Phase 1 closure mission … ADR-023 §8.33 and B-1 only" | The three source changes (written 19:59 to 20:00) and the first test method |
| 20:05 | "B-1 Contract Hardening, Evidence Completion, and Phase 1 Closure Progress" | Additional assertions |
| 20:26 | "Autonomous B-1 Completion, Phase 1 Closure, Verification, and Evidence Consolidation" | The second test method (test file written 20:28:27) |

A continuity record for the revision was drafted but never authorized, so no section of this ADR recorded it. That is the gap noted in §8.35.3. The prompts authorized engineering work on B-1; none of them accepted this revision as an amendment of §8.33.8.

**Changes** (difference ignoring line endings) [IMPLEMENTATION FACT]:

| File | Change |
|---|---|
| `ProjectionFacetConstraint.php` | +64 lines. New public `fromCapabilityDecision(CapabilityDecisionIdentity, list<string> $facetCodes): list<self>`. It is the former private factory method `resolvedQueryConstrainableCells` moved without change of logic, plus the loop that built the constraints: codes are normalized; an outcome matches a code only by the exact Cap dimension key; outcomes without a string `dimension_key`, without a valid Cap state, or without a boolean `missing_record` are rejected; conflicting outcomes for one code are rejected; an absent cell or `missing_record` gives `MISSING` |
| `ProjectionQueryConstraintsFactory.php` | +5, −66. `derive()` calls `ProjectionFacetConstraint::fromCapabilityDecision`; the private method and three imports are removed; the docblock "Sole producer of ProjectionQueryConstraints" becomes "Derives ProjectionQueryConstraints from caller facet codes" |
| `ProjectionQueryConstraints.php` | +17. `create()` resolves the constraints from the capability decision and throws `ProjectionInvariantViolation` "Facet constraint [{code}] does not match the capability decision." unless each supplied constraint equals the resolved one in its serialization and in `evidenceFreshness`. The docblock cites §8.33.8, M-1 and R-1 |
| `ProjectionQueryConstraintsTest.php` | +97: the two test methods below; no existing line changed |

**The two test methods** [IMPLEMENTATION FACT]:

| Method | Assertions | Content |
|---|---|---|
| `test_direct_create_accepts_only_constraints_the_capability_decision_resolves` | 8 | Direct `create()` rejects four constraints the decision does not carry: `VERIFIED` + `CURRENT` without a Cap cell; `CURRENT` asserted over `STALE`; a constraint whose freshness differs while its serialization is equal; `MISSING` asserted over `VERIFIED` + `CURRENT`. An outcome without `dimension_key` is rejected by `derive()` and by `create()` with the same message. A matching direct `create()` gives exactly the factory payload |
| `test_numeric_codes_keep_distinct_identities_and_empty_lists_are_valid` | 8 | `'7'` and `'07'` stay distinct facets (only `'7'` matches its Cap cell), with one `QUERY_CONSTRAINT_UNKNOWN` warning for `'07'`. Codes that collide after normalization (`'9'`, `' 9 '`) are rejected on both paths with the existing messages. An unmatched direct `VERIFIED` + `CURRENT` constraint is rejected. An empty facet list is valid and identical on both paths |

**Rationale** [INFERENCE]: before the revision the factory was documented as the sole producer, but `ProjectionQueryConstraints::create()` is public and accepted any caller-built constraint. A caller could therefore assert `VERIFIED` with `CURRENT` freshness that the capability decision does not carry. M-1 (`VERIFIED` is `CONSTRAINABLE` only with established `CURRENT` freshness) and R-1 (constraints come only from the resolved capability decision, without a parallel resolution) of §8.33.8 exclude that. The revision keeps a single resolution, in `ProjectionFacetConstraint`, and checks every direct construction against it. It adds no field and changes neither the M-1 mapping, nor the duplicate, normalization, and alignment rules, nor any warning text.

**Compatibility** [IMPLEMENTATION FACT]: for valid input the factory output is unchanged; the 15 methods (23 cases) of §8.33.8 are unchanged and pass. The only change in behaviour is that direct `create()` now rejects constraints that differ from the decision. No file of `backend/app` outside `Projection/` references `ProjectionQueryConstraints`, `ProjectionFacetConstraint`, or the factory, so no production caller is affected. R3 shares no file or type with B-1 and does not depend on it.

**Test evidence** [IMPLEMENTATION FACT]: in the R3 Unit run (§8.36.7), `ProjectionQueryConstraintsTest` has 25 cases, all passing, including both methods.

**Status**: OPEN and UNAPPROVED [OWNER DECISION]. Not reverted, accepted, or modified. The evidence is consistent with M-1 and R-1 and shows no incompatibility [INFERENCE]. Owner options [PROPOSED]: accept the revision as an amendment of §8.33.8 (new hashes as above), or restore the four files to the hashes of §8.33.8 from the copy.

### 8.36.3 — R3 design

Names and rules are the executor's design within the prompt's requirements [INFERENCE]. All new types are under `backend/app/Services/Agriculture/Research/Search/Contract/` unless stated.

| Element | Content |
|---|---|
| Failure categories (`ScientificSourceFailureCategory`) | `transport`, `timeout`, `rate_limited`, `upstream_unavailable`, `request_rejected`, `authentication`, `not_configured`, `policy_denied`, `circuit_open`, `malformed_response`, `budget_exhausted`, `invalid_request`, `source`, `unclassified`. Retryable: `transport`, `timeout`, `rate_limited`, `upstream_unavailable` only |
| Classifier (`ScientificSourceFailureClassifier`) | The single definition of an expected source failure. Thrown: `ScientificSourceFailure` gives its own category; Laravel `ConnectionException` gives `transport`; Laravel `RequestException` is classified by HTTP status; Guzzle `TransferException` gives `transport`; anything else is not expected. HTTP status: 429 `rate_limited`, 408 `timeout`, 401, 402, 403 `authentication`, 5xx `upstream_unavailable`, other 4xx `request_rejected`, anything else `unclassified`. It also classifies v1 outcomes (table below) and recognizes process-pool infrastructure failures: Symfony Process exceptions, Illuminate `ProcessFailedException`, and exactly `\Exception` whose message starts with "Concurrent process failed with exit code [", which is what Laravel's process driver throws when a worker exits with a non-zero code |
| Retry policy (`ScientificSourceRetryPolicy`, `ScientificSourceRetryDecision`) | `maxAttempts` ≥ 1 and 0 ≤ `baseDelayMs` ≤ `maxDelayMs`; default `none()` (one attempt). `decide()` stops, in this order, when the category is not retryable, when the attempts are exhausted, when a Retry-After value exceeds the maximum delay, or when the delay (Retry-After, otherwise base × 2^(attempt − 1), capped) plus 1 second for the retried request exceeds the remaining budget; otherwise it retries after that delay. Deterministic, no jitter, no sleeping |
| Licensing (`ScientificSourceLicensingDeclaration`, `ScientificSourceUsage`, `ScientificSourceUsageStatus`) | Usages `retrieval`, `metadata_storage`, `full_text_storage`, `excerpt_display`, `redistribution`, `citation_linking`; statuses `permitted`, `conditional`, `prohibited`, `unresolved`. Every usage defaults to `unresolved`; `permits()` is true only for `permitted`. A licence identifier or any resolved usage requires both a terms reference and an evidence reference. Unknown licensing is never permission |
| Search request (`ScientificSourceSearchRequest`) | Query text, limit ≥ 1, options keyed by name; reads the remaining search budget from `search_budget_remaining_seconds`. The query text is passed exactly as `ScientificSearchQueryBuilder` produced it (D-04); the contract never rewrites it |
| Descriptor (`ScientificSourceAdapterDescriptor`) | Source key, contract version `scientific_source_adapter.v2`, licensing, retry policy, maximum concurrent requests (≥ 1, default 1), and optional maximum results per request, pagination support, and requests per minute. `null` means undeclared and is never treated as a capability |
| v2 interface (`App\Contracts\ScientificSourceAdapterV2Interface`) | Extends the v1 interface with `descriptor()` and `searchRequest(ScientificSourceSearchRequest)`. A v2 adapter remains a valid v1 adapter |
| v1 bridge (`ScientificSourceAdapterV1Bridge`) | `wrap()` presents a v1 adapter as v2 with a fully undeclared descriptor (licensing unresolved, retry policy `none()`, all limits `null`) and passes the request fields to `search()` unchanged. A v2 adapter is returned as it is |
| Registration (`Registry/ScientificSourceRegistration`) | Accepts contract versions v1 and v2. A registration that declares v2 for a class not implementing the v2 interface is rejected |
| `ScientificSourceFailure` (`Search/`) | Gains an optional category, default `source` (the R1 behaviour); otherwise compatible |
| Contract violation (`ScientificSourceContractViolation`) | `\LogicException` for invalid contract values: a defect, never contained |

**v1 error vocabulary** [IMPLEMENTATION FACT]: every error string the five adapters and the orchestrator emit, and every `FaoStatErrorCategory` value, is classified. A test scans those six files and fails if a new string is not classified.

| v1 error | Category |
|---|---|
| `request_exception` | `transport` |
| `timeout` | `timeout` |
| `rate_limited` | `rate_limited` |
| `http_error` | by HTTP status |
| `provider_auth`, `provider_auth_or_billing` | `authentication` |
| `missing_api_key`, `unsupported_sources` | `not_configured` |
| `malformed_or_unusable_results` | `malformed_response` |
| `time_budget_exhausted` | `budget_exhausted` |
| `empty_query` | `invalid_request` |
| `adapter_exception` | the recorded `failure_category`, otherwise `unclassified` |
| FAOSTAT `EMPTY_RESULT` | not a failure |
| FAOSTAT `AUTHENTICATION_ERROR`, `AUTHORIZATION_ERROR`, `NOT_AUTHENTICATED` | `authentication` |
| FAOSTAT `UPSTREAM_SERVER_ERROR` | `upstream_unavailable` |
| FAOSTAT `NETWORK_ERROR` | `transport` |
| FAOSTAT `TIMEOUT` | `timeout` |
| FAOSTAT `UNKNOWN_UPSTREAM_ERROR` | `source` |
| FAOSTAT `DISABLED`, `CONFIGURATION_ERROR`, `NOT_READY` | `not_configured` |
| FAOSTAT `DOMAIN_NOT_ALLOWED`, `DOMAIN_NOT_VERIFIED` | `policy_denied` |
| FAOSTAT `CIRCUIT_OPEN` | `circuit_open` |
| FAOSTAT `INCOMPLETE_FILTERS`, `AMBIGUOUS_CODE`, `VALIDATION_ERROR` | `invalid_request` |
| Any other string; a failed or unavailable outcome without error | `unclassified` |

### 8.36.4 — Exception boundary in both paths

**Sequential path** [IMPLEMENTATION FACT]: `MultiSourceScientificSearchOrchestrator::searchAdapter()` calls `searchRequest()` on v2 adapters and `search()` on v1 adapters. A thrown exception is contained only if the classifier recognizes it: one `failed` outcome, error `adapter_exception`, `observability.failure_category` set to the category. Anything else propagates. The R1 behaviour for `HttpClientException` and `ScientificSourceFailure` is kept (categories `transport` and `source`); Guzzle transfer exceptions are now also contained.

**Concurrent path** [IMPLEMENTATION FACT]: §8.35.3 left it unchanged and deferred it here. Before R3 each worker task caught `\Throwable` per provider, the pool catch caught `\Throwable`, and both logged the exception message. Now each worker task runs the same `searchAdapter()` boundary, so expected failures are contained in the worker and return as outcomes; anything else leaves the worker. Laravel's process driver re-creates a worker exception in the parent by its class name, or throws `\Exception` "Concurrent process failed with exit code [N]…" when the worker exits abnormally. The pool catch now contains only the infrastructure failures the classifier recognizes (logged without message, providers not replayed, as before) and rethrows everything else. A defect, `StrayRequestException`, or `AssertionFailedError` raised in a worker therefore reaches the caller with its original class.

**Source adapters** [IMPLEMENTATION FACT]: the OpenAlex, Crossref, Semantic Scholar, and Consensus adapters each had an internal `catch (\Throwable)` that turned any exception into a failed outcome. Each now rethrows what the classifier does not recognize. The FAOSTAT adapter is unchanged; its vocabulary is classified (§8.36.3).

**Log content** [IMPLEMENTATION FACT]: the log contexts of the four adapters, the orchestrator boundary, and the pool catch record the exception class instead of the message. A transport exception message contains the request URL, and the OpenAlex request URL carries the configured `api_key`. A test checks that a configured key never appears in any log record. Crossref and Consensus still log the query text, as before.

**Observability** [IMPLEMENTATION FACT]: `search_observability.source_failures` lists `{source, error, failure_category, retryable}` for each outcome the classifier maps to a category. It follows `provider_failures`; no existing key changed.

**Retry** [IMPLEMENTATION FACT]: the retry policy is defined and tested but not wired into the orchestrator; every source has the policy `none()`. OpenAlex and Semantic Scholar keep their existing internal 429 retry; Crossref and Consensus do not retry. [INFERENCE] Orchestrator-level retry is left to a later increment so that retries are not doubled.

**Feature tests** [IMPLEMENTATION FACT]: the first full Feature run had 8 regressions, all from unfaked Semantic Scholar requests. Before R3 the adapter's `catch (\Throwable)` turned Laravel's `StrayRequestException` (raised by `Http::preventStrayRequests()`) into a failed outcome; it now propagates, as §8.35.3 requires for test-framework signals. The tests were made explicit, following the existing pattern of the same files (tests 11 and 12 of `WsaEnterpriseStage9SecurityTest`): an empty Semantic Scholar response (`api.semanticscholar.org/*` → HTTP 200, `{"data": []}`) in `WsaEnterpriseStage9IntegrationTest` (`fakeScholarly()` and test c), `WsaEnterpriseStage9SecurityTest` (tests 13, 14, 17), and `WsaEnterpriseStage10ProductionTest` (`test_external_content_is_data_and_missing_doi_is_not_fabricated`). In `test_15_provider_timeouts_do_not_become_500_or_leak_traces` Semantic Scholar throws `ConnectionException('timeout')`, matching that test's purpose. No assertion was changed. `ProviderConcurrencyOrchestratorTest` now simulates the pool failure with the exception the driver actually throws (`\Exception` "Concurrent process failed with exit code [255]. …") instead of a generic `RuntimeException`, which is now correctly treated as a defect.

### 8.36.5 — Gate B record GB-R3-01

Identifier and wording [INFERENCE]; scope [OWNER DECISION] (§8.36.1 and the prompt).

| Field | Content |
|---|---|
| Authorized scope | Contract types under `Search/Contract/`; the v2 interface; registration versioning; the `ScientificSourceFailure` category; the orchestrator boundary in both paths; narrowing the catches and log contexts of the four HTTP adapters; tests; Semantic Scholar fakes in three Feature tests required by the narrowed boundary; this section |
| Prohibited scope | Network access, live probes, activation; the B-1 files; other changes to adapter behaviour; CSQ, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, `AnswerComposer`, gates; database, migrations, configuration, `.env`, `phpunit.xml`, `composer.lock`, dependencies; deletion; staging and commits |
| Acceptance criteria | Expected source failures become failed outcomes and all other exceptions propagate, in both paths; v1 adapters work unchanged; the v1 error vocabulary is fully classified; no regression against the pre-R1 baseline |
| Result | Met (§8.36.7) |

### 8.36.6 — Files

**This increment** (uncommitted, unstaged) [IMPLEMENTATION FACT]. `…` is `backend/app/Services/Agriculture/Research/Search`; app files are LF, test files keep their line endings (new ones CRLF), none has a BOM.

| File | State | SHA-256 |
|---|---|---|
| `backend/app/Contracts/ScientificSourceAdapterV2Interface.php` | new | `1ce1332c2d906537eb8393bfdd4d72470c5d88830c353961bef2905a07cd6c0d` |
| `…/Contract/ScientificSourceAdapterDescriptor.php` | new | `3fa58bf04e8609a5e8b8bc80072e5969a89a155f1dfbca8e3b59d153dd41ed18` |
| `…/Contract/ScientificSourceAdapterV1Bridge.php` | new | `22cd846c615dc7b1e3171759163be69a476a274553756f32185caf40205fd1c0` |
| `…/Contract/ScientificSourceContractViolation.php` | new | `4f44efbadc0b1963467ae78968003dc5c078f5f8773ba96318f36bdf2e40d974` |
| `…/Contract/ScientificSourceFailureCategory.php` | new | `a2ed182392d1cb4e49d3e5cf6e9d57a61a761fe37ffcfc4960b2ef7f8b344fe3` |
| `…/Contract/ScientificSourceFailureClassifier.php` | new | `28c5e6a2d3da6e7a79a1bdd68190c060e1815330dc242f1229d8542e5f8d35ff` |
| `…/Contract/ScientificSourceLicensingDeclaration.php` | new | `74392e2ddd196f92bff6f397cea5d1c11817b64987f833bae8aff72341ce3218` |
| `…/Contract/ScientificSourceRetryDecision.php` | new | `58151fd8212f9d5fdd9e151bbbcd12c1ad302e4dedc8cd717fc4102a90185dea` |
| `…/Contract/ScientificSourceRetryPolicy.php` | new | `37de8136cdb16816918e8174b10f73320eef09bef6f68c17a6a37fdd0f09cea6` |
| `…/Contract/ScientificSourceSearchRequest.php` | new | `1687047327a8c7e290087d353b2653b799a3ed4b8a3657bccda778f2300c11fc` |
| `…/Contract/ScientificSourceUsage.php` | new | `94ca26a7019585dfb765f7e160f4f71b83952d8ef2af385d0f9c018333ecf443` |
| `…/Contract/ScientificSourceUsageStatus.php` | new | `99a11d31b6f8b0918e86db7915a5091bdd2de1eeab3307e40fc7c7752568f1f9` |
| `…/ScientificSourceFailure.php` | modified (R1 file) | `cf5b033db5897523a0f0ea14ec6f7a41d7c959f34150c391bcbc010fc8f7a8e5` |
| `…/Registry/ScientificSourceRegistration.php` | modified (R1 file) | `bb792bb1d91a5ca071c8632a475511ea9ed95251c8916547cd79d480ed70521c` |
| `…/MultiSourceScientificSearchOrchestrator.php` | modified (R1 file) | `dcd553771d3a14ba0018a7348b7204b402f20340a99162673b82f7bec561cfad` |
| `…/Adapters/OpenAlexScientificSourceAdapter.php` | modified (tracked) | `5a5490632ac34bb827295e4416f5a5056baa0e15f519fe507ca205d6558f4f50` |
| `…/Adapters/CrossRefScientificSourceAdapter.php` | modified (tracked) | `815faafa7f8a9784384ba7a006245c7244c8ae10d539db046a1728159cd5f0c3` |
| `…/Adapters/SemanticScholarScientificSourceAdapter.php` | modified (tracked) | `9e78de8aa7692cd612b024e46d6cb26f9175004334e67968ad3098b46aed9fad` |
| `…/Adapters/ConsensusScientificSourceAdapter.php` | modified (tracked) | `8cf5e624de295a976bb7818408b0c31754417d9afaf8e986ece5a492f17ce73c` |
| `backend/tests/Unit/Agriculture/Research/Search/Contract/ScientificSourceAdapterContractV2Test.php` | new | `a065143348aa7107ba2dbe071246ef10f22d0b9aed14a84781fa5d4e5f42dd30` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificSourceFailureBoundaryTest.php` | new | `ee1e793a09b4242dc8049430e3ff235b651cd1ee02a3f818d0eb1f73370094bf` |
| `backend/tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCatalogContractTest.php` | modified (R1 file) | `64df667ee42f62fabc9a3e46616efb42689101d32a44ee862c74c4f015e369f0` |
| `backend/tests/Unit/Agriculture/Research/Search/ProviderConcurrencyOrchestratorTest.php` | modified (tracked) | `5e3fef5b9b0be77a80403fa91bd085dbccb95dcb3db579ebe27acb72662e661a` |
| `backend/tests/Feature/WsaEnterpriseStage9IntegrationTest.php` | modified (tracked) | `b13630f3db3553422bde206388896ead5e8cd44e8d4843d733fcd7010f6b23e8` |
| `backend/tests/Feature/WsaEnterpriseStage9SecurityTest.php` | modified (tracked) | `7dd9db4b5041e202dd2c6700d3395af6f3801b823d2bd32f89457b897ecfec27` |
| `backend/tests/Feature/WsaEnterpriseStage10ProductionTest.php` | modified (tracked) | `caa6bff33b75fd6bb3ccd8d4600035c257adf5c6d7361549da537e4a35b96030` |

The R2 files that R3 did not touch keep the hashes of §8.35.5, and the B-1 files keep the hashes of §8.36.2. Compared with the capture after R1 (§8.34.5), `git status` differs only by 29 paths: the 7 new R2 paths of §8.35.5 and the 22 R3 paths above that were not already listed. Branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`, 0 staged, 0 stashes. The hash prefixes of `composer.lock`, `phpunit.xml`, and `backend/.env` are unchanged (§8.34.5), as are the 109 register and the legacy-62 file. Before this append this ADR had SHA-256 `08c17ba1c880ee66bc5827615e442b5563c931f2279472512c8638b42e481981` (459,496 bytes); it is changed only by appending this section.

### 8.36.7 — Tests

**Means** [IMPLEMENTATION FACT]: as in §8.34.6 (isolated container, `--network none`, read-only source and vendor, SQLite in memory). Comparison per test case against the pre-R1 baseline of §8.34.6; failure messages compared with line numbers normalized.

| Run | Tests | Failures | Errors | Skipped | Against the pre-R1 baseline |
|---|---|---|---|---|---|
| `tests/Unit/Agriculture/Research/Search` | 261 | 0 | 0 | 0 | — |
| Unit | 1,371 | 13 | 16 | 0 | 0 regressions, 0 newly passing, 0 removed; 128 added (61 in R2, 67 in R3), all passing; failure messages identical |
| Feature, first run | 950 | — | — | 12 | 8 regressions, all from unfaked Semantic Scholar requests (§8.36.4) |
| Feature, after the fakes | 950 | 84 | 0 | 12 | 0 regressions, 0 newly passing, 0 removed; failure messages identical |

**New and changed tests** (all passing): `ScientificSourceAdapterContractV2Test` (44 cases: retryable set; 8 expected exceptions and their categories; 7 defects and test signals not classified; process-pool infrastructure detection; 15 v1 outcomes; a scan of the six emitting files for unclassified error strings; retry decisions, including Retry-After, cap, and budget; licensing defaults and validation; request and descriptor validation; the v1 bridge; `ScientificSourceFailure` compatibility; v2 registration). `ScientificSourceFailureBoundaryTest` (23 cases: for each of the four HTTP adapters, a defect inside `Http::fake` propagates as the same instance, `StrayRequestException` propagates, and a `ConnectionException` is contained without the configured key or any message in the logs; concurrent path: a worker defect propagates, exceptions re-created by the driver propagate for `RuntimeException`, `\Exception`, `TypeError`, `StrayRequestException`, and `AssertionFailedError`, pool failures are contained without message, and an expected failure in one task fails only that source and is reported in `source_failures`; sequential path: `source_failures` for `rate_limited`; a v2 adapter receives its query variants unchanged). `ScientificSourceCatalogContractTest` (13 cases; the version message lists both versions, and a v1 adapter declaring v2 is rejected). `ProviderConcurrencyOrchestratorTest` (12 cases; realistic pool-failure exception, §8.36.4).

**Pre-existing failures**: the 29 Unit and 84 Feature cases of §8.34.6, unchanged and not investigated. Two of them, `WsaEnterpriseStage10ProductionTest::test_queue_worker_retries_are_bounded_in_compose` and `::test_ci_keeps_stage10_gate`, fail with "Unable to locate repository root for compose/CI fixtures." because the protocol mounts `backend` only [INFERENCE].

### 8.36.8 — Residual items

| Item | Status |
|---|---|
| A worker of the process pool calls `report()` on an exception before exiting; Laravel's reporting may log the message | [UNRESOLVED]; outside the orchestrator |
| Malformed worker output raises an `ErrorException` in the parent, which propagates | [IMPLEMENTATION FACT]; treated as a defect |
| The five adapters implement v1 only and run through the v1 boundary; no adapter declares a v2 descriptor | NOT MIGRATED; the v1 bridge provides undeclared descriptors |
| Licensing of all five sources | UNRESOLVED (no evidence; network not authorized) |
| Orchestrator-level retry | NOT WIRED (§8.36.4) |
| B-1 revision | OPEN, UNAPPROVED (§8.36.2) |

### 8.36.9 — Legacy 62 and 109 reconciliation

The legacy-62 matrix of §8.34.3 is unchanged: one untracked historical file, retained. Deletion manifest: **EMPTY**. The 109 register is unchanged; no seat is bound or activated; runtime integration of every seat stays **NOT EXECUTED**.

### 8.36.10 — Status matrix

| Item | Status on 2026-10-15 |
|---|---|
| Approved decisions (D-04, D-05, D-09, D-10, D-10.9.2, D-10.9.3, and others) | Unchanged |
| Gate B | GB-IC-01, GB-B1-01, GB-R1-01, GB-R2-01, GB-R3-01 |
| R1 | IMPLEMENTED and stabilized; concurrent path completed in R3; uncommitted; tests RUN |
| R2 | As §8.35.8 |
| R3 (adapter contract v2) | IMPLEMENTED: typed failure categories, classifier, retry policy (not wired), licensing declaration, request, descriptor, v2 interface, v1 bridge, registration versioning, boundary in both paths; adapters not natively migrated; uncommitted; tests RUN |
| Live probes and source activation | NOT AUTHORIZED (§8.36.1) |
| B-1 revision | OPEN, UNAPPROVED; evidence in §8.36.2 |
| R4 (provenance and licensing), R5 (source-family adapters), R6 (Onboarding Contract and 109 reconciliation), R7 (shared logic and isolation), R8 (onboarding automation) | NOT STARTED |
| P1-EXIT-06 | NOT CLOSED |
| Legacy-62 register | RETAINED; manifest empty |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

### 8.36.11 — Correction: Feature coverage and R3 acceptance

**Feature coverage** [IMPLEMENTATION FACT]: in the isolated container, `phpunit --testsuite Feature --list-test-files` discovers 105 of the 179 `*Test.php` files under `backend/tests/Feature`. The 74 others (every file sorting before `Phase11IdorTest.php`, and all of `tests/Feature/Agriculture/`) are not run by the suite, although they run when named explicitly. Every Feature result of §8.34.6, §8.35.6, and §8.36.7 therefore covers those 105 files only; their statements remain true for those files. The cause was not investigated [UNRESOLVED]. The 74 files include scientific-search tests (Crossref retrieval, OpenAlex rate-limit budget, Consensus, research-agent stages 1 to 5, provider isolation, universal answer orchestrator).

**Baseline for the 74 files** [IMPLEMENTATION FACT]: a pre-R1 tree was rebuilt outside the repository from the working tree, excluding the 34 paths R1 to R3 added and restoring the 14 tracked files they modified from HEAD (none of the 14 had changes before R1; each restored file matches its HEAD blob). Under the protocol of §8.34.6 the 74 files gave 890 tests, 50 failures, 3 errors, 2 skipped. The same run on the R3 tree was not executed: the tool's automatic review blocked it and the owner declined the approval request [M]. R3 has therefore **not** been verified against these 74 files.

**R3 acceptance** [IMPLEMENTATION FACT]: the mission's acceptance for R3 is that the versioned contract is used by production adapters and orchestrator code and that migration behaviour is tested. §8.36 meets it for the orchestrator only; the five production adapters still implement v1 (§8.36.8). The Gate B criteria of §8.36.5 were narrower than the mission's. Status of R3: **PARTIAL**. The planned completion [PROPOSED]: each of the five adapters implements the v2 interface with a descriptor declared only from its own code (results per request: OpenAlex 10, Crossref 100, Semantic Scholar 20, Consensus 20, FAOSTAT 20; licensing unresolved; retry policy `none()`), `searchRequest()` delegating to the unchanged `search()`, catalog registrations at v2, and the orchestrator passing `max(1, limit)` to v2 requests, which keeps the current behaviour because every adapter already clamps to at least 1. `UniversalAnswerOrchestratorFeatureTest` mocks the concrete adapter classes with `search()` only and must also stub `searchRequest()`.

## 8.37 — Owner decisions of 2026-10-15 (second set): Feature coverage runs, R3 completion

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. R3: **IMPLEMENTED**. P1-EXIT-06: **NOT CLOSED** (increments R1 to R3 only). P1-EXIT-01 (c): **NOT CLOSED**. Phase 1: **BLOCKED**. Phase 2: **NOT STARTED**.

**Source.** After §8.36.11 the executor presented one structured decision request; the answers are in §8.37.1 [OWNER DECISION]. The request and the answers are conversation records, not archived in the repository [M]. Earlier sections are unchanged. No decision of §8.30 to §8.36 is changed. Tags as in §8.33.8.

### 8.37.1 — Decisions

| Item | Decision (owner answer) | Tag | Effect |
|---|---|---|---|
| The 74 Feature files the suite does not discover (§8.36.11) | Run them, with the protocol of §8.34.6, on the current code and on every later increment | [OWNER DECISION] | §8.37.2 and §8.37.4 |
| Next step | Finish R3: migrate the five adapters to contract v2, verify, then continue with R4 | [OWNER DECISION] | §8.37.3 |

The tool's automatic review blocked two of the authorized runs; the owner declined the two resulting approval requests; each run was executed later when the review did not block it [M]. Every run listed in §8.37.4 was executed and observed.

### 8.37.2 — R3 boundary on the 74 files

[IMPLEMENTATION FACT]: the R3 tree of §8.36 (before the migration below) gave, for the 74 files, 890 tests, 50 failures, 3 errors, 2 skipped: 0 regressions, 0 newly passing, 0 removed against the pre-R1 tree of §8.36.11, and the 53 failure messages are identical. The statement of §8.36.11 that R3 was not verified against these files is superseded by this result.

### 8.37.3 — Adapter migration

Design [INFERENCE] within the planned completion of §8.36.11 [PROPOSED there]; implemented as follows [IMPLEMENTATION FACT]:

| Element | Change |
|---|---|
| `Search/Contract/DelegatesSearchRequestToSearch` | New trait: `searchRequest()` calls the adapter's own `search()` with the request's query text, limit, and options unchanged (D-04) |
| The five adapters | OpenAlex, Crossref, Semantic Scholar, Consensus, and `FaoStatDeveloperPortalAdapter` implement `ScientificSourceAdapterV2Interface` and use the trait. `search()` is unchanged except that the existing literal result caps became named constants with the same values |
| Descriptors | Licensing fully unresolved (no evidence recorded); retry policy `none()` (the 429 retries inside OpenAlex and Semantic Scholar are adapter-internal); one concurrent request; pagination support and requests per minute undeclared; maximum results per request as each adapter's code already enforces: OpenAlex 10, Crossref 100 (`MAX_FETCH_ROWS`), Semantic Scholar 20, Consensus 20, FAOSTAT 20 |
| `ScientificSourceAdapterDescriptor` | Docblock: a value may also be declared when the adapter's own code establishes it |
| `Registry/ScientificSourceCatalog` | The five production registrations declare `scientific_source_adapter.v2` |
| `MultiSourceScientificSearchOrchestrator` | Passes `max(1, limit)` to v2 requests, because a v2 request requires a limit of at least 1 while `UniversalAnswerOrchestrator` forwards a caller-supplied `limit`. Every migrated adapter already clamped the limit to at least 1, so behaviour is unchanged. v1 adapters still receive the limit unchanged |
| Production path | The orchestrator now calls `searchRequest()` for all five sources; the v1 bridge is no longer used in production but stays available for future v1 adapters |

**Tests** [IMPLEMENTATION FACT]: `ScientificSourceAdapterConformanceTest` now requires every catalog adapter to implement v2 and register v2, and checks each descriptor (source key, contract version, unresolved licensing, `none()` retry policy, one concurrent request, undeclared pagination and rate, a declared result cap, and use of the delegating trait); 5 cases added. `ScientificSourceAdapterContractV2Test`: the trait passes the request through unchanged; 1 case added. `ScientificSourceFailureBoundaryTest`: a limit of 0 reaches a v2 adapter as 1; 1 case added. `ScientificSourceCatalogContractTest`: production registrations are v2; the case "v1 adapter declaring v2" now uses the file's v1 example adapter, because OpenAlex is v2. `UniversalAnswerOrchestratorFeatureTest` (tracked, one of the 74 files) mocks the concrete adapter classes; its mock now answers `searchRequest` as well as `search`, with the same empty outcome. No assertion was changed.

**Acceptance** [IMPLEMENTATION FACT]: the mission's R3 acceptance (the versioned contract is used by production adapters and orchestrator code, and migration behaviour is tested) is met. The R3 status of §8.36.11 (**PARTIAL**) is superseded: R3 is **IMPLEMENTED**, uncommitted.

### 8.37.4 — Tests

Means as in §8.34.6. Comparison per test case; failure messages compared with line numbers normalized.

| Run | Tests | Failures | Errors | Skipped | Against the baseline |
|---|---|---|---|---|---|
| `tests/Unit/Agriculture/Research/Search`, `…/Coexistence`, `tests/Unit/Agriculture/Intelligence/FaoStat`, `UniversalAnswerOrchestratorFeatureTest` | 438 | 7 | 0 | 0 | The 7 failures are the FAOSTAT failures of §8.34.6 |
| Unit | 1,378 | 13 | 16 | 0 | Pre-R1: 0 regressions, 0 newly passing, 0 removed; 135 added (61 by R1 and R2, 74 by R3; §8.36.7 should read "61 by R1 and R2"), all passing; failure messages identical |
| Feature, discovered files (105) | 950 | 84 | 0 | 12 | Pre-R1: 0 regressions, 0 newly passing, 0 removed; failure messages identical |
| Feature, the 74 undiscovered files | 890 | 50 | 3 | 2 | Pre-R1 tree of §8.36.11: 0 regressions, 0 newly passing, 0 removed; failure messages identical; `UniversalAnswerOrchestratorFeatureTest` 6 of 6 passing |

### 8.37.5 — Files

**Changed by this completion** (uncommitted, unstaged) [IMPLEMENTATION FACT]. `…` is `backend/app/Services/Agriculture/Research/Search`. Line endings kept (app LF, tests CRLF); no BOM.

| File | State | SHA-256 |
|---|---|---|
| `…/Contract/DelegatesSearchRequestToSearch.php` | new | `85b603eefcae6a39dee61eb93c0cbbbaa6ed15d35e3657294881df63c1872663` |
| `…/Contract/ScientificSourceAdapterDescriptor.php` | modified (R3 file) | `d43b9d8b846d548101b789400a5fa509abb3db54ee5c70406a46acf7a1cec330` |
| `backend/app/Services/Agriculture/Intelligence/Adapters/Scientific/FaoStat/FaoStatDeveloperPortalAdapter.php` | modified (tracked; no change before) | `bd3c41d7f8dad8352e5061f9a0082e11c8813fffc40eeea45dbee3ca504fecc3` |
| `…/Adapters/OpenAlexScientificSourceAdapter.php` | modified | `016cf5a9f8ea535ffa7a3a735d637a09d4ccd2e3b3463a09f368542a05671780` |
| `…/Adapters/CrossRefScientificSourceAdapter.php` | modified | `92580136f74db7e48bf19f81b7b8c19035f3cf84b5413d88fdf8439df2248628` |
| `…/Adapters/SemanticScholarScientificSourceAdapter.php` | modified | `502fa007e90761dd9dec868a1ae0fa32fbd54411277ef0fbbbb9f8f71a3652e9` |
| `…/Adapters/ConsensusScientificSourceAdapter.php` | modified | `c430b1ad14a76368b1c4cb7297545fc28555d04db047eb84d9772ab99e41e49a` |
| `…/Registry/ScientificSourceCatalog.php` | modified (R1 file) | `483066f39a527133671aef48638e30fcd69672846bc033031bfc110d30eb850a` |
| `…/MultiSourceScientificSearchOrchestrator.php` | modified | `51437456485b4286f4b78f1d40822c8f48124f34ac8ba3705f06ada12d55edf0` |
| `backend/tests/Feature/Agriculture/Intelligence/UniversalAnswerOrchestratorFeatureTest.php` | modified (tracked; no change before) | `38e35b0a0ca7947cd5805c25c7e165d6b542e2f75dd2648850b4e29d669488c5` |
| `backend/tests/Unit/Agriculture/Research/Search/Registry/ScientificSourceCatalogContractTest.php` | modified | `9f390e53d060bc679f59ae0b012741541c81395bd8ca87d8bb378b1b1aa6504f` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificSourceAdapterConformanceTest.php` | modified | `3104542462934dd200df829a083ef90d1ba4439e96245f2e92bd9fee665afa9d` |
| `backend/tests/Unit/Agriculture/Research/Search/Contract/ScientificSourceAdapterContractV2Test.php` | modified | `98e88f51641a930f13fc9b0f383d0a5e9525404a0653a2757513cac46de72d80` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificSourceFailureBoundaryTest.php` | modified | `12fa09d080541cb1a355a66b793bdb047315b19c2860836e66d03dab15c63186` |

Every other file of §8.36.6 keeps its hash. `git status` differs from the state recorded in §8.36.6 only by three paths (the FAOSTAT adapter, `UniversalAnswerOrchestratorFeatureTest`, the new trait); no entry was removed. HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`, 0 staged. `composer.lock`, `phpunit.xml`, `backend/.env`, the 109 register, and the legacy-62 file are unchanged. Before this append this ADR had SHA-256 `e638f7058525e8fc1f42c94e4cda54ba60032cce53e0b4c5b3ad7a14778f997c` (490,930 bytes); it is changed only by appending this section.

### 8.37.6 — Status matrix

| Item | Status on 2026-10-15 |
|---|---|
| Approved decisions (D-04, D-05, D-09, D-10, D-10.9.2, D-10.9.3, and others) | Unchanged |
| R1, R2 | As §8.36.10 |
| R3 (adapter contract v2) | IMPLEMENTED: contract, boundary in both paths, five adapters on v2; retry policy defined but not wired; licensing of all five UNRESOLVED; uncommitted; tests RUN on all 179 Feature files and the Unit suite |
| Feature discovery gap (§8.36.11) | Cause [UNRESOLVED]; mitigated by running the 74 files explicitly |
| Live probes and source activation | NOT AUTHORIZED (§8.36.1) |
| B-1 revision | OPEN, UNAPPROVED (§8.36.2) |
| R4 to R8 | NOT STARTED |
| P1-EXIT-06 | NOT CLOSED |
| Legacy-62 register | RETAINED; manifest empty |
| Phase 1 | BLOCKED |
| Phase 2 | NOT STARTED |

## 8.38 — R4: provenance, licensing and error integrity (2026-10-15)

Before this append this ADR had SHA-256 `14d5b529f2a0d9426d080bbce4f872ebf11cdd2de32757cb17ab6907ad34fed5` (500,647 bytes). Nothing above this line was changed.

### 8.38.1 Owner decisions of 2026-10-15 (third set)

| Question | Decision |
|---|---|
| Docker Desktop was not running, so no test could run | The owner started Docker Desktop [OWNER DECISION]. |
| Auto-review blocked the runner script in `%TEMP%\wsa-r1`, and the owner skipped the approval card | Run the same `docker run` command inline in the shell: `--network none`, read-only source and vendor mounts, tmpfs storage, an empty `.env` [OWNER DECISION]. This is the §8.34.6 protocol; only the wrapper changed. |
| Licensing of every source is unresolved, yet the Results List and Viewer showed provider abstracts | "Hide abstracts at the user boundary unless excerpt_display is permitted (changes the Results List and Viewer)" [OWNER DECISION]. |

### 8.38.2 Findings before the change [V]

These were read from the code as it stood after §8.37, before any R4 edit.

1. `ScientificSearchResult` had no retrieval time, no publication date finer than a year, no licence, no access method, no adapter and no record of transformations. The original record survived only in `rawMetadata`.
2. The Consensus and Semantic Scholar normalizers create `consensus:md5(title|year)` and `semantic_scholar:md5(title|year)` when the provider gives no DOI, identifier or URL. `ScientificUserPresentation::isCatalogIdentity()` accepts these prefixes, so they are part of the stable result identity (mission §5). They were kept, and R4 discloses them as derived.
3. Semantic Scholar keys a result by DOI ahead of `paperId`, so the provider's own identifier was reachable only through `rawMetadata`.
4. `CrossRefScientificClient::abstractFromWork()` uses the subtitle when there is no abstract. Nothing disclosed this.
5. FAOSTAT's canonical URL is built by `FaoStatDeveloperPortalAdapter::reconstructQueryUrl()` from dimension codes, and its identifier is composed from codes.
6. When the deduplicator merged two results it kept only the first source's `sourceIdentifier` and `canonicalUrl`. The other source's identity survived only inside `rawMetadata`.
7. Every evidence item's `retrieved_at` was the time Stage 4 validation ran (`now()` in `AgriculturalScientificValidationService::validate()`), not the time the provider was called.
8. The Stage 3 status is `search_completed` whenever at least one source succeeded, even when others failed. Failed sources appeared only in the diagnostic `scientific_search` payload. `user_presentation`, the only contract the Home, Crop and Viewer pages render, had no source coverage at all.
9. All five adapters declare licensing unresolved (§8.37), but the Results List and Viewer displayed provider abstracts.

### 8.38.3 Design

**Per-record provenance.** A new readonly class, `Search\Provenance\ScientificSourceRecordProvenance`, holds one record per contributing source record. Null means unknown. Its fields are:

| Field | Filled by | Content |
|---|---|---|
| `sourceKey` | normalizer or adapter | canonical source key |
| `originalIdentifier`, `originalIdentifierField` | normalizer | the identifier exactly as the provider returned it, and its field (`id`, `DOI`, `paperId`) |
| `recordUrl` | normalizer | the provider's own record URL as returned; http or https only |
| `publishedAt` | normalizer | publication date as returned, at its own precision (`2021-03-04`, `2020-05`) |
| `identifierBasis` | normalizer or adapter | `provider_identifier`, `doi`, `url`, `derived_title_year_hash`, `derived_dimension_codes` |
| `canonicalUrlBasis` | normalizer or adapter | `provider_url`, `provider_record_identifier`, `doi_resolver`, `provider_open_access_pdf`, `constructed_from_codes` |
| `transformations` | normalizer or adapter | only transformations that changed a value, for example `doi_lowercased`, `abstract_taken_from_subtitle`, `abstract_reconstructed_from_inverted_index` |
| `retrievedAt` | orchestrator (FAOSTAT also records its own) | UTC ISO 8601 time the adapter returned |
| `adapterClass`, `contractVersion`, `accessFamily` | orchestrator | adapter class; v2 from the descriptor, otherwise v1; access family from the catalog registration |
| `licensing` | orchestrator | the v2 descriptor's declaration. A v1 adapter, or a missing declaration, is unresolved |

**Search results and normalization.**
- `ScientificSearchResult` gains a `provenance` list (an optional trailing constructor argument), `primaryProvenance()` and `withRetrievalContext()`. `toArray()` adds `provenance`, and every existing field is unchanged.
- The four scholarly normalizers fill the provider-side fields. The FAOSTAT adapter discloses its identifier and URL as derived from codes, and keeps its own `retrieved_at`.

**Orchestrator stamping.**
- The orchestrator stamps each returned result as soon as the adapter call returns, on both the sequential and the process-pool paths.
- Stamping fills only unknown fields and runs outside the failure-containment `try`. A defect in `descriptor()` therefore propagates (the R3 boundary, §8.36).

**Deduplication.** The deduplicator keeps every contributing record. A repeat of the same source, identifier and URL from another query variant keeps the first retrieval.

**Validation.** An evidence item's `retrieved_at` is the source record's retrieval time. Only when no record exists does it fall back to the validation time. Provenance gains `retrieved_at_basis` (`source_retrieval` or `validation_time`) and `records`.

**User boundary (`ScientificUserPresentation`).**
- Results rows and sources rows carry a `provenance` disclosure:
  - source, `found_by_sources`, `retrieved_at` and its basis;
  - `licensing_status`: `unresolved` when any record is fully unresolved or there is no record, otherwise `declared`;
  - `usage` for each of the six usages: the most restrictive status across records, in the order prohibited, unresolved, conditional, permitted;
  - the records themselves.
- Sources rows find their evidence through `evidence_id`, so `AnswerComposer` is unchanged.
- An abstract is shown only when `usage.excerpt_display` is `permitted`. Otherwise `abstract` is null and `abstract_withheld_reason` is `excerpt_display_<status>`.
- `source_coverage` reports:
  - whether coverage is complete;
  - the search status;
  - responded, failed and unreached sources;
  - failure categories for each source that did not respond;
  - `notice_code`: `partial_source_coverage`, `no_sources_searched`, or null.
- `user_notice_code` is unchanged.
- `AgriculturalResearchAgent` passes the search report to `fromSynthesis()` on all three response paths.

**Unchanged:**
- Every `sourceIdentifier` value (including the derived keys), the deduplication keys and the canonical result IDs.
- The Stage 3 status strings, ranking, `RelevanceGate`, `AccuracyGate`, `AnswerComposer`, confidence and evidence sufficiency.
- `ScientificSearchQueryBuilder` (D-04), CSQ authority, and Home/Crop isolation.
- Provider requests; no field was added to the Semantic Scholar request.
- Source-file preservation and the feedback contracts.
- PDF links are still shown. They are citation linking, and the owner decision covered abstracts only.

### 8.38.4 Effect on the frontend [V]

The frontend reads `user_presentation.results[].abstract` (`frontend/src/public/scientificUserPresentation.ts`, line 114). It already handles a null abstract: `researchContentCapability.ts` falls back to the PDF or the original source. No frontend file was changed.

Every source is still unresolved, so the Results List and Viewer will show no abstract until an adapter declares excerpt display permitted with evidence. The new fields `provenance`, `abstract_withheld_reason` and `source_coverage` are not rendered yet.

### 8.38.5 Tests

`tests/Unit/Agriculture/Research/Search/ScientificResultProvenanceTest.php` is new, with 15 tests. They show that:

- An OpenAlex record keeps the provider identifier, record URL and publication date, and names each transformation.
- Missing Crossref metadata stays unknown. No identifier, URL, date or basis is invented, and licensing is fully unresolved.
- A Crossref record keeps its date at the precision given, keeps the DOI exactly as Crossref returned it, and discloses a subtitle used as the abstract.
- A derived Consensus identifier is disclosed as derived. Semantic Scholar keeps `paperId` even when the DOI keys the result.
- FAOSTAT's identifier and URL are disclosed as derived from codes, and its own retrieval time is kept.
- The orchestrator stamps retrieval time, adapter, contract version, access family and unresolved licensing for both v2 and v1 adapters. Unknown licensing permits none of the six usages, and the stamp is visible in `toArray()`.
- A failed source is reported while the other source's provenance survives. `sourceCoverage()` marks coverage partial and names the failure category.
- Coverage with no selected source is not complete.
- Deduplication keeps every source's record, and a repeat keeps its first retrieval.
- Validation carries the source retrieval time and records into evidence, and falls back to `validation_time` when no record exists.
- Presentation reports unresolved licensing, withholds the abstract with its reason, and leaves the result ID unchanged.
- An abstract is shown only when every contributing source permits excerpt display. A prohibited record withholds it.
- A result without an abstract reports nothing withheld.
- The most restrictive declared usage across records is reported.
- A result without any provenance is unresolved, and nothing is invented.

`tests/Feature/ScientificUserPresentationContractTest.php` changed to follow the owner decision:
- `test_results_pass_abstract_and_pdf_without_changing_identity_or_primary_answer` and `test_unsafe_pdf_url_is_not_forwarded` now give their evidence a provenance record that permits excerpt display, through a new helper, `excerptPermittedRecord()`.
- Their assertions are unchanged.

### 8.38.6 Verification [V]

All runs used the isolated Docker protocol: `--network none`, read-only mounts, HEAD `f2025315…`, with nothing staged.

| Run | Result |
|---|---|
| `r4-new`: the R4 test and the presentation contract test | 43 tests, 216 assertions, OK |
| `r4-unit`: `--testsuite Unit` | 1,393 tests, 13 failures, 16 errors. The 29 failing cases are exactly the 29 failing in `base-unit`. 0 regressions. 150 added since before R1 (135 by R1 to R3, 15 by R4), all passing. Failure messages are identical to R3 (`r3b-unit`) |
| `r4-feature`: `--testsuite Feature` (105 files) | 950 tests, 84 failures, 12 skipped. 0 regressions. Failure messages identical to `base-feature` and `r3b-feature` |
| `r4-feature-extra`: the 74 undiscovered Feature files (§8.36.11) | 890 tests, 50 failures, 3 errors, 2 skipped. 0 regressions. The 53 failure messages are identical to `pre-feature-extra` and `r3b-feature-extra` |

**Correction to §8.37.** §8.37 called the Unit failure messages "identical". The assertion text is identical. However, the stack trace of one baseline failure, `FaoStatPhase3FixTest::test_openalex_agri_domain_does_not_reach_faostat_and_qcl_is_used`, has had two more frames since R3: `DelegatesSearchRequestToSearch` and one orchestrator frame. They come from the FAOSTAT adapter's v2 call path. R4's frames are identical to R3's.

### 8.38.7 Files (SHA-256 prefix after R4)

| File | State | SHA-256 |
|---|---|---|
| `backend/app/Services/Agriculture/Research/Search/Provenance/ScientificSourceRecordProvenance.php` | new | `14eec57a` |
| `backend/app/Services/Agriculture/Research/Search/ScientificSearchResult.php` | modified | `78cefd79` |
| `backend/app/Services/Agriculture/Research/Search/ScientificResultNormalizer.php` | modified | `ceeba8a9` |
| `backend/app/Services/Agriculture/Research/Search/ScientificResultDeduplicator.php` | modified | `0555d7de` |
| `backend/app/Services/Agriculture/Research/Search/MultiSourceScientificSearchOrchestrator.php` | modified (R2, R3 and R4) | `0d52172f` |
| `backend/app/Services/Agriculture/Research/Validation/AgriculturalScientificValidationService.php` | modified | `f01edb83` |
| `backend/app/Services/Agriculture/Research/Synthesis/ScientificUserPresentation.php` | modified | `f907f44d` |
| `backend/app/Services/Agriculture/Research/AgriculturalResearchAgent.php` | modified | `b6971bb9` |
| `backend/app/Services/Agriculture/Intelligence/Adapters/Scientific/FaoStat/FaoStatDeveloperPortalAdapter.php` | modified (R3 and R4) | `53c2cdcc` |
| `backend/tests/Unit/Agriculture/Research/Search/ScientificResultProvenanceTest.php` | new | `d9cb612e` |
| `backend/tests/Feature/ScientificUserPresentationContractTest.php` | modified | `9f384924` |

Line endings follow each file's previous working-tree form. The ADR records no secret value.

### 8.38.8 Acceptance

The acceptance criterion reads: "Provenance and licensing status survive the production path and are represented honestly at the user-facing boundary." The criterion is met for the scholarly and FAOSTAT production paths, verified by the tests in §8.38.5 and the runs in §8.38.6:

- Provenance survives normalization, the orchestrator, deduplication and validation.
- Licensing status, retrieval basis and source coverage reach `user_presentation`.
- Unknown licensing permits nothing, and a derived identifier or URL names its basis.

### 8.38.9 Residuals and open items

1. Records dropped during normalization (no title) are not counted for each adapter. Only a response in which every record was dropped is reported, as `malformed_or_unusable_results`.
2. The diagnostic payload keys still carry provider text: `scientific_search.results[].abstract`, `validated_evidence[].evidence_text` and `rejected_evidence`. They are not the render contract, but the API returns them. **Owner decision needed** if they must also follow the excerpt rule.
3. `ScientificKnowledgePersistenceService` still persists evidence while metadata storage and full-text storage are unresolved. R4 did not change it, and the database was not touched. **Owner decision needed.**
4. Synthesized answers can restate evidence text. `AnswerComposer` is preserved (mission §5) and was not changed.
5. Licensing remains unresolved for all five sources. Resolving it requires reading the providers' terms, which needs network access, and that is not authorized.
6. The Semantic Scholar request does not ask for `publicationDate`, so that date stays null unless the provider returns it. Consensus records carry no provider identifier.
7. `FaoStatDomainVerifier` builds results without a provenance record. It is a verification path, not a Stage 3 search.
8. Some evidence copies under `%TEMP%\wsa-r1` disappeared between steps although this session issued no delete command:
   - six of the eight pre-R4 copies in `r4-pre`;
   - part of `snap-preR1`.

   Every affected original is recoverable from git HEAD, because those files were unmodified when they were copied. The baseline JUnit files are intact. The likely cause is temporary-file cleanup outside this session. Keeping evidence outside `%TEMP%` is advisable.
9. Why `--testsuite Feature` misses 74 files (§8.36.11) has not been investigated.
10. B-1 remains OPEN and UNAPPROVED.

### 8.38.10 State and status [V]

HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0`, with nothing staged. `git status --porcelain --untracked-files=all` lists 1,195 entries: the 1,186 of §8.37 plus the 9 R4 paths (2 new, 7 newly modified). The plain `git status --porcelain` count of 84 collapses untracked directories; nothing was removed.

These files are unchanged (SHA-256 prefix):

| File | SHA-256 |
|---|---|
| `backend/composer.lock` | `D6EE17A75BF1DA5B` |
| `backend/phpunit.xml` | `1EC076C544341F95` |
| `backend/.env` (content not read) | `16B96F1E9230C8DA` |
| The 109 register | `783035186579AA55` |
| The legacy-62 register | `9170257A5FEA2D1F` |

No commit, push, stage, deletion, network access, live probe, database access or dependency installation took place.

| Increment | Status |
|---|---|
| R1, R2, R3 | As §8.37 |
| R4 (provenance, licensing, error integrity) | IMPLEMENTED: per-record provenance through normalization, orchestrator, deduplication, validation and presentation; licensing status and source coverage at the user boundary; abstracts withheld unless excerpt display is permitted (owner decision); uncommitted; tests RUN on the Unit suite and all 179 Feature files |
| R5 (source-family adapters), R6 (Onboarding Contract and 109 reconciliation), R7 (shared logic and isolation), R8 (onboarding automation) | NOT STARTED |

## 8.39 — R5: reusable source-family adapters (2026-10-15)

Before this append this ADR had SHA-256 `6d8eea5f98e29c05d9586821c63444d4bf650b69d66e2c50913d3b2ab4e75ca4` (517,582 bytes). No earlier content was changed.

### 8.39.1 Need established from evidence

The family need was taken from `2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json` (documented modalities of all 109 seats), not from the candidate list in the mission:

| Documented modality | Seats | Decision |
|---|---|---|
| `api` (REST) | 13 (for example G2-05 J-STAGE WebAPI, G5-04 GBIF, G6-20 BHL, the ScienceDirect seats) | REST/JSON family implemented |
| `oai_pmh` | 2 documented (G2-15, G6-20); more seats run on OJS, EPrints, DergiPark or Zenodo platforms, but that is inference, not evidence | OAI-PMH family implemented as a harvester |
| `bulk_download` / `static_download` | 9 / 20 | Not implemented: needs storage and ingestion, which means a database or storage decision (owner) |
| `rss` | 3 | Not implemented: a feed takes no query, so it cannot serve the search operation |

No seat is identity-verified, endpoint-tested or integration-verified in that artifact, so no real source was registered, profiled or activated.

### 8.39.2 Design

- **Shared outer contract.** `ScientificSourceFamilyProfile` (per-source configuration: key, display name, access family, licensing, export) and `ScientificSourceFamilyAdapter` (one class for every source of a family; static `accessFamily()`, `profile()`).
- **Registration.** `ScientificSourceRegistration` gains an optional `familyProfile`. A family adapter requires a profile, and only a family adapter takes one; the profile's source key and family, the adapter's family and the registration's `accessFamily` must agree. `toArray()` adds `family_profile` (null for the five legacy sources).
- **Registry.** `ScientificSourceAdapterRegistry` passes the profile as the `profile` constructor argument. Nothing else in shared orchestration, selection, ranking, validation or synthesis changed.
- **Shared record builder.** `ScientificSourceFamilyRecord` builds the result and its R4 provenance record for every family: DOI from a bare, `doi:` or resolver value; a provider URL only when it is http(s); identifier basis doi > provider > url; canonical URL basis provider URL > DOI resolver; every change is named as a transformation; licensing comes from the profile, so it stays unresolved unless declared with evidence.
- **REST/JSON family.** `RestJsonSourceProfile` (https endpoint without credentials, query or fragment; query and limit parameter names; dot paths for identifier and title (required), abstract, doi, url, published, year, authors and journal; non-secret fixed parameters; optional `RestJsonCredential`, which names a config key and never holds the value). `RestJsonScientificSourceAdapter` implements contract v2: the query text is sent unchanged (D-04); the limit is capped at the declared maximum; a missing credential is `missing_api_key` and sends nothing; a 429 response is `rate_limited`; any other non-2xx is `http_error`; a result list missing from the declared path is `malformed_or_unusable_results` (FAILED, not empty); expected transport failures become outcomes; any other throwable propagates.
- **OAI-PMH family.** `OaiPmhSourceProfile` (https base URL, optional setSpec, `oai_dc` only, because OAI-PMH 2.0 requires every repository to disseminate it). `OaiPmhHarvester` implements ListRecords with from/until (validated granularity) or an exclusive resumption token, and returns an `OaiPmhHarvestPage`:
  - deleted records are listed and records without a title are counted;
  - `noRecordsMatch` is an empty, complete list;
  - other OAI-PMH errors are REQUEST_REJECTED, with the codes kept;
  - 503 with Retry-After is RATE_LIMITED;
  - a response containing a DOCTYPE is refused as malformed, so no external or recursive entity is processed;
  - `dc:rights` stays in raw metadata and is not treated as a licence decision.

  The harvester is not a search adapter: OAI-PMH defines no query verb, so an OAI-PMH source cannot be registered for the search operation.

### 8.39.3 Tests (no network)

`tests/Unit/Agriculture/Research/Search/Family/RestJsonSourceFamilyTest.php` and `OaiPmhSourceFamilyTest.php` add 47 tests. Fixtures are in `tests/Fixtures/ScientificSourceFamilies/`; they are protocol-shaped documents on `example.org`, not copies of any provider's responses. The tests cover:

- field mapping and provenance;
- missing metadata staying null;
- credential handling, with no secret in any outcome or export;
- each failure category and its retryability;
- defect propagation;
- profile and registration validation;
- registry construction of two family sources from one class;
- an end-to-end run of two REST family sources through the production orchestrator, selector and registry, with a third, NOT_VERIFIED source denied by the capability policy.

### 8.39.4 Verification (isolated Docker as in §8.34.6: `--network none`, read-only mounts, run inline)

| Run | Result | Against R4 (§8.38) |
|---|---|---|
| Focused (Family, Registry, Contract, conformance, failure boundary, provenance) | 197 tests, all OK | — |
| Unit suite | 1,440 tests; 29 not passing | 0 regressions; 47 added, all passing; failure messages identical |
| Feature suite | 950 tests; 84 not passing | 0 regressions; messages identical |
| 74 undiscovered Feature files (run twice) | 890 tests; 53 not passing | 0 regressions; messages identical |

Protected files are unchanged: composer.lock, phpunit.xml, .env and the 109 register (`783035186579aa55…`). HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0`, nothing is staged, and there was no commit, push, deletion, network access or database access.

### 8.39.5 Files

- New: `app/Services/Agriculture/Research/Search/Family/` (ScientificSourceFamilyProfile, ScientificSourceFamilyAdapter, ScientificSourceFamilyRecord, `Rest/` RestJsonSourceProfile, RestJsonCredential, RestJsonScientificSourceAdapter, `OaiPmh/` OaiPmhSourceProfile, OaiPmhHarvester, OaiPmhHarvestPage); the two test files; five fixtures.
- Changed: `Registry/ScientificSourceRegistration.php` (optional family profile, validation, export) and `ScientificSourceAdapterRegistry.php` (passes the profile).

### 8.39.6 Acceptance

"A new source using an existing family can be integrated without rewriting shared scientific-search logic": met for REST/JSON sources. A new source is a profile plus a registration, demonstrated end to end with two sources sharing one adapter class.

For OAI-PMH the reusable harvester exists, but it reaches users only through a local index, which is not built. The test registrations use the only existing lifecycle, `LEGACY_STAGE3`, because non-legacy lifecycle states belong to the R6 Onboarding Contract.

### 8.39.7 Residuals and owner decisions

1. **Harvested-record storage and index (owner decision).** OAI-PMH, bulk and static families need storage to serve search, and therefore a database or storage decision.
2. **Live verification (authorization).** No real REST or OAI-PMH profile exists, and no response schema of a real provider was assumed. Profiles for seats such as G2-05, G5-04, G6-20 and G2-15 require network authorization to verify their endpoints.
3. **Rate limits are declared but not enforced** (`requestsPerMinute` is exported only), and the retry policy is still not wired (as in §8.36).
4. **Lifecycle for non-legacy registrations** is deferred to R6.

### 8.39.8 Status

| Increment | Status |
|---|---|
| R1 to R4 | As §8.38 |
| R5 (source-family adapters) | IMPLEMENTED: REST/JSON search family (configuration-driven, in the production registry path) and OAI-PMH harvesting family; no real source profiled or activated; bulk/static and RSS not built (§8.39.1); uncommitted; tests RUN on the Unit suite and all 179 Feature files |
| R6 (Onboarding Contract and 109 reconciliation), R7 (shared logic and isolation), R8 (onboarding automation) | NOT STARTED |

## 8.40 — R6: Source Onboarding Contract, reconciliation of the 109 seats, and the OAI-PMH and bulk/static storage and indexing lifecycle (2026-10-15)

Before this append this ADR had SHA-256 `ba27a968d87c79832abbfd26ab8d3544b462a0bc72a5f8c974ab22da9ffb2dbd` (525,658 bytes). No earlier content was changed.

**Source.** On 2026-10-15 at 19:21 local time the user issued "WSA-Enterprise — Unified Autonomous Engineering Execution Mandate". It authorizes implementing R6 as typed code, reconciling all 109 entries, and building a reusable OAI-PMH and bulk/static storage and indexing lifecycle. It forbids network access, live sources, migrations, new infrastructure, dependency changes, Git operations and deletions. This section records what was done under that mandate. It does not change D-04, D-09, D-10.x or any earlier decision.

### 8.40.1 Evidence, decisions and assumptions

| Kind | Record |
|---|---|
| Evidence | The membership register (`783035186579aa55…`) and `2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json`, which declares the same register hash. Both were read; neither was changed. |
| Decision | R6 implementation is now authorized by the mandate. §8.11.8 had recorded it as not authorized at that time. The contract stays ORCHESTRATION + AUDIT only (§8.11). |
| Decision | The membership-register PHP loader (D-10.12) stays unimplemented. The reconciler is pure: it takes decoded inputs and the register hash. Only tests read the files. No runtime code loads the register. |
| Decision | No binding path was opened. `ScientificSourceRegistration` still rejects any `adr_id` (D-10.11). |
| Decision | Documentary evidence can make a dimension at most PARTIALLY_EVIDENCED. Documented licence text is evidence, not permission. Every seat keeps a fully unresolved `ScientificSourceLicensingDeclaration`. |
| Assumption | A seat is UNAVAILABLE only when a fetch failure in the evidence names the seat's own candidate URL. This means the site could not be loaded during the 2026-10-14 review; it is not a permanent fact. |
| Assumption | Candidate access families are derived only from documented modalities: `api` → REST_API, `oai_pmh` → OAI_PMH, `rss`/`atom` → RSS_ATOM, `bulk_download` → BULK_DATASET, `static_download` → STATIC_DATASET. Platform inference (OJS, EPrints, DergiPark) is not used. |

### 8.40.2 Source Onboarding Contract (typed)

Namespace `App\Services\Agriculture\Research\Onboarding`:

- `ScientificSourceOnboardingContract` holds one decision for one `adr_id`. Its fields are exactly the frozen §8.11.3 matrix (`toArray()` keys are pinned by a test). Each field reuses the owning authority's type:
  - `AdrMembershipId` and `CanonicalSourceIdentityId` for identity;
  - Cap v2 `CapabilityState` per operation, and `CapabilityAccessMethod`;
  - `PathEligibilityState` and `PathStatus`;
  - `ScientificSourceLicensingDeclaration` for `license_state` and `reuse_state`.
- Invariants:
  - CONDITIONALLY_ELIGIBLE, ELIGIBLE and ACTIVE require a canonical identity, identity evidence, an IC reference, a Capability decision, a Path decision with eligibility, a Projection identity, and a licence that permits retrieval.
  - ELIGIBLE and ACTIVE also require Path ELIGIBLE and search VERIFIED.
  - CONDITIONALLY_ELIGIBLE accepts Path CONDITIONAL and search PARTIAL.
  - ACTIVE requires the runtime sourceKey it is bound to.
  - A Cap state other than UNVERIFIED needs a Capability decision identity.
  - A Path status needs a `path_id`.
  - Only an ACTIVE seat may be anything other than NOT_SELECTABLE.
  - A decision cannot supersede itself.
- `SeatActivationState` is the D-10 vocabulary: NOT_READY, UNVERIFIED, CONDITIONALLY_ELIGIBLE, ELIGIBLE, ACTIVE, SUSPENDED, DEFERRED. It is a separate namespace from Path, Cap and FAOSTAT (§8.11.4).
- `OnboardingEvidenceStatus` is the reconciliation vocabulary: VERIFIED, PARTIALLY_EVIDENCED, UNVERIFIED, UNAVAILABLE, BLOCKED. A VERIFIED dimension needs an evidence reference, and a seat with blockers cannot be VERIFIED.
- `OnboardingBlocker` defines 13 codes, each naming the authority or gate that must act.
- `SeatReconciliationEntry` requires exactly 26 dimensions:
  - identity, membership, family, adapter compatibility;
  - search, pagination, filtering, full text, bulk/harvest;
  - query mapping, authentication, credential settings, access method;
  - licence, retention, indexing, excerpt display, user-facing use;
  - provenance, record identity, dedupe, ingestion, freshness, deletion/withdrawal;
  - implementation, test coverage.
- `ScientificSourceOnboardingReconciler` and `ScientificSourceOnboardingReconciliation` compute the entries, the integrity findings and the summary.

### 8.40.3 Reconciliation of the 109 seats

The artifact is `docs/architecture/science-source-expansion/2026-10-15-R6-SOURCE-ONBOARDING-RECONCILIATION.v1.json` (SHA-256 `ef1e1e5a9fb5029fb6138e90027630da804925b8af0ecc5a75b14159fa114a8c`, 1,269,784 bytes). It was exported by the reconciliation test in the isolated run (`WSA_RECONCILIATION_EXPORT`), is marked "OFFLINE - NOT PERSISTED - OWNER VERIFICATION PENDING", and is not a membership change.

| Seat status | Seats | Rule |
|---|---|---|
| VERIFIED | 0 | No identity binding, endpoint test or accepted licence exists |
| PARTIALLY_EVIDENCED | 63 | Candidate identity evidenced and an access method documented |
| UNVERIFIED | 17 | Identity not established or no access documented |
| UNAVAILABLE | 13 | The candidate site failed to load during the review |
| BLOCKED | 16 | Ambiguous identity; owner decision required |

Activation state: 93 NOT_READY and 16 DEFERRED (the ambiguous seats); none eligible.

| Blocker | Seats |
|---|---|
| Identity not verified, integration unclassified, endpoint not tested, licence unresolved, Capability unverified, onboarding decision not accepted | 109 each |
| No machine access documented | 73 |
| Evidence fetch failures | 32 |
| Local-index persistence not authorized (OAI-PMH, bulk or static only) | 21 |
| Identity ambiguous | 16 |
| Credentials required | 5 |
| Identity not established | 3 |
| No family implementation (RSS only) | 2 |

Candidate families: REST_API 13, STATIC_DATASET 19, BULK_DATASET 9, RSS_ATOM 3, OAI_PMH 2. Dual pairs stay 10 independent seats, each with its own decision id and no canonical identity.

### 8.40.4 Integrity audit (registry, capability, profile, query compilation, licensing)

No ERROR findings. All of the following hold:

- the evidence hash matches the register;
- seat count, group counts and dual pairs are consistent;
- there are no duplicate seats or evidence records;
- there is no SAME_AS;
- no registration carries an `adr_id`;
- no active query profile lacks a registration;
- every registered source has a query profile;
- no family implementation class is dead.

| Finding | Severity | Detail |
|---|---|---|
| `stub_declares_unverified_query_capabilities` | WARNING ×5 | The inactive profile stubs agris, gbif, pubmed, europe_pmc and agricola declare identifier fields and constraints that no evidence or endpoint test supports. For example, `agris` declares natural-language search with `agris_id`, while the AGRIS evidence documents only an opt-in ODS bulk dump. The stubs are inactive and unregistered, so nothing executes them. They were not changed. |
| `profile_seat_name_correspondence` | INFO ×3 | agris ↔ G1-01, agricola ↔ G5-02, gbif ↔ G5-04. A name match is not SAME_AS and not a binding. |
| `registered_profile_inactive` | INFO | `consensus` is registered but its query profile is inactive. |
| `legacy_operational_basis` | INFO ×5 | The five registrations rest on the legacy basis for search only. Other operations are undeclared and therefore denied. |
| `family_without_implementation` | INFO | RSS_ATOM. A feed takes no query, would need the same local index, and only 2 seats document RSS alone. |

Known gaps confirmed rather than introduced:

- FAOSTAT-specific orchestrator branches (R7);
- licensing of the five legacy sources unresolved (§8.37);
- `requestsPerMinute` declared but not enforced, and the retry policy not wired (§8.39.7).

### 8.40.5 Storage gate (§8.3) and the lifecycle built

Existing facilities:

- no search engine dependency;
- no migration for source records;
- `knowledge_embeddings` is an organization-scoped pgvector store with no generations, tombstones or source identity, so it is unsuitable.

| Alternative for persistent records | Needs | Disposition |
|---|---|---|
| New tables in the existing database | A schema and a migration (D-10.12 says not authorized; the mandate forbids migrations) | BLOCKED: owner authorization of a schema |
| Reuse `knowledge_embeddings` | Nothing new | Rejected: wrong model and wrong scope |
| A search engine (Meilisearch, Elasticsearch, Typesense) | New infrastructure and a dependency | BLOCKED: new infrastructure gate |
| Files under local storage | An operational durability and concurrency decision | Not chosen without the owner |

Built without new infrastructure. Every independent component sits behind a storage port:

- `ScientificRecordStore`, the port, and `InMemoryScientificRecordStore`, the reference implementation, which is not bound in the container. They provide:
  - generations, with staging, atomic commit and discard;
  - identity `sourceKey|original identifier`;
  - content-hash idempotency that ignores retrieval time;
  - stale-update protection by datestamp, for both upserts and tombstones;
  - DELETED and WITHDRAWN tombstones that keep provenance;
  - SNAPSHOT commits that withdraw absent records;
  - a checkpoint committed with each generation.
- `LocalRecordRetentionPolicy`:
  - nothing is ingested unless retrieval is PERMITTED;
  - nothing is stored unless metadata storage is PERMITTED (CONDITIONAL counts as not permitted);
  - abstracts and raw payloads are kept only if full-text storage is PERMITTED, and the omission is recorded as a provenance transformation;
  - display stays gated by `excerpt_display` in `ScientificUserPresentation` (§8.37).
- `LocalIndexIngestionService` checks the licence before any request or file read.
  - OAI-PMH: each page is committed with its checkpoint, and resumes at the token. A `badResumptionToken` restarts the window once, which is idempotent. The high-water mark advances only when a list completes, and the next `from` uses the repository granularity (new `OaiPmhSourceProfile::$datestampGranularity`, default day).
  - Bulk/static: only a complete snapshot is committed. Malformed lines, an over-long line, too many records, a missing header or an unreadable file leave the active generation unchanged.
- Bulk/static family: `BulkDatasetSourceProfile` (JSON Lines dot paths or CSV columns, withdrawn flag, bounded `maxRecords` and `maxLineBytes`) and `BulkDatasetReader`, which reads local files only and refuses URLs and stream wrappers. It processes one bounded line at a time, de-duplicates by identifier, and returns a content SHA-256 for complete snapshots only.
- `LocalScientificIndex` (`local_scientific_index.v1`) holds active records only. Matching is conjunctive, so the index never widens the compiled query. Ranking is title-weighted with a stable tie-break and is not a relevance verdict. Tokens are Unicode letter and digit runs. The index is rebuildable from the store, and a test proves that a rebuild returns the same results as incremental maintenance.
- `LocalIndexScientificSourceAdapter` is a contract-v2 family adapter for OAI-PMH, bulk and static sources. An index that was never built is UNAVAILABLE (`local_index_not_built`), not EMPTY. It is not registered for any production source.

Changes to R5 code (uncommitted, from §8.39):

- `ScientificSourceFamilyAdapter::accessFamily()` became `servesAccessFamily(family)`, so that one local-index adapter can serve three families; registration validation and the REST adapter follow.
- The REST adapter's scalar helper moved to `ScientificSourceFamilyRecord::scalar()`.
- `OaiPmhSourceProfile` gained the datestamp granularity.

### 8.40.6 Search-architecture integration

- The local adapter receives the compiled query unchanged, like a remote adapter: there is no keyword reduction and no source-specific branch.
- An end-to-end test runs a bulk source through the production registry, selector and orchestrator without any HTTP request.
- Selection roles (Home/Crop), evidence sufficiency, confidence thresholds, deduplication and ranking code were not changed.
- Stored results carry the R4 provenance record (adapter, contract version, access family, licensing, identifier basis), so the existing deduplicator and the presentation gate apply.

### 8.40.7 Verification (isolated Docker as in §8.34.6: `--network none`, read-only mounts, run inline)

| Run | Result | Against R5 (§8.39) |
|---|---|---|
| Focused (Onboarding, LocalIndex, Family, Registry) | 138 tests, 4,864 assertions, all OK | — |
| Unit suite | 1,497 tests; 29 not passing | 0 regressions; 57 added, all passing; 29/29 failure messages identical |
| Feature suite | 950 tests; 84 not passing | 0 regressions; 84/84 messages identical |
| 74 undiscovered Feature files | 890 tests; 53 not passing | 0 regressions; 53/53 messages identical |

New tests (57):

- `ScientificSourceOnboardingContractTest` (28);
- `ScientificSourceOnboardingReconciliationTest` (10; its expectations are recomputed from the evidence file, not taken from the reconciler);
- `LocalIndexLifecycleTest` (14);
- `LocalIndexSearchIntegrationTest` (5).

There are four new fixtures. They are synthetic, on `example.org`, with no provider data.

**Measurement.** No quality or performance measurement was made. The local index did not exist before, so there is no valid baseline, and no improvement is claimed.

Protected files are unchanged:

- composer.lock `D6EE17A75BF1DA5B`, composer.json `DE302DFA539371AA`, phpunit.xml `1EC076C544341F95`, .env `16B96F1E9230C8DA`;
- the 109 register `783035186579AA55`;
- the legacy-62 register `9170257A5FEA2D1F`.

HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0` on branch `phase-18-m18-ai-marketing-communications`. Nothing is staged, and no file was deleted. There was no commit, push, pull, branch change, network access, live-source access, migration, dependency change or database access.

### 8.40.8 Blockers

| Operation | Reason | Evidence | Safe alternative completed | Authorization required |
|---|---|---|---|---|
| Persistent record store and index | A schema or migration, or new infrastructure, is not authorized | D-10.12; mandate §5.3 | Port, in-memory reference, lifecycle and tests (§8.40.5) | Owner choice among the §8.40.5 alternatives, then a migration or infrastructure gate |
| Real profiles, endpoint verification and activation of any seat | Live access is forbidden; identity is unverified and licences are unresolved | §8.40.3 | Offline reconciliation, with per-seat blockers and next actions | A network gate, an identity binding, licence acceptance, and Capability and Path decisions for each `adr_id` |
| Resolving the 16 ambiguous identities | An owner decision | Evidence identity state AMBIGUOUS | Seats DEFERRED, with their alternatives kept in the evidence | Owner identity decision |
| Correcting or removing the five stub profiles | A behaviour change to CSQ inputs; not needed while inactive | §8.40.4 | Findings recorded | Owner decision |

### 8.40.9 Status

| Increment | Status |
|---|---|
| R1 to R5 | As §8.39 (R5 interface adjusted in §8.40.5) |
| R6 (Onboarding Contract and 109 reconciliation) | IMPLEMENTED as typed code and an offline reconciliation. All 109 seats are reconciled; none is verified, bound or eligible. Uncommitted. |
| OAI-PMH and bulk/static storage and indexing lifecycle | PARTIALLY COMPLETE: the lifecycle, licence gating, ingestion, index and search adapter are implemented and tested in memory; persistent storage is BLOCKED (§8.40.8). |
| R7 (shared logic and isolation), R8 (onboarding automation) | NOT STARTED |

## 8.41 — R7: durable PostgreSQL record store for the local index, and resolution of the five R6 reconciliation discrepancies (2026-10-15)

Before this append this ADR had SHA-256 `9636407592d7f01fd443298a8abc7caf2a5c9290ebb5e62618946ef1ed27e2d8` (541,848 bytes). No earlier content was changed.

**Source.** On 2026-10-15 at 21:42 local time the user issued "WSA-Enterprise — Full Autonomous Engineering Execution Mandate: End-to-End Implementation, Durable Persistence, Real Testing, Scientific Search Integration, and Strict Safety Controls". It makes durable storage mandatory (its §7), allows an unapplied migration where a schema is needed (§8 Case B), and requires real-backend tests, with PostgreSQL not substituted by SQLite. It keeps the prohibitions on Git operations, deletion, dependency changes, production databases and migrations, deployment, and live source access. In answer to a decision request, the user authorized an isolated, disposable PostgreSQL test container with a securely generated password and no published ports. This section records what was done. It does not change D-04, D-09, D-10.x or any earlier decision.

### 8.41.1 Decisions

| Kind | Record |
|---|---|
| Decision | §8.3 storage gate, §8.40.5 alternative 1: new tables in the existing PostgreSQL database (§8 Case B). Reusing `knowledge_embeddings`, a search engine, or local files stay rejected for the reasons in §8.40.5. |
| Decision | The migration is authored, not deployed. It was applied only to the disposable test database `wsa_r7_store_test`. D-10.12 is unchanged: D-10 itself still authorizes no schema; the authority for this one is the mandate, and it covers source records, not the D-10 domains (identity, Capability, Path, Projection, binding, activation, correlation). |
| Decision | Authoritative store and derived index are separate. The rows are authoritative. `LocalScientificIndex` stays in memory and is rebuilt from them; it is never persisted. |
| Decision | The persistence blocker of §8.40.8 is replaced by `local_index_store_not_deployed`. The store exists and is verified; what is missing is a deployed schema. |
| Decision | Reconciliation schema `adr023.source_onboarding_reconciliation.v2` is exported to a new file. The v1 artifact is kept unchanged as the R6 record. |
| Decision | No 62→109 mapping was produced. The register omits `historical_62_xref` (§8.11, D-09 item 4), and a name-based mapping would be an identity decision. The legacy-62 register is unchanged. |
| Disclosure | The store classes, the migration, the provider binding and the change to `LocalIndexSearchIntegrationTest` were written between 22:15 and 22:21 local time, within this mandate. The executor's working record of writing them was lost when its context was compacted. They were reviewed afterwards against this design, and every test below covers them. |

### 8.41.2 The five R6 discrepancies

| # | Discrepancy | Cause | Resolution |
|---|---|---|---|
| 5.1 | Family counts (REST 13, STATIC 19, BULK 9, RSS 3, OAI 2) total 46, not 109 | They count seat-family pairs: a seat may document several families or none | New `SeatFamilyState` gives each seat exactly one state: `remote_api` 9, `remote_api_and_local_index` 4, `local_index` 21, `unimplemented_family` 2, `no_machine_access` 73 (total 109). The export labels the 46 as `candidate_family_assignments` over 36 seats. |
| 5.2 | Evidence status was easy to read as readiness | One `status` field next to `activation_state` | Each seat now exports `evidence_axes`: identity, access, licensing and capability evidence; the licensing declaration (always UNRESOLVED); the Capability decision (always NONE); documentary evidence status; onboarding readiness (NOT_READY 93, DEFERRED 16); and operational activation (INACTIVE 109). The ambiguous seat-level `status` key is renamed `documentary_evidence_status`. |
| 5.3 | 21 persistence blockers against 30 static/bulk/OAI assignments | The 30 are assignments over 25 seats: G1-14, G5-04, G6-02 and G6-14 document bulk and static, and G6-20 documents OAI-PMH and bulk. Four of the 25 (G1-14, G5-04, G6-02, G6-20) also document a REST API, so the local index is optional for them. In addition, `ingestion` was BLOCKED for all 25 while the blocker covered 21. | `ingestion` is now BLOCKED only for the 21, with the same rule as the blocker. `local_index_reconciliation` in the export lists every set. Next-gate candidates (no seat-specific blocker): G1-01, G1-13, G1-15, G1-17, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G5-04, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16. Held: G1-14, G6-02 (credentials); G2-24, G3-13, G6-22 (ambiguous identity); G6-14, G6-15 (evidence fetch failures); G2-24 and G6-20 have two such blockers. "Next gate" means owner identity verification and an authorized endpoint test. No seat may be activated. |
| 5.4 | Five `stub_declares_unverified_query_capabilities` warnings | Inactive profile stubs agris, gbif, pubmed, europe_pmc and agricola declare identifier and constraint fields written before any evidence review | Each warning carries a disposition in `warning_dispositions` (`OnboardingWarningDisposition`): its evidence (the declared fields), root cause, impact (none at runtime: inactive and unregistered), corrective action (keep unchanged and inactive until an accepted onboarding and Capability decision back each field), verification, and limitation (no probe is authorized). The stubs were not changed, so CSQ inputs are unchanged. |
| 5.5 | R5 and R6 JUnit comparison | — | Compared per test ID and per failure text. Unit: R5 1,440 → R6 1,497, with 57 added (all passing), 0 removed, 0 state changes, and 29/29 failures byte-identical. Feature: 950 → 950, with 84/84 identical. The 74 undiscovered Feature files: 890 → 890, with 53/53 identical. No normalization was needed: even line numbers match. |

The v2 artifact is `docs/architecture/science-source-expansion/2026-10-15-R7-SOURCE-ONBOARDING-RECONCILIATION.v2.json` (SHA-256 `e69fb4be2786aba62191e32c74cf3692f3b5afad3bcd7c8d1ba62193495f9399`, 1,348,237 bytes). It was exported by the reconciliation test, which also asserts that the seat ids equal the register in register order, that the register hash matches, and that a second reconciliation produces byte-identical JSON. Decision ids are now `onb-r7-20261015-<adr_id>`. The v1 artifact (`ef1e1e5a…`) is unchanged.

### 8.41.3 Durable store


- `scientific_source_records` is the authoritative store, with one row per identity `(source_key, original_identifier)` (unique `scientific_source_records_identity_uq`, so two sources never collide). A row holds:
  - the lifecycle state;
  - the content hash (SHA-256 of the normalized result, excluding retrieval time);
  - the source datestamp;
  - the generation, and `first_generation`, which an update does not change;
  - the retained record as versioned JSON (`ScientificRecordCodec` v1), with its provenance and licensing;
  - the tombstone reason;
  - timestamps.
- `scientific_source_states` holds the committed generation and the harvest checkpoint (including the resumption token) of each source. It is the row a commit locks.
- `scientific_source_generations` is an append-only log of committed generations, with their mode and counts.

On PostgreSQL, CHECK constraints enforce three rules:

- an active row has a record and a hash, and no tombstone reason;
- a tombstone has a reason, and no record or hash;
- `generation >= first_generation >= 1`, and the mode is `incremental` or `snapshot`.

The tables have no tenant, organization or user column and no foreign keys. They hold global reference data of public sources.

`DatabaseScientificRecordStore` implements the `ScientificRecordStore` port. The in-memory reference store is kept, and both share `ScientificRecordGenerationMerge`, so their lifecycle rules cannot drift apart.

- Changes are staged in the process and written by `commit()` in one transaction:
  1. insert the state row if it is missing, then lock it (`SELECT … FOR UPDATE`);
  2. refuse the commit with `CONCURRENT_GENERATION` if the staged base is no longer the committed generation;
  3. merge the changes: idempotent by hash, stale datestamps ignored for upserts and tombstones, and records absent from a complete snapshot withdrawn as `absent_from_complete_snapshot`;
  4. upsert the changed rows;
  5. append the generation log and update the generation and checkpoint.

  A process that dies before COMMIT leaves nothing behind.
- `readActive()` reads the generation and the active rows in one REPEATABLE READ READ ONLY transaction.
- Every row read back is decoded strictly, and its hash is recomputed. A mismatch raises `RECORD_CORRUPT`.
- PDO and connection faults raise `STORE_UNAVAILABLE`. Messages carry the SQLSTATE only.

Integration:

- `AgriculturalIntelligenceServiceProvider` binds `ScientificRecordStore` to `DatabaseScientificRecordStore`, and `LocalScientificIndex` as a singleton.
- `LocalIndexIngestionService` writes through the store. `rebuildIndex` and the search adapter read through `readActive`.
- `LocalIndexScientificSourceAdapter` takes the store and rebuilds the index whenever the index generation differs from the committed generation, so a restart cannot serve a lost index.
- Search outcomes are distinct:
  - a never-committed source is UNAVAILABLE `local_index_not_built`;
  - a store fault or a corrupt row is FAILED with its reason, never EMPTY.
- No production source is registered to the adapter, so no runtime path reaches the tables.
- The compiled query is passed unchanged. CSQ, `QueryUnderstandingService`, `ScientificSearchQueryBuilder`, selection, ranking and deduplication were not changed.

Licensing is unchanged from §8.40.5:

- nothing is ingested unless retrieval is permitted;
- nothing is stored unless metadata storage is permitted;
- abstracts and raw payloads are kept only with full-text storage permission, and the omission is recorded as a provenance transformation. Text that may not be stored is therefore not searchable.

Search writes nothing and never stores the question.

### 8.41.4 Verification

The PostgreSQL environment, the commands, and the incident:

- **Server.** PostgreSQL 16.14 (`postgres:16-alpine`) in container `wsa-r7-store-pg-20261015`:
  - `--network none`, no published ports, volume `wsa-r7-store-pgdata-20261015`;
  - database `wsa_r7_store_test`, role `wsa_r7`;
  - the password was generated with a CSPRNG and never printed.
- **Test runs.** PHP runs in `wsa-enterprise-backend-test:latest`, which joins the server's network namespace (`--network container:<pg>`). The backend, vendor and docs are mounted read-only, with an empty `.env`, and the run uses the existing `PostgresTestDatabaseGuard` variables (`DB_TEST_*`, `DB_TEST_ALLOW_DESTRUCTIVE_RESET=wsa_r7_store_test`). The command is `php vendor/bin/phpunit -c phpunit.pgsql.xml --no-coverage --do-not-cache-result --log-junit … <file>`.
- **Default suites.** They ran as in §8.34.6 (`--network none`, SQLite in memory).
- **Incident (recorded, not counted).** The first server-restart attempt passed its test file in a PowerShell parameter named `$args`, an automatic variable, so the whole PostgreSQL suite ran instead. The command was also launched twice, and the two runs migrated the same database at once, which produced 135 errors (`relation "migrations" does not exist`, a duplicate `users` type). The overlapping runs were discarded. Every result below is from a single run with nothing else connected.

| Run | Result |
|---|---|
| `tests/Pgsql/ScientificRecordStorePostgresTest.php`, three separate runs | OK (13 tests, 244 assertions) each; exit 0 |
| Server restart: write phase in one PHP container, then `docker restart` of the PostgreSQL container (postmaster start 20:09:36 → 20:19:45 UTC), then verify phase in a new PHP container | Write OK (1 test, 5 assertions); verify OK (1 test, 15 assertions); exit 0 and 0 |
| Full `phpunit.pgsql.xml` | 135 tests, 1,084 assertions, 1 skipped (the restart test without a phase), 0 failures; exit 0. The §8.25 baseline is 121 tests and 840 assertions, and 121 + 13 + 1 = 135 and 840 + 244 = 1,084. |
| Onboarding unit tests | OK (42 tests, 5,677 assertions) |
| Unit suite | 1,501 tests; 29 not passing. Against R6: 4 added, all passing; 0 removed; 0 state changes; 29/29 failure texts byte-identical. |
| Feature suite | 950 tests; 84 not passing. Against R6: 0 state changes; 84/84 identical. One extra assertion, from `PlantAiDiagnosisKnowledgeBaseStage7Test::test_27_no_database_migration_requirement`, which asserts once per migration file. |
| 74 undiscovered Feature files | 890 tests; 53 not passing. Against R6: 0 state changes; 53/53 identical. |
| `php -l` on the 23 PHP files created or changed | No errors |
| `pint --test` on the same files | Fails on 22 of 23 (line endings under `core.autocrlf=true`, brace position, `!` spacing). The same rules fail on 12 of 15 untouched tracked files, and the repository has no `pint.json`. Nothing was reformatted. PHPStan is not installed. |

The PostgreSQL tests (13), each against the real server:

1. **Schema.** The unique identity rejects a duplicate (SQLSTATE 23505). The CHECK constraints reject an invalid lifecycle, generation or mode (SQLSTATE 23514). The column list holds no tenant data.
2. **Fresh-process recovery.** A worker process commits a snapshot. A second process searches it, with fresh container services and a fresh index. A fresh store reads back the identity, hash, provenance, licensing and lifecycle.
3. **Idempotency and updates.** An identical re-ingestion rewrites no row. A changed snapshot updates in place: the same row id, a new hash, and `first_generation` kept. An absent record is withdrawn, and search follows.
4. **Collisions.** The same provider identifier in two sources stays two rows.
5. **Stale writes.** An older version or tombstone never overwrites a newer committed one.
6. **Rejected snapshots.** A malformed or unreadable snapshot changes no row, state or generation.
7. **Interruption.** A worker holding an uncommitted generation is killed with SIGKILL. Its changes were never visible to other connections. After its backend is gone, nothing remains, and the next run commits generation 2.
8. **Harvest resume.** An OAI-PMH harvest paused after one page resumes in a new process at the stored token, then completes and records the deletion.
9. **Concurrency.** A competing commit waits on the source row lock, as `pg_blocking_pids` reports. A writer staged against the superseded base is refused with `CONCURRENT_GENERATION`, and the withdrawn record is not resurrected.
10. **Licensing.** Restricted abstracts are not persisted or searchable. Permitted content is persisted and searchable.
11. **Failures.** An unreachable server gives FAILED `local_store_unavailable`. A tampered row gives FAILED `local_store_record_corrupt`. A never-ingested source gives UNAVAILABLE.
12. **Rebuild.** An index rebuilt from `readActive` equals the incrementally maintained index, for six queries.
13. **Read-only search.** Search changes no row, and the question text appears in no table.

The restart test verifies the following after the server restart:

- generations [2, 1];
- the revised record, with `first_generation` 1 and generation 2;
- both kinds of tombstone;
- the in-progress checkpoint, with its token;
- an adapter search answering from index generation 2, and EMPTY for withdrawn content;
- a harvest that resumes and completes.

**Measurement.** No performance or relevance measurement was made. There is no baseline for the local index and no approved relevance set, so no improvement is claimed.

### 8.41.5 Source families

| Family | Implemented (offline, tested) | Verified against a real source |
|---|---|---|
| REST/JSON | `RestJsonScientificSourceAdapter` (§8.39) | No; the five legacy sources run on the legacy basis (§8.40.4) |
| OAI-PMH | Harvester with a durable checkpoint and resumption (§8.40.5 and this section) | No; live harvesting is not authorized |
| Bulk and static datasets | `BulkDatasetReader`, complete-snapshot replacement, and durable generations | No |
| RSS/Atom | Nothing: neither a parser nor a production family exists | No; 2 seats document RSS only |

### 8.41.6 Repository state

HEAD is `f2025315d8990f52f3c658c968a3ec8252ac18b0` on branch `phase-18-m18-ai-marketing-communications`. Nothing is staged and there are no stashes. Since the start of the mandate, 7 untracked files were added and none disappeared. Protected files are unchanged:

- composer.lock `D6EE17A75BF1DA5B`, composer.json `DE302DFA539371AA`, phpunit.xml `1EC076C544341F95`, .env `16B96F1E9230C8DA`;
- the 109 register `783035186579AA55`;
- the legacy-62 register `9170257A5FEA2D1F`.

The only tracked files changed by this mandate are `AgriculturalIntelligenceServiceProvider.php` (the two bindings) and this ADR (this append). No file was deleted. There was no commit, push, pull, branch change, dependency change, network access, live-source access, or production database access.

The earlier test container `wsa-r7-pg`, whose password generation had failed, was removed after it was confirmed to hold an empty test database. The user authorized that removal. The new container and its volume are kept.

### 8.41.7 Blockers

| Operation | Reason | Authorization required |
|---|---|---|
| Applying the migration outside the disposable test database | Production migrations and deployment are not authorized | Owner approval of the schema, then a migration and deployment gate |
| Registering the local-index adapter for any real source | Identities unverified, licences unresolved, no endpoint test; activation is not authorized | Per-seat onboarding decisions (identity, licence, Capability, Path) and a network gate for endpoint tests |
| Resolving the 16 ambiguous identities | Owner decision | Owner identity decision |
| An explicit legacy-62 → 109 mapping | `historical_62_xref` was omitted by decision; a mapping is an identity decision | Owner decision on `historical_62_xref` |
| RSS/Atom family | Not implemented; outside this mandate's tested scope | Owner decision that the 2 RSS-only seats justify it |
| Changing the five stub profiles | Would change CSQ inputs | Owner decision |
| Performance and relevance measurement | No baseline and no approved relevance set | An approved evaluation set and method |

### 8.41.8 Status

| Item | Status |
|---|---|
| R6 discrepancies 5.1–5.5 | RESOLVED, with the v2 export and tests |
| Durable record store | IMPLEMENTED and VERIFIED on a real, disposable PostgreSQL 16 server, including fresh-process and server-restart recovery. NOT DEPLOYED: the migration has not been applied outside that database. |
| Production search path | WIRED (container binding, adapter and ingestion) but UNUSED: no source is registered to the local-index adapter |
| R7 shared logic beyond the store, and R8 | NOT STARTED |

## 8.42 — R8: Canonical scientific source registry consolidation, durable-store migration review, and search-path proof (2026-10-16)

Before this append this ADR had SHA-256 `1c506997a3632315287a87402c4b1c6b09cec684ecee86878bcf71a7d97beef9` (561,092 bytes). No earlier content was changed.

**Source.** On 2026-10-16 the user issued "WSA-Enterprise — Autonomous Engineering Execution Mandate: Canonical Source Registry Consolidation + Durable Storage Validation + Scientific Search Integration". It requires consolidating the legacy 62 and current 109 inventories into one evidence-based canonical registry with permanent duplicate prevention, reviewing the R7 PostgreSQL store and migration, and proving ingestion-to-search integration — without Git operations, production migration, live source access, or silent identity merges. This section records what was done.

### 8.42.1 Governing decision (D-11): consolidation without silent membership expansion

| Kind | Record |
|---|---|
| Decision (D-11) | A new artifact, `ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v1.json`, is the authoritative **canonical identity and membership-resolution registry**. The immutable inputs remain `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md` and `ADR-023-109-MEMBERSHIP-REGISTER.v1.json`. |
| Supersedes | The IU-10A / §8.7 practice of permanently omitting any machine-readable 62↔109 consolidation layer, and the §8.41 statement that “no 62→109 mapping was produced” as a standing prohibition on building a reviewable consolidation. |
| Does **not** supersede | D-09’s **109-seat CURRENT enumeration**; G3-15 REMOVED with no replacement; G2-17 = REMVT and G2-19 = Animal Bioscience; dual G1/G6 seats remaining distinct without automatic SAME_AS; the immutability of the 62 and 109 source files; D-10.11 (no `adr_id` on Stage-3 registration without an accepted onboarding decision); activation remaining separate from membership. |
| Justification | Two inventories without a consolidation contract blocked evidence-based aliasing and duplicate prevention. D-11 adds that contract without inventing CONFIRMED merges or expanding CURRENT membership. |
| Membership rule | CURRENT unique members = the 109 seats. Legacy-62 rows become `HISTORICAL_ONLY` records. A legacy row becomes a confirmed historical alias of a CURRENT seat only after an owner-accepted `CONFIRMED_SAME_SOURCE` decision. |

### 8.42.2 Identity-matching criteria

| Classification | When it may be assigned |
|---|---|
| `CONFIRMED_SAME_SOURCE` | Authoritative identity evidence, official publisher/URL evidence, or an explicit ADR/owner decision. **Names alone never suffice.** |
| `CONFIRMED_DISTINCT_SOURCES` | ADR dual pairs (D-09); legacy identity_notes that mark master_ids as distinct (`≠ #N`). |
| `POSSIBLE_MATCH_REQUIRES_REVIEW` | Exact normalized display-name equality across registers, with no confirming publisher/URL evidence in either register. |
| `INSUFFICIENT_IDENTITY_EVIDENCE` | No normalized name match and no other identity evidence. |

### 8.42.3 Consolidation results (executed, deterministic)

Inputs: 62 + 109 = **171**. All 171 are accounted for exactly once.

Artifact: `docs/architecture/science-source-expansion/2026-10-16-R8-SOURCE-REGISTRY-CONSOLIDATION-MANIFEST.v1.json` (SHA-256 `0711dc953b3f77f62e928ec6ad1cc48bc1c736b2682954fd47a118f30f541085`).

Canonical registry: `docs/architecture/science-source-expansion/ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v1.json` (SHA-256 `6b29fc92c4ea9a20c3b77553fc5a6d85bb5b5d283e00804f7ac6c1cab8da91f3`).

| Metric | Count |
|---|---|
| CURRENT unique members | 109 |
| Canonical records (CURRENT + HISTORICAL_ONLY) | 171 |
| `CONFIRMED_SAME_SOURCE` (cross-registry) | 0 |
| `CONFIRMED_DISTINCT_SOURCES` relationships | 12 (10 dual pairs + 2 legacy note pairs) |
| `POSSIBLE_MATCH_REQUIRES_REVIEW` relationships | 49 |
| Legacy rows with insufficient evidence (unmapped) | 13 |
| G3-15 | Recorded as `HISTORICAL_REMOVED`; absent from CURRENT |
| Sources activated by consolidation | 0 (every record `INACTIVE`) |

Typed implementation under `App\Services\Agriculture\Research\Membership\Consolidation\`: parsers, consolidator, `CanonicalScientificSourceRegistry` with idempotent re-registration, rejection of conflicting canonical/inventory/historical bindings, and refusal of display names as definitive identity keys.

### 8.42.4 Durable storage and migration review


Findings:

- Authoritative rows vs derived in-memory index remain separated.
- Uniqueness, lifecycle CHECKs, commit locking, stale-base refusal, tombstones, and fail-closed search outcomes behave as in §8.41.
- No second persistence system was introduced for the canonical registry: it is a file-backed governance artifact (membership SoT), while harvested **records** continue to use the PostgreSQL store. A DB schema for canonical membership is not required for D-11.
- Gaps closed in this increment: migration rollback + re-apply on an empty disposable database; missing-schema search returns `FAILED` / `local_store_unavailable` (not EMPTY); provider binding asserted.

### 8.42.5 Scientific search integration

Default Stage-3 production catalog still registers only the five REST legacy sources; no production source is bound to `LocalIndexScientificSourceAdapter` (unchanged, and still correct under D-10.11).

Proved on disposable PostgreSQL 16:

1. Canonical `adr_id` resolution and dual-name ambiguity refusal.
2. Ingestion through `LocalIndexIngestionService` into `DatabaseScientificRecordStore` (container binding).
3. Fresh-store read of identity, provenance and adapter class.
4. Derived-index search SUCCESS for eligible records, EMPTY for withdrawn, no question text persisted.
5. Provider resolves `ScientificRecordStore` → `DatabaseScientificRecordStore` only.

CSQ / compiler / ranking / confidence contracts were not modified.

### 8.42.6 Verification

| Run | Result |
|---|---|
| `tests/Unit/Agriculture/Research/Membership` (`--network none`) | OK (10 tests, 5,448 assertions), exit 0 |
| `tests/Pgsql/ScientificRecordStoreMigrationTest.php` | OK (4 tests, 46 assertions), exit 0 |
| `tests/Pgsql/CanonicalRegistryLocalIndexIntegrationTest.php` | OK (1 test, 18 assertions), exit 0 |
| `tests/Pgsql/ScientificRecordStorePostgresTest.php` | OK (13 tests, 244 assertions), exit 0 |

Protected inputs unchanged: legacy-62 `9170257A…`, membership-109 `78303518…`, composer.lock / composer.json / phpunit.xml / .env prefixes as in §8.41.

HEAD remains `f2025315d8990f52f3c658c968a3ec8252ac18b0` on `phase-18-m18-ai-marketing-communications`. Nothing staged. No commit, push, production migration, or live source access.

### 8.42.7 Remaining gates

| Gate | Authorization required |
|---|---|
| Accept or reject each `POSSIBLE_MATCH_REQUIRES_REVIEW` | Owner identity review; then bind confirmed historical aliases |
| Deploy the record-store migration outside the disposable test DB | Owner schema/deployment approval |
| Register any CURRENT seat to a Stage-3 / local-index adapter | Accepted onboarding + Capability + Path + licensing; network gate for live probes |
| Expand CURRENT membership beyond 109 | Explicit ADR membership decision (D-09 retained) |

## 8.43 — R8 owner-authorized identity consolidation (45 links) and legacy-62 final accounting (2026-10-16)

**Status:** EXECUTED (identity layer only). Append-only continuity record. Does **not** rewrite D-09, D-11, §8.42, the immutable 62-register, or the 109 membership register.

### 8.43.1 Owner authorization scope

| Item | Decision |
|---|---|
| Batch | All 44 evidence-review cases with `APPROVE_SAME_SOURCE_RECOMMENDED` |
| Explicit add-on | `PM-27` — legacy `#35 APIS` ↔ CURRENT `G3-01` (owner: same scientific source) |
| Total authorized relationships | **45** (relationship pairs, not an expansion of CURRENT seats) |
| Explicit exclusions (unchanged) | `PM-02` (AgroSpace — conflicting evidence), `PM-08` (European Poultry Science — needs further owner decision), `PM-22` (Aquaculture Journal — insufficient evidence), `PM-26` (IDEAL Project — identity unresolved) |
| Non-authorization | No CURRENT membership change; no activation; no license/Capability transfer; no rewrite of historical registers |

### 8.43.2 Execution mechanism (D-11 retained)

- Binding uses the approved identity contract: `historical_identifiers` (+ co-bound `inventory_keys`) on the existing seat canonical id `adr023.canonical.seat.{adr_id}`.
- Linked legacy rows are **not** given a separate `adr023.canonical.historical62.{id}` record after confirmation (they resolve to the seat canonical).
- Immutable inputs remain byte-stable: `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md` and `ADR-023-109-MEMBERSHIP-REGISTER.v1.json`.
- Name-only matching never creates `CONFIRMED_SAME_SOURCE`; only the owner-approved link list does.

### 8.43.3 Results

| Metric | Value |
|---|---|
| Owner links applied | 45 / 45 (none blocked) |
| CURRENT unique members | **109** (unchanged) |
| Canonical records after binding | 126 (109 seats + 17 unlinked historical-only) |
| Remaining `POSSIBLE_MATCH_REQUIRES_REVIEW` | 4 (PM-02, PM-08, PM-22, PM-26) |
| Unmapped legacy (no name match in 49) | 13 |
| Activation | All records `INACTIVE` |
| PM-27 | `#35` → `adr023.canonical.seat.G3-01`; historical id retained |

Legacy-62 final accounting taxonomy (exactly 62 rows, one per `legacy_id`):

| Taxonomy | Count |
|---|---:|
| `LINKED_TO_CANONICAL_CURRENT` | 45 |
| `POSSIBLE_MATCH_UNRESOLVED` | 3 |
| `CONFLICT_REQUIRES_REVIEW` | 1 |
| `NO_MATCH_IDENTIFIED` | 13 |
| Sum | **62** |

`NO_MATCH_IDENTIFIED` means no match under available evidence, not proof of independence. Unresolved possibles mean duplicates are **not** fully closed.

### 8.43.4 Artifacts

| Artifact | Role |
|---|---|
| `2026-10-16-R8-SOURCE-REGISTRY-CONSOLIDATION-MANIFEST.v2.json` | Post-authorization consolidation manifest |
| `ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v2.json` | Canonical registry with confirmed historical aliases |
| `2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.v1.json` | Machine-readable full 62 accounting |
| `2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.md` | Human-readable full 62 accounting |
| Prior v1 manifest/registry | Preserved as pre-authorization baseline (§8.42) |

### 8.43.5 Verification

| Run | Result |
|---|---|
| `tests/Unit/Agriculture/Research/Membership/SourceRegistryConsolidationTest.php` | OK (16 tests, 256 assertions), exit 0 |

Protected inputs unchanged at execution: legacy-62 `9170257a…`, membership-109 `78303518…`. HEAD remains `f2025315d8990f52f3c658c968a3ec8252ac18b0` on `phase-18-m18-ai-marketing-communications`. No git add/commit/push.

### 8.43.6 Remaining owner gates

| Gate | Cases / notes |
|---|---|
| Resolve or reject remaining possibles | PM-02, PM-08, PM-22, PM-26 |
| Decide fate of 13 `NO_MATCH_IDENTIFIED` historical rows | Evidence hunt or explicit HISTORICAL_ONLY retention |
| License / Capability / activation | Separate authorities; identity binding does not grant them |
| Expand CURRENT beyond 109 | Still requires an explicit D-09-class membership decision |

## 8.44 — R8 Stage A–E final resolution attempt, provenance migration readiness, and legacy-62 retirement gate (2026-10-16)

**Status:** PARTIAL EXECUTION. Append-only. Does **not** rewrite D-09, D-11, §8.42, or §8.43. Does **not** delete `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`.

### 8.44.1 Stage A — independent verification (read-only)

Verified against the live working tree:

- Branch `phase-18-m18-ai-marketing-communications`, HEAD `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
- Prior §8.43 accounting claims (45 linked / 3 possible / 1 conflict / 13 no-match = 62) are internally consistent; PM-27 preserved; exclusions not silently merged; CURRENT=109; activation INACTIVE on all canonical records.
- Path correction: the 109 membership register lives under `docs/architecture/science-source-expansion/`, not `docs/architecture/`.
- Artifact: `2026-10-16-R8-STAGE-A-INDEPENDENT-VERIFICATION.v1.md`.

### 8.44.2 Stage B — outstanding four

| Case | Outcome | Notes |
|---|---|---|
| PM-08 `#12` ↔ G2-03 | `SAME_SOURCE_CONFIRMED` | ISSN-L 0003-9098 / online 1612-9199 continuity despite Ulmer→Elsevier/PSA platform change |
| PM-22 `#29` ↔ G2-20 | `SAME_SOURCE_CONFIRMED` | MDPI Aquaculture Journal eISSN 2673-9496 |
| PM-26 `#33` ↔ G2-24 | `SAME_SOURCE_CONFIRMED` | IDEAL project identity; live app vs DataShare DOI `10.7488/ds/2768` is access-path distinction |
| PM-02 `#3` ↔ G1-04 | `UNRESOLVED_REQUIRES_OWNER_DECISION` | Belgrade Faculty repository vs Chile `agrospace.cl` are distinct; seat identity_binding still NONE |

Artifact: `2026-10-16-R8-STAGE-B-C-IDENTITY-RESOLUTION.v1.json`.

### 8.44.3 Stage C — remaining legacy rows

Twelve prior `NO_MATCH_IDENTIFIED` rows were evidence-linked to existing CURRENT seats where IC/ISSN/official naming established same-source identity despite non-exact display-name normalization (`NM-01`…`NM-12`).

One row is a confirmed distinct source **absent** from the 109 register:

| Legacy | Outcome | Blocker |
|---|---|---|
| `#20` Aquaculture, Fish and Fisheries (Wiley ISSN 2693-8847) | `DISTINCT_SOURCE_CONFIRMED` | Adding a CURRENT seat requires an explicit D-09-class membership decision (not taken here) |

Post Stage B/C accounting taxonomy (62 rows, no silent drops):

| Taxonomy | Count |
|---|---:|
| `LINKED_TO_CANONICAL_CURRENT` | 60 |
| `CONFLICT_REQUIRES_REVIEW` (PM-02) | 1 |
| `HISTORICAL_ONLY_DISTINCT` (legacy #20) | 1 |
| Sum | **62** |

### 8.44.4 Stage D — authoritative operational registry

| Layer | Authority |
|---|---|
| CURRENT membership SoT | `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` (D-09) — **109 seats unchanged** |
| Canonical identity / historical alias layer | `ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY` (D-11); v2 on disk from §8.43; final v3 machine export pending PHPUnit/Docker |
| Provenance of Stage B/C decisions | `2026-10-16-R8-STAGE-B-C-IDENTITY-RESOLUTION.v1.json` + accounting v2 |
| Activation | Remains separate; no source activated |

Implementation code path updated: `R8FinalIdentityLinks` (60 links), consolidator owner-link binding, accounting builder v2 fields, unit tests extended. **PHPUnit was not re-executed in this session** because the Docker engine was unavailable (`dockerDesktopLinuxEngine` pipe missing; no local `php` binary).

### 8.44.5 Stage E — legacy-62 register retirement

**Deletion NOT performed.** Exact blockers:

1. PM-02 remains unresolved (owner seat-binding decision required).
2. Legacy `#20` is a confirmed distinct source with no CURRENT seat; deleting the register would lose the only numbered historical label unless a membership decision and provenance migration are completed first.
3. D-09 item 4 still retains the 62-register for historical identity traceability / `historical_62_xref` until an explicit superseding retirement decision is accepted.
4. Consolidation parsers/tests still consume the markdown register as an immutable input.
5. Final consolidator export/tests could not be re-verified without Docker/PHP in this environment.

Safest next actions: (a) owner binds G1-04; (b) owner decides membership fate of Wiley AFF `#20`; (c) append a dedicated retirement decision superseding D-09 retention after provenance is fully resident in the canonical registry; (d) re-run Membership unit tests; (e) only then delete the markdown register.

## 8.45 — R8 FINAL CLOSURE: PM-02 Belgrade, legacy #20 membership, Registry v3, and legacy-62 markdown retirement (2026-10-16)

**Status:** EXECUTED. Append-only. Does **not** rewrite D-06/D-09/D-11 historical wording. Present-tense authority for CURRENT membership and historical provenance is established here.

### 8.45.1 Owner clarifications applied

| Item | Decision |
|---|---|
| PM-02 | Owner: intended source is **AgroSpace — Belgrade**. Chile `agrospace.cl` is a distinct namesake and is **not** the seat identity. `legacy #3` ↔ `G1-04` confirmed SAME_SOURCE. |
| Legacy `#20` | Wiley journal *Aquaculture, Fish and Fisheries*, ISSN **2693-8847**, URL `https://onlinelibrary.wiley.com/journal/26938847`. Distinct from `G2-11` (*Aquaculture and Fisheries*). Owner FINAL CLOSURE MANDATE §5 authorizes adding CURRENT seat **G2-26** (INACTIVE). |

### 8.45.2 CURRENT membership SoT revision

| Artifact | Role |
|---|---|
| `ADR-023-109-MEMBERSHIP-REGISTER.v1.json` | Frozen D-09 snapshot (109 seats) — retained |
| `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` | **Authoritative CURRENT membership** — **110** seats (`membership_revision=110.v1`) including `G2-26` |

Activation remains INACTIVE for all seats. No Stage-3 adapter registration is authorized by this section.

### 8.45.3 Canonical identity registry v3

| Metric | Value |
|---|---|
| Confirmed historical→canonical links | **62 / 62** |
| CURRENT members | **110** |
| Canonical records | **110** (every legacy id binds onto a seat canonical) |
| Activation | All `INACTIVE` |
| Artifacts | `ADR-023-CANONICAL-SCIENTIFIC-SOURCE-REGISTRY.v3.json`, `2026-10-16-R8-SOURCE-REGISTRY-CONSOLIDATION-MANIFEST.v3.json`, `2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.v3.*` |

### 8.45.4 Supersession of D-09 markdown retention (present tense only)

D-09 item 4 historically retained `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md` for `historical_62_xref` / lineage. That **markdown file is hereby retired and deleted** under this §8.45 decision because:

1. All 62 `legacy_id` values are bound in the canonical registry via `historical_identifiers`.
2. A byte-frozen provenance snapshot exists: `ADR-023-LEGACY-62-PROVENANCE-SNAPSHOT.v1.json` (captured from the markdown at SHA-256 `9170257a…` before deletion).
3. Final accounting v3 documents every disposition.
4. Operational/test consumers load the snapshot when the markdown is absent.

D-09’s original text is **not rewritten**. Present-tense historical provenance authority is the snapshot + canonical `historical_identifiers`, not the deleted markdown.

### 8.45.5 Verification

| Run | Result |
|---|---|
| `SourceRegistryConsolidationTest.php` + export (`WSA_R8_FINAL_EXPORT_DIR`) | OK (15 tests, 379 assertions), exit 0 — produced Registry/Manifest/Accounting v3 + provenance snapshot |
| `tests/Unit/Agriculture/Research/Membership/` after markdown deletion | OK (16 tests, 5620 assertions), exit 0 — snapshot path |
| `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md` | **Deleted** (absent on disk) |

HEAD remains on `phase-18-m18-ai-marketing-communications`. No git add/commit/push.

## 8.46 — R9: Seventeen next-gate seats — selective integration and activation (2026-10-16)

**Authority.** Owner engineering execution mandate to implement, integrate, test, and activate the seventeen R7 `next_gate_candidates` (ADR-023 §8.41.2), without git commit/push.

**Exact seats (authoritative, not substituted):** G1-01, G1-13, G1-15, G1-17, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G5-04, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16.

### 8.46.1 Design completed

| Component | Change |
|---|---|
| `ScientificSourceLifecycle::GOVERNED_ONBOARDED` | ADR seats with accepted ACTIVE onboarding |
| `ScientificSourceSelectionRole::GOVERNED_EXPANSION` | Auto-selected after statistical + Internet-First defaults; Stage-3 defaults unchanged |
| `AcceptedOnboardingDecisionCatalog` / `NextGateEligibleSeatCatalog` | Inventory, blockers, family profiles, selective ACTIVE contracts |
| `ScientificSourceRegistration` | Allows `adr_id` only with ACTIVE onboarding bound to the same `sourceKey` |
| `ScientificSourceCatalog` | Merges activated next-gate registrations after the five legacy Stage-3 sources |
| Reconciler | Distinguishes `registration_governed_bound` from `registration_external_only` |

### 8.46.2 Activation outcome

| Seat | Runtime | Notes |
|---|---|---|
| **G5-04 GBIF** | **ACTIVE** (`sourceKey=gbif`, REST species search) | Identity documentary evidence; Terms of Use + per-dataset licences; authorized live probe HTTP 200 with `scientificName` results |
| G6-03, G6-04, G6-06, G6-08 | DEFERRED | Dual peers of G1 seats; `dual_peer_runtime_deferred` |
| Remaining 12 | NOT_READY | Licence / endpoint / local-index ingest / store-deployment blockers retained honestly |

Local-index adapters, bulk/static/OAI profiles, and onboarding scaffolds were completed where families were documented; seats were **not** marked ACTIVE solely because adapters exist.

### 8.46.3 Artifacts

- `2026-10-16-NEXT-GATE-17-ENDPOINT-VERIFICATION.v1.json`
- `2026-10-16-NEXT-GATE-17-EVALUATION-BASELINE.v1.json`
- Engineering report: `2026-10-16-R9-NEXT-GATE-17-ENGINEERING-REPORT.ar.md`

No commit or push was performed under this section.

## 8.47 — Source Expansion Mission: identity verification, blocker registers, and retrieval evaluation harness (2026-10-17)

**Status:** EXECUTION RECORD (Workstreams A/B/C). **Does not close Phase 1.** **Does not authorize additional activation.** **No commit/push.**

### 8.47.1 Authority and preservation

- Mission: Scientific Source Expansion — Workstreams A (17 next-gate seats), B (16 ambiguous / licences / RSS / 62↔CURRENT), C (retrieval quality harness).
- Branch at execution: `phase-18-m18-ai-marketing-communications` @ `f2025315d8990f52f3c658c968a3ec8252ac18b0` (reconfirmed at start; do not assume older HEADs).
- Protected WIP preserved: no `git reset` / `restore` / `clean` / `stash` / branch switch; no broad staging; no commit.
- Activation: **unchanged**. G5-04 remains the only ACTIVE next-gate seat (R9 / §8.46). Dual peers remain DEFERRED. Other next-gate seats remain NOT_READY.

### 8.47.2 Authoritative inventories reconfirmed

| Inventory | Count | Source | Evidence |
|---|---:|---|---|
| R7 next_gate_candidates | 17 | `2026-10-15-R7-SOURCE-ONBOARDING-RECONCILIATION.v2.json` | [DOCUMENTED] |
| R7 identity_ambiguous | 16 | same R7 `#seats.blockers` | [DOCUMENTED] |
| D-09 frozen membership | 109 | ADR D-09 / 109 register snapshot | [DOCUMENTED] |
| CURRENT membership v2 | 110 | `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` (G2-26 Wiley post-R8) | [DOCUMENTED] |
| Legacy historical | 62 | R8 accounting v3 | [DOCUMENTED] |

Pins preserved: `G2-17=REMVT`, `G2-19=Animal Bioscience`, `G3-15` ABSENT (no replacement).

### 8.47.3 Workstream A — 17-seat verification matrix

Artifact: `science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-NEXT-GATE-17-VERIFICATION-MATRIX.v1.json`

Seat IDs (exact): G1-01, G1-13, G1-15, G1-17, G1-19, G2-15, G2-23, G3-05, G4-01, G4-02, G5-04, G6-03, G6-04, G6-06, G6-08, G6-12, G6-16.

Summary from matrix generation:

- IDENTITY_VERIFIED: 0 (IC `identity_verified=false` for all; candidate evidence may exist)
- Licence DECLARED_IN_IC among 17: 8
- Prior authorized connectivity PASSED: 2 (G5-04 OK; G1-01 CHANGED/HTTP 200)
- Prior connectivity FAILED: 1 (G2-15 WAF blocked)
- This mission new live probes: **NOT RUN** (authorization boundary §6)
- Activation changed this mission: **none**

HTTP success is not scientific appropriateness proof (recorded in matrix limitations).

### 8.47.4 Workstream B — blockers

| ID | Topic | Status |
|---|---|---|
| B1 | 16 ambiguous identities | Register written; **0 resolved** (owner decision required) |
| B2 | Licence/access | Assessment register; unresolved licences ≠ unrestricted |
| B3 | RSS/Atom | Decision: **DO_NOT_USE_AS_PRIMARY_SCIENTIFIC_SEARCH**; ingestion not activated |
| B4 | 62↔CURRENT | Summary linked to accounting v3; 109 frozen ≠ 110 CURRENT preserved |

Artifacts under `2026-10-17-SOURCE-EXPANSION-MISSION/`:

- `2026-10-17-AMBIGUOUS-IDENTITY-REGISTER-16.v1.json`
- `2026-10-17-LICENSE-ACCESS-ASSESSMENT.v1.json`
- `2026-10-17-RSS-ATOM-DECISION.v1.json`
- `2026-10-17-LEGACY62-CURRENT-MAPPING-SUMMARY.v1.json`
- `2026-10-17-SOURCE-EXPANSION-ISSUE-REGISTER.v1.json`
- `2026-10-17-CONSOLIDATED-GATE-MATRIX.v1.json`

### 8.47.5 Workstream C — retrieval evaluation

- Benchmark (PROVISIONAL): `2026-10-17-SCIENTIFIC-RETRIEVAL-BENCHMARK.provisional.v1.json`
- PHP harness: `backend/app/Services/Agriculture/Research/Evaluation/ScientificRetrievalEvaluationHarness.php` (+ Case, Result, Loader)
- Reuses `App\Services\Ai\Evaluation\RetrievalEvaluationMetrics` for P/R/F1/Hit/MRR; adds nDCG (binary), facet preservation, unsupported-facet explicitness, duplicate rate, negative contamination, source-identity correctness
- Unit test: `backend/tests/Unit/Agriculture/Research/Evaluation/ScientificRetrievalEvaluationHarnessTest.php` — **NOT RUN** (vendor/autoload absent; php CLI absent; Docker not authorized for this mission)
- Offline executed mirror: `tools/science-source-expansion/run_scientific_retrieval_eval_offline.py` → `2026-10-17-SCIENTIFIC-RETRIEVAL-EVAL-RESULTS.offline.v1.json` **[EXECUTED]**
- Macro (provisional fixtures): precision≈0.583, recall=1.0, facet_preservation=1.0, unsupported_explicit=1.0, median fixture latency 7.65 ms (n=10; p95/p99 withheld)
- Prior R9 baseline pointer retained: `2026-10-16-NEXT-GATE-17-EVALUATION-BASELINE.v1.json`

CSQ / Builder / EntityCatalog role separation unchanged. No source activation for evaluation.

### 8.47.6 Environment

- `backend/vendor/autoload.php`: **missing** (incomplete vendor tree)
- `php` CLI: **absent**
- Docker client: present — **not used** (no authorization for container test execution in this mission)
- Network live probes this mission: **not executed**

### 8.47.7 Phase 1 / gates

- Phase 1 exit conditions are **not** marked closed by this section.
- P1-EXIT-01(c) and historical B-1 NOT RUN items are **not** redeclared PASS.
- Gate status for this mission: see consolidated gate matrix (EVIDENCE_PARTIAL / BLOCKED_OWNER / PASS_POLICY / BASELINE_PROVISIONAL).

### 8.47.8 Owner decisions still required

1. Per-seat identity binding for seats with candidate evidence and for all 16 ambiguous seats.
2. Licence acceptance for intended usage (retrieval / metadata storage / caching) per seat.
3. Authorization for bounded live connectivity probes for the 14 unprobed next-gate seats.
4. Separate activation mandates (beyond preserving G5-04).
5. Expert validation of provisional benchmark judgments before accepting production quality thresholds.
6. Authorization to restore PHPUnit runtime (vendor + php, or Docker test image) to EXECUTE harness tests.

### 8.47.9 Artifacts and generators

- Generator: `tools/science-source-expansion/generate_2026_10_17_mission_artifacts.py`
- Offline eval: `tools/science-source-expansion/run_scientific_retrieval_eval_offline.py`
- Arabic engineering report: `science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-17-SOURCE-EXPANSION-ENGINEERING-REPORT.ar.md`

No commit or push was performed under this section.

## 8.48 — Source Expansion Mission continuation: readiness reconciliation, licence wiring, expanded provisional eval (2026-10-17)

**Status:** EXECUTION RECORD (Workstreams A/B/C continuation). **Does not close Phase 1.** **Does not authorize additional activation.** **No commit/push.**

### 8.48.1 Baseline verification

- Branch: `phase-18-m18-ai-marketing-communications`
- HEAD verified: `f2025315d8990f52f3c658c968a3ec8252ac18b0` (matches reported starting HEAD)
- `backend/vendor/autoload.php`: **ABSENT**
- `php` CLI: **ABSENT**
- Docker: **not authorized** for test execution in this mission
- Protected WIP preserved (no reset/restore/clean/stash/commit)

### 8.48.2 Workstream A

- Seventeen next-gate seats reconfirmed from R7 / `NextGateEligibleSeatCatalog::ADR_IDS`.
- Activation **unchanged**: only `G5-04` ACTIVE; dual peers DEFERRED; others NOT_READY.
- Historical connectivity claims treated as historical: prior probes for G5-04/G1-01/G2-15 not re-executed as live authorization this session (`CONNECTIVITY_NOT_TESTED` for new probes).
- Implemented:
  - `NextGateSeatReadinessDisposition` + `NextGateSeatReadinessReconciler`
  - Catalogue licensing wired from IC evidence for **G1-01** (CC BY 3.0 IGO ODS) and **G1-17** (CC0) on inactive contracts — **without activation**
  - Readiness reconciliation artifact: `2026-10-17-NEXT-GATE-17-READINESS-RECONCILIATION.v1.json`
- PHPUnit catalog/reconciler tests extended — **NOT RUN** (environment)

### 8.48.3 Workstream B

- Ambiguous 16: **0** evidence-safe SAME_SOURCE resolutions; owner pack issued.
- Licence: unresolved ≠ permission; G1-01/G1-17 documentary licences reflected in catalogue contracts.
- RSS/Atom: prior decision retained — not primary scientific search; no ingestion.
- 109 frozen vs 110 CURRENT preserved.
- Owner decision pack: `2026-10-17-OWNER-DECISION-PACK.v1.json`
- B statuses: B1 OPEN; B2 PARTIAL; B3 PASS_POLICY; B4 PASS_PROVENANCE

### 8.48.4 Workstream C

- Provisional benchmark expanded **10 → 18** cases (empty/duplicate/failure/malformed/pagination/conflict/silent-facet-drop/poultry). Labels remain **PROVISIONAL**.
- Offline runner re-executed with metric self-tests and wall-clock harness timing (n=100 full-corpus passes).
- R9 baseline file preserved by reference (not overwritten).
- Previous 10-case macros are **not** treated as acceptance baseline; expanded corpus macros are not claimed as improvement over the prior report without methodology note.
- Live-network latency: **UNVERIFIED**
- PHPUnit harness tests: **NOT RUN**

### 8.48.5 Artifacts

Under `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/`:

- readiness reconciliation, owner decision pack, revised provisional benchmark, offline eval results
- generators: `tools/science-source-expansion/run_scientific_retrieval_eval_offline.py`, `generate_readiness_and_owner_pack.py`

No commit or push was performed under this section.

## 8.49 — PHP/PHPUnit recovery, catalog test isolation, activation eligibility freeze (2026-10-17)

**Status:** EXECUTION RECORD. **No commit/push.** **No new source activation.**

### 8.49.1 Environment recovery (Work Package D)

- Portable official PHP **8.4.26 NTS** installed under G:\\tools\\php-8.4.26-nts (windows.php.net ZIP; not Docker; not system winget).
- composer.phar 2.10.3 under G:\\tools\\composer.phar.
- composer install from lockfile in ackend/ — **exit 0**; lockfile not rewritten.
- PHPUnit **11.5.56** executed successfully for Research Onboarding/Evaluation/Catalog suites after one in-scope test fix.

### 8.49.2 Tests

- Focused next-gate + eval: OK (11 tests).
- Broader Research Onboarding + Evaluation + ScientificSourceCatalogContractTest: initially 1 failure (GBIF appeared in isolated catalog fixture).
- Fix: disable gricultural_intelligence.gbif.enabled in the isolating contract test.
- Rerun: **OK (67 tests, 5894 assertions), exit 0**.

### 8.49.3 Activation

- Automatic activation criteria applied: **no additional seat met all conditions**.
- G5-04 remains the sole ACTIVE next-gate seat (preserved).
- G1-01 / G1-17 retain documentary licence wiring on inactive contracts only.
- Artifact: 2026-10-17-ACTIVATION-ELIGIBILITY.v1.json

### 8.49.4 Evaluation

- Offline provisional 18-case eval re-executed (Python); R9 baseline unchanged.

### 8.49.5 Identity / licensing

- Ambiguous 16 remain unresolved without SAME_SOURCE fabrication.
- Owner decision pack retained (37 items).
- 109 frozen vs 110 CURRENT preserved.

No commit or push was performed under this section.

## 8.50 — Full scientific source activation mission continuation (2026-10-17)

**Status:** EXECUTION RECORD. **No commit/push/deploy.** **No new runtime activation beyond G5-04.**

### 8.50.1 Engineering delivered

- Fixed next-gate scaffold profiles so documentary licence usages flow into `BulkDatasetSourceProfile` / `OaiPmhSourceProfile` (status tokens normalized to enum values).
- Corrected AGRIS ODS usage mapping so CC BY 3.0 IGO permits retrieval + metadata_storage (attribution is licence compliance, not an unresolved owner gate).
- Wired documentary licences for G1-13 (conditional commercial contact) and G3-05 (CC BY 4.0) without activation.
- Added deterministic LocalIndex path fixtures + PHPUnit coverage for G1-01 and G1-17 (ingest → search → empty → unseeded UNAVAILABLE).
- Extended GBIF adapter tests for empty list, malformed payload, and HTTP 503 isolation.
- Readiness reconciler: `LICENCE_CLEARED_AWAITING_LOCAL_INDEX`; automatic-activation policy flag; empty LocalIndex is not readiness.

### 8.50.2 Activation decision

- **G5-04** remains the sole ACTIVE next-gate seat (live probe HTTP 200 to `api.gbif.org` species search, limit=1).
- **G1-01 / G1-17** remain NOT_READY: LocalIndex path is fixture-proven, but durable store migration is not deployed and production index generation is 0. Empty index must not be registered as live search.
- Dual peers G6-03/04/06/08 remain DEFERRED.
- Remaining seats remain NOT_READY for licence, API absence, or ingest gates.

### 8.50.3 Tests and evaluation

- Focused Research Onboarding + Evaluation + CatalogContract: **OK (71 tests, 5934 assertions), exit 0**.
- LocalIndex unit suite: **OK (19 tests, 124 assertions)**.
- Offline provisional eval (18 cases): precision 0.588, recall 0.944, MRR 0.685, nDCG 0.861, facet 0.926 — R9 baseline preserved; labels remain PROVISIONAL.

### 8.50.4 Artifacts

- `2026-10-17-ACTIVATION-ELIGIBILITY.v1.json` updated.
- `2026-10-17-MASTER-FINAL-ENGINEERING-REPORT.ar.md` updated.
- `2026-10-17-SCIENTIFIC-RETRIEVAL-EVAL-RESULTS.offline.v1.json` re-executed.

No commit or push was performed under this section.

## 8.51 — Five-source continuation + Mission Artifact Archive layer (2026-10-18)

**Status:** EXECUTION RECORD / ARCHITECTURE EXTENSION RECORD. **No commit/push/deploy/Docker.** **No new runtime ACTIVE seat beyond G5-04.**

### 8.51.1 Purpose

Preserve the 2026-10-18 dual workstream: (A) maximum legitimate integration readiness for G5-04 / G1-02 / G6-20 / G1-01 / G1-17; (B) durable general-purpose **Mission Artifact Archive** implementing D-08 continuity as a searchable, integrity-verified store distinct from scientific LocalIndex and Capability Store.

### 8.51.2 Workstream A — verified outcomes (local)

- **G5-04 GBIF:** remains sole ACTIVE; regression included in focused suite.
- **G1-02 Organic Eprints:** LocalIndex GEN=2 / 189 rows; cross-process search success; production catalog registration absent; NOT_READY.
- **G6-20 BHL:** saved `BHL_API_KEY` used via `config(agricultural_intelligence.bhl.api_key)` without printing; live RestJson adapter + MultiSourceScientificSearchOrchestrator (ephemeral LEGACY registration only) returned results with PartID/TitleID provenance; production catalog `has(bhl)=false`; OE store unchanged at 189; NOT_READY.
- **G1-01 AGRIS:** ODS harvest resumed from checkpoint cursor 5 → 10 (10 providers cumulative projected in cache); LocalIndex GEN=4 / 1317 active rows after idempotent merge ingest; per-provider CC-BY-4.0 on harvested artifacts only; corpus incomplete (1244 downloadURL); NOT_READY.
- **G1-17 Dr. Duke:** prior 16-table reconciliation retained; GEN=2 / 1500 search rows; FARMACY vs FARMACY_NEW not blind-merged; NOT_READY.
- Owner gates OD-G1-02 / OD-G6-20 / OD-G1-01 / OD-G1-17 / OD-CAPVER-FOUR-SEATS / OD-PGSQL-PRODUCTION-LOCALINDEX remain open (proposal JSON is not approval).

### 8.51.3 Workstream B — Mission Artifact Archive (new)

**New code (not a rewrite of scientific stores):**
- `FilesystemMissionArtifactArchive` — SQLite metadata + content-addressed blobs, SHA-256, append-only supersession, relationships, idempotent `ingestion_identity`.
- `ControlledMissionArtifactImporter` — allowlisted roots, secret filter, resumable checkpoint/manifest.
- Artisan: `mission-artifacts ensure|import|search|show|verify|export|latest-report|list-mission`.
- Config: `config/mission_artifact_archive.php` → local sqlite under `storage/app/mission_artifact_archive/` (not production SoR; PostgreSQL parity PENDING).

**AHO:** repository inspection found **zero** `.aho` files and **no** AHO schema/consumers. Format is reserved; originals would be retained without invented schema extraction (`aho_extraction=unsupported_no_schema_discovered`).

**Controlled import coverage (measured):** discovered 133 eligible files under mission + D-08 supplement-01 roots; archived 133 (+2 demo supersession entries → 135 total); `aho_discovered=0`; secrets excluded by filter; originals on disk untouched.

### 8.51.4 Tests (this section)

- Focused archive + BHL/GBIF/Family/LocalIndex durability: **OK (68 tests, 348 assertions), exit 0**.
- Live BHL orchestrator probe: `live_app_path_ok_not_activated`.
- Prior broader suite from same day (214/6902) remains historical evidence of shared research stack health; not re-claimed as re-run unless separately executed.

### 8.51.5 Stopping point

No commit/push. No fabricated ACTIVE. Archive is **IMPLEMENTED_WITH_LIMITED_IMPORT** (two roots; not whole-repo). Full D-08 historical corpus beyond these roots remains pending controlled import. PostgreSQL production LocalIndex/archive verification PENDING.


## 8.52 — Final audit, expanded D-08 archive migration, owner-decision package (2026-10-18)

**Status:** EXECUTION / AUDIT RECORD. **No commit/push/deploy/Docker.** **No new ACTIVE seat beyond G5-04.**

### 8.52.1 Verified runtime baseline

- HEAD `f2025315…`; membership `110.v1`.
- LocalIndex: OE GEN=2/189; AGRIS GEN=4/1317; Duke GEN=2/1500; AGRIS checkpoint cursor=10/1244.
- BHL live adapter+orchestrator probe: `live_app_path_ok_not_activated`; production catalog without `bhl`.
- Archive count after expanded migration: **632** (prior limited import 136 explained as mission+supp-01+demos only).

### 8.52.2 Archive migration expansion

Controlled import roots completed: SOURCE-EXPANSION-MISSION, D-08-supplement-01, D-08-supplement-02, D-08-archive-2026-10-01-to-2026-10-14 (584 eligible; idempotent path short-circuit for prior imports), plus 42 top-level `science-source-expansion/` files, plus ADR-023 original and 3 labeled embedded excerpts (`is_excerpt=true`). AHO files/schema: still none. Defect repaired: `findByOriginalPath` prevents duplicate rows when ingestion identity scheme changes.

### 8.52.3 Owner decision package

Published decision-request (not approval): `2026-10-18-OWNER-DECISION-PACKAGE-G1-02-G6-20.v1.json` and completion matrix `2026-10-18-FIVE-SOURCE-COMPLETION-MATRIX.v1.json`. Critical path remains onboarding ACTIVE for G1-02 and/or G6-20, then CapVer.

No commit or push under this section.

## 8.53 — Owner ACCEPT G1-02/G6-20 + CapVer AUTHORIZE + governed activation (2026-10-18)

**Status:** OWNER DECISION RECORD AND EXECUTION RECORD. **No commit/push/deploy/Docker.**

### 8.53.1 Explicit owner decision (recorded)

Source: FINAL OWNER AUTHORIZATION & FIVE-SOURCE COMPLETION MISSION (2026-10-18) [OWNER DECISION] [P].

| Decision id | Seat | Outcome | Notes |
|---|---|---|---|
| OD-G1-02-ONBOARDING-ACTIVE | G1-02 | **ACCEPT** | Onboarding ACTIVE authorized |
| OD-G6-20-ONBOARDING-ACTIVE | G6-20 | **ACCEPT** | Onboarding ACTIVE authorized |
| OD-CAPVER-ACCEPTED-SEATS | G1-02, G6-20 | **AUTHORIZE** | CapVer delegation for accepted seats subject to prerequisites; **not** CapVer success |

Owner note (AR): «الموافقة على onboarding للمصدرين وفق معايير القبول المعتمدة، ثم تفويض CapVer لكل مصدر مقبول. لا يُعتبر أي مصدر ACTIVE قبل اجتياز التحقق والتسجيل والاختبارات المطلوبة.»

Authoritative recorded artifact: `2026-10-18-OWNER-DECISION-RECORDED-G1-02-G6-20.v1.json`. The prior package JSON remains a historical **request**, not rewritten.

### 8.53.2 CapVer execution

- Workflow: `CapVerSeatVerificationService` + Artisan `scientific:capver-accepted`.
- Cap Store writes attempted via `CapabilityStoreRepository::persistCurrent` (writer-neutral; VERIFIED only under CapVer authority).
- Onboarding contracts authored ACTIVE with `capabilityDimensionStates.search=VERIFIED` and CapVer decision identities `cap-r11-20261018-G1-02-search` / `cap-r11-20261018-G6-20-search` (GBIF pattern).
- Cap Store persist outcome is reported separately (`PERSISTED` | `ALREADY_CURRENT` | `STORE_UNAVAILABLE` | `FAILED`); STORE_UNAVAILABLE does not invent Cap Store success.

### 8.53.3 Runtime activation (governed)

After CapVer prerequisites + contract authorship:

- `CurrentMembershipNearReadySeatCatalog` seats G1-02 / G6-20: `intendedActivation=ACTIVE`, empty blockers, `isRuntimeActive()=true`.
- `AcceptedOnboardingDecisionCatalog` merges CURRENT-membership ACTIVE contracts with next-gate (G5-04).
- Production `ScientificSourceCatalog` registers `organic_eprints` and `bhl` as `GOVERNED_ONBOARDED`.
- Identity bridge bindings: `adr023.canonical.seat.G1-02` / `adr023.canonical.seat.G6-20`.

**G5-04 GBIF** remains ACTIVE; not rebuilt.

### 8.53.4 Still Condition B (not cleared by this ACCEPT)

- OD-G1-01 / OD-G1-17 scope decisions (bounded AGRIS subset vs full corpus; limited Duke projection vs full).
- OD-PGSQL-PRODUCTION-LOCALINDEX (PostgreSQL production LocalIndex/archive parity).
- Cap Store production persistence when local Cap Store DB unavailable.
- Optional BHL key rotation (manual; never auto; never archive the key).

No commit or push under this section.

## 8.54 — AGRIS bounded + Dr. Duke limited activation (2026-10-18)

**Status:** OWNER AUTHORIZATION + ENGINEERING SCOPE DECISION + EXECUTION RECORD. **No commit/push/deploy/Docker.**

### 8.54.1 Owner authorization

MASTER EXECUTION PROMPT — Full Engineering Authorization for AGRIS and Dr. Duke (2026-10-18) [OWNER DECISION] [P]. Authorizes engineering to select and implement the smallest legitimate activation scope; CapVer; governed registration; and application-path verification within ADR-023 and license constraints.

### 8.54.2 Scope decisions (recorded)

Authoritative record: `2026-10-18-OWNER-DECISION-RECORDED-G1-01-G1-17-SCOPE.v1.json`. Prior proposal packages remain historical requests (not rewritten).

| Decision | Seat | Outcome |
|---|---|---|
| OD-G1-01-SCOPE-BOUNDED-SUBSET | G1-01 | **ACCEPT** bounded licensed ODS subset (GEN=4 / 1317 active; cursor 10/1244; full corpus **not** claimed) |
| OD-G1-01-ONBOARDING-ACTIVE | G1-01 | **ACCEPT** |
| OD-G1-17-SCOPE-LIMITED-PROJECTION | G1-17 | **ACCEPT** limited search projection (GEN=2 / 1500; 16-table reconciliation; FARMACY≠FARMACY_NEW; ETHNOBOT traditional-use only) |
| OD-G1-17-ONBOARDING-ACTIVE | G1-17 | **ACCEPT** |
| OD-CAPVER-G1-01-G1-17 | G1-01, G1-17 | **AUTHORIZE** (≠ CapVer success) |

Protected sources G5-04 / G1-02 / G6-20 unchanged by this section's scope decisions.

### 8.54.3 CapVer execution

- `CapVerSeatVerificationService` extended to next-gate seats.
- Decision identities: `cap-r12-20261018-G1-01-search`, `cap-r12-20261018-G1-17-search`.
- Cap Store: **STORE_UNAVAILABLE** locally (pgsql driver / Compose postgres unreachable). Contract VERIFIED authored under CapVer authority (GBIF pattern). Cap Store success **not** claimed.

### 8.54.4 Governed runtime

- `NextGateEligibleSeatCatalog` G1-01 / G1-17: `ACTIVE`, empty blockers, `isRuntimeActive()=true`.
- Catalog keys include `agris`, `dr_duke` as `GOVERNED_ONBOARDED`.
- Durable LocalIndex probes (sqlite `scientific_records`): AGRIS `wheat` → success; Dr. Duke `Mentha` → success with stable identifiers/provenance.
- Coverage limitations remain explicit in access notes and decision record.

### 8.54.5 Still Condition B

- Cap Store Postgres persist for G1-01 / G1-17 (and prior seats).
- OD-PGSQL-PRODUCTION-LOCALINDEX for production LocalIndex parity.
- Full AGRIS corpus and full Duke projection remain **separate** milestones (not activation blockers for the recorded bounded/limited scopes).

No commit or push under this section.

## 8.55 — Five-source activation engineering wave (2026-10-18)

**Status:** ENGINEERING EXECUTION RECORD (MASTER EXECUTION AUTHORIZATION). **No commit/push/deploy/Docker.** Append-only; does not rewrite §8.54.

### 8.55.1 Blocker A — Cap Store / CapVer semantics

| Item | Evidence |
|---|---|
| PHP `pdo_pgsql` / `pgsql` | **Enabled** (`php -m`; DLLs under `G:\tools\php-8.4.26-nts\ext\`) |
| Cap Store host | Configured `DB_HOST=postgres` — **unreachable** (DNS / no local PostgreSQL service; Docker forbidden) |
| Semantics fix | `STORE_UNAVAILABLE` **must not** set `capVerPassedForActivation=true`; `capability_persisted` only for `PERSISTED`/`ALREADY_CURRENT` |
| CapVer five seats | `scientific:capver-accepted` → all four CapVer seats: `contract_verified=yes`, `store=STORE_UNAVAILABLE`, `activation_pass=no` (exit 1) |
| Independent read-back | **Not executed** — Cap Store unreachable |
| Diagnostics | `scientific:diagnose-persistence` |

### 8.55.2 Blocker B — Production LocalIndex (PostgreSQL)

| Item | Evidence |
|---|---|
| Active LocalIndex | `scientific_records` **sqlite** — reachable; schema present |
| Inventory (this wave) | AGRIS active **17223** GEN=6; Dr. Duke active **42376** GEN=10; Organic Eprints active **189** GEN=2 |
| Production cutover | **Unverified** — no authorized reachable PostgreSQL; SQLite ≠ `PRODUCTION_READY` |

### 8.55.3 Blocker C — AGRIS harvest progress

| Item | Evidence |
|---|---|
| Scope | Legally accessible ODS providers with per-artifact **CC-BY-4.0**; allowlist `https://agris.fao.org/ods/` |
| Checkpoint | **27 / 1244** providers completed (resumable; full-scan skip-completed mode) |
| Engineering | Raised CLI ceilings; HTTP **429** retry/backoff; failed URLs no longer permanently skipped by cursor alone |
| LocalIndex | GEN=6 / **17223** active after merged ingest; probe `wheat` → success |
| Full body | **Not complete** — remaining ~1217 providers; rate-limit constrained |

### 8.55.4 Blocker D — Dr. Duke projection

| Item | Evidence |
|---|---|
| Reconciliation | 16 tables; row inventory recorded (`reconcile_20261018.json`); FARMACY≠FARMACY_NEW documented |
| Projector v2 | Streams large tables; writes distinct `source_table` for FARMACY and FARMACY_NEW; `farmacy_farmacy_new_merged=0` |
| Full searchable artifact | `dr_duke_full_search.v1.jsonl` — **211961** records (FNFTAX+CHEMICALS full; ETHNOBOT/FARMACY/FARMACY_NEW budgets applied) |
| Indexed coherent snapshot | GEN=10 / **42376** active from `dr_duke_expanded.v3.jsonl`; probe `Mentha` → success |
| Full LocalIndex load of 211961 | Requires streaming snapshot ingest (chunked snapshot withdraws peers) — **remaining** |

### 8.55.5 Organic Eprints / BHL / GBIF revalidation

- CapVer seats G1-02 / G6-20: same Cap Store blocker (`activation_pass=no`).
- OE LocalIndex path tests: pass; catalog ACTIVE preserved.
- GBIF/BHL adapter structural tests: mostly pass; pre-existing conformance expectation `licensing->isFullyUnresolved()` fails where licensing evidence is now declared (document; do not weaken falsely).
- BHL API key: not printed/archived.

### 8.55.6 State vocabulary (this wave)

| State | AGRIS | Dr. Duke | OE | BHL | GBIF |
|---|---|---|---|---|---|
| TECHNICALLY_INTEGRATED | yes | yes | yes | yes | yes |
| ONBOARDED / GOVERNED_REGISTERED | yes | yes | yes | yes | yes |
| CAPABILITY_VERIFIED (contract authored) | yes | yes | yes | yes | yes |
| CAPABILITY_PERSISTED | **no** | **no** | **no** | **no** | prior / unverified here |
| ACTIVE (runtime catalog) | yes (bounded+) | yes (expanded) | yes | yes | yes |
| PRODUCTION_READY | **no** | **no** | **no** | **no** | **no** |
| FULL_SCOPE_COMPLETE | **no** (27/1244) | partial (artifact yes; full index stream pending) | n/a bounded | n/a | n/a |

### 8.55.7 Remaining external closure

1. Provide reachable authorized PostgreSQL (replace Compose hostname `postgres` or install/approve local service) — **without Docker in this mission**.
2. Run Cap Store migrations + `scientific:capver-accepted` until `PERSISTED`/`ALREADY_CURRENT` + independent read-back.
3. Set `SCIENTIFIC_RECORD_STORE_DRIVER=pgsql` + `scientific:ensure-record-store` + optional `--copy-to-pgsql`.
4. Continue AGRIS harvest under rate limits until checkpoint covers legally accessible body.
5. Streaming LocalIndex snapshot ingest for Duke full JSONL.

Arabic completion report: `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-18-FIVE-SOURCE-FULL-ACTIVATION-REPORT.ar.md`.

No commit or push under this section.

## 8.56 — Six-priority closure plan execution (2026-10-18)

**Status:** ENGINEERING EXECUTION RECORD for priorities 1–6. **No commit/push/deploy/Docker.** Append-only after §8.55. Membership remains **`110.v1`**.

### 8.56.1 Priority 1 — Authorized PostgreSQL — `BLOCKED`

| Check | Result |
|---|---|
| `DB_CONNECTION` / Cap Store | `pgsql` · host **`postgres`** · port 5432 · db `wsa_enterprise` |
| DNS | `GetHostAddresses('postgres')` → **No such host is known** |
| Port 5432 / Windows service | No listener / no PostgreSQL service observed |
| `pdo_pgsql` | **yes** (driver present) |
| LocalIndex connection | `scientific_records` · **sqlite** · reachable |
| Invented localhost / install / Docker | **Not done** (forbidden without owner approval) |

**Minimum external requirement:** owner-approved reachable PostgreSQL endpoint replacing unresolvable Compose hostname `postgres` (or explicit approval to install/start a local service). Acceptance: `scientific:diagnose-persistence` shows `cap_store.reachable=true`.

### 8.56.2 Priority 2 — CapVer + LocalIndex — partial / `NOT VERIFIED` on PostgreSQL

- CapVer semantics (prior wave) retained: `STORE_UNAVAILABLE` ≠ activation pass.
- Live CapVer (G1-02, G6-20, G1-01, G1-17): `contract_verified=yes`, `store=STORE_UNAVAILABLE`, `activation_pass=no` · report `2026-10-18-CAPVER-FIVE-SOURCE.v3.json`.
- G5-04 CapVer persist: **NOT VERIFIED** (same store host; GBIF historically Cap-verified under different path — not re-persisted here).
- Unit CapVer tests: pass (fixtures).
- SQLite LocalIndex durable counts (probe): AGRIS **87984** active · Dr. Duke **211948** active · OE **189** active.
- SQLite→PostgreSQL copy: **NOT VERIFIED** (target unreachable).
- New: `ingestIncrementalBulk` + CLI `--mode=incremental` (chunk-safe; no peer withdraw).

### 8.56.3 Priority 3 — AGRIS — progress, not `FULL_SCOPE_COMPLETE`

| Metric | Value |
|---|---|
| Providers completed | **40 / 1244** |
| Active LocalIndex rows | **87984** |
| FTS documents after rebuild | **87984** |
| License gate | Per-artifact CC-BY-4.0 only |
| Engineering | `Retry-After` on HTTP 429; full-scan skip-completed; `--mode=incremental` ingest |
| Application search | `wheat` → **success** (after `SqliteFtsScientificIndex::rebuildFromStore`) |

`FULL_SCOPE_COMPLETE` **not** claimed.

### 8.56.4 Priority 4 — Dr. Duke — durable full load + FTS application search

| Metric | Value |
|---|---|
| Projection artifact | `dr_duke_full_search.v1.jsonl` ≈ 211961 lines |
| Table budgets in projector | FNFTAX/CHEMICALS full; ETHNOBOT/FARMACY/FARMACY_NEW capped at 60000 each in full artifact |
| Durable active rows | **211948** (incremental chunks; `withdrawn=0` on chunk commits) |
| FARMACY ≠ FARMACY_NEW | Enforced (`farmacy_farmacy_new_merged=0`) |
| FTS rebuild | **211948** docs · gen aligned |
| Application Mentha search | **success** · `fnftax:614` Mentha aquatica |
| Engineering fixes | `iterateActive` streaming rebuild; provider DI imports for `LocalIndexSearchEngine`; `--mode=incremental` |

### 8.56.5 Priority 5 — OE / BHL / GBIF

- Conformance licensing test: resolved licensing requires terms+evidence; unresolved stays fail-closed; declared RPM/maxResults allowed when ≥1.
- `test_registered_adapter_declares_only_what_its_code_establishes`: **10 passed** (includes gbif + bhl).
- OE LocalIndex path tests: **4 passed**.
- CapVer persist for OE/BHL/GBIF: blocked by P1 (`NOT VERIFIED` on PostgreSQL).
- BHL key: not printed/rotated.

### 8.56.6 Priority 6 — Final acceptance matrix

| Source | ACTIVE catalog | CAPABILITY_PERSISTED | FULL_SCOPE / full index | PRODUCTION_READY |
|---|---|---|---|---|
| G1-01 AGRIS | yes | no | no (40/1244 providers) | no |
| G1-17 Dr. Duke | yes | no | durable+FTS yes; source-table row caps remain on ETHNOBOT/FARMACY* | no |
| G1-02 OE | yes | no | n/a bounded | no |
| G6-20 BHL | yes | no | n/a API | no |
| G5-04 GBIF | yes | NOT VERIFIED this wave | n/a API | no |

**Independent work completed while P1 blocked:** CapVer honesty tests, AGRIS harvest/ingest, Duke incremental+FTS, conformance licensing fix, LocalIndex DI/stream rebuild.

Arabic report: `2026-10-18-SIX-PRIORITY-CLOSURE-REPORT.ar.md`.

No commit or push under this section.

## 8.57 — Remaining blockers resolution v2.2 (2026-10-19)

Append-only historical record. Does not rewrite §8.50–§8.56.

### 8.57.1 Execution identity

- Date: 2026-10-19 (continuation of 2026-10-18 controlled session).
- Branch: `phase-18-m18-ai-marketing-communications`.
- HEAD before and after: `f2025315d8990f52f3c658c968a3ec8252ac18b0` (no commit, no push, no deploy).
- Membership baseline: `110.v1` unchanged.
- Arabic engineering report: `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-19-REMAINING-BLOCKERS-RESOLUTION-V22-REPORT.ar.md`.

### 8.57.2 Files touched this section (WIP; not committed)

- ADR append: this §8.57 only (prior sections unchanged).
- Report: `2026-10-19-REMAINING-BLOCKERS-RESOLUTION-V22-REPORT.ar.md`.
- Evidence (operational, under `backend/e2e-tmp/`): `duke_reconcile_v22.json`, `v22_evidence.json`, `v22_evidence2.json`, `_duke_reconcile_v22.php`, `_v22_*.php`.
- CapVer outcomes JSON: `e2e-tmp/capver_v22.json` (path resolved under repo `e2e-tmp/` from artisan cwd).
- AGRIS: `harvest_manifest.v5.json`, checkpoint `completed_providers` → **97**, `harvest_run_v5.log`.

### 8.57.3 P1 — PostgreSQL / CapVer — `BLOCKED` (re-verified)

- `scientific:diagnose-persistence`: Cap Store `host=postgres`, `failure_class=dns_name_resolution_failure`, `reachable=false`, `table_present=false`.
- LocalIndex: sqlite `scientific_record_store.sqlite`, `reachable=true`, table `scientific_source_records` present.
- `pdo_pgsql=yes`; localhost:5432 not accepting connections in this environment.
- `scientific:capver-accepted`: G1-01, G1-17, G1-02, G6-20 all `contract_verified_authored=true`, `capability_store_status=STORE_UNAVAILABLE`, `capver_passed_for_activation=false`.
- Independent PostgreSQL write-then-read: **not verified** (blocked). SQLite ≠ Cap Store proof.
- Docker / PostgreSQL install / speculative host rewrite: **not performed** (forbidden).

### 8.57.4 P2 — Test count discrepancy

| Run (this section) | Filter | Tests | Assertions |
|---|---|---:|---:|
| A | `OrganicEprints\|CapVerSeat\|AgrisOds` | 12 | 110 |
| B | `declares_only_what_its_code_establishes` | 10 | 83 |
| C | `IncrementalBulkIngestDoesNotWithdrawPeersTest` | 1 | 6 |

Filter A composition (12): CapVer×4 + OrganicEprintsOaiLocalIndexPath×4 + AgrisOdsAndDrDukeProjector×2 + AgrisOdsBoundedHarvester×2.  
Prior reports citing **10/100** vs **12/117**: exact historical command for 117 assertions **`NOT_VERIFIED`** in this wave; 10↔12 is consistent with excluding/including the two BoundedHarvester tests. No evidence of deleted tests in the current tree.

### 8.57.5 P3 — AGRIS inventory / license / harvest

- **1244** = `download_url_total` / `dcat:downloadURL` count (provider download endpoints), not LocalIndex rows.
- Checkpoint completed providers: **97** (was 40 at start of v5 batch).
- Manifest v5 totals: attempted 60, downloaded 58, projected 57, skipped_unverified_license 1, failed 2, records 588683.
- License in projected providers this batch: CC-BY-4.0 ×57; null-license skip enforced (e.g. FR0.zip).
- LocalIndex `agris` active rows still **87984** — new JSONL not bulk-ingested this section (`PARTIAL`).

### 8.57.6 P4 — Dr. Duke reconciliation / Mentha / indexing

Reconciliation (`duke_reconcile_v22.json`):

| Metric | Value |
|---|---:|
| JSONL lines | 211961 |
| Unique IDs | 211948 |
| Duplicate ID occurrences | 13 |
| SQLite active | 211948 |
| unique − active | 0 |

Interpretation: the 13-line gap is **duplicate source IDs in JSONL**, not missing active LocalIndex rows.

Mentha: `fnftax:614` = Mentha aquatica; `fnftax:2008` = Mentha rotundifolia → **`LEGITIMATE_SOURCE_UPDATE`**.

CSV source vs projection caps (ETHNOBOT/FARMACY/FARMACY_NEW at 60k in JSONL) remain scope caps, not LocalIndex corruption. FTS table present (`scientific_source_fts` content count 299933 across sources). Full in-memory rebuild under ~2 GiB prior failure: not re-proven as full success this section; incremental fixture test passed.

### 8.57.7 P5 — OE / BHL / GBIF

| Source | Active LocalIndex | CapVer live | Notes |
|---|---:|---|---|
| organic_eprints | 189 | STORE_UNAVAILABLE | Bounded sample ≠ full OE corpus |
| bhl | 0 | STORE_UNAVAILABLE | API path; key length 36 in `.env` (not printed) |
| gbif | 0 | not in default accepted-seat run | Conformance dataset included in Filter B |

BHL secret scan (docs/app/tests/config, files ≤2MB, 1988 files): **`NO_EXPOSURE_FOUND_WITHIN_CHECKED_SCOPE`**. Historical docs may still recommend owner rotation if prior out-of-scope exposure occurred; no rotation performed.

### 8.57.8 P6 — Mission artifact archive

- Before this section’s new-file import: `mission_artifacts` **649** total; mission **70**.
- After first v2.2 batch import: **654** total (+5); mission **75**.
- After rev2 report import: **655** total; mission **76**; `pending_eligible=0`; `failed=0`.
- Newly archived (examples):  
  - `maa_179691efe82d75d5_a9c50e07b6ee` — report v22 first capture (SHA `179691efe82d75d5…`; verify+export match).  
  - `maa_50254972c1eb1375_ec187c24e351` — report v22 **rev2** (SHA `50254972c1eb1375…`; verify+export match; distinct title to preserve version after in-place edit).  
  - `maa_abb244d56a1fbb6e_4fb01dd4e056` — Duke reconcile v22 JSON.  
  - `maa_cb9159da0d336157_fc27b6306771` — AGRIS harvest manifest v5.  
  - `maa_50a0e8541a34bffb_0d5272c73056` — CapVer outcomes v22.  
  - `maa_53eb82693bbd5a81_6a4025e0e63c` — LocalIndex evidence v22.
- Prior wave read-back of `maa_b3d28d015174be8d_98daf0f2bc53` (SIX-PRIORITY): verify ok, SHA match.
- Cursor/ChatGPT full transcripts: **`NOT_AVAILABLE_FROM_PROJECT_ENVIRONMENT`**.
- Archive status for available project files: **`COMPLETE_WITHIN_VERIFIED_SCOPE`** (not absolute COMPLETE).

### 8.57.9 P7 — Acceptance / production readiness

- Local unit/conformance filters above: green.
- **PRODUCTION_READY:** no (Cap Store not persisted; AGRIS new harvest not fully ingested; BHL/GBIF LocalIndex empty by design of API seats).
- Owner next step: authorized PostgreSQL endpoint resolving where config expects `postgres` (or explicit approved config change), then CapVer independent read-back.

No commit or push under this section.

## 8.58 — PostgreSQL / CapVer recovery verification v4.4 (2026-10-19)

Append-only. Does not rewrite §8.50–§8.57.

### 8.58.1 Execution identity

- Date: 2026-10-19.
- Branch: `phase-18-m18-ai-marketing-communications`.
- HEAD before and after: `f2025315d8990f52f3c658c968a3ec8252ac18b0` (no commit, no push).
- PHP CLI: 8.4.26 NTS; `pdo_pgsql` / `pgsql` / `PDO` present; `backend/vendor/autoload.php` present; config cache absent.
- PostgreSQL server (local): `pg_isready -h 127.0.0.1 -p 5432` → accepting connections (PostgreSQL 17 bin path). Host name `postgres`: no response / DNS unresolved from Windows.

### 8.58.2 Non-secret connection settings

| Key | Before this section | After minimal fix |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | unchanged |
| `DB_HOST` | `postgres` | **`127.0.0.1`** |
| `DB_PORT` | `5432` | unchanged |
| `DB_DATABASE` | `wsa_enterprise` | unchanged |
| `DB_USERNAME` | `wsa` | unchanged |
| `DB_PASSWORD` | configured (redacted) | unchanged (no rotation) |

Reason for host change: Gate B evidence — Laravel CLI on Windows; Compose hostname `postgres` does not resolve; TCP to `127.0.0.1:5432` accepts. One-setting change only. No Docker. No secret printed. No full `.env` backup copy created (avoids duplicating secrets on disk).

LocalIndex remains SQLite (`scientific_records` / `scientific_record_store.sqlite`); Cap Store path is separate.

### 8.58.3 Gate results (this section)

| Gate | Result | Evidence |
|---|---|---|
| A — project integrity | `VERIFIED` | branch/HEAD/WIP (~150 short lines); no destructive git |
| B — PostgreSQL connectivity for app | `BLOCKED` | DNS fixed; **password authentication failed for user `wsa`** with current `.env` password; no admin `postgres` password available in agent environment (`PGPASSWORD` unset, no `pgpass.conf`) |
| C — Laravel identity + privileges | `BLOCKED` | depends on B |
| D — Cap Store migration/table | `PENDING` | migration file examined; not executed (no authenticated Laravel connection) |
| E — CapVer write + independent read | `NOT_VERIFIED` | live path not run (auth blocked). Unit suite CapVerSeatVerificationServiceTest: **4 passed / 38 assertions** (mocked store) |
| F — documentation | this §8.58 + Arabic report | completed in same wave |

### 8.58.4 Role / database existence findings

- Probe `127.0.0.1` as `wsa` → `authentication_failure` for both `wsa_enterprise` and `postgres` databases.
- Error class implies **role `wsa` exists** (not `role_not_found`). Password in `.env` does **not** match the role.
- Existence of database `wsa_enterprise`: **`NOT_VERIFIED`** (auth failure precedes catalog inspection without admin credentials).
- Per §6.6.3: **no password rotation performed**. Status: `BLOCKED — awaiting approval to rotate wsa password` (or owner-supplied matching secret update to `.env` only).

### 8.58.5 Cap Store architecture (re-checked)

- Default Laravel connection (`pgsql`) used by `EloquentCapabilityStoreRepository` / `CghiaCapabilityCell`.
- Table: `cghia_capability_cell_records`; migration `2026_10_09_140000_…` creates table with no FK dependency on other `cghia_*` tables (standalone `Schema::create` + partial unique indexes).
- CapVer seats authorized: `G1-02`, `G6-20`, `G1-01`, `G1-17` only — **GBIF (`G5-04`) not in `capVerAuthorizedSeats()`**.
- Activation pass requires `PERSISTED|ALREADY_CURRENT` plus contract authorship; `STORE_UNAVAILABLE` never activation-pass.

### 8.58.6 Commands / tests executed this section

- `pg_isready` (127.0.0.1 accept; postgres hostname fail).
- DNS lookup: `postgres` → No such host.
- `php artisan scientific:diagnose-persistence` before host fix → `dns_name_resolution_failure`.
- Same after host fix → `authentication_or_authorization_failure`.
- `e2e-tmp/_v44_pg_probe.php` (PDO probe; secrets redacted in output).
- CapVer unit tests (mock): OK 4/38.
- **Not executed:** `migrate`, live `scientific:capver-accepted` write path, independent Cap Store read, admin `\du`/`\l`, password rotation, Docker.

### 8.58.7 Files changed this section

- `backend/.env` — `DB_HOST` only (`postgres` → `127.0.0.1`); gitignored.
- `backend/e2e-tmp/_v44_pg_probe.php` — new diagnostic probe (WIP/untracked under e2e-tmp).
- This ADR append (§8.58).
- Arabic engineering report under mission folder (same wave).

### 8.58.8 Remaining / next within authorization

1. Owner approval to rotate `wsa` password via interactive `psql` `\password wsa`, **or** update `DB_PASSWORD` in `.env` to the already-correct role password (value never logged).
2. Optionally confirm/create database `wsa_enterprise` with admin session once auth path is known.
3. Then: `scientific:diagnose-persistence` → targeted Cap Store migration → `scientific:capver-accepted` → independent read of `cghia_capability_cell_records` by `adr_id` / `capability_record_id` (not fictional `seat_id` columns).
4. GBIF CapVer remains out of default authorized seat set unless owner extends catalog.

No commit or push under this section.

## 8.59 — Operational Restoration Checkpoint — 2026-10-19

**Status:** DOCUMENTATION / RUNTIME FREEZE REFERENCE. **No commit/push/deploy.** Append-only after §8.58. Does not rewrite §8.50–§8.58.

### 8.59.1 Purpose

Record the successful local Docker stack restoration of 2026-10-19 as a frozen operational checkpoint before two later, separately authorized reviews: (A) CapVer missing-table schema review; (B) backup and recovery review. Neither review is started by this section.

### 8.59.2 Checkpoint summary (from prior execution reports — not re-probed here)

- Restored the local environment that had been running before the 2026-10-15 Docker stop.
- Freed host TCP 5432 by stopping Windows service `postgresql-x64-17` (StartType remained `Automatic` during that stop). Per **owner attestation**, PostgreSQL 17 was later removed from Windows; whether Windows data directories were deleted was **not** verified in the restoration reports.
- Original Compose PostgreSQL returned on Docker: `wsa-enterprise-postgres-1` / PostgreSQL 16.14 / database `wsa_enterprise` / volume `wsa-enterprise_postgres_data`.
- Running/healthy (7/10): `postgres-1`, `redis-1`, `backend-1`, `frontend-1`, `nginx-1` (host **8079**), `queue-1`, `scheduler-1`.
- Left stopped on purpose (3/10): `wsa-r7-store-pg-20261015`, `wsa-backup-verification-pg`, `wsa-enterprise-postgres-1-orphan` (shares `postgres_data` — must not run with the live primary).
- Operator URL: `http://127.0.0.1:8079/` — owner reported no apparent issues; not a claim of full functional coverage.
- Known gaps: `cghia_capability_cell_records` absent; no migrate/restore during restoration; no new backup created; backup integrity not proven in restoration; PHP `intl` missing affected `db:show` formatting only; queue bind path via `/run/desktop/mnt/host/g/...` unchanged.

### 8.59.3 Decisions locked by this checkpoint

- Preserve original PostgreSQL/Redis data; no container recreate; no R7/backup/orphan start.
- This checkpoint does **not** authorize schema changes, data deletion, or service redesign.

### 8.59.4 Deferred work (not started)

- **Task A — CapVer Schema Review:** cause of missing `cghia_capability_cell_records`; migration/contract analysis; report only until separate approval.
- **Task B — Backup and Recovery Review:** inventory backups; integrity/readability; safe isolated restore plan; no create/restore in that future task until separately approved.

### 8.59.5 Artifact

Arabic checkpoint record: `docs/architecture/science-source-expansion/2026-10-17-SOURCE-EXPANSION-MISSION/2026-10-19-OPERATIONAL-RESTORATION-CHECKPOINT.ar.md`

No commit or push under this section.

## 8.60 — Migration gap closure + CapVer PostgreSQL persistence — 2026-10-19

**Status:** EXECUTED AND VERIFIED under owner MASTER ENGINEERING EXECUTION authorization. Append-only after §8.59. Does **not** rewrite §8.50–§8.59. **No commit/push.**

### 8.60.1 Scope

Close demonstrated Cap Store / CGHIA / Platform Admin schema gaps on primary Docker PostgreSQL; verify CapVer persistence; leave untracked scientific-source migration pending architectural SoT decision; create and verify a fresh logical backup before any schema change.

### 8.60.2 Runtime identity (CONFIRMED)

| Item | Observed |
|------|----------|
| Health | `GET http://127.0.0.1:8079/api/v1/health` → HTTP 200 `{"status":"ok"}` |
| Containers (healthy) | `postgres-1`, `redis-1`, `backend-1`, `frontend-1`, `nginx-1`, `queue-1`, `scheduler-1` |
| PostgreSQL | container `wsa-enterprise-postgres-1` · version **16.14** · volume `wsa-enterprise_postgres_data` |
| Database session | `wsa_enterprise` / user `wsa` |
| Laravel connection | driver `pgsql` · host `postgres` · database `wsa_enterprise` · user `wsa` · `APP_ENV=local` · config cache absent |
| Dotenv note | Duplicate `FAOSTAT_ENABLED=true` on lines 73–74; last-wins identical value — **no behavior change**; left unmodified |

### 8.60.3 Backup (VERIFIED — archive listing level)

| Field | Value |
|-------|-------|
| Path | `backups/wsa_pre_migrate_20261019_215350.dump` |
| Format | PostgreSQL custom (`-F c`) · `--no-owner --no-privileges` |
| Size | 4 600 454 bytes (nonzero) |
| SHA-256 | `B6580C9BACF4F52E0C94DE72FA732CF184A76E761ED7800CA8A0BCB1227EEDF1` |
| Archive TOC | 1609 entries · `dbname: wsa_enterprise` · dumped from 16.14 · `pg_restore --list` EXIT 0 |
| Validation level | **Archive listing + nonempty file + SHA-256.** Isolated restore into a non-primary instance was **NOT** performed in this session. |
| Recovery plan | Stop migrations; keep dump; restore only into an isolated Postgres instance that does **not** share `wsa-enterprise_postgres_data`; never restore over live primary as routine recovery. |
| Git | `/backups/` added to `.gitignore` so dumps are not accidentally staged. Dump file itself was never committed. |

### 8.60.4 Migration inventory (recalculated)

| Metric | Before | After |
|--------|--------|-------|
| Migration files on disk | 63 | 63 |
| Records in `migrations` | 55 | **62** |
| Pending | 8 | **1** (scientific untracked only) |
| Orphan DB records without files | 0 | 0 |

### 8.60.5 Executed migrations (dependency order used)

CapVer table is structurally independent of IC/Identity/Correlation (no FKs). Priority CapVer-first per authorization; then remaining CGHIA; then Platform Admin after requirement proof.

| # | Migration | Batch | Evidence |
|---|-----------|-------|----------|
| 1 | `2026_10_09_140000_create_cghia_capability_cell_records_table` | 3 | `--pretend` reviewed; DONE; table+partial UQs present; health 200 |
| 2 | `2026_10_01_150000_create_durable_correlation_records_table` | 4 | pretend+DONE; health 200 |
| 3 | `2026_10_02_160000_create_cghia_identity_binding_records_table` | 5 | pretend+DONE; health 200 |
| 4 | `2026_10_02_230000_create_cghia_integration_classification_records_table` | 6 | pretend+DONE; health 200 |
| 5 | `2026_10_09_190000_add_single_active_index_to_cghia_integration_classification_records` | 7 | ACTIVE-dupe preflight 0 rows; index `cghia_ic_records_single_active_uq` created; health 200 |
| 6 | `2026_09_23_180000_add_platform_administrator_identity_to_users` | 8 | pretend+DONE; column default false; health 200 |
| 7 | `2026_09_23_180100_create_platform_rbac_tables` | 9 | pretend+DONE; 5 platform_* tables; health 200 |

### 8.60.6 Deliberately left pending

| Migration | Classification | Reason |
|-----------|----------------|--------|
| `2026_10_15_220000_create_scientific_source_record_store_tables.php` | **REQUIRES_OWNER_DECISION** | Git status remains `??` (untracked). **Not** applied to PostgreSQL. **Not** staged/moved/deleted. Active LocalIndex SoT is connection `scientific_records` → **sqlite** `storage/app/scientific_record_store.sqlite` with `scientific_source_records=300635`, FTS present, generations=50. PostgreSQL has **0** `scientific_source_*` tables. Applying this migration to primary PG would create an unapproved parallel store unless owner authorizes PG cutover. |

### 8.60.7 Platform Admin / RBAC decision

**REQUIRED and EXECUTED** (not deferred).

Evidence of current approved requirement:

- Backend: `RequirePlatformAdministrator` middleware, `PlatformRbacService`, `PlatformAdministratorAuthorizer`, Admin API controllers, `User::isPlatformAdministrator()`.
- Mobile: `admin-mobile` API client + auth guard tests for `is_platform_administrator`.
- Architecture: `docs/architecture/platform-admin/PLATFORM-ADMIN-ARCHITECTURAL-DECISIONS.md`; Phase-10 release gate Platform RBAC PASS.
- Feature tests: `Adm04PlatformAdministratorArchitectureTest` — **19 tests, 88 assertions, OK**.

Post-migration privilege check: `users` with `is_platform_administrator=true` → **0 / 11** (no unintended elevation). Migrations create schema only; no default administrator seeded.

### 8.60.8 CapVer results

| Check | Result |
|-------|--------|
| Schema present | `cghia_capability_cell_records` **CONFIRMED** |
| Partial unique indexes | `cghia_cap_cells_current_canonical_uq`, `cghia_cap_cells_current_seat_scoped_uq` **VERIFIED** |
| Model/fillable/casts vs migration | Agreement — **no code defect demonstrated; no application code changed** |
| `scientific:diagnose-persistence` | Cap Store `table_present=true`, `failure_class=successful_connection` |
| Unit/Feature CapVer+Capability suite | `--filter 'CapVer\|CapabilityStore\|EloquentCapability'` → **62 tests, 270 assertions, OK** (exit 0) |
| Live PostgreSQL probe | Unique synthetic persist → CURRENT row readable after reconnect; duplicate CURRENT → `CapabilityInvariantViolation`; probe rows deleted | **VERIFIED** |
| Code fixes | **None** (no demonstrated defect) |

### 8.60.9 Scientific persistence decision

- **Authoritative LocalIndex (current):** SQLite file store on `scientific_records` connection — preserved untouched.
- **PostgreSQL scientific_source_*:** absent — intentional under current config.
- Untracked migration disposition: **REQUIRES_OWNER_DECISION** (see 8.60.6). Issue **not** falsely closed.

### 8.60.10 Tests and health (actual)

| Command | Exit | Result |
|---------|------|--------|
| Health after each of 7 migrations | — | HTTP 200 each time |
| Final health | — | HTTP 200 |
| PHPUnit CapVer/Capability filter | 0 | 62 / 270 OK |
| PHPUnit Adm04 Platform Admin | 0 | 19 / 88 OK |
| `scientific:diagnose-persistence` | 0 | Cap Store table present |
| Isolated restore of backup dump | — | **NOT TESTED** |

### 8.60.11 Unresolved / owner next steps

1. Decide whether LocalIndex must cut over to PostgreSQL (apply + track `2026_10_15_220000_create_scientific_source_record_store_tables`) or remain SQLite SoT; do not apply blindly.
2. Optional: isolated `pg_restore` of `wsa_pre_migrate_20261019_215350.dump` into a throwaway container (not sharing primary volume) to raise backup validation beyond archive listing.
3. Optional: run `scientific:capver-accepted` for owner-accepted seats now that Cap Store schema exists (does not fabricate success).
4. No commit/push performed under this section.

## 8.61 — Post-remediation verification gate — 2026-10-19

**Status:** VERIFICATION EXECUTED (no schema remigration). Append-only after §8.60. Does **not** rewrite §8.60. **No commit/push.** Does **not** declare `PRODUCTION_READY` while Scientific SoT remains open.

### 8.61.1 Backup integrity (re-verified)

| Check | Result |
|-------|--------|
| Path | `backups/wsa_pre_migrate_20261019_215350.dump` (unchanged) |
| Size / mtime UTC | 4,600,454 bytes · 2026-10-19 18:54:04 UTC |
| SHA-256 | `B6580C9BACF4F52E0C94DE72FA732CF184A76E761ED7800CA8A0BCB1227EEDF1` — **matches** historical value |
| `pg_restore --list` | EXIT 0 · TOC Entries 1609 · review artifact `backups/wsa_pre_migrate_20261019_215350_toc.list` |
| TABLE defs | **148** unique |
| TABLE DATA | **148** unique · **0** tables lacking TABLE DATA · **0** orphan TABLE DATA |
| SEQUENCE / SEQUENCE SET | 138 / 138 |
| INDEX / CONSTRAINT (TOC) | 187 / 570 |
| Post-migrate objects in dump | CapVer/CGHIA/platform_* tables **absent** (expected: dump taken before the seven migrations) |

**Completeness judgment:** Dump schema↔data inventory is internally consistent (148/148). Completeness vs a frozen pre-migrate live snapshot list is **inferential**: restored DB had 148 public tables, `migrations=55` (batches 1=54 + 2=1), `users=11`; live post-migrate DB now has 157 tables (=148+9 new tables from CapVer/correlation/identity/IC/platform RBAC) and `migrations=62`. Full per-table row-count parity across all 148 tables was **not** executed.

### 8.61.2 Isolated restore

| Item | Result |
|------|--------|
| Isolation | Container `wsa_restore_test` · volume `wsa_restore_test_vol` only · **not** `wsa-enterprise_postgres_data` · image `postgres:16.14` |
| First restore (default) | EXIT 1 · 286 errors all `role "wsa" does not exist` · still yielded 148 tables / users=11 / migrations=55 |
| Clean restore | `pg_restore --no-owner --no-acl` · **EXIT 0** · **0 ERROR** |
| Restored CapVer table | `to_regclass(...cghia_capability_cell_records)` = NULL (**expected** for pre-migrate dump) |
| Cleanup | Container + volume removed after identity proof · backup file retained · live health still HTTP 200 · live migrations still 62 |

### 8.61.3 CapVer ALREADY_CURRENT contract

**Code facts:** `CapVerSeatVerificationService` calls `findCurrentFact` first; if CURRENT + VERIFIED → `ALREADY_CURRENT` and **does not** call `persistCurrent`. Repository `persistCurrent` still throws `CapabilityInvariantViolation` when a CURRENT head already exists (conflict path). Unit test `test_already_current_verified_is_idempotent` asserts `ALREADY_CURRENT` and `persistCurrent` never called.

**Tests this gate:** CapVerSeatVerificationServiceTest 4/38 OK; CapVer\|CapabilityStore filter 62/270 OK (exit 0). No application code change (no defect vs ADR contract).

### 8.61.4 Migration batches (live, read-only)

| Batch | Count | Interpretation |
|-------|------:|----------------|
| 1 | 54 | Historical baseline batch |
| 2 | 1 | `2026_09_21_140000_create_research_feedback_dataset_table` |
| 3–9 | 1 each | The seven gated migrations from §8.60 (CapVer-first order) |
| Total | 62 | Scientific migration **not** in table · 0 duplicate migration names |

Matches **scenario B** (55 prior = 54+1), not A.

### 8.61.5 Platform Admin provisioning

Supported mechanisms (no admin created this gate):

1. `deploy:bootstrap-admin` / `ProductionAdminBootstrap` — requires `ADMIN_BOOTSTRAP_ENABLED` + `ADMIN_PASSWORD` (and email); grants via `PlatformRbacService::grantPlatformAdministrator`.
2. Admin API `POST/PATCH /api/v1/admin/users` — middleware `platform.administrator` + permission `platform.users.manage`; cannot self-escalate as org admin (`test_spoofed_x_organization_id_cannot_create_platform_authority`).

Live: `is_platform_administrator` true count remains **0 / 11** (unchanged; by design until explicit bootstrap/API). Tests: Adm04 + ProductionAdminBootstrap **25/107 OK**.

### 8.61.6 Scientific SoT

Still **REQUIRES_OWNER_DECISION**. Evidence unchanged in role: SQLite LocalIndex `scientific_source_records=300635` reachable; PostgreSQL has 0 `scientific_source_*` tables; untracked migration remains `??` and **not** applied.

### 8.61.7 Runtime / Git

Health HTTP 200; Cap Store `table_present=true`; WIP short-status count ~151; no commit/push.

No commit or push under this section.

## 8.62 — Option A: operational SQLite scientific store — 2026-10-20

**Status:** VERIFIED for this operational phase. Append-only after §8.61. Does **not** rewrite §8.41 or §8.59–§8.61. **No commit/push.** Does **not** apply `2026_10_15_220000_create_scientific_source_record_store_tables.php` to PostgreSQL and does **not** copy scientific rows into PostgreSQL.

### 8.62.1 Owner decision and relation to §8.41

The owner selected **Option A** for this phase: the operational scientific record store remains the SQLite file on the `scientific_records` connection.

§8.41 still records the earlier design for a PostgreSQL durable store and an authored, not-deployed migration. That historical design is **not** rewritten here. What this section records is the operational fact and the owner’s phase decision: primary PostgreSQL has **zero** `scientific_source_*` tables, the migration file stays untracked (`??`), and creating a parallel PostgreSQL store is **not** authorized.

### 8.62.2 Docker recovery observed this session

| Item | Observed |
|------|----------|
| Engine | Docker 29.6.2 · 10 containers · 7 running · 3 stopped |
| Stopped, left untouched | `wsa-r7-store-pg-20261015`, `wsa-backup-verification-pg`, `wsa-enterprise-postgres-1-orphan` |
| Volumes | `wsa-enterprise_postgres_data` still mounted on `wsa-enterprise-postgres-1`. No volume created or deleted |
| Startup | PostgreSQL and Nginx were briefly `unhealthy`. Postgres log: `database system was not properly shut down; automatic recovery in progress`, then `database system is ready to accept connections`. After that, all seven application containers were `healthy` |
| During startup only | `GET /api/v1/health` returned HTTP 500 while Postgres was still rejecting connections. After recovery it returned HTTP 200 `{"status":"ok"}` |

No container was created, recreated, or removed.

### 8.62.3 Primary PostgreSQL — read only

| Check | Result |
|-------|--------|
| Session | `wsa_enterprise` / `wsa` / PostgreSQL 16.14 |
| Laravel | driver `pgsql`, host `postgres`, database `wsa_enterprise`, user `wsa` |
| Config cache | absent in the container and absent at `backend/bootstrap/cache/config.php` |
| `migrations` rows | **62** |
| `cghia_capability_cell_records` | present |
| CapVer indexes | `cghia_capability_cell_records_pkey`, `cghia_capability_cell_records_capability_record_id_unique`, `cghia_cap_cells_subject_lifecycle_idx`, `cghia_cap_cells_current_canonical_uq`, `cghia_cap_cells_current_seat_scoped_uq` |
| `scientific_source_*` on PostgreSQL | **0** |
| migrate / rollback | **not run** |

### 8.62.4 Actual SQLite role

`.env` has no `SCIENTIFIC_RECORD_STORE_*` overrides. `config/database.php` therefore uses connection `scientific_records` with driver `sqlite` and database `storage/app/scientific_record_store.sqlite`. Laravel in the backend container resolved that path to `/var/www/html/storage/app/scientific_record_store.sqlite`.

`AgriculturalIntelligenceServiceProvider` binds `ScientificRecordStore` to `DatabaseScientificRecordStore` on `agricultural_intelligence.local_index.connection` (default `scientific_records`). `LocalIndexSearchEngine` uses `SqliteFtsScientificIndex` when `search_backend` is `auto` or `fts5` and the driver is sqlite. The `search_backend` key is absent, so the code default `auto` selects FTS5.

| Object | Role |
|--------|------|
| `scientific_source_records` | durable rows. **300 635** total; **300 121** `active`; **514** not active |
| `scientific_source_states` | committed generation per source: `organic_eprints=2`, `dr_duke=19`, `agris=29` |
| `scientific_source_generations` | **50** append-only generation rows |
| `scientific_source_fts` | FTS5 virtual table (`unicode61`). **300 121** rows, equal to the active-record count |
| `scientific_source_fts_meta` | same three sources and the same generations, `index_version=sqlite_fts_scientific_index.v1` |
| Triggers | **0**. FTS is updated by `SqliteFtsScientificIndex::rebuild`, `synchronize`, and `apply`, not by SQLite triggers |

The SQLite file’s own `migrations` table contains only `2026_10_15_220000_create_scientific_source_record_store_tables`. That is the SQLite store’s schema history. It is not a PostgreSQL migration record.

SQLite 3.46.1. `PRAGMA journal_mode` = `delete`.

### 8.62.5 Backup and integrity

| Field | Value |
|-------|--------|
| Method | PHP `SQLite3::backup()` from a read-only open of the live file |
| Path | `backups/scientific_record_store_pre_optionA_20261020_213410.sqlite` |
| Size | 1 354 821 632 bytes |
| SHA-256 | `A7FEB1170BA77497BF18173441E0212F6DF0A7397856C7740BC9F94756EEBBC9` |
| Open | succeeded |
| Live `PRAGMA integrity_check` | `ok` (`query_only=ON`) |
| Backup `PRAGMA integrity_check` | `ok` |
| FTS5 `integrity-check` | executed **only on the backup**. Returned **0** rows (`ok`). SHA-256 after that command was **unchanged** |
| Live file after checks | size unchanged; mtime still 2026-10-19 20:14:04 +03:00 |

### 8.62.6 Tests actually run

| Command | Result |
|---------|--------|
| `phpunit` `LocalIndexLifecycleTest` + `ScientificRecordsSqliteDurabilityTest` + `IncrementalBulkIngestDoesNotWithdrawPeersTest` | **OK, 16 tests, 115 assertions, exit 0**. These use an in-memory store or `storage/framework/testing/…`, not the operational file |
| First directory run of `tests/Unit/Agriculture/Research/Search/LocalIndex` | **21 tests, 136 assertions, 1 error, exit 2** |
| The error | `LocalIndexSearchIntegrationTest::test_a_local_index_source_runs_through_the_production_orchestrator_without_http` issued `DELETE FROM scientific_source_fts WHERE source_key = dataset_example` on the **operational** file because the container’s `SqliteFtsScientificIndex` singleton was already bound there. SQLite returned `database is locked` because integrity_check held the file. The delete did not commit |
| Post-check of that attempt | live size and mtime unchanged; `dataset_example` FTS rows = **0** |
| `tests/Pgsql/*` | **NOT TESTED**. That suite is bound to `phpunit.pgsql.xml` and was not run, so it could not touch `wsa_enterprise` |

No PHP application code was changed.

### 8.62.7 Git

WIP short-status count remained 151. The scientific migration file is still `??`. `/backups/` is already ignored. No commit and no push.

## 8.66 — Scoped GBIF (`G5-04`) CapVer authorization — 2026-10-22

Append-only. Does not rewrite §8.58.5 or any earlier section. Does not close Phase 1. Does not authorize a PostgreSQL migration, a new CapVer table, or seat `G6-06`.

### 8.66.1 Status and scope

- Date: 2026-10-22.
- Status: authorization recorded. Persistence completion evidence is **not** established by this section.
- Scope: one seat, `G5-04` (source key `gbif`, REST species search).
- This section does not authorize CapVer for any other seat and does not change onboarding outcomes for seats other than the already-recorded `onb-r9-20261016-G5-04` contract.

### 8.66.2 Authority

The 2026-10-22 owner execution assignment “Five-Source Completion, Existing SQLite CapVer Verification, Governed AGRIS Expansion, and Pre-Commit Review” explicitly includes GBIF (`G5-04`) in the CapVer scope. That assignment is the authority for this limited supersession.

### 8.66.3 Supersession

§8.58.5 remains historically true for the state it recorded: `capVerAuthorizedSeats()` then contained only `G1-02`, `G6-20`, `G1-01`, and `G1-17`, and GBIF was excluded from that list.

This section supersedes **only** that exclusion, and only for seat `G5-04`. The original §8.58.5 sentences are unchanged. No other restriction in §8.58.5 is superseded. `G6-06` / `dr_duke_g6` stays deferred.

### 8.66.4 Code consequence

`AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats()` includes `G5-04`. The existing NextGate decision id `onb-r9-20261016-G5-04` is visible to the CapVer onboarding gate. `CapVerSeatVerificationService` carries one ACCESS_METHOD cell spec:

- capability decision identity: `cap-r9-20261016-G5-04-search` (already on the G5-04 contract)
- capability record id: `cap-rec-r9-20261016-G5-04-api`
- access method: `api`
- snapshot: `gbif.rest_api.species_search.v1`

### 8.66.5 Evidence required for completion

Completion of this authorization requires all of the following, and this section does not claim them:

1. A write through the existing SQLite capability-persistence table and its established repository.
2. An independent read-back of one CURRENT `VERIFIED` cell for `G5-04` / `cap-rec-r9-20261016-G5-04-api`.
3. A repeated authorized run that returns `ALREADY_CURRENT` and does not insert a second current cell.

### 8.66.6 What this section does not authorize

- No new SQLite table, schema, or migration for CapVer.
- No PostgreSQL connection and no CapVer write to PostgreSQL. Rows written to `cghia_capability_cell_records` on PostgreSQL before this section, if any, are historical evidence only.
- No move of the scientific record store off SQLite Option A (§8.62).
- No activation of `G6-06`.
- No increase of the Dr. Duke 60 000-record projection caps.
- No claim that a passing contract test or a live GBIF HTTP response is itself capability persistence.

### 8.66.7 Execution result on 2026-10-22

Host PHP 8.4.26 has `pdo_sqlite`. The scientific SQLite file `storage/app/scientific_record_store.sqlite` was not opened. Its filesystem identity was unchanged: length `1354821632`, LastWriteTimeUtc `2026-10-19T17:14:04Z`, SHA-256 `3F0114A665682000637C318E07649F62B1A3B4482635A25A19726317EDD19E8C`. Its schema history (§8.62.4) contains only `2026_10_15_220000_create_scientific_source_record_store_tables`. The isolated test file `scientific_records_108.sqlite` contains scientific-record tables only, not `cghia_capability_cell_records`.

The implemented Cap Store repository remains `EloquentCapabilityStoreRepository` on the default Laravel connection. No separate SQLite capability file or connection exists in the repository. Creating a table or pointing the repository at PostgreSQL was not done. `scientific:capver-accepted` was not executed. CapVer persistence for all five seats is therefore **not verified** on SQLite in this section.

No commit and no push.

## 8.67 — Owner authorization to use PostgreSQL `wsa_enterprise` for CapVer cells — 2026-10-23

Append-only. Does not rewrite §8.58.5, §8.60, §8.62, or §8.66. Does not move the scientific record store. Does not claim that a CapVer cell was written on this date.

### 8.67.1 Status and scope

- Date: 2026-10-23.
- Status: authorization recorded. Persistence and idempotency evidence are **not** established by this section.
- Scope: CapVer capability-cell persistence for seats already present in `AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats()` (`G1-02`, `G6-20`, `G1-01`, `G1-17`, `G5-04`).
- `G6-06` / `dr_duke_g6` stays outside that list.

### 8.67.2 Authority

The 2026-10-23 owner execution assignment “CapVer Persistence Recovery, PostgreSQL Integration, Backup Safety, Read-Back & Idempotency Closure” explicitly authorizes PostgreSQL database `wsa_enterprise` for CapVer. That authorization supersedes, for CapVer cell persistence only, the 2026-10-22 execution ban on opening PostgreSQL.

### 8.67.3 What this does and does not change

- The Cap Store contract remains table `cghia_capability_cell_records` and migration `2026_10_09_140000_create_cghia_capability_cell_records_table.php`. No new table and no new migration.
- `EloquentCapabilityStoreRepository` and `CghiaCapabilityCell` keep using the default Laravel connection. With `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, and `DB_DATABASE=wsa_enterprise`, that default is the authorized CapVer target. The global default was not changed.
- §8.66.5 still describes the evidence shape (one CURRENT `VERIFIED` cell, then `ALREADY_CURRENT` on repeat). The store that must supply that evidence is PostgreSQL `wsa_enterprise`, not a new SQLite file. §8.66.6’s “no PostgreSQL write” sentence remains the historical limit of the 2026-10-22 mission. This section supersedes that sentence only for CapVer cells on `wsa_enterprise`.
- ADR-023 §8.62 Option A remains the scientific record store. `scientific_records` stays SQLite. Scientific rows are not moved.

### 8.67.4 Execution result on 2026-10-23

Host PHP 8.4.26 has `pdo_pgsql`. `backend/bootstrap/cache/config.php` is absent. A TCP connect to `127.0.0.1:5432` returned connection refused (Windows error 10061). `Test-NetConnection` reported `TcpTestSucceeded=False`. The same probe at `2026-10-23T10:07:23Z` again returned `TCP=closed errno=10061` with `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=wsa_enterprise`, and `DB_USERNAME=wsa`. No PostgreSQL server accepted a connection, so the connection-identity gate failed before any write.

Consequences:

- `scientific:capver-accepted` was not executed.
- No migration was applied. The backup gate was not reached because there was no reachable target database to back up.
- No row was read back. Idempotency was not exercised.
- The scientific SQLite file was not opened. Its length stayed `1393659904` and its `LastWriteTimeUtc` stayed `2026-10-22T09:53:58Z`.

No commit and no push.

## 8.68 — CapVer SQLite return stopped at the historical-data gate — 2026-10-23

Append-only. Does not rewrite §8.60, §8.62, §8.66, or §8.67. Does not create a CapVer SQLite file. Does not claim that the 2026-10-22 PostgreSQL cells were reread.

### 8.68.1 Authority and status

The 2026-10-23 mandate “CapVer SQLite Persistence Recovery” directs CapVer persistence back to a dedicated SQLite store and requires a historical-data gate before any empty store is created. Status of this section: **gate not passed**. No persistent CapVer write was performed.

### 8.68.2 What was inspected

| Artifact | Result |
|---|---|
| `backups/wsa_pre_migrate_20261019_215350.dump` | Present. 4 600 454 bytes. LastWriteTimeUtc 2026-10-19 18:54:04. TOC list has **no** `cghia_capability_cell_records`. §8.61.2 already recorded the restored table as NULL because the dump predates the CapVer migration |
| Other files in `backups/` | No later PostgreSQL dump. The scientific SQLite backup is not a CapVer store |
| TCP `127.0.0.1:5432` | Earlier the same day: Windows 10061 connection refused. Recheck during this section: Windows 10060 timed out. No PostgreSQL session |
| `docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md` | Historical report of four `CURRENT` `VERIFIED` cells (`G1-02`, `G6-20`, `G1-01`, `G1-17`) written through a PostgreSQL session. `G5-04` was absent. This section did not reread those rows |

### 8.68.3 Decision

Those four cells may still exist in the PostgreSQL data directory that accepted the 2026-10-22 write. No approved export of them was found, and the server did not accept a connection. Creating `backend/storage/app/capver.sqlite` empty would ignore that evidence. The return to SQLite is not authorization to discard it.

No dedicated CapVer database was created. The existing migration was not applied to a persistent file. `storage/app/.gitignore` already ignores `*`, so a future `capver.sqlite` under that directory would be untracked; that rule was not changed.

Isolated evidence only: PHPUnit `RefreshDatabase` on the testing SQLite connection ran the existing migration. SQLite library 3.53.4. Laravel framework v12.69.2. Filter of schema plus partial-unique tests: **3 tests, 35 assertions, exit 0**. That does not populate a durable CapVer store.

The scientific file `storage/app/scientific_record_store.sqlite` stayed at length `1393659904` and LastWriteTimeUtc `2026-10-22T09:53:58Z`.

No commit and no push.

## 8.69 — One-pass CapVer recovery stopped: historical rows were not recoverable — 2026-10-23

Append-only. Does not rewrite §8.60 through §8.68. Does not create `backend/storage/app/capver.sqlite`. Does not claim that the 2026-10-22 PostgreSQL cells were reread or regenerated.

### 8.69.1 Report classification

`docs/2026-10-22-FIVE-SOURCE-COMPLETION-REPORT.ar.md` is a summary, not a recovery source. For `G1-02`, `G6-20`, `G1-01`, and `G1-17` it records `capability_record_id`, dimension family `ACCESS_METHOD`, dimension code, command statuses `PERSISTED` then `ALREADY_CURRENT`, and an independent read of `VERIFIED` / `CURRENT`. It does not record `canonical_identity_id`, `seat_override_applied`, `evidence_freshness_class`, `snapshot_version`, `update_method`, `limitation_text`, `verified_at`, `observed_at`, `capability_evidence_refs`, `created_at`, or `updated_at`. Those columns are required or nullable by `2026_10_09_140000_create_cghia_capability_cell_records_table.php`. The table has no `seat_id`, `decision`, `persisted_at`, or `provenance` column, and no foreign keys.

Saved execution JSON files that contain evidence lists, including `e2e-tmp/capver_v22.json`, record `capability_persisted: false` and `STORE_UNAVAILABLE`. They are not copies of the persisted rows. No JSON in the repository contains `"capability_store_status": "PERSISTED"`.

### 8.69.2 Regeneration

`capability_record_id` values for the four seats are string literals in `CapVerSeatVerificationService::seatSpec()`. They are not hashes and do not include the write timestamp. `verified_at` is the execution clock. The current authorized batch also includes `G5-04`, which the 2026-10-22 report excluded. The service file is untracked, so Git history cannot prove the four specifications were unchanged at the successful write. Verdict: **regeneration possible but not equivalent**. `scientific:capver-accepted` was not run.

### 8.69.3 PostgreSQL and backups

No Windows service name matched PostgreSQL. Nothing was listening on local port 5432, and no `postgres` process was running. `pg_ctl` was not on `PATH`. `C:\Program Files\PostgreSQL\17\bin\postgres.exe` is absent. The data directory there is PostgreSQL 17, has no `postmaster.pid`, and its only log is `postgresql-2026-10-19_015820.log`. That log says role `wsa` does not exist. It was not started. It is not the PostgreSQL 16 session named in the 2026-10-22 report.

Named backup locations contained one PostgreSQL dump: `backups/wsa_pre_migrate_20261019_215350.dump`, 4 600 454 bytes, LastWriteTimeUtc 2026-10-19 18:54:04. Its TOC has no `cghia_capability_cell_records`. `backend/storage/app` has `scientific_record_store.sqlite` only. The other named backup directories were absent or had no CapVer dump. No recovery JSON was written, because the missing persisted fields were not invented.

### 8.69.4 Isolated migration evidence

On `backend/storage/framework/testing/migration_test.sqlite` only, the existing migration went up and rolled back with exit 0. SQLite 3.53.4. The partial unique indexes and the unique `capability_record_id` index were present. A synthetic fixture read back as `CURRENT`. A second current fact and a duplicate `capability_record_id` were rejected. `PRAGMA integrity_check` returned `ok`. After rollback the CapVer table was absent. This file is covered by `backend/storage/framework/testing/.gitignore`. It is not an operational store and contains no historical cells.

The scientific file stayed at length `1393659904` and LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`.

No commit and no push.

## 8.70 — Historical CapVer rows recovered into dedicated SQLite — 2026-10-23

Append-only. Does not rewrite §8.60 through §8.69. Does not move the scientific record store. Does not treat a regenerated `verified_at` as the historical row.

### 8.70.1 Source

Docker engine 29.6.2 was available. Inspection exit codes were 0. Container `wsa-enterprise-postgres-1` was already running (`postgres:16-alpine`, server 16.14, healthy), started `2026-10-23T11:42:54Z`. Its only data mount is volume `wsa-enterprise_postgres_data` at `/var/lib/postgresql/data`. Database name `wsa_enterprise`, role `wsa`. `wsa-enterprise-backend-1` and `wsa-enterprise-redis-1` were also running, so this container was not stopped after the read.

The read used `BEGIN READ ONLY` with `transaction_read_only=on`. No SQL write, DDL, or migration was issued against that database. The table `public.cghia_capability_cell_records` held exactly four rows.

### 8.70.2 Recovered rows

| Seat | capability_record_id | storage id | verified_at | lifecycle |
|---|---|---|---|---|
| `G1-02` | `cap-rec-r11-20261018-G1-02-oai_pmh` | 2 | `2026-10-22T06:48:42+00:00` | `CURRENT` / `VERIFIED` |
| `G6-20` | `cap-rec-r11-20261018-G6-20-api` | 3 | `2026-10-22T06:48:44+00:00` | `CURRENT` / `VERIFIED` |
| `G1-01` | `cap-rec-r12-20261018-G1-01-bulk` | 4 | `2026-10-22T06:48:44+00:00` | `CURRENT` / `VERIFIED` |
| `G1-17` | `cap-rec-r12-20261018-G1-17-static` | 5 | `2026-10-22T06:48:44+00:00` | `CURRENT` / `VERIFIED` |

Storage id 1 is absent. `canonical_identity_id` and `limitation_text` are null. `seat_override_applied` is false. Evidence refs, snapshot, `observed_at`, and `created_at`/`updated_at` are in the recovery artifact. `G5-04` is not among these rows.

Artifact: `backend/storage/framework/recovery/capver-historical-recovery.json`. SHA-256 `e69ea920ced083f72aa1741aa78e162c0f0590dc1760553d65b98e765c3b78c8`. That hash covers the file bytes. Per-record hashes inside the file cover the canonical persisted columns. Neither hash, by itself, replaces the read-only source check.

### 8.70.3 Regeneration

`capability_record_id` remains a literal in `CapVerSeatVerificationService::seatSpec()`. `verified_at` is the execution clock. `executeAcceptedSeats()` also includes `G5-04`, which was not in the historical table. Verdict: **regeneration possible but not equivalent**. The historical rows were imported. They were not regenerated.

### 8.70.4 Dedicated store

Connection name `capver`, driver sqlite, file `backend/storage/app/capver.sqlite`. `CghiaCapabilityCell` and `EloquentCapabilityStoreRepository` transactions use `capability_store.connection`, which defaults to `capver`. PHPUnit forces that setting to `sqlite` and `CAPVER_DATABASE` to `:memory:` so the existing RefreshDatabase tests stay on one connection and do not open the operational file. The migration refuses the scientific record store path and PostgreSQL database `wsa_enterprise`. The schema contract is unchanged.

Import is idempotent. A different payload for the same `capability_record_id` throws and does not overwrite. SQLite backup uses `SQLite3::backup` into `backend/storage/app/capver.sqlite.backup`, and restore was checked by writing a new file under `storage/framework/testing`, not by replacing the source.

After import, `scientific:capver-accepted` was run separately for `G1-02`, `G6-20`, `G1-01`, and `G1-17`. Each returned `ALREADY_CURRENT` with exit 0. The full authorized batch was not run, so `G5-04` was not inserted. An independent PDO read-back after those commands still matched the four artifact hashes. `PRAGMA integrity_check` was `ok`.

The scientific file stayed at length `1393659904` and LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`.

No commit and no push.

## 8.71 — G5-04 GBIF capability record completion and persistence verification — 2026-10-23

Append-only. Does not rewrite §8.60 through §8.70. Does not claim that this row was recovered from the 2026-10-22 PostgreSQL table. That table had four rows and no `G5-04`.

### 8.71.1 Authority and bounds

§8.66 authorizes seat `G5-04` only, source key `gbif`, and one `ACCESS_METHOD` / `api` cell. §8.66.4 names `cap-rec-r9-20261016-G5-04-api`, decision `cap-r9-20261016-G5-04-search`, and snapshot `gbif.rest_api.species_search.v1`. §8.66.5 requires a repository write, an independent read-back of one `CURRENT` `VERIFIED` cell, and a repeat that returns `ALREADY_CURRENT` without a second current cell. §8.66.6 still forbids a new table, a PostgreSQL write, moving the scientific store, and treating a live HTTP response as persistence by itself. §8.70 left this seat unpersisted on purpose. This section records that completion on the dedicated `capver` SQLite file.

The command signature has no `--seat` option. The seat selector is the positional `adr_id`. Omitting it runs every authorized seat. The invocation used was `scientific:capver-accepted G5-04`.

### 8.71.2 State before the write

`capver.sqlite` had four rows, ids 2–5, and no row whose natural key or `capability_record_id` was `G5-04` / `cap-rec-r9-20261016-G5-04-api`. `PRAGMA integrity_check` was `ok`. File SHA-256 before the insert was `412dee7813730c6de5d4511c2e90f089ef83d21bce5f5d2d998d961efba36124`. A read-only SQLite backup was written to `backend/storage/app/capver.sqlite.pre-g504.backup` and was not an overwrite of `capver.sqlite.backup`.

### 8.71.3 Persisted row

The first command exited 0 with `store=PERSISTED` and `store_detail=cap-rec-r9-20261016-G5-04-api`. An independent read-only PDO query then found one natural-key match:

- storage `id`: `6` (SQLite autoincrement; not chosen in advance)
- `capability_record_id`: `cap-rec-r9-20261016-G5-04-api`
- `adr_id`: `G5-04`
- `canonical_identity_id`: null
- `dimension_family` / `dimension_code`: `ACCESS_METHOD` / `api`
- `seat_override_applied`: false
- `capability_state`: `VERIFIED`
- `evidence_freshness_class`: `CURRENT`
- `cell_lifecycle`: `CURRENT`
- `snapshot_version`: `gbif.rest_api.species_search.v1`
- `observed_at`: `2026-10-22T06:33:36+00:00`
- `verified_at`: `2026-10-23T12:20:05+00:00` (execution clock of this write, not a historical PostgreSQL timestamp)
- `created_at` / `updated_at`: `2026-10-23 12:20:06`
- `update_method`: `capver_authorized_evidence_review`
- `limitation_text`: null
- evidence refs, in stored order: the §8.66 authorization line, `IC-ER 2026-10-13 G5-04`, the recorded species-search observation `Live GET /v1/species/search q=Triticum HTTP 200 2026-10-22T06:33:36Z usageKey=315051481`, `docs/architecture/science-source-expansion/2026-10-16-NEXT-GATE-17-ENDPOINT-VERIFICATION.v1.json#G5-04`, and `onb-r9-20261016-G5-04 ACTIVE species-search contract`

Total rows became 5. No other new record id appeared. The four historical rows were byte-identical to the pre-write export.

### 8.71.4 Repeat, tests, and untouched stores

The same command was run again and exited 0 with `store=ALREADY_CURRENT`. A second read-only PDO comparison matched the post-insert rows, including `verified_at` and the four historical rows. `PRAGMA integrity_check` remained `ok`.

Isolated regression file `tests/Unit/Agriculture/Research/Capability/CapVer/CapVerG504IsolationTest.php`: `OK (3 tests, 47 assertions)`, exit 0, `00:17.101`. It used `storage/framework/testing/capver_g504_isolation.sqlite`, not the operational file. It covered one insert, a no-change repeat, rejection of the same `capability_record_id` on another natural key, and rejection of a second `CURRENT` cell for this natural key. When a different current id already occupies the natural key, `execute('G5-04')` returns `ALREADY_CURRENT` and does not replace that id.

The scientific file stayed at length `1393659904`, LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`, and SHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. The application default connection remained `pgsql`. No Docker command was run. No commit and no push.


## 8.72 — Round 2.5 evidence limits — 2026-10-23

Append-only record of measurement limits. This section does not rewrite §8.60 through §8.71. It does not adopt a new architectural decision, activate a seat, authorize CapVer ship, or publish §§8.66–8.71.

### 8.72.1 D-08 archive

On 2026-10-23 the three D-08 directories were counted again: 436, 95, and 12 files. Each directory has its own `MANIFEST.md`. None of those manifests contains a SHA-256 value. Manifest verification stays `INVENTORY_ONLY`. A SHA-256 computed from a file in the archive does not prove that the file still matches its original source. The original transcript was not available, so original-source verification stays `NOT VERIFIED`. The five previously identified exception files are still present and were not rewritten. No archive byte was changed in this round.

لا يمكن حاليًا إثبات المطابقة البايتية للمصدر الأصلي باستخدام الأدلة المتاحة.

### 8.72.2 Integration classification

`EloquentIntegrationClassificationRepository` stores classification records, enforces idempotency, and rejects a second active row. It does not send HTTP requests and it does not verify endpoints. No live verification of the 109 sources was run. The persistence tests from the previous round were not repeated in this round. The state remains partial: structure was tested earlier, and live verification is still pending. This section does not close that gap.

### 8.72.3 Isolated tests in this round

Knowledge persistence, covering the early-reject cases and the storage cases, exited 0: `OK (11 tests, 79 assertions)`. The default connection and the capver connection were sqlite `:memory:`. Source bytes were written through `Storage::fake('local')`. Downloads used `Http::fake()`. The semantic index was a mock.

Mission artifact export now rejects a name that is not one safe file name, including an empty name, `.`, `..`, `/`, `\`, and Windows device names. A blob directory that cannot be created returns `blob_dir` and does not insert a row. A blob path that leaves the blob root is not read. That suite exited 0: `OK (13 tests, 91 assertions)`. Temporary archives were under `storage/framework/testing`, and none remained after the run.

### 8.72.4 Protected files

After those tests, `scientific_record_store.sqlite` was still length 1393659904, LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`, SHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. `capver.sqlite` was still length 32768, LastWriteTimeUtc `2026-10-23T12:20:07.1352738Z`, SHA-256 `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875`. The comparison is a file-byte hash. The databases were not opened.

No staging, commit, or push was performed.

### 8.72.5 Still open

Original D-08 bytes are not verified. Endpoint verification for integration classification was not performed. §§8.66–8.71 remain unapproved local text. Round 3 is not authorized by this section.

## 8.73 — Round 3.5 measurement — 2026-10-24

Append-only. This section does not rewrite §8.60 through §8.72. It does not close Phase 1, activate a seat, authorize CapVer ship, or adopt a new architectural decision. The sentence in §8.72.5 that §§8.66–8.71 were unapproved local text remains the record of 2026-10-23. Those sections, and §8.72, are present in commit `a0dbaa35e47e80039b389aecf323b31c8d83939a`. Publication is not a new approval beyond that commit.

### 8.73.1 Recovery directory

`backend/storage/framework/recovery/` was untracked. `backend/storage/framework/.gitignore` gained one line, `recovery/`. `git check-ignore -v` attributes `backend/storage/framework/recovery/capver-historical-recovery.json` to that line. The same command does not ignore `backend/storage/framework/cache/data/.gitignore`. No tracked path was removed from the index, and the recovery files were not edited or deleted.

### 8.73.2 Archive symlink behavior

`readableBlobPath` refuses a symlink and refuses a resolved path outside the blob root. An internal symlink is not followed. `exportOriginal` already refused a destination name that is an existing symlink. `MissionArtifactArchiveTest` was executed on 2026-10-24 with PHP `8.4.26` and PHPUnit `11.5.56`: `OK (17 tests, 116 assertions)`, exit 0, no skipped tests. PHP `symlink()` returned permission denied. The test created the links with `cmd /c mklink`. The archives used `storage/framework/testing/mission_artifact_archive_*`. `backend/tests/bootstrap.php` forced `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` before Laravel booted.

### 8.73.3 D-08

The original decision D-08 says every Cursor prompt and report for this phase must be archived under `science-source-expansion/` with date and phase labels, and that later decisions supersede earlier ones without deleting them. That text does not require a SHA-256 comparison. The base manifest says its source is the Cursor conversation transcript. That transcript was not available here as an independent byte source. Byte equality with the transcript stays unverified. No archive file was modified in this round. The 2026-10-23 counts, 436, 95, and 12, were not recomputed.

لا يمكن حاليًا إثبات المطابقة البايتية للمصدر الأصلي باستخدام الأدلة المتاحة.

### 8.73.4 Integration classification and CapVer

`EloquentIntegrationClassificationRepository` still does not send HTTP requests. §8.30.5 records `ENDPOINT TESTED 0` for the 109-seat documentary review, and §8.63.6 keeps verified per-seat IC evidence required before runtime binding. No live verification was run, and no new verifier was added.

`CapVerAuthorizedCellCountTest` names seats `G1-02`, `G6-20`, `G1-01`, `G1-17`, and `G5-04` and opens `storage/framework/testing/capver_authorized_cell_count_*.sqlite`. It was not re-executed in this round. Round 3 recorded that test as passing inside a process that also skipped the archive symlink test. This ADR has no section that defines a layer named Layer H. The five-cell test is not treated here as closure of an undefined layer.

### 8.73.5 Protected files

After the archive test, file hashes were measured again without opening either database. `scientific_record_store.sqlite` remained length `1393659904`, LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`, SHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. `capver.sqlite` remained length `32768`, LastWriteTimeUtc `2026-10-23T12:20:07.1352738Z`, SHA-256 `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875`. Both matched the measurement taken before the test.

## 8.74 — D-08 transcript comparison — 2026-10-24

Append-only. This section does not rewrite §8.60 through §8.73. It is not the report required by P1-EXIT-11, and it does not record Phase 1 exit. It does not authorize endpoint probes, CapVer ship, or a definition of a layer named Layer H.

### 8.74.1 Independent source

The archives name one Cursor conversation transcript. On 2026-10-24 that file was `agent-transcripts/b258b225-a306-467c-9ffa-e460a3b095af.jsonl`, 18,413 lines, length 62,157,108 bytes. Other chat transcripts in the same projects directory were not used: the existing manifests cite line numbers that belong to this file. The comparison read those lines and did not modify the transcript or the earlier archives.

### 8.74.2 Records archived on or before 2026-10-14

540 record files were compared, excluding the three `MANIFEST.md` files. 531 are byte-identical to the owner query or executor text at the cited line. Three reports remain redacted: their non-redacted segments occur in order in the cited line, with a non-empty gap for each `[REDACTED]`. They are `2026-10-09-2220-do-not-stage-or-commit-anything-yet-provide-the-complete-pro-report.md` (line 13840), `2026-10-09-2239-continue-the-ic-integrity-review-the-temporary-file-wsa-ic-c-report.md` (line 13845), and `2026-10-09-2305-continue-report.md` (line 13853). Six cited lines no longer hold the archived text. The same text is byte-identical at a current line: the 14:05 report cites 14759 and is at 14757; the 14:57 prompt cites 14761 and is at 14758 and 14759; the supplement-01 14:57 report cites 14855 and is at 14854; the supplement-01 16:29 prompt cites 14867 and is at 14860 and 14861; the supplement-02 16:29 report cites 14914 and is at 14907; the supplement-02 17:04 prompt cites 14916 and is at 14908 and 14909. Those six files were not edited.

### 8.74.3 Supplement 03

D-08 requires every prompt and report of this phase to be archived. After line 14909, the same transcript held owner prompts that were not in the 2026-10-14 archives. A new directory, `2026-10-24-D-08-archive-supplement-03/`, records 128 unique prompts and 117 reports. Identical consecutive prompts are stored once. A report is the last executor message of at least 300 characters after the prompt and before the next distinct owner prompt or system notification. Eleven prompts had no such report. All 245 new record files are byte-identical to the cited line. A confirming read, when the transcript had grown to 18,414 lines, found the same 245 files still byte-identical; the added line is after those records and is not archived. The credential scan of that directory found no private-key block, no `AKIA` key, no GitHub personal-access token, and no Bearer token. The one assignment-shaped string is the already published test sentinel `BHL_API_KEY=should-never-archive`. The executor report for the prompt that requested this comparison did not exist when the files were written.

### 8.74.4 What this does not close

Layer F stays open. §8.30.5 records `ENDPOINT TESTED` 0 because GB-IC-01 prohibits requests to machine endpoints. §8.63.6 still requires verified per-seat IC evidence before runtime binding, and the IC inventory remains OPEN. No live probe was added, and a mocked HTTP client is not that evidence. Layer H stays undefined: this ADR still has no adopted section that defines a layer by that name, and no other architecture document or PHP test defines one. The five-cell CapVer test was not re-run; its code and isolated database path were not changed. §8.64.7 still prevents Phase 1 exit until every checklist item is PASS and P1-EXIT-11 is recorded. Neither condition is met here.

Before this section was added, and before supplement 03 was written, both protected databases were hashed without being opened. `scientific_record_store.sqlite` was length `1393659904`, LastWriteTimeUtc `2026-10-22T09:53:58.9832998Z`, SHA-256 `8827901A30DC8F93888F82E10B096190DB440766733D8B860269D9ABEEE11211`. `capver.sqlite` was length `32768`, LastWriteTimeUtc `2026-10-23T12:20:07.1352738Z`, SHA-256 `66CA7C1497BA84E3FF9ECCBF01D6A5E2BC193571469EEAED1A5D10B19C00B875`. Both matched the values in §8.73.5.
