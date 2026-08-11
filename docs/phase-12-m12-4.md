# Phase 12 — M12.4 Monitoring & Health Checks

## Objective
Provide a small, reliable health contract for production operations without adding external monitoring dependencies.

## Delivered

- Liveness endpoint: `/api/v1/health/live`.
- Backward-compatible liveness endpoint: `/api/v1/health`.
- Readiness endpoint: `/api/v1/health/ready`.
- PostgreSQL readiness probe.
- Laravel cache readiness probe.
- HTTP `503` when readiness dependencies are unavailable.
- Feature tests for liveness, compatibility, and healthy readiness.
- Operational documentation for Docker/NGINX health signals.

## Acceptance criteria

- Application liveness is independently testable.
- Readiness reflects required database/cache dependencies.
- Existing NGINX healthcheck remains compatible.
- No external monitoring vendor is required for this milestone.
- No production secrets are committed.

## Out of scope

- Rollback and backup procedures (M12.5).
- External alerting/observability platform integrations.
- AI self-healing.
- Product features.
