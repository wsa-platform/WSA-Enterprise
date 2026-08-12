# Phase 16 — M16 Verification

**Branch:** `phase-16-m16-internationalization`  
**Baseline:** M15 merged to `main` (PR #24)

---

## Pre-flight (Phase 1)

| Check | Result |
| --- | --- |
| Branch from `main` after M15 | ✓ |
| M15 merge present (`78ce195`) | ✓ |
| M13/M14/M15 code preserved | ✓ |
| No unrelated deletions | ✓ |

---

## Internationalization (Phase 2)

| Requirement | Verification |
| --- | --- |
| Arabic (`ar`) full RTL | `html[dir="rtl"]` CSS + `isRtlLanguage('ar')` |
| English (`en`) LTR | Default language |
| Turkish (`tr`) LTR | Supported + translated locale file |
| French (`fr`) LTR | Supported + translated locale file |
| No German | Not in `SUPPORTED_LANGUAGES` |
| Language selector visible | `LanguageSelector` in `AppShell` header |
| Persistence | `localStorage` key `wsa.language` |
| Fallback | `fallbackLng: 'en'` in i18next config |
| Translation keys (not hard-coded) | All major pages/components migrated |
| Login / auth | `LoginPage`, `AcceptInvitationPage` |
| Dashboard / navigation | `DashboardPage`, `AppShell` nav + breadcrumbs |
| Enterprise admin areas | Users, roles, teams, audit, monitoring, analytics, API clients |
| Organization / settings | `OrganizationPage`, `SettingsPage` |
| Invitations / sessions | `UsersPage`, `SettingsPage` sessions panel |
| Errors / empty / confirm UI | `UiPrimitives`, `ConfirmDialog`, `DataTable`, `PaginationBar` |
| Key parity across locales | `frontend/src/i18n/i18n.test.ts` |

---

## Administration (Phase 3)

| Area | Status |
| --- | --- |
| Organization management | Existing APIs + i18n UI |
| Organization settings | Locale restricted to `en/ar/tr/fr` |
| User / role / permission admin | M14 preserved + i18n |
| Invitations | M15 preserved + i18n |
| Session management | M15 preserved + i18n |
| Monitoring / analytics / API clients | M14/M15 preserved + i18n |
| Audit | M14 preserved + i18n |
| Authorization boundaries | Unchanged |

---

## Security (Phase 4)

| Check | Result |
| --- | --- |
| Authentication (Sanctum) | Unchanged |
| RBAC / org-scoped access | Unchanged |
| Invitation security | M15 regression test passes |
| Session revocation | M15 regression test passes |
| Locale validation (no arbitrary values) | `PUT organization/settings` rejects `de` |
| Cross-org isolation | No M16 changes to isolation middleware |

---

## Frontend Quality (Phase 5)

| Check | Result |
| --- | --- |
| `npm run build` | Pass |
| Routes intact | All M15 routes preserved |
| RTL/LTR CSS | `App.css` RTL block |
| Loading / error / empty states | Translated via shared components |

---

## Mobile (Phase 6)

Documented in [phase-16-roadmap.md](phase-16-roadmap.md#mobile-status-phase-6-audit). No mobile rebuild in M16.

---

## Tests (Phase 7)

### Frontend

```text
npm run build          → PASS
npm test               → PASS (incl. i18n key parity, client Accept-Language)
```

### Backend

```text
Phase16M16InternationalizationTest (6 tests) → PASS
  - Accept-Language sets locale
  - Unsupported language falls back to en
  - Supported locales constant
  - Organization settings locale validation
  - M15 invitations + sessions regression
```

---

## API / OpenAPI

- No breaking API changes
- `Accept-Language` request header documented implicitly via existing health/org settings endpoints
- OpenAPI spec unchanged structurally (no new routes)

---

## Architecture Decisions

1. **i18next over custom solution** — ecosystem standard, JSON locale files, React hooks
2. **Frontend-first i18n** — user-facing strings in locale JSON; backend locale for future validation messages
3. **Single source of supported languages** — frontend `languages.ts` + backend `SetLocaleFromHeader::SUPPORTED_LOCALES`
4. **RTL via document `dir`** — avoids per-component direction props
5. **Mobile deferred** — web admin completion prioritized; Flutter remains operational-only

---

## Sign-off Checklist

- [x] Four languages only: ar, en, tr, fr
- [x] Arabic RTL verified in CSS + language helper
- [x] Language selector + persistence
- [x] Enterprise admin i18n coverage
- [x] M15 regression tests green
- [x] Documentation complete
- [ ] PR opened and CI green (pending push)
