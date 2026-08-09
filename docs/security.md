# WSA-Enterprise Security Model

**Last updated:** Phase 8 (2026-08-09)

## Overview

WSA-Enterprise is a multi-tenant agricultural platform. Security is enforced at authentication, organization scoping, and permission layers.

## Authentication

- **Mechanism:** Laravel Sanctum personal access tokens (Bearer)
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

### Policies & controllers

- `AuthorizesOrganizationAccess` concern on module controllers
- `OrganizationScopeValidator` on FK references in write payloads
- Resource lookups scoped by `organization_id` or `whereHas` through org-owned relations

## Tenant isolation

| Scenario | Expected response |
| --- | --- |
| Foreign `X-Organization-Id` header | 403 |
| Foreign resource ID (scoped lookup) | 404 |
| Foreign role assignment | 422 validation error |
| Cross-org task update | 404 |

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

## Production checklist

- [ ] `APP_DEBUG=false`
- [ ] Strong unique `APP_KEY`
- [ ] `SESSION_ENCRYPT=true` if using cookie sessions
- [ ] `ALLOW_REGISTRATION=false` unless intentionally open
- [ ] Restrict CORS to known frontend origins
- [ ] Rotate demo/seeded credentials
- [ ] Configure token expiration for mobile clients
- [ ] Review rate limits under expected load

## Automated security tests

| Test class | Coverage |
| --- | --- |
| `AuthAccessTest` | Login, org access |
| `Phase7E2EWorkflowTest` | Cross-tenant 403 |
| `Phase8SecurityTest` | Registration gate, task IDOR, role scoping, pagination, profile sanitization |
| `Phase8ComprehensiveWorkflowTest` | Full workflow + logout invalidation |

## Reporting vulnerabilities

Document internal security contact/process before public production deployment.
