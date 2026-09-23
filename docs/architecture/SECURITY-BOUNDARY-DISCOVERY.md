# Security Boundary Discovery

> **HISTORICAL / PRE-G1**
>
> This is a pre-G1 forensic discovery record. Preserve the evidence body.
> Do **not** read later sections (MODEL B “PARTIALLY SUPPORTED”, “G1 NOT READY”, Open Decision #3) as the live security contract.
> **CURRENT writes:** MODEL B is implemented (R1 / Phase 8A-1). P8-F1 / P8-F2 remain frozen.
> Phase 8 closeout: `docs/architecture/PHASE-8-CLOSEOUT.md`.

**Document type:** Discovery only (no remediation)  
**Created for:** Pre-G1 / U8.1–U8.2 human decision gate  
**Evidence standard:** PROVEN / PARTIALLY PROVEN / REQUIRES VERIFICATION / SAFE / OPEN DECISION  

---

## 1. Scope

This document answers whether WSA-Enterprise allows:

```
UNAUTHENTICATED PUBLIC INPUT
        ↓
CLIENT-CONTROLLED ORGANIZATION
        ↓
ORGANIZATION RESOLUTION
        ↓
TENANT-SCOPED OPERATION
        ↓
READ / WRITE / PERSISTENCE
```

and maps **every** public path that can turn client-controlled organization identity into tenant-scoped reads or writes.

**In scope:** routes, controllers, middleware, policies/gates, organization resolvers, research/crop/plant-diagnosis public APIs, Library/scientific persistence, tenant context, FE/mobile public clients, config/docs/tests as policy evidence.

**Out of scope:** implementing fixes; changing code/tests/config/ADRs; destructive Git; runtime exploit attempts.

**Related prior findings (reused, not re-audited as Phase-1):** NEW-02, Open Decision #3, MASTER G1 / U8.1–U8.2.

---

## 2. Repository Baseline

| Field | Value |
|-------|--------|
| Local HEAD | `74861783574b0fd407d964900af2ecc827fe542f` |
| Remote architecture commit | `1eaced9cb9902d295c2c04c37d529580b4b345e6` (ADR-021 docs-only child) |
| Relationship | `7486178 → 1eaced9` (linear; application code identical for security paths) |
| Working tree | DIRTY — ~19 tracked modifications + large untracked WIP |
| Index | Clean (no staged changes observed at discovery start) |
| ADR-021 on local tree | ABSENT (readable via object `1eaced9`) |
| This discovery | Read-only investigation + this single new doc file |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

---

## 3. Organization Identity Inputs

### 3.1 Classification legend

| Class | Meaning |
|-------|---------|
| REQUEST INPUT | Client-supplied body/query/route/header |
| AUTHENTICATION-DERIVED | Bound from principal credentials |
| SESSION-DERIVED | Stored client session (e.g. localStorage) used only as header after auth |
| CONFIG-DERIVED | Server or build-time config |
| DATABASE-DERIVED | Loaded from DB after trusted key |
| INTERNAL SERVICE VALUE | Passed between services after upstream resolution |

### 3.2 Inventory (security-relevant)

| Location | Symbol | Class | Data flow |
|----------|--------|-------|-----------|
| `backend/routes/api.php` public group | N/A | ROUTE PARAMETER (prefix) | `/api/v1/public/*` — no `auth.principal` |
| Public research `query`/`synthesize` body | `organization`, `organization_id` | BODY FIELD / REQUEST INPUT | → `AgriculturalResearchAgentController::resolveOrganization` → `organization->id` → Agent → persist |
| Public research `plan`/`search`/`validate` | (no org fields) | — | No org resolution; no Library write |
| Public crop farming-needs query | `organization`, `organization_id` | QUERY PARAMETER / REQUEST INPUT | → `PublicFieldCropCultivationController::resolvePublicOrganization` → Agent `conductCropProfileResearch` → persist |
| Public library/training/crop-files | `organization`, `organization_id` | QUERY PARAMETER / REQUEST INPUT | → `resolvePublicOrganization` → scoped **read** of published rows |
| Public plant diagnosis `analyze` | `organization`, `organization_id` | BODY FIELD (nullable) | If present → `PlantAiDiagnosisController::resolveOrganization` → engine payload; **no Library write proven** |
| Public plant diagnosis `knowledge` | `organization` | BODY FIELD (nullable) | Validated; **not used for org resolve / tenant DB** |
| Config `wsa.public_organization_slug` | `WSA_PUBLIC_ORG_SLUG` / default `wsa-demo` | CONFIG-DERIVED | Fallback when client omits org (research/crop/plant resolvers differ on whether omit is allowed) |
| Authenticated `X-Organization-Id` | header | REQUEST INPUT + AUTHENTICATION-DERIVED membership | `ResolveOrganizationContext` membership check → `TenantContext` |
| API client auth | `client.organization_id` | AUTHENTICATION-DERIVED | `AuthenticateApiPrincipal` binds org; rejects mismatched header |
| Trait `ResolvesOrganization` | request attributes / TenantContext / user orgs | AUTHENTICATION-DERIVED | Used on protected controllers after middleware |
| FE `researchAgent.ts` / `fieldCropCultivation.ts` / `libraryCropFiles.ts` | `VITE_PUBLIC_ORG_SLUG` ?? `wsa-demo` | CONFIG-DERIVED (client build) | Always sends slug as body/query — **not** a server trust boundary |
| Flutter `AppConfig.publicOrganizationSlug` | `--dart-define` / default `wsa-demo` | CONFIG-DERIVED (client build) | Same pattern via `PublicPlatformApi` |
| FE AuthContext `wsa_organization_id` | localStorage | SESSION-DERIVED | Used with authenticated API + `X-Organization-Id` |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  
**SECURITY PROPERTY:** Client can supply tenant identity on public surfaces; authenticated surfaces re-bind via membership or API-client credential.

---

## 4. Organization Resolvers

### 4.1 Resolver table

#### R1 — `AgriculturalResearchAgentController::resolveOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Api/AgriculturalResearchAgentController.php` |
| CLASS | `AgriculturalResearchAgentController` |
| METHOD | `resolveOrganization` (private) |
| INPUT | `organization_id` (int) OR `organization` (slug) OR neither |
| SOURCE OF INPUT | Validated request body (`query`, `synthesize`) |
| AUTHENTICATION REQUIRED? | **No** (public routes) |
| AUTHORIZATION REQUIRED? | **No** |
| MEMBERSHIP CHECK? | **No** |
| OWNERSHIP CHECK? | **No** |
| FALLBACK? | Yes: `config('wsa.public_organization_slug')` then **first Organization by id** |
| DEFAULT ORGANIZATION? | Config slug `wsa-demo` (if present in DB) |
| ERROR BEHAVIOR | Missing slug/id → `ModelNotFoundException` → HTTP 404 `{status: organization_not_found, message: Organization not found.}` |
| CALLERS | `query`, `synthesize` |
| CALLEES | `Organization::query()->findOrFail` / `where('slug')` / `orderBy('id')->firstOrFail` |
| CLASSIFICATION | **UNSAFE** for public use (client picks any existing tenant; no membership) |
| CONTEXT | CONTEXT-DEPENDENT: same method is only used from unauthenticated public actions today |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  
**FLOW:** client body → validate (presence only) → resolve any org → `conductResearch` / `synthesizeResearch($organizationId)` → `ScientificKnowledgePersistenceService::persist`  
**SECURITY PROPERTY:** Tenant write authorization  

#### R2 — `PublicFieldCropCultivationController::resolvePublicOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Api/PublicFieldCropCultivationController.php` |
| METHOD | `resolvePublicOrganization` |
| INPUT | `organization_id` OR `organization` (required_without each other) |
| AUTH / MEMBERSHIP / OWNERSHIP | **No / No / No** |
| FALLBACK | Config public slug, then first org by id |
| CALLERS | `farmingNeedsProfile` |
| CLASSIFICATION | **UNSAFE** — **SAME ROOT CAUSE** as R1 |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  
**FLOW:** query params → resolve → `AgriculturalResearchAgent::conductCropProfileResearch` → `conductResearch` → Library persist  

#### R3 — `PublicPlatformController::resolvePublicOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Api/PublicPlatformController.php` |
| METHOD | `resolvePublicOrganization` |
| INPUT | Required `organization` or `organization_id` (query) |
| AUTH / MEMBERSHIP | **No** |
| FALLBACK | **None** (findOrFail only) |
| CALLERS | `publishedLibraryItems`, `publishedTrainingCourses` |
| CLASSIFICATION | **UNSAFE for tenant selection** on **read** of published content; **no write** |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  
**SECURITY PROPERTY:** Cross-tenant published-content disclosure + existence enumeration  

#### R4 — `PublicCropLibraryFileController::resolvePublicOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Api/PublicCropLibraryFileController.php` |
| Same pattern as R3 | Required client org; published files only; streams file content |
| CLASSIFICATION | **UNSAFE for tenant selection** on **read**; **no write** |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

#### R5 — `PlantAiDiagnosisController::resolveOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Api/PlantAiDiagnosisController.php` |
| METHOD | `resolveOrganization` |
| INPUT | Optional `organization_id` / `organization` on `analyze` |
| AUTH / MEMBERSHIP | **No** |
| FALLBACK | Config public slug, then first org |
| CALLERS | `analyze` only when org fields present |
| CALLEES | Engine receives `organization_id`; KB `knowledge` does **not** call this resolver |
| CLASSIFICATION | **RELATED ROOT CAUSE** for **enumeration**; write-to-Library **not proven** |

**STATUS:** PROVEN (resolve) / PARTIALLY PROVEN (no DB tenant write)  
**CONFIDENCE:** HIGH (resolve); HIGH that Stage-7 KB is in-memory (`InMemoryDiagnosisKnowledgeStore`)  

#### R6 — `ResolveOrganizationContext` middleware

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Middleware/ResolveOrganizationContext.php` |
| INPUT | `X-Organization-Id` OR first active membership |
| AUTHENTICATION | Required (`auth.principal` group) |
| MEMBERSHIP CHECK | **Yes** — `$user->organizations()->where(...)->first()`; 403 + audit on denial |
| API client path | Org already set from credential; middleware only copies to `TenantContext` |
| CLASSIFICATION | **AUTH-SAFE** for authenticated user principal |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

#### R7 — `AuthenticateApiPrincipal` (API client branch)

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Middleware/AuthenticateApiPrincipal.php` |
| INPUT | API client credential → `client.organization_id` |
| Header mismatch | Rejects if `X-Organization-Id` ≠ client org |
| CLASSIFICATION | **AUTH-SAFE** (credential-bound tenant) |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

#### R8 — Concern `ResolvesOrganization`

| Field | Value |
|-------|--------|
| FILE | `backend/app/Http/Controllers/Concerns/ResolvesOrganization.php` |
| INPUT | Request attributes / TenantContext / user’s first org |
| CLASSIFICATION | **AUTH-SAFE** when used only behind `auth.principal` + `resolve.organization` |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

### 4.2 Earliest trust-boundary failure (public writes)

```
CLIENT organization | organization_id
  → public controller resolve* (existence only)
  → int $organizationId
  → AgriculturalResearchAgent::*
  → ScientificKnowledgePersistenceService::persist (trusts int)
  → LibraryItem.organization_id = $organizationId
```

**First divergence:** unauthenticated client-controlled org resolve with no membership/auth gate.  
Downstream persist is **SEC-DERIVED**, not a separate root cause.

---

## 5. Public Routes

Source: `backend/routes/api.php` — `Route::prefix('public')->middleware('throttle:60,1')` (no `auth.principal`).

| Method | Path | Controller | Org input? | Capability |
|--------|------|------------|------------|------------|
| GET | `/api/v1/public/services` | `PublicPlatformController::serviceCatalog` | Documents browse params | READ catalog metadata |
| GET | `/api/v1/public/library/items` | `publishedLibraryItems` | **Yes** (required) | READ published LibraryItems |
| GET | `/api/v1/public/library/crop-files` | `PublicCropLibraryFileController::index` | **Yes** | READ published file metadata |
| GET | `/api/v1/public/library/crop-files/{fileId}/content` | `content` | **Yes** | READ/stream published files |
| GET | `/api/v1/public/training/courses` | `publishedTrainingCourses` | **Yes** | READ published courses |
| GET | `/api/v1/public/market/listings` | `MarketplacePublicController` | No | READ published marketplace |
| GET | `/api/v1/public/market/listings/{listing}` | `show` | No | READ published listing |
| GET | `/api/v1/public/market/categories` | `categories` | No | READ |
| GET | `/api/v1/public/market/units` | `units` | No | READ |
| GET | `/api/v1/public/field-crops/taxonomy` | `PublicFieldCropTaxonomyController` | No | READ static catalog |
| GET | `/api/v1/public/field-crops/farming-needs-profile` | `PublicFieldCropCultivationController` | **Yes** | **WRITE** (research persist) + expensive work |
| POST | `/api/v1/public/research-agent/query` | `query` | **Yes** | **WRITE** + expensive work |
| POST | `/api/v1/public/research-agent/plan` | `plan` | No | EXPENSIVE work (no persist) |
| POST | `/api/v1/public/research-agent/search` | `search` | No | EXPENSIVE work (no persist) |
| POST | `/api/v1/public/research-agent/validate` | `validate` | No | EXPENSIVE work (no persist) |
| POST | `/api/v1/public/research-agent/synthesize` | `synthesize` | **Yes** | **WRITE** + expensive work |
| POST | `/api/v1/public/plant-diagnosis/analyze` | `analyze` | Optional | EXPENSIVE work; org optional; no Library write proven |
| POST | `/api/v1/public/plant-diagnosis/knowledge` | `knowledge` | Optional unused for tenant | READ in-memory KB |

**Adjacent unauthenticated (outside `/public` prefix):** `/api/v1/auth/*` registration/login/OAuth/OTP — creates/authenticates users; not Library research persist. Not expanded here beyond noting they are throttled separately.

**Protected contrast:** `/api/v1/*` group with `auth.principal`, `resolve.organization`, `api_client.routes`, `throttle:120,1` — membership-checked tenant context.

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

---

## 6. Public Write Surfaces

### 6.1 Map

| Route | Controller → Service | Org resolution | AuthZ | Persistence | Public can select org? | Cross-tenant write? | LibraryItem? |
|-------|----------------------|----------------|-------|-------------|------------------------|---------------------|--------------|
| POST `.../research-agent/query` | → `AgriculturalResearchAgent::conductResearch` | R1 | None | `ScientificKnowledgePersistenceService::persist` | **Yes** | **Yes** (any existing org id/slug) | **Yes** (when verification gates pass) |
| POST `.../research-agent/synthesize` | → `synthesizeResearch` | R1 | None | Same | **Yes** | **Yes** | **Yes** |
| GET `.../farming-needs-profile` | → `conductCropProfileResearch` → `conductResearch` | R2 | None | Same | **Yes** | **Yes** | **Yes** |
| POST `.../plant-diagnosis/analyze` | → `PlantAiDiagnosisEngine::diagnose` | R5 if org present | None | No LibraryItem path proven; Stage-7 store in-memory | Influences resolved id in payload | N/A for Library | **No** (proven absence of Library write) |
| Public library/training/crop-file GETs | Eloquent queries | R3/R4 | None | **Read only** | **Yes** | N/A write; **cross-tenant published read** | Read |
| plan/search/validate | Agent stages 2–4 | None | None | None | No | No | No |

### 6.2 Answers (aggregate)

| Question | Answer |
|----------|--------|
| Can public caller select an organization? | **Yes** on write routes requiring org + all published browse routes |
| Can public caller influence organization? | **Yes** (id or slug). FE/mobile normally send `wsa-demo`, but API does not enforce that |
| Can public caller write data? | **Yes** — research query/synthesize + crop farming-needs |
| Write into existing organization? | **Yes** |
| Write into another organization? | **Yes**, if that org exists |
| Indirect persistence? | Crop path reuses research Agent persist |
| Trust `organization_id` in service? | **Yes** — Agent and PersistenceService take `int $organizationId` with no auth check |
| LibraryItem creation? | **Yes** on successful Stage-5 persist |
| Other tenant-scoped records? | Semantic index sync via `KnowledgeSemanticIndexSync::syncLibraryItem` after Library write (same org scoping as item) |

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  
**FILE:** controllers + `ScientificKnowledgePersistenceService.php` + `AgriculturalResearchAgent.php`  

---

## 7. Library/Persistence Paths

### 7.1 Primary write chain (public)

```
ROUTE POST /api/v1/public/research-agent/query|synthesize
  OR GET /api/v1/public/field-crops/farming-needs-profile
← CONTROLLER resolve* (client org)
← AgriculturalResearchAgent::conductResearch|synthesizeResearch|conductCropProfileResearch
← ScientificKnowledgePersistenceService::persist($organizationId, ...)
← LibraryItem save (organization_id = $organizationId, publication_status = published)
← KnowledgeSemanticIndexSync::syncLibraryItem
```

| Checkpoint | Auth source | Org source | AuthZ | Tenant check | Policy/Gate | Model protection | DB constraint | Risk |
|------------|-------------|------------|-------|--------------|-------------|------------------|---------------|------|
| Controller | None | Client | Existence only | None | None | N/A | N/A | **SEC-ROOT** |
| Agent | None | int arg | None | None | None | N/A | N/A | SEC-DERIVED |
| PersistenceService | None | int arg (`<1` skip only) | None | Filters by org_id for upsert | None | None | FK `organization_id` → organizations | SEC-DERIVED |
| LibraryItem | N/A | column set | None | No global scope found | No LibraryItemPolicy found | fillable includes `organization_id` | unique `(organization_id, slug)` | SEC-DERIVED |

**Critical rule confirmed:** controller validation (`required_without`, integer/string) ≠ authorization.

### 7.2 Other Library writers (not public research entry)

| Path | Entry | Org source | Public? |
|------|-------|------------|---------|
| `FieldCropLibraryRepository::save...` | Seeders / CropKnowledgeEngine / discoverers | Caller-supplied int | Not a public HTTP entry by itself |
| `LibraryController` | Authenticated API | `ResolvesOrganization` | No |
| `KnowledgeIngestionService` | AI retrieval ops | Service caller | Behind auth operator/AI surfaces |

**STATUS:** PROVEN for public research/crop chain; PARTIALLY PROVEN that no other public HTTP path writes LibraryItem  
**CONFIDENCE:** HIGH  

### 7.3 Public Library **read** chain

```
GET /public/library/items|crop-files|training/courses
← resolvePublicOrganization(client)
← LibraryItem|TrainingCourse where organization_id AND publication_status/status = published
```

Risk: cross-tenant **published** content disclosure + org existence oracle — not the same as write, but same client-controlled tenant selection pattern.

---

## 8. Tenant Isolation Model

| Layer | Mechanism | Public research/crop? |
|-------|-----------|------------------------|
| Middleware | `ResolveOrganizationContext` membership | **Not applied** on `/public/*` |
| TenantContext | Request-scoped org id | Not set on public research path |
| Controller | Ad-hoc `resolveOrganization` | Existence lookup only |
| Policy/Gate | `OrganizationPermissionPolicy` family | **Not used** on public research |
| Service | Trusts `organizationId` int | No re-check |
| Model | `organization_id` column; no tenant global scope observed on `LibraryItem` | Column set by writer |
| Database | FK + unique `(organization_id, slug)` | Prevents orphan FK; **does not** prevent wrong-tenant write |

**Overall classification:** **INCONSISTENT**

- Authenticated APIs: **MULTI-LAYER** (middleware membership + controller `ResolvesOrganization` + query filters).
- Public research/crop: **CONTROLLER-ONLY existence check** then service trust → effectively **ABSENT** authorization for tenant write.

**Client-supplied tenant identity without trusted auth context:** all R1–R5 public resolvers; published browse R3–R4.

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

---

## 9. Enumeration Risks

| Resolver | Valid id | Valid slug | Existence oracle | Metadata leak | Notes |
|----------|----------|------------|------------------|---------------|-------|
| R1 research | 404 vs success timing/body | Same | **Yes** — distinct 404 `organization_not_found` | Message does not dump SQL; returns org not found | Tests assert no schema leak (`WsaEnterpriseStage9SecurityTest`) |
| R2 crop | Same pattern | Same | **Yes** | Arabic message + status | |
| R3/R4 browse | Laravel `findOrFail` / `firstOrFail` | Same | **Yes** | Success returns `organization_id` + `organization_slug` | Stronger metadata confirmation on success |
| R5 plant | Same if org provided | Same | **Yes** when org fields sent | | Org optional — omit avoids oracle |
| Auth middleware | 403 membership denial | N/A | Different (auth required) | Audit `security.cross_tenant_denied` | SAFE pattern |

**STATUS:** PROVEN (static)  
**CONFIDENCE:** HIGH  
**RUNTIME SECURITY VERIFICATION REQUIRED:** differential timing / cache side-channels (optional; not performed).

---

## 10. Crop Security Path

| Field | Value |
|-------|--------|
| Route | `GET /api/v1/public/field-crops/farming-needs-profile` |
| Controller | `PublicFieldCropCultivationController::farmingNeedsProfile` |
| Resolver | R2 `resolvePublicOrganization` |
| Service | `AgriculturalResearchAgent::conductCropProfileResearch` → `conductResearch` |
| Persistence | Same Stage-5 Library persist as Home research |
| Taxonomy route | No org — SAFE for tenant write |
| FE | `frontend/src/api/fieldCropCultivation.ts` always sends `VITE_PUBLIC_ORG_SLUG ?? 'wsa-demo'` |
| Flutter | `mobile/lib/data/api/public_api.dart` uses `publicOrganizationSlug` |

**Classification vs research agent write:** **SAME ROOT CAUSE** (client org → Agent → persist).  
**Crop-only taxonomy:** **SAFE** (no org).  
**Crop published file browse:** **RELATED ROOT CAUSE** (client org → tenant **read** of published files).

**STATUS:** PROVEN  
**CONFIDENCE:** HIGH  

---

## 11. Plant Diagnosis Security Path

| Field | Value |
|-------|--------|
| Routes | `POST .../plant-diagnosis/analyze`, `.../knowledge` |
| Auth | Public + throttle only |
| Org on analyze | Optional; if present → R5 any-org resolve |
| Engine | Echoes `organization_id` in result; builds `PlantDiagnosisRequest` |
| Library persistence | **Not found** in diagnosis services |
| Knowledge base | `InMemoryDiagnosisKnowledgeStore` — “no DB migration required” |
| Knowledge endpoint | Accepts `organization` in validation; does not resolve Organization model |

**Classification:**

| Concern | Class |
|---------|--------|
| Client org → any tenant resolve | **RELATED ROOT CAUSE** (enumeration / trust of client id in response metadata) |
| Client org → Library write | **DIFFERENT / SAFE** relative to NEW-02 write (no path proven) |
| Expensive unauthenticated work | Separate abuse/cost concern (U8.3 territory) — out of G1 write-binding scope |

**STATUS:** PROVEN for resolve; PROVEN no Library write in diagnosis stack  
**CONFIDENCE:** HIGH  

---

## 12. Product Policy Evidence

### Evidence for MODEL A (public research non-persistent)

| Evidence | Assessment |
|----------|------------|
| `plan`/`search`/`validate` have no persist | Supports **partial** non-persist stages |
| `query`/`synthesize`/crop profile **do** persist | Contradicts pure MODEL A |
| Docs call Library “memory” with Internet-First primary | Does not disable public persist |

**Verdict:** **NOT SUPPORTED** as the current overall product behavior for `query`/`synthesize`/crop profile.

### Evidence for MODEL B (persist only into server-selected public/demo org)

| Evidence | Assessment |
|----------|------------|
| `config/wsa.php` `public_organization_slug` → `WSA_PUBLIC_ORG_SLUG` default `wsa-demo` | Strong server intent for a designated public org |
| Resolver fallbacks to that slug | Supports intended default tenant |
| FE/Flutter always send `wsa-demo` / `VITE_PUBLIC_ORG_SLUG` | Client convention matches demo org |
| Tests seed/use `wsa-demo` for public research/crop | Habitual demo-org contract in tests |
| **But** resolvers honor arbitrary client id/slug first | Server does **not** currently bind; client can override |
| MASTER Open Decision #3 still open | Human policy not closed |
| NEW-02 blocker: “Product intent for public demo org” | Explicitly unresolved |

**Verdict:** **PARTIALLY SUPPORTED** by architecture and clients; **not enforced** by server; **OPEN DECISION** for product.

### Evidence for MODEL C (auth + membership required to persist)

| Evidence | Assessment |
|----------|------------|
| Authenticated stack has membership middleware | Pattern exists elsewhere |
| Public research routes explicitly unauthenticated | Contradicts requiring auth today |
| PublicPlatformController lists library manage as `requires_auth` while public research still persists | Catalog messaging ≠ research behavior |
| No public research route under `auth.principal` | Would be a product change |

**Verdict:** **PARTIALLY SUPPORTED** as an available platform pattern; **NOT SUPPORTED** as current public research behavior.

### Evidence for MODEL D (transient until explicit authorized persist)

| Evidence | Assessment |
|----------|------------|
| Separate stage endpoints without org | Stages can run without persist |
| No “save” endpoint requiring auth after synthesize | No explicit authorized commit step |
| `query`/`synthesize` persist inline | Contradicts MODEL D for those endpoints |

**Verdict:** **NOT SUPPORTED** for current `query`/`synthesize`/crop profile; stages 2–4 alone resemble transient work.

---

## 13. Provable Security Contract

What is **already true** in code today (not aspirational):

| # | Question | Provable answer |
|---|----------|-----------------|
| 1 | Who is the caller? | Unauthenticated public client (throttle only) on `/public/*` research/crop write paths |
| 2 | What identity is trusted? | **Existence** of Organization row matching client id/slug (**not** membership) |
| 3 | Who determines organization? | **Client**, with config slug / first-org fallback only if client omits (and validators often forbid omit) |
| 4 | What can the client provide? | Any `organization` slug or `organization_id` integer that exists |
| 5 | What must be server-derived? | **OPEN DECISION** — config defines a public slug but server does not force it on write paths |
| 6 | What authorization is required? | **None** on public write paths |
| 7 | What persistence is permitted? | Stage-5 verified knowledge → `LibraryItem` under resolved org when claims/citations/validation thresholds pass |
| 8 | Unauthorized organization input? | **OPEN DECISION** — “unauthorized” is undefined without membership; nonexistent → 404 |
| 9 | When no organization exists? | 404 `organization_not_found`; fallback first org if slug path misses public slug (dangerous if DB nonempty) |
| 10 | Response | JSON research/crop payload including persistence status; 404 on missing org |
| 11 | What may be exposed? | Org existence; on browse success `organization_id`/`slug`; research content; published library/file content for chosen org |

**Aspirational contract for G1 cannot be fully stated without Open Decision #3.**

---

## 14. Root-Cause Map

| ID | Path | Class | Notes |
|----|------|-------|-------|
| SEC-ROOT-1 | Public client org → R1/R2 → Agent → Library persist | **SEC-ROOT** | Earliest trust-boundary failure (NEW-02 / RC-D) |
| SEC-DERIVED-1 | `ScientificKnowledgePersistenceService` trusts int | SEC-DERIVED | Same root; harden as defense-in-depth optional |
| SEC-DERIVED-2 | Semantic index sync after Library write | SEC-DERIVED | Follows LibraryItem org |
| SEC-RELATED-1 | Public browse R3/R4 client org → published read | RELATED | Disclosure/enumeration; not G1 write root |
| SEC-RELATED-2 | Plant diagnosis optional org resolve | RELATED | Enumeration; no Library write |
| SAFE-1 | Research plan/search/validate | SAFE | No org; no persist |
| SAFE-2 | Taxonomy / marketplace public lists | SAFE | No client org tenant write |
| SAFE-3 | Authenticated `ResolveOrganizationContext` | SAFE | Membership enforced |
| LEGACY | First-org `orderBy('id')->firstOrFail` fallback | LEGACY risk amplifier | If public slug missing |
| WIP | Scientific WIP overlapping Agent/QUS/etc. | WIP | Must not be disturbed by discovery; may affect future G1 file touch list if WIP modifies Agent |

**Earliest failure:** SEC-ROOT-1.

---

## 15. Security Remediation Boundary

**Recommended primary boundary:** **Organization resolution at the public controller (or a dedicated public-org resolver service used only by public routes)** — enforce server-selected public org **or** reject client tenant identity — **before** passing `organizationId` into `AgriculturalResearchAgent`.

| Option | Fit | Why |
|--------|-----|-----|
| Controller / public resolver | **Best primary** | First divergence is R1/R2; matches MASTER G1 file list; smallest blast radius; testable via HTTP Feature tests |
| Middleware | Possible secondary | Would need a **new** public-specific middleware (current `resolve.organization` is auth-membership oriented) |
| Policy/Gate | Poor alone | Policies are user-permission oriented; public has no user |
| Persistence guard | Defense-in-depth | Should not be sole fix; services are also called from non-public paths with trusted ints |
| TenantContext | Incomplete alone | Public routes never set it today |
| Multiple layers | Ideal end-state | Controller bind + persistence allowlist of public org id — after product decision |

**Why not prefer persistence-only:** Agent still performs expensive work and returns tenant-scoped persistence metadata; wrong org id must die at the entry trust boundary.

**Crop:** Same root cause; fixing only research controller **leaves** crop write path open unless R2 included or shared resolver extracted — G1 unit scope should explicitly include or exclude crop (OPEN for execution scoping; security-wise crop is same root).

**Plant diagnosis:** Not required for NEW-02 Library write closure; optional follow-on for enumeration.

---

## 16. Security Test Matrix

*(Design only — not implemented.)*

### TEST-01 — Public request without organization

| Field | Value |
|-------|--------|
| PRECONDITION | Public org `wsa-demo` may exist |
| REQUEST | POST `/public/research-agent/query` with `query` only |
| EXPECTED AUTHORIZATION | N/A |
| EXPECTED RESULT | 422 (current validators) **or** post-fix: server-bound behavior if product chooses omit→public-org |
| EXPECTED DATABASE EFFECT | No new LibraryItem |
| SECURITY PROPERTY | Client cannot skip org checks ambiguously |

### TEST-02 — Public request with valid public organization

| Field | Value |
|-------|--------|
| PRECONDITION | Org slug = config public slug |
| REQUEST | POST query/synthesize with that slug |
| EXPECTED AUTHORIZATION | Allowed under MODEL B; forbidden under MODEL C without auth |
| EXPECTED RESULT | 200 pipeline; persistence only into public org |
| EXPECTED DATABASE EFFECT | LibraryItem only for public org id |
| SECURITY PROPERTY | Legitimate demo path |

### TEST-03 — Public request with arbitrary organization

| Field | Value |
|-------|--------|
| PRECONDITION | Second org `victim-org` exists |
| REQUEST | POST with `organization=victim-org` |
| EXPECTED AUTHORIZATION | **Deny** under MODEL B (force public / 403/422); under MODEL C require auth |
| EXPECTED RESULT | Non-success for persist into victim |
| EXPECTED DATABASE EFFECT | **Zero** LibraryItem for victim |
| SECURITY PROPERTY | No client tenant selection |

### TEST-04 — Public request targeting another organization (numeric id)

| Field | Value |
|-------|--------|
| PRECONDITION | Victim org id known |
| REQUEST | `organization_id=<victim>` |
| EXPECTED AUTHORIZATION | Deny (MODEL B/C) |
| EXPECTED RESULT | 403/422/404 per chosen contract (**OPEN**) |
| EXPECTED DATABASE EFFECT | No write to victim |
| SECURITY PROPERTY | Cross-tenant write prevention |

### TEST-05 — Invalid organization

| Field | Value |
|-------|--------|
| REQUEST | Nonexistent slug/id |
| EXPECTED RESULT | 404 (current) or uniform error if anti-enumeration chosen |
| EXPECTED DATABASE EFFECT | None |
| SECURITY PROPERTY | Stable error; optional anti-enumeration |

### TEST-06 — Authenticated authorized organization

| Field | Value |
|-------|--------|
| PRECONDITION | User member of org A; if product keeps separate authenticated research API |
| REQUEST | Auth + membership header |
| EXPECTED AUTHORIZATION | Allow |
| NOTE | Today public routes ignore auth; may be N/A until MODEL C API exists |
| SECURITY PROPERTY | Membership path |

### TEST-07 — Authenticated unauthorized organization

| Field | Value |
|-------|--------|
| PRECONDITION | User not member of org B |
| REQUEST | Auth + `X-Organization-Id: B` on **protected** routes |
| EXPECTED RESULT | 403 + audit (existing middleware tests) |
| SECURITY PROPERTY | Auth tenant isolation regression |

### TEST-08 — Cross-tenant access

| Field | Value |
|-------|--------|
| Combine | TEST-03/04 write + browse read of unpublished items |
| EXPECTED | Unpublished items never public-readable; writes never cross tenant |
| SECURITY PROPERTY | Isolation |

### TEST-09 — Library persistence ownership

| Field | Value |
|-------|--------|
| PRECONDITION | Successful verified synthesis under allowed org only |
| EXPECTED DATABASE EFFECT | `library_items.organization_id` equals server-authorized org |
| SECURITY PROPERTY | Ownership integrity |

### TEST-10 — Organization enumeration

| Field | Value |
|-------|--------|
| REQUEST | Probe valid vs invalid slugs/ids |
| EXPECTED | Per product: current 404 oracle **or** hardened uniform response |
| SECURITY PROPERTY | Existence leakage |

### TEST-11 — Crop public path

| Field | Value |
|-------|--------|
| REQUEST | GET farming-needs-profile with victim org |
| EXPECTED | Same authorization as research write (SAME ROOT) |
| EXPECTED DATABASE EFFECT | No victim LibraryItem |
| SECURITY PROPERTY | Crop parity with research binding |

### TEST-12 — Plant diagnosis public path

| Field | Value |
|-------|--------|
| REQUEST | analyze with victim org / without org |
| EXPECTED | No LibraryItem; optional: reject arbitrary org resolve |
| EXPECTED DATABASE EFFECT | No tenant Library write |
| SECURITY PROPERTY | Diagnosis not a NEW-02 write vector |

---

## 17. MODEL A/B/C/D Evidence Matrix

| Model | Supported by current architecture? | Summary |
|-------|--------------------------------------|---------|
| A — Non-persistent public research | **NOT SUPPORTED** | `query`/`synthesize`/crop profile persist |
| B — Server-selected public/demo org only | **PARTIALLY SUPPORTED** | Config + clients align; server does not enforce |
| C — Auth + membership required | **PARTIALLY SUPPORTED** (pattern elsewhere) / **NOT SUPPORTED** (public routes today) | Would be a product change |
| D — Transient until explicit authorized save | **NOT SUPPORTED** | Inline persist on query/synthesize |

**Do not treat PARTIALLY SUPPORTED MODEL B as a closed decision.** MASTER §25 Open Decision #3 remains authoritative.

---

## 18. G1 Readiness

**NOT READY — PRODUCT DECISION**

Additionally:

| Factor | Status |
|--------|--------|
| Open Decision #3 (demo-org bind vs auth-required) | **Open** |
| G0 tip at `1eaced9` / ADR-021 local | Preferred baseline sync still open (WIP-safe FF) — **NOT READY — WIP/BASELINE BLOCKER** for governance purity; application security code identical on `7486178` vs `1eaced9` |
| Technical root cause known? | **Yes** (SEC-ROOT-1) |
| Remediation boundary identifiable? | **Yes** (public org resolve before Agent) |
| Crop same-root inclusion in G1 file set? | **OPEN** (security-same; plan file list historically research-controller-first) |

G1 implementation must not proceed until product chooses MODEL B vs C (or another explicit model) and scopes crop parity.

---

## 19. Open Decisions

1. **Open Decision #3 (MASTER):** Public research persistence — bind to server public/demo org only (**MODEL B**) vs require authentication/membership (**MODEL C**) vs other.
2. **G0:** Authorize WIP-safe fast-forward to `1eaced9` before application commits?
3. **G1 scope:** Include `PublicFieldCropCultivationController` in the same atomic unit as research (recommended security-wise) or defer as “related” with accepted residual risk?
4. **Public published browse (R3/R4):** Intentional multi-tenant published catalog by slug, or should browse also be forced to public org only?
5. **Anti-enumeration:** Keep distinct 404 `organization_not_found` vs uniform error?
6. **First-org fallback:** Remove `orderBy('id')->firstOrFail` as unsafe default?
7. **Plant diagnosis org fields:** Strip/ignore vs resolve-only public slug?

---

## 20. Blockers

| Blocker | Blocks |
|---------|--------|
| Product intent Open Decision #3 | G1/U8.1–U8.2 behavioral implementation |
| Human authorization for G0 FF | Local ADR-021 tip / governance baseline |
| Dirty protected WIP | Any remediation that would overwrite scientific WIP files |
| Crop inclusion decision | Completeness of write-surface closure |
| Runtime security verification (optional) | Timing/oracle hardening claims |

---

## Appendix A — Authenticated contrast (safe pattern)

```
Bearer user
  → AuthenticateApiPrincipal
  → ResolveOrganizationContext
       X-Organization-Id must be a membership
       else 403 + audit security.cross_tenant_denied
  → TenantContext
  → Controllers via ResolvesOrganization
```

This pattern **must not** be confused with public `resolveOrganization` helpers that share a similar name.

---

## Appendix B — Similar identity→persist patterns (bounded)

| Pattern | Public? | Notes |
|---------|---------|-------|
| `X-Organization-Id` | Only with auth | Membership-checked — SAFE |
| API client org | Credential-bound | SAFE |
| Public `organization_id` body/query | Yes | UNSAFE on write paths |
| Plant `organization_id` in diagnosis payload | Optional public | RELATED (no Library write) |
| Marketplace public | No org id selection | Lists published globally — different model |

No additional public `user_id`/`owner_id` → Library write path was identified in this scoped search.

---

## Appendix C — Discovery integrity

| Check | Result |
|-------|--------|
| Application code changed | **NO** |
| Tests changed | **NO** |
| Config changed | **NO** |
| ADRs changed | **NO** |
| WIP touched | **NO** (read-only) |
| Git commit/push | **NO** |
| File created | `docs/architecture/SECURITY-BOUNDARY-DISCOVERY.md` |

---

*End of Security Boundary Discovery.*
