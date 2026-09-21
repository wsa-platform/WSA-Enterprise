# WSA-ENTERPRISE — Architectural Decisions R1–R7

**Status:** Approved and implemented (Phase 2)  
**Authority:** Phase-2 Master Implementation Prompt + ADR-021  
**HEAD baseline:** `23c57b87c695366ad2a7a2ea0e4c981629a99cdd`  
**Date:** 2026-09-21

These decisions are normative for Phase 2. Documentation must match runtime.

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
