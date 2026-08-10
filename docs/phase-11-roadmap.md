# Phase 11 — Roadmap

**Branch:** `phase-11-enterprise-expansion`  
**Baseline:** Phase 10 merged to `main`  
**Status:** Milestone 1 complete (audit + architecture) — awaiting approval

---

## Milestone Overview

| # | Milestone | Scope | Depends on |
|---|-----------|-------|------------|
| M1 | Architecture audit + target architecture | Docs only | — |
| M2 | Multi-tenancy + RBAC + security | Backend foundation | M1 ✓ |
| M3 | AI platform | Providers, quotas, usage | M2 |
| M4 | Enterprise dashboard | React feature modules | M2, M3 |
| M5 | Flutter platform | Mobile architecture alignment | M2, M3 |
| M6 | Billing + entitlements | Domain + read APIs | M2 |
| M7 | Notifications + audit + observability | Queue pipeline + audit expansion | M2, M3 |
| M8 | API + testing + CI/CD + production hardening | OpenAPI, healthchecks, deploy docs | All |
| M9 | Final integration + production readiness | Cross-module E2E, security review, final docs | M8 ✓ |

**Each milestone must:** compile, pass tests, avoid regressions, commit separately with clear message.

---

## Milestone 1 — Architecture Audit + Target Architecture ✅

**Deliverables:**

- [x] `docs/phase-11-architecture-audit.md`
- [x] `docs/phase-11-architecture.md`
- [x] `docs/phase-11-roadmap.md`
- [x] Supporting docs updated (security, multi-tenancy, ai-platform, billing, deployment)

**Exit criteria:** Audit approved; no code changes.

---

## Milestone 2 — Multi-Tenancy + RBAC + Security

### Backend

- [ ] `BelongsToOrganization` trait with optional global scope
- [ ] `TenantContext` service — central org resolution for services
- [ ] Enterprise role seeder (Owner, Admin, Manager, Member, Viewer slugs)
- [ ] Extend `PermissionService` — cache invalidation on all RBAC mutations
- [ ] Consolidate authorization: extend Policies to match `PermissionService` checks
- [ ] `EntitlementService` foundation (reads plan features; stub allows all until M6)
- [ ] Expand cross-tenant tests for: tasks, notifications, commerce, training, library

### Database

- [ ] Migration: `teams`, `team_user`
- [ ] Migration: `organization_settings`
- [ ] Migration: add `slug` to roles (if not present) + system role seeds

### Tests

- [ ] `Phase11TenantScopeTest` — global scope prevents cross-org reads
- [ ] `Phase11RbacTest` — enterprise role permission matrix
- [ ] `Phase11PrivilegeEscalationTest` — member cannot assign admin roles
- [ ] `Phase11IdorTest` — resource ID enumeration blocked

**Commit message example:** `Strengthen multi-tenant scoping and enterprise RBAC foundation.`

---

## Milestone 3 — AI Platform

### Backend

- [ ] `AiProviderResolver` — config + org override
- [ ] `AiQuotaService` + `AiUsageRecorder`
- [ ] `cancelled` status support on `AiRequest`
- [ ] Usage API: `GET /api/v1/ai/usage`
- [ ] Quota enforcement before dispatch (429 when exceeded)
- [ ] Optional: first real provider stub (config-gated, not default)

### Database

- [ ] Migration: `usage_records` (org_id, metric, quantity, period)
- [ ] Migration: add `cancelled_at` to `ai_requests` (nullable)

### Tests

- [ ] `Phase11AiQuotaTest`
- [ ] `Phase11AiCancellationTest`
- [ ] Extend `Phase10AsyncAiTest` for quota + usage recording

**Commit message example:** `Extend AI platform with quotas, usage tracking, and cancellation.`

---

## Milestone 4 — Enterprise Dashboard

### Frontend

- [ ] Extract routes from `App.tsx` into `routes/`
- [ ] Feature modules: `features/access/` (users, roles, permissions, audit logs)
- [ ] Feature modules: `features/ai/` with async polling
- [ ] Feature modules: `features/teams/`, `features/settings/`
- [ ] Shared UI: `DataTable`, `Pagination`, `EmptyState`, `ErrorBanner`
- [ ] `usePermission` hook for permission-aware UI
- [ ] API modules: `teams.ts`, `analytics.ts`

### Tests

- [ ] TypeScript build + lint (existing CI)
- [ ] Optional: Vitest for `usePermission` hook

**Commit message example:** `Add enterprise dashboard modules for access management and async AI.`

---

## Milestone 5 — Flutter Platform

### Mobile

- [ ] Restructure: `data/`, `domain/`, `presentation/`
- [ ] Split `api_client.dart` by domain
- [ ] Integrate `models.dart` into data layer
- [ ] AI request + polling use case
- [ ] Notifications screen
- [ ] Profile + settings screens
- [ ] Remove hardcoded demo credentials from UI
- [ ] `TokenStorage` abstraction interface

### Tests

- [ ] Unit tests for typed model parsers
- [ ] Unit tests for AI polling use case
- [ ] Widget test for login screen (no hardcoded creds)

**Commit message example:** `Align Flutter architecture with backend API and typed models.`

---

## Milestone 6 — Billing + Entitlements

### Backend

- [ ] Domain models: `Plan`, `PlanFeature`, `Subscription`, `Entitlement`
- [ ] Migrations for billing tables
- [ ] `BillingProviderInterface` + `NullBillingProvider` (no-op)
- [ ] Seed default plans (Free, Pro, Enterprise — feature flags only)
- [ ] Read APIs: plans catalog, org subscription, usage summary
- [ ] Wire `EntitlementService` to subscription data

### Tests

- [ ] `Phase11BillingTest` — plan seeding, entitlement resolution
- [ ] `Phase11EntitlementEnforcementTest` — feature gated by plan

**Commit message example:** `Add provider-independent billing domain and entitlement checks.`

---

## Milestone 7 — Notifications + Audit + Observability

### Backend

- [ ] `NotificationService` + `SendNotificationJob`
- [ ] AI completion/failure notifications
- [ ] Security notification foundation
- [ ] Expand audit coverage: org changes, team changes, billing changes
- [ ] `X-Request-Id` middleware
- [ ] Enhanced `LogApiRequests` with request ID

### Database

- [ ] Migration: `notification_deliveries`

### Frontend

- [ ] Notifications feature module (in-app list, mark read)

### Tests

- [ ] `Phase11NotificationTest`
- [ ] `Phase11AuditCoverageTest`

**Commit message example:** `Add notification pipeline and expand enterprise audit coverage.`

---

## Milestone 8 — API Platform + Testing + CI/CD + Production

### API

- [ ] Expand OpenAPI for new Phase 11 endpoints
- [ ] OpenAPI contract test job in CI (subset)
- [ ] Per-org AI rate limiting
- [ ] API client registry foundation (`api_clients` table)

### Infrastructure

- [ ] Docker healthchecks: backend, queue, frontend, nginx
- [ ] Scheduler service in Compose
- [ ] Production compose override documentation

### CI/CD

- [ ] Security test job or tagged PHPUnit group
- [ ] Archive stale `backend/.github/workflows/`
- [ ] Optional: deploy workflow stub (build images)

### Documentation

- [ ] Update `README.md` Phase 11 section
- [ ] Refresh architecture v1 stale sections
- [ ] Final Phase 11 verification report

**Commit message example:** `Harden CI/CD, expand OpenAPI, and add production healthchecks.`

---

## Milestone 9 — Final Integration + Production Readiness ✅

### Integration

- [x] Cross-module workflow test (auth → billing → AI → notifications → audit → analytics → API clients)
- [x] Analytics foreign-org isolation regression
- [x] Billing entitlement audit regression

### API / OpenAPI

- [x] OpenAPI ↔ route parity test for documented paths
- [x] Align billing plan assignment schema (`plan_slug`)

### Documentation

- [x] `docs/phase-11-final-verification.md`
- [x] Refresh `production-readiness.md` and `e2e-testing.md`

**Commit message example:** `Complete Phase 11 final integration and production readiness verification.`

---

## Database Changes Summary

| Migration | Milestone | Tables/Columns |
|-----------|-----------|----------------|
| Teams | M2 | `teams`, `team_user` |
| Org settings | M2 | `organization_settings` |
| Role slugs | M2 | `roles.slug`, system role seeds |
| Usage records | M3 | `usage_records` |
| AI cancellation | M3 | `ai_requests.cancelled_at` |
| Billing domain | M6 | `plans`, `plan_features`, `subscriptions`, `entitlements` |
| Notifications | M7 | `notification_deliveries` |
| API clients | M8 | `api_clients` |

**No modifications to existing migrations.**

---

## API Changes Summary

| Endpoint group | Milestone | Breaking? |
|----------------|-----------|-----------|
| `/api/v1/teams` | M2 | No (new) |
| `/api/v1/organizations/{id}/settings` | M2 | No (new) |
| `/api/v1/ai/usage` | M3 | No (new) |
| AI cancel action | M3 | No (new) |
| `/api/v1/billing/*` | M6 | No (new) |
| `/api/v1/notifications` (enhanced) | M7 | No (extends existing) |
| `/api/v1/analytics/overview` | M8 | No (new) |

---

## Testing Strategy

### Per milestone gate

1. `docker compose --profile test run --rm backend-test` — 84+ tests pass
2. `npm run lint && npm run build` — frontend clean
3. `flutter analyze && flutter test` — mobile clean
4. `swagger-cli validate docs/openapi.yaml` — OpenAPI valid
5. Manual smoke: health, login (API), org switch, one CRUD module

### Security test matrix (M2 + M8)

| Test | Description |
|------|-------------|
| Cross-tenant read | Foreign org header → 403; foreign resource → 404 |
| Cross-tenant write | Foreign org FK → 422 |
| Privilege escalation | Member cannot grant admin |
| IDOR | Sequential ID guessing blocked |
| Rate limit | AI endpoint throttled per org |

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Breaking existing clients | Additive API only; regression test suite |
| Tenant data leak | BelongsToOrganization trait + expanded cross-tenant tests |
| RBAC migration confusion | Seed system roles; preserve pivot baselines |
| Billing scope creep | Interface only; no payment provider until approved |
| Frontend rewrite | Feature modules alongside existing pages |
| CI regression | Milestone commits; never weaken test DB guard |

---

## Approval Checkpoint

**Before Milestone 2 begins, confirm:**

1. Target architecture approved
2. Enterprise role taxonomy approved
3. Billing scope (domain-only, no Stripe) approved
4. Team entity scope approved
5. Milestone sequencing approved

**After approval:** Begin M2 on `phase-11-enterprise-expansion` with first implementation commit.
