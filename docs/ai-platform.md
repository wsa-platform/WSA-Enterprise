# AI Platform

**Last updated:** Phase 11 (2026-08-10)

## Overview

WSA-Enterprise provides **agricultural decision-support AI** through a provider-abstracted, queue-backed request pipeline. AI outputs are advisory only — not authoritative diagnoses.

---

## Architecture

```
Client → POST /api/v1/ai/requests
       → AiController
       → AiService
         → AiProviderInterface (MockAiProvider | future providers)
         → AiRequestValidator
         → AiResponseNormalizer
         → AuditService
         → [async] ProcessAiRequest job → Redis queue → queue worker
```

---

## Request Lifecycle

| Status | Meaning |
|--------|---------|
| `pending` | Created; job queued (async) or about to process (sync) |
| `processing` | Worker acquired lock; provider executing |
| `completed` | Output persisted |
| `failed` | Error captured in `error_message` |
| `cancelled` | User/system cancelled before completion (Phase 11) |

### Sync vs async

Controlled by environment variable:

```env
AI_ASYNC_DISPATCH=false   # default — returns 201 with completed result
AI_ASYNC_DISPATCH=true    # returns 202 with pending; requires queue worker
```

| Mode | HTTP | Requires queue worker |
|------|------|----------------------|
| Sync | 201 Created | No |
| Async | 202 Accepted | Yes |

Poll async requests:

```
GET /api/v1/ai/requests/{id}
```

---

## Supported Request Types

| Type | Purpose |
|------|---------|
| `diagnosis` | Plant disease decision support |
| `library_summary` | Summarize library content |
| `library_qa` | Q&A over library knowledge |
| `training_assistance` | Training content assistance |

Validate types via `AiRequestValidator`.

---

## Provider Abstraction

```php
interface AiProviderInterface {
    public function process(AiRequest $request): array;
    public function name(): string;
}
```

**Rules:**

- Domain code uses `AiService` only — never vendor SDKs directly
- Provider binding in `AppServiceProvider` via config `AI_PROVIDER`
- Phase 11 adds `AiProviderResolver` for org-level overrides

### Current providers

| Provider | Config value | Status |
|----------|-------------|--------|
| Mock | `mock` | Default — no external calls |

---

## Queue Configuration

```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

Docker Compose includes a dedicated `queue` service:

```bash
docker compose logs -f queue
docker compose exec backend php artisan queue:failed
```

Queue worker entrypoint waits for Redis readiness before starting (prevents crash-loops).

Job: `App\Jobs\ProcessAiRequest`

- Implements `ShouldQueue`, `ShouldBeUnique`
- Configurable tries/timeout via `config/ai.php`
- Idempotent: terminal states not reprocessed
- Row-level DB lock during processing

---

## Security

- AI `input` is **hidden** from API responses (`sanitizeAiRequest`)
- AI `input` is persisted in DB — review retention policy for production
- AI endpoints throttled: 30 requests/minute
- Requires `ai.use` permission
- All requests scoped to active organization
- Audit events: `ai.request.dispatched`, processing, completed, failed

---

## Usage & Quotas (Phase 11)

```
AiService → AiQuotaService → EntitlementService (plan limits)
         → AiUsageRecorder → usage_records table
```

- Over-quota requests return **429 Too Many Requests**
- Usage summary: `GET /api/v1/ai/usage` (planned)
- Token/request counts recorded per org per billing period

---

## Client Integration

### Web (`frontend/src/api/ai.ts`)

- `createAiRequest()` — POST
- `fetchAiRequest()` — GET poll
- `pollAiRequest()` — helper with interval (wire in AI feature module)

### Mobile (`mobile/lib/api/`)

- Mirror web API methods
- Phase 11: polling use case in domain layer

---

## Configuration Reference

| Variable | Default | Purpose |
|----------|---------|---------|
| `AI_PROVIDER` | `mock` | Provider selection |
| `AI_TIMEOUT` | `30` | Provider timeout seconds |
| `AI_ASYNC_DISPATCH` | `false` | Async mode toggle |
| `QUEUE_CONNECTION` | `redis` | Queue driver |

---

## Related Documents

- [phase-11-architecture.md](./phase-11-architecture.md)
- [deployment.md](./deployment.md)
- [security.md](./security.md)
