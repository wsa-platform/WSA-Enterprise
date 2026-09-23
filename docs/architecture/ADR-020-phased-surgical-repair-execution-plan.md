# ADR-020 — Phased Surgical Repair Execution Plan

- **Status:** HISTORICAL / PARTIALLY SUPERSEDED BY ADR-021
- **Date:** 2026-09-17
- **Scope:** WSA-Enterprise Home Page scientific research pipeline repair program
- **Decision type:** Execution architecture / change isolation / release discipline
- **Related ADR:** ADR-019 — Protect the Crop Page Pipeline

> This remains a historical Home-only 8-phase surgical repair plan. Protected-WIP and lockfile safety principles below remain useful where still applicable.
> ADR-021 is the current 10-phase system-wide remediation model (`PHASE 9 — LEGACY + WIP + ENGINEERING CLEANUP`).
> ADR-020 “Phase 8” meant Home validation/sign-off — not remediation 8A/8B Security + Library.
> Do not rewrite the historical execution plan. Do not delete this ADR.

## 1. Decision

The approved repair program for the Home Page scientific research pipeline SHALL be executed as a sequence of **8 clearly separated major phases**.

Each implementation phase SHALL be completed, tested, audited, human-reviewed, committed, pushed, remotely verified, and then frozen before the next implementation phase begins.

Phase 0 is a baseline/protection phase and does not require a new code commit. Phases 1–7 are implementation phases and each MUST end with its own dedicated commit and push. Phase 8 is final system validation and sign-off rather than a new repair batch.

The governing repair style is **Surgical General Repair**: repair the general root cause of the observed failure class at the smallest responsible boundary, without question-specific workarounds, broad refactors, or unnecessary shared-behavior changes.

## 2. Non-Negotiable Architectural Constraints

### 2.1 Crop Page protection

The Crop Page / Field Crop pipeline remains fully protected under ADR-019. No phase may modify, refactor, optimize, reorganize, or behaviorally change the protected Crop Page pipeline.

If a shared component is implicated and the proposed change could affect Crop Page behavior, the change MUST be isolated to the Home Page path or otherwise proven behavior-preserving before acceptance.

### 2.2 WIP protection

Existing WIP remains preserved. No phase may overwrite, revert, reset, stash, clean, or otherwise destroy unrelated WIP.

Protected WIP and existing scientific work include, as applicable: QUS, Q1–Q5 work, entity resolution, species identity, compositional agricultural semantics, scientific directness, evidence sufficiency, Stage 5 scientific logic, EVL, ClaimEvidenceMatcher, OpenAlex, Crossref, Semantic Scholar, Open-Meteo, Free Search MCP, provider selection/search architecture, post-synthesis optimization, language contract, FieldCropTaxonomyCatalog, AgriculturalEntityCatalog, AgriculturalKnowledgeQuery, QueryUnderstandingService, ResearchPlanner, ScientificEvidenceDirectnessAssessor, ScientificEvidenceRelevanceGate, ScientificResultRanker, ScientificSearchQueryBuilder, SearchFlow, GeoScope, ProviderActivation, generated outputs/logs/IDE artifacts, composer.lock, .env.example, e2e-tmp historical/temp artifacts, and frontend/mobile artifacts.

### 2.3 Git safety

The repair program MUST NOT use destructive Git operations unless explicitly authorized.

The following are prohibited by default:

- `git reset`
- `git restore`
- `git stash`
- `git clean`
- `git checkout`
- `git rebase`
- `git cherry-pick`

Whole-tree staging is also prohibited:

- `git add .`
- `git add -A`

Only explicitly approved files/hunks may be staged.

No commit or push is allowed until the phase gate has passed.

## 3. Phase Model

Every implementation phase follows this lifecycle:

`Inspect → Surgical Repair → Tests → Regression → Crop/WIP Audit → Human Review → Commit → Push → Verify Remote HEAD → Freeze → Next Phase`

If scope expansion is discovered, execution MUST stop. The phase may not silently absorb unrelated architectural changes.

## 4. Phase 0 — Baseline + Protection

**Purpose:** Establish the exact starting point and protect existing work.

Activities:

- Verify branch and HEAD.
- Verify working-tree/WIP state.
- Record protected files and paths.
- Record Crop Page protected boundary.
- Establish test baseline.
- Establish latency baseline.
- Classify intended changes as Home-only, Shared-but-behavior-preserving, or Cross-Pipeline.
- Confirm the approved scope of each subsequent phase.

**Code changes:** None.

Phase 0 establishes the baseline rather than creating a new implementation commit.

## 5. Phase 1 — P4a: Crossref Observability

**Purpose:** Measure Crossref contribution to end-to-end latency before making performance changes.

The phase is limited to surgical instrumentation of the Crossref scientific source adapter and related approved test/instrumentation surfaces.

Measure, where applicable:

- request duration
- HTTP status
- retry count
- result count
- timeout/error outcome

This phase MUST NOT change timeout policy, provider selection, provider concurrency, ranking, Composer behavior, FAOSTAT behavior, or QUS behavior.

**Commit requirement:** Dedicated Phase 1 commit.

## 6. Phase 2 — P2: General Statistical Measure Alignment

**Purpose:** Repair the general semantic alignment mechanism that can reject valid statistical observations when entity, location, and year match but the requested measurement family does not align correctly.

Primary focus is the smallest responsible statistical alignment boundary, including `ScientificStatisticalClaimAligner` and its measure-family/property matching logic, subject to Phase 2 forensic confirmation.

The semantic model MUST keep distinct dimensions separate:

- Entity
- Location
- Time
- Measure
- Unit

The repair MUST be general and provider-independent where the responsible abstraction is provider-independent. It MUST NOT hard-code a specific crop, country, year, value, or question.

Regression MUST cover positive and negative measure families and ensure temporal metadata such as year cannot be misclassified as a measurement family.

**Commit requirement:** Dedicated Phase 2 commit.

## 7. Phase 3 — P4b: FAOSTAT Duplicate Execution Control

**Purpose:** Avoid unnecessary FAOSTAT variant execution when a valid and complete result already satisfies the current requirement.

The change MUST remain provider-aware and narrowly scoped.

It MUST NOT globally change `ADEQUATE_RESULT_COUNT`, globally alter provider policy, or introduce unrelated concurrency changes.

Regression MUST cover complete, incomplete, empty, failed, and multi-variant cases.

**Commit requirement:** Dedicated Phase 3 commit.

## 8. Phase 4 — P3: Evidence State + Composer

**Purpose:** Establish and enforce an explicit evidence-state contract so that supporting evidence cannot be presented as if it were direct evidence and irrelevant supporting material is not rendered as an answer substitute.

The mutually exclusive final evidence states are:

### DIRECT_SUFFICIENT

At least one direct evidence item exists, all required semantic requirements are satisfied, the modality is appropriate, and claim coverage is sufficient.

### DIRECT_PARTIAL

At least one direct evidence item exists and directly answers part of the request, but one or more required requirements remain missing or failed.

### SUPPORTING_ONLY

No direct evidence exists, but one or more valid supporting items exist with genuine semantic relevance.

### INSUFFICIENT

No sufficient direct evidence exists, evidence is present, but critical requirements remain unsatisfied.

### NO_VALID_EVIDENCE

No evidence survives normalization, alignment, relevance, and validation.

The final result MUST contain exactly one final evidence state plus relevant metadata.

Composer behavior MUST be driven by evidence state, evidence role, validated evidence, claim coverage, and missing requirements rather than raw source counts.

The phase MUST preserve useful supporting evidence where appropriate; it must specifically prevent unrelated supporting evidence from being promoted into an apparent direct answer.

**Commit requirement:** Dedicated Phase 4 commit.

## 9. Phase 5 — P1: Entity Contract + Specificity Preservation

**Purpose:** Preserve confirmed agricultural entity specificity across the semantic pipeline without inventing specificity and without forcing unnecessary database/API changes.

The phase begins with a design/impact audit across:

- Subject
- AgriculturalKnowledgeQuery
- ResearchPlanner
- QueryBuilder
- Ranker
- Composer
- API serialization
- Persistence, if applicable

The implementation MUST choose the smallest backward-compatible representation that preserves specificity when it is actually confirmed.

Generic entities remain valid. Specificity MUST NOT be inferred merely because a term resembles a breed or variety.

Regression MUST cover generic family/entity, confirmed specific entity, unknown specificity, breed/variety cases, and backward-compatible fallbacks.

**Commit requirement:** Dedicated Phase 5 commit.

## 10. Phase 6 — P4c: Legacy Short-Circuit

**Purpose:** Avoid unnecessary Legacy Knowledge Engine execution when the modern pipeline has already produced a validated `DIRECT_SUFFICIENT` result.

The Legacy system MUST NOT be deleted, globally disabled, or rewritten as part of this phase.

The short-circuit applies only where the evidence/answer policy establishes that Legacy is no longer required.

Regression MUST prove that partial, supporting-only, insufficient, and no-evidence paths retain their existing policy.

**Commit requirement:** Dedicated Phase 6 commit.

## 11. Phase 7 — P4d: Provider Concurrency

**Purpose:** Reduce latency by executing genuinely independent scientific providers concurrently without changing semantic behavior.

This phase is intentionally last among the repair phases because concurrency must operate on a stable semantic and evidence architecture.

Concurrency MUST preserve:

- provider eligibility
- provider roles
- evidence states
- deduplication semantics
- ranking semantics
- timeout/retry behavior
- deterministic or contractually valid result behavior
- partial-provider failure handling

Regression MUST cover ordering, deduplication, timeouts, rate limits, provider failure, retry behavior, and determinism/contractual result stability.

**Commit requirement:** Dedicated Phase 7 commit.

## 12. Phase 8 — Final Full Regression + Final Sign-off

Phase 8 is a validation and acceptance phase, not a new repair batch.

It MUST validate the integrated system across:

- backend regression
- QUS
- Planner
- statistical alignment
- scholarly retrieval
- evidence state
- Composer
- provider orchestration
- API behavior
- Crop Page regression
- WIP integrity
- full latency comparison against the baseline

No new unrelated repair may be introduced during Phase 8 without an explicit new phase/decision.

## 13. Phase Gate and Human Sign-off

Every implementation phase requires:

1. Automated tests.
2. Targeted regression.
3. Crop Page protection check.
4. WIP integrity check.
5. Surgical diff audit.
6. Latency comparison where relevant.
7. Technical report documenting root cause, changes, test evidence, and scope.
8. Human review and explicit GO/NO-GO.
9. Only after GO: commit and push.
10. Verify the remote branch points to the new commit.
11. Freeze the completed phase.

A phase with unresolved semantic uncertainty, scope expansion, Crop Page impact, WIP contamination, or unexplained regression is NO-GO.

## 14. Change Impact Classification

Every proposed modification is classified as:

- **Home-only:** allowed when it does not affect Crop Page behavior.
- **Shared but behavior-preserving:** requires explicit proof through targeted regression.
- **Shared and potentially behavior-changing / Cross-Pipeline:** prohibited for this repair program unless the change is isolated away from Crop Page and separately approved.

## 15. Provider Selection Principle

Provider selection SHALL NOT be hard-coded solely from a question label such as `Statistical → FAOSTAT Required`.

The intended policy chain is:

`Question Contract → Evidence Requirements → Provider Capabilities → Eligibility → Provider Role → Execution Policy`

Provider capabilities include, as applicable:

- FAOSTAT: statistical observations
- OpenAlex: scholarly discovery/evidence
- Crossref: scholarly metadata/discovery
- Semantic Scholar: scholarly discovery
- Official/Web sources: official or web evidence

Provider capability does not by itself define DIRECT or SUPPORTING. Evidence role is determined after normalization, alignment, relevance, and validation.

## 16. Documentation Policy

Architecture documentation SHALL be updated only when a phase changes an architectural contract, evidence model, entity model, provider policy, or other durable architectural decision.

This ADR records the phased execution discipline. Additional ADRs may be created only when justified by an accepted architectural change.

## 17. Rollback Policy

Rollback is phase-local and non-destructive.

Each completed phase is independently identifiable by its commit. If a later phase causes a problem, investigation and recovery MUST preserve earlier accepted work and unrelated WIP.

No repository-wide destructive rollback is permitted.

## 18. Approved Sequence

`Phase 0 Baseline/Protection`

→ `Phase 1 P4a Crossref Observability`

→ `Phase 2 P2 Statistical Measure Alignment`

→ `Phase 3 P4b FAOSTAT Duplicate Control`

→ `Phase 4 P3 Evidence State + Composer`

→ `Phase 5 P1 Entity Specificity`

→ `Phase 6 P4c Legacy Short-Circuit`

→ `Phase 7 P4d Provider Concurrency`

→ `Phase 8 Final Full Regression + Sign-off`

No phase may be skipped, silently merged with another phase, or expanded beyond its approved scope without explicit review.

## 19. Acceptance Principle

The repair program is accepted only when the Home Page scientific research pipeline is improved through general root-cause repairs while:

- the Crop Page pipeline remains unchanged,
- existing WIP remains intact,
- each implementation phase has an auditable commit and push boundary,
- regressions are understood and controlled,
- latency changes are measured rather than assumed,
- and final integrated validation passes.
