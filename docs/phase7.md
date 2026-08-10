# Phase 7 — Authorization, UX, Regression & Production Readiness

Phase 7 completes tenant-safe authorization (A–D), Flutter functional parity (E), full regression coverage (F), and production-readiness review (G).

## Scope delivered

| Track | Summary |
| --- | --- |
| **A–D** (merged) | PermissionService, policies, tenant FK validation, form requests, rate limits, React org switcher + CRUD, Business UI |
| **E** | Flutter CRUD/forms, org switcher, full module navigation, session restore + 401 handling |
| **F** | Backend E2E workflow test, Phase 7 regression suite, CI runs `flutter test` |
| **G** | Production-readiness report in `docs/production-readiness.md` |

## Flutter mobile (Phase 7 E)

The Flutter app mirrors the React web workflow:

1. Sign in → organization selection (dropdown)
2. Dashboard with workflow metrics
3. Farms (6 tabs), Crops (6 tabs), Soil (3 tabs)
4. Diagnosis (submit case + 4 tabs)
5. Training (create course + tabs)
6. Library (publish item + search)
7. AI (provider notice + submit request)
8. Business (catalog/directory/inventory/sales)

### API usage

- Base URL: `API_URL` dart define (default `http://localhost:8081/api/v1`)
- Auth: Sanctum bearer token persisted in `SharedPreferences`
- Tenant: `X-Organization-Id` header on all authenticated requests
- CRUD: POST/PUT/DELETE on existing module paths (same contracts as web)

### Run locally

```bash
cd mobile
flutter create .
flutter pub get
flutter analyze
flutter test
flutter run --dart-define=API_URL=http://localhost:8081/api/v1
```

## Regression testing (Phase 7 F)

Backend feature tests:

- `AgriculturalModuleTest` — Phase 4
- `Phase5ModuleTest` — diagnosis, training, library, AI
- `Phase6ModuleTest` — tenant header, platform endpoints
- `Phase7ModuleTest` — authorization matrix
- `BusinessModuleTest` — business tenant isolation
- `AuthAccessTest` — login and 401
- `Phase7E2EWorkflowTest` — full agricultural workflow + cross-tenant 403

CI (`.github/workflows/ci.yml`):

- `backend`: `php artisan test`
- `frontend`: `npm run build`
- `mobile`: `flutter analyze` + `flutter test`

## Demo credentials

`admin@wsa.test` / `password` (Green Valley Farm / `wsa-demo` when seeded)

See `docs/production-readiness.md` for deployment checklist and remaining gaps.
