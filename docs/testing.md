# WSA Enterprise Testing Guide

## Database isolation (Phase 10)

**Never run `php artisan test` against the Docker staging database (`wsa_enterprise`).**  
PHPUnit uses `RefreshDatabase`, which migrates and wipes tables on every run.

| Environment | Database | How to run |
|-------------|----------|------------|
| Local PHPUnit (default) | SQLite `:memory:` | `cd backend && php artisan test` |
| CI / Docker isolated | PostgreSQL `wsa_enterprise_test` | GitHub Actions or `./scripts/run-backend-tests.sh` |
| Docker staging | PostgreSQL `wsa_enterprise` | Migrations/seed only — **no PHPUnit** |

### Safeguard

`Tests\Support\DatabaseSafety` refuses to run when `DB_DATABASE` is listed in `FORBIDDEN_TEST_DATABASES` (default: `wsa_enterprise`).

### Docker isolated tests

```bash
./scripts/run-backend-tests.sh
# Windows: .\scripts\run-backend-tests.ps1
```

This creates `wsa_enterprise_test` if needed and runs `docker compose --profile test run --rm backend-test`.

### Configuration files

- `backend/.env.testing.example` — isolated PostgreSQL test settings
- `backend/phpunit.xml` — SQLite in-memory defaults + forbidden DB list
- `backend/config/database.php` — `pgsql_testing` connection

## Frontend

```bash
cd frontend
npm install
npm run lint
npm run build
```

## Mobile

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
```

## OpenAPI

CI validates `docs/openapi.yaml` with `@redocly/cli lint`.

## Staging smoke tests (manual)

After `docker compose up -d`:

1. `GET http://localhost:8081/api/v1/health`
2. Browser login at `http://localhost:8081/` (`admin@wsa.test` / `password`)
3. Direct SPA routes: `/login`, `/` (dashboard)
4. Queue worker: `docker compose logs -f queue`

Do **not** run PHPUnit inside the staging `backend` container without the `backend-test` profile.
