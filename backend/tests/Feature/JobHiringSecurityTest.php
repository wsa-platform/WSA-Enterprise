<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\JobContactRequest;
use App\Models\JobContactTransaction;
use App\Models\JobEmploymentRecord;
use App\Models\JobSeekerProfile;
use App\Models\JobTalentProfile;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobHiringSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @return array<string, string> */
    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('hiring-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    private function talentUser(string $email = 'hiring-talent@wsa.test'): User
    {
        return User::create([
            'name' => 'Hiring Talent',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }

    /** @return array<string, string> */
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

        $token = $user->createToken('hiring-talent')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /** @return array{0: JobTalentProfile, 1: array<string, string>, 2: Organization} */
    private function seededCandidate(): array
    {
        $organization = Organization::first();
        $talent = $this->talentUser();
        $headers = $this->talentHeaders($talent, $organization);
        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Irrigation Engineer',
            'specialization' => 'Irrigation',
            'employment_status' => 'hired',
            'contact' => [
                'email' => 'locked-candidate@wsa.test',
                'phone' => '+962700000111',
            ],
        ], $headers)->assertOk();

        $profile = JobTalentProfile::where('user_id', $talent->id)->firstOrFail();

        return [$profile, $headers, $organization];
    }

    public function test_candidate_cannot_self_assign_hired_status_on_talent_profile(): void
    {
        [$profile] = $this->seededCandidate();

        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->employment_status);
    }

    public function test_candidate_cannot_modify_job_seeker_application_or_hiring_status(): void
    {
        $organization = Organization::first();
        $user = $this->talentUser('seeker-status@wsa.test');
        $headers = $this->talentHeaders($user, $organization);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Seeker Status Test Name',
            'email' => 'seeker-status@wsa.test',
            'recruitment_status' => JobSeekerProfile::STATUS_HIRED,
            'payment_status' => 'paid',
            'employment_status' => 'hired',
            'user_id' => 999999,
            'organization_id' => 999999,
        ]), $headers)->assertCreated()
            ->assertJsonPath('recruitment_status', JobSeekerProfile::STATUS_NEW);

        $profile = JobSeekerProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $profile->recruitment_status);
    }

    public function test_employer_cannot_read_contact_before_payment(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);

        $search = $this->getJson('/api/v1/jobs/candidates?specialization=Irrigation', $adminHeaders)->assertOk();
        $payload = json_encode($search->json());
        $this->assertStringNotContainsString('locked-candidate@wsa.test', $payload);
        $this->assertStringNotContainsString('+962700000111', $payload);

        $show = $this->getJson("/api/v1/jobs/candidates/{$profile->id}", $adminHeaders)->assertOk();
        $this->assertStringNotContainsString('locked-candidate@wsa.test', json_encode($show->json()));

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-locked@wsa.test'],
        ], $adminHeaders)->assertCreated()->json('id');

        $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)
            ->assertForbidden();
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
        $this->assertSame(0, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
    }

    public function test_fake_payment_status_and_failed_charge_do_not_unlock_contact(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-fake@wsa.test'],
        ], $adminHeaders)->json('id');

        $response = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'fail-'.Str::uuid(),
            'payment_status' => 'paid',
            'status' => 'hired',
        ], $adminHeaders);

        $response->assertStatus(422);
        $this->assertStringNotContainsString('locked-candidate@wsa.test', json_encode($response->json()));
        $this->assertSame('failed', JobContactRequest::find($requestId)->status);
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
        $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)
            ->assertForbidden();
    }

    public function test_hire_without_verified_payment_is_forbidden(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-nopay@wsa.test'],
        ], $adminHeaders)->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)
            ->assertForbidden();
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
    }

    public function test_verified_payment_unlocks_contact_creates_hiring_record_and_marks_hired(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Farm HR', 'email' => 'farm-hr@wsa.test'],
            'job_reference' => 'JOB-SECURE-1',
        ], $adminHeaders)->json('id');

        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);

        $pay = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $this->assertSame('locked-candidate@wsa.test', $pay->json('exchange.candidate_contact.email'));
        $this->assertStringNotContainsString(
            'locked-candidate@wsa.test',
            (string) json_encode($pay->json('transaction')),
        );
        $this->assertNotNull($pay->json('hiring_record.id'));
        $this->assertSame(JobTalentProfile::STATUS_HIRED, $profile->fresh()->employment_status);
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());

        $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)
            ->assertOk()
            ->assertJsonPath('candidate_contact.email', 'locked-candidate@wsa.test')
            ->assertJsonPath('employer_contact.email', 'farm-hr@wsa.test');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)
            ->assertOk();
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
    }

    public function test_notifications_and_job_seeker_status_change_only_after_verified_hiring(): void
    {
        [$profile, $talentHeaders, $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $talent = User::findOrFail($profile->user_id);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Hiring Talent Test Name',
            'email' => $talent->email,
        ]), $talentHeaders)->assertCreated();
        $this->assertSame(JobSeekerProfile::STATUS_NEW, JobSeekerProfile::where('user_id', $talent->id)->value('recruitment_status'));
        $this->assertSame(0, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->count());

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Farm HR', 'email' => 'notify-hr@wsa.test'],
        ], $adminHeaders)->json('id');

        $this->assertSame(0, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->count());

        $key = 'ok-notify-'.Str::uuid();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => $key,
        ], $adminHeaders)->assertOk();

        $notifications = AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->get();
        $this->assertCount(2, $notifications);
        foreach ($notifications as $notification) {
            $encoded = json_encode($notification->data);
            $this->assertStringNotContainsString('locked-candidate@wsa.test', $encoded);
            $this->assertStringNotContainsString('+962700000111', $encoded);
            $this->assertStringNotContainsString('notify-hr@wsa.test', $encoded);
        }

        $this->assertSame(JobTalentProfile::STATUS_HIRED, $profile->fresh()->employment_status);
        $this->assertSame(JobSeekerProfile::STATUS_HIRED, JobSeekerProfile::where('user_id', $talent->id)->value('recruitment_status'));

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => $key,
        ], $adminHeaders)->assertOk();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertOk();

        $this->assertCount(2, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->get());
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
    }

    public function test_contact_endpoint_rejects_forged_completed_payment_without_unlock(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-forged@wsa.test'],
        ], $adminHeaders)->json('id');

        JobContactTransaction::create([
            'contact_request_id' => $requestId,
            'amount' => 49,
            'currency' => 'USD',
            'payment_provider' => 'mock',
            'payment_status' => 'completed',
            'contact_exchange_status' => 'pending',
        ]);

        $forbidden = $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)
            ->assertForbidden();
        $this->assertStringNotContainsString('locked-candidate@wsa.test', json_encode($forbidden->json()));
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
        $this->assertSame(0, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->count());
    }

    public function test_contact_for_another_candidate_stays_locked_after_unrelated_payment(): void
    {
        [$profileA, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);

        $talentB = $this->talentUser('second-candidate@wsa.test');
        $headersB = $this->talentHeaders($talentB, $organization);
        $this->putJson('/api/v1/jobs/talent/me', [
            'professional_name' => 'Second Candidate',
            'specialization' => 'Irrigation',
            'contact' => [
                'email' => 'second-candidate@wsa.test',
                'phone' => '+962700000222',
            ],
        ], $headersB)->assertOk();
        $profileB = JobTalentProfile::where('user_id', $talentB->id)->firstOrFail();

        $requestA = $this->postJson("/api/v1/jobs/candidates/{$profileA->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-a@wsa.test'],
        ], $adminHeaders)->json('id');
        $requestB = $this->postJson("/api/v1/jobs/candidates/{$profileB->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-b@wsa.test'],
        ], $adminHeaders)->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestA}/pay", [
            'idempotency_key' => 'ok-a-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $locked = $this->getJson("/api/v1/jobs/contact-requests/{$requestB}/contact", $adminHeaders)
            ->assertForbidden();
        $this->assertStringNotContainsString('second-candidate@wsa.test', json_encode($locked->json()));
        $this->assertStringNotContainsString('+962700000222', json_encode($locked->json()));
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profileB->fresh()->employment_status);
        $this->assertSame(JobTalentProfile::STATUS_HIRED, $profileA->fresh()->employment_status);
    }

    public function test_wrong_organization_cannot_pay_or_read_contact(): void
    {
        [$profile, , $orgA] = $this->seededCandidate();
        $orgB = Organization::create(['name' => 'Hiring Org B', 'slug' => 'hiring-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A HR', 'email' => 'orga-hr@wsa.test'],
        ], $this->adminHeaders($orgA))->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'wrong-org-'.Str::uuid(),
        ], $this->adminHeaders($orgB))->assertNotFound();
        $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $this->adminHeaders($orgB))
            ->assertNotFound();
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
    }

    public function test_unauthenticated_contact_endpoint_is_rejected(): void
    {
        $this->getJson('/api/v1/jobs/contact-requests/1/contact')->assertUnauthorized();
    }

    public function test_second_employer_cannot_unlock_or_hire_after_first_verified_hire(): void
    {
        [$profile, $talentHeaders, $orgA] = $this->seededCandidate();
        $orgB = Organization::create(['name' => 'Second Employer', 'slug' => 'second-employer']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);

        $headersA = $this->adminHeaders($orgA);
        $headersB = $this->adminHeaders($orgB);

        $requestA = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A', 'email' => 'a-hr@wsa.test'],
            'job_reference' => 'JOB-A',
        ], $headersA)->assertCreated()->json('id');
        $requestB = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org B', 'email' => 'b-hr@wsa.test'],
            'job_reference' => 'JOB-B',
        ], $headersB)->assertCreated()->json('id');

        $this->postJson("/api/v1/jobs/contact-requests/{$requestA}/pay", [
            'idempotency_key' => 'ok-a-'.Str::uuid(),
        ], $headersA)->assertOk();

        $this->assertSame('payment_pending', JobContactRequest::withoutGlobalScopes()->find($requestB)->status);
        $this->postJson("/api/v1/jobs/contact-requests/{$requestB}/pay", [
            'idempotency_key' => 'ok-b-'.Str::uuid(),
        ], $headersB)->assertStatus(409);
        $this->getJson("/api/v1/jobs/contact-requests/{$requestB}/contact", $headersB)->assertForbidden();
        $this->getJson("/api/v1/jobs/contact-requests/{$requestA}/contact", $headersA)
            ->assertOk()
            ->assertJsonPath('candidate_contact.email', 'locked-candidate@wsa.test');
        $this->assertSame('payment_pending', JobContactRequest::withoutGlobalScopes()->find($requestB)->fresh()->status);
        $this->assertSame(JobTalentProfile::STATUS_HIRED, $profile->fresh()->employment_status);
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());

        $mine = $this->getJson('/api/v1/jobs/talent/me/contact-requests', $talentHeaders)->assertOk();
        $this->assertGreaterThanOrEqual(2, count($mine->json('data')));
        $this->assertStringNotContainsString('a-hr@wsa.test', json_encode($mine->json()));
        $this->assertStringNotContainsString('b-hr@wsa.test', json_encode($mine->json()));
    }

    public function test_employer_cv_requires_verified_unlock_and_rejects_path_traversal(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('job-cvs/'.$profile->id.'/cv.pdf', 'cv-bytes');
        $profile->update(['cv_path' => 'job-cvs/'.$profile->id.'/cv.pdf']);

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'cv-hr@wsa.test'],
        ], $adminHeaders)->json('id');

        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertForbidden();

        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-cv-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertOk();

        $profile->update(['cv_path' => '../secrets/id.pdf']);
        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertNotFound();
    }

    public function test_crm_cv_requires_verified_org_unlock_not_private_data_alone(): void
    {
        [$talent, $talentHeaders, $orgA] = $this->seededCandidate();
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Irrigation Engineer Test Name',
            'email' => 'crm-cv-engineer@wsa.test',
        ]), $talentHeaders)->assertCreated();
        $seeker = JobSeekerProfile::where('user_id', $talent->user_id)->firstOrFail();
        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/'.$seeker->id.'/cv.pdf', 'seeker-cv');
        $seeker->update(['cv_path' => 'job-cvs/'.$seeker->id.'/cv.pdf']);

        $headersA = $this->adminHeaders($orgA);
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $headersA)->assertForbidden();
        $this->assertArrayNotHasKey('cv_path', $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersA)->json());
        $this->assertArrayNotHasKey('email', $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersA)->json());
        $this->assertArrayNotHasKey('phone', $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersA)->json());

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$talent->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A HR', 'email' => 'a-cv@wsa.test'],
        ], $headersA)->assertCreated()->json('id');
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-crm-cv-'.Str::uuid(),
        ], $headersA)->assertOk();
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $headersA)->assertOk();

        $orgB = Organization::create(['name' => 'CRM CV Org B', 'slug' => 'crm-cv-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);
        $headersB = $this->adminHeaders($orgB);
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $headersB)->assertForbidden();

        $otherUser = $this->talentUser('crm-cv-other@wsa.test');
        $orgA->members()->syncWithoutDetaching([$otherUser->id => ['role' => 'member', 'is_active' => true]]);
        $memberRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgA->id)
            ->where('slug', 'member')
            ->firstOrFail();
        $otherUser->roles()->sync([$memberRole->id => ['organization_id' => $orgA->id]]);
        $otherHeaders = [
            'Authorization' => 'Bearer '.$otherUser->createToken('crm-cv-other')->plainTextToken,
            'X-Organization-Id' => (string) $orgA->id,
        ];
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Other Seeker Test Name',
            'email' => $otherUser->email,
        ]), $otherHeaders)->assertCreated();
        $otherSeeker = JobSeekerProfile::where('user_id', $otherUser->id)->firstOrFail();
        Storage::disk('local')->put('job-cvs/'.$otherSeeker->id.'/cv.pdf', 'other-cv');
        $otherSeeker->update(['cv_path' => 'job-cvs/'.$otherSeeker->id.'/cv.pdf']);
        $this->get("/api/v1/job-seekers/{$otherSeeker->id}/cv", $headersA)->assertForbidden();
    }

    public function test_crm_email_and_phone_require_verified_org_unlock(): void
    {
        [$talent, $talentHeaders, $orgA] = $this->seededCandidate();
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Irrigation Engineer Test Name',
            'email' => 'crm-private@wsa.test',
            'phone' => '+962700009999',
        ]), $talentHeaders)->assertCreated();
        $seeker = JobSeekerProfile::where('user_id', $talent->user_id)->firstOrFail();
        $headersA = $this->adminHeaders($orgA);

        $before = $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersA)->assertOk();
        $this->assertSame($seeker->id, $before->json('id'));
        $this->assertSame('Irrigation Engineer Test Name', $before->json('full_name'));
        $this->assertArrayNotHasKey('email', $before->json());
        $this->assertArrayNotHasKey('phone', $before->json());
        $this->assertArrayNotHasKey('address', $before->json());
        $this->assertArrayNotHasKey('email', $before->json('user') ?? []);
        $this->assertStringNotContainsString('crm-private@wsa.test', (string) json_encode($before->json()));
        $this->assertStringNotContainsString('+962700009999', (string) json_encode($before->json()));

        $list = $this->getJson('/api/v1/job-seekers?per_page=100', $headersA)->assertOk();
        $row = collect($list->json('data'))->firstWhere('id', $seeker->id);
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('email', $row);
        $this->assertArrayNotHasKey('phone', $row);
        $this->assertArrayNotHasKey('email', $row['user'] ?? []);

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$talent->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A HR', 'email' => 'a-contact@wsa.test'],
        ], $headersA)->assertCreated()->json('id');
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-crm-contact-'.Str::uuid(),
        ], $headersA)->assertOk();

        $after = $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersA)->assertOk();
        $this->assertSame('crm-private@wsa.test', $after->json('email'));
        $this->assertSame('+962700009999', $after->json('phone'));
        $this->assertArrayNotHasKey('email', $after->json('user') ?? []);

        $orgB = Organization::create(['name' => 'CRM Contact Org B', 'slug' => 'crm-contact-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);
        $headersB = $this->adminHeaders($orgB);
        $otherOrg = $this->getJson("/api/v1/job-seekers/{$seeker->id}", $headersB)->assertOk();
        $this->assertArrayNotHasKey('email', $otherOrg->json());
        $this->assertArrayNotHasKey('phone', $otherOrg->json());
        $this->assertStringNotContainsString('crm-private@wsa.test', (string) json_encode($otherOrg->json()));
        $this->assertStringNotContainsString('+962700009999', (string) json_encode($otherOrg->json()));

        $otherUser = $this->talentUser('crm-contact-other@wsa.test');
        $orgA->members()->syncWithoutDetaching([$otherUser->id => ['role' => 'member', 'is_active' => true]]);
        $memberRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgA->id)
            ->where('slug', 'member')
            ->firstOrFail();
        $otherUser->roles()->sync([$memberRole->id => ['organization_id' => $orgA->id]]);
        $otherHeaders = [
            'Authorization' => 'Bearer '.$otherUser->createToken('crm-contact-other')->plainTextToken,
            'X-Organization-Id' => (string) $orgA->id,
        ];
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Other Contact Seeker Name',
            'email' => 'other-private@wsa.test',
            'phone' => '+962700008888',
        ]), $otherHeaders)->assertCreated();
        $otherSeeker = JobSeekerProfile::where('user_id', $otherUser->id)->firstOrFail();
        $idor = $this->getJson("/api/v1/job-seekers/{$otherSeeker->id}", $headersA)->assertOk();
        $this->assertArrayNotHasKey('email', $idor->json());
        $this->assertArrayNotHasKey('phone', $idor->json());
        $this->assertStringNotContainsString('crm-private@wsa.test', (string) json_encode($idor->json()));
        $this->assertStringNotContainsString('other-private@wsa.test', (string) json_encode($idor->json()));
        $this->assertStringNotContainsString('+962700009999', (string) json_encode($idor->json()));
        $this->assertStringNotContainsString('+962700008888', (string) json_encode($idor->json()));
    }

    public function test_hire_ignores_client_transaction_id_and_returns_shaped_payload(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-hire-shape@wsa.test'],
        ], $adminHeaders)->json('id');
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-shape-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $hire = $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [
            'transaction_id' => 999999,
            'payment_status' => 'completed',
            'contact_exchange_status' => 'completed',
        ], $adminHeaders)->assertOk();

        $this->assertNotNull($hire->json('id'));
        $this->assertSame($profile->id, $hire->json('talent_profile_id'));
        $this->assertSame(JobTalentProfile::STATUS_HIRED, $hire->json('employment_status'));
        $this->assertArrayNotHasKey('organization_id', $hire->json());
        $this->assertArrayNotHasKey('notes', $hire->json());
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
    }

    public function test_wrong_organization_cannot_download_unlocked_cv_or_reuse_idempotency_key(): void
    {
        [$profile, $talentHeaders, $orgA] = $this->seededCandidate();
        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/'.$profile->id.'/cv.pdf', 'talent-cv');
        $profile->update(['cv_path' => 'job-cvs/'.$profile->id.'/cv.pdf']);

        $headersA = $this->adminHeaders($orgA);
        $orgB = Organization::create(['name' => 'CV Isolation Org B', 'slug' => 'cv-isolation-org-b']);
        $admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $orgB->members()->syncWithoutDetaching([$admin->id => ['role' => 'admin', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $adminRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $orgB->id)
            ->where('slug', 'admin')
            ->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['organization_id' => $orgB->id]]);
        $headersB = $this->adminHeaders($orgB);

        $requestA = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org A', 'email' => 'a-cv-unlock@wsa.test'],
        ], $headersA)->json('id');
        $requestB = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'Org B', 'email' => 'b-cv-unlock@wsa.test'],
        ], $headersB)->assertCreated()->json('id');
        $key = 'ok-shared-'.Str::uuid();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestA}/pay", [
            'idempotency_key' => $key,
        ], $headersA)->assertOk();
        $this->get("/api/v1/jobs/contact-requests/{$requestA}/cv", $headersA)->assertOk();
        $this->get('/api/v1/jobs/talent/me/cv', $talentHeaders)->assertOk();

        $this->get("/api/v1/jobs/contact-requests/{$requestA}/cv", $headersB)->assertNotFound();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestA}/hire", [], $headersB)->assertNotFound();

        $reuse = $this->postJson("/api/v1/jobs/contact-requests/{$requestB}/pay", [
            'idempotency_key' => $key,
        ], $headersB);
        $this->assertContains($reuse->status(), [409, 422]);
        $this->assertStringNotContainsString('locked-candidate@wsa.test', (string) json_encode($reuse->json()));
        $this->get("/api/v1/jobs/contact-requests/{$requestB}/cv", $headersB)->assertForbidden();
    }

    public function test_cv_download_rejects_foreign_prefix_and_arbitrary_paths(): void
    {
        [$profile, $talentHeaders, $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/999999/secret.pdf', 'secret');
        Storage::disk('local')->put('etc/passwd', 'root');
        $profile->update(['cv_path' => 'job-cvs/999999/secret.pdf']);
        $this->get('/api/v1/jobs/talent/me/cv', $talentHeaders)->assertNotFound();

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'path-hr@wsa.test'],
        ], $adminHeaders)->json('id');
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-path-'.Str::uuid(),
        ], $adminHeaders)->assertOk();
        $denied = $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertNotFound();
        $this->assertStringNotContainsString('secret.pdf', (string) json_encode($denied->json()));

        $profile->update(['cv_path' => 'etc/passwd']);
        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertNotFound();
        $this->get('/api/v1/jobs/talent/me/cv', $talentHeaders)->assertNotFound();
    }

    /**
     * Sequential two-employer hire protection. A true parallel HTTP race is not executed here:
     * this Windows PHPUnit environment does not provide pcntl_fork / concurrent HTTP workers.
     */
    public function test_sequential_second_hire_attempt_is_rejected_after_lock(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'seq-hr@wsa.test'],
        ], $adminHeaders)->json('id');
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-seq-'.Str::uuid(),
        ], $adminHeaders)->assertOk();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertOk();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertOk();
        $this->assertSame(1, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
    }

    public function test_forged_completed_exchange_without_hiring_record_does_not_unlock(): void
    {
        [$profile, , $organization] = $this->seededCandidate();
        $adminHeaders = $this->adminHeaders($organization);
        $requestId = $this->postJson("/api/v1/jobs/candidates/{$profile->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'hr-forged-both@wsa.test'],
        ], $adminHeaders)->json('id');

        JobContactTransaction::create([
            'contact_request_id' => $requestId,
            'amount' => 49,
            'currency' => 'USD',
            'payment_provider' => 'mock',
            'payment_status' => 'completed',
            'contact_exchange_status' => 'completed',
            'exchanged_at' => now(),
        ]);

        $forbidden = $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)
            ->assertForbidden();
        $this->assertStringNotContainsString('locked-candidate@wsa.test', (string) json_encode($forbidden->json()));
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertForbidden();
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $profile->fresh()->employment_status);
        $this->assertSame(0, JobEmploymentRecord::where('talent_profile_id', $profile->id)->count());
        $this->assertSame(0, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->count());
    }

    public function test_crm_hired_status_does_not_bypass_marketplace_payment_or_unlock(): void
    {
        [$talent, $talentHeaders, $organization] = $this->seededCandidate();
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Irrigation Engineer Test Name',
            'email' => 'crm-hire-bypass@wsa.test',
            'phone' => '+962700001234',
        ]), $talentHeaders)->assertCreated();
        $seeker = JobSeekerProfile::where('user_id', $talent->user_id)->firstOrFail();
        $adminHeaders = $this->adminHeaders($organization);

        $this->patchJson("/api/v1/job-seekers/{$seeker->id}/status", [
            'status' => JobSeekerProfile::STATUS_HIRED,
        ], $talentHeaders)->assertForbidden();

        foreach ([
            JobSeekerProfile::STATUS_UNDER_REVIEW,
            JobSeekerProfile::STATUS_QUALIFIED,
            JobSeekerProfile::STATUS_INTERVIEW,
            JobSeekerProfile::STATUS_ACCEPTED,
            JobSeekerProfile::STATUS_HIRED,
        ] as $status) {
            $this->patchJson("/api/v1/job-seekers/{$seeker->id}/status", [
                'status' => $status,
            ], $adminHeaders)->assertOk()->assertJsonPath('recruitment_status', $status);
        }

        $crm = $this->getJson("/api/v1/job-seekers/{$seeker->id}", $adminHeaders)->assertOk();
        $this->assertSame(JobSeekerProfile::STATUS_HIRED, $crm->json('recruitment_status'));
        $this->assertArrayNotHasKey('email', $crm->json());
        $this->assertArrayNotHasKey('phone', $crm->json());
        $this->assertStringNotContainsString('crm-hire-bypass@wsa.test', (string) json_encode($crm->json()));
        $this->assertStringNotContainsString('+962700001234', (string) json_encode($crm->json()));
        $this->assertSame(JobTalentProfile::STATUS_AVAILABLE, $talent->fresh()->employment_status);
        $this->assertSame(0, JobEmploymentRecord::where('talent_profile_id', $talent->id)->count());
        $this->assertSame(0, AppNotification::withoutGlobalScopes()->where('type', 'jobs.hiring.completed')->count());
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $adminHeaders)->assertForbidden();

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$talent->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'crm-still-pays@wsa.test'],
        ], $adminHeaders)->assertCreated()->json('id');
        $this->getJson("/api/v1/jobs/contact-requests/{$requestId}/contact", $adminHeaders)->assertForbidden();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/hire", [], $adminHeaders)->assertForbidden();
    }

    public function test_job_seeker_and_talent_cv_stores_stay_isolated(): void
    {
        [$talent, $talentHeaders, $organization] = $this->seededCandidate();
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Irrigation Engineer Test Name',
            'email' => 'isolated-engineer@wsa.test',
        ]), $talentHeaders)->assertCreated();
        $seeker = JobSeekerProfile::where('user_id', $talent->user_id)->firstOrFail();
        $this->assertNotSame($talent->id, $seeker->id);

        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/'.$talent->id.'/talent.pdf', 'talent-cv-bytes');
        Storage::disk('local')->put('job-cvs/'.$seeker->id.'/seeker.pdf', 'seeker-cv-bytes');
        $talent->update(['cv_path' => 'job-cvs/'.$talent->id.'/talent.pdf']);
        $seeker->update(['cv_path' => 'job-cvs/'.$seeker->id.'/seeker.pdf']);

        $adminHeaders = $this->adminHeaders($organization);
        $this->get('/api/v1/jobs/talent/me/cv', $talentHeaders)->assertOk();
        $this->get('/api/v1/job-seekers/me/cv', $talentHeaders)->assertOk();
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $adminHeaders)->assertForbidden();

        $requestId = $this->postJson("/api/v1/jobs/candidates/{$talent->id}/contact-requests", [
            'employer_contact' => ['name' => 'HR', 'email' => 'cv-iso@wsa.test'],
        ], $adminHeaders)->json('id');
        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertForbidden();
        $this->postJson("/api/v1/jobs/contact-requests/{$requestId}/pay", [
            'idempotency_key' => 'ok-cv-iso-'.Str::uuid(),
        ], $adminHeaders)->assertOk();

        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertOk();
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $adminHeaders)->assertOk();

        $seeker->update(['cv_path' => 'job-cvs/'.$talent->id.'/talent.pdf']);
        $this->get("/api/v1/job-seekers/{$seeker->id}/cv", $adminHeaders)->assertNotFound();
        $talent->update(['cv_path' => 'job-cvs/'.$seeker->id.'/seeker.pdf']);
        $this->get("/api/v1/jobs/contact-requests/{$requestId}/cv", $adminHeaders)->assertNotFound();
    }
}
