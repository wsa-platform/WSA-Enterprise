# Phase 12 — Production Deployment Hardening

**Baseline:** Phase 11 complete (`main`)  
**Target:** Single Docker host production deployment with Let's Encrypt TLS and GHCR

---

## Milestone Overview

| # | Milestone | Scope | Status |
|---|-----------|-------|--------|
| M12.1 | Production Docker + TLS | Prod Compose, nginx TLS, Certbot, TrustProxies | Complete |
| M12.2 | Deployment automation | GHCR push, SSH deploy workflow, smoke checks | In progress |
| M12.3 | Production secrets management | `.env.production.example`, secrets docs, no hardcoded passwords | Pending |
| M12.4 | Monitoring and health checks | Logging defaults, runbook, probe spec | Pending |
| M12.5 | Rollback and production verification | Rollback runbook, backup, M12 sign-off | Pending |

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

## Approval

M12.3 begins only after M12.2 PR merge and explicit approval.
