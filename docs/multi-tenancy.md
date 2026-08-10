# Multi-Tenancy

**Last updated:** Phase 11 (2026-08-10)

## Overview

WSA-Enterprise is a **multi-tenant SaaS platform**. Each tenant is an **Organization**. Users may belong to multiple organizations. All tenant-owned data is scoped by `organization_id`.

---

## Tenant Model

```
User ←→ organization_user (pivot: role) ←→ Organization
                                              ↓
                                    organization_id on all domain tables
```

### Organization membership

- Users join organizations via `organization_user` pivot
- Pivot roles: `admin`, `member` (baseline permissions)
- Explicit org-scoped roles override pivot baselines when assigned

---

## Tenant Resolution

### Client responsibility

Every authenticated API request (except platform org list) must include:

```
Authorization: Bearer {sanctum_token}
X-Organization-Id: {organization_id}
```

Web and mobile clients store the active org ID in:

- Web: `localStorage` key `wsa_organization_id`
- Mobile: `SharedPreferences` key `wsa_organization_id`

### Server resolution

1. **`ResolveOrganizationContext` middleware** (all API routes)
   - Validates user is member of header org → **403** if not
   - Sets `organization_id` on request attributes
   - Falls back to user's first org if header omitted

2. **Controller layer**
   - `AuthorizesOrganizationAccess` concern
   - `organization($request)` returns resolved org ID

3. **Service layer** (Phase 11)
   - `TenantContext` service injects org ID into domain operations

4. **Model layer** (Phase 11)
   - `BelongsToOrganization` trait applies global scope and auto-sets org on create

---

## Isolation Rules

| Scenario | HTTP response | Rationale |
|----------|---------------|-----------|
| Foreign `X-Organization-Id` header | **403 Forbidden** | User not member of org |
| Foreign resource ID (scoped query) | **404 Not Found** | Prevent ID enumeration |
| Foreign FK in write payload | **422 Validation Error** | `OrganizationScopeValidator` |
| Cross-org role assignment | **422 Validation Error** | Access controller validation |

---

## Tenant-Owned Resources

All tables with `organization_id`:

- Platform: projects, tasks
- Access: roles, permissions, audit_logs
- Business: companies, customers, products, warehouses, orders, invoices
- Agricultural: farms, crops, soil records
- Decision support: diagnosis, training, library
- AI: ai_requests
- Notifications: app_notifications
- Phase 11: teams, usage_records, subscriptions, entitlements

**Users** are global (not org-scoped). Membership is via pivot.

---

## Query Discipline

### Current pattern (Phase 10)

```php
AiRequest::where('organization_id', $this->organization($request))->findOrFail($id);
```

### Target pattern (Phase 11)

```php
// Trait auto-scopes when TenantContext is set
AiRequest::findOrFail($id); // implicitly scoped to active org
```

**Rule:** Never accept `organization_id` from request body for authorization purposes.

---

## Cross-Tenant Testing

Regression tests in:

- `Phase10TenantSecurityTest`
- `Phase11TenantScopeTest` (planned)
- Module-specific tests in agricultural, business, AI suites

Run isolated tests only:

```bash
docker compose --profile test run --rm backend-test
```

**Never** run PHPUnit against `wsa_enterprise` staging database.

---

## Teams (Phase 11)

Teams are **sub-groups within an organization**:

```
Organization → Team → team_user → User
```

Teams do not create separate tenant boundaries. All team data remains org-scoped. Teams enable finer-grained access within an org (future).

---

## Super Admin (Phase 11)

Platform-level super admin is **environment-gated** (`SUPER_ADMIN_ENABLED=false` by default). When enabled, designated users can access cross-org operations for internal operations only. Not exposed in default staging/demo.

---

## Related Documents

- [security.md](./security.md)
- [phase-11-architecture.md](./phase-11-architecture.md)
- [testing.md](./testing.md)
