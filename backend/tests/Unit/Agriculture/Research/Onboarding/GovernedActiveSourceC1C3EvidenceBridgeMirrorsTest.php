<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Onboarding\NextGateEligibleSeatCatalog;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\Family\Rest\RestJsonScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\ScientificEvidenceAnswerCitationBridge;
use Tests\TestCase;

/**
 * C1→C3 mirrors for ACTIVE governed seats other than GBIF (G5-04 covered separately).
 *
 * Each method: orchestrator retrieval → evidence bridge → AnswerComposer citations.
 * PARTIAL §8.1 only — never SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class GovernedActiveSourceC1C3EvidenceBridgeMirrorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['agricultural_intelligence.stage3_home_scholarly_concurrency' => false]);
        NextGateEligibleSeatCatalog::swap(null);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        NextGateEligibleSeatCatalog::swap(null);
        CurrentMembershipNearReadySeatCatalog::swap(null);
        AcceptedOwnerDecisionCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_agris_orchestrator_result_identity_traces_into_answer_citations(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-01');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        (new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        ))->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_agris_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-18T17:00:00+00:00'),
        );

        $registration = $this->localIndexRegistration(
            sourceKey: 'agris',
            adrId: 'G1-01',
            accessFamily: ScientificSourceAccessFamily::BULK_DATASET,
            familyProfile: $seat->familyProfile,
            capabilityNote: 'test:c1c3 agris bridge',
        );
        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(ScientificRecordStore::class, $store);

        // Keep scientificName null so LocalIndex variant preference does not replace the
        // CSQ query text that matches the AGRIS fixture title (same as Agris governed path).
        $csqQuery = 'Deficit irrigation effects on wheat yield';
        $originalQuestion = 'What are deficit irrigation effects on wheat yield?';
        $report = $this->executeForcedSource(
            registration: $registration,
            originalQuestion: $originalQuestion,
            csqQuery: $csqQuery,
            sourceKey: 'agris',
            scientificName: null,
            crop: null,
            topic: 'irrigation',
            subject: ['type' => 'topic', 'value' => 'irrigation'],
        );

        // Adapter prefers DOI as source_identifier when present on the fixture record.
        $result = $this->assertSourceHit($report->results, 'agris', '10.0000/agris.fixture.1');
        $this->assertAnswerTrace($result, 'irrigation', 'wheat deficit irrigation yield');
    }

    public function test_organic_eprints_orchestrator_result_identity_traces_into_answer_citations(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G1-02');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        Http::fake([
            'https://orgprints.org/cgi/oai2*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/organic_eprints_list_records_page2.xml')),
                200,
                ['Content-Type' => 'text/xml'],
            ),
        ]);

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        (new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        ))->harvestOaiPmh($seat->familyProfile, new DateTimeImmutable('2026-10-17T20:45:00+00:00'), maxPages: 1);

        // Match OrganicEprintsOaiLocalIndexPathTest: isolated LEGACY registration without adrId
        // (ADR seat binding requires GOVERNED_ONBOARDED + accepted decision).
        $registration = $this->localIndexRegistration(
            sourceKey: 'organic_eprints',
            adrId: null,
            accessFamily: ScientificSourceAccessFamily::OAI_PMH,
            familyProfile: $seat->familyProfile,
            capabilityNote: 'test:c1c3 organic_eprints bridge',
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
        );
        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(ScientificRecordStore::class, $store);

        $csqQuery = 'Organic Agriculture and the Environment';
        $report = $this->executeForcedSource(
            registration: $registration,
            originalQuestion: $csqQuery,
            csqQuery: $csqQuery,
            sourceKey: 'organic_eprints',
            scientificName: null,
            crop: null,
            topic: 'organic_agriculture',
            subject: ['type' => 'topic', 'value' => 'organic_agriculture'],
        );

        $result = $this->assertSourceHit($report->results, 'organic_eprints', 'oai:orgprints.org:573');
        $this->assertAnswerTrace($result, 'organic_agriculture', 'Organic Agriculture and the Environment');
    }

    public function test_dr_duke_orchestrator_result_identity_traces_into_answer_citations(): void
    {
        $seat = NextGateEligibleSeatCatalog::get('G1-17');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        (new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        ))->ingestSnapshot(
            $seat->familyProfile,
            base_path('tests/Fixtures/ScientificSourceFamilies/next_gate_dr_duke_fixture_v1.jsonl'),
            new DateTimeImmutable('2026-10-18T17:00:00+00:00'),
        );

        $registration = $this->localIndexRegistration(
            sourceKey: 'dr_duke',
            adrId: 'G1-17',
            accessFamily: ScientificSourceAccessFamily::STATIC_DATASET,
            familyProfile: $seat->familyProfile,
            capabilityNote: 'test:c1c3 dr_duke bridge',
            executionGroup: ScientificSourceExecutionGroup::SEQUENTIAL,
        );
        app()->instance(LocalScientificIndex::class, $index);
        app()->instance(ScientificRecordStore::class, $store);

        $catalog = new ScientificSourceCatalog([$registration]);
        $builder = Mockery::mock(ScientificSearchQueryBuilder::class);
        $builder->shouldReceive('buildVariantsFromPlan')->andReturn([
            'mint',
            'Mentha taxonomy',
            'mint taxonomy',
        ]);
        $builder->shouldReceive('buildFromPlan')->andReturn('mint');
        $builder->shouldReceive('buildConsensusRequestOptions')->andReturn([]);

        $originalQuestion = 'Mentha piperita ethnobotany';
        $plan = $this->plan(
            originalQuestion: $originalQuestion,
            csqSurface: $originalQuestion,
            sourceKey: 'dr_duke',
            scientificName: 'Mentha piperita',
            crop: 'mint',
            topic: 'taxonomy',
            subject: ['type' => 'crop', 'value' => 'mint'],
        );

        $orchestrator = new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            new ScientificSourceSelector($catalog),
            $builder,
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        );
        $report = $orchestrator->execute($plan, 5, ['dr_duke']);
        $this->assertContains('dr_duke', $report->successfulSources);

        $result = $this->assertSourceHit($report->results, 'dr_duke', 'drduke-fixture-1');
        $this->assertAnswerTrace($result, 'taxonomy', 'Mentha piperita ethnobotany');
    }

    public function test_bhl_orchestrator_result_identity_traces_into_answer_citations(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G6-20');
        $this->assertNotNull($seat);
        $this->assertTrue($seat->isRuntimeActive());

        config(['agricultural_intelligence.bhl.api_key' => 'test-fixture-key-not-a-secret']);
        Http::fake([
            'https://www.biodiversitylibrary.org/api3*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/bhl_publication_search_fixture_v1.json')),
                200,
            ),
        ]);

        $registration = new ScientificSourceRegistration(
            sourceKey: 'bhl',
            adapterClass: RestJsonScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'test:c1c3 bhl bridge',
                    verifiedAt: '2026-10-18T00:00:00+00:00',
                    expiresAt: '2027-10-18T00:00:00+00:00',
                ),
            ],
            familyProfile: $seat->familyProfile,
        );

        $csqQuery = 'Triticum rust botanical literature';
        $report = $this->executeForcedSource(
            registration: $registration,
            originalQuestion: $csqQuery,
            csqQuery: $csqQuery,
            sourceKey: 'bhl',
            scientificName: 'Triticum',
            crop: null,
            topic: 'plant_pathology',
            subject: ['type' => 'topic', 'value' => 'triticum'],
        );

        $result = $this->assertSourceHit($report->results, 'bhl', 'bhl-fixture-part-1');
        $this->assertAnswerTrace($result, 'plant_pathology', 'Triticum rust botanical literature');
    }

    /**
     * Regression: G5-04 GBIF bridge remains green via shared helper (no alternate path).
     */
    public function test_gbif_bridge_regression_via_shared_citation_helper(): void
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
        $this->assertTrue($catalog->has('gbif'));
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
        $report = $orchestrator->execute(
            $this->plan(
                originalQuestion: $csqQuery,
                csqSurface: $csqQuery,
                sourceKey: 'gbif',
                scientificName: 'Triticum aestivum',
                crop: 'wheat',
                topic: 'taxonomy',
                subject: ['type' => 'crop', 'value' => 'wheat'],
            ),
            5,
            ['gbif'],
        );

        $result = $this->assertSourceHit($report->results, 'gbif', '2704179');
        $this->assertAnswerTrace($result, 'taxonomy', 'Triticum aestivum species classification');
    }

    /**
     * @param  list<ScientificSearchResult>  $results
     */
    private function assertSourceHit(array $results, string $sourceKey, string $sourceIdentifier): ScientificSearchResult
    {
        $hits = array_values(array_filter(
            $results,
            static fn (ScientificSearchResult $r): bool => $r->sourceKey === $sourceKey,
        ));
        $this->assertNotEmpty($hits, $sourceKey.' must return at least one orchestrator hit');
        $result = $hits[0];
        $this->assertSame($sourceKey, $result->sourceKey);
        $this->assertSame($sourceIdentifier, (string) $result->sourceIdentifier);
        $this->assertNotNull($result->primaryProvenance());
        $this->assertSame($sourceKey, $result->primaryProvenance()?->sourceKey);

        return $result;
    }

    private function assertAnswerTrace(
        ScientificSearchResult $result,
        string $claimTopic,
        string $scientificIdentityPhrase,
    ): void {
        $composer = ScientificEvidenceAnswerCitationBridge::makeComposer();
        $evidence = ScientificEvidenceAnswerCitationBridge::evidenceFromSearchResult(
            $result,
            claimTopic: $claimTopic,
            scientificIdentityPhrase: $scientificIdentityPhrase,
        );
        $this->assertSame($result->sourceKey, $evidence->sourceKey);
        $this->assertSame((string) $result->sourceIdentifier, $evidence->sourceId);
        $this->assertSame($result->sourceKey, $evidence->sourceAttribution['provenance']['source_key'] ?? null);

        $citations = ScientificEvidenceAnswerCitationBridge::buildCitations($composer, [$evidence]);
        $this->assertCount(1, $citations, $result->sourceKey.' evidence must reach AnswerComposer citations');
        ScientificEvidenceAnswerCitationBridge::assertCitationPreservesSourceIdentity($citations[0], $result);
    }

    private function executeForcedSource(
        ScientificSourceRegistration $registration,
        string $originalQuestion,
        string $csqQuery,
        string $sourceKey,
        ?string $scientificName,
        ?string $crop,
        string $topic,
        array $subject,
    ): object {
        $catalog = new ScientificSourceCatalog([$registration]);
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

        $report = $orchestrator->execute(
            $this->plan(
                originalQuestion: $originalQuestion,
                csqSurface: $csqQuery,
                sourceKey: $sourceKey,
                scientificName: $scientificName,
                crop: $crop,
                topic: $topic,
                subject: $subject,
            ),
            5,
            [$sourceKey],
        );
        $this->assertContains(
            $sourceKey,
            $report->successfulSources,
            $sourceKey.' must succeed; attempted='.json_encode($report->attemptedSources)
                .' failed='.json_encode($report->failedSources)
                .' outcomes='.json_encode(array_map(
                    static fn ($o): array => [
                        'source' => $o->sourceKey ?? null,
                        'status' => $o->status ?? null,
                        'error' => $o->error ?? null,
                    ],
                    $report->sourceOutcomes ?? [],
                )),
        );

        return $report;
    }

    private function localIndexRegistration(
        string $sourceKey,
        ?string $adrId,
        ScientificSourceAccessFamily $accessFamily,
        mixed $familyProfile,
        string $capabilityNote,
        ScientificSourceLifecycle $lifecycle = ScientificSourceLifecycle::GOVERNED_ONBOARDED,
        ScientificSourceSelectionRole $selectionRole = ScientificSourceSelectionRole::GOVERNED_EXPANSION,
        ScientificSourceExecutionGroup $executionGroup = ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
    ): ScientificSourceRegistration {
        return new ScientificSourceRegistration(
            sourceKey: $sourceKey,
            adapterClass: LocalIndexScientificSourceAdapter::class,
            adapterContractVersion: ScientificSourceAdapterV2Interface::V2_CONTRACT_VERSION,
            lifecycle: $lifecycle,
            accessFamily: $accessFamily,
            selectionRole: $selectionRole,
            executionGroup: $executionGroup,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            adrId: $adrId !== null ? AdrMembershipId::fromString($adrId) : null,
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    $capabilityNote,
                    verifiedAt: '2026-10-18T17:00:00+00:00',
                    expiresAt: '2027-10-18T17:00:00+00:00',
                ),
            ],
            familyProfile: $familyProfile,
        );
    }

    /**
     * @param  array{type: string, value: string}  $subject
     */
    private function plan(
        string $originalQuestion,
        string $csqSurface,
        string $sourceKey,
        ?string $scientificName,
        ?string $crop,
        string $topic,
        array $subject,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $originalQuestion,
            normalizedQuestion: $csqSurface,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $subject,
            crop: $crop,
            cropId: $crop,
            scientificName: $scientificName,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['overview'],
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
            subjectEntity: $subject,
            topics: [$topic],
            subtopics: [],
            requestedInformation: ['overview'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: [$sourceKey],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }
}
