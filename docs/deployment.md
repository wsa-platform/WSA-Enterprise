# WSA-Enterprise Deployment Guide

**Last updated:** Phase 8 (2026-08-09)

## Architecture

```
[Browser / Mobile] → [HTTPS Reverse Proxy] → [Nginx :8080]
                                              ├── /api → Laravel (PHP-FPM)
                                              └── /    → React static (Vite build)
[PostgreSQL 16] ← Laravel
[Redis 7]       ← Cache, sessions, queues
```

## Docker Compose (local / staging)

```bash
docker compose up -d --build
```

Services:

| Service | Purpose |
| --- | --- |
| `postgres` | Primary database (health check: `pg_isready`) |
| `redis` | Cache, sessions, queues |
| `backend` | Laravel API |
| `frontend` | React production build served via Nginx |
| `nginx` | Reverse proxy on port 8080 |

Laravel health: `GET /up` (framework) and `GET /api/v1/health` (API).

## Environment variables

Copy `backend/.env.example` to `backend/.env` and set:

### Required (production)

| Variable | Example | Notes |
| --- | --- | --- |
| `APP_ENV` | `production` | Never `local` in prod |
| `APP_DEBUG` | `false` | Prevents stack trace leakage |
| `APP_KEY` | `base64:...` | `php artisan key:generate` |
| `APP_URL` | `https://api.example.com` | Public API URL |
| `FRONTEND_URL` | `https://app.example.com` | CORS / Sanctum |
| `DB_*` | PostgreSQL credentials | Use strong passwords |
| `REDIS_HOST` | Redis hostname | Required for cache/sessions |

### Security (Phase 8)

| Variable | Default | Notes |
| --- | --- | --- |
| `ALLOW_REGISTRATION` | `false` | Set `true` only if open signup needed |
| `SANCTUM_TOKEN_EXPIRATION` | empty | Minutes; e.g. `43200` for 30 days |
| `SESSION_ENCRYPT` | `false` | Set `true` in production |
| `LOG_LEVEL` | `warning` | Reduce noise in production |

### Frontend

| Variable | Notes |
| --- | --- |
| `VITE_API_URL` | Production API base, e.g. `https://api.example.com/api/v1` |

Build: `cd frontend && npm run build`

### Mobile

```bash
flutter build apk --dart-define=API_URL=https://api.example.com/api/v1
```

## Database migrations

```bash
php artisan migrate --force
php artisan db:seed   # demo/staging only
```

**Production strategy:**

1. Take database backup before migrate
2. Run migrations in maintenance window or with zero-downtime strategy
3. Never run demo seeders in production

## Cache & queues

- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- Run worker: `php artisan queue:work --tries=3`
- Run scheduler: `* * * * * php artisan schedule:run`

## HTTPS / reverse proxy

Docker Compose exposes HTTP on `:8080`. For production:

1. Place Nginx, Traefik, or cloud load balancer in front
2. Terminate TLS at proxy
3. Set `APP_URL` and `FRONTEND_URL` to HTTPS URLs
4. Update `SANCTUM_STATEFUL_DOMAINS` if using cookie auth

Example proxy headers: `X-Forwarded-Proto`, `X-Forwarded-For`

## Health checks

| Endpoint | Use |
| --- | --- |
| `GET /up` | Laravel framework health |
| `GET /api/v1/health` | Load balancer API probe |

## Logging

- Laravel logs: `storage/logs/laravel.log`
- API request log channel: `api.request` entries via `LogApiRequests`
- Do not log secrets or full request bodies

## Backup strategy

1. **PostgreSQL:** Daily automated `pg_dump` with retention policy
2. **Redis:** Ephemeral cache — backup optional; sessions may be lost on restart
3. **Uploaded files:** Backup `storage/app` if media uploads enabled

## Monitoring readiness

- Monitor `/up` and `/api/v1/health`
- Alert on 5xx rate, DB connection failures, Redis unavailability
- Track queue depth if async jobs enabled

## CI validation

GitHub Actions (`.github/workflows/ci.yml`) runs on `main`, `phase-7-*`, and `phase-8-*`:

- `php artisan test` (PostgreSQL service)
- `npm run build` (frontend)
- `flutter analyze` + `flutter test` (mobile)

## What this guide does not cover

- Cloud-specific IAM, secrets managers, or Kubernetes manifests
- Actual production server provisioning
- TLS certificate issuance

Do not commit `.env` files or real credentials to the repository.
