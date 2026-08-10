# Phase 12 — Production Deployment Hardening

**Baseline:** Phase 11 complete (`main`)  
**Target:** Single Docker host production deployment with Let's Encrypt TLS and GHCR

---

## Milestone Overview

| # | Milestone | Scope | Status |
|---|-----------|-------|--------|
| M12.1 | Production Docker + TLS | Prod Compose, nginx TLS, Certbot, TrustProxies | In progress |
| M12.2 | Deployment automation | GHCR push, deploy workflow, smoke checks | Pending |
| M12.3 | Production secrets management | `.env.production.example`, secrets docs, no hardcoded passwords | Pending |
| M12.4 | Monitoring and health checks | Logging defaults, runbook, probe spec | Pending |
| M12.5 | Rollback and production verification | Rollback runbook, backup, M12 sign-off | Pending |

**Exclusions:** Stripe/payment providers, unrelated product features, API expansion unless deploy-required.

---

## M12.1 — Production Docker + TLS

### Infrastructure

- [x] Enhanced `docker-compose.prod.yml` (no public DB/Redis, no bind mounts, Certbot)
- [x] TLS nginx (`nginx/Dockerfile.prod`, `default.prod.conf.template`, `ssl-params.conf`)
- [x] Let's Encrypt init script (`scripts/init-letsencrypt.sh`)
- [x] Certbot automatic renewal service

### Application

- [x] Laravel `trustProxies` for reverse-proxy HTTPS
- [x] `URL::forceScheme('https')` in production when `APP_URL` is HTTPS
- [x] Frontend `VITE_API_URL` build arg for production API base

### Documentation

- [x] `docs/tls-production.md`
- [x] Updated `docs/docker-production.md`
- [x] `docs/phase-12-m12-1-verification.md`

### Tests

- [x] `Phase12M121TrustedProxyTest`

**Commit message example:** `Add production Docker Compose override and Let's Encrypt TLS.`

---

## Approval

M12.2 begins only after M12.1 PR merge and explicit approval.
