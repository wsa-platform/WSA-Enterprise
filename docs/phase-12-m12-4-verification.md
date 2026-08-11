# Phase 12 M12.4 — Verification Checklist

## Pre-merge checks

- [ ] `GET /api/v1/health/live` returns 200 with `status: ok`
- [ ] `GET /api/v1/health/ready` returns 200 when dependencies healthy
- [ ] `GET /api/v1/health` returns `{status: ok}` when healthy (smoke script compatible)
- [ ] Readiness failure returns 503 and creates `monitoring_events` row
- [ ] Audit log entries for detect / analyze / remediation
- [ ] Unauthorized remediation action rejected
- [ ] High-risk auto-remediation blocked without human approval

## Automated tests

```bash
# Docker (recommended on Windows)
.\scripts\run-backend-tests.ps1

# Or filter to M12.4
docker compose --profile test run --rm backend-test php artisan test --filter=Phase12M124
```

## Regression tests

```bash
docker compose --profile test run --rm backend-test php artisan test --filter=Phase12M121
docker compose --profile test run --rm backend-test php artisan test --filter=Phase8ComprehensiveWorkflowTest::test_health_endpoint_is_public
docker compose --profile test run --rm backend-test php artisan test --filter=Phase11M8OpenApiContractTest
```

## Manual smoke (staging / production)

```bash
curl -fsS https://<host>/api/v1/health/live
curl -fsS https://<host>/api/v1/health/ready
curl -fsS https://<host>/api/v1/health
```

## Migration

```bash
docker compose exec backend php artisan migrate
```

Creates `monitoring_events` table.

## Sign-off

- [ ] Architecture reviewed
- [ ] Safety boundaries approved (allowlist, no shell/DB destructive ops)
- [ ] Ready for PR to `main`
