# WSA-Enterprise Deployment Guide

**Last updated:** Phase 8 (2026-08-09)

## Architecture

```
[Browser / Mobile] → [HTTPS Reverse Proxy] → [Nginx :8081]
                                              ├── /api → Laravel (PHP-FPM)
                                              └── /    → React static (Vite build)
[PostgreSQL 16] ← Laravel
[Redis 7]       ← Cache, sessions, queues ← queue worker
```

## Docker Compose (local / staging)

### Windows (Docker Desktop + WSL2)

1. Install **Docker Desktop for Windows**.
2. Enable **Use the WSL 2 based engine** (Settings → General).
3. Enable integration with your default WSL distro (Settings → Resources → WSL Integration).
4. Clone or open the repo. Prefer a WSL filesystem path (e.g. `~/WSA-Enterprise`) for faster bind mounts; Windows paths (`C:\Users\...`) also work but may be slower.
5. Ensure ports **8081**, **5432**, and **6379** are free (or stop conflicting local Postgres/Redis services). Port 8080 is not used by this stack; Docker staging uses **8081** because some Windows hosts reserve 8080.

Bootstrap:

```powershell
.\scripts\staging-bootstrap.ps1
```

Or manually:

```powershell
Copy-Item backend\.env.example backend\.env
docker compose up --build -d
docker compose exec backend php artisan key:generate --force
docker compose exec backend php artisan migrate --seed --force
```

### Linux / macOS / WSL shell

```bash
./scripts/staging-bootstrap.sh
```

### Compose services

| Service | Purpose |
| --- | --- |
| `postgres` | Primary database (health check: `pg_isready`) |
| `redis` | Cache, sessions, queues (health check: `redis-cli ping`) |
| `backend` | Laravel API |
| `queue` | Laravel queue worker (`queue:work redis`) |
| `frontend` | React production build served via Nginx |
| `nginx` | Reverse proxy on port **8081** |

Laravel health: `GET /up` (framework) and `GET /api/v1/health` (API).

### Port strategy (local / staging)

| Context | URL / port | Notes |
| --- | --- | --- |
| Docker gateway | `http://localhost:8081` | Nginx serves SPA + proxies `/api` |
| Vite dev server | `http://localhost:5173` | Listed in `SANCTUM_STATEFUL_DOMAINS` for cookie-based dev |
| Mobile default API | `http://localhost:8081/api/v1` | Override with `--dart-define=API_URL=...` |

Do **not** add `localhost:8081` to `SANCTUM_STATEFUL_DOMAINS` when using bearer-token SPA auth through Nginx — it triggers CSRF mismatches.

### Queue worker

The `queue` service runs `php artisan queue:work redis` against the Redis connection configured in `backend/.env` (`QUEUE_CONNECTION=redis`).

Inspect worker logs:

```bash
docker compose logs -f queue
```

Inspect failed jobs (inside backend container):

```bash
docker compose exec backend php artisan queue:failed
docker compose exec backend php artisan queue:retry all
```

## Environment variables

Copy `backend/.env.example` to `backend/.env` before starting Compose (`docker-compose.yml` loads `backend/.env`). The bootstrap scripts create this file and run `php artisan key:generate`.

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

Docker Compose exposes HTTP on `:8081`. For production:

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

GitHub Actions (`.github/workflows/ci.yml`) runs on `main`, `phase-*`, and pull requests:

- PHP 8.4, `composer validate`, `php artisan test` (PostgreSQL service)
- `npm run lint`, `npm run build` (frontend)
- `flutter analyze` + `flutter test` (mobile)

## What this guide does not cover

- Cloud-specific IAM, secrets managers, or Kubernetes manifests
- Actual production server provisioning
- TLS certificate issuance

Do not commit `.env` files or real credentials to the repository.
