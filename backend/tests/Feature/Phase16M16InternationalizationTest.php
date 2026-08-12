<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleFromHeader;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group security
 */
class Phase16M16InternationalizationTest extends TestCase
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
        $token = $admin->createToken('phase16-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_accept_language_header_sets_application_locale(): void
    {
        $this->getJson('/api/v1/health', ['Accept-Language' => 'ar'])
            ->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_unsupported_accept_language_falls_back_to_english(): void
    {
        $this->getJson('/api/v1/health', ['Accept-Language' => 'de-DE,de;q=0.9'])
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_supported_locales_constant_matches_m16_requirements(): void
    {
        $this->assertSame(['en', 'ar', 'tr', 'fr'], SetLocaleFromHeader::SUPPORTED_LOCALES);
    }

    public function test_organization_settings_rejects_unsupported_locale(): void
    {
        $headers = $this->adminHeaders();

        $this->putJson('/api/v1/organization/settings', [
            'settings' => [
                'operations.locale' => 'de',
            ],
        ], $headers)->assertUnprocessable();
    }

    public function test_organization_settings_accepts_supported_locale(): void
    {
        $headers = $this->adminHeaders();

        $this->putJson('/api/v1/organization/settings', [
            'settings' => [
                'operations.locale' => 'fr',
            ],
        ], $headers)->assertOk()
            ->assertJsonFragment(['operations.locale' => ['value' => 'fr']]);
    }

    public function test_m15_invitation_endpoints_remain_available_after_m16(): void
    {
        $headers = $this->adminHeaders();

        $this->getJson('/api/v1/invitations', $headers)->assertOk();
        $this->getJson('/api/v1/auth/sessions', $headers)->assertOk();
    }
}
