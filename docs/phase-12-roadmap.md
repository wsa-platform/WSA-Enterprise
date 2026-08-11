# Phase 12 — Production Deployment Hardening

**Baseline:** Phase 11 complete (`main`)  
**Target:** Single Docker host production deployment with Let's Encrypt TLS and GHCR
**Status:** **COMPLETE** (2026-08-11)

See [phase-12-final-verification.md](phase-12-final-verification.md) for authoritative closure sign-off.

---

## Milestone Overview

| # | Milestone | Scope | Status |
|---|-----------|-------|--------|
| M12.1 | Production Docker + TLS | Prod Compose, nginx TLS, Certbot, TrustProxies | Complete |
| M12.2 | Deployment automation | GHCR push, SSH deploy workflow, smoke checks | Complete |
| M12.3 | Production secrets management | `.env.production.example`, secrets docs, no hardcoded passwords | Complete |
| M12.4 | AI monitoring & health checks | Health probes, incidents, safe remediation, AI analyzer foundation | Complete |
| M12.5 | Rollback and production verification | Backup, rollback runbook, verification scripts | Complete |

**Exclusions:** Stripe/payment providers, unrelated product features, API expansion unless deploy-required.

---

## M12.1 — Production Docker + TLS ✅

See [phase-12-m12-1-verification.md](phase-12-m12-1-verification.md).

**Merged:** PR #14 (`dec5049`)

---

## M12.2 — Deployment Automation ✅

### CI/CD

- [x] GHCR publish workflow (`publish-images.yml`)
- [x] GHCR Compose override (`docker-compose.ghcr.yml`)
- [x] Manual SSH deploy workflow (`deploy-production.yml`)
- [x] Host deploy script (`scripts/deploy-production.sh`)
- [x] Post-deploy smoke script (`scripts/smoke-production.sh`)

### Documentation

- [x] `docs/deploy-production.md`
- [x] CI prod+GHCR compose validation
- [x] `docs/phase-12-m12-2-verification.md`

**Merged:** PR #15 (`7c6061c`)

---

## M12.3 — Production Secrets Management ✅

- [x] `.env.production.example` — placeholders only
- [x] `docs/production-secrets.md`
- [x] Hardcoded passwords removed from env examples

See [phase-12-m12-3-verification.md](phase-12-m12-3-verification.md).

**Merged:** PR #17

---

## M12.4 — AI Monitoring & Health Checks ✅

### Health endpoints

- [x] `GET /api/v1/health/live` — liveness
- [x] `GET /api/v1/health/ready` — readiness (DB, cache, queue, storage, scheduler, auth)
- [x] `GET /api/v1/health` — backward-compatible legacy/smoke endpoint

### Monitoring foundation

- [x] `monitoring_events` table and `MonitoringEvent` model
- [x] `HealthCheckService`, `MonitoringEventService`, `RemediationService`
- [x] `AiMonitoringAnalyzerInterface` + stub analyzer
- [x] Safe remediation allowlist (`config/monitoring.php`)
- [x] Audit trail for detect / analyze / remediate / reject

### Documentation

- [x] `docs/phase-12-m12-4-monitoring-architecture.md`
- [x] `docs/phase-12-m12-4-verification.md`
- [x] OpenAPI paths for `/health`, `/health/live`, `/health/ready`

**Merged:** PR #18 (`1c8d316`)

---

## M12.5 — Rollback & Production Verification ✅

### Scripts

- [x] `scripts/backup-production.sh` — timestamped PostgreSQL backup (dry-run supported)
- [x] `scripts/rollback-production.sh` — immutable GHCR tag rollback (no auto DB restore)
- [x] `scripts/verify-production.sh` — HTTPS, health, container, Postgres/Redis checks

### Documentation

- [x] `docs/phase-12-m12-5-backup-and-rollback.md`
- [x] `docs/phase-12-m12-5-rollback-runbook.md`
- [x] `docs/phase-12-m12-5-verification.md`

### Tests

- [x] `Phase12M125ProductionOpsTest` — config validation, rollback safety, secret non-disclosure

**Merged:** PR #19 (`c42b981`). CI verified — 171 backend tests on merge run `31488373975`.

---

## Phase 12 closure

| Item | Location |
| --- | --- |
| Final sign-off | [phase-12-final-verification.md](phase-12-final-verification.md) |
| Production deploy guide | [deploy-production.md](deploy-production.md) |
| TLS guide | [tls-production.md](tls-production.md) |
| Backup / rollback | [phase-12-m12-5-backup-and-rollback.md](phase-12-m12-5-backup-and-rollback.md) |

**Phase 12 complete.** Do not expand scope into product features without a new phase approval.
