# Phase 13 — M13.1 Verification (Observability & Operations)

**Milestone:** M13.1 Observability & Operations  
**Date:** 2026-08-11

---

## Scope delivered

| Item | Status |
| --- | --- |
| M12.4 hardening — cache probe remediation | Done |
| M12.4 hardening — SafeRemediationExecutor container guard | Done |
| M12.4 hardening — sanitized health messages (`APP_DEBUG=false`) | Done |
| M12.4 hardening — open incident deduplication | Done |
| Scheduler heartbeat (`healthcheck:scheduler:last_run`) | Done |
| Operations runbook `docs/operations-monitoring.md` | Done |
| Certbot deploy hook + prod compose wiring | Done |
| Smoke script `/health/live` + `/health/ready` | Done |

---

## Key files

- `backend/app/Support/HealthCheckMessages.php`
- `backend/app/Services/Monitoring/*`
- `backend/app/Providers/AppServiceProvider.php`
- `backend/config/monitoring.php`
- `backend/routes/console.php`
- `scripts/certbot-deploy-hook.sh`
- `scripts/smoke-production.sh`
- `docker-compose.prod.yml`
- `docs/operations-monitoring.md`

---

## Tests

| Suite | Purpose |
| --- | --- |
| `Phase13M131ObservabilityOpsTest` | M13.1 acceptance tests (6 tests) |
| `Phase12M124HealthMonitoringTest` | M12.4 regression |
| `Phase12M125ProductionOpsTest` | Production script safety regression |

---

## Validation commands

```bash
docker compose --profile test run --rm backend-test php artisan test --filter=Phase13M131
docker compose --profile test run --rm backend-test php artisan test --filter=Phase12M124
docker compose -f docker-compose.yml -f docker-compose.prod.yml config --quiet
```

---

## Sign-off

- [ ] Local validation green (Phase 3 report)
- [ ] Operator review
- [ ] Merge approval

**M13.1:** Merged to `main` via PR #21 @ `4975fdf`
