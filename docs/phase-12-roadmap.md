# Phase 12 — Production Deployment Hardening

**Baseline:** Phase 11 complete (`main`)  
**Target:** Single Docker host production deployment with Let's Encrypt TLS and GHCR

---

## Milestone Overview

| # | Milestone | Scope | Status |
|---|-----------|-------|--------|
| M12.1 | Production Docker + TLS | Prod Compose, nginx TLS, Certbot, TrustProxies | Complete |
| M12.2 | Deployment automation | GHCR push, SSH deploy workflow, smoke checks | In progress |
| M12.3 | Production secrets management | `.env.production.example`, secrets docs, no hardcoded passwords | Complete |
| M12.4 | AI monitoring & health checks | Health probes, incidents, safe remediation, AI analyzer foundation | Complete |
| M12.5 | Rollback and production verification | Backup, rollback runbook, verification scripts | In progress |

**Exclusions:** Stripe/payment providers, unrelated product features, API expansion unless deploy-required.

---

## M12.1 — Production Docker + TLS ✅

See [phase-12-m12-1-verification.md](phase-12-m12-1-verification.md).

**Commit message example:** `Add production Docker Compose override and Let's Encrypt TLS.`

---

## M12.2 — Deployment Automation

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

**Deploy mechanism:** SSH to single Docker production host.  
**Registry:** `ghcr.io/wsa-platform/wsa-enterprise-*`  
**No secrets templates** (deferred to M12.3).

**Commit message example:** `Add GHCR publish workflow and SSH production deployment automation.`

---

## M12.4 — AI Monitoring & Health Checks

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

**Commit message example:** `Add AI monitoring foundation with health probes and safe remediation.`

---

## M12.5 — Rollback & Production Verification

### Scripts

- [x] `scripts/backup-production.sh` — timestamped PostgreSQL backup (dry-run supported)
- [x] `scripts/rollback-production.sh` — immutable GHCR tag rollback (no auto DB restore)
- [x] `scripts/verify-production.sh` — HTTPS, health, container, Postgres/Redis checks

### Documentation

- [x] `docs/phase-12-m12-5-backup-and-rollback.md`
- [x] `docs/phase-12-m12-5-rollback-runbook.md`

### Tests

- [x] `Phase12M125ProductionOpsTest` — config validation, rollback safety, secret non-disclosure

**Verification:** Pending local/CI run before marking complete.

**Commit message example:** `Add production backup, rollback, and verification scripts for M12.5.`

---

## Approval

M12.3 begins only after M12.2 PR merge and explicit approval.
