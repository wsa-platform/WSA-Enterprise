# Phase 12 M12.2 — Deployment Automation

**Date:** 2026-08-11
**Branch:** `phase-12-m12-2-deployment-automation` (merged via PR #15)
**Merge commit:** `e8beeec`
**Feature commit:** `7c6061c`
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

Enable required reviewers on the `production` environment before allowing deploys.

---

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| Dev Compose config | **PASS** | CI docker-validate |
| Prod + GHCR Compose config | **PASS** | CI docker-validate |
| Backend full suite | **PASS** | CI backend job on PR #15 |
| Publish workflow present | **PASS** | `publish-images.yml` |
| Deploy workflow present | **PASS** | `deploy-production.yml` |
| Smoke script HTTPS health check | **PASS** | `scripts/smoke-production.sh` |

### CI evidence (PR #15)

| Job | Result |
| --- | --- |
| backend | SUCCESS |
| frontend | SUCCESS |
| mobile | SUCCESS |
| openapi | SUCCESS |
| security | SUCCESS |
| docker-validate | SUCCESS |

CI run reference: `31436131051`.

---

## Deploy mechanism

- **Target:** Single Docker production host via SSH.
- **Registry:** `ghcr.io/wsa-platform/wsa-enterprise-*`
- **Flow:** Publish on `main` push → manual `deploy-production.yml` dispatch → host runs `deploy-production.sh` → migrate → smoke.

---

## Known limitations

1. First TLS bootstrap requires `./scripts/init-letsencrypt.sh` on host (M12.1).
2. Host `.env` must exist before deploy — use `.env.production.example` (M12.3).
3. Rollback and backup scripts provided in M12.5 — see [phase-12-m12-5-rollback-runbook.md](phase-12-m12-5-rollback-runbook.md).
4. `deploy-stub.yml` superseded by `publish-images.yml`.
5. Production host deploy not executed during closure — operator responsibility.

---

## Production host verification

**N/A — no production/staging host verification performed.**

---

## Acceptance status

**M12.2: COMPLETE** — merged to `main` via PR #15. CI green; documentation and workflows verified.
