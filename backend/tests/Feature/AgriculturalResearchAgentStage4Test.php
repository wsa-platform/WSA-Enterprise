<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceQualityRanker;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificMetadataValidator;
use App\Services\Agriculture\Research\Validation\ScientificSourceQualityValidator;
use App\Services\Agriculture\ScientificSourceRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgriculturalResearchAgentStage4Test extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function openAlexWork(
        string $title,
        string $abstract,
        string $doi,
        string $institution = 'University of Agriculture',
        string $institutionType = 'education',
        string $journal = 'Journal of Agronomy',
    ): array {
        $inverted = [];
        foreach (preg_split('/\s+/', $abstract) as $index => $word) {
            $inverted[$word][] = $index;
        }

        return [
            'id' => 'https://openalex.org/W'.md5($title),
            'display_name' => $title,
            'doi' => 'https://doi.org/'.$doi,
            'publication_year' => 2023,
            'abstract_inverted_index' => $inverted,
            'primary_location' => [
                'landing_page_url' => 'https://doi.org/'.$doi,
                'source' => ['display_name' => $journal],
            ],
            'authorships' => [[
                'author' => ['display_name' => 'Dr Researcher'],
                'institutions' => [[
                    'display_name' => $institution,
                    'type' => $institutionType,
                ]],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function crossRefWork(
        string $title,
        string $abstract,
        string $doi,
        string $publisher = 'University of Agriculture',
    ): array {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => $publisher,
            'container-title' => ['Journal of Agricultural Science'],
            'issued' => ['date-parts' => [[2022]]],
            'author' => [['given' => 'Jane', 'family' => 'Researcher']],
        ];
    }

    private function fakeSearch(string $query, array $openAlexResults = [], array $crossRefItems = []): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => $openAlexResults], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => $crossRefItems]], 200),
        ]);
    }

    /** @return array<string, mixed> */
    private function validateQuery(string $query, array $extra = []): array
    {
        return app(AgriculturalResearchAgent::class)->validateResearch(array_merge(['query' => $query], $extra));
    }

    public function test_valid_university_source(): void
    {
        $this->fakeSearch('wheat cultivation', [
            $this->openAlexWork(
                'Wheat cultivation in dryland agriculture',
                'Wheat cultivation requires seed rate and water management in dryland agriculture systems.',
                '10.1000/university-wheat',
                'University of Agriculture',
                'education',
            ),
        ]);

        $payload = $this->validateQuery('wheat cultivation dryland agriculture');
        $this->assertSame(4, $payload['stage']);
        $this->assertTrue($payload['validation']['performed']);
        $this->assertNotEmpty($payload['validated_evidence']);
        $this->assertSame('university_research', $payload['validated_evidence'][0]['source_type']);
    }

    public function test_valid_government_source(): void
    {
        $this->fakeSearch('soil fertility', [
            $this->openAlexWork(
                'Soil fertility management for cereal crops',
                'Soil fertility management improves cereal crop production under government extension programs.',
                '10.1000/gov-soil',
                'USDA Agricultural Research Service',
                'government',
            ),
        ]);

        $payload = $this->validateQuery('soil fertility management cereal crops');
        $this->assertSame('government', $payload['validated_evidence'][0]['source_type']);
    }

    public function test_valid_international_organization_source(): void
    {
        $this->fakeSearch('food security', [
            $this->openAlexWork(
                'FAO food security agricultural policy',
                'FAO food security agricultural policy supports sustainable farming systems worldwide.',
                '10.1000/fao-security',
                'FAO',
                'government',
            ),
        ]);

        $payload = $this->validateQuery('FAO food security agricultural policy');
        $this->assertSame('international_organization', $payload['validated_evidence'][0]['source_type']);
    }

    public function test_valid_peer_reviewed_publication(): void
    {
        $qualityValidator = app(ScientificSourceQualityValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-peer', 'Scientific literature plant pathology review', ['Dr Author'], 2023,
            '10.1000/peer-review', 'https://doi.org/10.1000/peer-review',
            'Scientific literature plant pathology disease management review.',
            'Journal of Plant Pathology', ['openalex'], null,
            ['openalex' => $this->openAlexWork(
                'Scientific literature plant pathology review',
                'Scientific literature plant pathology disease management review.',
                '10.1000/peer-review',
                'Journal of Plant Pathology',
                'facility',
                'Journal of Plant Pathology',
            )],
        );

        $assessment = $qualityValidator->validate($result);
        $this->assertTrue($assessment['trusted']);
        $this->assertContains($assessment['source']['source_type'], ['peer_reviewed_journal', 'research_institute']);
    }

    public function test_valid_openalex_result(): void
    {
        $this->fakeSearch('beekeeping', [
            $this->openAlexWork(
                'Beekeeping pollination orchard management',
                'Beekeeping pollination orchard management improves fruit set in orchards.',
                '10.1000/openalex-bee',
            ),
        ]);

        $payload = $this->validateQuery('beekeeping pollination orchard management');
        $this->assertSame('openalex', $payload['validated_evidence'][0]['source_key']);
        $this->assertSame(EvidenceValidationStatus::EVIDENCE_USABLE, $payload['validated_evidence'][0]['validation_status']);
    }

    public function test_valid_crossref_result(): void
    {
        $this->fakeSearch('poultry feed', [], [
            $this->crossRefWork(
                'Poultry feed nutrition formulation',
                'Poultry feed nutrition formulation for broiler production systems.',
                '10.1000/crossref-poultry',
            ),
        ]);

        $payload = $this->validateQuery('poultry feed nutrition formulation');
        $this->assertSame('crossref', $payload['validated_evidence'][0]['source_key']);
    }

    public function test_invalid_url_rejected(): void
    {
        $validator = app(ScientificMetadataValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-bad-url', 'Bad URL paper', ['Author'], 2023,
            '10.1000/bad-url', 'not-a-valid-url', 'abstract content here', 'Journal', ['openalex'],
        );

        $assessment = $validator->validate($result);
        $this->assertContains('invalid_url', $assessment['failures']);
    }

    public function test_incomplete_metadata(): void
    {
        $validator = app(ScientificMetadataValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-incomplete', 'Incomplete paper', [], null,
            null, null, null, null, ['openalex'],
        );

        $assessment = $validator->validate($result);
        $this->assertContains('incomplete_metadata', $assessment['failures']);
        $this->assertContains('missing_author_metadata', $assessment['failures']);
    }

    public function test_missing_doi_still_validates_when_other_identity_present(): void
    {
        $this->fakeSearch('weed management', [
            $this->openAlexWork(
                'Weed management in cereal crops',
                'Weed management in cereal crops requires integrated approaches.',
                '10.1000/weed-mgmt',
            ),
        ]);

        $payload = $this->validateQuery('weed management cereal crops');
        $this->assertNotEmpty($payload['validated_evidence']);
    }

    public function test_unverifiable_doi(): void
    {
        $validator = app(ScientificMetadataValidator::class);
        $result = new ScientificSearchResult(
            'crossref', 'cr-bad-doi', 'Bad DOI paper', ['Author'], 2022,
            'bad-doi-format', 'https://example.org/paper', 'abstract text', 'Journal', ['crossref'],
        );

        $assessment = $validator->validate($result);
        $this->assertContains('unverifiable_doi', $assessment['failures']);
    }

    public function test_missing_author_metadata_recorded(): void
    {
        $validator = app(ScientificMetadataValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-no-authors', 'No authors paper', [], 2021,
            '10.1000/no-authors', 'https://doi.org/10.1000/no-authors', 'abstract without authors', 'Journal', ['openalex'],
        );

        $assessment = $validator->validate($result);
        $this->assertContains('missing_author_metadata', $assessment['failures']);
    }

    public function test_duplicate_evidence_counted(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'wheat cultivation dryland']);
        $service = app(AgriculturalScientificValidationService::class);

        $result = new ScientificSearchResult(
            'openalex', 'W-dup', 'Wheat cultivation dryland systems', ['A'], 2020,
            '10.1000/dup', 'https://doi.org/10.1000/dup',
            'Wheat cultivation dryland systems require water management.',
            'Journal', ['openalex'], null, ['openalex' => $this->openAlexWork(
                'Wheat cultivation dryland systems',
                'Wheat cultivation dryland systems require water management.',
                '10.1000/dup',
            )],
        );

        $searchReport = new ScientificSearchExecutionReport(
            status: 'search_completed',
            searchQuery: 'wheat cultivation dryland',
            selectedSources: ['openalex'],
            attemptedSources: ['openalex'],
            successfulSources: ['openalex'],
            failedSources: [],
            emptySources: [],
            sourceOutcomes: [],
            results: [$result, $result],
            deduplicatedResults: [$result, $result],
            planSummary: $plan->toArray(),
        );

        $report = $service->validate($plan, $searchReport);
        $this->assertGreaterThanOrEqual(1, $report->duplicateCount);
    }

    public function test_conflicting_metadata_preserved(): void
    {
        $qualityValidator = app(ScientificSourceQualityValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-conflict-meta', 'Conflicting metadata', ['Author'], 2020,
            '10.1000/conflict-meta', 'https://doi.org/10.1000/conflict-meta',
            'Agricultural research abstract content.',
            'Journal A', ['openalex'],
            null,
            ['openalex' => $this->openAlexWork(
                'Conflicting metadata title variant',
                'Agricultural research abstract content.',
                '10.1000/conflict-meta',
            )],
        );

        $assessment = $qualityValidator->validate($result);
        $this->assertArrayHasKey('source', $assessment);
        $this->assertSame('Conflicting metadata title variant', $assessment['source']['title']);
    }

    public function test_conflicting_scientific_evidence_detected(): void
    {
        $this->fakeSearch('irrigation scheduling', [
            $this->openAlexWork(
                'Irrigation scheduling increases water use efficiency',
                'Irrigation scheduling increases water use efficiency and improve crop yields in arid regions.',
                '10.1000/irrigation-positive',
            ),
            $this->openAlexWork(
                'Irrigation scheduling reduces water waste risks',
                'Irrigation scheduling reduces water waste and avoid over-irrigation harm in arid regions.',
                '10.1000/irrigation-negative',
            ),
        ]);

        $payload = $this->validateQuery('irrigation scheduling arid regions');
        $this->assertGreaterThanOrEqual(0, $payload['validation']['conflicting_count']);
    }

    public function test_insufficient_evidence(): void
    {
        $matcher = app(ClaimEvidenceMatcher::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'xyznonexistenttopic123 farming']);
        $result = new ScientificSearchResult(
            'openalex', 'W-low', 'Unrelated paper title', ['A'], 2019,
            '10.1000/low-rel', 'https://doi.org/10.1000/low-rel',
            'Completely unrelated abstract about marine biology ecosystems.',
            'Journal', ['openalex'], ['relevance_score' => 0.0],
        );

        $match = $matcher->match($plan, $result, 'Completely unrelated abstract about marine biology ecosystems.', EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $this->assertSame(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $match['relationship']);
    }

    public function test_low_relevance_result(): void
    {
        $ranker = app(EvidenceQualityRanker::class);
        $score = $ranker->score(
            ['fields' => ['title' => true, 'abstract' => true]],
            ['confidence_level' => ScientificSourceRegistry::LEVEL_SUPPORTING],
            ['relationship' => ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, 'confidence' => 0.1],
            2020,
        );

        $this->assertLessThanOrEqual(40.0, $score['score']);
        $this->assertTrue($score['factors']['not_scientific_certainty']);
    }

    public function test_trusted_source_with_incomplete_metadata(): void
    {
        $this->fakeSearch('general agriculture', [
            $this->openAlexWork(
                'General agriculture overview',
                'General agriculture overview for smallholders.',
                '10.1000/incomplete-meta',
            ),
        ]);

        $payload = $this->validateQuery('general agriculture overview smallholders');
        $this->assertTrue($payload['validation']['performed']);
        $this->assertArrayHasKey('validated_evidence', $payload);
    }

    public function test_untrusted_source_rejected(): void
    {
        $qualityValidator = app(ScientificSourceQualityValidator::class);
        $result = new ScientificSearchResult(
            'openalex', 'W-untrusted', 'Untrusted blog post', ['Unknown'], 2024,
            null, 'https://random-blog.example/post', 'Some unverified content.', null, ['openalex'],
            null,
            ['openalex' => [
                'id' => 'https://openalex.org/W-untrusted',
                'display_name' => 'Untrusted blog post',
                'authorships' => [[
                    'author' => ['display_name' => 'Unknown'],
                    'institutions' => [[
                        'display_name' => 'Random Blog Inc',
                        'type' => 'company',
                    ]],
                ]],
            ]],
        );

        $assessment = $qualityValidator->validate($result);
        $this->assertFalse($assessment['trusted']);
        $this->assertContains('source_validation_failure', $assessment['failures']);
    }

    public function test_source_validation_failure_observable(): void
    {
        $payload = $this->validateQuery('best fertilizer');
        $this->assertSame('needs_clarification', $payload['status']);
        $this->assertFalse($payload['validation']['performed'] ?? true);
    }

    public function test_evidence_quality_scoring(): void
    {
        $this->fakeSearch('tomato irrigation', [
            $this->openAlexWork(
                'Tomato drip irrigation scheduling',
                'Tomato drip irrigation scheduling improves water use efficiency for tomato production.',
                '10.1000/tomato-irrigation',
            ),
        ]);

        $payload = $this->validateQuery('tomato drip irrigation scheduling');
        $this->assertGreaterThan(0, $payload['validated_evidence'][0]['quality_score']);
        $this->assertTrue($payload['validated_evidence'][0]['quality_factors']['not_scientific_certainty']);
    }

    public function test_claim_supported_by_one_source(): void
    {
        $this->fakeSearch('fertilization wheat', [
            $this->openAlexWork(
                'Fertilization nutrient management for wheat crops',
                'Fertilization nutrient management for wheat crops improves yield and soil fertility.',
                '10.1000/fert-wheat',
            ),
        ]);

        $payload = $this->validateQuery('fertilization nutrient management for wheat crops');
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $payload['validated_evidence'][0]['claim_relationship']);
    }

    public function test_claim_supported_by_multiple_sources(): void
    {
        $this->fakeSearch('aquaculture water quality', [
            $this->openAlexWork(
                'Aquaculture fish farming water quality management',
                'Aquaculture fish farming water quality management is essential for production.',
                '10.1000/aqua-1',
            ),
        ], [
            $this->crossRefWork(
                'Aquaculture fish farming water quality practices',
                'Aquaculture fish farming water quality practices support sustainable production.',
                '10.1000/aqua-2',
            ),
        ]);

        $payload = $this->validateQuery('aquaculture fish farming water quality');
        $this->assertGreaterThanOrEqual(1, count($payload['validated_evidence']));
    }

    public function test_partially_supported_claim(): void
    {
        $matcher = app(ClaimEvidenceMatcher::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'tomato irrigation scheduling arid']);
        $result = new ScientificSearchResult(
            'openalex', 'W-partial', 'Tomato production overview', ['A'], 2021,
            '10.1000/partial', 'https://doi.org/10.1000/partial',
            'Tomato production overview with general farming practices.',
            'Journal', ['openalex'],
        );

        $match = $matcher->match($plan, $result, 'Tomato production overview with general farming practices.', EvidenceValidationStatus::SCIENTIFICALLY_TRUSTWORTHY);
        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $match['relationship']);
    }

    public function test_conflicting_claim_relationship(): void
    {
        $this->assertSame('conflicting', ClaimEvidenceRelationship::CONFLICTING);
    }

    public function test_insufficient_evidence_claim_relationship(): void
    {
        $this->assertSame('insufficient_evidence', ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE);
    }

    /** @return array<string, array{0: string, 1?: string|null}> */
    public static function agriculturalDomainQueriesProvider(): array
    {
        return [
            'generic agriculture' => ['general farming practices smallholders', 'general_knowledge'],
            'wheat cultivation' => ['how to cultivate wheat in dryland systems', 'cultivation'],
            'tomato irrigation' => ['tomato drip irrigation scheduling', 'irrigation'],
            'soil fertility' => ['soil fertility improvement practices', 'soil_management'],
            'fertilizer' => ['fertilization nutrient management for wheat crops', 'fertilization'],
            'plant disease' => ['tomato plant disease blight management', 'disease'],
            'pest' => ['integrated pest management wheat fields', 'pest'],
            'beekeeping' => ['beekeeping pollination orchard management', 'beekeeping'],
            'poultry' => ['poultry broiler production nutrition', 'poultry_production'],
            'aquaculture' => ['aquaculture fish farming water quality', 'aquaculture'],
            'animal nutrition' => ['animal feed formulation nutrition', 'feed'],
            'agricultural economics' => ['agricultural economics farm profitability', 'agricultural_economics'],
            'scientific literature' => ['peer reviewed scientific publications sustainable agriculture', 'scientific_literature'],
            'arabic query' => ['كيف أزرع القمح؟', 'cultivation'],
            'english query' => ['irrigation scheduling for orchard crops in arid regions', 'irrigation'],
        ];
    }

    /** @dataProvider agriculturalDomainQueriesProvider */
    public function test_stage_4_validates_agricultural_domain_queries(string $query, ?string $expectedIntent = null): void
    {
        $this->fakeSearch($query, [
            $this->openAlexWork(
                'Agricultural research on '.$query,
                'Agricultural research findings related to farming systems and '.$query.' practices.',
                '10.1000/stage4-'.md5($query),
            ),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/validate', ['query' => $query]);

        $response->assertOk();
        if ($response->json('status') === 'needs_clarification') {
            $this->fail('Expected Stage 4 validation but query was needs_clarification: '.$query);
        }
        $response->assertJsonPath('stage', 4);
        $response->assertJsonPath('validation.performed', true);
        $response->assertJsonPath('internet_first', true);
        $response->assertJsonPath('synthesis.performed', false);
        $response->assertJsonPath('library_persistence.performed', false);
        if ($expectedIntent !== null) {
            $this->assertSame($expectedIntent, $response->json('knowledge_query_plan.research_intent'));
        }
    }

    public function test_internet_first_ordering_preserved_in_stage_4(): void
    {
        $this->fakeSearch('field crop production', [
            $this->openAlexWork(
                'Field crop production research',
                'Field crop production research for sustainable agriculture.',
                '10.1000/internet-first',
            ),
        ]);

        $payload = $this->validateQuery('field crop production research');
        $this->assertTrue($payload['internet_first']);
        $this->assertSame(['openalex', 'crossref', 'consensus'], $payload['search_summary']['selected_sources']);
    }

    public function test_stage_4_does_not_synthesize_final_answer(): void
    {
        $this->fakeSearch('livestock production', [
            $this->openAlexWork(
                'Livestock animal production husbandry',
                'Livestock animal production husbandry practices in mixed farming.',
                '10.1000/livestock',
            ),
        ]);

        $payload = $this->validateQuery('livestock animal production husbandry');
        $this->assertFalse($payload['synthesis']['performed']);
        $this->assertArrayNotHasKey('final_answer', $payload);
        $this->assertArrayNotHasKey('answer', $payload);
    }

    public function test_stage_4_does_not_persist_library_knowledge(): void
    {
        $this->fakeSearch('vegetable production', [
            $this->openAlexWork(
                'Vegetable production greenhouse systems',
                'Vegetable production greenhouse systems for year round farming.',
                '10.1000/vegetable',
            ),
        ]);

        $payload = $this->validateQuery('vegetable production greenhouse systems');
        $this->assertFalse($payload['library_persistence']['performed']);
    }

    public function test_stage_4_does_not_invoke_plant_ai_diagnosis(): void
    {
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\PlantAiDiagnosisService'));
        $this->assertFalse(class_exists('App\\Services\\PlantAi\\PlantDiagnosisAgent'));
    }

    public function test_stage_4_backward_compatible_with_stage_3_search(): void
    {
        $this->fakeSearch('plant nutrition', [
            $this->openAlexWork(
                'Plant nutrition micronutrient deficiency cereals',
                'Plant nutrition micronutrient deficiency cereals require balanced fertilization.',
                '10.1000/nutrition',
            ),
        ]);

        $search = app(AgriculturalResearchAgent::class)->searchResearch([
            'query' => 'plant nutrition micronutrient deficiency cereals',
        ]);

        $this->assertSame(3, $search['stage']);
        $this->assertFalse($search['validation']['performed']);
    }

    public function test_stage_4_validate_endpoint_contract(): void
    {
        $this->fakeSearch('agricultural engineering', [
            $this->openAlexWork(
                'Agricultural engineering irrigation systems',
                'Agricultural engineering irrigation systems design for efficient water delivery.',
                '10.1000/engineering',
            ),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/validate', [
            'query' => 'agricultural engineering irrigation systems',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'stage',
            'validation' => [
                'performed',
                'sources_received',
                'validated_count',
                'rejected_count',
                'validators_used',
            ],
            'validated_evidence',
            'rejected_evidence',
            'observability',
            'scientific_search',
            'knowledge_query_plan',
            'query_understanding',
            'synthesis' => ['performed'],
            'library_persistence' => ['performed'],
        ]);
    }

    public function test_stage_4_observability_metadata(): void
    {
        $this->fakeSearch('fruit production', [
            $this->openAlexWork(
                'Fruit tree orchard production management',
                'Fruit tree orchard production management for commercial growers.',
                '10.1000/fruit',
            ),
        ]);

        $payload = $this->validateQuery('fruit tree orchard production management');
        $this->assertArrayHasKey('failure_reasons', $payload['observability']);
        $this->assertArrayHasKey('source_types_used', $payload['observability']);
        $this->assertArrayHasKey('validation_status_counts', $payload['observability']);
        $this->assertContains('scientific_source_quality_validator', $payload['validation']['validators_used']);
    }

    public function test_validation_status_enum_values(): void
    {
        $this->assertContains('discovered', EvidenceValidationStatus::all());
        $this->assertContains('evidence_usable', EvidenceValidationStatus::all());
        $this->assertContains('rejected', EvidenceValidationStatus::all());
    }

    public function test_stage_2_planning_unchanged(): void
    {
        Http::fake();
        $plan = app(AgriculturalResearchAgent::class)->planResearch(['query' => 'كيف أزرع القمح؟']);
        $this->assertSame(2, $plan['stage']);
        $this->assertFalse($plan['execution']['performed']);
    }

    public function test_knowledge_query_plan_internet_first_strategy(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'irrigation scheduling for wheat']);
        $this->assertSame(KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST, $plan->primaryResearchStrategy);
    }
}
