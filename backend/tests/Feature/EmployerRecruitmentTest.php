<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\JobContactRequest;
use App\Models\JobEmploymentRecord;
use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('security')]
class EmployerRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.allow_registration', false);
        Config::set('app.allow_job_seeker_registration', true);
        Config::set('app.allow_employer_registration', true);
    }

    public function test_guest_employer_registration_creates_employer_workspace_without_job_seeker_profile(): void
    {
        $response = $this->registerEmployer('owner@wsa.test');
        $response->assertCreated()->assertJsonPath('recruitment.is_employer', true);
        $user = User::where('email', 'owner@wsa.test')->firstOrFail();
        $this->assertDatabaseMissing('job_seeker_profiles', ['user_id' => $user->id]);
        $this->assertTrue(class_exists(\App\Services\Welcome\WelcomeWorkflowService::class));
        $this->assertNotNull(app(\App\Services\Welcome\WelcomeWorkflowService::class));
        $this->assertDatabaseHas('welcome_events', [
            'user_id' => $user->id,
            'trigger' => 'registration',
        ]);
    }

    public function test_job_seeker_cannot_register_or_login_as_employer_with_the_same_account(): void
    {
        $this->postJson('/api/v1/auth/register', $this->authPayload('seeker@wsa.test', 'job_seeker'))
            ->assertCreated();

        $this->postJson('/api/v1/auth/register', $this->authPayload('seeker@wsa.test', 'employer'))
            ->assertStatus(422);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'seeker@wsa.test',
            'password' => 'password123',
            'audience' => 'employer',
        ])->assertForbidden()
            ->assertJsonFragment(['message' => __('jobs.job_seeker_cannot_be_employer')]);
    }

    public function test_employer_cannot_create_job_seeker_profile_or_login_as_job_seeker(): void
    {
        $employer = $this->registerEmployer('boss@wsa.test');
        $token = $employer->json('token');
        $organizationId = $employer->json('organization.id');

        $this->withHeaders($this->authHeaders($token, $organizationId))
            ->putJson('/api/v1/job-seekers/me', $this->jobSeekerPersonalPayload([
                'email' => 'boss@wsa.test',
                'full_name' => 'Employer Cannot Convert Name',
            ]))
            ->assertForbidden();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'boss@wsa.test',
            'password' => 'password123',
            'audience' => 'job_seeker',
        ])->assertForbidden()
            ->assertJsonFragment(['message' => __('jobs.employer_cannot_be_job_seeker')]);

        $this->assertDatabaseMissing('job_seeker_profiles', [
            'email' => 'boss@wsa.test',
        ]);
    }

    public function test_platform_account_without_recruitment_role_can_activate_employer_service(): void
    {
        $user = User::factory()->create([
            'name' => 'Platform Account',
            'email' => 'platform-employer@wsa.test',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'platform-employer@wsa.test',
            'password' => 'password123',
            'audience' => 'employer',
            'device_name' => 'test',
        ])->assertOk()
            ->assertJsonPath('recruitment.is_employer', true)
            ->assertJsonPath('recruitment.is_job_seeker', false);

        $this->assertSame(1, $user->fresh()->organizations()->count());
        $this->assertDatabaseMissing('job_seeker_profiles', ['user_id' => $user->id]);
    }

    public function test_duplicate_employer_service_activation_does_not_create_a_second_workspace(): void
    {
        $user = User::factory()->create([
            'email' => 'duplicate-employer@wsa.test',
            'password' => 'password123',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/auth/employer-service')
            ->assertOk()
            ->assertJsonPath('recruitment.is_employer', true);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/auth/employer-service')
            ->assertOk()
            ->assertJsonPath('recruitment.is_employer', true);

        $this->assertSame(1, $user->fresh()->organizations()->count());
    }

    public function test_job_seeker_cannot_activate_employer_service_and_arabic_message_is_returned(): void
    {
        $seeker = $this->postJson('/api/v1/auth/register', $this->authPayload('seeker-activate@wsa.test', 'job_seeker'))
            ->assertCreated();
        $token = $seeker->json('token');

        app()->setLocale('ar');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept-Language' => 'ar',
        ])->postJson('/api/v1/auth/employer-service')
            ->assertForbidden()
            ->assertJsonFragment([
                'message' => __('jobs.job_seeker_cannot_be_employer'),
            ]);

        $this->assertSame(
            'هذا الحساب مسجل بالفعل كطالب وظيفة ولا يمكن استخدامه كصاحب عمل.',
            __('jobs.job_seeker_cannot_be_employer'),
        );

        $this->assertDatabaseHas('job_seeker_profiles', [
            'email' => 'seeker-activate@wsa.test',
        ]);
        $this->assertSame(0, User::where('email', 'seeker-activate@wsa.test')->firstOrFail()->organizations()->count());
    }

    public function test_job_seeker_cannot_search_candidates_or_unlock_contact(): void
    {
        $seekerToken = $this->postJson('/api/v1/auth/register', $this->authPayload('locked-out@wsa.test', 'job_seeker'))
            ->json('token');

        $this->withHeaders(['Authorization' => 'Bearer '.$seekerToken])
            ->getJson('/api/v1/jobs/seekers')
            ->assertForbidden();
    }

    public function test_employer_search_filters_job_seekers_and_excludes_protected_fields(): void
    {
        $employer = $this->registerEmployer('recruiter@wsa.test');
        $headers = $this->authHeaders($employer->json('token'), $employer->json('organization.id'));
        $this->createSeeker('visible-seeker@wsa.test', [
            'full_name' => 'Ahmed Ali Hassan Omar',
            'target_job_title' => 'Agricultural Engineer',
            'country' => 'Turkey',
            'city' => 'Istanbul',
            'years_of_experience' => 6,
            'specialization' => 'Irrigation',
            'skills' => ['irrigation', 'agronomy'],
            'languages' => ['Arabic', 'English'],
            'desired_salary' => 12000,
            'phone' => '+905551112233',
            'address' => 'Secret street 12',
        ]);
        $this->createSeeker('hidden-seeker@wsa.test', [
            'full_name' => 'Sara Mohamed Ali Nasser',
            'target_job_title' => 'Accountant',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'years_of_experience' => 2,
            'specialization' => 'Finance',
            'skills' => ['accounting'],
            'languages' => ['Arabic'],
            'desired_salary' => 5000,
        ]);

        $search = $this->withHeaders($headers)->getJson(
            '/api/v1/jobs/seekers?job_title=Agricultural&country=Turkey&city=Istanbul&years_of_experience=5&languages=Arabic,English&specialization=Irrigation'
        )->assertOk();

        $this->assertSame(1, $search->json('total'));
        $payload = json_encode($search->json());
        $this->assertStringContainsString('Agricultural Engineer', (string) $payload);
        $this->assertStringNotContainsString('visible-seeker@wsa.test', (string) $payload);
        $this->assertStringNotContainsString('+905551112233', (string) $payload);
        $this->assertStringNotContainsString('Secret street 12', (string) $payload);
        $this->assertArrayNotHasKey('user_id', $search->json('data.0'));
        $this->assertArrayNotHasKey('email', $search->json('data.0'));
        $this->assertArrayNotHasKey('phone', $search->json('data.0'));
        $this->assertArrayNotHasKey('address', $search->json('data.0'));
        $this->assertSame('job_seeker', $search->json('data.0.employment_status'));

        $profileId = $search->json('data.0.id');
        $show = $this->withHeaders($headers)->getJson('/api/v1/jobs/seekers/'.$profileId)->assertOk();
        $this->assertArrayNotHasKey('user_id', $show->json());
        $this->assertArrayNotHasKey('email', $show->json());
        $this->assertArrayNotHasKey('phone', $show->json());
    }

    public function test_failed_and_forged_payment_do_not_unlock_contact_or_hire(): void
    {
        $employer = $this->registerEmployer('payer@wsa.test');
        $headers = $this->authHeaders($employer->json('token'), $employer->json('organization.id'));
        $seeker = $this->createSeeker('candidate-pay@wsa.test', [
            'phone' => '+966500111222',
            'target_job_title' => 'Farm Manager',
        ]);

        $requestId = $this->withHeaders($headers)->postJson('/api/v1/jobs/seekers/'.$seeker->id.'/contact-requests', [
            'employer_contact' => ['name' => 'HR Desk', 'email' => 'hr@wsa.test'],
        ])->assertCreated()->json('id');

        $failed = $this->withHeaders($headers)->postJson('/api/v1/jobs/contact-requests/'.$requestId.'/pay', [
            'idempotency_key' => 'fail-'.Str::uuid(),
            'payment_success' => true,
            'status' => 'hired',
        ]);
        $failed->assertStatus(422);
        $this->assertStringNotContainsString('candidate-pay@wsa.test', (string) json_encode($failed->json()));
        $this->assertSame(0, JobEmploymentRecord::count());
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $seeker->fresh()->recruitment_status);
        $this->assertSame(0, $this->hiringNotificationCount());

        $this->withHeaders($headers)->getJson('/api/v1/jobs/contact-requests/'.$requestId.'/contact')
            ->assertForbidden();
    }

    public function test_verified_payment_unlocks_contact_hires_and_is_idempotent(): void
    {
        $employer = $this->registerEmployer('success-hr@wsa.test');
        $headers = $this->authHeaders($employer->json('token'), $employer->json('organization.id'));
        $seeker = $this->createSeeker('hired-candidate@wsa.test', [
            'phone' => '+966500333444',
            'target_job_title' => 'Agronomist',
        ]);

        $requestId = $this->withHeaders($headers)->postJson('/api/v1/jobs/seekers/'.$seeker->id.'/contact-requests', [
            'employer_contact' => ['name' => 'Success HR', 'email' => 'success-hr@wsa.test', 'phone' => '+966500000001'],
        ])->assertCreated()->json('id');

        $key = 'ok-'.Str::uuid();
        $first = $this->withHeaders($headers)->postJson('/api/v1/jobs/contact-requests/'.$requestId.'/pay', [
            'idempotency_key' => $key,
        ])->assertOk();

        $this->assertSame('hired-candidate@wsa.test', $first->json('exchange.candidate_contact.email'));
        $this->assertSame('+966500333444', $first->json('exchange.candidate_contact.phone'));
        $this->assertNotNull($first->json('hiring_record.id'));
        $this->assertSame(JobSeekerProfile::STATUS_HIRED, $seeker->fresh()->recruitment_status);
        $this->assertSame(1, JobEmploymentRecord::count());
        $this->assertSame(2, $this->hiringNotificationCount());

        $second = $this->withHeaders($headers)->postJson('/api/v1/jobs/contact-requests/'.$requestId.'/pay', [
            'idempotency_key' => $key,
        ])->assertOk();
        $this->assertSame($first->json('hiring_record.id'), $second->json('hiring_record.id'));
        $this->assertSame(1, JobEmploymentRecord::count());
        $this->assertSame(2, $this->hiringNotificationCount());
        $this->assertSame(1, JobContactRequest::count());
    }

    public function test_employer_cannot_pay_another_employers_contact_request(): void
    {
        $first = $this->registerEmployer('alpha-hr@wsa.test');
        $second = $this->registerEmployer('beta-hr@wsa.test');
        $seeker = $this->createSeeker('shared-candidate@wsa.test');
        $requestId = $this->withHeaders($this->authHeaders($first->json('token'), $first->json('organization.id')))
            ->postJson('/api/v1/jobs/seekers/'.$seeker->id.'/contact-requests', [
                'employer_contact' => ['name' => 'Alpha', 'email' => 'alpha-hr@wsa.test'],
            ])->assertCreated()->json('id');

        $this->withHeaders($this->authHeaders($second->json('token'), $second->json('organization.id')))
            ->postJson('/api/v1/jobs/contact-requests/'.$requestId.'/pay', [
                'idempotency_key' => 'ok-'.Str::uuid(),
            ])->assertNotFound();

        $this->assertSame(0, JobEmploymentRecord::count());
        $this->assertSame(JobSeekerProfile::STATUS_NEW, $seeker->fresh()->recruitment_status);
    }

    /** @param  array<string, mixed>  $overrides */
    private function createSeeker(string $email, array $overrides = []): JobSeekerProfile
    {
        $user = User::create([
            'name' => $overrides['full_name'] ?? 'Seeker User Name Here',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('seeker')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        $education = $overrides['education'] ?? [['degree' => 'BSc Agriculture', 'year' => 2018]];
        unset($overrides['education']);
        $payload = $this->jobSeekerPersonalPayload(array_merge([
            'email' => $email,
            'full_name' => 'Seeker User Name Here',
            'target_job_title' => 'Specialist',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'specialization' => 'Agriculture',
            'biography' => 'Experienced candidate',
            'skills' => ['farming'],
            'languages' => ['Arabic'],
            'experience' => [['title' => 'Agronomist', 'company' => 'Farm Co']],
            'years_of_experience' => 4,
            'desired_salary' => 8000,
            'phone' => '+966500000000',
            'address' => 'Private address',
        ], $overrides));

        $this->withHeaders($headers)
            ->putJson('/api/v1/job-seekers/me', $payload)
            ->assertSuccessful();

        Storage::fake('local');
        $document = UploadedFile::fake()->create('degree.pdf', 80, 'application/pdf');
        $this->withHeaders($headers)
            ->post('/api/v1/job-seekers/me/primary-qualification', ['document' => $document])
            ->assertOk();

        $this->withHeaders($headers)
            ->putJson('/api/v1/job-seekers/me', array_merge($payload, ['education' => $education]))
            ->assertSuccessful();

        return JobSeekerProfile::where('user_id', $user->id)->firstOrFail();
    }

    private function registerEmployer(string $email): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/register', $this->authPayload($email, 'employer'));
    }

    /** @return array<string, string> */
    private function authPayload(string $email, string $audience): array
    {
        return [
            'name' => $audience === 'employer' ? 'Employer Account Owner' : 'Job Seeker Person',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => $audience,
            'device_name' => 'test',
        ];
    }

    /** @return array<string, string> */
    private function authHeaders(string $token, int $organizationId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'X-Organization-Id' => (string) $organizationId,
        ];
    }

    private function hiringNotificationCount(): int
    {
        return AppNotification::withoutGlobalScopes()
            ->where('type', 'jobs.hiring.completed')
            ->count();
    }
}
