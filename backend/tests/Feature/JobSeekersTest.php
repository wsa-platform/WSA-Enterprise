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

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Seeker User',
            'specialization' => 'Agronomist',
            'country' => 'SA',
            'city' => 'Riyadh',
            'skills' => ['irrigation'],
            'education' => [['institution' => 'KSU']],
            'experience' => [['title' => 'Farm lead']],
            'certifications' => [['name' => 'GAP']],
            'languages' => ['ar'],
            'cv_path' => 'resumes/seeker-user.pdf',
        ], $headers)->assertCreated()
            ->assertJsonPath('specialization', 'Agronomist')
            ->assertJsonPath('recruitment_status', JobSeekerProfile::STATUS_NEW);

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('specialization', 'Agronomist')
            ->assertJsonPath('email', 'myseeker@wsa.test')
            ->assertJsonPath('cv_path', 'resumes/seeker-user.pdf');

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Seeker User',
            'specialization' => 'Irrigation specialist',
        ], $headers)->assertOk()
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

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Mass Assign',
            'user_id' => $other->id,
            'owner_user_id' => $other->id,
            'recruitment_status' => JobSeekerProfile::STATUS_HIRED,
            'is_active' => false,
        ], $headers)->assertCreated();

        $profile = JobSeekerProfile::where('email', 'massassign@wsa.test')->first()
            ?? JobSeekerProfile::where('full_name', 'Mass Assign')->latest('id')->first();
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
        $this->assertArrayNotHasKey('cv_path', $response->json());
        $this->assertArrayNotHasKey('desired_salary', $response->json());
        $this->assertArrayNotHasKey('salary_currency', $response->json());
        $this->assertArrayNotHasKey('email', $response->json('user') ?? []);
        $this->assertSame($profile->completenessPercent(false), $response->json('completeness_percent'));

        $this->getJson("/api/v1/job-seekers/{$profile->id}", $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('cv_path', 'resumes/private-seeded.pdf')
            ->assertJsonPath('desired_salary', '15000.00')
            ->assertJsonPath('salary_currency', 'SAR')
            ->assertJsonPath('completeness_percent', $profile->completenessPercent(true));
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
        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'CV Privacy',
            'specialization' => 'Agronomist',
        ], $headers)->assertCreated();

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

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'CV Privacy',
            'specialization' => 'Agronomist',
            'email' => 'private-cv@wsa.test',
            'phone' => '+966500009997',
            'cv_path' => 'resumes/secret.pdf',
            'desired_salary' => 18000,
            'salary_currency' => 'SAR',
        ], $headers)->assertOk();

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

    public function test_owner_can_deactivate_own_profile(): void
    {
        $org = Organization::first();
        $user = User::create([
            'name' => 'Deactivate Me',
            'email' => 'deactivate@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$user->id => ['role' => 'member']]);
        $headers = $this->memberHeaders($user, $org);

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Deactivate Me',
        ], $headers)->assertCreated();

        $this->deleteJson('/api/v1/job-seekers/me')->assertUnauthorized();

        $this->deleteJson('/api/v1/job-seekers/me', [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Profile deactivated.');

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertTrue(JobSeekerProfile::where('user_id', $user->id)->exists());

        $intruder = User::create([
            'name' => 'Other Seeker',
            'email' => 'other-deactivate@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $org->members()->syncWithoutDetaching([$intruder->id => ['role' => 'member']]);
        $this->deleteJson('/api/v1/job-seekers/me', [], $this->memberHeaders($intruder, $org))
            ->assertNotFound();
        $this->assertTrue(JobSeekerProfile::where('user_id', $user->id)->where('is_active', false)->exists());
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

        $this->putJson('/api/v1/job-seekers/me', [
            'full_name' => 'Nested User',
            'experience' => [[
                'title' => 'Farm lead',
                'years' => 3,
                'ssn' => 'should-not-persist',
            ]],
            'education' => [['institution' => 'KSU', 'degree' => 'BSc']],
            'certifications' => [['name' => 'GAP']],
            'languages' => ['ar'],
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/job-seekers/me', $headers)
            ->assertOk()
            ->assertJsonPath('experience.0.title', 'Farm lead')
            ->assertJsonMissing(['ssn' => 'should-not-persist']);
    }

    public function test_recruitment_routes_are_documented_in_openapi(): void
    {
        $content = str_replace("\r\n", "\n", (string) file_get_contents($this->openApiSpecPath()));
        foreach ([
            '/job-seekers',
            '/job-seekers/me',
            '/job-seekers/{jobSeeker}',
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
