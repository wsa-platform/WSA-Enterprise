# Phase 11 M7 — Notifications, Audit & Observability

**Scope:** Notification pipeline, expanded audit coverage, request tracing.

## Backend

- `NotificationService` + `SendNotificationJob`
- `notification_deliveries` table tracks channel delivery state
- AI completion/failure in-app notifications
- Security notification foundation (cross-tenant attempt)
- Audit `request_id` column + middleware integration
- Enhanced `LogApiRequests` with duration_ms

## Audit actions expanded

| Action | Trigger |
|--------|---------|
| `organization.settings.updated` | Billing operational settings PUT |
| `team.created` / `team.member_*` | Existing team controller |
| `billing.subscription.*` | Existing subscription service |
| `security.cross_tenant_denied` | Organization middleware |

## API (unchanged routes)

- `GET /api/v1/notifications`
- `POST /api/v1/notifications/{id}/read`

## Frontend

- `frontend/src/features/notifications/` module export

## Configuration

| Variable | Default |
|----------|---------|
| `NOTIFICATIONS_ENABLED` | `true` |
| `NOTIFICATIONS_QUEUE` | `default` |
| `NOTIFICATIONS_EMAIL_ENABLED` | `false` |

Live email delivery is logged only when enabled — no external provider in M7.
