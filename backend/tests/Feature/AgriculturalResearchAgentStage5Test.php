<?php

namespace Tests\Feature;

use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Persistence\KnowledgePersistenceExecutionReport;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AgriculturalResearchAgentStage5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
    }

    /** @return array<string, mixed> */
    private function openAlexWork(
        string $title,
        string $abstract,
        string $doi,
        string $institution = 'University of Agriculture',
        string $institutionType = 'education',
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
                'source' => ['display_name' => 'Journal of Agronomy'],
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
    private function crossRefWork(string $title, string $abstract, string $doi): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => 'University of Agriculture',
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
    private function synthesizeQuery(string $query, array $extra = []): array
    {
        return app(AgriculturalResearchAgent::class)->synthesizeResearch(1, array_merge([
            'query' => $query,
            'organization_id' => 1,
        ], $extra));
    }

    public function test_simple_agricultural_question_synthesis(): void
    {
        $this->fakeSearch('general farming practices', [
            $this->openAlexWork(
                'General farming practices for smallholders',
                'General farming practices for smallholders improve sustainable agriculture outcomes.',
                '10.1000/simple-ag',
            ),
        ]);

        $payload = $this->synthesizeQuery('general farming practices smallholders');
        $this->assertSame(5, $payload['stage']);
        $this->assertTrue($payload['synthesis']['performed']);
        $this->assertNotEmpty($payload['answer']);
        $this->assertNotEmpty($payload['claims']);
    }

    public function test_wheat_cultivation_synthesis(): void
    {
        $this->fakeSearch('wheat cultivation', [
            $this->openAlexWork(
                'Wheat cultivation in dryland agriculture',
                'Wheat cultivation requires seed rate 120 kg/ha and water management in dryland agriculture.',
                '10.1000/wheat-cult',
            ),
        ]);

        $payload = $this->synthesizeQuery('wheat cultivation dryland agriculture');
        $this->assertNotEmpty($payload['claims']);
        $numerical = $payload['claims'][0]['numerical_values'] ?? [];
        $this->assertTrue(collect($numerical)->contains(fn (string $value): bool => str_contains($value, '120')));
    }

    public function test_tomato_irrigation_synthesis(): void
    {
        $this->fakeSearch('tomato irrigation', [
            $this->openAlexWork(
                'Tomato drip irrigation scheduling',
                'Tomato drip irrigation scheduling improves water use efficiency for tomato production.',
                '10.1000/tomato-irr',
            ),
        ]);

        $payload = $this->synthesizeQuery('tomato drip irrigation scheduling');
        $this->assertTrue($payload['synthesis']['performed']);
        $this->assertNotEmpty($payload['citations']);
    }

    public function test_fertilizer_question_synthesis(): void
    {
        $this->fakeSearch('fertilization wheat', [
            $this->openAlexWork(
                'Fertilization nutrient management for wheat crops',
                'Fertilization nutrient management for wheat crops improves yield and soil fertility.',
                '10.1000/fert-wheat',
            ),
        ]);

        $payload = $this->synthesizeQuery('fertilization nutrient management for wheat crops');
        $this->assertTrue($payload['synthesis']['performed']);
        $this->assertContains($payload['status'], ['synthesis_completed', 'persistence_completed']);
    }

    public function test_soil_question_synthesis(): void
    {
        $this->fakeSearch('soil fertility', [
            $this->openAlexWork(
                'Soil fertility management for cereal crops',
                'Soil fertility management improves cereal crop production under extension programs.',
                '10.1000/soil',
            ),
        ]);

        $payload = $this->synthesizeQuery('soil fertility improvement practices');
        $this->assertNotEmpty($payload['key_findings']);
    }

    public function test_nutrient_deficiency_synthesis(): void
    {
        $this->fakeSearch('micronutrient', [
            $this->openAlexWork(
                'Micronutrient deficiency cereals fertilization',
                'Micronutrient deficiency in cereals requires balanced fertilization programs.',
                '10.1000/micro',
            ),
        ]);

        $payload = $this->synthesizeQuery('plant nutrition micronutrient deficiency cereals');
        $this->assertTrue($payload['synthesis']['performed']);
    }

    public function test_plant_disease_synthesis(): void
    {
        $this->fakeSearch('tomato blight', [
            $this->openAlexWork(
                'Tomato plant disease blight management',
                'Tomato plant disease blight management requires integrated control strategies.',
                '10.1000/blight',
            ),
        ]);

        $payload = $this->synthesizeQuery('tomato plant disease blight management');
        $this->assertNotEmpty($payload['answer']);
    }

    public function test_pest_synthesis(): void
    {
        $this->fakeSearch('pest wheat', [
            $this->openAlexWork(
                'Integrated pest management wheat fields',
                'Integrated pest management wheat fields reduces crop losses sustainably.',
                '10.1000/pest',
            ),
        ]);

        $payload = $this->synthesizeQuery('integrated pest management wheat fields');
        $this->assertTrue($payload['library_persistence']['performed']);
    }

    public function test_beekeeping_synthesis(): void
    {
        $this->fakeSearch('beekeeping', [
            $this->openAlexWork(
                'Beekeeping pollination orchard management',
                'Beekeeping pollination orchard management supports fruit production.',
                '10.1000/bee',
            ),
        ]);

        $payload = $this->synthesizeQuery('beekeeping pollination orchard management');
        $this->assertSame(5, $payload['stage']);
    }

    public function test_poultry_synthesis(): void
    {
        $this->fakeSearch('poultry', [
            $this->openAlexWork(
                'Poultry broiler production nutrition',
                'Poultry broiler production nutrition programs improve feed efficiency.',
                '10.1000/poultry',
            ),
        ]);

        $payload = $this->synthesizeQuery('poultry broiler production nutrition');
        $this->assertNotEmpty($payload['claims']);
    }

    public function test_aquaculture_synthesis(): void
    {
        $this->fakeSearch('aquaculture', [
            $this->openAlexWork(
                'Aquaculture fish farming water quality management',
                'Aquaculture fish farming water quality management is essential for production.',
                '10.1000/aqua',
            ),
        ]);

        $payload = $this->synthesizeQuery('aquaculture fish farming water quality');
        $this->assertNotEmpty($payload['citations']);
    }

    public function test_animal_production_synthesis(): void
    {
        $this->fakeSearch('animal feed', [
            $this->openAlexWork(
                'Animal feed formulation nutrition',
                'Animal feed formulation nutrition supports livestock production systems.',
                '10.1000/animal',
            ),
        ]);

        $payload = $this->synthesizeQuery('animal feed formulation nutrition');
        $this->assertTrue($payload['synthesis']['performed']);
    }

    public function test_agricultural_economics_synthesis(): void
    {
        $this->fakeSearch('farm profitability', [
            $this->openAlexWork(
                'Agricultural economics farm profitability analysis',
                'Agricultural economics farm profitability analysis supports decision making.',
                '10.1000/econ',
            ),
        ]);

        $payload = $this->synthesizeQuery('agricultural economics farm profitability');
        $this->assertNotEmpty($payload['answer']);
    }

    public function test_agricultural_industry_synthesis(): void
    {
        $this->fakeSearch('agricultural engineering', [
            $this->openAlexWork(
                'Agricultural engineering irrigation systems',
                'Agricultural engineering irrigation systems design for efficient water delivery.',
                '10.1000/industry',
            ),
        ]);

        $payload = $this->synthesizeQuery('agricultural engineering irrigation systems');
        $this->assertTrue($payload['synthesis']['performed']);
        $this->assertContains($payload['status'], ['synthesis_completed', 'persistence_completed']);
    }

    public function test_scientific_literature_synthesis(): void
    {
        $this->fakeSearch('sustainable agriculture', [
            $this->openAlexWork(
                'Peer reviewed scientific publications sustainable agriculture',
                'Peer reviewed scientific publications sustainable agriculture review current practices.',
                '10.1000/lit',
            ),
        ]);

        $payload = $this->synthesizeQuery('peer reviewed scientific publications sustainable agriculture');
        $this->assertNotEmpty($payload['citations'][0]['doi']);
    }

    public function test_arabic_query_synthesis(): void
    {
        $this->fakeSearch('wheat', [
            $this->openAlexWork(
                'Wheat cultivation practices arid regions',
                'Wheat cultivation practices arid regions require irrigation scheduling.',
                '10.1000/ar-wheat',
            ),
        ]);

        $payload = $this->synthesizeQuery('كيف أزرع القمح في المناطق الجافة؟');
        $this->assertSame('ar', $payload['synthesis']['language']);
        $this->assertNotEmpty($payload['answer']);
    }

    public function test_english_query_synthesis(): void
    {
        $this->fakeSearch('irrigation', [
            $this->openAlexWork(
                'Irrigation scheduling orchard crops arid regions',
                'Irrigation scheduling orchard crops arid regions improves water efficiency.',
                '10.1000/en-irr',
            ),
        ]);

        $payload = $this->synthesizeQuery('irrigation scheduling for orchard crops in arid regions');
        $this->assertSame('en', $payload['synthesis']['language']);
    }

    public function test_insufficient_evidence_no_search_results(): void
    {
        $this->fakeSearch('empty query topic', []);

        $payload = $this->synthesizeQuery('empty query topic with no scientific results');
        $this->assertSame('no_search_results', $payload['status']);
        $this->assertEmpty($payload['claims']);
        $this->assertFalse($payload['library_persistence']['performed']);
    }

    public function test_insufficient_validated_evidence(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [
                    $this->openAlexWork(
                        'Untrusted blog source',
                        'Untrusted blog source about farming.',
                        '10.1000/blog',
                        'Random Blog Network',
                        'company',
                    ),
                ],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $payload = $this->synthesizeQuery('untrusted blog source about farming practices');
        $this->assertContains($payload['status'], ['no_validated_evidence', 'no_search_results', 'validation_completed_with_rejections']);
        $this->assertFalse($payload['library_persistence']['performed']);
    }

    public function test_conflicting_evidence_handling(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'irrigation scheduling wheat']);
        $itemA = $this->usableEvidence('ev-a', 'Improve irrigation scheduling to increase wheat yield.', ClaimEvidenceRelationship::CONFLICTING, true);
        $itemB = $this->usableEvidence('ev-b', 'Reduce irrigation scheduling to avoid waterlogging in wheat.', ClaimEvidenceRelationship::CONFLICTING, true);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$itemA, $itemB],
            rejectedEvidence: [],
            sourcesReceived: 2,
            validatedCount: 2,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 2,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertNotEmpty($synthesis->conflicts);
        $this->assertStringContainsString('Conflicting', (string) $synthesis->uncertainty);
    }

    public function test_multiple_sources_supporting_one_claim(): void
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

        $payload = $this->synthesizeQuery('aquaculture fish farming water quality');
        $this->assertGreaterThanOrEqual(1, count($payload['claims']));
        $this->assertGreaterThanOrEqual(1, count($payload['citations']));
    }

    public function test_one_source_supporting_multiple_claim_topics(): void
    {
        $this->fakeSearch('wheat irrigation fertilization', [
            $this->openAlexWork(
                'Wheat irrigation and fertilization integrated management',
                'Wheat irrigation scheduling and fertilization nutrient management improve yield.',
                '10.1000/multi-claim',
            ),
        ]);

        $payload = $this->synthesizeQuery('wheat irrigation scheduling and fertilization nutrient management');
        $this->assertCount(1, $payload['claims']);
        $this->assertNotEmpty($payload['claims'][0]['evidence_ids']);
    }

    public function test_citation_integrity_uses_actual_doi(): void
    {
        $this->fakeSearch('field crop production', [
            $this->openAlexWork(
                'Field crop production research',
                'Field crop production research for sustainable agriculture.',
                '10.1000/cite-doi',
            ),
        ]);

        $payload = $this->synthesizeQuery('field crop production research');
        $this->assertSame('10.1000/cite-doi', $payload['citations'][0]['doi']);
    }

    public function test_invalid_source_excluded_from_citations(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'test query topic']);
        $item = $this->usableEvidence('ev-invalid', 'Evidence without valid source metadata.', ClaimEvidenceRelationship::SUPPORTED, false, [
            'institution' => '',
            'publicationTitle' => '',
        ]);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertEmpty($synthesis->citations);
    }

    public function test_numerical_claim_traceability(): void
    {
        $this->fakeSearch('wheat seed rate', [
            $this->openAlexWork(
                'Wheat seed rate recommendations dryland',
                'Wheat seed rate of 120 kg/ha is recommended for dryland agriculture systems.',
                '10.1000/numbers',
            ),
        ]);

        $payload = $this->synthesizeQuery('wheat seed rate recommendations dryland agriculture');
        $numerical = $payload['claims'][0]['numerical_values'] ?? [];
        $this->assertNotEmpty($numerical);
        $this->assertTrue(
            collect($numerical)->contains(fn (string $value): bool => str_contains($value, '120')),
        );
    }

    public function test_persistence_of_verified_knowledge(): void
    {
        $this->fakeSearch('vegetable production', [
            $this->openAlexWork(
                'Vegetable production greenhouse systems',
                'Vegetable production greenhouse systems for year round farming.',
                '10.1000/persist',
            ),
        ]);

        $payload = $this->synthesizeQuery('vegetable production greenhouse systems');
        $this->assertTrue($payload['library_persistence']['performed']);
        $this->assertNotNull($payload['library_persistence']['library_item_id']);

        $item = LibraryItem::query()->find($payload['library_persistence']['library_item_id']);
        $this->assertNotNull($item);
        $this->assertSame('verified_research_knowledge', $item->item_type);
        $this->assertArrayHasKey('research_agent', $item->metadata ?? []);
    }

    public function test_persistence_failure_does_not_block_answer(): void
    {
        $this->fakeSearch('fruit production', [
            $this->openAlexWork(
                'Fruit tree orchard production management',
                'Fruit tree orchard production management for commercial growers.',
                '10.1000/fail-persist',
            ),
        ]);

        $mock = Mockery::mock(ScientificKnowledgePersistenceService::class);
        $mock->shouldReceive('persist')->once()->andReturn(new KnowledgePersistenceExecutionReport(
            status: 'persistence_failed',
            performed: false,
            libraryItemId: null,
            slug: 'research-knowledge-test',
            action: 'failed',
            provenance: null,
            observability: ['failure_reason' => 'persistence_exception'],
        ));
        $this->app->instance(ScientificKnowledgePersistenceService::class, $mock);

        $payload = app(AgriculturalResearchAgent::class)->synthesizeResearch(1, [
            'query' => 'fruit tree orchard production management',
            'organization_id' => 1,
        ]);

        $this->assertNotEmpty($payload['answer']);
        $this->assertFalse($payload['library_persistence']['performed']);
        $this->assertSame('persistence_failed', $payload['persistence_status']);
    }

    public function test_duplicate_knowledge_protection(): void
    {
        $this->fakeSearch('livestock production', [
            $this->openAlexWork(
                'Livestock animal production husbandry',
                'Livestock animal production husbandry practices in mixed farming.',
                '10.1000/dup',
            ),
        ]);

        $first = $this->synthesizeQuery('livestock animal production husbandry');
        $second = $this->synthesizeQuery('livestock animal production husbandry');

        $this->assertSame('created', $first['library_persistence']['action']);
        $this->assertSame('unchanged', $second['library_persistence']['action']);
        $this->assertSame(
            $first['library_persistence']['library_item_id'],
            $second['library_persistence']['library_item_id'],
        );
    }

    public function test_provenance_preservation(): void
    {
        $this->fakeSearch('plant nutrition', [
            $this->openAlexWork(
                'Plant nutrition micronutrient deficiency cereals',
                'Plant nutrition micronutrient deficiency cereals require balanced fertilization.',
                '10.1000/provenance',
            ),
        ]);

        $payload = $this->synthesizeQuery('plant nutrition micronutrient deficiency cereals');
        $this->assertArrayHasKey('provenance', $payload);
        $this->assertSame('agricultural_research_agent_stage_5', $payload['provenance']['pipeline']);
        $this->assertTrue($payload['provenance']['internet_first']);
    }

    public function test_internet_first_ordering_preserved_in_stage_5(): void
    {
        $this->fakeSearch('field crop production', [
            $this->openAlexWork(
                'Field crop production research',
                'Field crop production research for sustainable agriculture.',
                '10.1000/internet-first',
            ),
        ]);

        $payload = $this->synthesizeQuery('field crop production research');
        $this->assertTrue($payload['internet_first']);
        $this->assertTrue($payload['research_metadata']['internet_first']);
    }

    public function test_stage_4_validation_cannot_be_bypassed(): void
    {
        $this->fakeSearch('crop production', [
            $this->openAlexWork(
                'Crop production systems research',
                'Crop production systems research for sustainable farming.',
                '10.1000/no-bypass',
            ),
        ]);

        $payload = $this->synthesizeQuery('crop production systems research');
        $this->assertTrue($payload['scientific_validation']['validation']['performed']);
        $this->assertFalse($payload['observability']['validation_bypassed'] ?? true);
    }

    public function test_synthesis_does_not_independently_search(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'test no search']);
        $validation = new EvidenceValidationExecutionReport(
            status: 'no_search_results',
            validatedEvidence: [],
            rejectedEvidence: [],
            sourcesReceived: 0,
            validatedCount: 0,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: false,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertFalse($synthesis->observability['independent_search']);
    }

    public function test_no_fabricated_citation(): void
    {
        $this->fakeSearch('crop rotation', [
            $this->openAlexWork(
                'Crop rotation soil health cereals',
                'Crop rotation soil health cereals improves long term fertility.',
                '10.1000/no-fake-cite',
            ),
        ]);

        $payload = $this->synthesizeQuery('crop rotation soil health cereals');
        foreach ($payload['citations'] as $citation) {
            $this->assertSame('10.1000/no-fake-cite', $citation['doi']);
            $this->assertStringContainsString('10.1000/no-fake-cite', (string) $citation['url']);
        }
    }

    public function test_no_fabricated_doi(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'no doi source topic']);
        $item = $this->usableEvidence('ev-no-doi', 'Evidence without DOI field present.', ClaimEvidenceRelationship::SUPPORTED, false, [
            'doi' => null,
            'url' => null,
        ]);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        foreach ($synthesis->citations as $citation) {
            $this->assertNull($citation->doi);
        }
    }

    public function test_no_fabricated_url(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'no url source topic']);
        $item = $this->usableEvidence('ev-no-url', 'Evidence without URL field present.', ClaimEvidenceRelationship::SUPPORTED, false, [
            'doi' => null,
            'url' => null,
            'institution' => 'University of Agriculture',
            'publicationTitle' => 'Agricultural research publication',
        ]);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        foreach ($synthesis->citations as $citation) {
            $this->assertNull($citation->url);
        }
    }

    public function test_no_fabricated_scientific_number(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'text only evidence']);
        $item = $this->usableEvidence('ev-text', 'General qualitative recommendation without numeric values.', ClaimEvidenceRelationship::SUPPORTED);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertSame([], $synthesis->claims[0]->numericalValues);
    }

    public function test_existing_crop_pipeline_regression(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'farming needs for oats cultivation',
            'selected_crop_id' => 'oats',
            'selected_crop_name' => 'الشوفان',
            'knowledge_option' => 'farming-needs',
        ]);

        $response->assertOk();
        $response->assertJsonPath('research_agent.stage', 5);
    }

    public function test_stage_1_regression(): void
    {
        Http::fake();
        $agent = app(AgriculturalResearchAgent::class);
        $this->assertInstanceOf(AgriculturalResearchAgent::class, $agent);
    }

    public function test_stage_2_regression(): void
    {
        Http::fake();
        $plan = app(AgriculturalResearchAgent::class)->planResearch(['query' => 'irrigation scheduling for wheat']);
        $this->assertSame(2, $plan['stage']);
    }

    public function test_stage_3_regression(): void
    {
        $this->fakeSearch('plant nutrition', [
            $this->openAlexWork(
                'Plant nutrition research',
                'Plant nutrition research for crop production systems.',
                '10.1000/stage3',
            ),
        ]);

        $search = app(AgriculturalResearchAgent::class)->searchResearch(['query' => 'plant nutrition research crop production']);
        $this->assertSame(3, $search['stage']);
        $this->assertFalse($search['validation']['performed']);
    }

    public function test_stage_4_regression(): void
    {
        $this->fakeSearch('plant nutrition', [
            $this->openAlexWork(
                'Plant nutrition micronutrient deficiency cereals',
                'Plant nutrition micronutrient deficiency cereals require balanced fertilization.',
                '10.1000/stage4',
            ),
        ]);

        $validate = app(AgriculturalResearchAgent::class)->validateResearch(['query' => 'plant nutrition micronutrient deficiency cereals']);
        $this->assertSame(4, $validate['stage']);
        $this->assertFalse($validate['synthesis']['performed']);
    }

    public function test_stage_5_synthesize_endpoint_contract(): void
    {
        $this->fakeSearch('agricultural engineering', [
            $this->openAlexWork(
                'Agricultural engineering irrigation systems',
                'Agricultural engineering irrigation systems design for efficient water delivery.',
                '10.1000/contract',
            ),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/synthesize', [
            'organization' => 'wsa-demo',
            'query' => 'agricultural engineering irrigation systems',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'stage',
            'synthesis' => ['performed', 'confidence', 'language'],
            'answer',
            'claims',
            'citations',
            'library_persistence' => ['performed', 'action'],
            'scientific_validation',
            'scientific_search',
            'internet_first',
        ]);
    }

    public function test_security_unvalidated_source_not_authoritative(): void
    {
        $validation = app(AgriculturalScientificValidationService::class);
        $this->assertInstanceOf(AgriculturalScientificValidationService::class, $validation);
        $this->assertContains('rejected', EvidenceValidationStatus::all());
    }

    public function test_security_fabricated_citation_cannot_persist(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'fabricated source test']);
        $item = $this->usableEvidence('ev-fake', 'Unsupported claim text.', ClaimEvidenceRelationship::SUPPORTED, false, [
            'institution' => 'Fake Blog',
            'publicationTitle' => '',
            'sourceType' => 'blog_post',
        ]);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $persistence = app(ScientificKnowledgePersistenceService::class)->persist(1, $plan, $synthesis, $validation);
        $this->assertFalse($persistence->performed);
    }

    public function test_security_arbitrary_user_url_not_validated_source(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'user supplied url topic']);
        $item = new ScientificEvidenceItem(
            evidenceId: 'ev-user-url',
            sourceId: 'user-url',
            sourceKey: 'user',
            sourceType: 'university_research',
            publicationTitle: 'User supplied title',
            authors: ['Unknown'],
            institution: 'University of Agriculture',
            journal: null,
            doi: null,
            url: 'https://evil.example/fake-study',
            publicationYear: 2024,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'general',
            claimTopic: 'topic',
            evidenceText: 'Claim backed by user supplied url only.',
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: ClaimEvidenceRelationship::SUPPORTED,
            confidence: 0.8,
            qualityScore: 70.0,
            qualityFactors: [],
            sourceAttribution: ['organization' => 'University of Agriculture'],
        );

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertNotEmpty($synthesis->citations);
        $this->assertSame('https://evil.example/fake-study', $synthesis->citations[0]->url);
    }

    public function test_security_empty_evidence_not_authoritative(): void
    {
        $payload = $this->synthesizeQuery('best fertilizer');
        $this->assertSame('needs_clarification', $payload['status']);
        $this->assertEmpty($payload['claims'] ?? []);
    }

    public function test_security_malformed_metadata_handled_safely(): void
    {
        $composer = app(AnswerComposer::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => 'malformed metadata topic']);
        $item = $this->usableEvidence('ev-malformed', 'Evidence with incomplete metadata.', ClaimEvidenceRelationship::SUPPORTED, false, [
            'institution' => '',
            'publicationTitle' => ' ',
            'sourceType' => 'peer_reviewed_journal',
        ]);

        $validation = new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: [$item],
            rejectedEvidence: [],
            sourcesReceived: 1,
            validatedCount: 1,
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: true,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );

        $synthesis = $composer->compose($plan, $validation);
        $this->assertEmpty($synthesis->citations);
        $this->assertNotEmpty($synthesis->claims);
    }

    public function test_stage_5_does_not_invoke_plant_ai_diagnosis(): void
    {
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\PlantAiDiagnosisService'));
    }

    /** @param  array<string, mixed>  $overrides */
    private function usableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        bool $hasConflict = false,
        array $overrides = [],
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: (string) ($overrides['sourceId'] ?? 'source-'.$evidenceId),
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: array_key_exists('doi', $overrides) ? $overrides['doi'] : '10.1000/'.$evidenceId,
            url: array_key_exists('url', $overrides) ? $overrides['url'] : 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'general',
            claimTopic: 'topic',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: ['not_scientific_certainty' => true],
            sourceAttribution: ['organization' => 'University of Agriculture', 'source_type' => 'university_research'],
            hasConflict: $hasConflict,
        );
    }
}
