# WSA-ENTERPRISE — Architectural Decisions R1–R8

**Status:** Approved and implemented (Phase 2)  
**Authority:** Phase-2 Master Implementation Prompt + ADR-021  
**HEAD baseline:** `23c57b87c695366ad2a7a2ea0e4c981629a99cdd`  
**Date:** 2026-09-21

These decisions are normative for the project. Documentation must match runtime where implemented; R8 is an approved architectural decision with implementation pending.

---

## R1 — Public Research / Organization (MODEL B)

- Public research does **not** require the user to select an Organization.
- Server resolves the public tenant via `PublicTenantResolver` + `config('wsa.public_organization_slug')` (`WSA_PUBLIC_ORG_SLUG`).
- Client-supplied `organization` / `organization_id` are accepted for compatibility only and are **never** authoritative for persistence.
- Failure when public tenant is missing/inactive: HTTP 503 with `error.code = public_organization_unavailable` (Home also emits legacy `status`; Crop also emits legacy `load_state`).
- Persistence rejects writes when bound public tenant mismatches attempted `organization_id`.

**Primary files:** `PublicTenantResolver.php`, `TenantContext.php`, `AgriculturalResearchAgentController.php`, `PublicFieldCropCultivationController.php`, `ScientificKnowledgePersistenceService.php`, `config/wsa.php`.

---

## R2 — Answer Language

- `answer_language = question_language` (from QUS detection of the question text).
- Platform / UI locale (`Accept-Language`, `ui_locale`) must **not** override answer language.
- Concepts remain separate: `ui_locale`, `question_language`, `answer_language`, source/document language, persisted locale.

**Primary files:** `QueryUnderstandingService.php` (`resolveAnswerLanguageFromQuestion`), `AnswerComposer.php` (reads `constraints.answer_language`).

---

## R3 — Supported Languages + Original Source Preservation

- Platform UI languages: `ar | en | fr | tr`.
- When a verified source is eligible for automatic Library storage, the **original source file bytes** are downloaded and stored unchanged (`file_disk` / `file_path`).
- No translate / rewrite / summarize / language convert of the stored source file.
- Answer may be in question language; stored file remains original.

**Primary files:** `ScientificKnowledgePersistenceService::preserveOriginalSourceFile`.

---

## R4 — Home vs Crop Research

- **Home:** free-form research question (`POST /api/v1/public/research-agent/query`).
- **Crop:** predefined crop-specific question (`GET /api/v1/public/field-crops/farming-needs-profile`); not free-form.
- Shared scientific foundation: search, evidence, validation, confidence, limitations, sources, save eligibility, original source preservation.
- Domain envelopes may differ; adapters must not drop scientific fields.

---

## R5 — Evidence Verification and Automatic Save

- Composer is **not** the sole save-eligibility authority.
- Flow: Question → QUS → KQP → Search → Retrieval → Evidence → Verification → Eligibility → Composition → Response → Auto Library Save when eligible.
- Auto-save requires `EvidenceValidationExecutionReport.evidenceSufficient` plus eligible synthesis status.
- Insufficiently verified answers may still be shown with limitations; they are **not** auto-saved as verified research.
- No user-controlled “save as verified” bypass.

**Primary files:** `ScientificKnowledgePersistenceService`, `EvidenceVerificationLayer`, `HomeEvidenceLifecycleDisposition` (Home disposition labels only).

---

## R6 — Evidence Semantics (Separate Axes)

Never collapse these dimensions:

| Axis | Field | Owner (examples) |
|------|-------|------------------|
| Directness | `directness` | `ScientificEvidenceDirectnessAssessor` |
| Claim relation | `claim_relation` | `ClaimEvidenceMatcher` / claim relationship |
| Disposition | `evidence_lifecycle_disposition` | `HomeEvidenceLifecycleDisposition` (Home) |

- Legacy directness token `supported` is a **deprecated alias** of `supporting` on the directness axis — not claim support.
- Do not overload generic `supported` without field context.

---

## R7 — AI Feedback / Continuous Learning

- After interaction, the system may ask if the answer was useful.
- **Only positive feedback** is persisted (`research_feedback_dataset`).
- Negative feedback is discarded (HTTP 200, `persisted=false`).
- Feedback is **not** scientific truth and **must not** directly mutate production scientific behavior.
- Learning loop: Positive Feedback → Dataset → Structured Analysis → Hypothesis → Testing → Deliberate Adoption.

**Endpoint:** `POST /api/v1/public/research-agent/feedback`

---

## Related Phase-2 contracts

See `docs/architecture/CANONICAL-CONTRACT-SPECIFICATION-v1.md`.

Phase 8 security / Library Page closeout (no change to R1–R7 semantics): `docs/architecture/PHASE-8-CLOSEOUT.md`.


---

## R8 — R33-OD-1 Independent Multi-Answer Architecture

**Status:** Approved architectural decision; implementation pending  
**Date:** 2026-09-29  
**Scope:** Home Web + Crop Web

R33-OD-1 introduces a **new independent Multi-Answer scientific-answer model**.

### R8.1 — Independent Producer

- Multi-Answers are produced by a **dedicated independent Multi-Answer Producer**.
- The Producer is architecturally separate from Composer Units A/B/C.
- The existing `ScientificAnswerCandidatePresenter` / `candidates[]` path is **not** the Multi-Answer producer.
- Claims must not be silently reinterpreted as independent scientific answers.
- Composer Units A/B/C remain CLOSED and are not reopened by R33-OD-1.

### R8.2 — Primary Answer Relationship

- `primary_answer` remains singular.
- Existing final-answer eligibility and the **0.50 threshold** remain unchanged.
- R33-OD-1 adds `multi_answers[]`; it does not replace `primary_answer`.

Conceptual presentation contract:

`primary_answer` + `multi_answers[]` + `candidates[]` + `results[]` + `sources`

### R8.3 — Existing Candidates Contract

- Existing `candidates[]` remains a separate claim-alternative presentation feature.
- `candidates[]` must not be migrated, renamed, or silently aliased to `multi_answers[]`.
- The semantics of the existing candidates contract remain unchanged.

### R8.4 — Multi-Answer Identity

Each independent Multi-Answer has its own deterministic `answer_id`.

- `answer_id` is semantically distinct from `claim_id`, `result_id`, `evidence_id`, and `source_id`.
- The detailed deterministic identity inputs and normalization rules are part of the later R33-OD-1 Identity Contract / implementation specification and must not be invented implicitly.

### R8.5 — Multi-Answer Payload

The authoritative Multi-Answer object is defined conceptually as:

- `answer_id`
- `answer`
- `result_id`
- `evidence_ids`
- `source_ids`
- `position`
- `provenance`

The payload is expected to be carried through the existing `user_presentation` boundary as `multi_answers[]`, subject to the later implementation-specification audit.

### R8.6 — Provenance

- Backend maintains the complete provenance relationship needed to connect: **Answer → Evidence → Sources → Result**.
- User presentation exposes relevant source/evidence/result navigation rather than internal provenance implementation details.
- Internal provenance structures must not be promoted to public API merely by reuse.
- Confidence is not user-visible unless separately authorized.

### R8.7 — Ordering

- The Multi-Answer Producer is authoritative for `position`.
- Presentation layers must preserve the Producer-defined order.
- The frontend must not invent a scientific ranking rule from confidence, claim order, result order, or source order.

### R8.8 — Carousel

- Carousel is a **presentation mechanism only**.
- It navigates among backend-provided Multi-Answers.
- It does not determine scientific correctness.
- It is not a voting, ranking, or truth-selection mechanism.
- Detailed visual behavior is an implementation/UI decision and is not part of this architectural contract.

### R8.9 — Scope and Closed Boundaries

- R33-OD-1 applies to **Home Web + Crop Web**.
- Flutter is outside R33-OD-1 scope.
- Stage-4 Results List and `results[]` remain CLOSED and unchanged.
- Viewer/Results contracts must not be redesigned by R33-OD-1.
- R33-OD-2, R33-OD-3, and R33-OD-4 remain separate decisions.
- Protected WIP remains outside this decision.

### R8.10 — Implementation Gate

This decision authorizes the architectural direction only.

Before implementation, a separate read-only implementation-specification audit must define and verify:

- the exact Producer boundary and inputs/outputs;
- deterministic `answer_id` inputs and normalization;
- concrete provenance schema;
- `position` validation/invariants;
- exact backend/frontend insertion points;
- required regression and R33-OD-1 contract tests.

No code change is authorized by this document alone.
