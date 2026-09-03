<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AgriculturalResearchAgentController;
use App\Http\Controllers\Api\PlantAiDiagnosisController;
use App\Http\Middleware\LogApiRequests;
use App\Models\Organization;
use App\Providers\AppServiceProvider;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ScientificMetadataValidator;
use App\Services\Agriculture\ScientificSourceDiscoveryPipeline;
use App\Support\HealthCheckMessages;
use App\Support\ProductionSafeApiExceptionRenderer;
use App\Support\ScientificHttp;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[Group('stage10')]
#[Group('security')]
class WsaEnterpriseStage10ProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        Http::preventStrayRequests();
    }

    public function test_health_live_and_ready_do_not_leak_debug(): void
    {
        config(['app.debug' => false]);

        $live = $this->getJson('/api/v1/health/live');
        $live->assertOk();
        $live->assertJsonPath('status', 'ok');
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug($live->json() ?? []));
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug((string) $live->getContent()));

        $ready = $this->getJson('/api/v1/health/ready');
        $ready->assertOk();
        $ready->assertJsonPath('status', 'ok');
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug($ready->json() ?? []));

        $legacy = $this->getJson('/api/v1/health');
        $legacy->assertOk();
        $legacy->assertExactJson(['status' => 'ok']);
    }

    public function test_unhandled_api_exception_hides_trace_when_debug_off(): void
    {
        config(['app.debug' => false]);

        $request = Request::create('/api/v1/health/live', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(ExceptionHandler::class)->render(
            $request,
            new \RuntimeException('secret boom at '.base_path().' vendor/laravel APP_KEY=base64:fake'),
        );

        $this->assertSame(500, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('Server error.', $payload['message'] ?? null);
        $this->assertArrayNotHasKey('exception', $payload);
        $this->assertArrayNotHasKey('file', $payload);
        $this->assertArrayNotHasKey('line', $payload);
        $this->assertArrayNotHasKey('trace', $payload);
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug((string) $response->getContent()));
        $this->assertStringNotContainsString('secret boom', (string) $response->getContent());
    }

    public function test_validation_errors_remain_structured_without_traces(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', []);
        $response->assertUnprocessable();
        $response->assertJsonStructure(['message', 'errors']);
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug((string) $response->getContent()));
    }

    public function test_production_boot_forces_debug_off(): void
    {
        config(['app.debug' => true]);
        $this->app->instance('env', 'production');

        (new AppServiceProvider($this->app))->boot();

        $this->assertFalse((bool) config('app.debug'));
    }

    public function test_health_failure_messages_hide_secrets_when_debug_off(): void
    {
        config(['app.debug' => false]);

        $message = HealthCheckMessages::forFailure(
            'database',
            new \RuntimeException('pgsql: password=super-secret host=/var/www/html'),
        );

        $this->assertSame('Database connection failed.', $message);
        $this->assertStringNotContainsString('super-secret', $message);
    }

    public function test_api_request_logs_omit_credentials(): void
    {
        $source = (string) file_get_contents((new ReflectionClass(LogApiRequests::class))->getFileName());
        $this->assertStringContainsString("'api.request'", $source);
        $this->assertStringNotContainsString('$request->all()', $source);
        $this->assertStringNotContainsString('$request->input(', $source);
        $this->assertStringNotContainsString('password', strtolower($source));
        $this->assertStringNotContainsString('authorization', strtolower($source));
        $this->assertStringNotContainsString('bearer', strtolower($source));

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'stage10@wsa.test',
            'password' => 'super-secret-login-password',
        ]);
        $this->assertStringNotContainsString('super-secret-login-password', (string) $response->getContent());
        $this->assertFalse(ProductionSafeApiExceptionRenderer::payloadLeaksDebug((string) $response->getContent()));
    }

    public function test_scientific_http_timeout_is_bounded(): void
    {
        $this->assertSame(15, ScientificHttp::timeoutSeconds());

        config(['wsa.scientific_http_timeout' => 120]);
        $this->assertSame(60, ScientificHttp::timeoutSeconds());

        config(['wsa.scientific_http_timeout' => 0]);
        $this->assertSame(1, ScientificHttp::timeoutSeconds());
    }

    public function test_queue_worker_retries_are_bounded_in_compose(): void
    {
        $root = $this->repositoryRoot();
        $compose = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'docker-compose.yml');
        $this->assertStringContainsString('--tries=3', $compose);
        $this->assertStringContainsString('--timeout=90', $compose);

        $prod = (string) file_get_contents($root.DIRECTORY_SEPARATOR.'docker-compose.prod.yml');
        $this->assertMatchesRegularExpression('/APP_DEBUG:\\s*"false"/', $prod);
        $this->assertStringContainsString('APP_ENV: production', $prod);
    }

    public function test_external_content_is_data_and_missing_doi_is_not_fabricated(): void
    {
        $inverted = [];
        foreach (preg_split('/\s+/', 'Wheat drip irrigation scheduling in arid agriculture.') ?: [] as $index => $word) {
            $inverted[$word][] = $index;
        }

        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [[
                    'id' => 'https://openalex.org/Wstage10',
                    'display_name' => 'Wheat drip irrigation scheduling in arid agriculture',
                    'publication_year' => 2024,
                    'abstract_inverted_index' => $inverted,
                    'primary_location' => [
                        'landing_page_url' => 'https://openalex.org/works/Wstage10',
                        'source' => ['display_name' => 'Journal of Agronomy'],
                    ],
                    'authorships' => [[
                        'author' => ['display_name' => 'Dr Researcher'],
                        'institutions' => [[
                            'display_name' => 'University of Agriculture',
                            'type' => 'education',
                        ]],
                    ]],
                ]],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $normalized = app(ScientificResultNormalizer::class)->fromOpenAlexWork([
            'id' => 'https://openalex.org/Wstage10',
            'display_name' => 'Wheat drip irrigation scheduling in arid agriculture',
            'publication_year' => 2024,
            'abstract_inverted_index' => $inverted,
            'primary_location' => [
                'landing_page_url' => 'https://openalex.org/works/Wstage10',
            ],
        ]);
        $this->assertNull($normalized->doi);

        $response = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $response->assertOk();
        $body = (string) $response->getContent();
        $this->assertStringNotContainsString('10.9999/', $body);
        $this->assertStringNotContainsString('10.1000/fake', $body);

        $dois = [];
        $walk = function (mixed $value) use (&$dois, &$walk): void {
            if (! is_array($value)) {
                return;
            }
            if (array_key_exists('doi', $value) && is_string($value['doi']) && $value['doi'] !== '') {
                $dois[] = $value['doi'];
            }
            foreach ($value as $child) {
                $walk($child);
            }
        };
        $walk($response->json());
        $this->assertSame([], $dois);
    }

    public function test_scientific_validation_rejects_unverifiable_doi_without_fabrication(): void
    {
        $validator = app(ScientificMetadataValidator::class);
        $assessment = $validator->validate(new ScientificSearchResult(
            'crossref',
            'cr-stage10-bad-doi',
            'Stage 10 bad DOI paper',
            ['Author'],
            2022,
            'not-a-real-doi',
            'https://example.org/stage10-paper',
            'Abstract content for Stage 10 validation safety.',
            'Journal',
            ['crossref'],
        ));

        $this->assertContains('unverifiable_doi', $assessment['failures']);
        $this->assertFalse($assessment['fields']['doi'] ?? true);
    }

    public function test_library_persistence_keeps_stage5_provenance_contract(): void
    {
        $method = (new ReflectionClass(ScientificKnowledgePersistenceService::class))
            ->getMethod('provenance');
        $method->setAccessible(true);

        $source = $this->codeWithoutComments(new ReflectionClass(ScientificKnowledgePersistenceService::class));
        $this->assertStringContainsString("'pipeline' => 'agricultural_research_agent_stage_5'", $source);
        $this->assertStringContainsString("'internet_first'", $source);
        $this->assertStringContainsString("'validation_status'", $source);
        $this->assertTrue($method->isPrivate());
    }

    public function test_research_remains_internet_first_and_independent_of_diagnosis(): void
    {
        $order = app(ScientificSourceDiscoveryPipeline::class)->discovererOrder();
        $this->assertSame('external_openalex', $order[0]);
        $this->assertSame('external_crossref', $order[1]);
        $this->assertTrue(array_search('library_structured', $order, true) > array_search('external_crossref', $order, true));

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
        $this->assertArrayNotHasKey('scientific_search', $knowledge->json());
    }

    public function test_ci_keeps_stage10_gate(): void
    {
        $ci = (string) file_get_contents(
            $this->repositoryRoot()
            .DIRECTORY_SEPARATOR.'.github'
            .DIRECTORY_SEPARATOR.'workflows'
            .DIRECTORY_SEPARATOR.'ci.yml'
        );
        $this->assertStringContainsString('--group=stage10', $ci);
        $this->assertStringContainsString('--group=security', $ci);
        $this->assertStringContainsString('php artisan test --no-ansi', $ci);
    }

    private function repositoryRoot(): string
    {
        foreach ([
            '/var/www/repo',
            dirname(base_path()),
            realpath(base_path('..')) ?: '',
            getenv('WSA_REPO_ROOT') ?: '',
        ] as $candidate) {
            if ($candidate !== '' && is_file($candidate.DIRECTORY_SEPARATOR.'docker-compose.yml')) {
                return $candidate;
            }
        }

        $this->fail('Unable to locate repository root for compose/CI fixtures.');
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
