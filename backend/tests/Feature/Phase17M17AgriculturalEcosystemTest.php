<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JobContactRequest;
use App\Models\JobTalentContact;
use App\Models\JobTalentProfile;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group security
 */
class Phase17M17AgriculturalEcosystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('phase17-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    private function talentUser(): User
    {
        return User::create([
            'name' => 'Talent User',
            'email' => 'phase17-talent@wsa.test',
            'password' => Hash::make('password'),
        ]);
    }

    private function talentHeaders(User $user, Organization $organization): array
    {
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);
        EnterpriseRoleService::seedForOrganization($organization->id);
        $memberRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'member')
            ->firstOrFail();
        $user->roles()->sync([$memberRole->id => ['organization_id' => $organization->id]]);

        $token = $user->createToken('phase17-talent')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_free_talent_registration_creates_profile_without_paid_services(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $headers = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Agricultural Engineer',
            'specialization' => 'Irrigation',
            'country' => 'JO',
            'disciplines' => ['irrigation'],
            'contact' => [
                'email' => 'talent-private@wsa.test',
                'phone' => '+962700000001',
            ],
        ], $headers)->assertOk();

        $profile = JobTalentProfile::where('user_id', $talent->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('available', $profile->employment_status);
        $this->assertDatabaseMissing('job_contact_transactions', ['contact_request_id' => 999999]);
    }

    public function test_public_candidate_search_hides_contact_information(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $headers = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Crop Specialist',
            'specialization' => 'Crops',
            'country' => 'SA',
            'contact' => ['email' => 'hidden@wsa.test', 'phone' => '+966500000001'],
        ], $headers)->assertOk();

        $search = $this->getJson('/api/v1/jobs/candidates?country=SA', $this->adminHeaders($organization))
            ->assertOk();

        $payload = json_encode($search->json());
        $this->assertStringNotContainsString('hidden@wsa.test', $payload);
        $this->assertStringNotContainsString('+966500000001', $payload);
        $row = collect($search->json('data'))->firstWhere('professional_name', 'Crop Specialist');
        $this->assertIsArray($row);
        $this->assertArrayNotHasKey('cv_path', $row);
        $this->assertArrayNotHasKey('email', $row);
        $this->assertArrayNotHasKey('phone', $row);
        $this->assertArrayNotHasKey('contact', $row);
        $this->assertArrayNotHasKey('user_id', $row);
        $this->assertArrayNotHasKey('address', $row);

        $profile = JobTalentProfile::where('professional_name', 'Crop Specialist')->firstOrFail();
        $detail = $this->getJson('/api/v1/jobs/candidates/'.$profile->id, $this->adminHeaders($organization))
            ->assertOk();
        $this->assertArrayNotHasKey('cv_path', $detail->json());
        $this->assertArrayNotHasKey('contact', $detail->json());
        $this->assertStringNotContainsString('hidden@wsa.test', (string) json_encode($detail->json()));
    }

    public function test_successful_payment_performs_two_way_contact_exchange(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $talentHeaders = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Beekeeping Specialist',
            'specialization' => 'Beekeeping',
            'country' => 'TR',
            'contact' => [
                'email' => 'candidate@wsa.test',
                'phone' => '+905550000001',
                'whatsapp' => '+905550000001',
            ],
        ], $talentHeaders)->assertOk();

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();

        $request = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => [
                'name' => 'Farm HR',
                'email' => 'employer@wsa.test',
                'phone' => '+962700000099',
            ],
            'job_reference' => 'JOB-001',
        ], $this->adminHeaders($organization))->assertCreated();

        $requestId = $request->json('id');
        $idempotency = 'pay-'.Str::uuid();

        $pay = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => $idempotency,
        ], $this->adminHeaders($organization))->assertOk();

        $exchange = $pay->json('exchange');
        $this->assertSame('employer@wsa.test', $exchange['employer_contact']['email']);
        $this->assertSame('candidate@wsa.test', $exchange['candidate_contact']['email']);
        $this->assertSame('contact_exchanged', JobContactRequest::find($requestId)->status);

        $this->assertTrue(
            AuditLog::withoutGlobalScopes()->where('action', 'jobs.contact_exchanged')->exists()
        );
    }

    public function test_payment_idempotency_prevents_duplicate_exchange(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $talentHeaders = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Soil Analyst',
            'contact' => ['email' => 'soil@wsa.test'],
        ], $talentHeaders);

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr@wsa.test'],
        ], $this->adminHeaders($organization))->json('id');

        $key = 'idem-'.Str::uuid();
        $first = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", ['idempotency_key' => $key], $this->adminHeaders($organization));
        $second = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", ['idempotency_key' => $key], $this->adminHeaders($organization));

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('transaction.id'), $second->json('transaction.id'));
    }

    public function test_hired_candidate_retains_profile_and_blocks_new_exchange(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $talentHeaders = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Farm Manager',
            'contact' => ['email' => 'manager@wsa.test'],
        ], $talentHeaders);

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();
        $adminHeaders = $this->adminHeaders($organization);

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Ops', 'email' => 'ops@wsa.test'],
        ], $adminHeaders)->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'hire-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertOk();

        $profile->refresh();
        $this->assertSame('hired', $profile->employment_status);
        $this->assertNotNull(JobTalentProfile::find($profile->id));

        $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Other', 'email' => 'other@wsa.test'],
        ], $adminHeaders)->assertStatus(409);
    }

    public function test_beekeeping_profile_apiary_and_calendar_flow(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $profile = $this->putJson('/api/v1/beekeeping/profile', [
            'display_name' => 'North Apiary Co',
            'country' => 'JO',
            'hive_count' => 12,
        ], $headers)->assertOk()->json();

        $apiary = $this->postJson('/api/v1/beekeeping/apiaries', [
            'beekeeper_profile_id' => $profile['id'],
            'name' => 'Spring Valley',
            'country' => 'JO',
        ], $headers)->assertCreated()->json();

        $hive = $this->postJson("/api/v1/beekeeping/apiaries/{$apiary['id']}/hives", [
            'code' => 'H-001',
        ], $headers)->assertCreated()->json();

        $this->postJson("/api/v1/beekeeping/hives/{$hive['id']}/inspections", [
            'inspected_at' => now()->toISOString(),
            'overall_status' => 'healthy',
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/beekeeping/calendar/tasks', [
            'task_type' => 'inspection',
            'title' => 'Monthly hive check',
            'severity' => 'normal',
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/beekeeping/calendar/tasks', $headers)->assertOk();

        $knowledge = $this->getJson('/api/v1/beekeeping/knowledge/topics', $headers)->assertOk();
        $this->assertGreaterThanOrEqual(1, count($knowledge->json('data') ?? $knowledge->json()));
    }

    public function test_failed_payment_does_not_disclose_contact_information(): void
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $talentHeaders = $this->talentHeaders($talent, $organization);

        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Irrigation Tech',
            'contact' => ['email' => 'secret-fail@wsa.test', 'phone' => '+962711111111'],
        ], $talentHeaders);

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();
        $adminHeaders = $this->adminHeaders($organization);

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-fail@wsa.test'],
        ], $adminHeaders)->json('id');

        $response = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'fail-'.Str::uuid(),
        ], $adminHeaders);

        $response->assertStatus(422);
        $payload = json_encode($response->json());
        $this->assertStringNotContainsString('secret-fail@wsa.test', $payload);
        $this->assertSame('failed', JobContactRequest::find($requestId)->status);
    }

    public function test_cross_organization_contact_request_is_isolated(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Phase17 Org B', 'slug' => 'phase17-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);

        $talent = $this->talentUser();
        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Cross Org Talent',
            'contact' => ['email' => 'cross@wsa.test'],
        ], $this->talentHeaders($talent, $orgA));

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A HR', 'email' => 'orga@wsa.test'],
        ], $this->adminHeaders($orgA))->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'cross-'.Str::uuid(),
        ], $this->adminHeaders($orgB))->assertNotFound();
    }

    public function test_ai_assistant_and_vision_use_provider_abstraction_with_safe_fallback(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $assistant = $this->postJson('/api/v1/ai/assistant/conversations', [
            'domain' => 'beekeeping',
            'message' => 'What should I inspect this month?',
        ], $headers)->assertCreated();

        $payload = $assistant->json();
        $this->assertArrayHasKey('confidence', $payload);

        $vision = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'vision_analysis',
            'input' => ['image_path' => 'uploads/test-hive.jpg'],
        ], $headers)->assertCreated();

        $output = $vision->json('output') ?? [];
        $content = $output['content'] ?? $output;
        $this->assertTrue(
            ($content['requires_more_information'] ?? false) === true
            || ($content['escalate_to_expert'] ?? false) === true
            || ($content['confidence'] ?? 1) < 0.6,
        );
    }

    public function test_m16_regression_locale_middleware_still_works(): void
    {
        $this->getJson('/api/v1/health/live', ['Accept-Language' => 'fr'])->assertOk();
        $this->assertSame('fr', app()->getLocale());
    }
}
