<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserIdentity;
use App\Models\WelcomeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformExtensionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $this->organization = Organization::where('slug', 'wsa-demo')->firstOrFail();
    }

    private function headers(): array
    {
        return ['X-Organization-Id' => (string) $this->organization->id];
    }

    public function test_registration_creates_identity_and_welcome_event(): void
    {
        config(['app.allow_registration' => true]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user']);

        $user = User::where('email', 'newuser@wsa.test')->firstOrFail();
        $this->assertDatabaseHas('user_identities', [
            'user_id' => $user->id,
            'provider' => UserIdentity::PROVIDER_EMAIL,
        ]);
        $this->assertDatabaseHas('welcome_events', [
            'user_id' => $user->id,
            'trigger' => 'registration',
        ]);
    }

    public function test_providers_status_requires_auth(): void
    {
        $this->getJson('/api/v1/providers/status')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_provider_status(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/providers/status')
            ->assertOk()
            ->assertJsonStructure(['providers' => ['email', 'sms', 'whatsapp', 'ai']]);
    }

    public function test_communications_compose_and_inbox(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->postJson('/api/v1/communications/messages', [
                'subject' => 'Test Subject',
                'body' => 'Test body',
                'channel' => 'email',
                'recipients' => [['email' => 'member@wsa.test']],
            ])
            ->assertCreated()
            ->assertJsonPath('subject', 'Test Subject');

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/inbox')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_analytics_traffic_returns_empty_when_no_events(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/analytics/traffic?days=7')
            ->assertOk()
            ->assertJsonPath('events_total', 0)
            ->assertJsonPath('page_views_total', 0);
    }

    public function test_phone_otp_returns_disconnected_when_sms_unconfigured(): void
    {
        config(['marketing.providers.sms' => 'none']);

        $this->postJson('/api/v1/auth/phone/send-otp', ['phone' => '+966500000000'])
            ->assertStatus(503)
            ->assertJsonPath('sent', false);
    }

    public function test_auth_identities_list(): void
    {
        Sanctum::actingAs($this->admin);

        UserIdentity::create([
            'user_id' => $this->admin->id,
            'provider' => UserIdentity::PROVIDER_EMAIL,
            'provider_id' => $this->admin->email,
            'email' => $this->admin->email,
            'verified_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/identities')
            ->assertOk()
            ->assertJsonFragment(['provider' => UserIdentity::PROVIDER_EMAIL]);
    }

    public function test_welcome_event_is_idempotent(): void
    {
        WelcomeEvent::create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'trigger' => 'registration',
            'status' => WelcomeEvent::STATUS_COMPLETED,
        ]);

        app(\App\Services\Welcome\WelcomeWorkflowService::class)
            ->dispatchRegistrationWelcome($this->admin, $this->organization->id);

        $this->assertSame(1, WelcomeEvent::where('user_id', $this->admin->id)->where('trigger', 'registration')->count());
    }
}
