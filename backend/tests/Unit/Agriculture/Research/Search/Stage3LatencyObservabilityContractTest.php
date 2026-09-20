<?php

namespace Tests\Unit\Agriculture\Research\Search;

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
use Tests\TestCase;

/**
 * RC-L Phase-1: instrumentation-only contract — map observability.latency_ms →
 * top-level duration_ms and surface stage3_elapsed_ms on the Stage-3 report.
 * No variant/provider/threshold behavior changes asserted beyond timing presence.
 */
class Stage3LatencyObservabilityContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_outcome_to_array_maps_observability_latency_ms_to_duration_ms(): void
    {
        $outcome = new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [],
            observability: ['latency_ms' => 1234, 'http_status' => 200],
        );

        $array = $outcome->toArray();

        $this->assertSame(1234, $array['duration_ms']);
        $this->assertSame(1234, $array['observability']['latency_ms']);
    }

    public function test_outcome_to_array_leaves_duration_ms_null_when_latency_absent(): void
    {
        $outcome = new ScientificSourceSearchOutcome(
            sourceKey: 'crossref',
            status: ScientificSourceSearchOutcome::STATUS_EMPTY,
            results: [],
            observability: ['http_status' => 200],
        );

        $this->assertNull($outcome->toArray()['duration_ms']);
    }

    public function test_outcome_to_array_leaves_duration_ms_null_when_observability_null(): void
    {
        $outcome = new ScientificSourceSearchOutcome(
            sourceKey: 'semantic_scholar',
            status: ScientificSourceSearchOutcome::STATUS_FAILED,
            results: [],
            error: 'timeout',
        );

        $this->assertNull($outcome->toArray()['duration_ms']);
    }

    public function test_execute_report_exposes_stage3_and_provider_timings(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);

        $adapter = Mockery::mock(ScientificSourceAdapterInterface::class);
        $adapter->shouldReceive('sourceKey')->andReturn('openalex');
        $adapter->shouldReceive('isEnabled')->andReturn(true);
        $adapter->shouldReceive('search')->andReturn(new ScientificSourceSearchOutcome(
            sourceKey: 'openalex',
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: 'openalex',
                    sourceIdentifier: 'oa-lat-1',
                    title: 'Latency instrumentation fixture',
                    authors: ['A Researcher'],
                    publicationYear: 2024,
                    doi: '10.1000/rcl-lat',
                    canonicalUrl: 'https://example.test/oa-lat-1',
                    abstract: 'Home research latency observability fixture.',
                    journal: 'Ag Journal',
                    foundBySources: ['openalex'],
                ),
            ],
            observability: ['latency_ms' => 450],
        ));

        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturn([$adapter]);

        $queryBuilder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $queryBuilder->shouldReceive('buildVariantsFromPlan')->andReturn(['home wheat yield research']);
        $queryBuilder->shouldReceive('buildFromPlan')->andReturn('home wheat yield research');
        $queryBuilder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $queryBuilder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );

        $report = $orchestrator->execute($this->homePlan(), 5, ['openalex']);
        $array = $report->toArray();

        $this->assertArrayHasKey('stage3_elapsed_ms', $array['plan']);
        $this->assertIsInt($array['plan']['stage3_elapsed_ms']);
        $this->assertGreaterThanOrEqual(0, $array['plan']['stage3_elapsed_ms']);
        $this->assertSame(['openalex' => 450], $array['plan']['provider_duration_ms']);
        $this->assertSame(450, $array['source_outcomes'][0]['duration_ms'] ?? null);
        $this->assertSame(450, $array['timings']['provider_duration_ms']['openalex'] ?? null);
        $this->assertArrayHasKey('stage3_elapsed_ms', $array['timings']);
    }

    private function homePlan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'What is the average wheat yield in Egypt?',
            normalizedQuestion: 'What is the average wheat yield in Egypt?',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: null,
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
            subjectEntity: ['type' => 'crop', 'value' => 'wheat'],
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
