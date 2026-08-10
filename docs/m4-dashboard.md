# M4 Enterprise Dashboard

**Last updated:** Phase 11 Milestone 4 (2026-08-10)

## Overview

M4 delivers a permission-aware enterprise dashboard shell and admin experience on top of the existing Phase 10/11 backend.

## Frontend architecture

- **Shell:** `AppShell` — sidebar sections, mobile menu, breadcrumbs, org switcher, role chip
- **Permissions:** `PermissionProvider` loads `GET /platform/me` per organization change
- **Data loading:** `useAsyncData` hook for page-level fetch/reload/error state
- **Tables:** `DataTable`, `PaginationBar`, `StatusBadge`, `EmptyState`, `ErrorBanner`

## Routing

| Route | Page | Permission |
|-------|------|------------|
| `/` | Enterprise dashboard | `platform.view` |
| `/organization` | Organization profile | `platform.view` |
| `/admin/users` | User management | `access.manage` |
| `/admin/teams` | Team list | `access.manage` |
| `/admin/teams/:id` | Team detail | `access.manage` |
| `/admin/roles` | Roles & permissions | `access.manage` |
| `/admin/audit` | Audit logs | `access.manage` |
| `/ai/workspace` | AI workspace | `ai.use` |
| `/ai/requests/:id` | AI request detail | `ai.use` |
| `/notifications` | Notifications | `platform.view` |
| `/settings` | Settings | `platform.view` |
| `/farms`, `/crops`, … | Existing modules | module permissions |

## API modules

| File | Purpose |
|------|---------|
| `api/platform.ts` | `/platform/me`, `/platform/access-summary` |
| `api/teams.ts` | Team CRUD + members |
| `api/notifications.ts` | Notifications |
| `api/ai.ts` | Usage, cancel, filtered list |
| `api/client.ts` | 401/403/404/422/429 handling, request ID capture |

## RBAC UI

Navigation items are filtered by permissions from `/platform/me`. Unauthorized pages show an error banner; API calls still enforce server-side policies.

## AI workspace

- Submit requests via existing M3 APIs
- Handles sync (201) and async (202 → poll) flows
- Displays quota from `/ai/usage`
- Cancel pending/processing requests
- Request detail page shows output/errors (no provider secrets)

## Responsive behavior

- Desktop: persistent sidebar
- Mobile (≤760px): collapsible menu, scrollable tables, stacked metrics

## Testing

```bash
cd frontend && npm run lint && npm run build
cd .. && docker compose --profile test run --rm backend-test
```

Backend M4 tests: `Phase11M4DashboardTest`

## Related documents

- [m4-dashboard-audit.md](./m4-dashboard-audit.md)
- [multi-tenancy.md](./multi-tenancy.md)
- [security.md](./security.md)
- [ai-platform.md](./ai-platform.md)
