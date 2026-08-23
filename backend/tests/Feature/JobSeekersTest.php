<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JobSeekerProfile;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobSeekersTest extends TestCase
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
        $token = $admin->createToken('job-seekers-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    /** @return array<string, string> */
    private function memberHeaders(User $user, Organization $organization): array
    {
        $token = $user->createToken('job-seekers-member')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    private function attachViewer(User $user, Organization $organization): void
    {
        $organization->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'is_active' => true],
        ]);
        $role = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'viewer')
            ->firstOrFail();
        $user->roles()->sync([$role->id => ['organization_id' => $organization->id]]);
        app(PermissionService::class)->forget($user, $organization->id);
    }

    public function test_admin_can_list_job_seekers(): void
    {
        $response = $this->getJson('/api/v1/job-seekers', $this->adminHeaders());
        $response->assertOk()->assertJsonStructure(['data', 'total']);
        $this->assertGreaterThanOrEqual(1, $response->json('total'));

        $this->getJson('/api/v1/job-seekers?status=not-a-status', $this->adminHeaders())
            ->assertUnprocessable();
    }

    public function test_member_without_jobs_view_is_denied(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'No Jobs',
            'email' => 'nojobs@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);

        $this->getJson('/api/v1/job-seekers', $this->memberHeaders($user, $org))
            ->assertForbidden();
    }

    public function test_user_can_crud_own_profile(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Seeker User',
            'email' => 'myseeker@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Seeker User Test Name',
            'email' => 'myseeker@wsa.test',
            'specialization' => 'Agronomist',
            'country' => 'SA',
            'city' => 'Riyadh',
            'skills' => ['irrigation'],
            'experience' => [['title' => 'Farm lead']],
            'certifications' => [['name' => 'GAP']],
            'languages' => ['ar'],
        ]), $headers)->assertCreated()
            ->assertJsonPath('specialization', 'Agronomist')
            ->assertJsonPath('recruitment_status', JobSeekerProfile::STATUS_NEW);

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('specialization', 'Agronomist')
            ->assertJsonPath('email', 'myseeker@wsa.test');

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Seeker User Test Name',
            'email' => 'myseeker@wsa.test',
            'specialization' => 'Irrigation specialist',
        ]), $headers)->assertOk()
            ->assertJsonPath('specialization', 'Irrigation specialist');

        $this->assertSame(1, JobSeekerProfile::where('user_id', $user->id)->count());
    }

    public function test_seeker_cannot_mass_assign_owner_or_status(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Mass Assign',
            'email' => 'massassign@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $other = User::where('email', 'admin@wsa.test')->first();
        $headers = $this->memberHeaders($user, $org);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Mass Assign Test Name',
            'email' => 'massassign@wsa.test',
            'user_id' => $other->id,
            'owner_user_id' => $other->id,
            'recruitment_status' => JobSeekerProfile::STATUS_HIRED,
            'is_active' => false,
        ]), $headers)->assertCreated();

        $profile = JobSeekerProfile::where('email', 'massassign@wsa.test')->first()
            ?? JobSeekerProfile::where('full_name', 'Mass Assign Test Name')->latest('id')->first();
        $this->assertNotNull($profile);
        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $profile->recruitment_status);
        $this->assertTrue($profile->is_active);
    }

    public function test_seeker_cannot_access_another_profile_as_owner(): void
    {
        $org = Organization::first();
        $owner = JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_NEW)->first();
        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$intruder->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($intruder, $org);

        $this->getJson('/api/v1/job-seekers/me', $headers)->assertNotFound();
        $this->getJson("/api/v1/job-seekers/{$owner->id}", $headers)->assertForbidden();
        $this->patchJson("/api/v1/job-seekers/{$owner->id}", [
            'specialization' => 'Hacked',
        ], $headers)->assertForbidden();
        $this->patchJson("/api/v1/job-seekers/{$owner->id}/status", [
            'status' => JobSeekerProfile::STATUS_UNDER_REVIEW,
        ], $headers)->assertForbidden();
        $this->getJson("/api/v1/job-seekers/{$owner->id}/notes", $headers)->assertForbidden();
    }

    public function test_viewer_cannot_see_private_contact_or_cv_path(): void
    {
        $org = Organization::first();
        $viewer = User::create([
            'name' => 'Viewer',
            'email' => 'jobs-viewer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachViewer($viewer, $org);
        $profile = JobSeekerProfile::first();
        $viewerHeaders = $this->memberHeaders($viewer, $org);
        $beforeCv = $this->getJson("/api/v1/job-seekers/{$profile->id}", $viewerHeaders)
            ->assertOk();
        $this->assertArrayNotHasKey('cv_path', $beforeCv->json());
        $publicCompleteness = $beforeCv->json('completeness_percent');
        $profile->cv_path = 'resumes/private-seeded.pdf';
        $profile->desired_salary = 15000;
        $profile->salary_currency = 'SAR';
        $profile->email = 'hidden-recruiter@wsa.test';
        $profile->phone = '+966500009998';
        $profile->save();
        $profile->refresh();

        $this->assertSame($publicCompleteness, $profile->completenessPercent(false));
        $this->assertGreaterThan($publicCompleteness, $profile->completenessPercent(true));

        $response = $this->getJson("/api/v1/job-seekers/{$profile->id}", $viewerHeaders)
            ->assertOk();
        $this->assertSame($publicCompleteness, $response->json('completeness_percent'));
        $this->assertArrayNotHasKey('email', $response->json());
        $this->assertArrayNotHasKey('phone', $response->json());
        $this->assertArrayNotHasKey('address', $response->json());
        $this->assertArrayNotHasKey('cv_path', $response->json());
        $this->assertArrayNotHasKey('desired_salary', $response->json());
        $this->assertArrayNotHasKey('salary_currency', $response->json());
        $this->assertArrayNotHasKey('email', $response->json('user') ?? []);
        $this->assertSame($profile->completenessPercent(false), $response->json('completeness_percent'));

        $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('desired_salary', '15000.00')
            ->assertJsonPath('salary_currency', 'SAR')
            ->assertJsonPath('completeness_percent', $profile->completenessPercent(false));
        $adminPrivate = $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->adminHeaders())->json();
        $this->assertArrayNotHasKey('cv_path', $adminPrivate);
        $this->assertArrayNotHasKey('has_cv', $adminPrivate);
        $this->assertArrayNotHasKey('cv_filename', $adminPrivate);
        $this->assertArrayNotHasKey('email', $adminPrivate);
        $this->assertArrayNotHasKey('phone', $adminPrivate);
        $this->assertArrayNotHasKey('address', $adminPrivate);
        $this->assertArrayNotHasKey('email', $adminPrivate['user'] ?? []);
    }

    public function test_public_completeness_ignores_private_contact_cv_and_salary(): void
    {
        $publicAttrs = [
            'full_name' => 'CV Privacy',
            'country' => 'SA',
            'city' => 'Riyadh',
            'specialization' => 'Agronomist',
        ];
        $privateAttrs = [
            'email' => 'cv-privacy@wsa.test',
            'phone' => '+966500009999',
            'cv_path' => 'resumes/secret.pdf',
            'desired_salary' => 18000,
            'salary_currency' => 'SAR',
        ];
        $publicOnly = new JobSeekerProfile($publicAttrs);
        $withPrivate = new JobSeekerProfile($publicAttrs + $privateAttrs);

        $this->assertSame(
            $publicOnly->completenessPercent(false),
            $withPrivate->completenessPercent(false)
        );
        $this->assertGreaterThan(
            $withPrivate->completenessPercent(false),
            $withPrivate->completenessPercent(true)
        );
        $this->assertSame(
            (new JobSeekerProfile($publicAttrs + ['email' => 'a@b.test', 'phone' => '+966500000000']))->completenessPercent(false),
            $publicOnly->completenessPercent(false)
        );
        $this->assertSame(
            (new JobSeekerProfile($publicAttrs + ['desired_salary' => 99999]))->completenessPercent(true),
            $publicOnly->completenessPercent(true)
        );

        $org = Organization::first();
        $user = User::create([
            'name' => 'CV Privacy',
            'email' => 'cv-privacy-owner@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'CV Privacy Test Name',
            'email' => 'cv-privacy-owner@wsa.test',
            'specialization' => 'Agronomist',
        ]), $headers)->assertCreated();

        $viewer = User::create([
            'name' => 'CV Privacy Viewer',
            'email' => 'cv-privacy-viewer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachViewer($viewer, $org);
        $profile = JobSeekerProfile::where('user_id', $user->id)->firstOrFail();
        $viewerHeaders = $this->memberHeaders($viewer, $org);
        $before = $this->getJson("/api/v1/job-seekers/{$profile->id}", $viewerHeaders)
            ->assertOk()
            ->json('completeness_percent');

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'CV Privacy Test Name',
            'email' => 'private-cv@wsa.test',
            'specialization' => 'Agronomist',
            'phone' => '+966500009997',
            'cv_path' => 'resumes/secret.pdf',
            'desired_salary' => 18000,
            'salary_currency' => 'SAR',
        ]), $headers)->assertOk();

        $after = $this->getJson("/api/v1/job-seekers/{$profile->id}", $viewerHeaders)
            ->assertOk();
        $this->assertArrayNotHasKey('email', $after->json());
        $this->assertArrayNotHasKey('phone', $after->json());
        $this->assertArrayNotHasKey('cv_path', $after->json());
        $this->assertArrayNotHasKey('desired_salary', $after->json());
        $this->assertArrayNotHasKey('salary_currency', $after->json());
        $this->assertSame($before, $after->json('completeness_percent'));
        $row = collect(
            $this->getJson('/api/v1/job-seekers?per_page=100', $viewerHeaders)
                ->assertOk()
                ->json('data')
        )->firstWhere('id', $profile->id);
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('email', $row);
        $this->assertArrayNotHasKey('phone', $row);
        $this->assertArrayNotHasKey('cv_path', $row);
        $this->assertArrayNotHasKey('desired_salary', $row);
        $this->assertSame($before, $row['completeness_percent']);
    }

    public function test_admin_can_update_status_and_private_data_hidden_without_permission(): void
    {
        $profile = JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_NEW)->first();
        $this->assertNotNull($profile);

        $member = User::where('email', 'member@wsa.test')->first();
        $org = Organization::first();
        $memberHeaders = $this->memberHeaders($member, $org);

        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => JobSeekerProfile::STATUS_UNDER_REVIEW,
            'notes' => 'Phone screen passed',
        ], $memberHeaders)->assertForbidden();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => JobSeekerProfile::STATUS_UNDER_REVIEW,
            'notes' => 'Phone screen passed',
        ], $this->adminHeaders())->assertOk()
            ->assertJsonPath('recruitment_status', JobSeekerProfile::STATUS_UNDER_REVIEW);

        $this->assertTrue(
            AuditLog::withoutGlobalScopes()->where('action', 'recruitment.status_changed')->exists()
        );

        $this->getJson("/api/v1/job-seekers/{$profile->id}/history", $this->adminHeaders())
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.notes', 'Phone screen passed');
    }

    public function test_viewer_history_omits_notes_and_member_is_forbidden(): void
    {
        $org = Organization::first();
        $profile = JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_NEW)->first();
        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => JobSeekerProfile::STATUS_UNDER_REVIEW,
            'notes' => 'Confidential recruiter note',
        ], $this->adminHeaders())->assertOk();

        $viewer = User::create([
            'name' => 'History Viewer',
            'email' => 'history-viewer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachViewer($viewer, $org);
        $history = $this->getJson("/api/v1/job-seekers/{$profile->id}/history", $this->memberHeaders($viewer, $org))
            ->assertOk();
        $this->assertNotEmpty($history->json('data'));
        $this->assertArrayNotHasKey('notes', $history->json('data.0'));

        $member = User::where('email', 'member@wsa.test')->first();
        $this->getJson("/api/v1/job-seekers/{$profile->id}/history", $this->memberHeaders($member, $org))
            ->assertForbidden();

        $intruder = User::create([
            'name' => 'History Intruder',
            'email' => 'history-intruder@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$intruder->id => ['role' => 'member']]);
        $this->getJson("/api/v1/job-seekers/{$profile->id}/history", $this->memberHeaders($intruder, $org))
            ->assertForbidden();
    }

    public function test_invalid_status_and_transitions_are_rejected(): void
    {
        $profile = JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_NEW)->first();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => 'not-a-status',
        ], $this->adminHeaders())->assertUnprocessable();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => JobSeekerProfile::STATUS_HIRED,
        ], $this->adminHeaders())->assertUnprocessable();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}", [
            'user_id' => 999999,
            'recruitment_status' => JobSeekerProfile::STATUS_HIRED,
            'specialization' => 'Updated by admin',
        ], $this->adminHeaders())->assertOk()
            ->assertJsonPath('specialization', 'Updated by admin')
            ->assertJsonPath('recruitment_status', JobSeekerProfile::STATUS_NEW);

        $this->assertSame($profile->user_id, $profile->fresh()->user_id);
    }

    public function test_hired_status_is_terminal(): void
    {
        $profile = JobSeekerProfile::where('recruitment_status', JobSeekerProfile::STATUS_QUALIFIED)->first()
            ?? JobSeekerProfile::first();
        $profile->recruitment_status = JobSeekerProfile::STATUS_HIRED;
        $profile->save();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}/status", [
            'status' => JobSeekerProfile::STATUS_REJECTED,
        ], $this->adminHeaders())->assertUnprocessable();
    }

    public function test_admin_can_add_and_list_recruiter_notes(): void
    {
        $profile = JobSeekerProfile::first();
        $this->postJson("/api/v1/job-seekers/{$profile->id}/notes", [
            'body' => 'Strong candidate for irrigation role',
            'is_private' => true,
        ], $this->adminHeaders())->assertCreated()
            ->assertJsonPath('body', 'Strong candidate for irrigation role');

        $this->assertTrue(
            AuditLog::withoutGlobalScopes()->where('action', 'recruitment.note_added')->exists()
        );

        $this->getJson("/api/v1/job-seekers/{$profile->id}/notes", $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Strong candidate for irrigation role');
    }

    public function test_unknown_profile_and_malformed_payloads_are_rejected(): void
    {
        $this->getJson('/api/v1/job-seekers/999999', $this->adminHeaders())->assertNotFound();
        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => ['not-a-string'],
        ], $this->adminHeaders())->assertUnprocessable();
        $this->postJson('/api/v1/job-seekers/'.JobSeekerProfile::first()->id.'/notes', [
            'body' => '',
        ], $this->adminHeaders())->assertUnprocessable();
    }

    public function test_recruitment_report_endpoint(): void
    {
        $this->getJson('/api/v1/reports/recruitment?days=30', $this->adminHeaders())
            ->assertOk()
            ->assertJsonStructure(['summary' => ['total_profiles', 'hired_total'], 'by_status']);

        $member = User::where('email', 'member@wsa.test')->first();
        $this->getJson('/api/v1/reports/recruitment?days=30', $this->memberHeaders($member, Organization::first()))
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_application_without_deleting_the_user(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Deactivate Me',
            'email' => 'deactivate@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Deactivate Me Test Name',
            'email' => 'deactivate@wsa.test',
        ]), $headers)->assertCreated();

        $other = User::create([
            'name' => 'Keep Profile',
            'email' => 'keep-profile@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$other->id => ['role' => 'member']]);
        $otherHeaders = $this->memberHeaders($other, $org);
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Keep Profile User Name',
            'email' => 'keep-profile@wsa.test',
        ]), $otherHeaders)->assertCreated();
        $otherProfileId = JobSeekerProfile::where('user_id', $other->id)->value('id');

        $this->deleteJson('/api/v1/job-seekers/me')->assertUnauthorized();

        $this->deleteJson('/api/v1/job-seekers/me', [], $headers)
            ->assertOk()
            ->assertJsonPath('message', __('jobs.application_deleted'));

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertNotFound();

        $this->assertTrue(User::where('id', $user->id)->exists());
        $this->assertTrue(JobSeekerProfile::withTrashed()->where('user_id', $user->id)->exists());
        $this->assertFalse(JobSeekerProfile::where('user_id', $user->id)->exists());
        $this->assertTrue(User::where('id', $other->id)->exists());
        $this->assertTrue(JobSeekerProfile::where('id', $otherProfileId)->exists());

        $intruder = User::create([
            'name' => 'Other Seeker',
            'email' => 'other-deactivate@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$intruder->id => ['role' => 'member']]);
        $this->deleteJson('/api/v1/job-seekers/me', [], $this->memberHeaders($intruder, $org))
            ->assertNotFound();
        $this->assertTrue(User::where('id', $user->id)->exists());
        $this->assertTrue(JobSeekerProfile::withTrashed()->where('user_id', $user->id)->exists());
    }

    public function test_nested_json_is_validated_and_unknown_keys_are_stripped(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Nested User',
            'email' => 'nested@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Nested User',
            'experience' => 'not-an-array',
        ], $headers)->assertUnprocessable();

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Nested User',
            'experience' => ['Farm lead'],
        ], $headers)->assertUnprocessable();

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Nested User',
            'education' => [['institution' => 'KSU', 'year' => 'not-a-year']],
        ], $headers)->assertUnprocessable();

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Nested User',
            'skills' => [123],
        ], $headers)->assertUnprocessable();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Nested User Test Name',
            'email' => 'nested@wsa.test',
            'experience' => [[
                'title' => 'Farm lead',
                'company' => 'WSA Farms',
                'start_date' => '2020-01-01',
                'end_date' => '2022-06-01',
                'current' => false,
                'years' => 3,
                'ssn' => 'should-not-persist',
            ]],
            'certifications' => [['name' => 'GAP']],
            'languages' => ['ar'],
            'years_of_experience' => 5,
        ]), $headers)->assertCreated();

        Storage::fake('local');
        $document = \Illuminate\Http\UploadedFile::fake()->create('degree.pdf', 120, 'application/pdf');
        $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document], $headers)->assertOk();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Nested User Test Name',
            'email' => 'nested@wsa.test',
            'experience' => [[
                'title' => 'Farm lead',
                'company' => 'WSA Farms',
                'start_date' => '2020-01-01',
                'end_date' => '2022-06-01',
                'current' => false,
                'years' => 3,
            ]],
            'education' => [['institution' => 'KSU', 'degree' => 'BSc', 'country' => 'SA']],
            'certifications' => [['name' => 'GAP']],
            'languages' => ['ar'],
            'years_of_experience' => 5,
        ]), $headers)->assertOk();

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('experience.0.title', 'Farm lead')
            ->assertJsonPath('experience.0.start_date', '2020-01-01')
            ->assertJsonPath('education.0.country', 'SA')
            ->assertJsonPath('years_of_experience', 5)
            ->assertJsonMissing(['ssn' => 'should-not-persist']);

        $this->assertArrayNotHasKey('recruitment_status', $this->getJson('/api/v1/job-seekers/me', $headers)->json('experience.0'));
    }

    public function test_candidate_cannot_set_completeness_or_nested_system_fields(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'System Fields',
            'email' => 'system-fields@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);

        $created = $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'System Fields Test Name',
            'email' => 'system-fields@wsa.test',
            'phone' => '+962700000000',
            'country' => 'JO',
            'city' => 'Amman',
            'completeness_percent' => 100,
            'cv_path' => '../secrets/id.pdf',
            'photo_path' => '../secrets/photo.jpg',
            'primary_qualification_path' => '../secrets/degree.pdf',
            'employment_status' => 'hired',
            'payment_status' => 'paid',
            'tenant_id' => 99,
            'experience' => [[
                'title' => 'Lead',
                'recruitment_status' => 'hired',
                'payment_status' => 'paid',
            ]],
        ]), $headers)->assertCreated();

        $this->assertNotEquals(100, $created->json('completeness_percent'));
        $this->assertNull($created->json('cv_path'));
        $this->assertArrayNotHasKey('photo_path', $created->json());
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $created->json('recruitment_status'));
        $this->assertArrayNotHasKey('recruitment_status', $created->json('experience.0') ?? []);
        $this->assertArrayNotHasKey('payment_status', $created->json('experience.0') ?? []);

        $profile = JobSeekerProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($profile->cv_path);
        $this->assertNull($profile->photo_path);
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $profile->recruitment_status);
    }

    public function test_only_owner_can_download_own_cv(): void
    {
        $org = Organization::first();
        $owner = User::create([
            'name' => 'CV Owner',
            'email' => 'cv-owner@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $intruder = User::create([
            'name' => 'CV Intruder',
            'email' => 'cv-intruder@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([
            $owner->id => ['role' => 'member'],
            $intruder->id => ['role' => 'member'],
        ]);
        $ownerHeaders = $this->memberHeaders($owner, $org);
        $intruderHeaders = $this->memberHeaders($intruder, $org);

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'CV Owner Test Name',
            'email' => 'cv-owner@wsa.test',
        ]), $ownerHeaders)->assertCreated();
        $profile = JobSeekerProfile::where('user_id', $owner->id)->firstOrFail();
        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/'.$profile->id.'/cv.pdf', 'cv-bytes');
        $profile->update(['cv_path' => 'job-cvs/'.$profile->id.'/cv.pdf']);

        $this->get('/api/v1/job-seekers/me/cv', $ownerHeaders)->assertOk();
        $this->get('/api/v1/job-seekers/me/cv', $intruderHeaders)->assertNotFound();
        $this->getJson('/api/v1/job-seekers/me', $ownerHeaders)
            ->assertOk()
            ->assertJsonPath('has_cv', true)
            ->assertJsonPath('cv_filename', 'cv.pdf');
        $this->assertArrayNotHasKey('cv_path', $this->getJson('/api/v1/job-seekers/me', $ownerHeaders)->json());

        $profile->update(['cv_path' => '../secrets/passwd']);
        $this->get('/api/v1/job-seekers/me/cv', $ownerHeaders)->assertNotFound();
    }

    public function test_recruiter_crm_cv_is_permission_scoped_and_omits_filesystem_path(): void
    {
        $orgA = Organization::first();
        $owner = User::create([
            'name' => 'CRM CV Owner',
            'email' => 'crm-cv-owner@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $orgA->members()->syncWithoutDetaching([$owner->id => ['role' => 'member']]);
        $ownerHeaders = $this->memberHeaders($owner, $orgA);
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'CRM CV Owner Test Name',
            'email' => 'crm-cv-owner@wsa.test',
        ]), $ownerHeaders)->assertCreated();
        $profile = JobSeekerProfile::where('user_id', $owner->id)->firstOrFail();
        Storage::fake('local');
        Storage::disk('local')->put('job-cvs/'.$profile->id.'/cv.pdf', 'crm-cv-bytes');
        $profile->update(['cv_path' => 'job-cvs/'.$profile->id.'/cv.pdf']);

        $adminJson = $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->adminHeaders($orgA))
            ->assertOk();
        $this->assertArrayNotHasKey('cv_path', $adminJson->json());
        $this->assertArrayNotHasKey('has_cv', $adminJson->json());
        $this->assertArrayNotHasKey('cv_filename', $adminJson->json());
        $this->get("/api/v1/job-seekers/{$profile->id}/cv", $this->adminHeaders($orgA))->assertForbidden();

        $viewer = User::create([
            'name' => 'CRM CV Viewer',
            'email' => 'crm-cv-viewer@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $this->attachViewer($viewer, $orgA);
        $this->get("/api/v1/job-seekers/{$profile->id}/cv", $this->memberHeaders($viewer, $orgA))->assertForbidden();
        $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->memberHeaders($viewer, $orgA))
            ->assertOk();
        $this->assertArrayNotHasKey('cv_path', $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->memberHeaders($viewer, $orgA))->json());
        $this->assertArrayNotHasKey('email', $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->memberHeaders($viewer, $orgA))->json());

        $orgB = Organization::create(['name' => 'CRM Org B', 'slug' => 'crm-org-b']);
        $outsider = User::create([
            'name' => 'CRM Outsider',
            'email' => 'crm-cv-outsider@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $orgB->members()->syncWithoutDetaching([$outsider->id => ['role' => 'member', 'is_active' => true]]);
        $this->get("/api/v1/job-seekers/{$profile->id}/cv", $this->memberHeaders($outsider, $orgB))->assertForbidden();

        $this->patchJson("/api/v1/job-seekers/{$profile->id}", [
            'cv_path' => '../secrets/id.pdf',
        ], $this->adminHeaders($orgA))->assertOk();
        $this->assertSame('job-cvs/'.$profile->id.'/cv.pdf', $profile->fresh()->cv_path);

        $this->get('/api/v1/job-seekers/'.$profile->id.'/cv')->assertUnauthorized();
        $profile->update(['cv_path' => '../secrets/id.pdf']);
        $this->get("/api/v1/job-seekers/{$profile->id}/cv", $this->adminHeaders($orgA))->assertForbidden();
    }

    public function test_owner_completeness_is_server_calculated_and_photo_is_owner_only(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Photo Owner',
            'email' => 'photo-owner@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $intruder = User::create([
            'name' => 'Photo Intruder',
            'email' => 'photo-intruder@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([
            $user->id => ['role' => 'member'],
            $intruder->id => ['role' => 'member'],
        ]);
        $headers = $this->memberHeaders($user, $org);
        $intruderHeaders = $this->memberHeaders($intruder, $org);

        $empty = new JobSeekerProfile(['full_name' => 'Empty']);
        $this->assertSame(0, $empty->ownerCompletenessPercent());

        $partial = $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Photo Owner Test Name',
            'email' => 'photo-owner@wsa.test',
            'phone' => '+962700000000',
            'country' => 'JO',
            'city' => 'Amman',
        ]), $headers)->assertCreated();
        $this->assertSame(14, $partial->json('completeness_percent'));

        Storage::fake('local');
        $document = \Illuminate\Http\UploadedFile::fake()->create('degree.pdf', 80, 'application/pdf');
        $this->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document], $headers)->assertOk();

        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Photo Owner Test Name',
            'email' => 'photo-owner@wsa.test',
            'phone' => '+962700000000',
            'country' => 'JO',
            'city' => 'Amman',
            'target_job_title' => 'Agronomist',
            'biography' => 'Summary',
            'specialization' => 'Soil',
            'education' => [['institution' => 'KSU', 'degree' => 'BSc']],
            'experience' => [['title' => 'Lead']],
            'skills' => ['irrigation'],
            'languages' => ['ar'],
            'completeness_percent' => 3,
        ]), $headers)->assertOk();
        $withoutCv = $this->getJson('/api/v1/job-seekers/me', $headers)->assertOk();
        $this->assertSame(86, $withoutCv->json('completeness_percent'));
        $this->assertFalse($withoutCv->json('has_photo'));
        $this->assertArrayNotHasKey('photo_path', $withoutCv->json());
        $this->post('/api/v1/job-seekers/me/photo', [], $headers)->assertNotFound();
        $this->get('/api/v1/job-seekers/me/photo', $headers)->assertNotFound();
        $this->get('/api/v1/job-seekers/me/photo', $intruderHeaders)->assertNotFound();
    }

    public function test_job_seeker_cv_upload_accepts_pdf_only(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Cv Pdf Owner',
            'email' => 'cv-pdf-owner@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);
        $this->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
            'full_name' => 'Cv Pdf Owner Test Name',
            'email' => 'cv-pdf-owner@wsa.test',
        ]), $headers)->assertCreated();

        Storage::fake('local');
        $docx = \Illuminate\Http\UploadedFile::fake()->create('resume.docx', 80, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $jpg = \Illuminate\Http\UploadedFile::fake()->create('resume.jpg', 40, 'image/jpeg');
        $arabicRejection = $this->post('/api/v1/job-seekers/me/cv', ['cv' => $docx], $headers + ['Accept-Language' => 'ar'])
            ->assertUnprocessable();
        $this->assertStringContainsString('يجب رفع السيرة الذاتية بصيغة PDF فقط', json_encode($arabicRejection->json(), JSON_UNESCAPED_UNICODE));
        $this->post('/api/v1/job-seekers/me/cv', ['cv' => $jpg], $headers)
            ->assertUnprocessable();

        $pdfPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wsa-job-seeker-cv.pdf';
        file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
        $pdf = new \Illuminate\Http\UploadedFile($pdfPath, 'resume.pdf', 'application/pdf', null, true);
        $uploaded = $this->post('/api/v1/job-seekers/me/cv', ['cv' => $pdf], $headers)->assertOk();
        $this->assertTrue($uploaded->json('has_cv'));
        $this->assertNotEmpty($uploaded->json('cv_filename'));
        $this->assertStringEndsWith('.pdf', (string) $uploaded->json('cv_filename'));
        $this->assertArrayNotHasKey('cv_path', $this->getJson('/api/v1/job-seekers/me', $headers)->json());
        $this->assertTrue(str_starts_with((string) JobSeekerProfile::query()->where('email', 'cv-pdf-owner@wsa.test')->value('cv_path'), 'job-cvs/'));
    }

    public function test_recruitment_routes_are_documented_in_openapi(): void
    {
        $content = str_replace("\r\n", "\n", (string) file_get_contents($this->openApiSpecPath()));
        foreach ([
            '/job-seekers',
            '/job-seekers/me',
            '/job-seekers/me/cv',
            '/job-seekers/me/primary-qualification',
            '/job-seekers/{jobSeeker}',
            '/job-seekers/{jobSeeker}/cv',
            '/job-seekers/{jobSeeker}/status',
            '/job-seekers/{jobSeeker}/notes',
            '/job-seekers/{jobSeeker}/history',
            '/reports/recruitment',
        ] as $path) {
            $this->assertStringContainsString("  {$path}:", $content, "Missing OpenAPI path {$path}");
        }
        $jobSeekersBlock = strstr($content, "  /job-seekers:\n");
        $this->assertNotFalse($jobSeekersBlock);
        $meOffset = strpos($jobSeekersBlock, "  /job-seekers/me:");
        $this->assertNotFalse($meOffset);
        $listGet = substr($jobSeekersBlock, 0, $meOffset);
        $this->assertStringContainsString("'422': { \$ref: '#/components/responses/ValidationError' }", $listGet);
    }
}
