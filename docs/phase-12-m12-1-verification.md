# Phase 12 M12.1 — Production Docker + TLS

**Date:** 2026-08-11  
**Branch:** `phase-12-m12-1-production-docker-tls`  
**Scope:** Single Docker host production configuration with Let's Encrypt TLS

---

## Deliverables

| Item | Location |
| --- | --- |
| Production Compose override | `docker-compose.prod.yml` |
| TLS nginx template | `nginx/default.prod.conf.template` |
| TLS nginx image | `nginx/Dockerfile.prod` |
| SSL parameters | `nginx/ssl-params.conf` |
| Certbot renewal service | `docker-compose.prod.yml` (`certbot`) |
| Initial cert bootstrap | `scripts/init-letsencrypt.sh` |
| TLS runbook | `docs/tls-production.md` |
| Trusted proxy middleware | `backend/bootstrap/app.php` |
| HTTPS URL forcing (production) | `backend/app/Providers/AppServiceProvider.php` |
| Frontend prod API URL build arg | `frontend/Dockerfile` |
| Proxy trust regression test | `Phase12M121TrustedProxyTest.php` |

---

## Production topology

```
Internet :443/:80
    → nginx (TLS termination, HSTS)
        ├── /api/* → backend PHP-FPM (no bind mount)
        └── /      → frontend static build
    certbot (renewal loop, webroot ACME)
    postgres, redis (internal network only)
    queue, scheduler
```

---

## Verification

| Check | Result |
| --- | --- |
| Dev Compose config | PASS (`docker compose config`) |
| Prod Compose config | PASS (requires `DOMAIN`, `POSTGRES_PASSWORD` in `.env`) |
| Backend full suite | **PASS** | 153 tests, 643 assertions |
| `Phase12M121TrustedProxyTest` | **PASS** | Forwarded headers + HTTPS URL forcing |
| CI docker-validate (prod) | Run on PR |

---

## Known limitations (M12.1)

1. Initial cert requires `./scripts/init-letsencrypt.sh` on the production host with public DNS.
2. Secrets templates and GHCR deploy workflow deferred to M12.2–M12.3.
3. Rollback runbook deferred to M12.5.
