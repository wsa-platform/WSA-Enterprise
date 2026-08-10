# E2E & Regression Testing

**Last updated:** Phase 8 (2026-08-09)

## Strategy

WSA-Enterprise uses layered automated testing:

| Layer | Tool | Scope |
| --- | --- | --- |
| Backend API | PHPUnit (`php artisan test`) | Auth, permissions, CRUD, tenant isolation, workflows |
| Frontend | TypeScript build + oxlint | Compile-time correctness |
| Mobile | `flutter analyze` + `flutter test` | API client, widgets, unwrap logic |
| CI | GitHub Actions | All layers on push/PR |

Manual device E2E is documented but not required for CI green.

## Backend test suites

### Phase 4–6 regression

- `AgriculturalModuleTest` — farm, crop, soil modules
- `BusinessModuleTest` — catalog, commerce, operations
- `Phase5ModuleTest`, `Phase6ModuleTest` — training, library, diagnosis, AI

### Phase 7

- `Phase7ModuleTest` — authorization integration
- `Phase7E2EWorkflowTest` — agricultural workflow + cross-tenant 403
- `Phase7FullRegressionTest` — combined module smoke
- `AuthAccessTest` — login and org access

### Phase 8

- `Phase8SecurityTest` — registration gate, task IDOR, role scoping, pagination, profile
- `Phase8ComprehensiveWorkflowTest` — full user journey including logout/token invalidation

## Workflow covered (Phase 8 comprehensive)

```
Sign in (Sanctum actingAs)
  → list organizations
  → dashboard + workflow summary
  → create/update farm
  → crop types, soil analyses
  → diagnosis request
  → training course + enrollment
  → library item + search
  → AI library_qa request
  → cross-tenant dashboard → 403
  → logout → token invalidated → 401
```

## Running tests locally

### Backend (requires PHP 8.3 + PostgreSQL)

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan test
```

### Frontend

```bash
cd frontend
npm install
npm run build
npm run lint
```

### Mobile (requires Flutter SDK)

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
```

### Docker stack (integration)

```bash
docker compose up -d --build
# API at http://localhost:8081/api/v1/health
```

## CI triggers

Push to `main`, `phase-7-*`, or `phase-8-*` branches runs full CI.

## Environment limitations

If PHP, Docker, or Flutter are unavailable locally (e.g. Windows without WSL):

- Rely on CI for authoritative results
- Frontend `npm run build` can still validate TypeScript/React changes
- Document exact missing tooling in phase completion reports

## Adding new E2E coverage

1. Extend `Phase8ComprehensiveWorkflowTest` or add focused Feature test
2. Use `RefreshDatabase` and explicit org/user setup (no dependency on demo seed order)
3. Assert HTTP status codes matching security model (403 vs 404)
4. Preserve backward-compatible list response assertions (array vs paginated)

## Known gaps

- No Playwright/Cypress browser automation in CI
- No Flutter integration tests against live API in CI
- Load/performance testing not automated
