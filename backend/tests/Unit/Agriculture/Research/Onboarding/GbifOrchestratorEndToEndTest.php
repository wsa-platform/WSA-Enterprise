<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Onboarding\SeatActivationState;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * G5-04 GBIF: adapter → registry → orchestrator → provenance (fixture HTTP).
 * Distinct from live HTTP probes; preserves ACTIVE without re-declaring activation.
 */
class GbifOrchestratorEndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_gbif_remains_active_and_flows_through_production_catalog_orchestrator(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G5-04');
        $this->assertNotNull($seat);
        $this->assertSame(SeatActivationState::ACTIVE, $seat->intendedActivation);
        $this->assertTrue($seat->isRuntimeActive());

        $this->travelTo(Carbon::parse('2026-10-17T21:00:00+00:00'));
        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::response([
                'offset' => 0,
                'limit' => 2,
                'endOfRecords' => false,
                'count' => 2,
                'results' => [
                    [
                        'key' => 2704179,
                        'scientificName' => 'Triticum aestivum L.',
                        'rank' => 'SPECIES',
                        'publishedIn' => 'Sp. Pl. 1: 85 (1753)',
                    ],
                    [
                        'key' => 2704178,
                        'scientificName' => 'Triticum L.',
                        'rank' => 'GENUS',
                    ],
                ],
            ], 200),
        ]);

        $catalog = ScientificSourceCatalog::default();
        $this->assertTrue($catalog->has('gbif'));
        $this->assertSame('G5-04', $catalog->get('gbif')?->adrId?->value);

        $csqQuery = 'Triticum aestivum species classification';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        $report = $orchestrator->execute($this->plan($csqQuery), 5, ['gbif']);

        $this->assertSame(['gbif'], $report->attemptedSources);
        $this->assertSame(['gbif'], $report->successfulSources);
        $this->assertGreaterThanOrEqual(1, count($report->results));
        $first = $report->results[0];
        $this->assertSame('gbif', $first->sourceKey);
        $this->assertSame('2704179', (string) $first->sourceIdentifier);
        $this->assertSame('Triticum aestivum L.', $first->title);
        $prov = $first->primaryProvenance();
        $this->assertNotNull($prov);
        $this->assertSame(RestJsonScientificSourceAdapter::class, $prov->adapterClass);
        $this->assertSame(ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION, $prov->contractVersion);
        $this->assertSame('rest_api', $prov->accessFamily);
        $this->assertSame('2026-10-17T21:00:00+00:00', $prov->retrievedAt);

        Http::assertSent(static function (Request $request) use ($csqQuery): bool {
            if (! str_starts_with($request->url(), 'https://api.gbif.org/v1/species/search')) {
                return false;
            }
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['q'] ?? null) === $csqQuery;
        });
    }

    public function test_gbif_is_auto_selected_from_csq_plan_without_forced_source_keys(): void
    {
        $this->travelTo(Carbon::parse('2026-10-17T21:00:00+00:00'));
        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::response([
                'offset' => 0,
                'limit' => 1,
                'endOfRecords' => true,
                'count' => 1,
                'results' => [
                    [
                        'key' => 2704179,
                        'scientificName' => 'Triticum aestivum L.',
                        'rank' => 'SPECIES',
                        'publishedIn' => 'Sp. Pl. 1: 85 (1753)',
                    ],
                ],
            ], 200),
            '*' => Http::response(['results' => [], 'message' => ['items' => []]], 200),
        ]);

        config(['agricultural_intelligence.gbif.enabled' => true]);
        $catalog = ScientificSourceCatalog::default();
        $csqQuery = 'Triticum aestivum species classification';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $selector = new ScientificSourceSelector($catalog);
        $selected = $selector->selectSources($this->plan($csqQuery));
        $this->assertContains('gbif', $selected, 'GOVERNED_EXPANSION must include GBIF when enabled');

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            $selector,
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        // Null sourceKeys → selector-driven runtime path (home research agent).
        $report = $orchestrator->execute($this->plan($csqQuery), 3, null);
        $this->assertContains('gbif', $report->attemptedSources);
        $this->assertContains('gbif', $report->successfulSources);
        $gbifHits = array_values(array_filter(
            $report->results,
            static fn ($r): bool => $r->sourceKey === 'gbif',
        ));
        $this->assertNotEmpty($gbifHits);
        $this->assertSame('2704179', (string) $gbifHits[0]->sourceIdentifier);
        $this->assertSame('SPECIES', $gbifHits[0]->abstract);
        $this->assertSame(RestJsonScientificSourceAdapter::class, $gbifHits[0]->primaryProvenance()?->adapterClass);
    }

    /**
     * §8.1 C1→C3 bridge for G5-04 (PARTIAL): orchestrator gbif identity must survive into AnswerComposer citations.
     * Does not claim SCIENTIFIC_INTEGRATION_VERIFIED.
     */
    public function test_gbif_orchestrator_result_identity_traces_into_answer_citations(): void
    {
        $this->travelTo(Carbon::parse('2026-10-17T21:00:00+00:00'));
        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::response([
                'offset' => 0,
                'limit' => 1,
                'endOfRecords' => true,
                'count' => 1,
                'results' => [
                    [
                        'key' => 2704179,
                        'scientificName' => 'Triticum aestivum L.',
                        'rank' => 'SPECIES',
                        'publishedIn' => 'Sp. Pl. 1: 85 (1753)',
                    ],
                ],
            ], 200),
        ]);

        $catalog = ScientificSourceCatalog::default();
        $csqQuery = 'Triticum aestivum species classification';
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([$csqQuery]);
        $builder->shouldReceive('buildFromPlan')->andReturn($csqQuery);
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        $report = $orchestrator->execute($this->plan($csqQuery), 5, ['gbif']);
        $this->assertNotEmpty($report->results);
        $result = $report->results[0];
        $this->assertSame('gbif', $result->sourceKey);
        $this->assertSame('2704179', (string) $result->sourceIdentifier);

        $relevance = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $relevance->shouldReceive('isRelevant')->andReturn(true);
        $relevance->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);
        $composer = new AnswerComposer(
            app(ScientificSourceValidator::class),
            $relevance,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner,
            expressionAccuracyGate: new AnswerExpressionAccuracyGate,
        );

        $evidence = new ScientificEvidenceItem(
            evidenceId: 'ev-gbif-'.$result->sourceIdentifier,
            sourceId: (string) $result->sourceIdentifier,
            sourceKey: $result->sourceKey,
            sourceType: 'peer_reviewed_journal',
            publicationTitle: $result->title.' taxonomy evidence for Triticum aestivum classification',
            authors: $result->authors !== [] ? $result->authors : ['GBIF Backbone'],
            institution: 'University Research Institute',
            journal: 'Agronomy Journal',
            doi: $result->doi ?? '10.1000/gbif-'.$result->sourceIdentifier,
            url: $result->canonicalUrl ?? 'https://www.gbif.org/species/'.$result->sourceIdentifier,
            publicationYear: $result->publicationYear ?? 2023,
            retrievedAt: $result->primaryProvenance()?->retrievedAt ?? now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'taxonomy',
            evidenceText: $result->title.' reports species classification evidence for Triticum aestivum.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.9,
            qualityScore: 0.9,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            sourceAttribution: [
                'organization' => 'University Research Institute',
                'source_type' => 'peer_reviewed_journal',
                'found_by_sources' => [$result->sourceKey],
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'provenance' => [
                    'source_key' => $result->sourceKey,
                    'source_identifier' => $result->sourceIdentifier,
                    'adapter_class' => $result->primaryProvenance()?->adapterClass,
                ],
            ],
        );

        $method = new ReflectionMethod(AnswerComposer::class, 'buildCitations');
        $method->setAccessible(true);
        /** @var list<ResearchAnswerCitation> $citations */
        $citations = $method->invoke($composer, [$evidence], []);
        $this->assertCount(1, $citations);
        $this->assertSame('gbif', $citations[0]->sourceKey);
        $this->assertSame('2704179', $citations[0]->sourceId);
        $this->assertSame('ev-gbif-2704179', $citations[0]->evidenceId);
        $this->assertContains('gbif', $citations[0]->contributingProviders);
    }

    public function test_gbif_provider_errors_are_isolated_on_orchestrator_path(): void
    {

        Http::fake([
            'https://api.gbif.org/v1/species/search*' => Http::response('upstream unavailable', 503),
        ]);

        $catalog = ScientificSourceCatalog::default();
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn(['Triticum']);
        $builder->shouldReceive('buildFromPlan')->andReturn('Triticum');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );

        $report = $orchestrator->execute($this->plan('Triticum'), 5, ['gbif']);
        $this->assertSame(['gbif'], $report->attemptedSources);
        $this->assertSame([], $report->successfulSources);
        $this->assertContains('gbif', $report->failedSources);
        $this->assertNotEmpty($report->sourceOutcomes);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $report->sourceOutcomes[0]->status);
        $this->assertTrue(NextGateEligibleSeatCatalog::get('G5-04')?->isRuntimeActive());
    }

    private function plan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: 'taxonomy',
            subtopic: null,
            requestedInformation: ['classification'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'literature_review',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'literature_review',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'crop', 'value' => 'wheat'],
            topics: ['taxonomy'],
            subtopics: [],
            requestedInformation: ['classification'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['gbif'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
