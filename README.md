# WSA-Enterprise

WSA-Enterprise is a multi-client platform with a Laravel 12 API, React 19 web application, and Flutter mobile application.

## Architecture

| Directory | Stack | Purpose |
| --- | --- | --- |
| `backend` | Laravel 12, Sanctum | REST API and authentication |
| `frontend` | React 19, Vite, TypeScript | Browser client |
| `mobile` | Flutter | iOS and Android client |
| `nginx` | Nginx | HTTP gateway for the Laravel API |

PostgreSQL stores application data and Redis provides cache, sessions, and queues.

## Start with Docker

```bash
cp backend/.env.example backend/.env
docker compose up --build
```

The API is available at `http://localhost:8080`; its health endpoint is `GET /api/v1/health`.

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

See `docs/phase7.md` and `docs/production-readiness.md`.

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
