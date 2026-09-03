# WSA-Enterprise Production Readiness Report

**Last updated:** Stage 10 production hardening (2026-09-03)

## Executive summary

WSA-Enterprise is suitable for **controlled single-host production deployment** with the Docker stack, Let's Encrypt TLS, GHCR image publish, SSH deploy automation, health monitoring, and backup/rollback scripts documented in Phase 12.

Phase 12 (M12.1–M12.5) adds production Docker + TLS, deployment automation, secrets templates, AI monitoring foundation with health probes, and backup/rollback/verification scripts. See [phase-12-final-verification.md](phase-12-final-verification.md).

Phase 13 (M13.1–M13.4) closes M12.4 observability deferrals, adds scheduler heartbeat and ops runbook, wires frontend Vitest in CI, and hardens production web/mobile clients (no embedded demo credentials). See [phase-13-roadmap.md](phase-13-roadmap.md).

M19 adds production-safe CORS (configurable origins only; no localhost inheritance in production) and service-ownership isolation for committed owned-service APIs. See [security.md](security.md).

**Stage 10** adds production exception safety (no API stack traces), compose `APP_DEBUG=false`, bounded scholarly HTTP timeouts, Stage 10 CI gate, and ops env-name documentation. See [stage-10-production.md](stage-10-production.md). Research Agent Internet-First and Plant AI Diagnosis independence are unchanged.

**Phase 12 additions:** See [phase-12-roadmap.md](phase-12-roadmap.md), [deploy-production.md](deploy-production.md), [tls-production.md](tls-production.md).

**Prior phases:** Phase 8 security hardening; Phase 11 enterprise features complete.

---

## 1. Production configuration

| Area | Status | Notes |
| --- | --- | --- |
| Docker Compose | Ready | Postgres, Redis, Nginx, backend, frontend, **queue**, **scheduler** |
| Laravel `.env` | Review required | Copy `.env.example`; set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY` |
| Database | Ready | PostgreSQL 16; migrations + seeders for demo |
| Redis | Ready | Cache/sessions/queues configured |
| Frontend build | Ready | Vite production build via CI |
| Mobile API URL | Configurable | `--dart-define=API_URL=https://api.example.com/api/v1` |
| HTTPS / TLS | **Implemented (M12.1)** | Let's Encrypt via nginx + certbot; see [tls-production.md](tls-production.md) |
| Stage 10 prod flags | **Implemented** | Compose production override sets `APP_DEBUG=false`; API 500s omit traces |
| GHCR deploy | **Implemented (M12.2)** | See [deploy-production.md](deploy-production.md) |
| Production backup/rollback | **Implemented (M12.5)** | `scripts/backup-production.sh`, `rollback-production.sh`, `verify-production.sh` |
| Health monitoring | **Implemented (M12.4)** | `/health/live`, `/health/ready`, `monitoring_events` |
| Horizontal scaling | **Not validated** | Single-node Docker assumed |

### Required before production

- [ ] Set `APP_DEBUG=false` and unique secrets per environment (template: `.env.production.example`)
- [ ] Configure real mail, queue workers, and scheduled tasks if notifications are enabled
- [ ] Point mobile/web clients to HTTPS API URL (`VITE_API_URL`)
- [ ] Replace demo credentials and rotate seeded passwords
- [ ] Run `./scripts/init-letsencrypt.sh` on production host (first TLS bootstrap)
- [ ] Configure GitHub `production` environment secrets for deploy workflow

---

## 2. Security configuration

| Control | Status | Notes |
| --- | --- | --- |
| Authentication | Sanctum tokens | Login/register throttled (`20/min`); API `120/min` |
| Registration | **Disabled by default** | `ALLOW_REGISTRATION=false`; explicit opt-in for production |
| Token expiry | Configurable | `SANCTUM_TOKEN_EXPIRATION` (minutes) |
| API logging | Structured | Method/path/status/user/org — no bodies |
| Authorization | PermissionService + policies | Pivot admin → `*`; explicit roles replace baseline |
| Tenant isolation | `X-Organization-Id` + FK validation | Cross-tenant header → 403; tested |
| Service ownership | `owner_user_id` + `services.supervise` | Members see owned records; supervisors see organization-wide (M19) |
| AI endpoints | Throttled per organization | `ai-org` limiter; quota + billing gating |
| CORS | `FRONTEND_URL` + optional `CORS_ALLOWED_ORIGINS` | Production never auto-includes localhost and never uses `*`. Local/testing keep localhost ports. |
| Password hashing | bcrypt | Login response excludes hash |
| API errors | JSON `{ message, errors? }` | No stack traces in API responses (Phase 6 handlers) |
| Access management | `access.manage` permission | Admin-only user/role endpoints |

### Findings

- **Low:** Demo login pre-filled in web/mobile clients — **addressed for production builds in M13.4** (`VITE_SHOW_DEMO_LOGIN=false`, Flutter `SHOW_DEMO_HINT` off by default). Local dev unchanged.
- **Resolved (Phase 8):** Token expiry documented via `SANCTUM_TOKEN_EXPIRATION`; open registration disabled by default.
- **Medium:** Rate limits are per-IP; consider authenticated user throttles for AI/diagnosis in high-traffic deployments.

---

## 3. API error handling & validation

- `ValidationException` → 422 with field errors
- `NotFoundHttpException` → 404 JSON
- Other HTTP exceptions → JSON with status code
- Form requests on auth, diagnosis request create, AI request create
- Agricultural/business modules use inline `$request->validate()` (consistent, tested)

**Recommendation:** Extend Form Request classes to high-traffic write endpoints incrementally (non-blocking).

---

## 4. Database indexes & performance

Phase 6 added performance indexes on high-traffic tables (diagnosis, library, AI). Agricultural and business tables use `organization_id` scoping on queries.

| Concern | Status |
| --- | --- |
| N+1 on dashboard | Eager loads projects/tasks |
| Library search | Paginated with filters |
| Large JSON payloads | GIS/geojson endpoints — monitor size limits |
| Connection pooling | Use PgBouncer for high concurrency (not configured) |

**Recommendation:** Run `EXPLAIN ANALYZE` on library search and dashboard under realistic data volumes before large-scale rollout.

---

## 5. Logging & safe error responses

- Laravel logging via `config/logging.php` (stack channel default)
- API does not expose exception traces to clients
- Queue failed jobs table available

**Required before production:**

- [ ] Centralized log aggregation (CloudWatch, Datadog, etc.) — M12.4 provides incidents + audit hooks only
- [ ] Alerting on 5xx rate and queue failures — operator-configured external tooling
- [x] Audit log for `access.manage` mutations (Phase 11 — `Phase11AuditCoverageTest`)
- [x] Health probes and monitoring incidents (Phase 12 M12.4)

---

## 6. React production readiness

| Item | Status |
| --- | --- |
| Build | CI green (`npm run build`) |
| Auth context + org switcher | Implemented |
| CRUD workflows | Farms, crops, soil, diagnosis, training, library, AI, business |
| Error/empty/loading states | Implemented |
| Env config | `VITE_API_URL` optional |

**Non-blocking:** No dedicated E2E browser test suite (manual/CI build only).

---

## 7. Flutter production readiness

| Item | Status |
| --- | --- |
| Analyze | CI green |
| Unit/widget tests | `flutter test` in CI |
| Session restore + 401 clear | Implemented |
| Org switcher | Implemented |
| Module navigation | 9 modules via drawer + bottom nav |
| CRUD forms | Farms, crops, soil, diagnosis, training, library, AI, business |

**Non-blocking:**

- No committed `android/` / `ios/` project folders (CI runs `flutter create .`)
- No integration tests against live API
- Store release signing not configured

---

## 8. CI/CD configuration

`.github/workflows/ci.yml`:

| Job | Command | Status |
| --- | --- | --- |
| backend | `php artisan test` (PostgreSQL service) | Required |
| frontend | `npm run lint`, `npm run build` | Required |
| mobile | `flutter analyze`, `flutter test` | Required |
| openapi | `swagger-cli validate docs/openapi.yaml` | Required |
| security | `php artisan test --group=security` | Required |
| docker-validate | prod + GHCR compose config | Required |
| stage10 | `php artisan test --group=stage10` | Required |

**Phase 12 deploy workflows:**

| Workflow | Purpose |
| --- | --- |
| `publish-images.yml` | Build and push GHCR images on `main` |
| `deploy-production.yml` | Manual SSH deploy to production host |

---

## 9. End-to-end workflow verification

Automated API workflow (`Phase7E2EWorkflowTest`):

1. Organizations + dashboard + workflow summary
2. Create farm → read fields/crops/soil
3. Submit diagnosis request
4. Create training course + library item
5. AI provider + AI request
6. Cross-tenant dashboard → 403
7. Unauthenticated dashboard → 401

Manual E2E (web `:8081` or mobile with API URL):

- Sign in → org selection → navigate all modules → create records → verify tenant scoping

---

## 10. Remaining items before real production

### Blocking (operator / host)

1. Production host `.env` from `.env.production.example` with unique secrets
2. First TLS bootstrap (`init-letsencrypt.sh`) and DNS configuration
3. GitHub `production` environment secrets and required reviewers
4. Remove/disable demo credentials in production builds
5. Run backup/verify/smoke scripts on production host before go-live

### Non-blocking (recommended)

1. Centralized log aggregation and external alerting
2. Form Request coverage for all write endpoints
3. Mobile integration tests with mocked HTTP
4. E2E browser tests for React
5. Real AI provider and payment provider (explicitly excluded from M12)

---

## Conclusion

WSA-Enterprise Phase 12 delivers a **production-hardened single-host Docker deployment** with TLS, GHCR deploy automation, health monitoring foundation, and backup/rollback procedures. Complete operator host setup and external observability before customer-facing production deployment.

---

## Review sign-off (Phase 12 closure)

| Review area | Result | Evidence |
| --- | --- | --- |
| TLS / HTTPS | Pass | M12.1 — [tls-production.md](tls-production.md) |
| Deploy automation | Pass | M12.2 — [deploy-production.md](deploy-production.md) |
| Secrets management | Pass | M12.3 — [production-secrets.md](production-secrets.md) |
| Health monitoring | Pass | M12.4 — [phase-12-m12-4-verification.md](phase-12-m12-4-verification.md) |
| Backup / rollback | Pass | M12.5 — [phase-12-m12-5-verification.md](phase-12-m12-5-verification.md) |
| Phase 12 closure | Pass | [phase-12-final-verification.md](phase-12-final-verification.md) |
| Production host exercised | N/A | No production/staging host verification performed |
