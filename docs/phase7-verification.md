# Phase 7 E-F-G Verification Matrix

**Branch:** `phase-7-efg`  
**Date:** 2026-08-09  
**CI run (pre-final commit):** [31315508186](https://github.com/wsa-platform/WSA-Enterprise/actions/runs/31315508186) — all jobs green

This document records what was verified automatically, through API-level E2E tests, and what requires manual device/emulator validation.

---

## Environment limitations (honest assessment)

| Capability | Available locally | Notes |
| --- | --- | --- |
| PHP / `php artisan test` | No | Verified via GitHub Actions backend job |
| Flutter SDK / emulator | No | Verified via GitHub Actions mobile job |
| Docker stack | No | Manual UI E2E against `:8081` not run in this session |
| Frontend `npm run build` | Yes | Verified locally |

**Manual mobile/device E2E was not performed** in this environment because Flutter SDK and a running API stack were unavailable. API contract and workflow behavior are covered by automated backend tests and Flutter unit/widget tests.

---

## Verification checklist

| # | Requirement | Method | Result |
| --- | --- | --- | --- |
| 1 | Sign in | `AuthAccessTest`, Flutter widget test (login screen) | **Pass (automated)** |
| 2 | Session restore | `ApiClient.restoreSession()` + `/user` probe; 401 clears session | **Pass (code + unit tests)** |
| 3 | Organization switching | `OrgSwitcher` + `X-Organization-Id`; Phase 6/7 backend tests | **Pass (automated API)** / **Manual mobile not run** |
| 4 | Tenant isolation / 403 | Phase6/7/Business/FullRegression tests | **Pass (automated)** |
| 5 | Farm / crop / soil workflows | `Phase7E2EWorkflowTest`, `AgriculturalModuleTest`, mobile CRUD forms | **Pass (automated API + code review)** |
| 6 | Diagnosis workflow | E2E POST `/diagnosis/requests`; mobile `DiagnosisScreen` | **Pass (automated API + code review)** |
| 7 | Training workflow | E2E POST `/training/courses`; mobile create course form | **Pass (automated API + code review)** |
| 8 | Library search/publish | E2E POST `/library/items`; mobile search + publish form | **Pass (automated API + code review)** |
| 9 | AI request + normalized response | E2E asserts `provider=mock`, `status=completed`; mobile AI screen | **Pass (automated API + code review)** |
| 10 | Loading / error / empty / session expiry | `AsyncState`, `RecordForm` validation tests; 401 → login redirect | **Pass (widget tests + code review)** |
| 11 | Phase 4/5/6/7 regression | Full backend suite — 47 tests, 193 assertions | **Pass (CI)** |
| 12 | Production-readiness review | `docs/production-readiness.md` | **Complete** |

---

## Backend test coverage (CI)

| Test file | Phase | Purpose |
| --- | --- | --- |
| `AgriculturalModuleTest` | 4 | Farm/crop/soil CRUD + scoping |
| `Phase5ModuleTest` | 5 | Diagnosis, training, library, AI |
| `Phase6ModuleTest` | 6 | Tenant header, platform endpoints |
| `AuthAccessTest` | 7 | Login, 401 |
| `BusinessModuleTest` | 7 | Business tenant isolation |
| `Phase7ModuleTest` | 7 | Viewer read/manage, cross-tenant write |
| `Phase7E2EWorkflowTest` | 7 F | Full agricultural workflow chain |
| `Phase7FullRegressionTest` | 7 F | Consolidated Phase 4–7 smoke |

---

## Manual E2E procedure (for staging/demo)

When Docker stack is running at `http://localhost:8081`:

### Web (React)

1. Open `http://localhost:8081`, sign in `admin@wsa.test` / `password`
2. Confirm org switcher loads organizations
3. Create farm → verify list refresh
4. Submit diagnosis case → verify requests tab
5. Create training course, library item, AI query
6. Switch org (if multi-org user) → verify data changes

### Mobile (Flutter)

```bash
cd mobile && flutter run --dart-define=API_URL=http://localhost:8081/api/v1
```

Repeat steps 1–6 on device/emulator.

### Cross-tenant negative test

Use API client with valid token but foreign `X-Organization-Id` → expect **HTTP 403**.

---

## Flutter features delivered (Phase 7 E)

- Expanded `ApiClient`: CRUD, org switch, error parsing, session expiry handling
- 9-module navigation (drawer + bottom nav)
- CRUD forms: farms, crops, soil, diagnosis, training, library, AI, business
- Org switcher, logout, user profile in drawer
- Loading/error/empty states via `AsyncState`
- Record detail bottom sheet on list tap
- Farm delete (permission-gated)

---

## Outstanding before PR merge to main

- [ ] Manual mobile E2E on emulator/device (not done in CI agent environment)
- [ ] User approval to create Pull Request
- [ ] Post-merge: configure production secrets, HTTPS, remove demo credentials
