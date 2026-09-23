# G1 Security Remediation — MODEL B Public Tenant Binding

**Status:** Implemented on the current branch (Phase 8A-1+; MODEL B write binding is on HEAD)  
**Decision:** MODEL B — Server-Controlled Public Organization  
**Root cause:** SEC-ROOT-1  
**Original G1 baseline HEAD:** `74861783574b0fd407d964900af2ecc827fe542f` *(historical start baseline)*  
**G0:** **HISTORICAL.** ADR-021 is present on this branch. Do not re-open G0 as a live gate.

> This file preserves the original G1 write-binding contract. It is **not** the Phase 8 closeout.
> Later 8A-2 audit events and 8B Library Page rules: `docs/architecture/PHASE-8-CLOSEOUT.md`.
> Do not reopen MODEL B vs MODEL C. R1 / Phase 8A-1 closed MODEL B for public **writes**.

---

## Security contract

For unauthenticated public research/crop **write** surfaces:

1. Caller is unauthenticated and has **no** tenant-selection authority.
2. Client `organization` / `organization_id` are accepted for compatibility only and are **never** authoritative.
3. Server resolves the public tenant exclusively from `config('wsa.public_organization_slug')` ← `WSA_PUBLIC_ORG_SLUG` (dev default `wsa-demo`).
4. Missing / empty / nonexistent / inactive configured public org → **fail closed** (HTTP 503 `public_organization_unavailable`).
5. No first-org fallback; no client-org fallback.
6. Trusted context is bound via `PublicTenantResolver::bindPublicTenant()` → `TenantContext::bindPublicTenant(PublicTenantContext)`.
7. Persistence refuses writes when `TenantContext::isPublicBound()` and `organizationId` ≠ bound public id.

Authenticated membership / `X-Organization-Id` flows are unchanged (`ResolveOrganizationContext`).

---

## Implementation boundary

```
Public Controller (research query/synthesize OR crop farming-needs-profile)
  → PublicTenantResolver::bindPublicTenant()
  → PublicTenantContext + TenantContext (publicBound=true)
  → AgriculturalResearchAgent(*(trusted organizationId), …)
  → ScientificKnowledgePersistenceService::persist (defense-in-depth)
  → LibraryItem
```

**New types:**

- `App\Services\Tenancy\PublicTenantContext`
- `App\Services\Tenancy\PublicTenantResolver`
- `App\Services\Tenancy\PublicTenantResolutionException`

**Touched (G1 only):**

- `AgriculturalResearchAgentController` (removed client `resolveOrganization`)
- `PublicFieldCropCultivationController` (same)
- `TenantContext` (public bind flag; `setOrganizationId` clears it)
- `ScientificKnowledgePersistenceService` (public mismatch guard)
- `config/wsa.php` (comment documenting MODEL B)
- Tests + this doc

**Not touched (protected WIP):** `AgriculturalResearchAgent.php` and other scientific WIP files.

---

## Persistence defense-in-depth

When `TenantContext::isPublicBound()` is true, `ScientificKnowledgePersistenceService::persist` skips with status `public_tenant_mismatch` if the supplied `$organizationId` does not equal the bound public organization id. Authenticated / non-public-bound calls are unaffected.

---

## Public write surfaces covered

| Endpoint | Binding |
|----------|---------|
| `POST /api/v1/public/research-agent/query` | MODEL B |
| `POST /api/v1/public/research-agent/synthesize` | MODEL B |
| `POST /api/v1/public/research-agent/feedback` | MODEL B *(Phase 8A-1/8A-2; same public-tenant bind; 8A-2 audits persist)* |
| `GET /api/v1/public/field-crops/farming-needs-profile` | MODEL B |

`plan` / `search` / `validate` remain org-less (no persist).

---

## Explicit non-goals (G1)

| Surface | Disposition |
|---------|-------------|
| Plant diagnosis public API | Unchanged — no Library persistence path |
| Public published library/training/crop-file **browse** | Unchanged — intentional published-content read by client org (RELATED, not write) |
| Authenticated tenant APIs | Unchanged membership model |

---

## Test coverage

`backend/tests/Feature/PublicTenantBindingSecurityTest.php` (+ Stage9 adjustments for MODEL B omit/ignore client org).

Maps to discovery TEST-01…TEST-12.

---

## Open / remaining

1. G0 WIP-safe FF to `1eaced9` — **HISTORICAL** (ADR-021 is on this branch).
2. Commit/push of G1 — **HISTORICAL** (MODEL B write binding shipped in Phase 8A-1).
3. Published browse cross-tenant read remains a separate RELATED decision (**P8-F1 FROZEN**).
4. `.env.example` already has unrelated WIP; `WSA_PUBLIC_ORG_SLUG` is documented in `config/wsa.php` (not re-touched `.env.example` to avoid WIP collision).

---

## Unresolved security questions

None blocking MODEL B write binding. Browse anti-enumeration / force-public-browse is deferred.
