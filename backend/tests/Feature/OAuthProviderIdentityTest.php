<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthProviderIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_includes_official_account_chooser(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost:5173/auth/callback',
        ]);

        $url = $this->getJson('/api/v1/auth/google/redirect')->assertOk()->json('url');

        $this->assertStringContainsString('accounts.google.com', (string) $url);
        $this->assertStringContainsString('prompt=select_account', (string) $url);
        $this->assertStringContainsString('client_id=test-google-client', (string) $url);
    }

    public function test_google_callback_rejects_browser_supplied_email_without_code(): void
    {
        $this->postJson('/api/v1/auth/google/callback', [
            'email' => 'attacker@wsa.test',
            'name' => 'Attacker',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'attacker@wsa.test']);
    }

    public function test_google_callback_rejects_unverified_identity_token(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        Cache::put('oauth:google:valid-state', true, 600);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['id_token' => 'id-token'], 200),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'accounts.google.com',
                'aud' => 'test-google-client',
                'sub' => 'google-sub-1',
                'email' => 'unverified@wsa.test',
                'email_verified' => 'false',
                'exp' => time() + 3600,
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code-1',
            'state' => 'valid-state',
            'device_name' => 'wsa-web-dashboard',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'unverified@wsa.test']);
    }

    public function test_google_callback_creates_user_from_verified_token_not_request_email(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        Cache::put('oauth:google:valid-state', true, 600);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['id_token' => 'id-token'], 200),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'https://accounts.google.com',
                'aud' => 'test-google-client',
                'sub' => 'google-sub-verified',
                'email' => 'google-user@wsa.test',
                'email_verified' => 'true',
                'name' => 'Google User',
                'exp' => time() + 3600,
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code-1',
            'state' => 'valid-state',
            'email' => 'forged@wsa.test',
            'device_name' => 'wsa-web-dashboard',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'google-user@wsa.test')
            ->assertJsonPath('created', true)
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseMissing('users', ['email' => 'forged@wsa.test']);
        $this->assertDatabaseHas('user_identities', [
            'provider' => UserIdentity::PROVIDER_GOOGLE,
            'provider_id' => 'google-sub-verified',
            'email' => 'google-user@wsa.test',
        ]);
    }

    public function test_existing_verified_email_is_linked_instead_of_duplicated(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        $existing = User::factory()->create(['email' => 'linked@wsa.test']);
        Cache::put('oauth:google:link-state', true, 600);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['id_token' => 'id-token'], 200),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'accounts.google.com',
                'aud' => 'test-google-client',
                'sub' => 'google-sub-link',
                'email' => 'linked@wsa.test',
                'email_verified' => true,
                'name' => 'Linked User',
                'exp' => time() + 3600,
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code-1',
            'state' => 'link-state',
            'device_name' => 'wsa-web-dashboard',
        ])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('user.id', $existing->id);

        $this->assertSame(1, User::query()->where('email', 'linked@wsa.test')->count());
    }

    public function test_provider_identity_cannot_attach_to_two_users(): void
    {
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        $owner = User::factory()->create(['email' => 'owner@wsa.test']);
        UserIdentity::create([
            'user_id' => $owner->id,
            'provider' => UserIdentity::PROVIDER_GOOGLE,
            'provider_id' => 'google-sub-unique',
            'email' => $owner->email,
            'verified_at' => now(),
        ]);

        Cache::put('oauth:google:unique-state', true, 600);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['id_token' => 'id-token'], 200),
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'accounts.google.com',
                'aud' => 'test-google-client',
                'sub' => 'google-sub-unique',
                'email' => 'other@wsa.test',
                'email_verified' => true,
                'name' => 'Other',
                'exp' => time() + 3600,
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/google/callback', [
            'code' => 'code-1',
            'state' => 'unique-state',
            'device_name' => 'wsa-web-dashboard',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $owner->id);

        $this->assertSame(1, UserIdentity::query()->where('provider_id', 'google-sub-unique')->count());
    }

    public function test_facebook_callback_requires_verified_email_and_debug_token(): void
    {
        config([
            'services.facebook.client_id' => 'fb-app',
            'services.facebook.client_secret' => 'fb-secret',
            'services.facebook.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        Cache::put('oauth:facebook:fb-state', true, 600);

        Http::fake([
            'graph.facebook.com/v18.0/oauth/access_token*' => Http::response(['access_token' => 'fb-token'], 200),
            'graph.facebook.com/debug_token*' => Http::response([
                'data' => ['is_valid' => true, 'app_id' => 'fb-app', 'user_id' => 'fb-user-1'],
            ], 200),
            'graph.facebook.com/me*' => Http::response([
                'id' => 'fb-user-1',
                'name' => 'Facebook User',
                'email' => 'facebook-user@wsa.test',
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/facebook/callback', [
            'code' => 'fb-code',
            'state' => 'fb-state',
            'email' => 'forged-fb@wsa.test',
            'device_name' => 'wsa-web-dashboard',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'facebook-user@wsa.test');

        $this->assertDatabaseMissing('users', ['email' => 'forged-fb@wsa.test']);
    }

    public function test_facebook_callback_rejects_missing_email(): void
    {
        config([
            'services.facebook.client_id' => 'fb-app',
            'services.facebook.client_secret' => 'fb-secret',
            'services.facebook.redirect' => 'http://localhost:5173/auth/callback',
            'app.allow_registration' => true,
        ]);
        Cache::put('oauth:facebook:fb-no-email', true, 600);

        Http::fake([
            'graph.facebook.com/v18.0/oauth/access_token*' => Http::response(['access_token' => 'fb-token'], 200),
            'graph.facebook.com/debug_token*' => Http::response([
                'data' => ['is_valid' => true, 'app_id' => 'fb-app', 'user_id' => 'fb-user-2'],
            ], 200),
            'graph.facebook.com/me*' => Http::response([
                'id' => 'fb-user-2',
                'name' => 'No Email',
            ], 200),
        ]);

        $this->postJson('/api/v1/auth/facebook/callback', [
            'code' => 'fb-code',
            'state' => 'fb-no-email',
            'device_name' => 'wsa-web-dashboard',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('user_identities', ['provider_id' => 'fb-user-2']);
    }
}
