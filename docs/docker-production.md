# Production Docker Compose

Use the default `docker-compose.yml` for local development (bind mounts, hot reload on `:8081`).

For **single-host production** with Let's Encrypt TLS:

```bash
cp .env.example .env
# Set DOMAIN, CERTBOT_EMAIL, POSTGRES_PASSWORD, VITE_API_URL

cp backend/.env.example backend/.env
# Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain

chmod +x scripts/init-letsencrypt.sh
./scripts/init-letsencrypt.sh
```

Or after certificates exist:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d
```

Full TLS guide: [tls-production.md](tls-production.md)

## Services

| Service | Health check | Production notes |
| --- | --- | --- |
| `backend` | `php artisan about` (Laravel boot) | No bind mounts; code baked in image |
| `queue` | `queue:work` process running | No bind mounts |
| `scheduler` | `schedule:work` process running | No bind mounts |
| `frontend` | HTTP GET `/` | Built with `VITE_API_URL` |
| `nginx` | HTTPS `GET /api/v1/health` | TLS on `:443`, redirect `:80` |
| `certbot` | Renewal loop every 12h | Webroot ACME challenge |
| `postgres` | `pg_isready` | **Not** exposed on host ports |
| `redis` | `redis-cli ping` | **Not** exposed on host ports |

## Production vs development

| Aspect | Development | Production (`docker-compose.prod.yml`) |
| --- | --- | --- |
| Entry URL | `http://localhost:8081` | `https://${DOMAIN}` |
| Postgres/Redis | Ports published | Internal network only |
| Source bind mounts | Yes | Removed |
| TLS | None | Let's Encrypt + HSTS |
| Cert renewal | — | `certbot` sidecar |

## Notes

- Set real secrets via `.env` and `backend/.env` (never commit).
- Rebuild images after deploys (`--build`).
- Scale queue workers by duplicating the `queue` service or using an orchestrator.
- Phase 12 M12.2 adds GHCR pull override and SSH deploy automation; M12.3 adds secrets templates.

See also [deployment.md](deployment.md), [deploy-production.md](deploy-production.md), [tls-production.md](tls-production.md), and [testing.md](testing.md).
