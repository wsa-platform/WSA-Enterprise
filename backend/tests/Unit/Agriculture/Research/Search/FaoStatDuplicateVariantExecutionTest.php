<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;
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

class FaoStatDuplicateVariantExecutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_complete_faostat_success_skips_redundant_second_variant(): void
    {
        $faoCalls = 0;
        $openAlexCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->completeResult()]);
        });
        $openAlex = $this->adapter('openalex', function () use (&$openAlexCalls) {
            $openAlexCalls++;

            return $this->successOutcome('openalex', [$this->scholarlyResult()]);
        });

        $this->orchestrator($fao, $openAlex)->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(1, $faoCalls);
        $this->assertSame(2, $openAlexCalls);
    }

    public function test_empty_faostat_skips_equivalent_second_variant(): void
    {
        $faoCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return new ScientificSourceSearchOutcome(
                sourceKey: FaoStatRuntimePolicy::canonicalSourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_EMPTY,
                error: 'EMPTY_RESULT',
            );
        });

        $this->orchestrator($fao, $this->idleOpenAlex())->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(1, $faoCalls, 'equivalent FAOSTAT options must not re-execute on EMPTY_RESULT');
    }

    public function test_failed_faostat_skips_equivalent_second_variant(): void
    {
        $faoCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return new ScientificSourceSearchOutcome(
                sourceKey: FaoStatRuntimePolicy::canonicalSourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'UPSTREAM_SERVER_ERROR',
                httpStatus: 500,
            );
        });

        $this->orchestrator($fao, $this->idleOpenAlex())->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(1, $faoCalls, 'equivalent FAOSTAT options must not re-execute after failure');
    }

    public function test_faostat_429_skips_equivalent_second_variant(): void
    {
        $faoCalls = 0;
        $openAlexCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return new ScientificSourceSearchOutcome(
                sourceKey: FaoStatRuntimePolicy::canonicalSourceKey(),
                status: ScientificSourceSearchOutcome::STATUS_FAILED,
                error: 'UPSTREAM_SERVER_ERROR',
                httpStatus: 429,
            );
        });
        $openAlex = $this->adapter('openalex', function () use (&$openAlexCalls) {
            $openAlexCalls++;

            return $this->successOutcome('openalex', [$this->scholarlyResult()]);
        });

        $this->orchestrator($fao, $openAlex)->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(1, $faoCalls, 'FAOSTAT options are variant-invariant; 429 must not re-query the portal');
        $this->assertSame(2, $openAlexCalls);
    }

    public function test_success_without_complete_observation_still_skips_equivalent_variant(): void
    {
        $faoCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->incompleteResult()]);
        });

        $this->orchestrator($fao, $this->idleOpenAlex())->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(1, $faoCalls);
    }

    public function test_complete_faostat_does_not_stop_other_providers(): void
    {
        $openAlexCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () {
            return $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->completeResult()]);
        });
        $openAlex = $this->adapter('openalex', function () use (&$openAlexCalls) {
            $openAlexCalls++;

            return $this->successOutcome('openalex', [$this->scholarlyResult()]);
        });

        $report = $this->orchestrator($fao, $openAlex)->execute($this->plan(), 5, ['fao_stat', 'openalex']);

        $this->assertSame(2, $openAlexCalls);
        $this->assertContains('openalex', $report->attemptedSources);
        $this->assertContains('fao_stat', $report->successfulSources);
        $this->assertContains('openalex', $report->successfulSources);
    }

    public function test_execute_state_does_not_leak_across_calls(): void
    {
        $faoCalls = 0;
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls) {
            $faoCalls++;

            return $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->completeResult()]);
        });
        $orchestrator = $this->orchestrator($fao, $this->idleOpenAlex());

        $orchestrator->execute($this->plan(), 5, ['fao_stat']);
        $orchestrator->execute($this->plan(), 5, ['fao_stat']);

        $this->assertSame(2, $faoCalls, 'each execute() must independently run FAOSTAT variant 1');
    }

    public function test_incomplete_observation_does_not_reexecute_equivalent_query(): void
    {
        $faoCalls = 0;
        $sequence = [
            $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->incompleteResult()]),
            $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), [$this->completeResult()]),
        ];
        $fao = $this->adapter(FaoStatRuntimePolicy::canonicalSourceKey(), function () use (&$faoCalls, &$sequence) {
            $faoCalls++;

            return array_shift($sequence) ?? $this->successOutcome(FaoStatRuntimePolicy::canonicalSourceKey(), []);
        });

        $this->orchestrator($fao, $this->idleOpenAlex())->execute($this->plan(), 5, ['fao_stat']);

        $this->assertSame(1, $faoCalls, 'incomplete observation must not trigger a second equivalent FAOSTAT call');
    }

    public function test_provider_eligibility_remains_unchanged(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => true]);
        $sources = app(ScientificSourceSelector::class)->selectSources($this->plan());
        $this->assertSame('fao_stat', $sources[0] ?? null);
        $this->assertContains('openalex', $sources);
        $this->assertContains('crossref', $sources);
        $this->assertContains('semantic_scholar', $sources);
    }

    private function orchestrator(
        ScientificSourceAdapterInterface $fao,
        ScientificSourceAdapterInterface $openAlex,
    ): MultiSourceScientificSearchOrchestrator {
        $registry = Mockery::mock(ScientificSourceAdapterRegistry::class);
        $registry->shouldReceive('resolveMany')->andReturnUsing(
            function (array $keys) use ($fao, $openAlex): array {
                $map = [
                    $fao->sourceKey() => $fao,
                    $openAlex->sourceKey() => $openAlex,
                ];
                $resolved = [];
                foreach ($keys as $key) {
                    if (isset($map[$key])) {
                        $resolved[] = $map[$key];
                    }
                }

                return $resolved;
            }
        );

        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['variant-a', 'variant-b']);
        $builder->shouldReceive('buildFromPlan')->andReturn('variant-a');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    /**
     * @param  callable(): ScientificSourceSearchOutcome  $search
     */
    private function adapter(string $sourceKey, callable $search): ScientificSourceAdapterInterface
    {
        $adapter = Mockery::mock(ScientificSourceAdapterInterface::class);
        $adapter->shouldReceive('sourceKey')->andReturn($sourceKey);
        $adapter->shouldReceive('displayName')->andReturn($sourceKey);
        $adapter->shouldReceive('search')->andReturnUsing($search);

        return $adapter;
    }

    private function idleOpenAlex(): ScientificSourceAdapterInterface
    {
        return $this->adapter('openalex', function () {
            return $this->successOutcome('openalex', [$this->scholarlyResult()]);
        });
    }

    /**
     * @param  list<ScientificSearchResult>  $results
     */
    private function successOutcome(string $sourceKey, array $results): ScientificSourceSearchOutcome
    {
        return new ScientificSourceSearchOutcome(
            sourceKey: $sourceKey,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: $results,
        );
    }

    private function completeResult(): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: FaoStatRuntimePolicy::canonicalSourceKey(),
            sourceIdentifier: 'QCL|59|15|5510|2020',
            title: 'Wheat — Production — Egypt — 2020',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://faostatservices.fao.org/api/v1/en/data/QCL',
            abstract: 'Value: 9101785 t',
            journal: null,
            foundBySources: [FaoStatRuntimePolicy::canonicalSourceKey()],
            relevanceMetadata: ['not_literature' => true],
            rawMetadata: [
                'faostat' => [
                    'item' => 'Wheat',
                    'area' => 'Egypt',
                    'year' => '2020',
                    'element' => 'Production',
                    'value' => '9101785',
                    'unit' => 't',
                ],
            ],
        );
    }

    private function incompleteResult(): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: FaoStatRuntimePolicy::canonicalSourceKey(),
            sourceIdentifier: 'partial',
            title: 'Incomplete observation',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: null,
            abstract: 'Value: 1',
            journal: null,
            foundBySources: [FaoStatRuntimePolicy::canonicalSourceKey()],
            rawMetadata: [
                'faostat' => [
                    'item' => 'Wheat',
                    'value' => '1',
                ],
            ],
        );
    }

    private function scholarlyResult(): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W1',
            title: 'Barley irrigation water requirement',
            authors: ['A Researcher'],
            publicationYear: 2021,
            doi: '10.1000/barley-water',
            canonicalUrl: 'https://example.test/p',
            abstract: 'Irrigation requirement',
            journal: 'Ag Journal',
            foundBySources: ['openalex'],
        );
    }

    private function plan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley production quantity Brazil 2019',
            normalizedQuestion: 'barley production quantity Brazil 2019',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: ['quantity'],
            constraints: [
                'question_type' => 'statistical',
                'year' => '2019',
            ],
            location: 'brazil',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'statistical_lookup',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'statistical_lookup',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'barley'],
            topics: ['production'],
            subtopics: [],
            requestedInformation: ['quantity'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat', 'openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
