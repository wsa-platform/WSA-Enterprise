# CANONICAL CONTRACT SPECIFICATION v1

**Status:** Authoritative for Phase 2 runtime  
**Aligned with:** R1–R7, Phase-2 forensic findings P2-C01…C18  
**Date:** 2026-09-21

This document describes **implemented** contracts. Do not document behavior the code does not provide.

---

## 1. Tenant contract (R1 / MODEL B)

| Aspect | Contract |
|--------|----------|
| Public tenant source | Server config `wsa.public_organization_slug` |
| Client org fields | Optional compatibility; ignored for selection |
| Bind | `PublicTenantResolver::bindPublicTenant()` → `TenantContext` |
| Persist ownership | Bound public `organization_id` only |
| Missing/inactive | HTTP 503, `error.code=public_organization_unavailable` |

### Error dual-emit (P2-C03 transitional)

| Surface | Legacy key | Shared |
|---------|------------|--------|
| Home research | `status` | `error.{code,http_status,message,details}` |
| Crop profile | `load_state` (+ `status`) | same `error` object |

---

## 2. Language contract (R2 / R3 / P2-C04 / C08 / C09)

| Concept | Source |
|---------|--------|
| `ui_locale` | Client chrome / `Accept-Language` transport |
| `question_language` | QUS detection on question text |
| `answer_language` | Equals `question_language` (supported: ar\|en\|tr\|fr; else en) |
| Persisted `library_items.locale` | Answer language without tr/fr→en collapse |
| Source file language | Original; never rewritten to answer language |

Flutter (`mobile`) and admin-mobile send dynamic `Accept-Language` for UI locale only.

---

## 3. Home research API

`POST /api/v1/public/research-agent/query`

**Request:** `query` required; `organization` / `organization_id` optional non-authoritative.

**Success:** synthesis merge including `answer`, `citations` (original source URLs), `confidence`, `limitations`, disposition metadata when Home.

**Errors:** disabled → 503 `status=disabled`; tenant → 503 shared error envelope.

---

## 4. Crop farming-needs API

`GET /api/v1/public/field-crops/farming-needs-profile`

**Request:** `selected_crop_id` + `selected_crop_name` required; org fields optional non-authoritative.

**Success:** legacy profile envelope + nested scientific research fields; predefined questions only (not Home free-text).

---

## 5. Shared scientific foundation

Home and Crop share: retrieval, evidence normalization/evaluation, verification, confidence, limitations, source handling, save eligibility, original source-file preservation.

QUS entry and response envelopes differ by design (R4).

---

## 6. Knowledge Query Plan

`KnowledgeQueryPlan::toArray()` **must** include `context_input` (P2-C07). Crop profile plans carry selected crop taxonomy ids in `contextInput` (not FAOSTAT codes).

---

## 7. Evidence axes (R6)

```
directness: direct | supporting | background | related | irrelevant | geographic_mismatch
            (+ deprecated alias supported → supporting)

claim_relation: supported | partially_supported | conflicting | insufficient_evidence | not_validated

disposition: provider_failed | no_results_retrieved | retrieved_but_rejected |
             validated_not_composer_eligible | composer_used | insufficient_for_synthesis | …
```

Separate fields; never collapse.

---

## 8. Verification / save eligibility (R5)

Authoritative gate for auto-save: validation `evidenceSufficient` + eligible synthesis status + verified claims/citations, enforced in `ScientificKnowledgePersistenceService`.

Composer status informs disposition; it is not sole eligibility authority.

When eligible: auto-save Library item under **Crop → Scientific Research** folder when crop context exists; download original source file unchanged.

Source links shown to users remain **original source URLs**, not Library URLs.

---

## 9. Feedback (R7)

`POST /api/v1/public/research-agent/feedback`

| Polarity | Persistence |
|----------|-------------|
| positive | Insert into `research_feedback_dataset` |
| negative / other | Discarded; not stored |

`policy.mutates_scientific_behavior = false` always.

---

## 10. FAOSTAT

| Item | Contract |
|------|----------|
| Runtime | Developer Portal `https://faostatservices.fao.org/api/v1` |
| Client | `FaoStatDeveloperPortalClient` |
| Dimensions | Area / Element / Item / Year |
| FENIX | Historical only; not active runtime |
| Codes | FAOSTAT codes ≠ crop taxonomy IDs |

---

## 11. Identifier boundaries (P2-C13 / C15)

| ID system | Type | Must not join as |
|-----------|------|------------------|
| Organization | integer PK / slug | crop taxonomy |
| Crop taxonomy | string id | FAOSTAT item |
| FAOSTAT codes | portal dimension codes | internal crop ids |

---

## 12. Intentionally retained differences

| Item | Rationale |
|------|-----------|
| Home vs Crop envelopes | R4 domain UX |
| Home-only multi-entity QB coverage | Intentional Home search fork (P2-C14) |
| Dual provider registries | Phase-3 consolidation candidate (P2-C12) |
| Implicit units in prose | No canonical unit DTO yet (P2-C17) |
