# Production Deployment Automation (M12.2)

**Target:** Single Docker host with GHCR images and SSH deploy from GitHub Actions.

---

## Overview

| Component | Purpose |
| --- | --- |
| `publish-images.yml` | Build and push images to GHCR on `main` push (or manual dispatch) |
| `deploy-production.yml` | Manual SSH deploy to production host |
| `docker-compose.ghcr.yml` | Pull pre-built images instead of local build |
| `scripts/deploy-production.sh` | Host-side pull, up, migrate, smoke |
| `scripts/smoke-production.sh` | HTTPS health verification |

Combine with M12.1 files:

```bash
docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  -f docker-compose.ghcr.yml \
  up -d --no-build
```

---

## GHCR images

Published to `ghcr.io/wsa-platform/`:

| Image | Service(s) |
| --- | --- |
| `wsa-enterprise-backend` | `backend`, `queue`, `scheduler` |
| `wsa-enterprise-frontend` | `frontend` |
| `wsa-enterprise-nginx` | `nginx` (TLS Dockerfile.prod) |

**Tags:**

| Tag | When |
| --- | --- |
| `main` | Latest merge to `main` (deploy default) |
| `<full-git-sha>` | Immutable commit reference |
| `sha-<run_number>` | CI run sequence tag |

---

## Production host setup (one-time)

1. Linux host with Docker and Docker Compose v2.
2. Clone this repository (e.g. `/opt/wsa-enterprise`).
3. Configure `.env` and `backend/.env` (see M12.1 TLS guide).
4. Run `./scripts/init-letsencrypt.sh` for TLS certificates.
5. Authenticate to GHCR:

```bash
echo "$GHCR_TOKEN" | docker login ghcr.io -u YOUR_GITHUB_USER --password-stdin
```

Use a GitHub PAT with `read:packages` scope, or configure package visibility as public.

6. Verify manual deploy:

```bash
IMAGE_TAG=main ./scripts/deploy-production.sh
```

---

## GitHub configuration

### Repository secrets (Deploy Production workflow)

| Secret | Description |
| --- | --- |
| `PROD_HOST` | Production server hostname or IP |
| `PROD_USER` | SSH username |
| `PROD_SSH_KEY` | Private SSH key (deploy key) |
| `PROD_DEPLOY_PATH` | Absolute path to repo clone on host |
| `GHCR_PULL_TOKEN` | PAT with `read:packages` for host pull via deploy script |

### GitHub Environment

Create environment **`production`** with required reviewers (recommended) before enabling `deploy-production.yml`.

### Publish workflow

Uses built-in `GITHUB_TOKEN` with `packages: write` — no extra secret required for publish on `main`.

---

## Deploy from GitHub Actions

1. Merge changes to `main` (triggers image publish).
2. Open **Actions → Deploy Production → Run workflow**.
3. Enter `image_tag` (`main` or full commit SHA).
4. Workflow SSHs to host, resets to `origin/main`, runs `deploy-production.sh`.

The deploy script:

1. Optionally logs in to GHCR (`GHCR_PULL_TOKEN`).
2. Pulls tagged images.
3. `docker compose up -d --no-build --wait`.
4. `php artisan migrate --force` (no seed).
5. Runs HTTPS smoke checks.

---

## Rollback (manual, M12.5 expands)

Redeploy a previous immutable tag:

```bash
IMAGE_TAG=<previous-git-sha> ./scripts/deploy-production.sh
```

---

## Troubleshooting

| Issue | Action |
| --- | --- |
| `pull access denied` | Run `docker login ghcr.io` on host; check `GHCR_PULL_TOKEN` |
| Smoke fails on health | Check nginx/certbot containers; verify `DOMAIN` DNS |
| Migrate fails | Inspect `docker compose logs backend`; restore from backup before retry |
| Wrong frontend API URL | Frontend uses relative `/api/v1` by default when same-origin |

---

## Out of scope (M12.2)

- `.env.production.example` templates → M12.3
- Monitoring runbook → M12.4
- Automated rollback / backup → M12.5
- Stripe / payment providers → excluded

See also [tls-production.md](tls-production.md), [docker-production.md](docker-production.md), [deployment.md](deployment.md).
