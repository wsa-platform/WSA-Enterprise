# Production Docker Compose

Use the default `docker-compose.yml` for local development (bind mounts, hot reload).

For production-like deployments, combine the base file with the override:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up --build -d
```

## Services

| Service | Health check |
| --- | --- |
| `backend` | `php artisan about` (Laravel boot) |
| `queue` | `queue:work` process running |
| `scheduler` | `schedule:work` process running |
| `frontend` | HTTP GET `/` |
| `nginx` | HTTP GET `/api/v1/health` |
| `postgres` | `pg_isready` |
| `redis` | `redis-cli ping` |

## Notes

- The override removes backend source bind mounts so containers run baked-in code. Rebuild images after deploys.
- Set real secrets via `backend/.env` (never commit production credentials).
- Scale queue workers by duplicating the `queue` service or using an orchestrator.
- Laravel scheduler requires the `scheduler` service (`php artisan schedule:work`).

See also [deployment.md](deployment.md) and [testing.md](testing.md).
