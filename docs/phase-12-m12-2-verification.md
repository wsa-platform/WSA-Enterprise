# Phase 12 M12.2 — Deployment Automation

**Date:** 2026-08-11  
**Branch:** `phase-12-m12-2-deployment-automation`  
**Scope:** GHCR image publish, SSH production deploy, smoke verification

---

## Deliverables

| Item | Location |
| --- | --- |
| GHCR publish workflow | `.github/workflows/publish-images.yml` |
| SSH deploy workflow (manual dispatch) | `.github/workflows/deploy-production.yml` |
| GHCR Compose override | `docker-compose.ghcr.yml` |
| Host deploy script | `scripts/deploy-production.sh` |
| Smoke script | `scripts/smoke-production.sh` |
| Deploy automation guide | `docs/deploy-production.md` |

---

## GHCR images

| Image | Tags |
| --- | --- |
| `ghcr.io/wsa-platform/wsa-enterprise-backend` | `main`, `<sha>`, `sha-<run>` |
| `ghcr.io/wsa-platform/wsa-enterprise-frontend` | same |
| `ghcr.io/wsa-platform/wsa-enterprise-nginx` | same (Dockerfile.prod) |

---

## GitHub secrets required (production environment)

| Secret | Purpose |
| --- | --- |
| `PROD_HOST` | SSH target |
| `PROD_USER` | SSH user |
| `PROD_SSH_KEY` | Deploy private key |
| `PROD_DEPLOY_PATH` | Repo path on host |
| `GHCR_PULL_TOKEN` | Pull packages on host during deploy |

---

## Verification

| Check | Result |
| --- | --- |
| Dev Compose config | PASS |
| Prod + GHCR Compose config | PASS (CI docker-validate) |
| Backend full suite | **PASS** | 153 tests, 643 assertions |
| Prod + GHCR Compose config | **PASS** | CI docker-validate |

---

## Known limitations (M12.2)

1. Host `.env` files must exist before deploy — templates deferred to M12.3.
2. First TLS bootstrap still requires `init-letsencrypt.sh` on host (M12.1).
3. Rollback is manual tag redeploy — full runbook in M12.5.
4. `deploy-stub.yml` superseded by `publish-images.yml`.

---

## Out of scope

- Secrets templates (M12.3)
- Monitoring (M12.4)
- Rollback automation (M12.5)
- Stripe / payment providers
