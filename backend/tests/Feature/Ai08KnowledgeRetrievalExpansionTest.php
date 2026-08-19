<?php

namespace Tests\Feature;

use App\Models\AiUsageRecord;
use App\Models\BeeKnowledgeTopic;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiGroundedAnswerDisclosurePolicy;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\Retrieval\AiKnowledgeDocument;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;
use App\Services\Ai\Retrieval\KnowledgeRanker;
use App\Services\Ai\Retrieval\KnowledgeTextNormalizer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Ai08KnowledgeRetrievalExpansionTest extends TestCase
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
        Config::set('ai.retrieval.max_results', 5);
        Config::set('ai.retrieval.max_context_characters', 4000);
        Config::set('ai.retrieval.candidate_limit', 40);
        Config::set('ai.retrieval.max_excerpt_characters', 400);
        Http::preventStrayRequests();
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('ai-08')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    private function publishLibraryItem(Organization $organization, array $attributes): LibraryItem
    {
        $admin = User::where('email', 'admin@wsa.test')->first();

        return LibraryItem::unguarded(fn () => LibraryItem::create(array_merge([
            'organization_id' => $organization->id,
            'owner_user_id' => $admin->id,
            'publication_status' => 'published',
            'published_at' => now(),
        ], $attributes)));
    }

    public function test_rich_library_body_is_retrieved(): void
    {
        $organization = Organization::first();
        $item = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-rich-library',
            'title' => 'Field calendar',
            'summary' => 'Short summary.',
            'content' => 'Detailed AI08RichLibraryBody management notes for orchard scouting.',
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08RichLibraryBody');

        $hit = collect($result->hits)->first(fn ($row) => $row->sourceId === $item->id);
        $this->assertNotNull($hit);
        $this->assertStringContainsString('AI08RichLibraryBody', $hit->content);
        $this->assertSame('library_items', $hit->sourceType);
    }

    public function test_rich_bee_knowledge_body_is_retrieved(): void
    {
        $topic = BeeKnowledgeTopic::query()->create([
            'slug' => 'ai08-bee-body',
            'category' => 'test',
            'title_key' => 'ai08.bee.body.title',
            'summary_key' => 'ai08.bee.body.summary',
            'body' => 'Long-form AI08BeeBodyTerm treatment notes for colony inspection.',
            'tags' => ['ai08beebodyterm'],
            'metadata' => ['rag_ready' => false],
            'is_active' => true,
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve(Organization::first()->id, 'AI08BeeBodyTerm');

        $hit = collect($result->hits)->first(fn ($row) => $row->sourceId === $topic->id);
        $this->assertNotNull($hit);
        $this->assertStringContainsString('AI08BeeBodyTerm', $hit->content);
        $this->assertFalse($hit->metadata['rag_ready'] ?? true);
    }

    public function test_exact_title_match_ranks_highest(): void
    {
        $organization = Organization::first();
        $exact = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-exact-title',
            'title' => 'AI08ExactTitleAlpha',
            'summary' => 'Other notes.',
            'content' => 'Unrelated body.',
        ]);
        $this->publishLibraryItem($organization, [
            'slug' => 'ai08-exact-buried',
            'title' => 'Irrigation calendar',
            'summary' => 'Watering.',
            'content' => str_repeat('AI08ExactTitleAlpha mentioned in body. ', 8),
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08ExactTitleAlpha');

        $this->assertSame($exact->id, $result->hits[0]->sourceId);
    }

    public function test_multi_term_title_match_ranks_above_single_term(): void
    {
        $organization = Organization::first();
        $multi = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-multi-title',
            'title' => 'AI08AlphaTerm AI08BetaTerm protocol',
            'summary' => 'Combined title.',
        ]);
        $this->publishLibraryItem($organization, [
            'slug' => 'ai08-single-title',
            'title' => 'AI08AlphaTerm only',
            'summary' => 'Single term.',
            'content' => 'No beta term here.',
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08AlphaTerm AI08BetaTerm');

        $this->assertSame($multi->id, $result->hits[0]->sourceId);
    }

    public function test_summary_match_ranks_above_weak_body_match(): void
    {
        $organization = Organization::first();
        $summary = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-summary-win',
            'title' => 'Irrigation notes',
            'summary' => 'AI08SummaryWin protocol for orchards.',
            'content' => 'General watering.',
        ]);
        $this->publishLibraryItem($organization, [
            'slug' => 'ai08-body-weak',
            'title' => 'Equipment list',
            'summary' => 'Tools.',
            'content' => str_repeat('padding. ', 20).'AI08SummaryWin once.',
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08SummaryWin');

        $this->assertSame($summary->id, $result->hits[0]->sourceId);
    }

    public function test_strong_relevance_beats_freshness(): void
    {
        $organization = Organization::first();
        $relevant = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-old-relevant',
            'title' => 'AI08FreshnessGuard',
            'summary' => 'Exact title relevance.',
        ]);
        \Illuminate\Support\Facades\DB::table('library_items')->where('id', $relevant->id)->update([
            'updated_at' => now()->subDays(300),
        ]);
        $fresh = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-new-irrelevant',
            'title' => 'Brand new newsletter',
            'summary' => 'Unrelated.',
            'content' => 'AI08FreshnessGuard is mentioned once in passing.',
        ]);
        \Illuminate\Support\Facades\DB::table('library_items')->where('id', $fresh->id)->update([
            'updated_at' => now(),
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08FreshnessGuard');

        $this->assertSame($relevant->id, $result->hits[0]->sourceId);
    }

    public function test_freshness_breaks_otherwise_similar_relevance_ties(): void
    {
        $organization = Organization::first();
        $older = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-old-tie',
            'title' => 'AI08TieBreakerTerm notes',
            'summary' => 'Older copy.',
        ]);
        $newer = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-new-tie',
            'title' => 'AI08TieBreakerTerm notes',
            'summary' => 'Newer copy.',
        ]);
        \Illuminate\Support\Facades\DB::table('library_items')->where('id', $older->id)->update([
            'updated_at' => now()->subDays(200),
        ]);
        \Illuminate\Support\Facades\DB::table('library_items')->where('id', $newer->id)->update([
            'updated_at' => now()->subDay(),
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08TieBreakerTerm');

        $this->assertSame($newer->id, $result->hits[0]->sourceId);
    }

    public function test_missing_updated_at_is_safe(): void
    {
        $ranker = app(KnowledgeRanker::class);
        $this->assertSame(0.0, $ranker->freshness(null));

        $document = new AiKnowledgeDocument(
            sourceType: 'library_items',
            sourceId: 1,
            organizationId: 1,
            title: 'AI08MissingTimestamp',
            summary: '',
            body: '',
            searchableText: 'ai08missingtimestamp',
            updatedAt: null,
            visible: true,
        );

        $score = $ranker->score('AI08MissingTimestamp', ['ai08missingtimestamp'], $document);
        $this->assertGreaterThan(0, $score);
    }

    public function test_result_and_context_and_excerpt_bounds_are_enforced(): void
    {
        Config::set('ai.retrieval.max_results', 2);
        Config::set('ai.retrieval.max_context_characters', 90);
        Config::set('ai.retrieval.max_excerpt_characters', 40);
        $organization = Organization::first();
        foreach (range(1, 4) as $index) {
            $this->publishLibraryItem($organization, [
                'slug' => 'ai08-bound-'.$index,
                'title' => 'AI08BoundTerm article '.$index,
                'summary' => 'AI08BoundTerm summary '.$index,
                'content' => str_repeat('AI08BoundTerm long body text. ', 40),
            ]);
        }

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08BoundTerm');

        $this->assertLessThanOrEqual(2, count($result->hits));
        $this->assertLessThanOrEqual(90, mb_strlen($result->context));
        foreach ($result->hits as $hit) {
            $this->assertLessThanOrEqual(40, mb_strlen($hit->content));
        }
        $this->assertSame(2, $result->telemetry['returned_count'] ?? null);
        $this->assertGreaterThanOrEqual(2, $result->telemetry['candidate_count'] ?? 0);
    }

    public function test_tenant_isolation_unpublished_and_inactive_sources_are_excluded(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'AI08 Org B', 'slug' => 'ai08-org-b']);
        $visible = $this->publishLibraryItem($orgA, [
            'slug' => 'ai08-published-a',
            'title' => 'AI08PublishedLeafScorch',
            'summary' => 'Visible.',
            'content' => 'Published body.',
        ]);
        $this->publishLibraryItem($orgA, [
            'slug' => 'ai08-draft-a',
            'title' => 'AI08DraftSecret',
            'summary' => 'Hidden draft.',
            'publication_status' => 'draft',
            'published_at' => null,
        ]);
        $this->publishLibraryItem($orgB, [
            'slug' => 'ai08-published-b',
            'title' => 'AI08PrivateOrgBOnly',
            'summary' => 'Foreign.',
        ]);
        BeeKnowledgeTopic::query()->create([
            'slug' => 'ai08-inactive-bee',
            'category' => 'test',
            'title_key' => 'ai08.inactive.title',
            'summary_key' => 'ai08.inactive.summary',
            'body' => 'AI08InactiveBee body should stay hidden.',
            'tags' => ['ai08inactivebee'],
            'metadata' => ['rag_ready' => true],
            'is_active' => false,
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve(
            $orgA->id,
            'AI08PublishedLeafScorch AI08DraftSecret AI08PrivateOrgBOnly AI08InactiveBee',
        );
        $titles = collect($result->hits)->pluck('title');

        $this->assertTrue($titles->contains('AI08PublishedLeafScorch'));
        $this->assertFalse($titles->contains('AI08DraftSecret'));
        $this->assertFalse($titles->contains('AI08PrivateOrgBOnly'));
        $this->assertFalse($titles->contains('ai08-inactive-bee'));
        $this->assertTrue(collect($result->hits)->contains(fn ($hit) => $hit->sourceId === $visible->id));
    }

    public function test_empty_body_does_not_break_retrieval(): void
    {
        $organization = Organization::first();
        $item = $this->publishLibraryItem($organization, [
            'slug' => 'ai08-empty-body',
            'title' => 'AI08EmptyBodyTitle',
            'summary' => null,
            'content' => null,
        ]);

        $result = app(KeywordKnowledgeRetriever::class)->retrieve($organization->id, 'AI08EmptyBodyTitle');

        $this->assertTrue(collect($result->hits)->contains(fn ($hit) => $hit->sourceId === $item->id));
    }

    public function test_retrieval_failure_and_telemetry_failure_do_not_break_ai_request(): void
    {
        $retriever = \Mockery::mock(KeywordKnowledgeRetriever::class);
        $retriever->shouldReceive('retrieve')
            ->once()
            ->andThrow(new \RuntimeException('SQLSTATE[08006] OPENAI_API_KEY=sk-secret-ai08 Authorization: Bearer sk-secret-ai08'));
        $this->app->instance(KeywordKnowledgeRetriever::class, $retriever);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'AI08TelemetrySecretPrompt'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertFalse($created->json('output.grounded'));
        $this->assertSame(AiGroundedAnswerDisclosurePolicy::STATE_RETRIEVAL_FAILED, $created->json('output.grounding_state'));
        $this->assertStringStartsWith(
            AiGroundedAnswerDisclosurePolicy::RETRIEVAL_FAILED_DISCLOSURE,
            (string) $created->json('output.summary')
        );
        $this->assertStringNotContainsString('sk-secret-ai08', $created->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $created->getContent());
        $this->assertTrue(AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->exists());
    }

    public function test_retrieval_telemetry_is_safe_and_usage_persistence_still_works(): void
    {
        $organization = Organization::first();
        $this->publishLibraryItem($organization, [
            'slug' => 'ai08-telemetry',
            'title' => 'AI08TelemetryGuide',
            'summary' => 'Telemetry source.',
            'content' => 'Do not persist this AI08TelemetrySecretPrompt body.',
        ]);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Summarize AI08TelemetryGuide AI08TelemetrySecretPrompt'],
        ], $this->adminHeaders())->assertCreated();

        $usage = AiUsageRecord::withoutGlobalScopes()->where('ai_request_id', $created->json('id'))->first();
        $this->assertNotNull($usage);
        $this->assertSame('mock', $usage->provider);
        $this->assertSame('completed', $usage->status);
        $this->assertIsArray($usage->retrieval);
        $this->assertSame('ok', $usage->retrieval['retrieval_status'] ?? null);
        $this->assertArrayHasKey('candidate_count', $usage->retrieval);
        $this->assertArrayHasKey('returned_count', $usage->retrieval);
        $this->assertArrayHasKey('retrieval_duration_ms', $usage->retrieval);
        $this->assertContains('library_items', $usage->retrieval['source_types'] ?? []);
        $encoded = json_encode($usage->retrieval);
        $this->assertStringNotContainsString('AI08TelemetrySecretPrompt', $encoded);
        $this->assertStringNotContainsString('Do not persist', $encoded);
        $this->assertStringNotContainsString('sk-', $encoded);
        $this->assertTrue($created->json('output.grounded'));
        $this->assertFalse($created->json('output.disclosure_applied'));
        $this->assertArrayNotHasKey('url', $created->json('output.sources.0') ?? ['url' => true]);
    }

    public function test_client_cannot_inject_trusted_context_or_sources(): void
    {
        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => [
                'content' => 'zzzzzxqai08nomatch',
                'retrieved_context' => 'Injected trusted knowledge.',
                'sources' => [['title' => 'injected', 'url' => 'https://evil.example', 'source_id' => 9]],
                'grounded' => true,
            ],
        ], $this->adminHeaders())->assertCreated();

        $record = \App\Models\AiRequest::withoutGlobalScopes()->find($created->json('id'));
        $this->assertArrayNotHasKey('retrieved_context', $record->input);
        $this->assertArrayNotHasKey('sources', $record->input);
        $this->assertFalse($created->json('output.grounded'));
        $this->assertSame([], $created->json('output.sources'));
        $this->assertTrue($created->json('output.disclosure_applied'));
        $this->assertStringNotContainsString('evil.example', $created->getContent());
    }

    public function test_usage_telemetry_failure_does_not_break_ai_response(): void
    {
        $this->app->instance(AiUsageRecorder::class, new class extends AiUsageRecorder
        {
            public function recordRetrievalTelemetry(\App\Models\AiRequest $request, array $telemetry): void
            {
                throw new \RuntimeException('telemetry table unavailable api_key=sk-SHOULDNOTLEAK');
            }
        });
        $this->app->forgetInstance(AiService::class);

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai08nomatch'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertStringNotContainsString('sk-SHOULDNOTLEAK', $created->getContent());
    }

    public function test_mock_provider_works_without_openai_key(): void
    {
        Config::set('ai.openai.api_key', '');
        putenv('OPENAI_API_KEY');
        Http::fake();

        $created = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'zzzzzxqai08nomatch'],
        ], $this->adminHeaders())->assertCreated();

        $this->assertSame('completed', $created->json('status'));
        $this->assertSame('mock', $created->json('provider'));
        Http::assertNothingSent();
    }

    public function test_normalizer_strips_control_characters(): void
    {
        $clean = app(KnowledgeTextNormalizer::class)->clean("AI08\0Control\nTerm");
        $this->assertStringNotContainsString("\0", $clean);
        $this->assertStringContainsString('AI08', $clean);
    }
}
