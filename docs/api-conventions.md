# WSA Enterprise API Conventions (v1)

This document describes HTTP API conventions for `/api/v1`. Existing clients remain compatible; new endpoints should follow these patterns.

## Base URL

| Environment | Base URL |
|-------------|----------|
| Docker staging | `http://localhost:8081/api/v1` |
| Vite dev (proxy) | `/api/v1` (same origin through Nginx) |
| Mobile | `--dart-define=API_URL=http://localhost:8081/api/v1` |

## Authentication

```
Authorization: Bearer {sanctum_token}
X-Organization-Id: {organization_id}   # required for tenant-scoped endpoints
Accept: application/json
Content-Type: application/json         # for POST/PUT/PATCH bodies
```

Registration is disabled by default (`ALLOW_REGISTRATION=false`).

## Response formats

### Legacy success (most existing endpoints)

Raw JSON object or array:

```json
{ "id": 1, "name": "Example" }
```

Paginated Laravel responses (when `page`/`per_page` provided):

```json
{
  "data": [],
  "current_page": 1,
  "last_page": 1,
  "per_page": 15,
  "total": 0
}
```

### Envelope success (new endpoints via `ApiResponse`)

```json
{
  "data": { "status": "ok" },
  "meta": {}
}
```

Paginated envelope:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 0
  }
}
```

### Validation error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Authentication failure (401)

Sanctum returns 401 when no/invalid token.

### Authorization failure (403)

```json
{ "message": "You do not have permission to perform this action." }
```

### Not found (404)

```json
{ "message": "Resource not found." }
```

Cross-tenant foreign IDs also return **404** (not 403) to avoid resource enumeration.

### No content (204)

Empty body for successful deletes and logout.

## HTTP status codes

| Code | Usage |
|------|-------|
| 200 | Successful GET/PUT/PATCH |
| 201 | Successful POST (resource created) |
| 202 | Accepted (async processing, e.g. AI when `AI_ASYNC_DISPATCH=true`) |
| 204 | Successful DELETE/logout |
| 401 | Unauthenticated |
| 403 | Forbidden (permission or org membership) |
| 404 | Resource not found |
| 422 | Validation failed |
| 429 | Rate limit exceeded |
| 500 | Server error (debug details only when `APP_DEBUG=true`) |

## Rate limits

| Group | Limit |
|-------|-------|
| Auth (`/auth/login`, `/auth/register`) | 20/minute |
| Authenticated API | 120/minute |

## Versioning

- Current version: **v1** (`/api/v1/*`)
- Breaking changes require a new version prefix
- Health check: `GET /api/v1/health` → `{ "status": "ok" }`

## OpenAPI

Machine-readable foundation: [`docs/openapi.yaml`](openapi.yaml)

## Background jobs (Phase 9)

- Default queue connection: `redis` (`QUEUE_CONNECTION=redis`)
- Docker Compose runs a dedicated `queue` worker service
- AI requests are synchronous by default; set `AI_ASYNC_DISPATCH=true` to return **202 Accepted** and process via `ProcessAiRequest`
- Inspect failed jobs: `php artisan queue:failed` / `queue:retry`

## Audit logging

Security-sensitive actions are recorded in `audit_logs`. Sensitive fields (passwords, tokens) are never stored. Query via `GET /api/v1/audit-logs` (`access.manage` permission).
