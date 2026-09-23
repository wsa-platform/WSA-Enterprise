# PHASE 8 — CLOSEOUT

**Document type:** Documentation-only closeout / index  
**Date:** 2026-09-23  
**Remediation program:** ADR-021 Phase 8 — Security + Tenant Isolation  
**Does not authorize:** application, test, Marketplace, FAOSTAT runtime, or protected-WIP changes

---

## Status

**CLOSED**

`PHASE_8_CLOSURE_GATE = PASS`

This file is the post-Phase-8 documentation index. It does **not** replace:

- `PHASE-8A-1-SECURITY-CONTRACT-ALIGNMENT.md` (authoritative 8A-1 detail)
- `WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md` (R1–R7)
- Phase 1 forensic snapshots (`CURRENT-SYSTEM-STATE.md`, `SYSTEM-WIDE-FORENSIC-AUDIT.md`, `SYSTEM-ARCHITECTURE-MAP.md`)

Phase 8A-2 and Phase 8B had **no** prior dedicated architecture documents. Those closures are summarized here for the first time.

**Official Phase 9 structure (do not invent “Phase 9-1”):**

```
PHASE 9 — LEGACY + WIP + ENGINEERING CLEANUP
  └── Wave 0 — Temporary Artifact Cleanup
  └── Wave 1 — Documentation Reconciliation
```

---

## Scope

| Sub-phase | Role |
|-----------|------|
| **8A-1** | Security contract alignment (MODEL B writes, U8.3 public expensive-compute protection) |
| **8A-2** | Durable security audit events for public tenant / persist / feedback |
| **8B** | Scientific research Library persistence + authenticated Library Page read contract |

**Frozen (not closed as “fixed”):**

| ID | Topic | Status |
|----|-------|--------|
| **P8-F1** | Public published browse (`/public/library`, crop-files, training) still resolves organization from **client** input | **FROZEN** — `BLOCKED_BY_HUMAN_DECISION` |
| **P8-F2** | Plant AI Diagnosis org resolve (client / fallback first-org) | **FROZEN** — `BLOCKED_BY_HUMAN_DECISION` |

Do not treat P8-F1 / P8-F2 as resolved. They remain explicit Phase 8 residual boundaries.

**Out of scope for Phase 8:** Marketplace. Marketplace was not modified and remains outside this contract.

---

## Authoritative Commits

| SHA | Subject | Role |
|-----|---------|------|
| `53b179754e9372c8a8ed53f35714c99722a4192a` | `feat(phase8): complete phase 8A-1 security alignment` | 8A-1 documentation alignment + U8.3 public limiters |
| `337c417a70b2842f2c44c936ef9fdbb45dc61c39` | `feat(phase8): complete phase 8A-2 security audit events` | 8A-2 `AuditService` events (persist / mismatch / feedback / bind failures) |
| `1a3f9067a2dab3a80a8b204fa18f2630f24afe17` | `feat(phase8): align library with scientific research contract` | 8B scientific-research persistence + public exclusion |
| `69c40ab7703bcc1cefc5fab1bcd4d1514762f9d4` | `fix(phase8): align library page with authenticated access contract` | 8B final Library Page authentication-only read |

Phase 8 closed at HEAD `69c40ab7703bcc1cefc5fab1bcd4d1514762f9d4`.

---

## 8A-1 — Security Alignment

Authoritative detail: `docs/architecture/PHASE-8A-1-SECURITY-CONTRACT-ALIGNMENT.md`.

Summary (do not treat this section as a rewrite of that file):

- **Public Tenant MODEL B / R1:** public research and crop **writes** bind the server-configured public organization via `PublicTenantResolver`. Client `organization` / `organization_id` are compatibility-only and are never authoritative for writes.
- Missing / empty / inactive configured public org fails closed (`public_organization_unavailable`, HTTP 503).
- Persistence refuses public-bound tenant mismatch.
- **U8.3:** all `/api/v1/public/*` traffic is under `throttle:public-global` (default 60/min). Specialized `public-expensive-compute` and `public-browse` buckets are **not additive**.
- Authenticated `/api/v1/*` and `/api/v1/health/*` stay outside the public limiter group.
- **P8-F1 / P8-F2** were explicitly out of 8A-1 scope and remain frozen.

---

## 8A-2 — Security Audit Events

Phase 8A-2 had no prior dedicated architecture document. Implementation commit: `337c417a70b2842f2c44c936ef9fdbb45dc61c39`.

That commit touched only:

- `ScientificKnowledgePersistenceService`
- `PositiveResearchFeedbackService`
- `PublicTenantResolver`
- `Phase8A2PublicSecurityAuditTest.php`

Summary of the implemented contract:

| Topic | State |
|-------|--------|
| Durable store | Existing `AuditService` / `audit_logs` (not a new audit subsystem) |
| Persistence accepted | `security.public_tenant_persistence_accepted` |
| Persistence rejected / mismatch | `security.public_tenant_persistence_rejected` when public-bound org mismatches |
| Feedback persistence | Positive feedback persist records `security.public_tenant_persistence_accepted` |
| Public tenant unavailable | `security.public_tenant_unavailable` (fail-closed bind) |
| Client org override ignored | `security.public_tenant_client_override_ignored` (compat field present, not authoritative) |
| Successful tenant bind | **No durable audit event** (Phase 8A-2 H2) |
| `LogApiRequests` | **Untouched** by 8A-2 |

8A-2 does not change MODEL B write binding, U8.3 limiters, P8-F1, or P8-F2.

---

## 8B — Library Contract

Phase 8B had no prior dedicated architecture document. Implementation commits: `1a3f9067a2dab3a80a8b204fa18f2630f24afe17` then `69c40ab7703bcc1cefc5fab1bcd4d1514762f9d4`.

This file is the **canonical documentation pointer** for the final Library Page contract.

### Surfaces (do not merge)

| Surface | Access rule |
|---------|-------------|
| **Library Page** (authenticated browse / search / Library file preview used by the Page) | Authentication only. Complete Library read. |
| **Authenticated Library writes** | Remain organization- and permission-controlled. |
| **Public Library APIs** | Separate. Not the Library Page. Verified scientific research is excluded from anonymous public Library item browse. |
| **Public crop-file APIs** | Separate. Verified scientific research / `scientific-research` section excluded. |
| **Raw storage** | Not made globally accessible by Phase 8. File open on the Library Page still goes through authenticated Library file endpoints. |
| **Marketplace** | Untouched. Outside this contract. |

### Scientific research persistence

- **R5** remains the authoritative auto-save eligibility gate (`evidenceSufficient` + eligible synthesis). Insufficiently verified answers are not auto-saved as verified research.
- When eligible, verified scientific research **can be automatically persisted** as `verified_research_knowledge`.
- Folder hierarchy: `[Research Topic] → Scientific Research → research items`. Crop topics use the `Crop → Scientific Research` label; other topics use `Topic → Scientific Research`.
- **Duplicate identity** for this path is **organization + slug**. A matching existing item is left unchanged. Do not invent additional duplicate guarantees.
- `owner_user_id` is **null** on the verified scientific research persistence path. Organization is a tenant/storage attribute, **not** the publisher.
- Original source file bytes remain unchanged when preserved (R3).

### Library Page READ (final contract)

Authenticated users may browse the complete Library Page.

Authentication is the only Library Page READ requirement.

Organization, `owner_user_id`, supervisor status, uploader, creator, publisher, company, department, research type, and item type do **not** restrict Library Page READ access.

This rule applies **ONLY** to the Library Page.

Library writes remain organization/permission controlled.

Public APIs remain separate.

Marketplace remains outside this contract.

Organization is not the publisher.

This does **not** make the rest of the platform organization-free.

---

## Public Boundary

Public research/crop **write** tenant selection remains MODEL B (server public org).

Public **browse** of published library / crop-files / training remains **P8-F1** (client org; frozen). Those endpoints are not the Library Page and must not be described as authentication-only complete-Library access.

Anonymous public Library / crop-file APIs must not expose verified scientific research.

---

## Marketplace

Marketplace was **not** modified in Phase 8 and is **out of scope**.

Phase 8 Library Page read access must not be applied to Marketplace APIs, seller/admin flows, or public market listings.

---

## Protected WIP

Phase 8 did not authorize rewriting, replacing, cleaning, or resetting the following protected files (actual repository paths):

- `backend/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php`
- `backend/app/Services/Agriculture/FieldCropTaxonomyCatalog.php`
- `backend/app/Services/Agriculture/Research/QueryUnderstandingService.php`
- `backend/app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php`
- `backend/app/Services/Agriculture/Research/ResearchPlanner.php`
- `backend/app/Services/Agriculture/Research/Synthesis/AnswerComposer.php`

Shorthand `backend/app/Services/Research/...` paths are not repository paths.

---

## Final Phase 8 Gate

| Item | State |
|------|--------|
| 8A-1 | Closed (`53b1797`) |
| 8A-2 | Closed (`337c417`) |
| 8B scientific research + public exclusion | Closed (`1a3f906`) |
| 8B Library Page authentication-only read | Closed (`69c40ab`) |
| P8-F1 / P8-F2 | Frozen (not fixed) |
| Marketplace | Untouched |
| Protected WIP listed above | Untouched by this closeout |

**PHASE_8_CLOSURE_GATE = PASS**
