<?php

namespace Tests\Feature;

use App\Contracts\AiKnowledgeRetrieverInterface;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiErrorSanitizer;
use App\Services\Ai\Evaluation\RetrievalEvaluationCase;
use App\Services\Ai\Evaluation\RetrievalEvaluationMetrics;
use App\Services\Ai\Evaluation\RetrievalEvaluationRunner;
use App\Services\Ai\Rag\RagOrchestrator;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\AiRetrievalResult;
use App\Services\Ai\Retrieval\KnowledgeIngestionService;
use App\Services\Ai\Retrieval\KnowledgeRetrievalOperationsService;
use App\Services\Ai\Retrieval\KnowledgeSemanticIndexInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Ai15RetrievalEvaluationHarnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('ai.provider', 'mock');
        Config::set('ai.fallback_provider', 'mock');
        Config::set('ai.async_dispatch', false);
        Config::set('ai.retrieval.enabled', true);
        Config::set('ai.retrieval.strategy', 'keyword');
        Config::set('ai.retrieval.semantic_enabled', true);
        Config::set('ai.retrieval.max_results', 5);
        Config::set('ai.retrieval.candidate_limit', 40);
        Config::set('ai.embeddings.enabled', true);
        Config::set('ai.embeddings.provider', 'mock');
        Config::set('ai.embeddings.model', 'mock-hash-v1');
        Config::set('ai.embeddings.dimensions', 64);
        Config::set('ai.openai.api_key', 'sk-ai15-secret-key');
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@wsa.test')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingest(Organization $organization, array $payload): LibraryItem
    {
        $result = app(KnowledgeIngestionService::class)->ingestLibraryItem(
            $organization->id,
            $payload,
            $this->admin()->id,
        );

        return LibraryItem::query()->findOrFail($result->sourceId);
    }

    private function key(LibraryItem $item): string
    {
        return 'library_items:'.$item->id;
    }

    public function test_precision_recall_f1_are_deterministic(): void
    {
        $metrics = app(RetrievalEvaluationMetrics::class);
        $score = $metrics->score(['a', 'b', 'c'], ['a', 'c', 'd'], 3);
        $this->assertSame(0.6667, $score['precision']);
        $this->assertSame(0.6667, $score['recall']);
        $this->assertSame(0.6667, $score['f1']);
        $this->assertSame(1.0, $score['hit']);
        $this->assertSame(1.0, $score['mrr']);
    }

    public function test_empty_results_and_empty_expected_are_safe(): void
    {
        $metrics = app(RetrievalEvaluationMetrics::class);
        $emptyBoth = $metrics->score([], [], 5);
        $this->assertSame(1.0, $emptyBoth['precision']);
        $this->assertSame(1.0, $emptyBoth['recall']);
        $this->assertSame(1.0, $emptyBoth['f1']);
        $this->assertSame(1.0, $emptyBoth['hit']);
        $this->assertSame(0.0, $emptyBoth['mrr']);

        $emptyRetrieved = $metrics->score([], ['a'], 5);
        $this->assertSame(0.0, $emptyRetrieved['precision']);
        $this->assertSame(0.0, $emptyRetrieved['recall']);
        $this->assertSame(0.0, $emptyRetrieved['hit']);
        $this->assertSame(0.0, $emptyRetrieved['mrr']);

        $emptyExpected = $metrics->score(['a'], [], 5);
        $this->assertSame(0.0, $emptyExpected['precision']);
        $this->assertSame(1.0, $emptyExpected['recall']);
        $this->assertSame(0.0, $emptyExpected['hit']);
    }

    public function test_duplicates_and_short_lists_are_handled(): void
    {
        $metrics = app(RetrievalEvaluationMetrics::class);
        $dupes = $metrics->score(['a', 'a', 'b'], ['a', 'a', 'c'], 5);
        $this->assertSame(0.5, $dupes['precision']);
        $this->assertSame(0.5, $dupes['recall']);
        $short = $metrics->score(['a'], ['a', 'b'], 5);
        $this->assertSame(1.0, $short['precision']);
        $this->assertSame(0.5, $short['recall']);
        $this->assertSame(1.0, $short['hit']);
        $this->assertSame(1.0, $short['mrr']);
    }

    public function test_hit_at_k_and_mrr_use_first_relevant_rank(): void
    {
        $metrics = app(RetrievalEvaluationMetrics::class);
        $second = $metrics->score(['x', 'a'], ['a'], 2);
        $this->assertSame(1.0, $second['hit']);
        $this->assertSame(0.5, $second['mrr']);
        $miss = $metrics->score(['x', 'y'], ['a'], 2);
        $this->assertSame(0.0, $miss['hit']);
        $this->assertSame(0.0, $miss['mrr']);
        $beyondK = $metrics->score(['x', 'a'], ['a'], 1);
        $this->assertSame(0.0, $beyondK['hit']);
        $this->assertSame(0.0, $beyondK['mrr']);
    }

    public function test_keyword_evaluation_fixture(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-keyword-exact',
            'title' => 'AI15KeywordExact citrus irrigation',
            'content' => 'AI15KeywordExact citrus irrigation schedule',
            'publication_status' => 'published',
        ]);
        $result = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'keyword-exact',
            organizationId: $organization->id,
            query: 'AI15KeywordExact citrus irrigation',
            expectedIds: [$this->key($item)],
            k: 5,
            strategy: 'keyword',
            expectedTopId: $this->key($item),
        ));
        $this->assertSame('keyword', $result->configuredStrategy);
        $this->assertSame('keyword', $result->effectiveStrategy);
        $this->assertTrue($result->expectedTopMatched);
        $this->assertSame(1.0, $result->metrics['hit']);
        $this->assertGreaterThan(0.0, $result->metrics['precision']);
    }

    public function test_semantic_and_hybrid_evaluation_use_mock_embeddings(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-semantic-orchard',
            'title' => 'AI15SemanticOrchard blight notes',
            'content' => 'AI15SemanticOrchard blight notes',
            'publication_status' => 'published',
        ]);
        $semantic = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'semantic-mock',
            organizationId: $organization->id,
            query: 'AI15SemanticOrchard blight notes',
            expectedIds: [$this->key($item)],
            strategy: 'semantic',
        ));
        $this->assertSame('semantic', $semantic->configuredStrategy);
        $this->assertContains($semantic->effectiveStrategy, ['semantic', 'keyword']);
        $this->assertContains($this->key($item), $semantic->retrievedIds);

        $hybrid = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'hybrid-mock',
            organizationId: $organization->id,
            query: 'AI15SemanticOrchard blight notes',
            expectedIds: [$this->key($item)],
            strategy: 'hybrid',
        ));
        $this->assertSame('hybrid', $hybrid->configuredStrategy);
        $this->assertContains($this->key($item), $hybrid->retrievedIds);
    }

    public function test_semantic_unavailable_fallback_is_reported_honestly(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-fallback',
            'title' => 'AI15Fallback citrus notes',
            'content' => 'AI15Fallback citrus notes',
            'publication_status' => 'published',
        ]);
        $index = \Mockery::mock(KnowledgeSemanticIndexInterface::class);
        $index->shouldReceive('isAvailable')->andReturn(false);
        $this->app->instance(KnowledgeSemanticIndexInterface::class, $index);
        $this->app->forgetInstance(RagOrchestrator::class);
        $this->app->forgetInstance(RetrievalEvaluationRunner::class);
        $this->app->forgetInstance(AiKnowledgeRetrieverInterface::class);

        $result = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'semantic-fallback',
            organizationId: $organization->id,
            query: 'AI15Fallback citrus notes',
            expectedIds: [$this->key($item)],
            strategy: 'semantic',
        ));
        $this->assertSame('semantic', $result->configuredStrategy);
        $this->assertSame('keyword', $result->effectiveStrategy);
        $this->assertSame('semantic_unavailable', $result->fallbackReason);
        $this->assertContains($this->key($item), $result->retrievedIds);
    }

    public function test_tenant_isolation_unpublished_and_deleted_are_excluded(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::query()->where('id', '!=', $orgA->id)->first()
            ?? Organization::query()->create(['name' => 'AI15 Org B', 'slug' => 'ai15-org-b']);
        $visible = $this->ingest($orgA, [
            'slug' => 'ai15-visible',
            'title' => 'AI15IsolationTerm citrus',
            'content' => 'AI15IsolationTerm citrus',
            'publication_status' => 'published',
        ]);
        $draft = $this->ingest($orgA, [
            'slug' => 'ai15-draft',
            'title' => 'AI15IsolationTerm draft',
            'content' => 'AI15IsolationTerm draft',
            'publication_status' => 'draft',
        ]);
        $foreign = $this->ingest($orgB, [
            'slug' => 'ai15-foreign',
            'title' => 'AI15IsolationTerm citrus',
            'content' => 'AI15IsolationTerm citrus',
            'publication_status' => 'published',
        ]);
        $deleted = $this->ingest($orgA, [
            'slug' => 'ai15-deleted',
            'title' => 'AI15IsolationTerm deleted',
            'content' => 'AI15IsolationTerm deleted',
            'publication_status' => 'published',
        ]);
        $deleted->delete();

        $result = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'isolation',
            organizationId: $orgA->id,
            query: 'AI15IsolationTerm citrus',
            expectedIds: [$this->key($visible)],
            strategy: 'hybrid',
        ));
        $this->assertContains($this->key($visible), $result->retrievedIds);
        $this->assertNotContains($this->key($draft), $result->retrievedIds);
        $this->assertNotContains($this->key($foreign), $result->retrievedIds);
        $this->assertNotContains('library_items:'.$deleted->id, $result->retrievedIds);
    }

    public function test_duplicate_knowledge_is_suppressed_and_empty_query_is_safe(): void
    {
        $organization = Organization::first();
        $this->ingest($organization, [
            'slug' => 'ai15-dup-a',
            'title' => 'AI15DupTitle identical body',
            'content' => 'AI15DupBody identical excerpt for citrus.',
            'publication_status' => 'published',
        ]);
        $this->ingest($organization, [
            'slug' => 'ai15-dup-b',
            'title' => 'AI15DupTitle identical body',
            'content' => 'AI15DupBody identical excerpt for citrus.',
            'publication_status' => 'published',
        ]);
        $dupes = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'duplicates',
            organizationId: $organization->id,
            query: 'AI15DupTitle identical body citrus',
            expectedIds: [],
            strategy: 'keyword',
        ));
        $this->assertSame(count($dupes->retrievedIds), count(array_unique($dupes->retrievedIds)));
        $duplicateTitles = 0;
        foreach ($dupes->retrievedIds as $id) {
            $itemId = (int) substr($id, strlen('library_items:'));
            $item = LibraryItem::query()->find($itemId);
            if ($item?->title === 'AI15DupTitle identical body') {
                $duplicateTitles++;
            }
        }
        $this->assertLessThanOrEqual(1, $duplicateTitles);

        $empty = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'empty',
            organizationId: $organization->id,
            query: 'AI15NoSuchKnowledge zzzqqq',
            expectedIds: [],
            strategy: 'keyword',
        ));
        $this->assertSame([], $empty->retrievedIds);
        $this->assertSame(1.0, $empty->metrics['precision']);
        $this->assertSame(1.0, $empty->metrics['recall']);
    }

    public function test_deterministic_tie_break_uses_existing_ranker(): void
    {
        $organization = Organization::first();
        $now = Carbon::parse('2026-08-20 12:00:00');
        $left = new AiRetrievalHit(
            sourceType: 'library_items',
            sourceId: 20,
            title: 'AI15Tie',
            content: 'AI15Tie',
            score: 1.0,
            organizationId: $organization->id,
            updatedAt: $now,
        );
        $right = new AiRetrievalHit(
            sourceType: 'library_items',
            sourceId: 10,
            title: 'AI15Tie',
            content: 'AI15Tie-other',
            score: 1.0,
            organizationId: $organization->id,
            updatedAt: $now,
        );
        $retriever = \Mockery::mock(AiKnowledgeRetrieverInterface::class);
        $retriever->shouldReceive('retrieve')->andReturn(new AiRetrievalResult([$left, $right], 'x', [
            'retrieval_status' => 'ok',
            'retrieval_strategy' => 'keyword',
        ]));
        $this->app->instance(AiKnowledgeRetrieverInterface::class, $retriever);
        $this->app->forgetInstance(RagOrchestrator::class);
        $this->app->forgetInstance(RetrievalEvaluationRunner::class);

        $first = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'tie-break',
            organizationId: $organization->id,
            query: 'AI15Tie',
            expectedIds: ['library_items:10'],
            strategy: 'keyword',
            expectedTopId: 'library_items:10',
        ));
        $second = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'tie-break',
            organizationId: $organization->id,
            query: 'AI15Tie',
            expectedIds: ['library_items:10'],
            strategy: 'keyword',
            expectedTopId: 'library_items:10',
        ));
        $this->assertSame(['library_items:10', 'library_items:20'], $first->retrievedIds);
        $this->assertSame($first->retrievedIds, $second->retrievedIds);
        $this->assertTrue($first->expectedTopMatched);
        $this->assertSame($first->metrics, $second->metrics);
    }

    public function test_complete_report_is_idempotent_and_contains_no_secrets(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-report',
            'title' => 'AI15Report citrus irrigation',
            'content' => 'AI15Report citrus irrigation',
            'publication_status' => 'published',
        ]);
        $cases = [
            new RetrievalEvaluationCase(
                id: 'report-keyword',
                organizationId: $organization->id,
                query: 'AI15Report citrus irrigation',
                expectedIds: [$this->key($item)],
                strategy: 'keyword',
            ),
            new RetrievalEvaluationCase(
                id: 'report-empty',
                organizationId: $organization->id,
                query: 'AI15Missing zzzqqq',
                expectedIds: [],
                strategy: 'hybrid',
            ),
        ];
        $runner = app(RetrievalEvaluationRunner::class);
        $first = $runner->runMany($cases)->toArray();
        $second = $runner->runMany($cases)->toArray();
        $this->assertSame($first['summary'], $second['summary']);
        $this->assertCount(2, $first['results']);
        $this->assertArrayHasKey('precision', $first['results'][0]);
        $this->assertArrayHasKey('recall', $first['results'][0]);
        $this->assertArrayHasKey('f1', $first['results'][0]);
        $this->assertArrayHasKey('hit', $first['results'][0]);
        $this->assertArrayHasKey('mrr', $first['results'][0]);
        $this->assertArrayHasKey('effective_strategy', $first['results'][0]);
        $encoded = json_encode($first);
        $this->assertStringNotContainsString('sk-ai15-secret-key', $encoded);
        $this->assertStringNotContainsString('Bearer ', $encoded);
        $this->assertStringNotContainsString('Authorization', $encoded);
        $this->assertStringNotContainsString('sk-ai15-secret-key', AiErrorSanitizer::redact('failed api_key=sk-ai15-secret-key'));
    }

    public function test_irrelevant_result_scores_zero_hit_and_evaluation_does_not_call_provider(): void
    {
        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-relevant',
            'title' => 'AI15Relevant citrus',
            'content' => 'AI15Relevant citrus',
            'publication_status' => 'published',
        ]);
        Http::fake();
        $result = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'irrelevant',
            organizationId: $organization->id,
            query: 'AI15NoOverlap zzzqqq',
            expectedIds: [$this->key($item)],
            strategy: 'keyword',
        ));
        $this->assertSame(0.0, $result->metrics['hit']);
        $this->assertSame(0.0, $result->metrics['mrr']);
        Http::assertNothingSent();
        $this->assertSame('keyword', config('ai.retrieval.strategy'));
    }

    public function test_invalid_strategy_is_normalized_and_k_cuts_the_ranked_list(): void
    {
        $metrics = app(RetrievalEvaluationMetrics::class);
        $cut = $metrics->score(['a', 'b', 'c'], ['c'], 2);
        $this->assertSame(0.0, $cut['hit']);
        $this->assertSame(0.0, $cut['mrr']);
        $this->assertSame(2, $cut['k']);

        $organization = Organization::first();
        $item = $this->ingest($organization, [
            'slug' => 'ai15-invalid-strategy',
            'title' => 'AI15InvalidStrategy citrus',
            'content' => 'AI15InvalidStrategy citrus',
            'publication_status' => 'published',
        ]);
        $result = app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'invalid-strategy',
            organizationId: $organization->id,
            query: 'AI15InvalidStrategy citrus',
            expectedIds: [$this->key($item)],
            strategy: 'not-a-strategy',
        ));
        $this->assertSame('keyword', $result->configuredStrategy);
        $this->assertContains($this->key($item), $result->retrievedIds);
        $this->assertSame('keyword', config('ai.retrieval.strategy'));
    }

    public function test_operations_strategy_contract_is_unchanged_by_evaluation(): void
    {
        $organization = Organization::first();
        $before = app(KnowledgeRetrievalOperationsService::class)->strategy();
        app(RetrievalEvaluationRunner::class)->run(new RetrievalEvaluationCase(
            id: 'contract',
            organizationId: $organization->id,
            query: 'AI15Contract zzzqqq',
            expectedIds: [],
            strategy: 'hybrid',
        ));
        $after = app(KnowledgeRetrievalOperationsService::class)->strategy();
        $this->assertSame($before['configured_strategy'], $after['configured_strategy']);
        $this->assertTrue($after['rag_orchestration'] ?? false);
        $this->assertArrayHasKey('reranker', $after);
        $this->assertSame('keyword', config('ai.retrieval.strategy'));
    }
}
