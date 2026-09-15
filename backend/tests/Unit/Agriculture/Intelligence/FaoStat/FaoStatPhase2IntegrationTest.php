<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConsiderationPolicy;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatEvidenceType;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatObservationRelevanceGate;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSearchResultFilter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStatScientificSourceAdapter;
use App\Services\Agriculture\Intelligence\Contracts\ProviderCapability;
use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\Fusion\EvidenceFusionService;
use App\Services\Agriculture\Intelligence\Orchestration\CapabilityDrivenSourceSelector;
use App\Services\Agriculture\Intelligence\Orchestration\RequiredCapabilityResolver;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FaoStatPhase2IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.base_url' => 'https://faostatservices.fao.org/api/v1',
            'agricultural_intelligence.faostat.allowed_host' => 'faostatservices.fao.org',
            'agricultural_intelligence.faostat.username' => 'portal-user',
            'agricultural_intelligence.faostat.password' => 'portal-pass',
            'agricultural_intelligence.faostat.lang' => 'en',
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
            'agricultural_intelligence.fao.enabled' => false,
            'agricultural_intelligence.openalex.enabled' => true,
            'agricultural_intelligence.crossref.enabled' => true,
            'agricultural_intelligence.semantic_scholar.enabled' => true,
        ]);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->app->forgetInstance(AgriculturalProviderRegistry::class);
        $this->app->forgetInstance(CapabilityDrivenSourceSelector::class);
    }

    public function test_universal_consideration_without_agricultural_detector(): void
    {
        $this->assertSame(FaoStatConsiderationPolicy::CONSIDERED, FaoStatConsiderationPolicy::decision());
        $sources = app(ScientificSourceSelector::class)->selectSources($this->plan(
            question: 'what causes poor nitrogen uptake in wheat under salinity?',
            intent: 'scientific_research',
            topic: 'salinity',
            questionType: 'mechanism',
            sense: 'salinity_physiology',
        ));
        $this->assertContains('fao_stat', $sources);
        $this->assertContains('openalex', $sources);
        $this->assertFalse(FaoStatConsiderationPolicy::decision() === 'agricultural_question');
    }

    public function test_scholarly_providers_remain_eligible_with_faostat(): void
    {
        $plan = $this->plan('irrigation efficiency research', 'scientific_research', 'irrigation');
        $sources = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertContains('fao_stat', $sources);
        $this->assertContains('openalex', $sources);
        $this->assertContains('crossref', $sources);
        $this->assertContains('semantic_scholar', $sources);

        $caps = app(RequiredCapabilityResolver::class)->resolve($plan);
        $this->assertContains(ProviderCapability::SCIENTIFIC_SEARCH, $caps);
        $this->assertNotContains(ProviderCapability::OFFICIAL_AGRICULTURAL_DATA, $caps);

        $trace = app(CapabilityDrivenSourceSelector::class)->selectWithTrace($plan, new ProviderQueryInput(
            query: 'irrigation efficiency research',
            requiredCapabilities: $caps,
        ));
        $this->assertContains('fao_stat', $trace->selectedIds());
        $this->assertContains('openalex', $trace->selectedIds());
    }

    public function test_statistical_question_can_become_direct_statistical_evidence(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]));

        $plan = $this->statisticalPlan();
        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        $this->assertContains('fao_stat', $report->selectedSources);
        $this->assertSame(FaoStatConsiderationPolicy::CONSIDERED, $report->planSummary['faostat_consideration']['decision'] ?? null);
        $this->assertNotEmpty($report->deduplicatedResults);
        $row = $report->deduplicatedResults[0];
        $this->assertSame('fao_stat', $row->sourceKey);
        $this->assertSame(FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE, $row->relevanceMetadata['evidence_type'] ?? null);
        $this->assertTrue($row->relevanceMetadata['not_literature'] ?? false);
        $this->assertNull($row->doi);
        $this->assertSame([], $row->authors);
        $this->assertNull($row->journal);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $report);
        $this->assertNotEmpty($validation->validatedEvidence);
        $item = $validation->validatedEvidence[0];
        $this->assertSame(EvidenceValidationStatus::EVIDENCE_USABLE, $item->validationStatus);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $item->claimRelationship);
        $this->assertSame('direct_statistical', $item->qualityFactors['evidence_directness'] ?? null);
        $this->assertTrue($item->qualityFactors['not_literature'] ?? false);
        $this->assertNotSame('direct', $item->qualityFactors['evidence_directness'] ?? null);
    }

    public function test_mechanism_question_considers_faostat_but_rejects_unrelated_observation(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]));

        $plan = $this->plan(
            question: 'What causes poor nitrogen uptake in wheat under salinity?',
            intent: 'scientific_research',
            topic: 'salinity',
            questionType: 'mechanism',
            sense: 'salinity_physiology',
            constraints: [
                'question_type' => 'mechanism',
                'scientific_sense' => 'salinity_physiology',
                'area' => '106',
                'item' => '15',
                'element' => '2510',
                'year' => '2022',
                'domain' => 'QCL',
            ],
        );
        $this->assertContains('fao_stat', app(ScientificSourceSelector::class)->selectSources($plan));
        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat']);
        $this->assertSame(FaoStatConsiderationPolicy::CONSIDERED, $report->planSummary['faostat_consideration']['decision'] ?? null);
        $this->assertSame([], $report->deduplicatedResults);
    }

    public function test_relevance_gate_rejects_mismatched_item_area_year(): void
    {
        $gate = app(FaoStatObservationRelevanceGate::class);
        $plan = $this->statisticalPlan(['item' => '15', 'area' => '106', 'year' => '2022', 'element' => '2510']);
        $obs = $this->observationResult(array_merge($this->italyWheatNormalized(), [
            'item_code' => '515',
            'item' => 'Apples',
            'year' => '2010',
            'area_code' => '2',
        ]));
        $got = $gate->assess($plan, $obs);
        $this->assertFalse($got['relevant']);
        $this->assertSame(FaoStatObservationRelevanceGate::NOT_RELEVANT, $got['decision']);
    }

    public function test_empty_200_does_not_enter_ranking(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [],
            ], 200),
        ]));
        $report = app(AgriculturalScientificSearchService::class)->search($this->statisticalPlan(), 5, ['fao_stat']);
        $this->assertSame([], $report->deduplicatedResults);
        $this->assertContains('fao_stat', $report->emptySources);
        $this->assertSame(
            FaoStatErrorCategory::EMPTY_RESULT,
            $report->planSummary['faostat_consideration']['evidence_decision'] ?? null,
        );
    }

    public function test_faostat_failure_does_not_fail_other_providers(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response(['error' => 'upstream'], 500),
            'api.openalex.org/works*' => Http::response(['results' => [[
                'id' => 'https://openalex.org/W1',
                'display_name' => 'Salinity and nitrogen uptake in wheat',
                'doi' => 'https://doi.org/10.1000/test',
                'publication_year' => 2020,
                'authorships' => [['author' => ['display_name' => 'Ada Lovelace']]],
                'primary_location' => ['source' => ['display_name' => 'Plant Journal']],
                'abstract_inverted_index' => ['Salinity' => [0], 'nitrogen' => [1]],
            ]]], 200),
        ]));

        $report = app(AgriculturalScientificSearchService::class)->search(
            $this->statisticalPlan(),
            5,
            ['fao_stat', 'openalex'],
        );
        $this->assertContains('fao_stat', $report->failedSources);
        $this->assertContains('openalex', $report->successfulSources);
        $this->assertNotEmpty($report->deduplicatedResults);
        foreach ($report->deduplicatedResults as $row) {
            $this->assertNotSame('fao_stat', $row->sourceKey);
        }
    }

    public function test_feature_flag_off_preserves_fenix_selector_behavior(): void
    {
        config(['agricultural_intelligence.faostat.enabled' => false, 'agricultural_intelligence.fao.enabled' => false]);
        $this->assertSame(FaoStatConsiderationPolicy::DISABLED, FaoStatConsiderationPolicy::decision());
        $sourcesOff = app(ScientificSourceSelector::class)->selectSources($this->plan('irrigation efficiency research', 'scientific_research', 'irrigation'));
        $this->assertNotContains('fao_stat', $sourcesOff);

        config(['agricultural_intelligence.fao.enabled' => true]);
        $sourcesFenix = app(ScientificSourceSelector::class)->selectSources($this->plan('irrigation efficiency research', 'scientific_research', 'irrigation'));
        $this->assertContains('fao_stat', $sourcesFenix);
        $this->app->forgetInstance(ScientificSourceAdapterRegistry::class);
        $this->assertInstanceOf(
            FaoStatScientificSourceAdapter::class,
            app(ScientificSourceAdapterRegistry::class)->get('fao_stat'),
        );
    }

    public function test_no_agricultural_question_gate_in_consideration_policy(): void
    {
        $source = file_get_contents((new \ReflectionClass(FaoStatConsiderationPolicy::class))->getFileName() ?: '') ?: '';
        $this->assertStringNotContainsString('agricultural_question', $source);
        $this->assertStringNotContainsString('is_agricultural', $source);
        $gate = file_get_contents((new \ReflectionClass(FaoStatObservationRelevanceGate::class))->getFileName() ?: '') ?: '';
        $this->assertStringNotContainsString('if agricultural', strtolower($gate));
        $this->assertTrue(FaoStatConsiderationPolicy::isEnabled());
        $this->assertSame(FaoStatConsiderationPolicy::CONSIDERED, FaoStatConsiderationPolicy::decision());
    }

    public function test_language_contract_portal_lang_independent_of_platform(): void
    {
        app()->setLocale('ar');
        config(['agricultural_intelligence.faostat.lang' => 'en']);
        $this->assertSame('en', FaoStatDeveloperPortalClient::lang());
        app()->setLocale('tr');
        $this->assertSame('en', FaoStatDeveloperPortalClient::lang());
        config(['agricultural_intelligence.faostat.lang' => 'ar']);
        $this->assertSame('en', FaoStatDeveloperPortalClient::lang());
        config(['agricultural_intelligence.faostat.lang' => 'fr']);
        $this->assertSame('fr', FaoStatDeveloperPortalClient::lang());
    }

    public function test_dual_element_codes_remain_separate_through_phase2_filter(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]));
        $report = app(AgriculturalScientificSearchService::class)->search($this->statisticalPlan(), 5, ['fao_stat']);
        $row = $report->deduplicatedResults[0];
        $this->assertSame('2510', $row->relevanceMetadata['query_element_code'] ?? null);
        $this->assertSame('5510', $row->relevanceMetadata['response_element_code'] ?? null);
        $this->assertNotSame(
            $row->relevanceMetadata['query_element_code'] ?? null,
            $row->relevanceMetadata['response_element_code'] ?? null,
        );
    }

    public function test_incomplete_filters_do_not_invent_codes(): void
    {
        $report = app(AgriculturalScientificSearchService::class)->search(
            $this->plan('wheat production in italy', 'agricultural_economics', 'production', 'statistical'),
            5,
            ['fao_stat'],
        );
        $this->assertSame([], $report->deduplicatedResults);
        $this->assertSame(
            FaoStatErrorCategory::INCOMPLETE_FILTERS,
            $report->planSummary['faostat_consideration']['error'] ?? null,
        );
        Http::assertNothingSent();
    }

    public function test_multi_source_fusion_keeps_stats_and_scholarly_separate(): void
    {
        $fusion = app(EvidenceFusionService::class)->fuse([
            new CanonicalAgriculturalResult(
                providerId: 'fao_stat',
                status: 'success',
                stats: [[
                    'provider_id' => 'fao_stat',
                    'source_role' => SourceRole::OFFICIAL_AGRICULTURAL_DATA,
                    'evidence_family' => 'official',
                    'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                    'title' => 'Wheat — Production — Italy — 2022',
                    'value' => '6609520',
                    'canonical_url' => 'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106&item=15&element=2510&year=2022',
                    'confidence' => 0.82,
                ]],
                confidence: 0.7,
            ),
            new CanonicalAgriculturalResult(
                providerId: 'openalex',
                status: 'success',
                scientificEvidence: [[
                    'provider_id' => 'openalex',
                    'source_role' => SourceRole::SCIENTIFIC_EVIDENCE,
                    'evidence_family' => 'scientific',
                    'title' => 'Salinity reduces nitrogen uptake in wheat',
                    'doi' => '10.1000/test',
                    'canonical_url' => 'https://doi.org/10.1000/test',
                    'confidence' => 0.8,
                ]],
                confidence: 0.8,
            ),
        ]);

        $families = array_map(static fn (array $row): string => (string) ($row['evidence_family'] ?? ''), $fusion->dedupedEvidence);
        $this->assertContains('official', $families);
        $this->assertContains('scientific', $families);
        $this->assertContains('fao_stat', $fusion->providersUsed);
        $this->assertContains('openalex', $fusion->providersUsed);
    }

    public function test_unrelated_observation_does_not_pollute_ranking_when_relevant_row_exists(): void
    {
        $plan = $this->statisticalPlan();
        $relevant = $this->observationResult($this->italyWheatNormalized());
        $unrelated = $this->observationResult(array_merge($this->italyWheatNormalized(), [
            'item_code' => '515',
            'item' => 'Apples',
            'area_code' => '2',
            'area' => 'Afghanistan',
            'year' => '2010',
        ]));
        $gate = app(FaoStatObservationRelevanceGate::class);
        $this->assertTrue($gate->assess($plan, $relevant)['relevant']);
        $this->assertFalse($gate->assess($plan, $unrelated)['relevant']);

        $filter = app(FaoStatSearchResultFilter::class);
        $report = $filter->apply($plan, new ScientificSearchExecutionReport(
            status: 'search_completed',
            searchQuery: 'wheat production',
            selectedSources: ['fao_stat'],
            attemptedSources: ['fao_stat'],
            successfulSources: ['fao_stat'],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [$relevant, $unrelated],
            deduplicatedResults: [$relevant, $unrelated],
            planSummary: [],
        ));
        $this->assertCount(1, $report->deduplicatedResults);
        $this->assertSame('Wheat', $report->deduplicatedResults[0]->rawMetadata['faostat']['item'] ?? null);
    }

    public function test_mocked_pipeline_considers_faostat_without_monopolizing_answer(): void
    {
        Http::fake($this->authAnd([
            'faostatservices.fao.org/api/v1/en/data/*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
            'api.openalex.org/works*' => Http::response(['results' => [[
                'id' => 'https://openalex.org/W2',
                'display_name' => 'Wheat physiology under salinity',
                'doi' => 'https://doi.org/10.1000/phys',
                'publication_year' => 2019,
                'authorships' => [['author' => ['display_name' => 'Researcher']]],
                'primary_location' => ['source' => ['display_name' => 'Agronomy Journal']],
            ]]], 200),
        ]));

        $plan = $this->statisticalPlan();
        $report = app(AgriculturalScientificSearchService::class)->search($plan, 5, ['fao_stat', 'openalex']);
        $this->assertSame(FaoStatConsiderationPolicy::CONSIDERED, $report->planSummary['faostat_consideration']['decision'] ?? null);
        $keys = array_map(static fn ($row) => $row->sourceKey, $report->deduplicatedResults);
        $this->assertContains('fao_stat', $keys);
        $this->assertContains('openalex', $keys);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $report);
        $fusion = app(EvidenceFusionService::class)->fuse([
            new CanonicalAgriculturalResult(
                providerId: 'scientific_pipeline',
                status: 'success',
                stats: array_values(array_filter(
                    array_map(static fn ($item) => $item->toArray(), $validation->validatedEvidence),
                    static fn (array $item): bool => ($item['source_key'] ?? '') === 'fao_stat',
                )),
                scientificEvidence: array_values(array_filter(
                    array_map(static fn ($item) => $item->toArray(), $validation->validatedEvidence),
                    static fn (array $item): bool => ($item['source_key'] ?? '') !== 'fao_stat',
                )),
            ),
        ]);
        $this->assertNotEmpty($fusion->results[0]->stats);
        $scholarly = $fusion->results[0]->scientificEvidence;
        $this->assertTrue($scholarly === [] || ($scholarly[0]['source_key'] ?? '') !== 'fao_stat');
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function statisticalPlan(array $constraints = []): KnowledgeQueryPlan
    {
        return $this->plan(
            question: 'Italy wheat production quantity 2022 harvested area yield statistics',
            intent: 'agricultural_economics',
            topic: 'production statistics',
            questionType: 'statistical',
            constraints: array_merge([
                'question_type' => 'statistical',
                'domain' => 'QCL',
                'area' => '106',
                'item' => '15',
                'element' => '2510',
                'year' => '2022',
            ], $constraints),
        );
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function plan(
        string $question,
        string $intent,
        string $topic,
        string $questionType = 'general',
        string $sense = '',
        array $constraints = [],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => $topic],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: array_merge([
                'question_type' => $questionType,
                'scientific_sense' => $sense,
            ], $constraints),
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'topic', 'value' => $topic],
            topics: [$topic],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    /** @return array<string, mixed> */
    private function italyWheatRow(): array
    {
        return [
            'Domain Code' => 'QCL',
            'Domain' => 'Crops and livestock products',
            'Area Code' => '106',
            'Area' => 'Italy',
            'Element Code' => '5510',
            'Element' => 'Production',
            'Item Code' => '15',
            'Item' => 'Wheat',
            'Year Code' => '2022',
            'Year' => '2022',
            'Unit' => 't',
            'Value' => '6609520',
            'Flag' => 'A',
            'Flag Description' => 'Official value',
            'Note' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function italyWheatNormalized(): array
    {
        return [
            'domain_code' => 'QCL',
            'domain' => 'Crops and livestock products',
            'area_code' => '106',
            'area' => 'Italy',
            'item_code' => '15',
            'item' => 'Wheat',
            'query_element_code' => '2510',
            'response_element_code' => '5510',
            'element_code' => '5510',
            'element' => 'Production',
            'year_code' => '2022',
            'year' => '2022',
            'unit' => 't',
            'value' => '6609520',
        ];
    }

    /** @param  array<string, mixed>  $observation */
    private function observationResult(array $observation): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: 'fao_stat',
            sourceIdentifier: 'QCL|'.$observation['area_code'].'|'.$observation['item_code'],
            title: ($observation['item'] ?? '').' — '.($observation['element'] ?? ''),
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: 'https://faostatservices.fao.org/api/v1/en/data/QCL',
            abstract: 'Value: '.($observation['value'] ?? ''),
            journal: null,
            foundBySources: ['fao_stat'],
            relevanceMetadata: [
                'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                'not_literature' => true,
                'query_element_code' => $observation['query_element_code'] ?? null,
                'response_element_code' => $observation['response_element_code'] ?? null,
            ],
            rawMetadata: ['faostat' => $observation],
        );
    }

    /**
     * @param  array<string, mixed>  $routes
     * @return array<string, mixed>
     */
    private function authAnd(array $routes): array
    {
        return array_merge([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response([
                'AuthenticationResult' => [
                    'AccessToken' => 'test-access-token',
                    'RefreshToken' => 'test-refresh-token',
                    'ExpiresIn' => 3600,
                    'TokenType' => 'Bearer',
                ],
            ], 200),
        ], $routes);
    }
}
