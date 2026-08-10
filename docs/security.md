# WSA-Enterprise Security Model

**Last updated:** Phase 11 (2026-08-10)

## Overview

WSA-Enterprise is a multi-tenant agricultural platform. Security is enforced at authentication, organization scoping, and permission layers.

## Authentication

- **User mechanism:** Laravel Sanctum personal access tokens (`Authorization: Bearer {token}`)
- **API client mechanism (M10):** Organization-registered clients via `Authorization: Bearer {client_id}:{client_secret}` or HTTP Basic
- **API client scopes:** `analytics.read`, `ai.read`, `billing.read` mapped to permission checks
- **API client allowlist:** GET `/analytics/overview`, `/ai/usage`, `/billing/usage` only
- **Login/register throttle:** 20 requests/minute per IP
- **Authenticated API throttle:** 120 requests/minute
- **Registration:** Disabled by default (`ALLOW_REGISTRATION=false`)
- **Token expiry:** Optional via `SANCTUM_TOKEN_EXPIRATION` (minutes)
- **Logout:** Deletes current access token (204 No Content)

## Authorization

### Organization membership

Every authenticated API request (except platform org list) requires:

1. Valid Sanctum token
2. `X-Organization-Id` header matching an organization the user belongs to

Cross-tenant header misuse returns **403 Forbidden**.

### Permissions

`PermissionService` resolves permissions per user + organization:

| Pivot role | Permissions |
| --- | --- |
| `admin` | `*` (all) |
| `member` | Baseline module permissions (farm, crop, soil, diagnosis, training, library, ai, business, platform.view) |

Explicit roles assigned via Access API replace baseline when present.

### Enterprise roles (Phase 11 — implemented)

System roles seeded per organization with slugs mapped to existing permission strings:

| Role slug | Permissions |
| --- | --- |
| `owner` | `*` (all) |
| `admin` | `*` (all) |
| `manager` | Module view/manage + `ai.use` (no `access.manage`) |
| `member` | Module view/manage baseline (existing member pivot) |
| `viewer` | Read-only module view permissions |

Seeder: `EnterpriseRoleService::seedForOrganization()`. Demo admin receives `owner` role.

Privileged role assignment (`owner`, `admin` slugs) requires elevated authorization via `EnterpriseRoleService::canAssignRole()`.

### Entitlements (Phase 11)

When `BILLING_ENABLED=true`, `EntitlementService` gates features by subscription plan. When disabled, all features allowed (current behavior).

### Policies & controllers

- `AuthorizesOrganizationAccess` concern on module controllers
- `OrganizationScopeValidator` on FK references in write payloads
- Resource lookups scoped by `organization_id` or `whereHas` through org-owned relations

## Tenant isolation

See [multi-tenancy.md](./multi-tenancy.md) for full tenant model.

| Scenario | Expected response |
| --- | --- |
| Foreign `X-Organization-Id` header | 403 |
| Foreign resource ID (scoped lookup) | 404 |
| Foreign role assignment | 422 validation error |
| Cross-org task update | 404 |

Phase 11 adds `BelongsToOrganization` trait for automatic query scoping when `TenantContext` is active (defense-in-depth).

## IDOR & mass assignment

- Route model binding replaced with explicit org-scoped queries where needed (e.g. tasks)
- `$request->validate()` on all write endpoints
- User profile responses exclude password hash and hidden attributes

## Transport & headers

- **CORS:** Configure `config/cors.php` for production origins
- **CSRF:** Relevant for stateful Sanctum SPA mode; API token mode uses Bearer auth
- **HTTPS:** Required in production; terminate at reverse proxy

## Logging

`LogApiRequests` middleware logs:

- HTTP method, path, status code
- Authenticated user ID
- Organization header value

Does **not** log request bodies, tokens, or passwords.

## Request tracing (Phase 11)

All API responses include `X-Request-Id` (client may supply or server generates UUID). Request ID is included in API logs and security audit metadata.

## Permission cache invalidation (Phase 11)

`PermissionCacheInvalidator` clears cached permissions when:

- Users are created in an organization
- Roles or permissions are created/updated
- Roles are assigned to users

Cache key format: `user_permissions:{userId}:{organizationId}` (60s TTL).

## AI rate limiting (Phase 11)

AI endpoints use per-organization rate limiting (`throttle:ai-org`). Default: 30 requests/minute per organization (`AI_RATE_LIMIT_PER_MINUTE`).

## Audit logging

Sensitive fields redacted via `AuditService::SENSITIVE_KEYS` (passwords, tokens, secrets).

Audited events include auth, user creation, role assignment, AI lifecycle. Phase 11 expands to org changes, team changes, billing changes, and security events.

**Never store passwords, tokens, or secrets in audit log metadata.**

## AI security

- AI input hidden from API responses; persisted in DB — review retention for production
- AI endpoints: 30 req/min throttle; requires `ai.use` permission
- Async AI requires explicit `AI_ASYNC_DISPATCH=true` and running queue worker
- See [ai-platform.md](./ai-platform.md)

## Production checklist

- [ ] `APP_DEBUG=false`
- [ ] Strong unique `APP_KEY`
- [ ] `SESSION_ENCRYPT=true` if using cookie sessions
- [ ] `ALLOW_REGISTRATION=false` unless intentionally open
- [ ] Restrict CORS to known frontend origins
- [ ] Rotate demo/seeded credentials
- [ ] Configure token expiration for mobile clients
- [ ] Set `AI_ASYNC_DISPATCH` explicitly for each environment
- [ ] Review rate limits under expected load
- [ ] Enable `BILLING_ENABLED` only after entitlement testing
- [ ] Never commit `.env` files

## Automated security tests

| Test class | Coverage |
| --- | --- |
| `AuthAccessTest` | Login, org access |
| `Phase7E2EWorkflowTest` | Cross-tenant 403 |
| `Phase8SecurityTest` | Registration gate, task IDOR, role scoping, pagination, profile sanitization |
| `Phase8ComprehensiveWorkflowTest` | Full workflow + logout invalidation |
| `Phase10TenantSecurityTest` | Cross-tenant audit logs, AI requests |
| `Phase10AsyncAiTest` | Async AI lifecycle, idempotency, tenant scope |
| `Phase11TenantScopeTest` | Global tenant scope, cross-tenant AI/notifications |
| `Phase11RbacTest` | Enterprise role matrix (owner/admin/manager/viewer) |
| `Phase11PrivilegeEscalationTest` | Owner role assignment restrictions |
| `Phase11IdorTest` | Team IDOR prevention |
| `Phase11TeamAuthorizationTest` | Team CRUD authorization + audit |
| `Phase11RequestIdTest` | X-Request-Id header + cross-tenant audit |
| `Phase11AiRateLimitTest` | Per-organization AI throttling |
| `EnterpriseRoleServiceTest` | Role assignment rules (unit) |

## Reporting vulnerabilities

Document internal security contact/process before public production deployment.
