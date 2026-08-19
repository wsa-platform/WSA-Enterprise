# AI Platform

**Last updated:** AI-07 grounded answer disclosure (2026-08-19)

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
- Client payloads cannot inject `sources`, `citations`, `retrieved_context`, or related trusted-knowledge fields

---

## Knowledge retrieval (AI-05)

Keyword retrieval searches existing `library_items` (published, current organization only) and active `bee_knowledge_topics`. There is no vector database.

Limits (`max_results`, `max_context_characters`, `candidate_limit`) bound database reads and provider context. Retrieved excerpts are labeled **UNTRUSTED RETRIEVED KNOWLEDGE** and must not override system/safety instructions.

`rag_ready` on bee knowledge topics is a readiness flag only — not a retriever.

Retrieval failure is logged through `AiErrorSanitizer` and does not fail the AI request.

---

## Grounded answers and citations (AI-06)

`AiGroundedAnswerPolicy` sits above AI-05 retrieval and around response normalization:

```
authorization → AI-05 retrieval → AI-06 grounded-answer policy → provider → normalize → citation integrity → AI-07 disclosure → AI-04 usage → response
```

| Retrieval outcome | `output.grounded` | `output.sources` | Provider |
|-------------------|-------------------|------------------|----------|
| Usable sources | `true` | Server-controlled citations from retrieval (`source_type`, `source_id`, `title`, `reference`) | Receives bounded untrusted context |
| Empty result | `false` | `[]` | Continues normally; no fabricated citations |
| Retrieval failure | `false` | `[]` | Continues normally; no fabricated context |

Citation rules:

- Structured sources come only from trusted server-side retrieval.
- Provider/model text cannot create trusted source objects or URLs.
- No citation URLs are invented.
- AI-04 `ai_usage_records` remain the usage persistence layer; persistence failures stay non-fatal.

---

## Grounded answer disclosure (AI-07)

`AiGroundedAnswerDisclosurePolicy` rewrites user-visible answer text after AI-06. The provider does not decide grounding and does not emit trusted citations.

Knowledge request types (`library_summary`, `library_qa`, `assistant`) use retrieval metadata already produced by AI-05/AI-06. Other request types are treated as general requests and do not receive a knowledge-base warning. There is no classifier or second model.

| `grounding_state` | User-visible text | Citations |
|-------------------|-------------------|-----------|
| `grounded` | Unchanged provider answer; no warning | Existing normalized `sources` |
| `empty_retrieval` | Concise disclosure that no matching internal source was found, then the general answer | `[]` |
| `retrieval_failed` | Concise disclosure that internal knowledge was unavailable, then the general answer | `[]` |
| `general_request` | Unchanged answer; no knowledge-base warning | AI-06 sources if any |

Disclosure rules:

- Applied once; duplicate disclosure text is not prepended again.
- Retrieved content remains **UNTRUSTED RETRIEVED KNOWLEDGE** and cannot suppress disclosure or change `grounded` / `grounding_state`.
- Client fields such as `grounded`, `grounding_state`, `sources`, and `retrieved_context` are ignored.
- No URLs or source IDs are invented. Empty retrieval and retrieval failure never fabricate citations.
- Retrieval failure stays non-fatal and never exposes exceptions, SQL, stack traces, API keys, or Authorization headers.
- Additive response fields: `grounding_state`, `disclosure_applied`, `disclosure_code`. Existing `grounded` and `sources` remain.

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
| `AI_RETRIEVAL_ENABLED` | `true` | Keyword retrieval over existing knowledge |
| `AI_RETRIEVAL_MAX_RESULTS` | `5` | Max citations/context hits |
| `AI_RETRIEVAL_MAX_CONTEXT_CHARACTERS` | `4000` | Max grounded context size |

---

## Related Documents

- [multi-tenancy.md](./multi-tenancy.md)
- [security.md](./security.md)
- [deployment.md](./deployment.md)
