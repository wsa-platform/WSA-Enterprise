# Phase 11 — Architecture Audit

**Repository:** wsa-platform/WSA-Enterprise  
**Baseline:** Phase 10 merged to `main` (PR #4, commit `cad5dce`)  
**Audit date:** 2026-08-10  
**Auditor role:** Lead Architect / Principal Engineer  
**Scope:** Full-stack read-only inspection — no implementation changes

---

## 1. Executive Summary

WSA-Enterprise is a **modular monolith** with a mature Laravel 12 API, React 19 web client, Flutter mobile client, Docker Compose staging stack, and GitHub Actions CI. Phase 10 delivered isolated test databases, async AI with Redis queue workers, expanded audit logging, cross-tenant security tests, modular frontend API clients, partial OpenAPI, and production hardening documentation.

**Verdict:** The system is a **working, staging-ready baseline** suitable for controlled demos and internal enterprise pilots. It is **not yet a full production-grade enterprise platform** without additional work on global tenant enforcement, enterprise RBAC, billing, notifications, observability, API completeness, and operational hardening.

**Priority order for Phase 11:** Security → Data integrity → Multi-tenancy → Backward compatibility → Testability → Scalability → Performance → UX.

---

## 2. Audit Matrix (20 Areas)

| # | Area | Status | Summary |
|---|------|--------|---------|
| 1 | Backend architecture | **Partial → Strong** | Controller-centric modular monolith; services for AI, audit, authz; 20 API controllers |
| 2 | Database schema | **Partial → Strong** | 21 migrations; ~54 org-scoped models; FK constraints; performance indexes |
| 3 | Models & relationships | **Partial** | Org ownership present; many models lack Eloquent relationship definitions |
| 4 | Authentication | **Production-ready (staging)** | Sanctum bearer tokens; throttling; registration off by default |
| 5 | Organizations / tenants | **Partial → Strong** | M2M users↔orgs; `X-Organization-Id` middleware; no global Eloquent scope |
| 6 | Roles & permissions | **Partial** | Pivot baselines + org-scoped roles; 17 permission strings; cache staleness risk |
| 7 | Audit logging | **Partial** | `AuditLog` model + `AuditService`; manual events; `Auditable` on Role/Permission only |
| 8 | AI request architecture | **Partial → Strong** | Async/sync lifecycle; `AiProviderInterface`; Mock provider only |
| 9 | Queue workers & Redis | **Production-ready (staging)** | Dedicated queue service; Redis wait entrypoint; isolated test sync queue |
| 10 | API controllers/routes | **Production-ready** | `/api/v1/*`; module-dispatch pattern; consistent permission checks |
| 11 | OpenAPI documentation | **Partial** | ~30–40% route coverage; CI validation |
| 12 | React architecture | **Partial → Strong** | Modular `api/` layer; 9 pages; no access-admin UI; no async AI polling UI |
| 13 | Flutter architecture | **Partial** | Monolithic `ApiClient`; typed `models.dart` unused; demo creds in UI |
| 14 | Nginx configuration | **Production-ready (staging)** | Gateway split `/api/` → PHP-FPM, `/` → SPA |
| 15 | Docker Compose | **Partial → Strong** | Full stack + queue; no TLS; host-exposed DB/Redis ports |
| 16 | Environment configuration | **Partial** | `.env.example` documented; `AI_ASYNC_DISPATCH` explicit; secrets in local `.env` |
| 17 | CI/CD workflows | **Partial** | 4-job CI (backend, frontend, mobile, openapi); no deploy pipeline |
| 18 | Existing tests | **Strong** | 84 PHPUnit tests; cross-tenant + async AI; no frontend E2E |
| 19 | Security controls | **Partial → Strong** | Authz on all endpoints; rate limits; no 2FA; token expiry optional |
| 20 | Documentation | **Strong** | Architecture v1, deployment, security, testing; some stale sections |

---

## 3. Backend Architecture

### 3.1 Structure

```
backend/
├── app/
│   ├── Http/Controllers/Api/     # 20 controllers (module-dispatch pattern)
│   ├── Http/Middleware/          # ResolveOrganizationContext, LogApiRequests
│   ├── Http/Requests/            # Form validation (auth, AI)
│   ├── Jobs/ProcessAiRequest.php
│   ├── Models/                   # ~60 Eloquent models
│   ├── Policies/                 # Farm, Business (registered, largely unused)
│   ├── Services/
│   │   ├── Ai/                   # AiService, MockAiProvider, normalizer
│   │   ├── Audit/AuditService.php
│   │   ├── Authorization/PermissionService.php
│   │   └── Diagnosis/, Media/
│   ├── Support/ApiResponse.php
│   └── Contracts/AiProviderInterface.php
├── config/                       # ai, permissions, sanctum, queue, database
├── database/migrations/          # 21 files
├── database/seeders/             # Database, Access, Agricultural, Phase5
├── routes/api.php
└── tests/                        # 21 test classes, 84 tests
```

### 3.2 Production-ready

- Laravel 12 bootstrap with JSON exception rendering for `api/*`
- Sanctum authentication with logout token revocation
- `PermissionService` + `authorizePermission()` on every protected controller method
- `ResolveOrganizationContext` middleware validates org membership
- Module-dispatch CRUD (`/farm/{module}`, `/crop/{module}`, etc.) — DRY, tested
- AI lifecycle: `pending` → `processing` → `completed`/`failed` with idempotent processing
- `ProcessAiRequest` job: `ShouldBeUnique`, retry, dedicated `failed()` handler
- Staging DB safeguard: `FORBIDDEN_TEST_DATABASES=wsa_enterprise`
- Rate limiting: auth 20/min, API 120/min, AI 30/min

### 3.3 Partial

- **Dual authorization patterns:** Policies/gates registered but controllers use `PermissionService` directly
- **No global tenant scope:** Isolation depends on manual `where('organization_id', …)` in every query
- **Permission cache:** 60s TTL; cleared on role assign only — not on permission CRUD or membership changes
- **API response envelopes:** `ApiResponse` helper used on audit logs only; other endpoints return raw Laravel JSON
- **Auditing coverage:** Most domain mutations not auto-audited; only auth, AI lifecycle, user create, role/permission create
- **Models:** Many are minimal fillable-only classes without relationship definitions

### 3.4 Missing

- Real AI provider implementations (OpenAI, Anthropic, etc.)
- Teams entity and team-scoped permissions
- Enterprise role taxonomy (Super Admin, Owner, Admin, Manager, Member, Viewer)
- Billing/subscriptions domain
- Notification dispatch pipeline (in-app model exists; no queue-driven delivery)
- API rate limiting per organization
- Structured request tracing / correlation IDs
- Laravel Horizon or queue monitoring
- Email verification, password reset, 2FA

### 3.5 Must NOT change

| Contract | Reason |
|----------|--------|
| `/api/v1/*` prefix | All clients depend on it |
| `X-Organization-Id` header | Middleware, tests, logging, mobile/web clients |
| Sanctum bearer `{ token, user }` login response | Web + mobile auth flows |
| Permission string names in `config/permissions.php` | Controllers + seeders + tests |
| Module-dispatch URL patterns | Frontend/mobile module configs |
| `AiRequest` status lifecycle | Async polling clients |
| `AI_ASYNC_DISPATCH` env toggle | Sync 201 vs async 202 behavior |
| `FORBIDDEN_TEST_DATABASES` guard | Prevents staging DB wipe in tests |
| Pagination backward compatibility | Omitting `page` returns arrays, not envelopes |

---

## 4. Database Schema

### 4.1 Migration inventory (21 files)

| Domain | Tables | Org-scoped |
|--------|--------|------------|
| Core | users, organizations, organization_user, projects, tasks | Yes (except users) |
| Access | roles, permissions, role_user, role_permission | Yes |
| Business | companies, branches, employees, customers, suppliers, products, categories, warehouses | Yes |
| Commerce | purchase_orders, sales_orders, invoices, inventory_*, app_notifications | Yes |
| Agricultural | farms, regions, fields, blocks, greenhouses, irrigation, GPS, GIS | Yes |
| Crop/Soil | crop_types, varieties, seasons, harvests, yields, soil_analyses | Yes |
| Diagnosis | subjects, symptoms, diseases, requests, results | Yes |
| Training | courses, lessons, enrollments, progress, certificates | Yes |
| Library | categories, items, tags | Yes |
| AI | ai_requests | Yes |
| Audit | audit_logs | Yes |
| Infra | jobs, cache, sessions | No |

### 4.2 Production-ready

- Foreign keys on tenant-owned resources
- Phase 6 performance indexes on high-traffic org-scoped columns
- JSON columns for AI input/output, audit metadata, notification data
- Timestamps on all domain tables

### 4.3 Risks

| Risk | Severity | Detail |
|------|----------|--------|
| No global tenant scope | **High** | Missed `organization_id` filter in new code → data leak |
| Manual query discipline | **High** | 54 models; no `BelongsToOrganization` trait enforcement |
| AI input persisted | **Medium** | PII/decision-support data in DB; retention policy needed |
| No soft deletes | **Low** | Hard deletes only; audit trail compensates partially |
| Migration immutability | **Ops** | Never rewrite historical migrations |

---

## 5. Authentication & Authorization

### 5.1 Authentication flow

```
Client → POST /api/v1/auth/login → Sanctum token
       → Authorization: Bearer {token}
       → X-Organization-Id: {org_id}
       → ResolveOrganizationContext (403 if not member)
       → Controller authorizePermission()
```

### 5.2 Current RBAC model

| Layer | Implementation |
|-------|----------------|
| Org membership | `organization_user` pivot (`admin` / `member`) |
| Permission resolution | Pivot baseline OR org-scoped role permissions |
| Admin baseline | `*` (all 17 permissions) |
| Member baseline | Module view + `ai.use` + `business.view` (no `access.manage`) |
| Explicit roles | `roles` + `permissions` + `role_user` + `role_permission` |

### 5.3 Gaps vs enterprise RBAC (Phase 11 target)

| Target role | Current equivalent | Gap |
|-------------|-------------------|-----|
| Super Admin | None (platform-wide) | No platform-level admin |
| Organization Owner | `admin` pivot | No ownership transfer, billing tie-in |
| Organization Admin | `admin` pivot + `access.manage` | Not distinguished from Owner |
| Manager | None | No module-scoped manage without full admin |
| Member | `member` pivot | Exists |
| Viewer | Test-only via explicit role | No seeded viewer baseline |

### 5.4 Security risks

| Risk | Severity |
|------|----------|
| Token non-expiration (default) | Medium |
| `ALLOW_REGISTRATION=true` misconfiguration | Medium |
| Unused policies may diverge from PermissionService | Medium |
| Bearer tokens in localStorage / SharedPreferences | Medium |
| No per-org rate limits | Low–Medium |
| `access.manage` concentration | Medium |

---

## 6. AI Platform

### 6.1 Current architecture

```
AiController
  → AiService
    → AiProviderInterface (MockAiProvider only)
    → AiRequestValidator / AiResponseNormalizer
    → AuditService (dispatched, processing, completed, failed)
    → sync: run() inline
    → async: dispatchForProcessing() → ProcessAiRequest → processRecord()
```

### 6.2 Production-ready

- Provider abstraction via interface + DI binding
- Async dispatch opt-in via `AI_ASYNC_DISPATCH`
- Idempotent processing with row locks
- Unique job constraint prevents duplicate processing
- AI input hidden from API responses
- Decision-support disclaimer on provider endpoint
- GET `/api/v1/ai/requests/{id}` polling endpoint

### 6.3 Missing

- Real provider implementations
- Usage tracking / quotas per organization
- `cancelled` status
- Provider configuration per organization
- AI completion notifications
- Cost/token billing integration

---

## 7. Queue & Redis

### 7.1 Configuration

| Setting | Staging | Tests |
|---------|---------|-------|
| `QUEUE_CONNECTION` | redis | sync |
| `CACHE_STORE` | redis | array |
| `SESSION_DRIVER` | redis | array |

### 7.2 Docker queue service

- Command: `php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90 --max-time=3600`
- Depends on: postgres (healthy), redis (healthy)
- Entrypoint: `wait_for_redis()` before `queue:work` (Phase 10 fix)
- Restart policy: `unless-stopped`

### 7.3 Gaps

- No Laravel Horizon / supervisor config in repo
- No scheduler/cron service in Compose
- No queue healthcheck on queue container
- `after_commit => false` on queue connections

---

## 8. Frontend (React 19)

### 8.1 Structure

```
frontend/src/
├── api/           # Modular client (Phase 10): client, auth, dashboard, ai, modules, users
├── context/       # AuthContext
├── components/    # AppShell, OrgSwitcher, RecordForm, ConfirmDialog
└── pages/         # Dashboard, ModulePage (farm/crop/soil/business), Diagnosis, Training, Library, AI, Login
```

### 8.2 Production-ready

- Protected routes with session guard
- Org switcher with `X-Organization-Id` propagation
- Modular API layer with retry, 401 handling, envelope unwrapping
- SPA routing via Nginx `try_files`
- Docker multi-stage build

### 8.3 Gaps

- No enterprise admin pages (users, roles, permissions, audit logs) despite API existing
- No async AI polling in `AiPage`
- No teams, billing, usage, notifications UI
- No frontend tests in CI
- Monolithic page components; limited shared UI library

---

## 9. Mobile (Flutter)

### 9.1 Structure

```
mobile/lib/
├── api/api_client.dart    # Monolithic HTTP client (~270 lines)
├── api/models.dart        # Typed parsers (unused by client)
├── screens/               # Dashboard, modules, features
└── widgets/               # org_switcher, record_form, async_state
```

### 9.2 Gaps

- No clean architecture layers (data/domain/presentation)
- Typed models not integrated into API client
- Demo credentials pre-filled in login screen
- SharedPreferences for tokens (not Keychain/Keystore)
- No async AI polling
- No notifications, profile, settings screens
- Platform projects generated in CI (`flutter create .`)

---

## 10. Infrastructure & CI/CD

### 10.1 Docker Compose services

| Service | Purpose | Healthcheck |
|---------|---------|-------------|
| postgres | Database | Yes |
| redis | Cache/queue/sessions | Yes |
| backend | PHP-FPM | No |
| queue | Redis queue worker | No |
| frontend | Nginx SPA | No |
| nginx | Gateway :8081 | No |
| backend-test | Isolated PHPUnit (profile) | N/A |

### 10.2 CI pipeline (`.github/workflows/ci.yml`)

| Job | Validates |
|-----|-----------|
| backend | PHPUnit 84 tests, isolated `wsa_enterprise_test` DB |
| frontend | oxlint + production build |
| mobile | flutter analyze + test |
| openapi | swagger-cli validate |

### 10.3 Gaps

- No deploy workflow
- No Docker Compose integration test in CI
- No OpenAPI contract tests against live routes
- No dependency vulnerability scanning
- Stale Laravel template workflows in `backend/.github/workflows/`

---

## 11. OpenAPI Coverage

**Documented (~22 route groups):** health, auth, user, dashboard, platform, users/roles, projects, tasks, farm CRUD, diagnosis requests, audit logs, roles/permissions, AI.

**Not documented:** crop, soil, directory, catalog, inventory, purchase/sales orders, invoices, reports, notifications, training, library (search + CRUD), diagnosis taxonomy CRUD.

**Coverage estimate:** ~35% of API surface.

---

## 12. Test Coverage Summary

| Category | Tests | Status |
|----------|-------|--------|
| Auth & access | 6+ | Strong |
| Cross-tenant security | 4+ | Strong (Phase 10) |
| Async AI workflow | 5+ | Strong (Phase 10) |
| Agricultural modules | 10+ | Strong |
| Business modules | 3+ | Adequate |
| Audit expansion | 3+ | Adequate |
| Queue foundation | 4+ | Strong |
| PermissionService unit | 0 | **Missing** |
| Policy unit | 0 | **Missing** |
| Rate limiting | 0 | **Missing** |
| Billing | 0 | N/A (not built) |
| Frontend E2E | 0 | **Missing** |

---

## 13. Technical Debt Register

| ID | Item | Impact | Phase 11 action |
|----|------|--------|-----------------|
| TD-01 | No global Eloquent tenant scope | High | Introduce `BelongsToOrganization` trait + optional global scope |
| TD-02 | Dual authz patterns (policies vs PermissionService) | Medium | Consolidate on one pattern; extend policies |
| TD-03 | Permission cache invalidation gaps | Medium | Invalidate on all RBAC mutations |
| TD-04 | Partial OpenAPI | Medium | Expand incrementally with contract tests |
| TD-05 | Stale architecture doc sections | Low | Update in Phase 11 docs pass |
| TD-06 | Orphaned Flutter models.dart | Low | Integrate into API layer |
| TD-07 | Mock-only AI provider | High | Add real provider behind interface |
| TD-08 | No frontend E2E | Medium | Add Playwright/Cypress in later milestone |
| TD-09 | backend/.github stale workflows | Low | Archive or remove |
| TD-10 | AppNotification unused pipeline | Medium | Build notification architecture |

---

## 14. Risk Assessment Summary

| Category | Level | Top risks |
|----------|-------|-----------|
| **Security** | Medium | No global tenant scope; token expiry optional; bearer in localStorage |
| **Data integrity** | Medium | Manual org scoping; permission cache staleness |
| **Scalability** | Medium | Single-node Docker; no horizontal scaling validation |
| **API consistency** | Low–Medium | Mixed envelope/raw responses; partial OpenAPI |
| **Operations** | Medium | No TLS, no deploy pipeline, no queue monitoring |
| **Compliance** | Medium | AI input retention; incomplete audit coverage |

---

## 15. What Must NOT Be Changed (Phase 11 constraint)

1. Existing `/api/v1` endpoints and response shapes (additive only)
2. Historical database migrations
3. `X-Organization-Id` tenant header contract
4. Sanctum bearer authentication model
5. Module-dispatch controller pattern
6. `AiProviderInterface` abstraction
7. Isolated test database strategy
8. Staging seed data (`admin@wsa.test` organization and demo records)
9. Nginx gateway routing split
10. Permission string catalog (extend, do not rename without migration)

---

## 16. Conclusion

Phase 10 provides a **solid, tested foundation**. Phase 11 should **extend and harden** — not rewrite. The highest-value work is:

1. **Centralized tenant enforcement** (trait, scope, service-layer guards)
2. **Enterprise RBAC** (role taxonomy, policies, least privilege)
3. **AI platform maturity** (real providers, quotas, usage tracking)
4. **Enterprise dashboard** (admin modules, async AI UI)
5. **Billing & notifications foundations** (domain models, no payment provider yet)
6. **Observability & audit expansion**
7. **API platform completeness** (OpenAPI, rate limits, envelopes)
8. **Production hardening** (healthchecks, deploy pipeline, docs refresh)

**Next document:** [phase-11-architecture.md](./phase-11-architecture.md)  
**Execution plan:** [phase-11-roadmap.md](./phase-11-roadmap.md)
