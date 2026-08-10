# M4 Enterprise Dashboard — Frontend Architecture Audit

**Date:** 2026-08-10  
**Branch:** `phase-11-m4-enterprise-dashboard`

## 1. Existing frontend architecture

| Area | Implementation |
|------|----------------|
| Framework | React 19 + TypeScript + Vite 8 |
| Routing | React Router v7 nested layout (`ProtectedShell` → `AppShell` → pages) |
| Auth | `AuthContext` — token/user/org in `localStorage` |
| API | Modular fetch client (`src/api/client.ts`) with Bearer + `X-Organization-Id` |
| Styling | Global CSS (`App.css`, `index.css`) — no Tailwind/UI kit |
| State | Local component state + `useAsyncData` hook; no Redux/Zustand |

## 2. Existing API capabilities (pre-M4)

- Dashboard: `GET /dashboard`, `GET /platform/workflow-summary`
- Access: users, roles, permissions, audit logs
- Teams: list/create/member add/remove
- AI: provider, requests CRUD, usage, cancel
- Notifications: list, mark read
- Health: `GET /health`

## 3. Reusable components (pre-M4)

- `AppShell`, `Panel`, `RecordList`, `ModuleTabs`
- `OrgSwitcher`, `ConfirmDialog`, `RecordForm`

## 4. Missing dashboard capabilities (addressed in M4)

- Permission-aware navigation
- Enterprise overview (users, teams, AI stats, audit activity)
- Dedicated admin pages (users, teams, roles, audit)
- AI workspace with quota, async polling, cancellation
- Settings and notifications UI
- Breadcrumbs, tables, empty/error/loading states

## 5. Security considerations

- Frontend permission checks are **UX only** via `PermissionProvider` + `GET /platform/me`
- Backend RBAC remains authoritative (`access.manage`, `ai.use`, etc.)
- Audit detail view redacts password/token/secret fields client-side
- AI input hidden by backend; provider secrets never exposed

## 6. Proposed M4 architecture

```
AuthProvider → SessionGuard → ProtectedShell → PermissionProvider → AppShell
  ├── Dashboard (access-summary + existing dashboard APIs)
  ├── Enterprise admin (/admin/*) — access.manage
  ├── AI workspace (/ai/workspace) — ai.use
  ├── Notifications — platform.view
  └── Existing agricultural/business modules (unchanged)
```

**New backend (minimal):**

- `GET /platform/me` — current user permissions for active org
- `GET /platform/access-summary` — enterprise dashboard metrics
- `GET /teams/{team}` — team detail with members

**Unavailable (explicitly marked in UI):**

- Organization settings API (`organization_settings` table exists; no REST route yet)
