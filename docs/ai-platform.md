# AI Platform

**Last updated:** AI-09 knowledge ingestion and retrieval operations (2026-08-19)

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

## Knowledge retrieval expansion (AI-08)

Keyword retrieval remains the baseline (no vector database). AI-08 adds rich bodies, deterministic ranking, bounded freshness, in-memory indexing, and safe usage telemetry.

Sources:

- `library_items.content` / `content_ar` — existing long-form bodies; published, current organization only
- `bee_knowledge_topics.body` — nullable long-form field; active catalog topics only; `rag_ready` stays metadata

`KnowledgeIndexer` + `KnowledgeTextNormalizer` clean text, derive searchable tokens, and excerpt bodies. Nothing is stored as prompts or provider responses.

Ranking (deterministic): exact title > title phrase/token > summary > body, plus multi-term title coverage. Freshness is a secondary 0–2 point signal from `updated_at` (missing timestamps score 0) and cannot outrank a title token. Ties break by `source_type`, then `source_id`.

Limits stay server-side: `max_results`, `max_context_characters`, `candidate_limit`, `max_excerpt_characters`. Clients cannot override them.

Observability is stored on `ai_usage_records.retrieval` as aggregates only (`candidate_count`, `returned_count`, `retrieval_duration_ms`, `source_types`, `retrieval_status`). Telemetry failures never break the AI response.

---

## Knowledge ingestion and retrieval operations (AI-09)

AI-09 is an operational layer **on top of** AI-08. It does not replace keyword retrieval, redesign ranking, or change provider/HTTP adapters. There is still **no** embeddings, vector database, semantic/hybrid retrieval, agents, tool calling, vision, or conversation-history RAG.

```
operator/service
  -> KnowledgeIngestionService (upsert, validate, sanitize)
  -> BeeKnowledgeBodyBackfillService (empty bodies only)
  -> KnowledgeFreshnessService (fresh / stale / unknown)
  -> KnowledgeRetrievalOperations (bounded inspect)
  -> KnowledgeRetrievalHealthService (tenant-scoped summary)

AI request path (unchanged):
  AiService -> AI-05 retriever (indexer/ranker) -> bounded context
            -> provider -> AI-06 grounding -> AI-07 disclosure
            -> AI-04 usage + retrieval telemetry
```

### Ingestion architecture

`KnowledgeIngestionService` upserts existing knowledge records:

- `library_items` identity: `(organization_id, slug)`
- `bee_knowledge_topics` identity: `slug` (platform catalog)

Tenant identity always comes from the authenticated/service caller. Client-supplied `organization_id` cannot override it. Library writes are scoped to that tenant; bee topics cannot be assigned a tenant.

Ingestion validates title/summary/body/slug/source type/publication state, normalizes searchable text, and derives tokens through `KnowledgeTextNormalizer`. Malformed input is rejected. Model-generated citation URLs and untrusted `url` / `source_url` / `citations` fields are never stored as authoritative source metadata. If `source` looks like a URL but is not a valid `http`/`https` URL, nothing is fabricated — the attribution is stored as empty.

### Idempotency

Repeating the same payload for the same source identity does not create duplicates, does not duplicate searchable content or source metadata, and does not bump `updated_at` when no fields changed.

### Bee knowledge backfill policy

`BeeKnowledgeBodyBackfillService` fills **missing/empty** `bee_knowledge_topics.body` values from existing catalog fields only (`slug`, `category`, `title_key`, `summary_key`, `tags`). It never overwrites a non-empty body, never invents scientific claims, and never fabricates URLs or citations. If those authoritative fields are insufficient, the row is skipped and the body stays null. The operation is explicit (not a model observer) and idempotent.

### Freshness policy

`KnowledgeFreshnessService` classifies `updated_at` as `fresh`, `stale`, or `unknown` (missing timestamp). Stale is `updated_at` older than `AI_RETRIEVAL_FRESHNESS_STALE_AFTER_DAYS` (default 90). Ranking is **not** redesigned: AI-08 `KnowledgeRanker` still applies a 0–2 point freshness signal that cannot outrank a title token. Tests should pass an explicit `$now` so classification stays deterministic.

### Retrieval operations

`KnowledgeRetrievalOperations` is backend/domain access for a future admin/operator interface — not an admin UI and not raw SQL. It can retrieve a bounded set by tenant, source type, publication state, and freshness, and can inspect one record plus source distribution. Default publication state is `published`. Unpublished library items are visible only when `publication_state` is explicitly `unpublished` or `all`, and only for the current tenant.

### Telemetry

`ai_usage_records.retrieval` remains aggregate-only. AI-09 adds optional `freshness_distribution` (`fresh` / `stale` / `unknown` counts) alongside `candidate_count`, `returned_count`, `retrieval_duration_ms`, `source_types`, and `retrieval_status` (`ok` / `empty` / `failed` / `disabled`). Secrets, API keys, Authorization headers, full prompts, and full model responses are not stored. Telemetry persistence failure is logged and never fails a successful AI response. The AI-04 `ai.requests` quota metric is unchanged.

### Health summary

`KnowledgeRetrievalHealthService::summary($organizationId)` reports indexed/available counts, published/unpublished, empty bodies, freshness buckets, deterministic source-type distribution, and retrieval success/empty/failure counts from that tenant’s usage records. Library totals are tenant-scoped. Bee topics remain a platform catalog (labeled `platform_catalog`) shared across tenants.

### Security boundaries

- Tenant isolation on library reads and writes
- Unpublished library content never enters normal AI retrieval
- Client tenant IDs cannot override authenticated context
- Ingestion and operational queries are sanitized
- AI-06 grounding, AI-07 disclosure, and AI-04 usage accounting are unchanged
- No second authorization system

### Operational limitations

- Keyword retrieval only (no embeddings / vector DB)
- Bee catalog is global, not per-tenant
- Backfill concatenates existing catalog keys; it does not author scientific content
- No admin UI, frontend, or mobile changes in AI-09
- OpenAI HTTP adapter remains unaware of retrieval internals

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
| `AI_RETRIEVAL_CANDIDATE_LIMIT` | `40` | Max candidates before final ranking |
| `AI_RETRIEVAL_MAX_EXCERPT_CHARACTERS` | `400` | Max characters per retrieved body excerpt |
| `AI_RETRIEVAL_FRESHNESS_STALE_AFTER_DAYS` | `90` | Operational stale threshold (ranking still uses AI-08 0–2 signal) |
| `AI_RETRIEVAL_OPERATIONS_MAX_RESULTS` | `50` | Max hits returned by retrieval operations |

---

## Related Documents

- [multi-tenancy.md](./multi-tenancy.md)
- [security.md](./security.md)
- [deployment.md](./deployment.md)
