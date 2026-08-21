<?php

namespace Tests\Feature;

use App\Models\JobSeekerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('security')]
class JobSeekerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seeker_can_register_when_generic_registration_is_disabled(): void
    {
        Config::set('app.allow_registration', false);
        Config::set('app.allow_job_seeker_registration', true);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Seeker',
            'email' => 'new-seeker@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => 'job_seeker',
            'device_name' => 'test',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'new-seeker@wsa.test')
            ->assertJsonStructure(['token', 'user']);

        $this->assertArrayNotHasKey('organization', $response->json());

        $user = User::where('email', 'new-seeker@wsa.test')->firstOrFail();
        $this->assertFalse($user->organizations()->exists());
        $this->assertDatabaseHas('job_seeker_profiles', [
            'user_id' => $user->id,
            'full_name' => 'New Seeker',
            'email' => 'new-seeker@wsa.test',
            'recruitment_status' => JobSeekerProfile::STATUS_NEW,
        ]);
    }

    public function test_generic_and_employer_registration_stay_disabled_without_allow_registration(): void
    {
        Config::set('app.allow_registration', false);
        Config::set('app.allow_job_seeker_registration', true);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked Owner',
            'email' => 'blocked-owner@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked Employer',
            'email' => 'blocked-employer@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => 'employer',
        ])->assertForbidden();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked Admin',
            'email' => 'blocked-admin@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'blocked-owner@wsa.test']);
        $this->assertDatabaseMissing('users', ['email' => 'blocked-employer@wsa.test']);
        $this->assertDatabaseMissing('users', ['email' => 'blocked-admin@wsa.test']);
    }

    public function test_job_seeker_registration_can_be_disabled_independently(): void
    {
        Config::set('app.allow_registration', true);
        Config::set('app.allow_job_seeker_registration', false);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Paused Seeker',
            'email' => 'paused-seeker@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => 'job_seeker',
        ])->assertForbidden();
    }

    public function test_registered_job_seeker_can_authenticate(): void
    {
        Config::set('app.allow_registration', false);
        Config::set('app.allow_job_seeker_registration', true);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Login Seeker',
            'email' => 'login-seeker@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'audience' => 'job_seeker',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login-seeker@wsa.test',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('user.email', 'login-seeker@wsa.test')
            ->assertJsonStructure(['token']);
    }
}
