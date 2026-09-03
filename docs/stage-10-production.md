# WSA Enterprise Stage 10 — Production Readiness, Deployment, and Expansion Hardening

**Status:** Implemented (code + tests; host deploy not performed)  
**Baseline:** Stage 9 commit `2c80a3a`  
**Does not rewrite:** Research Agent Stages 1–5, Plant AI Diagnosis Stages 6–7, Flutter Stage 8, Library UI

This document lists **environment variable names only**. Never commit real passwords, API keys, tokens, or private keys.

## Principles preserved

- Internet-First scientific research (external OpenAlex/Crossref before library memory).
- Plant AI Diagnosis remains independent of the Research Agent.
- External scholarly payloads are **data**, not invented citations. Missing DOI stays unset; engines must not fabricate DOIs.
- Library public UI and crop-file reading contracts are unchanged.

## A. Laravel production

| Name | Production intent |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (forced off when `APP_ENV=production`) |
| `APP_KEY` | Unique per environment |
| `APP_URL` | Public HTTPS origin |
| `LOG_CHANNEL` | `stack` (or operator aggregator) |
| `LOG_LEVEL` | `warning` or `error` |
| `FRONTEND_URL` | SPA origin for CORS |
| `CORS_ALLOWED_ORIGINS` | Optional extra origins (no `*`) |
| `SCIENTIFIC_HTTP_TIMEOUT` | Outbound OpenAlex/Crossref timeout seconds (default `15`, max `60`) |

Unhandled API exceptions render JSON `{ "message": "Server error." }` without traces, files, or exception class names when debug is off or the app is in production.

Trusted proxies remain enabled for `X-Forwarded-*` (Docker/TLS and hosted reverse proxies).

## B. API security

Existing controls stay in place: Sanctum, throttles (auth `20/min`, public research/diagnosis `60/min`, authenticated API `120/min`), validation JSON `422`, tenant header scoping.

TLS security headers (nginx production): `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options`. See [tls-production.md](tls-production.md).

## C–E. Research, sources, library

- Treat OpenAlex/Crossref bodies as untrusted **data**.
- Do not invent DOI/URL/title/year.
- Library persistence keeps pipeline provenance from Stage 5; Stage 10 does not change Library pages.

## F. Observability

| Probe | Route |
| --- | --- |
| Laravel | `GET /up` |
| Live | `GET /api/v1/health/live` |
| Ready | `GET /api/v1/health/ready` |
| Legacy smoke | `GET /api/v1/health` |

`LogApiRequests` records method, path, status, duration, user id, organization id, and request id. It does **not** log bodies, tokens, or passwords.

## G. Queues

Compose worker: `php artisan queue:work redis --tries=3 --timeout=90 --max-time=3600`.  
AI jobs use `AI_QUEUE_TRIES` (default `3`). Failed jobs table: `failed_jobs`.

Names: `QUEUE_CONNECTION`, `REDIS_HOST`, `REDIS_URL`, `AI_QUEUE_TRIES`, `AI_TIMEOUT`, `AI_QUEUE`.

## H. Docker

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml config --quiet
```

Production override sets `APP_ENV=production` and `APP_DEBUG=false` on `backend`, `queue`, and `scheduler`. Images must not bake secrets: `.env` and key material are dockerignored. Healthchecks remain on postgres, redis, backend, frontend, nginx, queue, and scheduler.

## I. Render (not deployed from this stage)

Configure these **names** in the Render dashboard (values only on the host):

`APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, `DATABASE_URL`, `DB_CONNECTION`, `DB_SSLMODE`, `REDIS_URL`, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `LOG_CHANNEL`, `LOG_LEVEL`, `ALLOW_REGISTRATION`, `SANCTUM_TOKEN_EXPIRATION`, `OPENALEX_MAILTO`, `SCIENTIFIC_HTTP_TIMEOUT`, `WSA_RESEARCH_AGENT_ENABLED`, `WSA_PLANT_DIAGNOSIS_ENABLED`

Do not set `PGSSLCERT`, `PGSSLKEY`, or `PGSSLROOTCERT` unless using mutual TLS. This repository does not run a Render deploy.

## J. CI

`.github/workflows/ci.yml` keeps existing jobs and adds `--group=stage10`. Full backend suite still runs `php artisan test`.

## K. Flutter

Production API base URL (existing compile-time config, no secrets):

```bash
flutter build apk --dart-define=API_URL=https://app.example.com/api/v1
```

Optional timeout names: `API_TIMEOUT_SECONDS` (default 30), `RESEARCH_TIMEOUT_SECONDS` (default 60), `DIAGNOSIS_TIMEOUT_SECONDS` (default 60). Store signing keys stay off-repo.

## Operator checklist (not executed here)

1. Host `backend/.env` from [production-secrets.md](production-secrets.md) placeholders.
2. TLS bootstrap and GHCR deploy remain as in [deploy-production.md](deploy-production.md).
3. Point mobile/web at HTTPS `API_URL` / `VITE_API_URL`.
4. Confirm `APP_DEBUG=false` and health probes after go-live.

Related: [production-readiness.md](production-readiness.md), [docker-production.md](docker-production.md), [deployment.md](deployment.md), [architecture/ADR-internet-first-agricultural-ai-research-agent.md](architecture/ADR-internet-first-agricultural-ai-research-agent.md).
