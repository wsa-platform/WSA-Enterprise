# Phase 16 — Product Completion & Internationalization (M16)

**Baseline:** `main` @ M15 merge (PR #24, `78ce195`)  
**Branch:** `phase-16-m16-internationalization`

---

## Objective

Complete enterprise administration UX and deliver platform-wide internationalization for exactly four languages — Arabic (RTL), English, Turkish, and French — while preserving all M13/M14/M15 functionality and organization isolation.

---

## M16 Work Packages

| WP | Scope | Status |
| --- | --- | --- |
| WP1 | Pre-flight audit from M15 `main` | Complete |
| WP2 | Frontend i18n (`i18next` + 4 locale files) | Implemented |
| WP3 | RTL/LTR layout (Arabic RTL, en/tr/fr LTR) | Implemented |
| WP4 | Language selector + `localStorage` persistence | Implemented |
| WP5 | Enterprise admin UI string coverage | Implemented |
| WP6 | Backend `Accept-Language` middleware + locale validation | Implemented |
| WP7 | Tests (frontend key parity + backend M16 suite) | Implemented |
| WP8 | Documentation + mobile audit | Implemented |

---

## i18n Architecture

### Frontend

- **Library:** `i18next` + `react-i18next`
- **Config:** `frontend/src/i18n/config.ts`
- **Locales:** `frontend/src/i18n/locales/{en,ar,tr,fr}.json`
- **Supported languages:** `en`, `ar`, `tr`, `fr` only (no German)
- **Default / fallback:** English (`en`)
- **Persistence:** `localStorage` key `wsa.language`
- **Document sync:** `document.documentElement.lang` and `dir` updated on change
- **API requests:** `Accept-Language` header via `buildHeaders()` in `frontend/src/api/client.ts`
- **Error mapping:** `frontend/src/i18n/apiErrors.ts` maps HTTP errors to translated messages
- **Selector:** `LanguageSelector` in `AppShell` header

### RTL / LTR

| Language | Code | Direction |
| --- | --- | --- |
| Arabic | `ar` | RTL |
| English | `en` | LTR |
| Turkish | `tr` | LTR |
| French | `fr` | LTR |

RTL rules live in `frontend/src/App.css` under `html[dir="rtl"]` selectors covering sidebar active indicator, tables, forms, pagination, breadcrumbs, confirm dialogs, and header actions.

### Backend

- **Middleware:** `SetLocaleFromHeader` reads `Accept-Language` and sets `app()->setLocale()` to the first supported primary tag (`en`, `ar`, `tr`, `fr`), falling back to `en`.
- **Organization settings:** `operations.locale` validated against the same four codes on `PUT /api/v1/organization/settings`.

---

## Administration (M16 audit)

Existing M14/M15 enterprise admin preserved and i18n-wrapped:

- Organization profile/settings (locale select restricted to supported languages)
- Users, roles, permissions, teams
- Invitations (invite / accept / revoke)
- Sessions (list / revoke in Settings)
- Monitoring, analytics, API clients, audit
- Authorization boundaries unchanged (`access.manage`, `monitoring.view`, org context via `X-Organization-Id`)

---

## Security (M16)

- No weakening of RBAC or organization isolation
- Locale validation prevents arbitrary `operations.locale` values
- M15 invitation/session endpoints regression-tested in `Phase16M16InternationalizationTest`
- No secrets or production credentials exposed

---

## Mobile Status (Phase 6 audit)

The Flutter app (`mobile/`) provides operational module access only:

| Area | Mobile state | M16 decision |
| --- | --- | --- |
| Auth / login | Basic token storage + session restore | Web-only admin; mobile login unchanged |
| Org switcher | Present | No i18n in mobile for M16 |
| Dashboard / modules | Farms, AI, notifications, profile | Operational parity; no enterprise admin |
| Users / roles / invitations | Not implemented | **Web-only** — schedule for future mobile milestone |
| Monitoring / analytics / audit | Not implemented | **Web-only** |
| i18n / RTL | Not implemented | **Future milestone** (after web i18n stabilizes) |

**Recommendation:** Keep enterprise administration web-only through M16. Add mobile i18n and limited admin (notifications, profile settings) in a dedicated mobile milestone.

---

## Tests

| Suite | Location | Coverage |
| --- | --- | --- |
| Frontend i18n | `frontend/src/i18n/i18n.test.ts` | 4 locales, RTL, key parity, fallback |
| Frontend API headers | `frontend/src/api/client.test.ts` | `Accept-Language` header |
| Backend M16 | `backend/tests/Feature/Phase16M16InternationalizationTest.php` | Locale middleware, settings validation, M15 regression |

Run:

```bash
cd frontend && npm run build && npm test
docker compose --profile test run --rm --no-deps backend-test php artisan test --filter=Phase16M16InternationalizationTest
```

---

## Known Limitations

- Backend API `message` strings remain English; locale middleware sets Laravel locale for future validation translations
- Mobile app has no M16 i18n
- Turkish/French may share English fallbacks for keys added during bulk page migration until fully reviewed
- Demo login hint (`loginDemo.ts`) remains environment-specific English

---

## Deferred (not M16)

- Mobile enterprise admin + mobile i18n
- Backend translated validation catalog for all endpoints
- Browser E2E across all four languages
- SSO / MFA enforcement
- Real AI / billing providers

---

## Key documents

- [phase-15-m15-verification.md](phase-15-m15-verification.md)
- [phase-16-m16-verification.md](phase-16-m16-verification.md)
