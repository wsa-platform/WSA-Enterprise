# Phase 17 — Agricultural Ecosystem (M17)

**Baseline:** M16 internationalization merge on `main`  
**Branch:** `phase-17-m17-agricultural-ecosystem`

---

## Objective

Deliver the M17 agricultural ecosystem product surface on web: jobs marketplace and talent profiles, beekeeping operations dashboard, AI assistant conversations, and AI vision analysis — with full i18n (en/ar/tr/fr), OpenAPI coverage, and documentation while preserving all prior milestone functionality.

---

## M17 Work Packages

| WP | Scope | Status |
| --- | --- | --- |
| WP1 | Backend routes (jobs, beekeeping, AI assistant) | Pre-existing on branch |
| WP2 | Frontend API modules (`jobs`, `beekeeping`, `assistant`) | Implemented |
| WP3 | React pages (marketplace, talent, beekeeping, assistant, vision) | Implemented |
| WP4 | App routes + AppShell navigation | Implemented |
| WP5 | i18n keys (4 locales, key parity) | Implemented |
| WP6 | OpenAPI M17 endpoints | Implemented |
| WP7 | Roadmap + verification docs | Implemented |

---

## Permissions

| Permission | Capability |
| --- | --- |
| `jobs.view` | Search and view public talent candidates |
| `jobs.manage` | Contact requests, matching, payment flow |
| `jobs.talent.register` | Free talent profile registration |
| `jobs.talent.manage` | CV upload/parse, profile management |
| `beekeeping.view` | View profile, apiaries, calendar, plants |
| `beekeeping.manage` | Create/update beekeeping records |
| `ai.assistant` | Conversational assistant (or `ai.use`) |
| `ai.vision` | Vision analysis requests (or `ai.use`) |

---

## Frontend Routes

| Path | Page | Permission gate |
| --- | --- | --- |
| `/jobs` | Jobs marketplace + contact flow | `jobs.view` |
| `/jobs/talent` | Talent registration + CV | `jobs.talent.register` / `jobs.talent.manage` |
| `/beekeeping` | Beekeeping dashboard (tabs) | `beekeeping.view` |
| `/ai/assistant` | AI assistant chat | `ai.assistant` or `ai.use` |
| `/ai/vision` | Image upload → `vision_analysis` | `ai.vision` or `ai.use` |

---

## API Surface (M17)

### Jobs (`/jobs/*`)

- `GET/PUT /jobs/talent/me` — talent profile
- `POST /jobs/talent/me/cv` — CV upload (multipart)
- `POST /jobs/talent/me/cv/parse` — AI CV parse
- `GET /jobs/candidates` — employer search
- `GET /jobs/candidates/{id}` — public profile
- `POST /jobs/candidates/{id}/contact-requests` — contact exchange
- `POST /jobs/contact-requests/{id}/pay` — payment initiation

### Beekeeping (`/beekeeping/*`)

- `GET/PUT /beekeeping/profile`
- `GET/POST /beekeeping/apiaries`
- `GET/POST /beekeeping/apiaries/{apiary}/hives`
- `GET/POST /beekeeping/calendar/tasks`
- `GET/POST /beekeeping/pollination/plants`

### AI Assistant (`/ai/assistant/*`)

- `GET/POST /ai/assistant/conversations`
- `POST /ai/assistant/conversations/{id}/messages`

### AI Vision

- `POST /ai/requests` with `request_type: vision_analysis` and `input.image_url` or `input.image_path`

---

## i18n

New translation sections in all four locale files:

- `nav.*` — Jobs, Beekeeping, AI Assistant, AI Vision, Ecosystem
- `jobs.*` — marketplace and talent profile strings
- `beekeeping.*` — dashboard tab labels and forms
- `aiAssistant.*` — conversation UI
- `aiVision.*` — image upload and analysis UI

Key parity enforced by `frontend/src/i18n/i18n.test.ts`.

---

## Known Limitations

- Assistant conversation history loads list only; message thread replay from server deferred
- Vision analysis uses client-side data URL (`image_url`); dedicated media upload endpoint not in M17
- Mobile app has no M17 screens (web-only)
- Real payment provider integration remains mock/stub from backend services

---

## Deferred (not M17)

- Mobile ecosystem modules
- Browser E2E for M17 flows
- Full conversation message history API
- Dedicated image upload/storage for vision

---

## Key documents

- [phase-16-m16-verification.md](phase-16-m16-verification.md)
- [phase-17-m17-verification.md](phase-17-m17-verification.md)
- [openapi.yaml](openapi.yaml)
