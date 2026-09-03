<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AgriculturalResearchAgentController;
use App\Http\Controllers\Api\PlantAiDiagnosisController;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use App\Services\Media\MediaReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[Group('stage9')]
class WsaEnterpriseStage9IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        Http::preventStrayRequests();
    }

    /** @return array<string, mixed> */
    private function openAlexWork(string $title, string $abstract, ?string $doi): array
    {
        $inverted = [];
        foreach (preg_split('/\s+/', $abstract) ?: [] as $index => $word) {
            $inverted[$word][] = $index;
        }

        $work = [
            'id' => 'https://openalex.org/W'.md5($title),
            'display_name' => $title,
            'publication_year' => 2023,
            'abstract_inverted_index' => $inverted,
            'primary_location' => [
                'landing_page_url' => $doi !== null ? 'https://doi.org/'.$doi : 'https://openalex.org/works/example',
                'source' => ['display_name' => 'Journal of Agronomy'],
            ],
            'authorships' => [[
                'author' => ['display_name' => 'Dr Researcher'],
                'institutions' => [[
                    'display_name' => 'University of Agriculture',
                    'type' => 'education',
                ]],
            ]],
        ];

        if ($doi !== null) {
            $work['doi'] = 'https://doi.org/'.$doi;
        }

        return $work;
    }

    private function fakeScholarly(array $openAlexResults = [], array $crossRefItems = []): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => $openAlexResults], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => $crossRefItems]], 200),
        ]);
    }

    public function test_a_core_boot_di_and_public_routes(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertInstanceOf(AgriculturalResearchAgent::class, app(AgriculturalResearchAgent::class));
        $this->assertInstanceOf(PlantAiDiagnosisEngine::class, app(PlantAiDiagnosisEngine::class));
        $this->assertInstanceOf(ScientificSourceDiscoveryPipeline::class, app(ScientificSourceDiscoveryPipeline::class));
        $this->assertInstanceOf(OpenAlexScientificSourceAdapter::class, app(OpenAlexScientificSourceAdapter::class));
        $this->assertInstanceOf(CrossRefScientificSourceAdapter::class, app(CrossRefScientificSourceAdapter::class));
        $this->assertInstanceOf(MediaReferenceService::class, app(MediaReferenceService::class));

        $uris = collect(Route::getRoutes())->map(fn ($route): string => $route->uri())->all();
        foreach ([
            'api/v1/public/research-agent/query',
            'api/v1/public/research-agent/plan',
            'api/v1/public/research-agent/search',
            'api/v1/public/research-agent/validate',
            'api/v1/public/research-agent/synthesize',
            'api/v1/public/plant-diagnosis/analyze',
            'api/v1/public/plant-diagnosis/knowledge',
            'api/v1/public/field-crops/taxonomy',
            'api/v1/public/library/crop-files',
        ] as $uri) {
            $this->assertContains($uri, $uris);
        }
    }

    public function test_a_validation_errors_are_structured_json(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', []);
        $response->assertUnprocessable();
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertArrayHasKey('query', $response->json('errors'));
    }

    public function test_b_full_pipeline_is_internet_first_and_library_is_memory(): void
    {
        $order = app(ScientificSourceDiscoveryPipeline::class)->discovererOrder();
        $this->assertSame('external_openalex', $order[0]);
        $this->assertSame('external_crossref', $order[1]);
        $this->assertContains('library_structured', $order);
        $this->assertTrue(array_search('library_structured', $order, true) > array_search('external_crossref', $order, true));

        $this->fakeScholarly([
            $this->openAlexWork(
                'Wheat drip irrigation scheduling in arid systems',
                'Wheat drip irrigation scheduling improves water use efficiency in arid agriculture.',
                '10.1000/stage9-wheat-irr',
            ),
        ]);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $this->assertSame(KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST, $plan->primaryResearchStrategy);
        $this->assertNotSame('LIBRARY_FIRST', $plan->primaryResearchStrategy);

        $response = $this->postJson('/api/v1/public/research-agent/synthesize', [
            'organization' => 'wsa-demo',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);

        $response->assertOk();
        $response->assertJsonPath('internet_first', true);
        $response->assertJsonPath('knowledge_query_plan.primary_research_strategy', KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST);
        $this->assertContains('openalex', $response->json('scientific_search.attempted_sources') ?? []);
        $dois = $this->decodedDois($response->json());
        $this->assertContains('10.1000/stage9-wheat-irr', $dois);
        $this->assertNotContains('10.9999/fabricated', $dois);
    }

    public function test_b_domain_queries_remain_plan_ready_without_library_first(): void
    {
        Http::fake();
        $planner = app(ResearchPlanner::class);

        foreach ([
            'how to cultivate wheat in dryland systems',
            'tomato drip irrigation scheduling',
            'soil fertility improvement practices',
            'beekeeping pollination orchard management',
        ] as $query) {
            $plan = $planner->planKnowledgeQuery(['query' => $query]);
            $this->assertSame(KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST, $plan->primaryResearchStrategy, $query);
            $this->assertTrue($plan->readyForStage3, $query);
        }
    }

    public function test_c_openalex_and_crossref_do_not_fabricate_doi_or_url(): void
    {
        $normalizer = app(ScientificResultNormalizer::class);

        $withoutDoi = $normalizer->fromOpenAlexWork($this->openAlexWork(
            'Title only OpenAlex work',
            'Abstract about wheat irrigation research.',
            null,
        ));
        $this->assertNotNull($withoutDoi);
        $this->assertNull($withoutDoi->doi);
        $this->assertNotSame('https://doi.org/10.1000/invented', $withoutDoi->canonicalUrl);

        $malformed = $normalizer->fromOpenAlexWork(['display_name' => '']);
        $this->assertNull($malformed);

        $crossrefNoDoi = $normalizer->fromCrossRefWork([
            'title' => ['Crossref title without DOI'],
            'abstract' => 'Irrigation research abstract for tomato production.',
            'URL' => 'https://api.crossref.org/works/example',
        ]);
        $this->assertNotNull($crossrefNoDoi);
        $this->assertNull($crossrefNoDoi->doi);
        $this->assertSame('https://api.crossref.org/works/example', $crossrefNoDoi->canonicalUrl);

        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => 'not-an-array'], 200),
            'api.crossref.org/works*' => Http::response(['message' => 'broken'], 200),
        ]);

        $search = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'wheat irrigation scheduling arid agriculture',
        ]);
        $search->assertOk();
        $body = json_encode($search->json());
        $this->assertStringNotContainsString('10.9999/', (string) $body);
        foreach ($search->json('results') ?? [] as $result) {
            $this->assertNotSame('10.1000/fake', $result['doi'] ?? null);
        }
    }

    public function test_d_evidence_attribution_conflicts_and_insufficient_evidence(): void
    {
        $this->fakeScholarly();

        $empty = $this->postJson('/api/v1/public/research-agent/synthesize', [
            'organization' => 'wsa-demo',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $empty->assertOk();
        $this->assertSame('no_search_results', $empty->json('status'));
        $this->assertSame([], $empty->json('citations') ?? []);
        $this->assertFalse((bool) ($empty->json('library_persistence.performed') ?? false));

        $this->fakeScholarly([
            $this->openAlexWork(
                'Conflicting irrigation volumes for wheat',
                'Wheat drip irrigation scheduling uses 400 mm seasonal water in arid agriculture.',
                '10.1000/stage9-conflict-a',
            ),
        ], [[
            'DOI' => '10.1000/stage9-conflict-b',
            'title' => ['Alternate irrigation volumes for wheat'],
            'abstract' => 'Wheat drip irrigation scheduling uses 800 mm seasonal water in arid agriculture.',
            'publisher' => 'University of Agriculture',
            'container-title' => ['Journal of Agricultural Science'],
            'issued' => ['date-parts' => [[2022]]],
            'author' => [['given' => 'Jane', 'family' => 'Researcher']],
        ]]);

        $conflict = $this->postJson('/api/v1/public/research-agent/validate', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $conflict->assertOk();
        $conflict->assertJsonPath('validation.performed', true);
        $this->assertIsInt($conflict->json('validation.duplicate_count'));
        $this->assertIsInt($conflict->json('validation.conflicting_count'));
    }

    public function test_e_library_pdf_listing_and_content_remain_intact(): void
    {
        Storage::fake('local');
        $org = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        $path = 'library/crop-files/wheat-guide.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 stage9');

        $item = LibraryItem::create([
            'organization_id' => $org->id,
            'slug' => 'crop-file-grains-wheat-farming-needs-stage9',
            'title' => 'دليل القمح',
            'title_ar' => 'دليل القمح',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => $path,
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $index = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'wsa-demo',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]));
        $index->assertOk();
        $index->assertJsonPath('data.0.id', $item->id);
        $index->assertJsonPath('data.0.preview_mode', 'inline_browser');
        $index->assertJsonMissingPath('data.0.file_path');
        $index->assertJsonMissingPath('data.0.file_disk');

        $content = $this->get('/api/v1/public/library/crop-files/'.$item->id.'/content?organization=wsa-demo');
        $content->assertOk();
        $content->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $content->headers->get('content-disposition'));
        $this->assertSame('%PDF-1.4 stage9', $content->streamedContent());
    }

    public function test_f_diagnosis_and_research_remain_runtime_independent(): void
    {
        $diagnosis = new ReflectionClass(PlantAiDiagnosisEngine::class);
        $research = new ReflectionClass(AgriculturalResearchAgent::class);
        $diagnosisController = new ReflectionClass(PlantAiDiagnosisController::class);
        $researchController = new ReflectionClass(AgriculturalResearchAgentController::class);

        $this->assertStringNotContainsString('Research\\', $this->constructorTypes($diagnosis));
        $this->assertStringNotContainsString('Diagnosis\\', $this->constructorTypes($research));
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $this->codeWithoutComments($diagnosis));
        $this->assertStringNotContainsString('PlantAiDiagnosisEngine', $this->codeWithoutComments($research));
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $this->codeWithoutComments($diagnosisController));
        $this->assertStringNotContainsString('PlantAiDiagnosisEngine', $this->codeWithoutComments($researchController));

        $knowledge = $this->postJson('/api/v1/public/plant-diagnosis/knowledge', [
            'crop' => 'tomato',
            'symptoms' => ['leaf spot'],
        ]);
        $knowledge->assertOk();
        $knowledge->assertJsonPath('independent_of_research_agent', true);
        $knowledge->assertJsonPath('engine', 'plant_ai_diagnosis_knowledge_base');
        $this->assertArrayNotHasKey('scientific_search', $knowledge->json());
    }

    public function test_h_public_contracts_do_not_require_auth(): void
    {
        $this->getJson('/api/v1/public/field-crops/taxonomy')->assertOk();

        $this->postJson('/api/v1/public/research-agent/plan', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ])->assertOk();
    }

    public function test_i_stage_1_to_8_orchestrators_still_resolve(): void
    {
        $this->assertTrue(class_exists(\Tests\Feature\AgriculturalResearchAgentStage1Test::class));
        $this->assertTrue(class_exists(\Tests\Feature\AgriculturalResearchAgentStage5Test::class));
        $this->assertTrue(class_exists(\Tests\Feature\PlantAiDiagnosisEngineStage6Test::class));
        $this->assertTrue(class_exists(\Tests\Feature\PlantAiDiagnosisKnowledgeBaseStage7Test::class));
        $this->assertTrue(class_exists(\Tests\Feature\FieldCropPublicTaxonomyTest::class));
        $this->assertTrue(class_exists(\Tests\Feature\CropLibraryPublicFilesTest::class));
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\Research\\AgriculturalResearchAgentV2'));
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\PlantAiDiagnosisService'));
    }

    /**
     * @param  mixed  $payload
     * @return list<string>
     */
    private function decodedDois(mixed $payload): array
    {
        $dois = [];
        $walk = function (mixed $value) use (&$dois, &$walk): void {
            if (! is_array($value)) {
                return;
            }
            if (isset($value['doi']) && is_string($value['doi']) && $value['doi'] !== '') {
                $dois[] = $value['doi'];
            }
            foreach ($value as $child) {
                $walk($child);
            }
        };
        $walk($payload);

        return array_values(array_unique($dois));
    }

    private function constructorTypes(ReflectionClass $class): string
    {
        $constructor = $class->getConstructor();
        if ($constructor === null) {
            return '';
        }

        $names = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type !== null) {
                $names[] = $type->__toString();
            }
        }

        return implode('|', $names);
    }

    private function codeWithoutComments(ReflectionClass $class): string
    {
        $source = file_get_contents($class->getFileName() ?: '') ?: '';
        $codeOnly = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;

        return preg_replace('#//.*$#m', '', $codeOnly) ?? $codeOnly;
    }
}
