# WSA-Enterprise

WSA-Enterprise is a multi-client platform with a Laravel 12 API, React 19 web application, and Flutter mobile application.

## Architecture

| Directory | Stack | Purpose |
| --- | --- | --- |
| `backend` | Laravel 12, Sanctum | REST API and authentication |
| `frontend` | React 19, Vite, TypeScript | Browser client |
| `mobile` | Flutter | iOS and Android client |
| `nginx` | Nginx | HTTP gateway for the Laravel API |

PostgreSQL stores application data and Redis provides cache, sessions, and queues. Docker Compose includes a dedicated `queue` worker service.

**Authoritative reference:** [WSA Enterprise Architecture v1.0](docs/architecture/WSA-Enterprise-Architecture-v1.md)

## Start with Docker

**Windows:** Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) with the **WSL2 backend** enabled. Run commands from PowerShell, Git Bash, or a WSL distro terminal in the project directory.

```powershell
# Recommended one-shot bootstrap (creates backend/.env, key, migrate + seed)
.\scripts\staging-bootstrap.ps1
```

Manual flow:

```powershell
Copy-Item backend\.env.example backend\.env
docker compose up --build -d
docker compose exec backend php artisan key:generate --force
docker compose exec backend php artisan migrate --seed --force
```

The app is available at `http://localhost:8081`. Health check: `GET /api/v1/health`. Demo login: `admin@wsa.test` / `password`.

Queue worker logs: `docker compose logs -f queue`. Failed jobs: `docker compose exec backend php artisan queue:failed`.

**Testing:** Never run `php artisan test` against the Docker staging database. See [docs/testing.md](docs/testing.md) for isolated test execution.

## Phase 11 enterprise expansion

Phase 11 delivers multi-tenant RBAC, AI platform, enterprise dashboard, billing, notifications/audit, Flutter alignment, API/production hardening, final integration verification, and API client M2M authentication (M1–M10).

| Milestone | Status | Highlights |
| --- | --- | --- |
| M1 | Complete | Architecture audit and target design |
| M2 | Complete | Tenant scoping, enterprise RBAC, security tests |
| M3 | Complete | AI providers, quotas, async dispatch, usage API |
| M4 | Complete | React enterprise dashboard modules |
| M5 | Complete | Billing plans, subscriptions, entitlements |
| M6 | Complete | Flutter layered architecture alignment |
| M7 | Complete | Notifications pipeline, audit `request_id`, observability |
| M8 | Complete | OpenAPI expansion, analytics overview, API client registry, Docker healthchecks, CI security job |
| M9 | Complete | Cross-module integration tests, OpenAPI parity, production readiness sign-off |
| M10 | Complete | API client bearer authentication with scoped read endpoints |

Key documents:

- [docs/phase-11-final-verification.md](docs/phase-11-final-verification.md) — **M9 final sign-off**
- [docs/phase-11-roadmap.md](docs/phase-11-roadmap.md)
- [docs/phase-11-architecture.md](docs/phase-11-architecture.md)
- [docs/phase-11-verification-report.md](docs/phase-11-verification-report.md)
- [docs/openapi.yaml](docs/openapi.yaml) — validated in CI (`swagger-cli validate`)
- [docs/docker-production.md](docs/docker-production.md) — production Compose override

New M8 API endpoints:

| Endpoint | Purpose |
| --- | --- |
| `GET /api/v1/analytics/overview` | Organization-scoped analytics snapshot |
| `GET/POST /api/v1/api-clients` | API client registry (hashed secrets) |
| `POST /api/v1/api-clients/{id}/revoke` | Revoke API client |

Docker services include `queue`, `scheduler`, and healthchecks on backend, frontend, nginx, postgres, and redis. Run isolated backend tests:

```powershell
docker compose --profile test run --rm backend-test
```

Supporting guides: [multi-tenancy](docs/multi-tenancy.md), [ai-platform](docs/ai-platform.md), [billing](docs/billing.md), [security](docs/security.md).

## Phase 10 production platform

Phase 10 adds isolated test databases, SPA routing fixes, production-ready async AI, expanded audit logging, multi-tenant security tests, modular frontend/mobile API clients, OpenAPI validation in CI, and production hardening documentation.

**Linux/macOS/WSL:** run `./scripts/staging-bootstrap.sh` instead.

## Local development

```bash
# Backend (requires PHP 8.2+ and Composer)
cd backend && composer install && php artisan key:generate && php artisan migrate

# Frontend (requires Node 22+)
cd frontend && npm install && npm run dev

# Mobile (requires Flutter SDK)
cd mobile && flutter create . && flutter pub get && flutter run
```

Sanctum protects API routes via `auth:sanctum`; `/api/v1/user` is the initial authenticated endpoint.

## Agricultural modules (Phase 4)

Farm, crop, and soil endpoints are organization-scoped and require authentication:

| Prefix | Examples |
| --- | --- |
| `/api/v1/farm/{module}` | farms, regions, fields, blocks, greenhouses, irrigation-zones, gps-coordinates, gis-maps |
| `/api/v1/crop/{module}` | types, varieties, seasons, growth-stages, harvests, yields |
| `/api/v1/soil/{module}` | analyses, nutrients, recommendations |

Seed demo agricultural data after migrating:

```bash
cd backend && php artisan migrate --seed
```

Demo login: `admin@wsa.test` / `password`. The web workspace provides route-based navigation for dashboard, farms, crops, soil, diagnosis, training, library, and AI services.

See `docs/database.md` for the full agricultural schema, `docs/phase6.md` for production integration details, `docs/phase7.md` for Phase 7 scope, and `docs/production-readiness.md` for the deployment checklist.

## Phase 7 authorization, UX, and production readiness

Phase 7 adds permission enforcement, React and Flutter CRUD workflows, business module UI, full regression tests, and a production-readiness review.

| Track | Highlights |
| --- | --- |
| Authorization | PermissionService, policies, tenant FK validation |
| Web | Organization switcher, CRUD forms, Business page |
| Mobile | Full module navigation, org switcher, CRUD forms, session restore |
| Testing | Phase 7 feature + E2E workflow tests; CI runs backend, frontend build, flutter analyze/test |

See `docs/phase7.md` and `docs/production-readiness.md`. Verification matrix: `docs/phase7-verification.md`.

## Phase 5 modules

Phase 5 adds decision-support diagnosis, training, library, and AI foundation services on top of the agricultural core.

| Area | API prefix | Notes |
| --- | --- | --- |
| Disease diagnosis | `/api/v1/diagnosis/*` | Decision-support only; mock AI provider by default |
| Training / education | `/api/v1/training/*` | Arabic-first course content, enrollments, progress, certificates |
| Agricultural library | `/api/v1/library/*` | Searchable articles/resources with tags and categories |
| AI services | `/api/v1/ai/*` | Provider abstraction via `AI_PROVIDER=mock` |

Seed all demo data:

```bash
cd backend && php artisan migrate --seed
```

Environment configuration:

```bash
AI_PROVIDER=mock
AI_TIMEOUT=30
```

The React workspace provides dedicated navigation for dashboard, farms, crops, soil, diagnosis, training, library, and AI services. The Flutter app adds dashboard and farms screens plus session restore.

## Phase 6 production integration

Phase 6 adds tenant header selection, platform workflow endpoints, hardened AI validation/normalization, safe media metadata handling, improved library search filters, performance indexes, and integrated web/mobile navigation.

| Area | API prefix | Notes |
| --- | --- | --- |
| Platform | `/api/v1/platform/*` | Organizations and workflow summary |
| Authorization | `X-Organization-Id` header | Optional active-tenant selection |
| AI services | `/api/v1/ai/*` | Validated request types + normalized outputs |

See `docs/phase6.md` for architecture, authorization, testing, and demo workflow details.
