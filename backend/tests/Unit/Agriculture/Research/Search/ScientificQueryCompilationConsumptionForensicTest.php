<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqEntity;
use App\Services\Agriculture\Research\CsqGeography;
use App\Services\Agriculture\Research\CsqProperty;
use App\Services\Agriculture\Research\CsqTime;
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
 * Forensic proof: scientific_query_compilation is attached to adapter options
 * but does not change the provider query string or increase call count.
 */
final class ScientificQueryCompilationConsumptionForensicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_compilation_is_metadata_only_same_query_same_call_count(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);

        $variants = ['builder-variant-alpha', 'builder-variant-beta'];
        $compiledSourceQuery = 'wheat yield Egypt 2022'; // what compiler would emit; must NOT replace builder query

        $calls = [];
        $openAlex = $this->capturingAdapter('openalex', $calls);
        $crossref = $this->capturingAdapter('crossref', $calls);
        $semantic = $this->capturingAdapter('semantic_scholar', $calls);

        $orchestrator = $this->orchestrator($openAlex, $crossref, $semantic, $variants);
        $orchestrator->execute($this->planWithCsq(), 5, ['openalex', 'crossref', 'semantic_scholar']);

        $bySource = [];
        foreach ($calls as $call) {
            $bySource[$call['source']][] = $call;
        }

        foreach (['openalex', 'crossref', 'semantic_scholar'] as $source) {
            $this->assertArrayHasKey($source, $bySource, "expected calls for {$source}");
            $this->assertCount(
                2,
                $bySource[$source],
                "{$source}: compilation must not add provider calls (MAX_VARIANTS_PER_PROVIDER=2)",
            );

            foreach ($bySource[$source] as $index => $call) {
                $this->assertSame(
                    $variants[$index],
                    $call['query'],
                    "{$source}: HTTP/query argument must remain builder variant, not compilation source_query",
                );
                $this->assertNotSame(
                    $compiledSourceQuery,
                    $call['query'],
                    "{$source}: compilation source_query must not replace the search query argument",
                );
                $this->assertArrayHasKey(
                    'scientific_query_compilation',
                    $call['options'],
                    "{$source}: compilation metadata must be attached when CSQ is present",
                );
                $compilation = $call['options']['scientific_query_compilation'];
                $this->assertIsArray($compilation);
                $this->assertSame($source, $compilation['source_key']);
                $this->assertTrue($compilation['representable']);
                $this->assertSame('wheat', $compilation['scientific_identity']['entity']['normalized']);
                $this->assertSame('yield', $compilation['scientific_identity']['property']['key']);
                $this->assertSame('Egypt', $compilation['scientific_identity']['geography']['country']);
                $this->assertSame(2022, $compilation['scientific_identity']['time']['year']);
            }
        }

        $this->assertCount(6, $calls, '3 providers × 2 variants; compilation must not double requests');
    }

    public function test_stage3_adapters_do_not_read_scientific_query_compilation(): void
    {
        $paths = [
            'app/Services/Agriculture/Research/Search/Adapters/OpenAlexScientificSourceAdapter.php',
            'app/Services/Agriculture/Research/Search/Adapters/SemanticScholarScientificSourceAdapter.php',
            'app/Services/Agriculture/Research/Search/Adapters/CrossRefScientificSourceAdapter.php',
            'app/Services/Agriculture/Intelligence/Adapters/Scientific/FaoStat/FaoStatDeveloperPortalAdapter.php',
        ];

        foreach ($paths as $relative) {
            $source = file_get_contents(base_path($relative));
            $this->assertIsString($source, $relative);
            $this->assertStringNotContainsString(
                'scientific_query_compilation',
                $source,
                "{$relative} must not consume scientific_query_compilation (forensic: METADATA ONLY)",
            );
        }
    }

    public function test_without_csq_compilation_key_is_absent_and_call_count_unchanged(): void
    {
        config([
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => false,
            'agricultural_intelligence.semantic_scholar.enabled' => false,
            'agricultural_intelligence.faostat.enabled' => false,
            'agricultural_intelligence.stage3_home_scholarly_concurrency' => false,
        ]);

        $variants = ['only-variant-a', 'only-variant-b'];
        $calls = [];
        $openAlex = $this->capturingAdapter('openalex', $calls);

        $this->orchestrator($openAlex, null, null, $variants)
            ->execute($this->planWithoutCsq(), 5, ['openalex']);

        $this->assertCount(2, $calls);
        foreach ($calls as $call) {
            $this->assertArrayNotHasKey('scientific_query_compilation', $call['options']);
            $this->assertContains($call['query'], $variants);
        }
    }

    /**
     * @param  list<array{source: string, query: string, options: array<string, mixed>}>  $calls
     */
    private function capturingAdapter(string $sourceKey, array &$calls): ScientificSourceAdapterInterface
    {
        return new class($sourceKey, $calls) implements ScientificSourceAdapterInterface
        {
            /** @param list<array{source: string, query: string, options: array<string, mixed>}> $calls */
            public function __construct(
                private string $key,
                private array &$calls,
            ) {}

            public function sourceKey(): string
            {
                return $this->key;
            }

            public function displayName(): string
            {
                return $this->key;
            }

            public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
            {
                $this->calls[] = [
                    'source' => $this->key,
                    'query' => $query,
                    'options' => $options,
                ];

                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->key,
                    status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
                    results: [
                        new ScientificSearchResult(
                            sourceKey: $this->key,
                            sourceIdentifier: $this->key.'-1',
                            title: 'Forensic hit',
                            authors: [],
                            publicationYear: 2022,
                            doi: '10.1000/forensic-'.$this->key,
                            canonicalUrl: 'https://example.test/'.$this->key,
                            abstract: 'forensic',
                            journal: null,
                            foundBySources: [$this->key],
                        ),
                    ],
                );
            }
        };
    }

    /**
     * @param  list<string>  $variants
     */
    private function orchestrator(
        ScientificSourceAdapterInterface $first,
        ?ScientificSourceAdapterInterface $second,
        ?ScientificSourceAdapterInterface $third,
        array $variants,
    ): MultiSourceScientificSearchOrchestrator {
        $adapters = [$first];
        if ($second !== null) {
            $adapters[] = $second;
        }
        if ($third !== null) {
            $adapters[] = $third;
        }

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

        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn($variants);
        $builder->shouldReceive('buildFromPlan')->andReturn($variants[0]);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        return new MultiSourceScientificSearchOrchestrator(
            $registry,
            app(ScientificSourceSelector::class),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
        );
    }

    private function planWithCsq(): KnowledgeQueryPlan
    {
        $csq = new CanonicalScientificQuestion(
            originalQuestion: 'What is wheat yield in Egypt in 2022?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'wheat',
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            property: new CsqProperty(key: 'yield', surface: 'yield'),
            geography: new CsqGeography(country: 'Egypt', label: 'Egypt'),
            time: new CsqTime(year: 2022),
        );

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $csq->originalQuestion,
            normalizedQuestion: $csq->originalQuestion,
            language: 'en',
            agriculturalDomain: 'crops',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'yield',
            subtopic: null,
            requestedInformation: ['yield'],
            constraints: [
                'question_type' => 'statistical',
                'year' => '2022',
            ],
            location: 'Egypt',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: $csq,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'crops',
            subjectEntity: ['type' => 'crop', 'value' => 'wheat'],
            topics: ['yield'],
            subtopics: [],
            requestedInformation: ['yield'],
            evidenceRequirements: [],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    private function planWithoutCsq(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'What is wheat yield in Egypt in 2022?',
            normalizedQuestion: 'What is wheat yield in Egypt in 2022?',
            language: 'en',
            agriculturalDomain: 'crops',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'yield',
            subtopic: null,
            requestedInformation: ['yield'],
            constraints: [],
            location: 'Egypt',
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: null,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'crops',
            subjectEntity: ['type' => 'crop', 'value' => 'wheat'],
            topics: ['yield'],
            subtopics: [],
            requestedInformation: ['yield'],
            evidenceRequirements: [],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
