<?php

namespace Tests\Feature;

use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase6ModuleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organization, 1: User} */
    private function actingAsTenantMember(string $slug = 'tenant-a', string $email = 'tenant@wsa.test'): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => $slug]);
        $user = User::create([
            'name' => 'Tenant User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_platform_lists_user_organizations(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->getJson('/api/v1/platform/organizations')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.slug', $organization->slug)
            ->assertJsonPath('0.role', 'admin');
    }

    public function test_cross_tenant_organization_header_is_rejected(): void
    {
        $this->actingAsTenantMember();

        $foreign = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $this->withHeader('X-Organization-Id', (string) $foreign->id)
            ->getJson('/api/v1/platform/workflow-summary')
            ->assertForbidden();
    }

    public function test_organization_header_selects_active_tenant(): void
    {
        [$organizationA, $user] = $this->actingAsTenantMember('tenant-a', 'multi@wsa.test');

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $organizationB->members()->attach($user->id, ['role' => 'member']);

        Farm::create(['organization_id' => $organizationA->id, 'code' => 'FA', 'name' => 'Farm A']);
        Farm::create(['organization_id' => $organizationB->id, 'code' => 'FB', 'name' => 'Farm B']);

        $this->withHeader('X-Organization-Id', (string) $organizationB->id)
            ->getJson('/api/v1/farm/farms')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'FB');
    }

    public function test_foreign_diagnosis_request_is_not_accessible(): void
    {
        [$organizationA] = $this->actingAsTenantMember();

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $foreignUser = User::create([
            'name' => 'Foreign',
            'email' => 'foreign@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $organizationB->members()->attach($foreignUser->id, ['role' => 'admin']);

        $foreignRequest = DiagnosisRequest::create([
            'organization_id' => $organizationB->id,
            'user_id' => $foreignUser->id,
            'reference' => 'DX-FOREIGN',
            'status' => 'completed',
        ]);

        $this->getJson('/api/v1/diagnosis/requests/'.$foreignRequest->id)->assertNotFound();
    }

    public function test_diagnosis_rejects_unsafe_image_path(): void
    {
        $this->actingAsTenantMember();

        $this->postJson('/api/v1/diagnosis/requests', [
            'reference' => 'DX-UNSAFE',
            'notes' => 'Unsafe path test',
            'image_disk' => 'local',
            'image_path' => '../secrets.env',
        ])->assertStatus(422);
    }

    public function test_library_search_supports_category_and_crop_filters(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $category = $this->postJson('/api/v1/library/categories', [
            'code' => 'CROP-GUIDES',
            'name' => 'Crop guides',
            'name_ar' => 'أدلة المحاصيل',
        ])->assertCreated()->json();

        $cropType = $this->postJson('/api/v1/crop/types', [
            'code' => 'TOM',
            'name' => 'Tomato',
        ])->assertCreated()->json();

        $this->postJson('/api/v1/library/items', [
            'slug' => 'tomato-guide',
            'title' => 'Tomato guide',
            'title_ar' => 'دليل الطماطم',
            'summary' => 'Tomato management article',
            'publication_status' => 'published',
            'published_at' => now()->toDateString(),
            'category_id' => $category['id'],
            'crop_type_id' => $cropType['id'],
        ])->assertCreated();

        $this->getJson('/api/v1/library/search?q=Tomato&category_id='.$category['id'].'&crop_type_id='.$cropType['id'])
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.slug', 'tomato-guide');
    }

    public function test_ai_provider_lists_supported_request_types(): void
    {
        $this->actingAsTenantMember();

        $this->getJson('/api/v1/ai/provider')
            ->assertOk()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonStructure(['supported_request_types']);
    }

    public function test_ai_rejects_unsupported_request_type(): void
    {
        $this->actingAsTenantMember();

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'unsupported_type',
            'input' => ['query' => 'test'],
        ])->assertStatus(422);
    }

    public function test_ai_training_assistance_returns_normalized_output(): void
    {
        $this->actingAsTenantMember();

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'training_assistance',
            'input' => ['lesson_title' => 'Irrigation basics'],
        ])->assertCreated()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('output.request_type', 'training_assistance')
            ->assertJsonPath('output.is_decision_support', true);
    }

    public function test_end_to_end_demo_workflow(): void
    {
        [$organization, $user] = $this->actingAsTenantMember('wsa-demo', 'admin@wsa.test');

        $this->getJson('/api/v1/platform/organizations')->assertOk();
        $this->getJson('/api/v1/platform/workflow-summary')->assertOk();

        $farm = $this->postJson('/api/v1/farm/farms', [
            'code' => 'GVF',
            'name' => 'Green Valley Farm',
        ])->assertCreated()->json();

        $crop = $this->postJson('/api/v1/crop/types', [
            'code' => 'TOM',
            'name' => 'Tomato',
        ])->assertCreated()->json();

        $this->getJson('/api/v1/farm/farms')->assertOk()->assertJsonPath('0.code', 'GVF');
        $this->getJson('/api/v1/crop/types')->assertOk()->assertJsonPath('0.code', 'TOM');

        $diagnosis = $this->postJson('/api/v1/diagnosis/requests', [
            'reference' => 'DX-E2E-001',
            'crop_type_id' => $crop['id'],
            'notes' => 'Lower leaf spotting on tomato crop.',
        ])->assertCreated()
            ->assertJsonPath('status', 'completed')
            ->assertJsonStructure(['image']);

        $course = $this->postJson('/api/v1/training/courses', [
            'code' => 'IRR-101',
            'title' => 'Irrigation basics',
            'title_ar' => 'أساسيات الري',
            'status' => 'published',
        ])->assertCreated()->json();

        $enrollment = $this->postJson('/api/v1/training/enrollments', [
            'course_id' => $course['id'],
        ])->assertCreated()->json();

        $this->getJson('/api/v1/training/enrollments')
            ->assertOk()
            ->assertJsonPath('0.id', $enrollment['id']);

        $this->postJson('/api/v1/library/items', [
            'slug' => 'tomato-care-guide',
            'title' => 'Tomato care guide',
            'title_ar' => 'دليل العناية بالطماطم',
            'publication_status' => 'published',
            'published_at' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/library/search?q=Tomato')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_qa',
            'input' => ['query' => 'How do I manage tomato leaf spots?'],
        ])->assertCreated()
            ->assertJsonPath('status', 'completed');

        $this->assertSame($organization->id, DiagnosisRequest::firstOrFail()->organization_id);
        $this->assertSame($user->id, DiagnosisRequest::firstOrFail()->user_id);
    }

    public function test_agricultural_and_phase5_regression_endpoints_remain_available(): void
    {
        $this->actingAsTenantMember();

        $this->getJson('/api/v1/farm/farms')->assertOk();
        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/soil/analyses')->assertOk();
        $this->getJson('/api/v1/diagnosis/categories')->assertOk();
        $this->getJson('/api/v1/training/courses')->assertOk();
        $this->getJson('/api/v1/library/items')->assertOk();
        $this->getJson('/api/v1/ai/provider')->assertOk();
    }
}
