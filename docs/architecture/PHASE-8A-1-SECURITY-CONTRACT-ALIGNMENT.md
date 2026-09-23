# Phase 8A-1 — Security Contract Alignment + Public Expensive-Compute Protection

**Status:** CLOSED — historical Phase 8A-1 document (still authoritative for 8A-1 detail)
**Baseline HEAD:** `53a87348b93d63e61c41ff1031c42203676e1378`
**Date:** 2026-09-22
**Scope:** U8.1 documentation alignment + U8.3 public expensive-compute protection
**Out of scope at 8A-1 time:** U8.4, P8-F1, P8-F2, Phase 8A-2, Phases 9–10

> Phase 8A-2 and Phase 8B subsequently completed Phase 8. P8-F1 / P8-F2 remain **frozen** (not fixed).
> Phase 8 closeout / Library Page contract: `docs/architecture/PHASE-8-CLOSEOUT.md`.
> This file is **not** rewritten; 8A-1 scope below remains the 8A-1 record.

---

## 1. Document authority

| Document | Role |
|----------|------|
| `WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md` | **AUTHORITATIVE** R1–R7 (including MODEL B writes) |
| `G1-PUBLIC-TENANT-BINDING-MODEL-B.md` | **AUTHORITATIVE** G1 write-binding decision (may be untracked WIP copy) |
| This file | **AUTHORITATIVE** Phase 8A-1 closure of stale write wording + U8.3 control model |
| `SECURITY-BOUNDARY-DISCOVERY.md` | **HISTORICAL** pre-G1 forensic (preserve; write path superseded) |
| `MASTER-REMEDIATION-EXECUTION-PLAN.md` §14 pre-8A-1 table | **HISTORICAL** snapshot of client-authoritative writes (superseded below) |
| `SYSTEM-WIDE-FORENSIC-AUDIT.md` Phase-1 NEW-02 row | **HISTORICAL** finding; write mitigation status updated by 8A-1 |
| `#18` / `#19` | **NOT_LOCALLY_RECOVERABLE** — do not invent definitions |

---

## 2. CURRENT AUTHORITATIVE STATE — Public WRITE tenant (MODEL B / R1)

```
Public write request (research query|synthesize|feedback|crop farming-needs)
  → Client may send organization|organization_id (compatibility ONLY)
  → PublicTenantResolver.bindPublicTenant()
       ← config('wsa.public_organization_slug') / WSA_PUBLIC_ORG_SLUG
  → TenantContext.isPublicBound() = true
  → Agent / PositiveResearchFeedbackService uses bound public organizationId
  → ScientificKnowledgePersistenceService refuses mismatch when public-bound
```

**CURRENT facts:**

- Client organization input is **not** authoritative for public writes.
- Missing / empty / inactive configured public org → fail closed (`public_organization_unavailable`, HTTP 503).
- Authenticated routes remain membership / API-client bound (unchanged).

### HISTORICAL STATE (pre-MODEL B / pre-G1) — preserved for evidence

Older Master Plan §14 / Security Boundary Discovery text described:

- Client-supplied `organization` / `organization_id` selected the write tenant via `resolveOrganization`.
- Anyone who could hit the public URL with a valid org id/slug could write into that org.

That description must **not** be treated as current behavior for public **writes**.

---

## 3. FROZEN — P8-F1 / P8-F2 (explicitly unresolved)

| ID | Topic | Status |
|----|-------|--------|
| **P8-F1** | Public published browse (library / crop-files / training) still resolves organization from **client** input | **BLOCKED_BY_HUMAN_DECISION** — Option A (force public tenant) vs Option B (intentional multi-org published catalog). **Do not implement in 8A-1.** |
| **P8-F2** | Plant AI Diagnosis org resolve (client / fallback first-org) | **BLOCKED_BY_HUMAN_DECISION**. **Do not implement in 8A-1.** |

---

## 4. Registry alignment (proven only)

| ID | Proven state after 8A-1 |
|----|-------------------------|
| NEW-02 (write path) | **MITIGATED** for public research/crop/feedback **writes** via MODEL B (code on HEAD) |
| NEW-02 residual | Browse / plant diagnosis remain separate frozen findings (P8-F1 / P8-F2) |
| SEC-1…SEC-4 | Unauthenticated public surface remains; write tenant selection no longer client-authoritative |
| `#18` / `#19` | Still **NOT_LOCALLY_RECOVERABLE** |

---

## 5. U8.3 — Public expensive-compute protection (CURRENT)

### Limiter topology (CURRENT — corrected)

```
ALL /api/v1/public/*
  → throttle:public-global          (aggregate ceiling; default 60/min)
       ├─ public-browse             (specialized sub-bucket; default 60/min)
       └─ public-expensive-compute  (specialized sub-bucket; default 60/min)
```

**Aggregate public capacity:** capped by `public-global` at **60 requests/minute** (inherited from pre-8A-1 shared `throttle:60,1` — not a newly invented product number).

**Specialized buckets are not additive.** `public-expensive-compute` and `public-browse` do **not** create an independent 60+60 combined public capacity. Every public request counts against `public-global` first; switching expensive ↔ browse (or other public routes) **cannot bypass** the global ceiling.

Authenticated `/api/v1/*` and `/api/v1/health/*` remain outside this public limiter group.

### Endpoints under specialized sub-bucket `public-expensive-compute`
(also always under `public-global`)

| Method | Route | Auth | Why expensive |
|--------|-------|------|---------------|
| POST | `/api/v1/public/research-agent/plan` | None | Planning / research stack |
| POST | `/api/v1/public/research-agent/search` | None | Multi-provider scholarly search |
| POST | `/api/v1/public/research-agent/validate` | None | Search + validation |
| GET | `/api/v1/public/field-crops/farming-needs-profile` | None | Full crop research + possible persist |
| POST | `/api/v1/public/research-agent/query` | None | Full research + possible persist |
| POST | `/api/v1/public/research-agent/synthesize` | None | Synthesis + possible persist |

Other `/api/v1/public/*` routes use specialized sub-bucket `public-browse` (also always under `public-global`).

### Control matrix

| Control | State | Notes |
|---------|-------|-------|
| Rate limit | **IMPLEMENTED** | `public-global` = aggregate public ceiling (default **60/min**). Specialized expensive/browse sub-buckets remain for future tightening. Tighter expensive-only values or concurrency = **HUMAN_POLICY_REQUIRED**. |
| Concurrency | **HUMAN_POLICY_REQUIRED** | No approved numeric concurrency policy; not invented. |
| Request budget | **PROVEN EXISTING** (Stage-3 search time budget) | Do not duplicate in Phase 8; no Phase 3 rewrite. |
| Provider-call budget | **PROVEN EXISTING** (orchestrator + time budget) | Phase 8 does not modify protected WIP / Stage-3 selection. |
| Timeout | **PROVEN EXISTING** (`wsa.scientific_http_timeout`, search time budget) | No new conflicting HTTP timeout invented. |
| Abuse detection | **PARTIAL** | 429 + structured tech logs (`security.public_global_throttled`, `security.public_expensive_compute_throttled`); U8.4 audit events deferred. |
| Observability | **IMPLEMENTED** | Warning logs include `request_id`, route, IP, limiter name. |

### Config keys (defaults preserve existing 60/min policy)

- `WSA_PUBLIC_GLOBAL_PER_MINUTE` → `config('wsa.public_global_per_minute')` default `60` (**aggregate public ceiling**)
- `WSA_PUBLIC_EXPENSIVE_COMPUTE_PER_MINUTE` → `config('wsa.public_expensive_compute_per_minute')` default `60` (sub-bucket)
- `WSA_PUBLIC_BROWSE_PER_MINUTE` → `config('wsa.public_browse_per_minute')` default `60` (sub-bucket)

Identity key: request IP (proxies trusted via existing `trustProxies`). Limitation: shared NAT IPs share budget — **documented**, not “solved” by invasive fingerprinting.

Public vs authenticated: authenticated groups retain `throttle:120,1` / `ai-org`; not merged with public global/expensive/browse limiters.

---

## 6. Explicit non-goals

- U8.4 structured `AuditService` public events → Phase 8A-2 **(8A-1 out of scope; later completed — see `PHASE-8-CLOSEOUT.md`)**
- P8-F1 / P8-F2 behavior changes
- Inventing `#18` / `#19` text
- Protected scientific WIP edits
