<?php

namespace Tests\Feature;

use App\Models\DiagnosisRequest;
use App\Models\Organization;
use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase5ModuleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTenantMember(): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_diagnosis_reference_data_requires_authentication(): void
    {
        $this->getJson('/api/v1/diagnosis/categories')->assertUnauthorized();
    }

    public function test_diagnosis_workflow_creates_decision_support_result(): void
    {
        [$organization, $user] = $this->actingAsTenantMember();

        $category = $this->postJson('/api/v1/diagnosis/categories', [
            'code' => 'CROP',
            'name' => 'Crop diseases',
            'name_ar' => 'أمراض المحاصيل',
            'is_active' => true,
        ])->assertCreated()->json();

        $this->postJson('/api/v1/diagnosis/requests', [
            'reference' => 'DX-001',
            'notes' => 'Lower leaf spotting observed.',
            'symptom_ids' => [],
        ])->assertCreated()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('organization_id', $organization->id);

        $request = DiagnosisRequest::firstOrFail();
        $this->assertSame('completed', $request->status);
        $this->assertCount(1, $request->results);
        $this->assertTrue($request->results->first()->is_decision_support);
    }

    public function test_training_enrollment_and_progress_workflow(): void
    {
        [$organization, $user] = $this->actingAsTenantMember();

        $course = $this->postJson('/api/v1/training/courses', [
            'code' => 'C1',
            'title' => 'Demo course',
            'title_ar' => 'دورة تجريبية',
            'locale' => 'ar',
            'status' => 'published',
        ])->assertCreated()->json();

        $lesson = $this->postJson('/api/v1/training/lessons', [
            'course_id' => $course['id'],
            'code' => 'L1',
            'title' => 'Lesson 1',
            'title_ar' => 'الدرس 1',
            'status' => 'published',
        ])->assertCreated()->json();

        $enrollment = $this->postJson('/api/v1/training/enrollments', [
            'course_id' => $course['id'],
        ])->assertCreated()->json();

        $this->postJson('/api/v1/training/progress/complete', [
            'enrollment_id' => $enrollment['id'],
            'lesson_id' => $lesson['id'],
            'score' => 85,
        ])->assertOk()->assertJsonPath('status', 'completed');

        $this->getJson('/api/v1/training/enrollments')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'completed');
    }

    public function test_library_search_returns_published_items_only(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->postJson('/api/v1/library/items', [
            'slug' => 'draft-item',
            'title' => 'Draft article',
            'title_ar' => 'مسودة',
            'publication_status' => 'draft',
        ])->assertCreated();

        $this->postJson('/api/v1/library/items', [
            'slug' => 'published-tomato-guide',
            'title' => 'Tomato guide',
            'title_ar' => 'دليل الطماطم',
            'summary' => 'Tomato management article',
            'summary_ar' => 'مقال إدارة الطماطم',
            'publication_status' => 'published',
            'published_at' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/library/search?q=Tomato')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.slug', 'published-tomato-guide');
    }

    public function test_ai_provider_uses_mock_without_external_calls(): void
    {
        [$organization] = $this->actingAsTenantMember();

        $this->getJson('/api/v1/ai/provider')
            ->assertOk()
            ->assertJsonPath('provider', 'mock');

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['title' => 'Demo article'],
        ])->assertCreated()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonPath('status', 'completed');
    }

    public function test_phase5_records_are_scoped_by_organization(): void
    {
        [$organizationA] = $this->actingAsTenantMember();

        TrainingCourse::create([
            'organization_id' => $organizationA->id,
            'code' => 'A1',
            'title' => 'Local course',
            'status' => 'published',
        ]);

        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        TrainingCourse::create([
            'organization_id' => $organizationB->id,
            'code' => 'B1',
            'title' => 'Foreign course',
            'status' => 'published',
        ]);

        $this->getJson('/api/v1/training/courses')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'A1');
    }

    public function test_agricultural_core_endpoints_remain_available(): void
    {
        $this->actingAsTenantMember();

        $this->getJson('/api/v1/farm/farms')->assertOk();
        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/soil/analyses')->assertOk();
    }
}
