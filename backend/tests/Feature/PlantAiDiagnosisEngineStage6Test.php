<?php

namespace Tests\Feature;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiProviderUnavailableException;
use App\Models\Organization;
use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\DiagnosisStatus;
use App\Services\Agriculture\Diagnosis\Image\DiagnosisImageValidator;
use App\Services\Agriculture\Diagnosis\Knowledge\DiagnosisKnowledgeSupportInterface;
use App\Services\Agriculture\Diagnosis\Knowledge\HeuristicDiagnosisKnowledgeSupport;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\PlantDiagnosisRequest;
use App\Services\Agriculture\Diagnosis\Safety\DiagnosisSafetyGuard;
use App\Services\Agriculture\Diagnosis\UncertaintyAssessment;
use App\Services\Agriculture\Diagnosis\Vision\VisionAnalysisProviderInterface;
use App\Services\Agriculture\Diagnosis\VisionObservation;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\AgriculturalScientificKnowledgeEngine;
use App\Services\Ai\MockAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class PlantAiDiagnosisEngineStage6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        config(['ai.provider' => 'mock']);
    }

    private function validImage(string $name = 'plant.png', int $width = 200, int $height = 200): UploadedFile
    {
        $binary = $this->makePngBinary(max(64, $width), max(64, $height));
        $path = tempnam(sys_get_temp_dir(), 'wsa_plant_');
        $this->assertNotFalse($path);
        $target = $path.'.png';
        rename($path, $target);
        file_put_contents($target, $binary);

        return new UploadedFile($target, $name, 'image/png', null, true);
    }

    private function validImageBinary(int $width = 128, int $height = 128): string
    {
        return $this->makePngBinary(max(64, $width), max(64, $height));
    }

    /**
     * Build a valid solid-color PNG without requiring the GD extension.
     */
    private function makePngBinary(int $width, int $height): string
    {
        $raw = '';
        for ($y = 0; $y < $height; $y++) {
            $raw .= "\x00"; // filter none
            for ($x = 0; $x < $width; $x++) {
                $raw .= "\x2E\x7D\x32"; // RGB green-ish plant tone
            }
        }

        $ihdr = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n";
        $png .= $this->pngChunk('IHDR', $ihdr);
        $png .= $this->pngChunk('IDAT', gzcompress($raw, 9) ?: '');
        $png .= $this->pngChunk('IEND', '');

        return $png;
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    public function test_architecture_independence_from_research_agent(): void
    {
        $this->assertTrue(class_exists(PlantAiDiagnosisEngine::class));
        // Protected Stage 4/5 assertion names must remain unused.
        $this->assertFalse(class_exists('App\\Services\\Agriculture\\PlantAiDiagnosisService'));
        $this->assertFalse(class_exists('App\\Services\\PlantAi\\PlantDiagnosisAgent'));

        $reflection = new ReflectionClass(PlantAiDiagnosisEngine::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);

        $dependencyNames = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type !== null) {
                $dependencyNames[] = $type->__toString();
            }
        }

        $joined = implode('|', $dependencyNames);
        $this->assertStringNotContainsString(AgriculturalResearchAgent::class, $joined);
        $this->assertStringNotContainsString(AgriculturalScientificKnowledgeEngine::class, $joined);
        $this->assertStringNotContainsString('Research\\', $joined);

        $source = file_get_contents($reflection->getFileName() ?: '') ?: '';
        // Ignore docblocks/comments: assert no runtime imports or calls.
        $codeOnly = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
        $codeOnly = preg_replace('#//.*$#m', '', $codeOnly) ?? $codeOnly;
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $codeOnly);
        $this->assertStringNotContainsString('AgriculturalScientificKnowledgeEngine', $codeOnly);
        $this->assertStringNotContainsString('ResearchPlanner', $codeOnly);
        $this->assertStringNotContainsString('AnswerComposer', $codeOnly);
        $this->assertStringNotContainsString('use App\\Services\\Agriculture\\Research\\', $codeOnly);
    }

    public function test_controller_does_not_invoke_research_agent(): void
    {
        $controllerSource = file_get_contents(app_path('Http/Controllers/Api/PlantAiDiagnosisController.php')) ?: '';
        $this->assertStringContainsString('PlantAiDiagnosisEngine', $controllerSource);
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $controllerSource);
        $this->assertStringNotContainsString('AgriculturalScientificKnowledgeEngine', $controllerSource);
    }

    public function test_image_validation_rejects_unsupported_type(): void
    {
        $validator = new DiagnosisImageValidator;
        $this->expectException(ValidationException::class);
        $validator->validateBinary('%PDF-1.4 fake', 'doc.pdf', 'application/pdf');
    }

    public function test_image_validation_rejects_corrupted_bytes(): void
    {
        $validator = new DiagnosisImageValidator;
        // JPEG magic bytes but truncated/corrupt body.
        $corrupt = "\xFF\xD8\xFF\xE0".random_bytes(32);
        $this->expectException(ValidationException::class);
        $validator->validateBinary($corrupt, 'bad.jpg', 'image/jpeg');
    }

    public function test_image_validation_rejects_oversized_image(): void
    {
        $validator = new DiagnosisImageValidator(maxBytes: 200);
        $this->expectException(ValidationException::class);
        $validator->validateBinary(str_repeat('A', 250), 'big.bin', 'image/png');
    }

    public function test_image_validation_does_not_trust_client_mime_alone(): void
    {
        $validator = new DiagnosisImageValidator;
        $binary = $this->validImageBinary(128, 128);
        $validated = $validator->validateBinary($binary, 'plant.png', 'application/octet-stream');

        $this->assertSame('image/png', $validated['metadata']->detectedMime);
        $this->assertSame('application/octet-stream', $validated['metadata']->clientClaimedMime);
        $this->assertGreaterThanOrEqual(64, $validated['metadata']->width);
    }

    public function test_image_metadata_hides_filesystem_paths(): void
    {
        $engine = app(PlantAiDiagnosisEngine::class);
        $result = $engine->diagnose([
            'image' => $this->validImage(),
            'plant_name' => 'Tomato',
        ]);

        $payload = $result->toArray();
        $encoded = json_encode($payload) ?: '';
        $this->assertStringNotContainsString('storage/', $encoded);
        $this->assertStringNotContainsString('C:\\', $encoded);
        $this->assertStringNotContainsString('/var/www', $encoded);
        $this->assertArrayHasKey('content_hash', $payload['image'] ?? []);
        $this->assertArrayNotHasKey('storage_path', $payload['image'] ?? []);
        $this->assertArrayNotHasKey('path', $payload['image'] ?? []);
        $this->assertArrayNotHasKey('absolute_path', $payload['image'] ?? []);
    }

    public function test_vision_produces_observations_not_immediate_disease_assertion(): void
    {
        $engine = app(PlantAiDiagnosisEngine::class);
        $result = $engine->diagnose([
            'image' => $this->validImage(),
            'plant_name' => 'Tomato',
            'crop_type' => 'tomato',
            'symptoms' => ['leaf spots'],
        ]);

        $this->assertNotSame(DiagnosisStatus::INVALID_INPUT, $result->status);
        $this->assertNotEmpty($result->observations);
        foreach ($result->observations as $observation) {
            $this->assertInstanceOf(VisionObservation::class, $observation);
            $this->assertNotSame('', $observation->description);
        }
    }

    public function test_insufficient_image_quality_status(): void
    {
        $this->app->instance(VisionAnalysisProviderInterface::class, new class implements VisionAnalysisProviderInterface
        {
            public function analyze(PlantDiagnosisRequest $request): array
            {
                return [
                    'image_quality' => 'blurry',
                    'plant_visible' => false,
                    'symptoms_visible' => false,
                    'quality_notes' => ['too blurry'],
                    'observations' => [],
                    'provider' => 'fixture',
                    'model' => 'fixture-v1',
                    'raw_status' => 'completed',
                ];
            }
        });

        $result = app(PlantAiDiagnosisEngine::class)->diagnose([
            'image' => $this->validImage(),
        ]);

        $this->assertSame(DiagnosisStatus::INSUFFICIENT_IMAGE, $result->status);
        $this->assertNotEmpty($result->additionalInfoRequests);
    }

    public function test_candidate_ranking_and_confidence_bands(): void
    {
        $engine = app(PlantAiDiagnosisEngine::class);
        $result = $engine->diagnose([
            'image' => $this->validImage(),
            'plant_name' => 'Tomato',
            'crop_type' => 'tomato',
            'notes' => 'Brown leaf spots with yellow halos',
            'symptoms' => ['spot', 'lesion'],
        ]);

        $this->assertNotEmpty($result->candidates);
        $previous = 1.0;
        foreach ($result->candidates as $index => $candidate) {
            $this->assertSame($index + 1, $candidate->rank);
            $this->assertLessThanOrEqual(DiagnosisConfidenceBand::MAX_IMAGE_ALONE_SCORE, $candidate->confidenceScore);
            $this->assertContains($candidate->confidenceBand, DiagnosisConfidenceBand::all());
            $this->assertLessThanOrEqual($previous + 0.0001, $candidate->confidenceScore);
            $previous = $candidate->confidenceScore;
            $this->assertFalse($candidate->scientificNameVerified);
            $this->assertNull($candidate->scientificName);
        }
    }

    public function test_safety_never_allows_100_percent_certainty(): void
    {
        $guard = app(DiagnosisSafetyGuard::class);
        $candidates = [
            new CandidateDiagnosis(
                id: 'fake',
                commonName: 'Fake absolute disease',
                scientificName: 'Inventus absolutus',
                scientificNameVerified: false,
                confidenceScore: 1.0,
                confidenceBand: DiagnosisConfidenceBand::HIGH,
                rank: 1,
                rationale: 'Apply fungicide X at 2.5 ml/L concentration.',
                evidence: [],
            ),
        ];

        $safe = $guard->apply(
            $candidates,
            new UncertaintyAssessment(overallUncertainty: 0.1),
            ['Spray pesticide Y at 500 ppm'],
            true,
        );

        $this->assertLessThanOrEqual(DiagnosisConfidenceBand::MAX_IMAGE_ALONE_SCORE, $safe['candidates'][0]->confidenceScore);
        $this->assertNull($safe['candidates'][0]->scientificName);
        $this->assertFalse($safe['candidates'][0]->scientificNameVerified);
        $this->assertStringNotContainsString('2.5 ml', $safe['candidates'][0]->rationale);
        foreach ($safe['management_notes'] as $note) {
            $this->assertStringNotContainsString('500 ppm', $note);
            $this->assertStringNotContainsString('2.5 ml', $note);
        }
        $this->assertTrue($safe['safety']->imageAloneNotDefinitive);
        $this->assertTrue($safe['safety']->pesticideDosageForbidden);
        $this->assertTrue($safe['safety']->managementDistinctFromDiagnosis);
    }

    public function test_additional_information_loop_is_selective(): void
    {
        $result = app(PlantAiDiagnosisEngine::class)->diagnose([
            'image' => $this->validImage(),
        ]);

        $this->assertLessThanOrEqual(4, count($result->additionalInfoRequests));
        $this->assertNotEmpty($result->additionalInfoRequests);
        $ids = array_map(static fn ($q) => $q->id, $result->additionalInfoRequests);
        $this->assertContains('plant_identity', $ids);
    }

    public function test_analysis_unavailable_when_provider_fails(): void
    {
        $this->app->instance(VisionAnalysisProviderInterface::class, new class implements VisionAnalysisProviderInterface
        {
            public function analyze(PlantDiagnosisRequest $request): array
            {
                throw new AiProviderUnavailableException('mock', 502, 'Provider down');
            }
        });

        $result = app(PlantAiDiagnosisEngine::class)->diagnose([
            'image' => $this->validImage(),
            'plant_name' => 'Wheat',
        ]);

        $this->assertSame(DiagnosisStatus::ANALYSIS_UNAVAILABLE, $result->status);
    }

    public function test_public_api_analyze_endpoint(): void
    {
        $response = $this->post('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'plant_name' => 'Tomato',
            'crop_type' => 'tomato',
            'symptoms' => ['leaf spots'],
            'image' => $this->validImage(),
        ]);

        $response->assertOk()
            ->assertJsonPath('stage', 6)
            ->assertJsonPath('engine', 'plant_ai_diagnosis')
            ->assertJsonPath('independent_of_research_agent', true)
            ->assertJsonStructure([
                'status',
                'message',
                'observations',
                'candidates',
                'uncertainty',
                'additional_info_requests',
                'management_notes',
                'safety',
                'observability',
                'image' => ['content_hash', 'detected_mime', 'size_bytes', 'width', 'height'],
            ]);

        $this->assertContains($response->json('status'), DiagnosisStatus::all());
        $this->assertArrayNotHasKey('storage_path', $response->json('image') ?? []);
    }

    public function test_public_api_rejects_filesystem_path_input(): void
    {
        $response = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'image_path' => 'ai-vision/1/secret.jpg',
            'plant_name' => 'Tomato',
        ]);

        $response->assertStatus(422);
    }

    public function test_public_api_invalid_input_without_image(): void
    {
        $response = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'plant_name' => 'Tomato',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', DiagnosisStatus::INVALID_INPUT);
    }

    public function test_public_api_accepts_base64_image(): void
    {
        $binary = $this->validImageBinary(160, 160);

        $response = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'plant_name' => 'Tomato',
            'image_base64' => base64_encode($binary),
            'image_name' => 'leaf.png',
            'image_mime' => 'image/jpeg',
        ]);

        $response->assertOk()
            ->assertJsonPath('engine', 'plant_ai_diagnosis');
    }

    public function test_observability_does_not_log_image_bytes_or_secrets(): void
    {
        Log::spy();

        app(PlantAiDiagnosisEngine::class)->diagnose([
            'image' => $this->validImage(),
            'plant_name' => 'Tomato',
            'notes' => 'token=sk-secretvalue api_key=abc',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context = []): bool {
                if ($message !== 'plant_diagnosis.started' && $message !== 'plant_diagnosis.completed') {
                    return false;
                }

                $encoded = json_encode($context) ?: '';

                return ! str_contains($encoded, 'sk-secretvalue')
                    && ! array_key_exists('image_binary', $context)
                    && ! array_key_exists('image_base64', $context)
                    && ! array_key_exists('binary', $context);
            })
            ->atLeast()
            ->once();
    }

    public function test_knowledge_support_is_abstracted_and_default_heuristic_bound(): void
    {
        $this->assertInstanceOf(
            HeuristicDiagnosisKnowledgeSupport::class,
            app(DiagnosisKnowledgeSupportInterface::class),
        );

        $support = app(DiagnosisKnowledgeSupportInterface::class);
        $candidates = $support->suggestCandidates(
            new PlantContext(plantName: 'Tomato'),
            [
                new VisionObservation(
                    id: 'obs-1',
                    type: 'leaf_spot',
                    description: 'Brown spot lesion on leaf',
                    supportingCues: ['spot', 'lesion'],
                ),
            ],
        );

        $this->assertNotEmpty($candidates);
        $this->assertNull($candidates[0]->scientificName);
        $this->assertFalse($candidates[0]->scientificNameVerified);
    }

    public function test_mock_ai_provider_supports_plant_vision_analysis(): void
    {
        /** @var MockAiProvider $provider */
        $provider = app(MockAiProvider::class);
        $output = $provider->complete('plant_vision_analysis', [
            'notes' => 'observe only',
            'image_content_hash' => 'abc',
        ]);

        $this->assertSame('adequate', $output['image_quality']);
        $this->assertNotEmpty($output['observations']);
        $this->assertInstanceOf(AiProviderInterface::class, $provider);
    }

    public function test_engine_result_statuses_are_stable_contract(): void
    {
        $this->assertSame(
            [
                'diagnosed',
                'probable',
                'uncertain',
                'insufficient_image',
                'insufficient_context',
                'invalid_input',
                'analysis_unavailable',
            ],
            DiagnosisStatus::all(),
        );
    }

    public function test_disabled_config_returns_503(): void
    {
        config(['wsa.plant_diagnosis.enabled' => false]);

        $response = $this->post('/api/v1/public/plant-diagnosis/analyze', [
            'image' => $this->validImage(),
        ]);

        $response->assertStatus(503)->assertJsonPath('status', 'disabled');
    }
}
