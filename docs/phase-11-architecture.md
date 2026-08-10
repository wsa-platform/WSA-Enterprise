# Phase 11 — Target Architecture

**Project:** WSA-Enterprise — Enterprise Platform Expansion  
**Baseline:** Phase 10 (merged to `main`)  
**Version:** 11.0  
**Status:** M1–M8 implemented on `main` (see [phase-11-verification-report.md](phase-11-verification-report.md))

---

## 1. Vision

Evolve the working Phase 10 foundation into a **production-grade, scalable, secure, multi-tenant enterprise platform** supporting web and mobile clients, async AI services, billing entitlements, notifications, and enterprise observability — **without breaking existing functionality**.

---

## 2. Architectural Principles

1. **Incremental evolution** — extend Phase 10; do not rewrite
2. **Security by default** — server-side authorization on every mutation
3. **Tenant isolation first** — no resource accessible without org validation
4. **Provider abstraction** — AI and billing behind interfaces
5. **Backward compatibility** — `/api/v1` contracts preserved; additive changes only
6. **Test-driven hardening** — every security/tenant feature has regression tests
7. **Environment explicitness** — production-safe defaults; no accidental async/billing flags

---

## 3. Component Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLIENTS                                        │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐       │
│  │ React 19 Web     │  │ Flutter Mobile   │  │ API Clients      │       │
│  │ (Enterprise UI)  │  │ (Field + AI)     │  │ (Future M2M)     │       │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘       │
└───────────┼─────────────────────┼─────────────────────┼─────────────────┘
            │ Bearer + X-Org-Id   │                     │
            ▼                     ▼                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     NGINX GATEWAY (:443 / :8081)                         │
│              /api/* → Laravel    /  → React SPA                          │
└───────────────────────────────┬─────────────────────────────────────────┘
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     LARAVEL 12 API (/api/v1)                             │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │
│  │ Auth Layer  │ │ Tenant Layer│ │ Authz Layer │ │ API Layer   │        │
│  │ Sanctum     │ │ OrgContext  │ │ Policies +  │ │ Controllers │        │
│  │             │ │ Resolver    │ │ Permissions │ │ Resources   │        │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘        │
│  ┌─────────────────────────────────────────────────────────────┐        │
│  │                     DOMAIN SERVICES                          │        │
│  │  Organization │ RBAC │ AI │ Billing │ Notifications │ Audit │        │
│  └─────────────────────────────────────────────────────────────┘        │
└───────────┬─────────────────────┬─────────────────────┬─────────────────┘
            ▼                     ▼                     ▼
┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐
│ PostgreSQL        │  │ Redis             │  │ Queue Workers     │
│ (tenant data)     │  │ cache/sessions/   │  │ AI, notifications │
│                   │  │ queue             │  │ billing events    │
└───────────────────┘  └───────────────────┘  └───────────────────┘
```

---

## 4. Domain Boundaries

| Domain | Responsibility | Phase 11 scope |
|--------|---------------|----------------|
| **Identity** | Users, authentication, sessions, API tokens | Extend token policies; no rewrite |
| **Tenancy** | Organizations, membership, org context | Centralize resolution; strengthen scoping |
| **Teams** | Sub-org groups (optional) | **New** — lightweight team entity |
| **Access** | Roles, permissions, RBAC | Enterprise role taxonomy |
| **Platform** | Projects, tasks, dashboard | Preserve; add usage metrics |
| **Agricultural** | Farm, crop, soil | Preserve unchanged |
| **Decision Support** | Diagnosis, training, library | Preserve unchanged |
| **AI** | Requests, providers, quotas, usage | Extend async platform |
| **Commerce** | Inventory, orders, invoices | Preserve unchanged |
| **Billing** | Plans, subscriptions, entitlements | **New** — provider-agnostic domain |
| **Notifications** | In-app, email foundation | **New** — queue-driven pipeline |
| **Audit** | Security & compliance events | Expand coverage |
| **Analytics** | Org usage, AI metrics, reports | **New** — read models |

**Rule:** Domains communicate through services and events — not direct cross-domain controller calls.

---

## 5. Database Boundaries

### 5.1 Existing schemas (preserve)

All Phase 10 tables remain. No destructive migrations.

### 5.2 New tables (Phase 11)

| Table | Purpose |
|-------|---------|
| `teams` | Organization sub-groups |
| `team_user` | Team membership |
| `organization_settings` | Org-level config (JSON) |
| `plans` | Billing plan definitions |
| `plan_features` | Feature flags per plan |
| `subscriptions` | Org subscription state |
| `usage_records` | Metered usage (AI tokens, requests) |
| `entitlements` | Resolved feature access per org |
| `notification_deliveries` | Notification dispatch tracking |
| `api_clients` | Future M2M client registry (foundation) |

### 5.3 Indexing strategy

- All new tenant tables: `(organization_id, created_at)` index minimum
- Usage/billing: `(organization_id, period_start)` for reporting
- Notifications: `(user_id, read_at, created_at)`

### 5.4 Soft deletes

Apply only where business requires recovery: `teams`, `subscriptions` (cancelled state preferred over delete).

---

## 6. API Boundaries

### 6.1 Versioning

- **Current:** `/api/v1` — all Phase 11 changes are additive
- **Future:** `/api/v2` only when breaking changes unavoidable; v1 maintained

### 6.2 New endpoint groups (planned)

```
/api/v1/teams                    # Team CRUD
/api/v1/organizations/{id}/settings
/api/v1/billing/plans            # Read-only plan catalog
/api/v1/billing/subscription     # Org subscription status
/api/v1/billing/usage            # Usage summary
/api/v1/notifications            # Enhanced in-app notifications
/api/v1/analytics/overview       # Org dashboard metrics
/api/v1/ai/usage                 # AI usage/quota status
```

### 6.3 Response standards

| Type | Format |
|------|--------|
| Legacy endpoints | Preserve existing raw/paginated Laravel JSON |
| New Phase 11 endpoints | `{ "data": ..., "meta": ... }` envelope via `ApiResponse` |
| Errors | `{ "message": "...", "errors": { field: [...] } }` |
| Async AI | 202 + `{ status: "pending" }`; poll GET for completion |

### 6.4 Rate limiting (planned)

| Tier | Limit |
|------|-------|
| Auth | 20/min/IP (existing) |
| API default | 120/min/user (existing) |
| AI endpoints | 30/min/org (upgrade from per-user) |
| Billing webhooks | N/A until provider integrated |

---

## 7. Authentication Flow

```
1. Client POST /api/v1/auth/login { email, password }
2. Server validates → Sanctum token (optional expiry via SANCTUM_TOKEN_EXPIRATION)
3. Client stores token (web: localStorage; mobile: secure storage target)
4. All requests:
   Authorization: Bearer {token}
   X-Organization-Id: {org_id}
5. ResolveOrganizationContext:
   - Validates membership → 403 if foreign org
   - Sets request attribute organization_id
6. Controller → authorize via Policy or PermissionService
7. Service layer → enforce tenant scope on all queries
```

**Phase 11 additions:**

- Token metadata endpoint (expiry, last used)
- Optional refresh token strategy (design only; implement if approved)
- API client credentials flow (foundation table only)

---

## 8. Authorization Flow

```
Request → ResolveOrganizationContext
        → Gate/Policy check (module action)
        → PermissionService.hasPermission(user, org, permission)
        → EntitlementService.canUseFeature(org, feature)  [NEW]
        → Service executes with TenantScope applied
```

### 8.1 Enterprise role mapping

| Role | Scope | Key permissions |
|------|-------|-----------------|
| **Super Admin** | Platform | All orgs (internal ops only; env-gated) |
| **Organization Owner** | Org | Full org control + billing + delete org |
| **Organization Admin** | Org | Users, roles, settings (no billing cancel) |
| **Manager** | Org/Team | Module manage permissions |
| **Member** | Org | Module view + create (existing baseline) |
| **Viewer** | Org | Read-only all modules |

**Implementation:** Extend existing `roles` table with `slug` + seeded system roles per org. Map to existing permission strings — no permission renames.

---

## 9. Tenant Isolation Strategy

### 9.1 Defense in depth (Phase 11)

| Layer | Mechanism |
|-------|-----------|
| **Middleware** | `ResolveOrganizationContext` — membership validation |
| **Controller** | `AuthorizesOrganizationAccess` — permission checks |
| **Service** | `TenantContext` service — resolved org ID injection |
| **Model** | `BelongsToOrganization` trait — auto-scope queries, auto-set on create |
| **Validator** | `OrganizationScopeValidator` — FK cross-tenant rejection |
| **Policy** | Resource-level authorize before show/update/delete |
| **Tests** | Cross-tenant regression suite expanded per module |

### 9.2 Rules

- Never trust client-supplied `organization_id` in request body
- Always derive org from middleware-resolved context
- Foreign resource lookup → **404** (not 403) to prevent enumeration
- Foreign org header → **403**

---

## 10. Queue Architecture

```
                    ┌─────────────────┐
                    │  Redis (queue)  │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        ▼                    ▼                    ▼
┌───────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ default queue │  │ notifications   │  │ billing         │
│ ProcessAiRequest│ │ SendNotification│  │ ProcessUsage    │
└───────────────┘  └─────────────────┘  └─────────────────┘
```

**Preserve:** Existing `ProcessAiRequest` job and Redis worker configuration.

**Add:**

- `notifications` queue for async notification delivery
- Job failure → audit log + notification to org admin (security events)
- Queue healthcheck in Docker Compose
- Optional: Laravel Horizon in production (document only)

**Environment control:**

- `AI_ASYNC_DISPATCH=false` (default) — sync AI for dev/simple deploys
- `AI_ASYNC_DISPATCH=true` — requires running queue worker

---

## 11. AI Architecture

```
AiController
  → AiService
    → AiProviderResolver (config + org override)     [NEW]
    → AiProviderInterface
      ├── MockAiProvider (existing)
      └── [Future: OpenAiProvider, AnthropicProvider]
    → AiQuotaService (org limits)                    [NEW]
    → AiUsageRecorder (tokens, latency)              [NEW]
    → AuditService
    → dispatchForProcessing() → ProcessAiRequest
```

### 11.1 Status lifecycle

```
pending → processing → completed
                    ↘ failed
                    ↘ cancelled  [NEW]
```

### 11.2 Provider rules

- Domain code never imports vendor SDKs directly
- Provider selected via config + optional org-level override
- All providers implement `AiProviderInterface::process(AiRequest): AiResult`
- Timeouts enforced at job level (`ProcessAiRequest::$timeout`)

### 11.3 Quotas (Phase 11 foundation)

- `usage_records` table tracks requests/tokens per org per period
- `EntitlementService` checks plan limits before dispatch
- Over-quota → 429 with clear error message

---

## 12. Notification Architecture

```
Event (AI completed, user invited, security alert)
  → NotificationService
    → Create AppNotification (in-app)
    → Dispatch SendNotificationJob (queue)
      → Email channel (foundation — log driver in dev)
      → Future: SMS, push
```

**Notification types:**

- `ai.request.completed` / `ai.request.failed`
- `security.login_new_device` (foundation)
- `system.maintenance`
- `billing.quota_warning` (when billing active)

---

## 13. Billing Architecture

**Provider-independent domain** — no Stripe integration in Phase 11 unless explicitly approved.

```
Plan (catalog)
  → PlanFeature (feature flags, limits)
    → Subscription (org state: trialing, active, past_due, cancelled)
      → Entitlement (resolved: can_use_ai, max_users, max_requests)
        → UsageRecord (metered consumption)
```

**Integration seam:**

```php
interface BillingProviderInterface {
    public function createSubscription(Organization $org, Plan $plan): SubscriptionResult;
    public function cancelSubscription(Subscription $sub): void;
    public function handleWebhook(array $payload): void;
}
```

**Phase 11 delivers:** Domain models, migrations, read APIs, entitlement checks — **not** live payment processing.

---

## 14. Frontend Architecture

### 14.1 Target structure

```
frontend/src/
├── api/                    # Existing modular client (extend)
│   ├── teams.ts            [NEW]
│   ├── billing.ts          [NEW]
│   ├── notifications.ts    [NEW]
│   └── analytics.ts        [NEW]
├── features/               [NEW — feature modules]
│   ├── dashboard/
│   ├── organizations/
│   ├── users/
│   ├── teams/
│   ├── access/             # roles, permissions, audit
│   ├── ai/
│   ├── notifications/
│   ├── billing/
│   └── settings/
├── components/ui/          [NEW — shared UI primitives]
├── hooks/                  [NEW — useAuth, useOrg, usePermission]
└── routes/                 # Route config extracted from App.tsx
```

### 14.2 UI requirements

- Permission-aware rendering (`usePermission('access.manage')`)
- Protected routes (existing `ProtectedShell` extended)
- Loading / error / empty states on all data pages
- Pagination + filtering on list views
- Async AI polling in AI feature module

**Rule:** No monolithic `App.tsx` growth — extract feature routes.

---

## 15. Mobile Architecture

### 15.1 Target layers

```
presentation/   → screens, widgets
domain/         → use cases (Login, FetchAiRequest, PollAiStatus)
data/           → api_client, models, local_storage
```

### 15.2 Phase 11 foundation

- Integrate `models.dart` into data layer
- Split `api_client.dart` by domain (mirror web `api/`)
- Secure token storage abstraction (interface now; Keychain/Keystore later)
- AI request creation + status polling
- Notifications list screen
- Profile + settings screens

**Rule:** No business logic in widgets — delegate to domain use cases.

---

## 16. Deployment Architecture

### 16.1 Environments

| Environment | Purpose | Key settings |
|-------------|---------|--------------|
| **local** | Developer machines | `APP_DEBUG=true`, mock AI |
| **staging** | Demo / QA | Docker Compose, seeded data |
| **production** | Live | TLS, secrets manager, real providers |

### 16.2 Production checklist additions (Phase 11)

- [ ] All services have healthchecks
- [ ] Queue worker monitored (restart policy + alerting)
- [ ] `AI_ASYNC_DISPATCH` explicitly set
- [ ] `SANCTUM_TOKEN_EXPIRATION` configured
- [ ] Postgres/Redis not host-exposed
- [ ] TLS termination at load balancer
- [ ] Isolated test DB never points to production

### 16.3 Docker Compose evolution

- Add healthchecks: backend (`/up`), queue (process check), frontend, nginx
- Add `scheduler` service: `php artisan schedule:run`
- Separate compose override for production (no bind mounts)

---

## 17. Observability & Audit

### 17.1 Audit events (expand coverage)

| Event | Trigger |
|-------|---------|
| `auth.login` / `auth.logout` | Existing |
| `user.created` / `user.updated` | Existing + expand |
| `role.assigned` / `permission.changed` | Existing + expand |
| `organization.updated` | **New** |
| `team.created` / `team.member_added` | **New** |
| `ai.request.dispatched/completed/failed` | Existing |
| `billing.subscription.changed` | **New** |
| `security.cross_tenant_attempt` | **New** |

**Never log:** passwords, tokens, API keys, full AI input payloads in audit metadata.

### 17.2 Request tracing (foundation)

- Add `X-Request-Id` response header
- Include request ID in `LogApiRequests` and audit metadata

---

## 18. Testing Architecture

| Layer | Tool | Scope |
|-------|------|-------|
| Backend unit | PHPUnit | Services, policies, entitlements |
| Backend feature | PHPUnit | API endpoints, cross-tenant, RBAC |
| Backend queue | PHPUnit | AI jobs, notification jobs |
| Security | PHPUnit | IDOR, privilege escalation, rate limits |
| Frontend | oxlint + tsc + build | Existing CI |
| Frontend component | Vitest (optional M6+) | Critical UI |
| Flutter | analyze + test | Models, API client |
| Contract | OpenAPI + PHPUnit | New endpoints |
| E2E | Manual + future Playwright | Login, org switch, AI flow |

**Invariant:** `FORBIDDEN_TEST_DATABASES=wsa_enterprise` — never weakened.

---

## 19. Migration Strategy

1. **Additive migrations only** — new tables/columns; no drops without deprecation period
2. **Feature flags via env** — billing, async AI, super admin
3. **Seed system roles** — migration + seeder for enterprise role slugs
4. **Backward-compatible API** — old clients continue working
5. **Incremental frontend** — new feature modules alongside existing pages

---

## 20. Related Documents

| Document | Purpose |
|----------|---------|
| [phase-11-architecture-audit.md](./phase-11-architecture-audit.md) | Current state audit |
| [phase-11-roadmap.md](./phase-11-roadmap.md) | Milestone execution plan |
| [multi-tenancy.md](./multi-tenancy.md) | Tenant isolation guide |
| [ai-platform.md](./ai-platform.md) | AI service architecture |
| [billing.md](./billing.md) | Billing domain design |
| [security.md](./security.md) | Security model (updated) |
| [deployment.md](./deployment.md) | Deployment guide (updated) |

---

**Status:** Awaiting approval before Milestone 2 implementation begins.
