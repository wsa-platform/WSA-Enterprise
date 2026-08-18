<?php

namespace Tests\Feature;

use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_503_when_oauth_is_disconnected(): void
    {
        $this->getJson('/api/v1/auth/google/redirect')
            ->assertStatus(503)
            ->assertJsonStructure(['error']);
    }

    public function test_google_redirect_returns_authorization_url_when_connected(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost/auth/callback',
        ]);

        $response = $this->getJson('/api/v1/auth/google/redirect')
            ->assertOk()
            ->assertJsonStructure(['url', 'state']);

        $this->assertStringContainsString('accounts.google.com', (string) $response->json('url'));
    }

    public function test_phone_otp_send_returns_503_when_sms_is_disconnected(): void
    {
        config(['marketing.providers.sms' => 'none']);

        $this->postJson('/api/v1/auth/phone/send-otp', [
            'phone' => '+966500000000',
        ])
            ->assertStatus(503)
            ->assertJsonPath('sent', false);
    }

    public function test_phone_otp_send_succeeds_when_sms_mock_is_configured(): void
    {
        config(['marketing.providers.sms' => 'mock']);

        $this->postJson('/api/v1/auth/phone/send-otp', [
            'phone' => '+966500000001',
        ])
            ->assertOk()
            ->assertJsonPath('sent', true);

        $this->assertDatabaseHas('phone_verifications', [
            'phone' => '+966500000001',
        ]);
    }

    public function test_phone_otp_verify_issues_token_for_valid_code(): void
    {
        config([
            'marketing.providers.sms' => 'mock',
            'app.allow_registration' => true,
        ]);

        PhoneVerification::create([
            'phone' => '+966500000002',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/auth/phone/verify-otp', [
            'phone' => '+966500000002',
            'code' => '123456',
            'name' => 'Phone User',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_forgot_password_does_not_reveal_whether_email_exists(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'missing@wsa.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If the email exists, a reset link was sent.');
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        User::create([
            'name' => 'Reset User',
            'email' => 'reset@wsa.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'reset@wsa.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertStatus(422);
    }

    public function test_reset_password_succeeds_with_broker_token(): void
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-ok@wsa.test',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset-ok@wsa.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully.');
    }

    public function test_phase3_auth_routes_are_documented_in_openapi(): void
    {
        $content = file_get_contents($this->openApiSpecPath());
        $this->assertNotFalse($content);

        foreach ([
            '/auth/forgot-password',
            '/auth/reset-password',
            '/auth/google/redirect',
            '/auth/google/callback',
            '/auth/phone/send-otp',
            '/auth/phone/verify-otp',
        ] as $path) {
            $this->assertStringContainsString(
                "  {$path}:",
                $content,
                "Missing OpenAPI path {$path}"
            );
        }
    }
}
