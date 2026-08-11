# Production TLS — Let's Encrypt (M12.1)

**Target:** Single Docker host with TLS terminated at the application nginx container.

---

## Prerequisites

1. DNS `A`/`AAAA` record for your domain pointing to the host public IP.
2. Ports **80** and **443** open to the internet (required for ACME HTTP-01).
3. Root `.env` configured (see `.env.example`):

| Variable | Example | Purpose |
| --- | --- | --- |
| `DOMAIN` | `app.example.com` | TLS certificate + nginx `server_name` |
| `CERTBOT_EMAIL` | `ops@example.com` | Let's Encrypt registration |
| `POSTGRES_PASSWORD` | strong random | Database credential |
| `VITE_API_URL` | `https://app.example.com/api/v1` | Frontend build-time API base |

4. `backend/.env` production values:

| Variable | Example |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://app.example.com` |
| `FRONTEND_URL` | `https://app.example.com` |
| `SESSION_ENCRYPT` | `true` |
| `LOG_LEVEL` | `warning` |

---

## First-time certificate bootstrap

```bash
cp .env.example .env
# Edit DOMAIN, CERTBOT_EMAIL, POSTGRES_PASSWORD, VITE_API_URL

cp backend/.env.example backend/.env
# Edit production APP_* and DB_* values

chmod +x scripts/init-letsencrypt.sh
./scripts/init-letsencrypt.sh
```

The script:

1. Creates a short-lived dummy certificate so nginx can start.
2. Starts nginx on ports 80/443.
3. Runs Certbot `certonly --webroot` for the real certificate.
4. Reloads nginx and starts the full production stack.

---

## Automatic renewal

The `certbot` service in `docker-compose.prod.yml` runs:

```text
certbot renew --webroot -w /var/www/certbot
```

every 12 hours. Nginx serves `/.well-known/acme-challenge/` from the shared `certbot_www` volume.

After renewal, reload nginx manually if needed:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload
```

Automatic post-renewal nginx reload is **not** implemented in Phase 12. The certbot sidecar runs `certbot renew` only; operators reload nginx after successful renewal when required.

---

## TLS configuration

| Setting | Location |
| --- | --- |
| Protocols | TLS 1.2 + 1.3 (`nginx/ssl-params.conf`) |
| HSTS | `max-age=31536000; includeSubDomains` |
| HTTP → HTTPS | Port 80 redirects all traffic except ACME webroot |
| Forwarded headers | nginx sets `X-Forwarded-Proto: https` to frontend |
| Laravel trust | `trustProxies(at: '*')` in `bootstrap/app.php` |
| URL scheme | `URL::forceScheme('https')` when `APP_URL` is HTTPS in production |

Certificate paths inside nginx:

```text
/etc/letsencrypt/live/${DOMAIN}/fullchain.pem
/etc/letsencrypt/live/${DOMAIN}/privkey.pem
```

Stored in Docker volume `certbot_conf`.

---

## Production Compose usage

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d
```

Production differences from development:

| Aspect | Development | Production override |
| --- | --- | --- |
| TLS | HTTP `:8081` | HTTPS `:443`, redirect `:80` |
| Postgres/Redis ports | Published | Internal network only |
| Backend bind mount | Yes | No — baked image only |
| Nginx config | `default.conf` | `default.prod.conf.template` |
| Certbot | — | Renewal sidecar |

---

## Verification

```bash
curl -fsS https://${DOMAIN}/api/v1/health
curl -fsS https://${DOMAIN}/up
```

Expect JSON `{"status":"ok"}` from health and HTTP 200 from `/up`.

---

## Troubleshooting

| Issue | Action |
| --- | --- |
| nginx fails to start | Ensure dummy/real certs exist under `certbot_conf` volume; run init script |
| ACME challenge fails | Confirm DNS, firewall, and port 80 reachable |
| Mixed content in browser | Set `VITE_API_URL` to HTTPS before frontend build |
| Wrong scheme in URLs | Set `APP_URL=https://...` and confirm TrustProxies active |

---

## Out of scope (M12.1)

- GHCR image push (M12.2) — see [deploy-production.md](deploy-production.md)
- Secrets manager integration (M12.3) — see [production-secrets.md](production-secrets.md)
- Automated post-renewal nginx reload hook — manual reload documented above
- Rollback procedures (M12.5) — see [phase-12-m12-5-rollback-runbook.md](phase-12-m12-5-rollback-runbook.md)

See also [docker-production.md](docker-production.md) and [deployment.md](deployment.md).
