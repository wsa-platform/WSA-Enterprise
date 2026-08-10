# Phase 11 — M10 Verification (API Client Authentication)

**Date:** 2026-08-10  
**Branch:** `phase-11-m10-api-client-authentication`  
**Merged to main:** `fecdc3b` via PR #12 (`f89fd8a`)  
**Scope:** Organization API client machine-to-machine (M2M) bearer authentication

---

## Milestone Status

| Item | Status |
| --- | --- |
| Bearer `{client_id}:{client_secret}` authentication | Complete |
| HTTP Basic credential support | Complete |
| Scope mapping (`analytics.read`, `ai.read`, `billing.read`) | Complete |
| Allowlisted read-only endpoints | Complete |
| Organization fixed to registered client | Complete |
| Sanctum user auth regression | Complete |
| OpenAPI + security documentation | Complete |

---

## Deliverables

| Item | Location |
| --- | --- |
| Principal auth middleware | `backend/app/Http/Middleware/AuthenticateApiPrincipal.php` |
| API client route allowlist | `backend/app/Http/Middleware/RestrictApiClientRoutes.php` |
| Credential verification | `backend/app/Services/Api/ApiClientAuthenticator.php` |
| Scope authorization | `backend/app/Services/Api/ApiClientAuthorizer.php` |
| Scope configuration | `backend/config/api_clients.php` |
| Route middleware ordering | `backend/bootstrap/app.php`, `backend/routes/api.php` |
| Controller scope checks | `backend/app/Http/Controllers/Concerns/AuthorizesOrganizationAccess.php` |
| Analytics null-safe user lookup | `backend/app/Http/Controllers/Api/AnalyticsController.php` |
| Security regression tests | `backend/tests/Feature/Phase11M10ApiClientAuthTest.php` |
| OpenAPI security schemes | `docs/openapi.yaml` |
| Security model update | `docs/security.md` |

---

## Authentication Model

| Mechanism | Format | Notes |
| --- | --- | --- |
| Bearer | `Authorization: Bearer {client_id}:{client_secret}` | Primary M2M method |
| HTTP Basic | `Authorization: Basic base64(client_id:client_secret)` | Compatibility fallback |
| User (Sanctum) | `Authorization: Bearer {token}` | Unchanged; checked first |

**Middleware order:** `auth.principal` → `resolve.organization` → `api_client.routes`

**Allowlisted GET endpoints for API clients:**

- `/api/v1/analytics/overview` — requires `analytics.read`
- `/api/v1/ai/usage` — requires `ai.read`
- `/api/v1/billing/usage` — requires `billing.read`

API clients cannot access write endpoints, user-only routes, or foreign organizations via `X-Organization-Id`.

---

## Verification Results (M10)

| Check | Result | Notes |
| --- | --- | --- |
| `Phase11M10ApiClientAuthTest` (7 tests) | **PASS** | Auth, scopes, revocation, allowlist, foreign org, Sanctum |
| Security group (includes M10) | **PASS** | 26 tests, 78 assertions (post-M10 main) |
| Backend full suite | **PASS** | 151 tests, 640 assertions (post-M10 main) |
| OpenAPI validation | **PASS** | `swagger-cli validate docs/openapi.yaml` |
| Sanctum regression | **PASS** | `test_sanctum_user_authentication_still_works` |
| Cross-tenant isolation | **PASS** | Foreign org header rejected for API clients |

---

## Security Controls Verified

| Control | Verification |
| --- | --- |
| Secret storage | Hashed at rest; plaintext shown once on create only |
| Revocation | Revoked clients receive 401 |
| Scope enforcement | Missing scope → 403 on protected endpoints |
| Route allowlist | Non-allowlisted routes → 403 for API clients |
| Tenant binding | Organization derived from client record; foreign header → 403 |
| No user impersonation | API clients cannot access user-only endpoints |

---

## Known M10 Limitations

1. **Read-only access** — API clients cannot perform mutations.
2. **Fixed allowlist** — Only three GET endpoints; expansion requires explicit scope + route review.
3. **No credential rotation API** — Revoke and recreate required for secret rotation.
4. **No token exchange** — Credentials sent on each request; no short-lived derived tokens.

---

## Exit Criteria

All M10 exit criteria met:

- [x] M2M authentication on M8 `api_clients` registry (no new migration)
- [x] Scoped read access to analytics, AI usage, and billing usage
- [x] Backward-compatible Sanctum user authentication
- [x] Security regression tests in `@group security`
- [x] Documentation updated (OpenAPI, security, roadmap)

**M10 approved for merge:** PR #12 merged 2026-08-10.
