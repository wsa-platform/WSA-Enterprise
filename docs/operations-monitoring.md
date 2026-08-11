# Operations & Monitoring — M13.1

**Last updated:** Phase 13 M13.1 (2026-08-11)

This runbook describes how to operate WSA-Enterprise monitoring on a single Docker host without external vendor agents (Datadog, Grafana, etc.). It builds on the M12.4 health and incident foundation.

## What to watch

| Signal | Endpoint / source | Healthy | Action |
| --- | --- | --- | --- |
| Liveness | `GET /api/v1/health/live` | HTTP 200, `"status":"ok"` | Restart backend container if failing |
| Readiness | `GET /api/v1/health/ready` | HTTP 200 | Inspect failing `checks` keys (database, cache, queue, storage, scheduler, authentication) |
| Legacy health | `GET /api/v1/health` | HTTP 200, `"status":"ok"` | Backward-compatible smoke target |
| Scheduler heartbeat | Cache key `healthcheck:scheduler:last_run` | Updated within ~2 minutes | Ensure `scheduler` service runs `php artisan schedule:work` |
| Container health | `docker compose ps` | All services healthy | Review logs for unhealthy service |
| TLS renewal | Certbot container logs | Successful renewals | Confirm deploy hook reloads nginx (see TLS section) |

Post-deploy smoke: `./scripts/smoke-production.sh` (includes `/health/live`, `/health/ready`, legacy `/health`, and `/up`).

Full verification: `./scripts/verify-production.sh`.

## Health check messages (production)

When `APP_DEBUG=false`, public health responses use generic messages via `HealthCheckMessages` (no stack traces, connection strings, or paths). When `APP_DEBUG=true` (local/staging only), raw exception messages may appear in check details for debugging.

## Monitoring incidents

Readiness failures create rows in `monitoring_events` (component, severity, lifecycle stage). M13.1 deduplicates **open** incidents per component when `monitoring.deduplicate_open_incidents=true` to reduce noise from repeated probe failures.

Remediation actions run only through `RemediationService` (audited). `SafeRemediationExecutor` is not directly resolvable from the container. Auto-remediation remains disabled by default (`monitoring.auto_remediation=false`).

Allowlisted actions include `cache.clear_probe_keys`, which clears the configured cache probe key (`monitoring.cache_probe_key`, default `healthcheck:probe:write`).

## Scheduler

`backend/routes/console.php` registers `monitoring-scheduler-heartbeat` (every minute), writing `healthcheck:scheduler:last_run` to cache.

The Docker `scheduler` service must run:

```bash
php artisan schedule:work
```

If the heartbeat is stale, readiness reports scheduler as unhealthy.

## Logging

| Setting | Production guidance |
| --- | --- |
| `LOG_CHANNEL` | `stderr` or `stack` writing to container stdout/stderr |
| `LOG_LEVEL` | `warning` or `error` in production |
| `LOG_SLACK_WEBHOOK_URL` | Optional — document for operators; wire Monolog Slack handler in a future phase if needed |

Structured API request logging is handled by `LogApiRequests` middleware. Audit events are persisted in `audit_logs`.

## TLS renewal and nginx reload

Production Compose mounts `scripts/certbot-deploy-hook.sh` as a Certbot deploy hook. After successful renewal, the hook attempts:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T nginx nginx -s reload
```

The Certbot container typically lacks Docker CLI access; run the same script on the **production host** after manual renewal if automatic reload fails. See [tls-production.md](tls-production.md).

## External observability integration (document-only)

No vendor agents are installed in M13. Operators may forward:

- Container stdout/stderr to their log platform
- HTTP synthetic checks against `/api/v1/health/live` and `/api/v1/health/ready`
- PostgreSQL and Redis metrics from host or sidecar exporters (not included in repo)
- GitHub Actions CI results for release quality gates

Webhook placeholders: configure alerting in your platform when readiness returns HTTP 503 for sustained intervals.

## Incident flow

1. **Detect** — smoke/verify script failure, readiness 503, or operator alert
2. **Triage** — `curl -fsSk https://${DOMAIN}/api/v1/health/ready | jq` (or inspect JSON without jq)
3. **Correlate** — `docker compose logs backend scheduler queue nginx --tail=200`
4. **Remediate** — restart affected service; use documented backup/rollback scripts for image-level issues ([phase-12-m12-5-rollback-runbook.md](phase-12-m12-5-rollback-runbook.md))
5. **Escalate** — if database corruption or data loss suspected, stop writes and restore from backup (manual DB restore not automated)

## Related documents

- [phase-12-m12-4-monitoring-architecture.md](phase-12-m12-4-monitoring-architecture.md)
- [phase-12-m12-4-verification.md](phase-12-m12-4-verification.md)
- [deploy-production.md](deploy-production.md)
- [production-readiness.md](production-readiness.md)
