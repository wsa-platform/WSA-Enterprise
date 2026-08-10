# AI Platform

**Last updated:** Phase 11 Milestone 3 (2026-08-10)

## Overview

WSA-Enterprise provides **agricultural decision-support AI** through a provider-abstracted, queue-backed request pipeline. AI outputs are advisory only — not authoritative diagnoses.

---

## Architecture

```
Client → POST /api/v1/ai/requests
       → AiController (RBAC: ai.use, quota check)
       → AiService
         → AiQuotaService (optional org limits)
         → AiProviderResolver → AiProviderInterface
         → AiRequestValidator / AiResponseNormalizer
         → AiUsageRecorder → usage_records
         → AuditService
         → [async] ProcessAiRequest job → Redis queue → queue worker
```

---

## Request Lifecycle

| Status | Meaning |
|--------|---------|
| `pending` | Created; job queued (async) |
| `processing` | Worker acquired lock; provider executing |
| `completed` | Output persisted |
| `failed` | Error captured in `error_message` |
| `cancelled` | Cancelled while pending/processing (`cancelled_at` set) |

Terminal states: `completed`, `failed`, `cancelled` — not reprocessed.

### Sync vs async

```env
AI_ASYNC_DISPATCH=false   # default — returns 201 with completed result
AI_ASYNC_DISPATCH=true    # returns 202 with pending; requires queue worker
```

| Mode | HTTP | Requires queue worker |
|------|------|----------------------|
| Sync | 201 Created | No |
| Async | 202 Accepted | Yes |

Poll: `GET /api/v1/ai/requests/{id}`  
Cancel: `POST /api/v1/ai/requests/{id}/cancel` (pending/processing only)  
Usage: `GET /api/v1/ai/usage`

---

## Supported Request Types

| Type | Purpose |
|------|---------|
| `diagnosis` | Plant disease decision support |
| `library_summary` | Summarize library content |
| `library_qa` | Q&A over library knowledge |
| `training_assistance` | Training content assistance |

---

## Provider Abstraction

```php
interface AiProviderInterface {
    public function complete(string $requestType, array $input): array;
    public function name(): string;
}
```

- Domain code uses `AiService` only — never vendor SDKs in controllers
- `AiProviderResolver` selects provider from config + optional org override (`organization_settings.key = ai.provider`)
- Default provider: `mock` (no external calls)

---

## Quotas & Usage

```env
AI_QUOTA_ENABLED=false              # default — unlimited (backward compatible)
AI_QUOTA_REQUESTS_PER_PERIOD=1000   # when enabled
AI_QUOTA_PERIOD=monthly             # monthly | daily
```

When `AI_QUOTA_ENABLED=true`:

- Each accepted AI request records a `usage_records` row (`metric: ai.requests`)
- Over-quota requests return **429** with `{ message, quota: { limit, used } }`
- Audit event: `ai.quota.exceeded`

`GET /api/v1/ai/usage` returns:

```json
{
  "enabled": true,
  "limit": 1000,
  "used": 42,
  "remaining": 958,
  "period_start": "2026-08-01"
}
```

---

## Rate Limiting

Per-organization throttle on all `/api/v1/ai/*` routes (`throttle:ai-org`):

```env
AI_RATE_LIMIT_PER_MINUTE=30
```

Rate limit key is organization ID — cannot be bypassed by changing user or request ID.

---

## Queue Configuration

```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

Job: `App\Jobs\ProcessAiRequest`

- `ShouldBeUnique` — prevents duplicate concurrent jobs per request ID
- Row-level DB lock during processing
- Idempotent for terminal states
- Skips `cancelled` requests
- `failed()` handler marks record failed + audits

Queue worker entrypoint waits for Redis before starting (Phase 10 fix).

---

## Audit Events

| Action | Trigger |
|--------|---------|
| `ai.request.created` | Request record created |
| `ai.request.dispatched` | Async job queued |
| `ai.request.started` | Processing begins |
| `ai.request.processing` | Status → processing (async path) |
| `ai.request.completed` | Successful completion |
| `ai.request.failed` | Provider or queue failure |
| `ai.request.cancelled` | User cancellation |
| `ai.quota.exceeded` | Quota limit hit |

Secrets and provider credentials are never logged.

---

## Security

- AI `input` hidden from API responses
- Requires `ai.use` permission (viewer role excluded)
- All requests scoped via `BelongsToOrganization` + tenant context
- Cross-tenant access returns **404**
- RBAC integrated with enterprise roles (M2)

---

## Configuration Reference

| Variable | Default | Purpose |
|----------|---------|---------|
| `AI_PROVIDER` | `mock` | Default provider |
| `AI_TIMEOUT` | `30` | Provider timeout (seconds) |
| `AI_ASYNC_DISPATCH` | `false` | Async mode |
| `AI_RATE_LIMIT_PER_MINUTE` | `30` | Per-org rate limit |
| `AI_QUOTA_ENABLED` | `false` | Enable usage quotas |
| `AI_QUOTA_REQUESTS_PER_PERIOD` | `1000` | Quota limit |
| `AI_QUOTA_PERIOD` | `monthly` | Quota period |
| `QUEUE_CONNECTION` | `redis` | Queue driver |

---

## Related Documents

- [multi-tenancy.md](./multi-tenancy.md)
- [security.md](./security.md)
- [deployment.md](./deployment.md)
