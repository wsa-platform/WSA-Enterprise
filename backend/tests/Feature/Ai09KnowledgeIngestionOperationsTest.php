<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\AiUsageRecord;
use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiGroundedAnswerDisclosurePolicy;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\Retrieval\BeeKnowledgeBodyBackfillService;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;
use App\Services\Ai\Retrieval\KnowledgeFreshnessService;
use App\Services\Ai\Retrieval\KnowledgeIngestionResult;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRanker;
use App\Services\Ai\Retrieval\KnowledgeRetrievalHealthService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperations;
use Database\Seeders\DatabaseSeeder;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Ai09KnowledgeIngestionOperationsTest extends TestCase
{
    use RefreshDatabase;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        Config::set('ai.retrieval.enabled', true);
        Config::set('ai.retrieval.max_results', 5);
        Config::set('ai.retrieval.max_context_characters', 4000);
        Config::set('ai.retrieval.candidate_limit', 40);
        Config::set('ai.retrieval.max_excerpt_characters', 400);
        Config::set('ai.retrieval.freshness_stale_after_days', 90);
        Http::preventStrayRequests();
        $this->now = new DateTimeImmutable('2026-08-19 12:00:00', new DateTimeZone('UTC'));
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $token = $this->admin()->createToken('ai-09')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingestLibrary(Organization $organization, array $payload): KnowledgeIngestionResult
    {
        return app(KnowledgeIngestionService::class)->ingestLibraryItem(
            $organization->id,
            $payload,
            $this->admin()->id,
        );
    }

    public function test_ingestion_creates_a_valid_record(): void
    {
        $organization = Organization::first();
        $result = $this->ingestLibrary($organization, [
            'slug' => 'ai09-create-valid',
            'title' => 'AI09CreateValidTitle',
            'summary' => 'Operator summary.',
            'content' => 'Authoritative orchard notes for AI09CreateValidTitle.',
            'publication_status' => 'published',
            'source' => 'FAO field guide',
        ]);

        $this->assertSame('created', $result->action);
        $this->assertSame('library_items', $result->sourceType);
        $item = LibraryItem::query()->find($result->sourceId);
        $this->assertNotNull($item);
        $this->assertSame($organization->id, $item->organization_id);
        $this->assertSame('AI09CreateValidTitle', $item->title);
        $this->assertSame('published', $item->publication_status);
        $this->assertSame('FAO field guide', $item->source);
        $this->assertNotEmpty($result->searchableTokens);
    }

    public function test_repeated_ingestion_is_idempotent(): void
    {
        $organization = Organization::first();
        $payload = [
            'slug' => 'ai09-idempotent',
            'title' => 'AI09IdempotentTitle',
            'summary' => 'Same summary.',
            'content' => 'Same body.',
            'publication_status' => 'published',
            'source' => 'Extension bulletin',
        ];
        $first = $this->ingestLibrary($organization, $payload);
        $original = LibraryItem::query()->find($first->sourceId);
        $updatedAt = (string) $original->updated_at;

        $second = $this->ingestLibrary($organization, $payload);
        $again = LibraryItem::query()->find($first->sourceId);

        $this->assertSame('unchanged', $second->action);
        $this->assertSame($first->sourceId, $second->sourceId);
        $this->assertSame(1, LibraryItem::query()->where('slug', 'ai09-idempotent')->where('organization_id', $organization->id)->count());
        $this->assertSame($updatedAt, (string) $again->updated_at);
        $this->assertSame($original->source, $again->source);
        $this->assertSame($original->content, $again->content);
    }

    public function test_ingestion_updates_an_existing_record_safely(): void
    {
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai09-update-safe',
            'title' => 'Old title',
            'summary' => 'Old summary.',
            'content' => 'Old body.',
            'publication_status' => 'draft',
        ]);
        $updated = $this->ingestLibrary($organization, [
            'slug' => 'ai09-update-safe',
            'title' => 'AI09UpdatedTitle',
            'summary' => 'New summary.',
            'content' => 'New body for operators.',
            'publication_status' => 'published',
        ]);

        $item = LibraryItem::query()->find($created->sourceId);
        $this->assertSame('updated', $updated->action);
        $this->assertSame($created->sourceId, $updated->sourceId);
        $this->assertSame('AI09UpdatedTitle', $item->title);
        $this->assertSame('New body for operators.', $item->content);
        $this->assertSame('published', $item->publication_status);
    }

    public function test_empty_bee_body_can_be_backfilled(): void
    {
        $topic = BeeKnowledgeTopic::query()->create([
            'slug' => 'ai09-backfill-empty',
            'category' => 'pests',
            'title_key' => 'ai09.backfill.title',
            'summary_key' => 'ai09.backfill.summary',
            'body' => null,
            'tags' => ['varroa'],
            'metadata' => ['rag_ready' => false],
            'is_active' => true,
        ]);

        $result = app(BeeKnowledgeBodyBackfillService::class)->backfillMissingBodies();
        $topic->refresh();

        $this->assertContains($topic->id, $result->updatedIds);
        $this->assertNotNull($topic->body);
        $this->assertStringContainsString('ai09-backfill-empty', $topic->body);
        $this->assertStringContainsString('ai09.backfill.title', $topic->body);
        $this->assertStringNotContainsString('http://', $topic->body);
    }

    public function test_non_empty_bee_body_is_never_overwritten_by_automatic_backfill(): void
    {
        $topic = BeeKnowledgeTopic::query()->create([
            'slug' => 'ai09-keep-body',
            'category' => 'pests',
            'title_key' => 'ai09.keep.title',
            'summary_key' => 'ai09.keep.summary',
            'body' => 'Keep this authoritative body unchanged.',
            'tags' => ['keep'],
            'is_active' => true,
        ]);

        $first = app(BeeKnowledgeBodyBackfillService::class)->backfillMissingBodies();
        $second = app(BeeKnowledgeBodyBackfillService::class)->backfillMissingBodies();
        $topic->refresh();

        $this->assertContains($topic->id, $first->unchangedIds);
        $this->assertContains($topic->id, $second->unchangedIds);
        $this->assertSame(0, $second->updated);
        $this->assertSame('Keep this authoritative body unchanged.', $topic->body);
    }

    public function test_insufficient_source_content_causes_safe_skip(): void
    {
        $topic = BeeKnowledgeTopic::unguarded(fn () => BeeKnowledgeTopic::create([
            'slug' => 'ai09-insufficient',
            'category' => '',
            'title_key' => '',
            'summary_key' => null,
            'body' => null,
            'tags' => null,
            'is_active' => true,
        ]));

        $outcome = app(BeeKnowledgeBodyBackfillService::class)->backfillTopic($topic);
        $topic->refresh();

        $this->assertSame('skipped', $outcome);
        $this->assertNull($topic->body);
    }

    public function test_unpublished_content_remains_isolated(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai09-draft-secret',
            'title' => 'AI09DraftSecretTerm',
            'summary' => 'Hidden draft.',
            'content' => 'Should not leak into normal retrieval.',
            'publication_status' => 'draft',
        ]);

        $retrieval = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI09DraftSecretTerm');
        $opsPublished = app(KnowledgeRetrievalOperations::class)->retrieve($organization->id, 'AI09DraftSecretTerm');
        $opsUnpublished = app(KnowledgeRetrievalOperations::class)->retrieve(
            $organization->id,
            'AI09DraftSecretTerm',
            ['publication_state' => 'unpublished'],
        );

        $this->assertFalse(collect($retrieval->hits)->contains(fn ($hit) => $hit->title === 'AI09DraftSecretTerm'));
        $this->assertFalse(collect($opsPublished['hits'])->contains(fn ($hit) => ($hit['title'] ?? '') === 'AI09DraftSecretTerm'));
        $this->assertTrue(collect($opsUnpublished['hits'])->contains(fn ($hit) => ($hit['title'] ?? '') === 'AI09DraftSecretTerm'));
    }

    public function test_tenant_a_cannot_retrieve_tenant_b_content(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI09 Org B', 'slug' => 'ai09-org-b']);
        $this->ingestLibrary($orgB, [
            'slug' => 'ai09-org-b-only',
            'title' => 'AI09PrivateOrgBOnly',
            'summary' => 'Foreign tenant body.',
            'content' => 'Secret to org B.',
            'publication_status' => 'published',
        ]);

        $retrieval = app(KeywordKnowledgeRetriever::class)->retrieve($orgA->id, 'AI09PrivateOrgBOnly');
        $ops = app(KnowledgeRetrievalOperations::class)->retrieve($orgA->id, 'AI09PrivateOrgBOnly');
        $inspect = app(KnowledgeRetrievalOperations::class)->inspect(
            $orgA->id,
            'library_items',
            LibraryItem::query()->where('slug', 'ai09-org-b-only')->first()->id,
        );

        $this->assertFalse(collect($retrieval->hits)->contains(fn ($hit) => $hit->title === 'AI09PrivateOrgBOnly'));
        $this->assertFalse(collect($ops['hits'])->contains(fn ($hit) => ($hit['title'] ?? '') === 'AI09PrivateOrgBOnly'));
        $this->assertNull($inspect);
    }

    public function test_tenant_a_cannot_modify_tenant_b_knowledge(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI09 Org B Write', 'slug' => 'ai09-org-b-write']);
        $created = $this->ingestLibrary($orgB, [
            'slug' => 'ai09-owned-by-b',
            'title' => 'Owned by B',
            'summary' => 'B summary.',
            'content' => 'Original B body.',
            'publication_status' => 'published',
        ]);

        $this->expectException(ValidationException::class);
        try {
            app(KnowledgeIngestionService::class)->updateLibraryItem($orgA->id, $created->sourceId, [
                'title' => 'Hijacked',
                'content' => 'Should not write.',
                'publication_status' => 'published',
            ]);
        } finally {
            $item = LibraryItem::query()->find($created->sourceId);
            $this->assertSame('Owned by B', $item->title);
            $this->assertSame($orgB->id, $item->organization_id);
        }
    }

    public function test_client_supplied_tenant_id_cannot_override_context(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI09 Org B Override', 'slug' => 'ai09-org-b-override']);

        $this->expectException(ValidationException::class);
        app(KnowledgeIngestionService::class)->ingestLibraryItem($orgA->id, [
            'organization_id' => $orgB->id,
            'slug' => 'ai09-override',
            'title' => 'Override attempt',
            'publication_status' => 'published',
        ], $this->admin()->id);
    }

    public function test_malformed_input_is_rejected(): void
    {
        $organization = Organization::first();

        try {
            $this->ingestLibrary($organization, [
                'slug' => '',
                'title' => str_repeat('x', 300),
                'summary' => str_repeat('y', 10001),
                'content' => str_repeat('z', 100001),
                'publication_status' => 'live',
            ]);
            $this->fail('Malformed ingestion must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
        }

        $this->assertSame(0, LibraryItem::query()->where('slug', '')->count());
    }

    public function test_invalid_urls_are_not_fabricated(): void
    {
        $organization = Organization::first();
        $result = $this->ingestLibrary($organization, [
            'slug' => 'ai09-bad-url',
            'title' => 'AI09BadUrlTitle',
            'summary' => 'No fabricated link.',
            'publication_status' => 'published',
            'source' => 'javascript:alert(1)',
            'url' => 'https://evil.example/citation',
            'source_url' => 'not-a-url',
            'metadata' => ['citations' => [['url' => 'https://model.example']]],
        ]);

        $item = LibraryItem::query()->find($result->sourceId);
        $this->assertNull($item->source);
        $this->assertArrayNotHasKey('citations', $item->metadata ?? []);
        $this->assertArrayNotHasKey('url', $item->metadata ?? []);
        $this->assertStringNotContainsString('evil.example', json_encode($item->toArray()));
    }

    public function test_freshness_calculation_is_deterministic(): void
    {
        $freshness = app(KnowledgeFreshnessService::class);
        $freshAt = new DateTimeImmutable('2026-08-10 12:00:00', new DateTimeZone('UTC'));
        $staleAt = new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame('fresh', $freshness->classify($freshAt, $this->now));
        $this->assertSame('stale', $freshness->classify($staleAt, $this->now));
        $this->assertSame('fresh', $freshness->classify($freshAt, $this->now));
        $this->assertSame(0.0, $freshness->rankingScore(null, $this->now));
        $this->assertLessThanOrEqual(2.0, $freshness->rankingScore($freshAt, $this->now));
        $this->assertGreaterThan($freshness->rankingScore($staleAt, $this->now), $freshness->rankingScore($freshAt, $this->now));
    }

    public function test_missing_updated_at_is_handled_safely(): void
    {
        $this->assertSame('unknown', app(KnowledgeFreshnessService::class)->classify(null, $this->now));
        $this->assertSame(0.0, app(KnowledgeRanker::class)->freshness(null));

        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai09-missing-ts',
            'title' => 'AI09MissingTimestamp',
            'summary' => 'Missing timestamp row.',
            'publication_status' => 'published',
        ]);
        DB::table('library_items')->where('id', $created->sourceId)->update(['updated_at' => null]);
        $item = LibraryItem::query()->find($created->sourceId);
        $this->assertSame('unknown', app(KnowledgeFreshnessService::class)->classify($item->updated_at, $this->now));
    }

    public function test_stale_knowledge_is_identified_correctly(): void
    {
        $organization = Organization::first();
        $created = $this->ingestLibrary($organization, [
            'slug' => 'ai09-stale-row',
            'title' => 'AI09StaleRowTitle',
            'summary' => 'Old notes.',
            'publication_status' => 'published',
        ]);
        DB::table('library_items')->where('id', $created->sourceId)->update([
            'updated_at' => '2026-01-01 12:00:00',
        ]);

        $ops = app(KnowledgeRetrievalOperations::class)->retrieve(
            $organization->id,
            'AI09StaleRowTitle',
            ['freshness' => 'stale', 'source_type' => 'library_items'],
            $this->now,
        );

        $this->assertTrue(collect($ops['hits'])->contains(fn ($hit) => ($hit['source_id'] ?? 0) === $created->sourceId));
        $this->assertSame('stale', $ops['hits'][0]['freshness'] ?? null);
    }

    public function test_exact_relevance_still_outranks_freshness(): void
    {
        $organization = Organization::first();
        $relevant = $this->ingestLibrary($organization, [
            'slug' => 'ai09-old-relevant',
            'title' => 'AI09FreshnessGuard',
            'summary' => 'Exact title relevance.',
            'publication_status' => 'published',
        ]);
        $fresh = $this->ingestLibrary($organization, [
            'slug' => 'ai09-new-irrelevant',
            'title' => 'Brand new newsletter',
            'summary' => 'Unrelated.',
            'content' => 'AI09FreshnessGuard is mentioned once in passing.',
            'publication_status' => 'published',
        ]);
        DB::table('library_items')->where('id', $relevant->sourceId)->update(['updated_at' => now()->subDays(300)]);
        DB::table('library_items')->where('id', $fresh->sourceId)->update(['updated_at' => now()]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI09FreshnessGuard');
        $this->assertSame($relevant->sourceId, $result->hits[0]->sourceId);
    }

    public function test_retrieval_telemetry_records_candidate_and_returned_counts(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai09-telemetry-hit',
            'title' => 'AI09TelemetryGuide',
            'summary' => 'Telemetry source.',
            'content' => 'Do not persist this AI09TelemetrySecretPrompt body.',
            'publication_status' => 'published',
        ]);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI09TelemetryGuide AI09TelemetrySecretPrompt'],
        ], $this->adminHeaders())->assertCreated();

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('ok', $usage->retrieval['retrieval_status'] ?? null);
        $this->assertGreaterThan(0, $usage->retrieval['candidate_count'] ?? 0);
        $this->assertGreaterThan(0, $usage->retrieval['returned_count'] ?? 0);
        $this->assertArrayHasKey('freshness_distribution', $usage->retrieval);
        $this->assertArrayHasKey('fresh', $usage->retrieval['freshness_distribution']);
        $this->assertStringNotContainsString('AI09TelemetrySecretPrompt', json_encode($usage->retrieval));
    }

    public function test_zero_result_retrieval_records_safely(): void
    {
        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai09nomatch'],
        ], $this->adminHeaders())->assertCreated();

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('empty', $usage->retrieval['retrieval_status'] ?? null);
        $this->assertSame(0, $usage->retrieval['candidate_count'] ?? null);
        $this->assertSame(0, $usage->retrieval['returned_count'] ?? null);
        $this->assertSame('completed', $created->json('status'));
        $this->assertTrue($created->json('output.disclosure_applied'));
    }

    public function test_telemetry_failure_does_not_break_ai_response(): void
    {
        $this->app->instance(AiUsageRecorder::class, new class extends AiUsageRecorder
        {
            public function recordRetrievalTelemetry(AiRequest $request, array $telemetry): void
            {
                throw new \RuntimeException('telemetry table unavailable api_key=sk-SHOULDNOTLEAK');
            }
        });
        $this->app->forgetInstance(AiService::class);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai09nomatch'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $created->getContent());
    }

    public function test_retrieval_health_summary_is_tenant_scoped(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI09 Org B Health', 'slug' => 'ai09-org-b-health']);
        $this->ingestLibrary($orgA, [
            'slug' => 'ai09-health-a',
            'title' => 'Org A health item',
            'publication_status' => 'published',
        ]);
        $this->ingestLibrary($orgB, [
            'slug' => 'ai09-health-b',
            'title' => 'Org B health item',
            'publication_status' => 'published',
        ]);

        $summaryA = app(KnowledgeRetrievalHealthService::class)->summary($orgA->id, $this->now);
        $summaryB = app(KnowledgeRetrievalHealthService::class)->summary($orgB->id, $this->now);

        $this->assertSame($orgA->id, $summaryA['organization_id']);
        $this->assertGreaterThanOrEqual(1, $summaryA['library_items']['total']);
        $this->assertSame(1, $summaryB['library_items']['total']);
        $this->assertNotEquals($summaryA['library_items']['total'], $summaryB['library_items']['total']);
    }

    public function test_retrieval_health_summary_counts_empty_bodies_and_freshness(): void
    {
        $organization = Organization::first();
        $empty = $this->ingestLibrary($organization, [
            'slug' => 'ai09-empty-body-count',
            'title' => 'AI09EmptyBodyCount',
            'summary' => null,
            'content' => null,
            'publication_status' => 'published',
        ]);
        $stale = $this->ingestLibrary($organization, [
            'slug' => 'ai09-stale-count',
            'title' => 'AI09StaleCount',
            'content' => 'Has a body.',
            'publication_status' => 'published',
        ]);
        DB::table('library_items')->where('id', $stale->sourceId)->update(['updated_at' => '2026-01-01 12:00:00']);
        DB::table('library_items')->where('id', $empty->sourceId)->update(['updated_at' => null]);

        $summary = app(KnowledgeRetrievalHealthService::class)->summary($organization->id, $this->now);

        $this->assertGreaterThanOrEqual(1, $summary['library_items']['empty_body']);
        $this->assertGreaterThanOrEqual(1, $summary['library_items']['freshness']['stale']);
        $this->assertGreaterThanOrEqual(1, $summary['library_items']['freshness']['unknown']);
        $this->assertArrayHasKey('fresh', $summary['library_items']['freshness']);
        $this->assertSame(
            $summary['library_items']['freshness']['fresh']
            + $summary['library_items']['freshness']['stale']
            + $summary['library_items']['freshness']['unknown'],
            $summary['library_items']['total'],
        );
    }

    public function test_source_distribution_is_deterministic(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai09-distribution',
            'title' => 'AI09DistributionTitle',
            'publication_status' => 'published',
        ]);

        $first = app(KnowledgeRetrievalOperations::class)->sourceDistribution($organization->id);
        $second = app(KnowledgeRetrievalOperations::class)->sourceDistribution($organization->id);
        $health = app(KnowledgeRetrievalHealthService::class)->summary($organization->id, $this->now);

        $this->assertSame($first, $second);
        $this->assertSame($first, $health['source_type_distribution']);
        $this->assertSame(['bee_knowledge_topics', 'library_items'], array_keys($first));
    }

    public function test_existing_ai08_retrieval_and_ai07_disclosure_and_ai04_usage_still_work(): void
    {
        $organization = Organization::first();
        $this->ingestLibrary($organization, [
            'slug' => 'ai09-regression-hit',
            'title' => 'AI09RegressionGuide',
            'summary' => 'Regression source.',
            'content' => 'Grounded body.',
            'publication_status' => 'published',
        ]);

        $grounded = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI09RegressionGuide'],
        ], $this->adminHeaders())->assertCreated();
        $this->assertTrue($grounded->json('output.grounded'));
        $this->assertFalse($grounded->json('output.disclosure_applied'));
        $this->assertSame('library_items', $grounded->json('output.sources.0.source_type'));

        $empty = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai09regressionempty'],
        ], $this->adminHeaders())->assertCreated();
        $this->assertTrue($empty->json('output.disclosure_applied'));
        $this->assertSame(AiGroundedAnswerDisclosurePolicy::STATE_EMPTY_RETRIEVAL, $empty->json('output.grounding_state'));

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $grounded->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('completed', $usage->status);
        $this->assertSame('mock', $usage->provider);
    }
}
