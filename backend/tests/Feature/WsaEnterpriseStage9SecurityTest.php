<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AgriculturalResearchAgentController;
use App\Http\Controllers\Api\PlantAiDiagnosisController;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Agriculture\Diagnosis\PlantAiDiagnosisEngine;
use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Media\MediaReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[Group('stage9')]
#[Group('security')]
class WsaEnterpriseStage9SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Organization::create(['name' => 'WSA Demo', 'slug' => 'wsa-demo']);
        Http::preventStrayRequests();
    }

    /** @param  \Illuminate\Testing\TestResponse  $response */
    private function assertNoSensitiveLeak($response): void
    {
        $payload = strtolower((string) json_encode($response->json()));
        $raw = strtolower($response->getContent() ?: '');
        $combined = $payload."\n".$raw;

        foreach ([
            'remember_token',
            'app_key',
            'stack trace',
            'g:\\wsa-enterprise',
            '/var/www/html',
            'vendor/laravel',
            'db_password',
            '"password":',
        ] as $needle) {
            $this->assertStringNotContainsString($needle, $combined, 'Unexpected leak of '.$needle);
        }
    }

    public function test_01_unauthorized_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/dashboard');
        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'Unauthenticated.');
        $this->assertNoSensitiveLeak($response);
    }

    public function test_02_invalid_bearer_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/v1/user');
        $response->assertUnauthorized();
        $this->assertNoSensitiveLeak($response);
    }

    public function test_03_malformed_authorization_header(): void
    {
        $response = $this->withHeader('Authorization', 'Basic ZmFrZTpmYWtl')
            ->getJson('/api/v1/dashboard');
        $response->assertUnauthorized();
        $this->assertNoSensitiveLeak($response);
    }

    public function test_04_login_does_not_leak_password_hash(): void
    {
        $org = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        $user = User::create([
            'name' => 'Stage9 User',
            'email' => 'stage9@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->attach($user->id, ['role' => 'admin']);

        $ok = $this->postJson('/api/v1/auth/login', [
            'email' => 'stage9@wsa.test',
            'password' => 'password',
            'device_name' => 'stage9',
        ]);
        $ok->assertOk();
        $ok->assertJsonMissingPath('user.password');
        $this->assertArrayNotHasKey('password', $ok->json('user') ?? []);
        $this->assertStringNotContainsString('$2y$', (string) $ok->getContent());

        $bad = $this->postJson('/api/v1/auth/login', [
            'email' => 'stage9@wsa.test',
            'password' => 'wrong-password',
        ]);
        $bad->assertUnprocessable();
        $this->assertStringNotContainsString('$2y$', (string) $bad->getContent());
    }

    public function test_05_authenticated_user_payload_omits_secrets(): void
    {
        $org = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        $user = User::create([
            'name' => 'Profile User',
            'email' => 'profile9@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->attach($user->id, ['role' => 'admin']);
        $token = $user->createToken('stage9')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $org->id,
        ])->getJson('/api/v1/user');

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name', 'email']);
        $response->assertJsonMissingPath('password');
        $this->assertSame(['id', 'name', 'email'], array_keys($response->json()));
    }

    public function test_06_library_index_does_not_leak_storage_paths(): void
    {
        Storage::fake('local');
        $org = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        Storage::disk('local')->put('library/crop-files/secret.pdf', '%PDF-1.4');
        LibraryItem::create([
            'organization_id' => $org->id,
            'slug' => 'stage9-secret-pdf',
            'title' => 'Secret',
            'title_ar' => 'سري',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => 'library/crop-files/secret.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $response = $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'wsa-demo',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]));
        $response->assertOk();
        $response->assertJsonMissingPath('data.0.file_path');
        $this->assertStringNotContainsString('library/crop-files/secret.pdf', (string) $response->getContent());
    }

    public function test_07_unpublished_library_files_are_hidden(): void
    {
        Storage::fake('local');
        $org = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        Storage::disk('local')->put('library/crop-files/draft.pdf', '%PDF-1.4');
        $draft = LibraryItem::create([
            'organization_id' => $org->id,
            'slug' => 'stage9-draft-pdf',
            'title' => 'Draft',
            'title_ar' => 'مسودة',
            'item_type' => 'crop_library_file',
            'publication_status' => 'draft',
            'file_disk' => 'local',
            'file_path' => 'library/crop-files/draft.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $this->getJson('/api/v1/public/library/crop-files?'.http_build_query([
            'organization' => 'wsa-demo',
            'plant_production_category_id' => 'grains',
            'field_crop_id' => 'wheat',
            'library_file_section' => 'farming-needs',
        ]))->assertOk()->assertJsonCount(0, 'data');

        $this->get('/api/v1/public/library/crop-files/'.$draft->id.'/content?organization=wsa-demo')
            ->assertNotFound();
    }

    public function test_08_cross_organization_library_content_is_forbidden(): void
    {
        Storage::fake('local');
        $orgA = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        $orgB = Organization::create(['name' => 'Other Org', 'slug' => 'other-org']);
        Storage::disk('local')->put('library/crop-files/b.pdf', '%PDF-1.4 other');
        $itemB = LibraryItem::create([
            'organization_id' => $orgB->id,
            'slug' => 'stage9-org-b',
            'title' => 'Org B',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => 'library/crop-files/b.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $response = $this->getJson('/api/v1/public/library/crop-files/'.$itemB->id.'/content?organization='.$orgA->slug);
        $response->assertNotFound();
        $response->assertJsonPath('message', 'Resource not found.');
        $this->assertStringNotContainsString('%PDF-1.4 other', (string) $response->getContent());
    }

    public function test_09_path_traversal_file_references_are_rejected(): void
    {
        $media = app(MediaReferenceService::class);
        try {
            $media->validateAndSanitize(['file_disk' => 'local', 'file_path' => '../.env']);
            $this->fail('Expected path traversal to abort');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('Invalid file reference.', $exception->getMessage());
        }

        try {
            $media->validateAndSanitize(['file_disk' => 'local', 'file_path' => '/etc/passwd']);
            $this->fail('Expected absolute path to abort');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_10_diagnosis_rejects_path_based_image_references(): void
    {
        $response = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'image_path' => 'storage/app/private/leaf.png',
            'file_path' => '../.env',
        ]);
        $response->assertUnprocessable();
        $this->assertArrayHasKey('image_path', $response->json('errors') ?? []);
        $this->assertStringNotContainsString('.env', strtolower((string) json_encode($response->json('errors'))));
    }

    public function test_11_research_query_length_and_required_fields(): void
    {
        $tooLong = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => str_repeat('wheat irrigation ', 400),
        ]);
        $tooLong->assertUnprocessable();

        $missingOrg = $this->postJson('/api/v1/public/research-agent/query', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $missingOrg->assertUnprocessable();
    }

    public function test_12_unknown_organization_does_not_leak_schema(): void
    {
        $response = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'does-not-exist-stage9',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $response->assertNotFound();
        $response->assertJsonPath('message', 'Organization not found.');
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
        $this->assertNoSensitiveLeak($response);
    }

    public function test_13_malformed_openalex_payload_does_not_invent_metadata(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response([
                'results' => [
                    ['id' => null, 'display_name' => null, 'doi' => 'not-a-real-work'],
                    'garbage',
                    ['display_name' => 'Usable title without identifiers'],
                ],
            ], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => 'nope']], 200),
        ]);

        $outcome = app(OpenAlexScientificSourceAdapter::class)->search('wheat irrigation scheduling arid agriculture');
        foreach ($outcome->results as $result) {
            $this->assertNotSame('https://doi.org/not-a-real-work', $result->canonicalUrl);
            if ($result->title === 'Usable title without identifiers') {
                $this->assertNull($result->doi);
            }
        }

        $search = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'wheat irrigation scheduling arid agriculture',
        ]);
        $search->assertOk();
        $this->assertStringNotContainsString('10.9999/fabricated', (string) $search->getContent());
    }

    public function test_14_malformed_crossref_and_provider_http_errors(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response('internal', 500),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => [['title' => []]]]], 200),
        ]);

        $search = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'wheat irrigation scheduling arid agriculture',
        ]);
        $search->assertOk();
        $this->assertContains('openalex', $search->json('failed_sources') ?? $search->json('scientific_search.failed_sources') ?? []);
        $this->assertNoSensitiveLeak($search);
    }

    public function test_15_provider_timeouts_do_not_become_500_or_leak_traces(): void
    {
        Http::fake([
            'api.openalex.org/works*' => function (): never {
                throw new ConnectionException('cURL error 28: timeout after 15000 ms');
            },
            'api.crossref.org/works*' => function (): never {
                throw new ConnectionException('timeout');
            },
        ]);

        $report = app(AgriculturalScientificSearchService::class)->search(
            app(\App\Services\Agriculture\Research\ResearchPlanner::class)->planKnowledgeQuery([
                'query' => 'wheat drip irrigation scheduling arid agriculture',
            ]),
        );
        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->failedSources);

        $response = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $response->assertOk();
        $this->assertNotSame(500, $response->status());
        $this->assertStringNotContainsString('ConnectionException', (string) $response->getContent());
        $this->assertNoSensitiveLeak($response);
    }

    public function test_16_disabled_engines_return_503_without_internals(): void
    {
        config(['wsa.research_agent.enabled' => false]);
        $research = $this->postJson('/api/v1/public/research-agent/query', [
            'organization' => 'wsa-demo',
            'query' => 'wheat drip irrigation scheduling arid agriculture',
        ]);
        $research->assertStatus(503);
        $research->assertJsonPath('status', 'disabled');
        $this->assertNoSensitiveLeak($research);

        config(['wsa.plant_diagnosis.enabled' => false]);
        $diagnosis = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'image_base64' => base64_encode('not-an-image'),
        ]);
        $diagnosis->assertStatus(503);
        $diagnosis->assertJsonPath('status', 'disabled');
        $this->assertNoSensitiveLeak($diagnosis);
    }

    public function test_17_injection_payloads_do_not_crash_research(): void
    {
        Http::fake([
            'api.openalex.org/works*' => Http::response(['results' => []], 200),
            'api.crossref.org/works*' => Http::response(['message' => ['items' => []]], 200),
        ]);

        $response = $this->postJson('/api/v1/public/research-agent/search', [
            'query' => "wheat irrigation'); DROP TABLE library_items;-- arid agriculture scheduling",
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('organizations', ['slug' => 'wsa-demo']);
        $this->assertNoSensitiveLeak($response);
    }

    public function test_18_bearer_token_does_not_bypass_public_library_scope(): void
    {
        Storage::fake('local');
        $orgA = Organization::query()->where('slug', 'wsa-demo')->firstOrFail();
        $orgB = Organization::create(['name' => 'Private Org', 'slug' => 'private-org']);
        $user = User::create([
            'name' => 'Member A',
            'email' => 'member-a@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $orgA->members()->attach($user->id, ['role' => 'admin']);
        Storage::disk('local')->put('library/crop-files/private.pdf', '%PDF-1.4 private');
        $private = LibraryItem::create([
            'organization_id' => $orgB->id,
            'slug' => 'stage9-private',
            'title' => 'Private',
            'item_type' => 'crop_library_file',
            'publication_status' => 'published',
            'published_at' => now(),
            'file_disk' => 'local',
            'file_path' => 'library/crop-files/private.pdf',
            'metadata' => [
                'plant_production_category_id' => 'grains',
                'field_crop_id' => 'wheat',
                'library_file_section' => 'farming-needs',
            ],
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$user->createToken('stage9')->plainTextToken,
            'X-Organization-Id' => (string) $orgA->id,
        ])->get('/api/v1/public/library/crop-files/'.$private->id.'/content?organization=wsa-demo')
            ->assertNotFound();
    }

    public function test_19_diagnosis_missing_image_is_invalid_not_research(): void
    {
        $response = $this->postJson('/api/v1/public/plant-diagnosis/analyze', [
            'organization' => 'wsa-demo',
            'plant_name' => 'tomato',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonPath('independent_of_research_agent', true);
        $response->assertJsonPath('engine', 'plant_ai_diagnosis');
        $this->assertArrayNotHasKey('citations', $response->json());
    }

    public function test_20_research_controller_has_no_diagnosis_runtime_dependency(): void
    {
        $researchSource = file_get_contents((new \ReflectionClass(AgriculturalResearchAgentController::class))->getFileName() ?: '') ?: '';
        $diagnosisSource = file_get_contents((new \ReflectionClass(PlantAiDiagnosisController::class))->getFileName() ?: '') ?: '';
        $engineSource = file_get_contents((new \ReflectionClass(PlantAiDiagnosisEngine::class))->getFileName() ?: '') ?: '';
        $agentSource = file_get_contents((new \ReflectionClass(AgriculturalResearchAgent::class))->getFileName() ?: '') ?: '';

        $this->assertStringNotContainsString('PlantAiDiagnosis', $researchSource);
        $this->assertStringNotContainsString('AgriculturalResearchAgent', $diagnosisSource);
        $this->assertStringNotContainsString('use App\\Services\\Agriculture\\Research\\', $engineSource);
        $this->assertStringNotContainsString('use App\\Services\\Agriculture\\Diagnosis\\', $agentSource);
    }

    public function test_21_invalid_storage_disk_is_rejected(): void
    {
        try {
            app(MediaReferenceService::class)->validateAndSanitize([
                'file_disk' => 's3-unapproved',
                'file_path' => 'library/crop-files/ok.pdf',
            ]);
            $this->fail('Expected invalid disk to abort');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('Invalid storage disk.', $exception->getMessage());
        }
    }

    public function test_22_normalizer_does_not_invent_doi_from_title(): void
    {
        $result = app(ScientificResultNormalizer::class)->fromOpenAlexWork([
            'display_name' => 'Please cite DOI 10.1000/should-not-copy',
            'doi' => '',
            'id' => 'https://openalex.org/Wstage9',
        ]);
        $this->assertNotNull($result);
        $this->assertNull($result->doi);
        $this->assertSame('https://openalex.org/Wstage9', $result->canonicalUrl);
    }

    public function test_23_error_pages_do_not_include_filesystem_paths(): void
    {
        $notFound = $this->getJson('/api/v1/public/library/crop-files/999999/content?organization=wsa-demo');
        $notFound->assertNotFound();
        $this->assertStringNotContainsString(str_replace('\\', '/', base_path()), str_replace('\\', '/', (string) $notFound->getContent()));
        $this->assertNoSensitiveLeak($notFound);

        $taxonomy = $this->getJson('/api/v1/public/field-crops/taxonomy');
        $taxonomy->assertOk();
        $this->assertNoSensitiveLeak($taxonomy);
    }
}
