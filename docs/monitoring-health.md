# M12.4 — Monitoring & Health Checks

## Scope

M12.4 adds lightweight application health endpoints and a documented health model for the existing Docker/NGINX deployment.

## Endpoints

### Liveness

`GET /api/v1/health/live`

Purpose: confirm that the Laravel application process is responding. It does not require database or cache dependencies.

Expected response: HTTP `200` with `status: ok`.

### Compatibility endpoint

`GET /api/v1/health`

This remains the liveness endpoint so existing NGINX and deployment checks continue to work without changes.

### Readiness

`GET /api/v1/health/ready`

Purpose: confirm that the application can reach its required runtime dependencies.

Checks:

- PostgreSQL via `select 1`.
- Laravel cache store via a short-lived write/read/delete probe.

Expected healthy response: HTTP `200` and `status: ok`.

If a dependency check fails, the endpoint returns HTTP `503` and `status: degraded` with the individual check states.

## Docker monitoring model

Existing Docker Compose healthchecks remain the container-level signal:

- PostgreSQL uses `pg_isready`.
- Redis uses `redis-cli ping`.
- Backend checks Laravel availability.
- Frontend checks the local HTTP server.
- NGINX checks `/api/v1/health`.
- Queue and scheduler check their worker processes.

M12.4 does not introduce an external monitoring vendor, paid observability service, or product telemetry.

## Operational rule

A liveness failure means the application is not responding and should be investigated/restarted according to the deployment policy.

A readiness failure means the application is responding but one or more required dependencies are unavailable. Do not treat readiness failure as proof that the application code itself is broken.

## Verification checklist

- [ ] Liveness returns HTTP 200.
- [ ] Readiness returns HTTP 200 when PostgreSQL and cache are healthy.
- [ ] Readiness returns HTTP 503 when a required dependency is unavailable.
- [ ] Existing NGINX `/api/v1/health` check remains compatible.
- [ ] Feature tests cover liveness, compatibility, and healthy readiness.
- [ ] No production secrets are added to the repository.

## Scope boundaries

- M12.4: health endpoints, dependency readiness, Docker/NGINX health documentation.
- M12.5: rollback, backups, production sign-off — not started.
- External monitoring/alerting integrations are intentionally deferred until the production operating model is finalized.
