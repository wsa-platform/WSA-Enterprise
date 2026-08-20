# AI Platform

**Last updated:** AI-13 production embeddings and PostgreSQL vector ANN/HNSW (2026-08-20)

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

## Hybrid knowledge retrieval foundation (AI-10)

AI-10 adds a **replaceable hybrid retrieval foundation** on top of AI-08/AI-09. Default behavior is unchanged: `AI_RETRIEVAL_STRATEGY=keyword`. The OpenAI HTTP adapter stays unaware of retrieval internals. AI-06 grounding, AI-07 disclosure, and AI-04 usage accounting are unchanged.

The AI-10 semantic implementation is a **deterministic lexical approximation** (normalized token overlap, weighted title/summary/body similarity). It is **not** neural embedding quality and does not use a vector database. A later milestone may plug in a real embedding/vector backend behind `KnowledgeSemanticIndexInterface` after operational requirements are met.

```
AiService
  -> KnowledgeRetrievalRouter (strategy: keyword | semantic | hybrid)
       -> KeywordKnowledgeRetriever (AI-08, unchanged algorithm)
       -> DeterministicLexicalSemanticIndex (replaceable)
       -> HybridKnowledgeRetrievalStrategy (bounded weighted mix)
  -> bounded context
  -> provider
  -> AI-06 grounding
  -> AI-07 disclosure
  -> AI-04 usage + retrieval telemetry
```

### Strategies

| Strategy | Behavior |
|----------|----------|
| `keyword` (default) | Existing AI-08 keyword retrieval |
| `semantic` | Deterministic lexical similarity over published/active documents |
| `hybrid` | `keyword_score * keyword_weight + semantic_score * semantic_weight + freshness_score * freshness_weight` |

Invalid strategy values fall back to keyword. Weights are clamped so freshness and weak lexical similarity cannot outrank an exact title match (`semantic_weight` max 0.5, `freshness_weight` max 1.0). Freshness uses AI-09 `KnowledgeFreshnessService.rankingScore` (0–2), not a second algorithm.

### Fallback

If semantic retrieval is disabled or throws:

- the AI request continues
- keyword retrieval is used
- telemetry `retrieval_status=fallback` with `fallback_reason` (`semantic_unavailable`, `semantic_error`, or `invalid_strategy`)

### Index lifecycle

Ingestion and Bee body backfill call `KnowledgeSemanticIndexSync`. Create/update/publish indexes the document; unpublish/remove drops it from the in-memory semantic overlay. If indexing fails, the knowledge row is left intact, the failure is logged, and keyword retrieval still works.

### Telemetry

Additive fields on `ai_usage_records.retrieval`: `retrieval_strategy`, `keyword_candidate_count`, `semantic_candidate_count`, optional `hybrid_result_count`, `fallback_reason`, `requested_strategy`. Existing AI-09 aggregates remain. Scoring breakdowns stay on hit metadata and are **not** copied into user-facing citations.

### Quality summary

`KnowledgeRetrievalQualityService` is tenant-scoped backend/domain output for a future operator API: keyword/semantic/hybrid counts, fallback/empty/error counts, average returned count, strategy and source-type distributions. No admin UI in AI-10.

### Isolation and trust

Every strategy uses the same tenant and publication filters as AI-08. Semantic similarity cannot promote unpublished or foreign-tenant documents, and cannot turn model/client URLs into trusted citations.

---

## Operator retrieval operations API (AI-11)

AI-11 is an **authenticated operator observability and knowledge-operations API**. It is **not** a public user retrieval API. It does **not** introduce an external vector database. AI-12 now supplies the production semantic backend behind the same `KnowledgeSemanticIndexInterface`; AI-11 remains the operational HTTP interface.

```
Authenticated operator
  -> /api/v1/operator/ai/...
  -> AiRetrievalOperationsController (validate, authorize, respond)
  -> KnowledgeRetrievalOperationsService
       -> AI-08/AI-09/AI-10 retrieval, quality, ingestion, indexing, telemetry
```

Controllers do not contain retrieval, ranking, indexing, or OpenAI HTTP logic.

### Authentication and authorization

| Requirement | Mechanism |
|-------------|-----------|
| Authentication | Existing `auth.principal` (Sanctum user or API client) |
| Tenant context | Existing `resolve.organization` |
| Authorization | Existing permission `access.manage` (organization admin/owner). No second auth system. No hardcoded admin email, user ID, password, or API key. |

Unauthenticated callers receive the standard `401 Unauthenticated.` response. Authenticated non-operators receive `403 This action is unauthorized.`

### Tenant isolation

Tenant scope always comes from the authenticated organization context. Client-supplied `organization_id` / `tenant_id` is rejected (`prohibited`) on mutating and telemetry requests. A tenant operator cannot inspect, ingest, reindex, publish, or unpublish another tenant's knowledge. Cross-organization `X-Organization-Id` for a non-member is denied by existing organization middleware. Platform-admin cross-tenant access is not invented here; only existing membership/context rules apply.

Operator knowledge writes are limited to **tenant `library_items`**. The Bee catalog remains a platform-wide source and is not writable through this API.

### Endpoints

All routes are authenticated and authorized. Envelope: `{ "data": ... }` via `ApiResponse::success`.

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/api/v1/operator/ai/retrieval/health` | Retrieval health |
| `GET` | `/api/v1/operator/ai/retrieval/strategy` | Safe effective strategy inspection |
| `GET` | `/api/v1/operator/ai/retrieval/quality` | Tenant-scoped quality summary |
| `GET` | `/api/v1/operator/ai/retrieval/telemetry` | Bounded retrieval telemetry |
| `POST` | `/api/v1/operator/ai/knowledge` | Ingest/update a library item |
| `POST` | `/api/v1/operator/ai/knowledge/{id}/index` | Reindex a tenant library item |
| `POST` | `/api/v1/operator/ai/knowledge/{id}/publish` | Publish (make retrievable) |
| `POST` | `/api/v1/operator/ai/knowledge/{id}/unpublish` | Unpublish (exclude from retrieval) |

Conceptual operator namespace `/api/operator/ai/...` is implemented under the existing `/api/v1` convention.

### Health

`status` is one of `healthy`, `degraded`, `unavailable`:

- keyword available and selected, semantic unused → `healthy` (`semantic_available` may be `false`)
- semantic/hybrid selected but semantic unavailable, or invalid configured strategy → `degraded`
- retrieval disabled → `unavailable`

Telemetry/knowledge-summary failures are logged and omitted; they must not crash the endpoint. Semantic availability failures cannot report `healthy` when semantic/hybrid is the effective strategy. Secrets, API keys, credentials, connection strings, and `.env` values are never returned.

### Strategy inspection

Returns configured vs effective strategy, keyword/semantic/hybrid/fallback capability flags, retrieval enabled, and **clamped** hybrid weights. Invalid configured strategies are reported as `invalid` and cannot become the active effective strategy (effective remains `keyword`). Raw environment values and secrets are not echoed.

### Quality

Reuses `KnowledgeRetrievalQualityService` (no new metrics engine). Tenant-scoped aggregates that already exist: success/empty/error/fallback counts, strategy distribution, source-type distribution, average returned count. Operator extras: `total_retrieval_requests`, `semantic_available`, `average_retrieval_duration_ms` (bounded lookback). If quality aggregation fails, the endpoint returns `status: unavailable` instead of fabricating values.

### Telemetry

Bounded operator view of `ai_usage_records.retrieval` only. Filters: `from`, `to`, `strategy`, `status`, `limit`. Default limit **25**, maximum **100**. Date range maximum **90** days (default last 7 days). Rows are tenant-scoped. Responses are allowlisted operational fields only — not raw prompts, model responses, API keys, authorization headers, or provider secrets.

### Ingestion, reindex, publish, unpublish

Ingestion reuses AI-09 `KnowledgeIngestionService` (normalizer + semantic sync). Tenant is taken from authorization context. Title/summary/body/source type/publication state are validated; system fields cannot be mass-assigned. Idempotency follows existing slug-per-tenant semantics.

Reindex uses the existing indexer + semantic sync. Keyword indexing remains usable if semantic is unavailable; the operation then reports `status=degraded` and `fallback_reason=semantic_unavailable` with `semantic_indexed=false` (no false semantic success).

Publish/unpublish reuse ingestion publication rules. **Unpublished knowledge is not retrievable** for keyword, semantic, or hybrid retrieval.

### Audit

Ingest, reindex, publish, and unpublish record existing `AuditService` actions: `ai.knowledge.ingested`, `ai.knowledge.reindexed`, `ai.knowledge.published`, `ai.knowledge.unpublished`. No second audit framework.

### Security restrictions

- Not a public retrieval API
- Existing auth + `access.manage` only
- Tenant isolation; no client tenant override
- Unpublished knowledge stays out of retrieval
- No secrets, credentials, prompts, or model completions in operator responses
- No OpenAI chat-adapter changes
- No admin-mobile / frontend changes

### Operational limitations

- Operator writes are library-item only
- Telemetry and duration lookbacks are bounded; they are not unlimited historical scans
- Health/quality degrade or omit sections when supporting queries fail; they do not throw raw exceptions to clients
- Semantic retrieval is implemented by AI-12 (see below)

---

## Production vector retrieval (AI-12)

AI-12 replaces the AI-10 deterministic/lexical semantic path with a **real embedding + vector similarity backend**. `KnowledgeSemanticIndexInterface` remains the stable abstraction. AI-11 operator endpoints remain the operational interface. Keyword retrieval, hybrid mixing, grounding, disclosure, and usage accounting are unchanged.

```
AiService
  -> KnowledgeRetrievalRouter (keyword | semantic | hybrid)
       -> KeywordKnowledgeRetriever (AI-08)
       -> VectorKnowledgeSemanticIndex (implements KnowledgeSemanticIndexInterface)
            -> EmbeddingProviderInterface (mock hashed embeddings | OpenAI /v1/embeddings)
            -> PostgresKnowledgeVectorStore (JSON vectors + native pgvector/HNSW when available)
       -> HybridKnowledgeRetrievalStrategy
  -> bounded context
  -> provider
  -> AI-06 grounding / AI-07 disclosure / AI-04 usage
```

This is **not** Pinecone, Weaviate, Milvus, Qdrant, or Elasticsearch. Vectors are stored in the existing PostgreSQL database (`knowledge_embeddings`). Cosine similarity is computed over persisted dense vectors. The AI-10 `DeterministicLexicalSemanticIndex` remains in the repository but is no longer the bound production semantic index.

### Embedding providers

| Provider | When | How |
|----------|------|-----|
| `mock` (default) | Tests and local without an API key | Deterministic hashed n-gram dense vectors (feature hashing), L2-normalized. Real cosine search, not lexical ranking and not random vectors. |
| `openai` | `AI_EMBEDDING_PROVIDER=openai` with existing `OPENAI_API_KEY` | Reuses the AI-03 OpenAI HTTP conventions (`Http::withToken`, base URL, timeout, bounded retries) against `/v1/embeddings`. |

The chat completions adapter is not a second embeddings client stack; embeddings use the same key, base URL, timeout, and retry settings with embedding-specific overrides.

### Vector storage

- Table: `knowledge_embeddings` (additive migration)
- Fields: source_type, source_id, organization_id (nullable for Bee catalog), embedding (JSON float array), embedding_model, embedding_dimensions, content_hash, indexed_at
- Unique identity: `(source_type, source_id)`
- Distance metric: **cosine similarity**
- Similarity threshold: `AI_EMBEDDING_SIMILARITY_THRESHOLD` (default `0.15`, clamped 0–1)
- Scan bound: `AI_EMBEDDING_MAX_SCAN` (default 500)

**pgvector / AI-13:** additive migration `2026_08_20_200000_add_knowledge_embedding_pgvector` runs `CREATE EXTENSION IF NOT EXISTS vector` **outside a PostgreSQL transaction**. If the server does not ship pgvector, the migration succeeds without changing existing JSON rows. If the extension is present, it adds `embedding_vector`, backfills from JSON, and creates an HNSW index (`vector_cosine_ops`). Retrieval then uses native `ORDER BY embedding_vector <=> query` with tenant and publication filters in SQL — not an O(N) PHP cosine loop. If native ANN is unavailable or fails, the AI-12 JSON cosine path is used. CI uses `pgvector/pgvector:pg16`. Local Docker `postgres:16-alpine` still falls back to JSON. `docker-compose.yml` was not rewritten.

### Content hash / idempotency

Embeddings are regenerated only when retrieval-relevant content, embedding model, or dimensions change. `content_hash` is SHA-256 over title, summary, body, searchable text, and source identity. Unchanged hash + same model + same dimensions → skip generation.

### Publication gate

Unpublished library items and inactive Bee topics are excluded **in the SQL eligibility query** (exists/join on `publication_status=published` / `is_active=true`), not merely filtered after scoring. Unpublish deletes the vector row via existing `KnowledgeSemanticIndexSync`. Publish re-indexes. Keyword, semantic, and hybrid cannot bypass this gate.

### Tenant isolation

Library vectors are stored and queried with `organization_id`. Bee catalog vectors keep `organization_id=null` (platform-wide, same distinction as AI-08/AI-09). Client tenant IDs are not trusted.

### Failure / fallback

Embedding or vector failures do not destroy keyword retrieval. Hybrid/semantic fall back through the existing AI-10 router (`semantic_unavailable`, `semantic_error`). Invalid or wrong-dimension vectors are rejected and never stored. Indexing reports success only when a fingerprint exists.

### Retry / timeout

OpenAI embedding HTTP uses bounded timeout, connect timeout, retry count (max 5), and linear backoff. There are no infinite retries.

### Batch indexing

`VectorKnowledgeSemanticIndex.indexDocuments()` embeds in bounded batches. A single failed document does not corrupt the index; remaining documents continue. Partial counts are returned.

### Reindex / lifecycle

AI-11 `POST /knowledge/{id}/index` now uses the vector backend. Additive fields: `total`, `indexed`, `skipped`, `failed`, `semantic_skipped`, `semantic_failed`, `removed`. Existing `keyword_indexed` / `semantic_indexed` / `status` / `fallback_reason` remain. Delete of a library item or Bee topic removes the vector (no orphans). Updates go through ingestion sync + content hash.

### Health, strategy, telemetry

AI-11 health adds `semantic_backend=vector`, `vector_store_available`, `embedding_provider_available`, `pgvector_available`, plus AI-13 `hnsw_available`, `ann_available`, and `distance_metric=cosine`. Keyword + vector store + embedding available → `healthy` (JSON fallback is a valid vector store). Embedding or store down while semantic is enabled → `degraded`. Retrieval disabled → `unavailable`. Missing pgvector alone is not degraded; health reports the capability honestly.

Strategy inspection reports vector capability, embedding provider **name**, model, dimensions, distance metric, HNSW/ANN availability, and threshold — never credentials.

Telemetry may include embedding/vector durations, provider name, model, threshold, semantic result count, `ann_used`, and `distance_metric`. API keys, Authorization headers, and raw prompts are not stored.

### Security

- Reuses existing OpenAI API key configuration; nothing is hardcoded
- Logs go through `AiErrorSanitizer`
- Operator responses still pass `withoutSecrets`
- Unpublished and cross-tenant vectors cannot be retrieved

### Operational limitations

- Default embedding provider is `mock` so environments without `OPENAI_API_KEY` keep working
- Native pgvector ANN/HNSW is used when the extension is installed; otherwise JSON cosine fallback remains
- JSON fallback scan is bounded (`AI_EMBEDDING_MAX_SCAN`); native ANN uses `LIMIT` plus the HNSW index
- No frontend / admin-mobile / chat-adapter changes

## Production embeddings and native ANN (AI-13)

AI-13 hardens AI-12. It does **not** replace `KnowledgeSemanticIndexInterface`. OpenAI embeddings stay behind `EmbeddingProviderInterface`. Vectors stay in PostgreSQL.

- Production OpenAI embeddings reuse AI-03 HTTP (`Http::withToken`, existing `OPENAI_API_KEY`, timeout, bounded retries) against `/v1/embeddings`. Oversized inputs are rejected. `dimensions` is sent only for `text-embedding-3-*` models.
- Mock embeddings remain the default when `OPENAI_API_KEY` is absent.
- Native storage column: `knowledge_embeddings.embedding_vector` (`vector`), dual-written with the JSON `embedding` column.
- Distance metric: cosine (`<=>` / `vector_cosine_ops`). Unsupported metric values are ignored; cosine is used.
- Operator `POST /api/v1/operator/ai/knowledge/backfill` supports tenant-scoped backfill with optional `dry_run`. It does not index another tenant's library items.
- `AI_EMBEDDING_ANN_ENABLED=false` forces the JSON fallback even when pgvector is present.

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
| `AI_RETRIEVAL_STRATEGY` | `keyword` | `keyword`, `semantic`, or `hybrid` (invalid values fall back to keyword) |
| `AI_RETRIEVAL_SEMANTIC_ENABLED` | `true` | Enable the replaceable semantic index (strategy default remains keyword) |
| `AI_RETRIEVAL_KEYWORD_WEIGHT` | `1.0` | Hybrid keyword weight (clamped 0–2) |
| `AI_RETRIEVAL_SEMANTIC_WEIGHT` | `0.25` | Hybrid semantic weight (clamped 0–0.5) |
| `AI_RETRIEVAL_FRESHNESS_WEIGHT` | `0.05` | Extra hybrid freshness weight (clamped 0–1; AI-08 ranker still includes 0–2) |
| `AI_EMBEDDING_ENABLED` | `true` | Disable embedding generation without removing keyword retrieval |
| `AI_EMBEDDING_PROVIDER` | `mock` | `mock` or `openai` |
| `AI_EMBEDDING_MODEL` | `mock-hash-v1` | Embedding model name (`text-embedding-3-small` when using OpenAI) |
| `AI_EMBEDDING_DIMENSIONS` | mock `64` / openai `1536` | Stored vector size when unset |
| `AI_EMBEDDING_DISTANCE_METRIC` | `cosine` | Only cosine is implemented |
| `AI_EMBEDDING_ANN_ENABLED` | `true` | Use native pgvector ANN when the column exists |
| `AI_EMBEDDING_TIMEOUT` | OpenAI timeout | Embedding HTTP timeout |
| `AI_EMBEDDING_RETRY_TIMES` | `2` | Bounded embedding retries |
| `AI_EMBEDDING_BATCH_SIZE` | `16` | Max texts per embedding batch |
| `AI_EMBEDDING_SIMILARITY_THRESHOLD` | `0.15` | Minimum cosine similarity |
| `AI_EMBEDDING_MAX_CANDIDATES` | retrieval candidate limit | Max semantic hits returned |
| `AI_EMBEDDING_MAX_SCAN` | `500` | Max eligible JSON vectors scanned when ANN is unavailable |

---

## Related Documents

- [multi-tenancy.md](./multi-tenancy.md)
- [security.md](./security.md)
- [deployment.md](./deployment.md)
