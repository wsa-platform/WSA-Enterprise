# Phase 17 — M17 Verification

**Branch:** `phase-17-m17-agricultural-ecosystem`  
**Baseline:** M16 merged to `main`

---

## Pre-flight

| Check | Result |
| --- | --- |
| Branch targets M17 agricultural ecosystem | ✓ |
| Prior M16 routes/pages preserved | ✓ |
| Backend M17 routes present (`/jobs/*`, `/beekeeping/*`, `/ai/assistant/*`) | ✓ |
| Permissions registered in `config/permissions.php` | ✓ |

---

## Frontend API Modules

| Module | File | Endpoints covered |
| --- | --- | --- |
| Jobs | `frontend/src/api/jobs.ts` | talent, search, contact, CV |
| Beekeeping | `frontend/src/api/beekeeping.ts` | profile, apiaries, calendar, plants |
| Assistant | `frontend/src/api/assistant.ts` | conversations, messages |
| Re-export | `frontend/src/api/index.ts` | Public barrel exports |

---

## Pages & Routes

| Requirement | Verification |
| --- | --- |
| Jobs marketplace (search + contact) | `JobsMarketplacePage` @ `/jobs` |
| Talent profile (registration + CV) | `TalentProfilePage` @ `/jobs/talent` |
| Beekeeping dashboard (4 tabs) | `BeekeepingDashboardPage` @ `/beekeeping` |
| AI assistant conversation UI | `AiAssistantPage` @ `/ai/assistant` |
| AI vision image upload | `AiVisionPage` @ `/ai/vision` → `POST /ai/requests` `vision_analysis` |
| App.tsx routes wired | ✓ |
| AppShell nav + breadcrumbs | ✓ |
| `useTranslation()` on all new pages | ✓ |
| `translateApiError` on mutations | ✓ |
| PageHeader / Panel / DataTable / ErrorBanner patterns | ✓ |

---

## i18n (4 locales)

| Requirement | Verification |
| --- | --- |
| Sections: `jobs`, `beekeeping`, `aiAssistant`, `aiVision` | Present in en/ar/tr/fr |
| Nav keys: Jobs, Beekeeping, AI Assistant, AI Vision | `nav.jobs`, `nav.beekeeping`, `nav.aiAssistant`, `nav.aiVision` |
| Key parity en ↔ ar/tr/fr | `frontend/src/i18n/i18n.test.ts` |
| Arabic RTL unchanged (global) | No M17 RTL regressions expected |

---

## OpenAPI

| Tag | Paths documented |
| --- | --- |
| Jobs | `/jobs/talent/me`, CV, candidates, contact-requests, pay |
| Beekeeping | profile, apiaries, hives, calendar/tasks, pollination/plants |
| AI Assistant | conversations + messages |
| AI (existing) | `vision_analysis` via `/ai/requests` POST (pre-existing) |

File: `docs/openapi.yaml`

---

## Permissions & UX Gates

| Page | Gate |
| --- | --- |
| Jobs marketplace | `jobs.view`; contact actions require `jobs.manage` |
| Talent profile | `jobs.talent.register` / `jobs.talent.manage` |
| Beekeeping | `beekeeping.view`; writes require `beekeeping.manage` |
| AI Assistant | `ai.assistant` or `ai.use` |
| AI Vision | `ai.vision` or `ai.use` |

---

## Manual Test Plan

1. Sign in with a role that includes M17 permissions.
2. Open **Jobs Marketplace** — run a search; if `jobs.manage`, submit a contact request.
3. Open **Talent Profile** — save profile, upload CV, trigger parse (if permitted).
4. Open **Beekeeping** — save profile; create apiary (requires profile id), calendar task, plant.
5. Open **AI Assistant** — start conversation, send follow-up message.
6. Open **AI Vision** — upload image, submit analysis, verify result panel.
7. Switch language to Arabic — confirm RTL layout and translated labels on all M17 pages.

---

## Build & Test Commands

```bash
cd frontend && npm run build && npm test
```

Expected: build succeeds; i18n key parity tests pass.

---

## Security

| Check | Result |
| --- | --- |
| RBAC unchanged for prior modules | ✓ |
| Organization scoping via `X-Organization-Id` | ✓ |
| Permission gates on frontend pages | ✓ |
| No secrets in new frontend code | ✓ |

---

## Known Gaps (accepted for M17)

- Conversation message history not loaded from server when selecting past conversation
- Vision uses data URL payload size limits from browser/API
- Payment/contact exchange UI uses backend mock transaction flow

---

## Sign-off Checklist

- [ ] Frontend build green
- [ ] i18n tests green
- [ ] Manual smoke on all five M17 pages
- [ ] OpenAPI reviewed against backend routes
- [ ] No regression on existing `/ai/workspace`, farms, admin pages
