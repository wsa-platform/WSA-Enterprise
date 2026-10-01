<?php

namespace Tests\Unit\Agriculture\Research\Coexistence;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * IU-09 correction — prove Orchestrator search observability attaches cghia_coexistence.
 */
final class Stage3CoexistenceSearchObservabilityAttachTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_observability_includes_cghia_coexistence_when_plan_attached(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
            'agricultural_intelligence.cghia_coexistence.mode' => 'cghia_attached',
        ]);

        $report = $this->orchestrator([$this->openAlexAdapter()], ['wheat yield egypt'])
            ->execute($this->plan(), 5, ['openalex']);

        $obs = $report->planSummary['search_observability'] ?? null;
        $this->assertIsArray($obs);
        $this->assertArrayHasKey('cghia_coexistence', $obs);
        $this->assertSame('cghia_attached', $obs['cghia_coexistence']['mode']);
        $this->assertContains('openalex', $obs['cghia_coexistence']['selector_source_keys']);

        // Existing Stage-3 observability keys remain intact (augment, do not replace).
        $this->assertArrayHasKey('selected_providers', $obs);
        $this->assertArrayHasKey('provider_status', $obs);
        $this->assertArrayHasKey('concurrency_mode', $obs);
        $this->assertSame(['openalex'], array_values($obs['selected_providers']));

        $entry = $obs['cghia_coexistence']['entries'][0] ?? null;
        $this->assertIsArray($entry);
        $this->assertSame('openalex', $entry['source_key']);
        $this->assertFalse($entry['cghia_artifacts_attached']);
        $this->assertFalse($entry['verified_automation_claimed']);
        $this->assertTrue($entry['legacy_compatible']);
    }

    public function test_legacy_only_search_observability_is_additive_without_cghia_artifacts(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
            'agricultural_intelligence.cghia_coexistence.mode' => 'legacy_only',
        ]);

        $report = $this->orchestrator([$this->openAlexAdapter()], ['wheat yield egypt'])
            ->execute($this->plan(), 5, ['openalex']);

        $this->assertSame(['openalex'], $report->selectedSources);
        $this->assertContains('openalex', $report->successfulSources);
        $this->assertNotEmpty($report->results);

        $obs = $report->planSummary['search_observability'] ?? null;
        $this->assertIsArray($obs);
        $this->assertArrayHasKey('selected_providers', $obs);
        $this->assertArrayHasKey('provider_status', $obs);
        $this->assertSame(['openalex'], array_values($obs['selected_providers']));

        $this->assertArrayHasKey('cghia_coexistence', $obs);
        $this->assertSame('legacy_only', $obs['cghia_coexistence']['mode']);

        $entry = $obs['cghia_coexistence']['entries'][0] ?? null;
        $this->assertIsArray($entry);
        $this->assertFalse($entry['cghia_artifacts_attached']);
        $this->assertFalse($entry['verified_automation_claimed']);
        $this->assertNull($entry['projection_identity'] ?? null);
        $this->assertNull($entry['capability_decision_identity'] ?? null);
        $this->assertNull($entry['path_decision_identity'] ?? null);
    }

    public function test_null_coexistence_plan_does_not_emit_cghia_coexistence_fragment(): void
    {
        $orchestrator = $this->orchestrator([$this->openAlexAdapter()], ['wheat yield egypt']);
        $method = new ReflectionMethod(MultiSourceScientificSearchOrchestrator::class, 'buildSearchObservability');
        $method->setAccessible(true);

        $obs = $method->invoke(
            $orchestrator,
            ['selected' => ['openalex'], 'skipped_inactive' => [], 'optional_not_selected' => [], 'policy' => null],
            ['output_count' => 1, 'input_count' => 1, 'equivalent_suppressed' => 0, 'truncated' => 0, 'variants' => ['wheat']],
            ['openalex'],
            ['openalex' => 'success'],
            [],
            [],
            1,
            1,
            1,
            'sequential',
            null,
        );

        $this->assertIsArray($obs);
        $this->assertArrayNotHasKey('cghia_coexistence', $obs);
        $this->assertArrayHasKey('selected_providers', $obs);
        $this->assertArrayHasKey('provider_status', $obs);
    }

    /**
     * @param  list<ScientificSourceAdapterInterface>  $adapters
     * @param  list<string>  $variants
     */
    private function orchestrator(array $adapters, array $variants): MultiSourceScientificSearchOrchestrator
    {
        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturnUsing(
            function (array $keys) use ($adapters): array {
                $map = [];
                foreach ($adapters as $adapter) {
                    $map[$adapter->sourceKey()] = $adapter;
                }
                $resolved = [];
                foreach ($keys as $key) {
                    if (isset($map[$key])) {
                        $resolved[] = $map[$key];
                    }
                }

                return $resolved;
            }
        );

        $queryBuilder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $queryBuilder->shouldReceive('buildVariantsFromPlan')->andReturn($variants);
        $queryBuilder->shouldReceive('buildFromPlan')->andReturn($variants[0] ?? 'agriculture');
        $queryBuilder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $queryBuilder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    private function openAlexAdapter(): ScientificSourceAdapterInterface
    {
        $adapter = Mockery::mock(ScientificSourceAdapterInterface::class);
        $adapter->shouldReceive('sourceKey')->andReturn('openalex');
        $adapter->shouldReceive('isEnabled')->andReturn(true);
        $adapter->shouldReceive('search')->andReturn(new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: 'openalex',
                    sourceIdentifier: 'oa-iu09-obs-1',
                    title: 'Wheat yield fixture for coexistence observability',
                    authors: ['A Researcher'],
                    publicationYear: 2024,
                    doi: '10.1000/iu09-obs',
                    canonicalUrl: 'https://example.test/oa-iu09-obs-1',
                    abstract: 'Deterministic Stage-3 fixture for IU-09 observability attach.',
                    journal: 'Ag Journal',
                    foundBySources: ['openalex'],
                ),
            ],
            observability: ['latency_ms' => 12],
        ));

        return $adapter;
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'What is the average wheat yield in Egypt?',
            normalizedQuestion: 'What is the average wheat yield in Egypt?',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'Wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'yield',
            subtopic: null,
            requestedInformation: ['average'],
            constraints: [
                'question_type' => 'research',
            ],
            location: 'egypt',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'generic_research',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'generic_research',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'wheat', 'label' => 'Wheat'],
            topics: ['yield'],
            subtopics: [],
            requestedInformation: ['average'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
