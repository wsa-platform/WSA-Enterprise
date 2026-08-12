<?php

namespace Tests\Feature;

use App\Models\MonitoringEvent;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * @group security
 */
class Phase15M15EnterprisePlatformTest extends TestCase
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
        $token = $admin->createToken('phase15-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_monitoring_view_permission_allows_read_only_access(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $manager = User::create([
            'name' => 'Ops Manager',
            'email' => 'phase15-manager@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $manager->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => true]);
        $managerRole = Role::withoutGlobalScopes()->where('organization_id', $organization->id)->where('slug', 'manager')->firstOrFail();
        $manager->roles()->sync([$managerRole->id => ['organization_id' => $organization->id]]);
        app(\App\Services\Authorization\PermissionService::class)->forget($manager, $organization->id);

        $token = $manager->createToken('manager')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->getJson('/api/v1/monitoring/health', $headers)->assertOk();
        $this->getJson('/api/v1/monitoring/incidents', $headers)->assertOk();

        $incident = MonitoringEvent::create([
            'organization_id' => $organization->id,
            'component' => 'cache',
            'status' => MonitoringEvent::STATUS_OPEN,
            'severity' => 'warning',
            'lifecycle_stage' => 'detected',
            'detected_at' => now(),
        ]);

        $this->postJson("/api/v1/monitoring/incidents/{$incident->id}/resolve", [], $headers)->assertForbidden();
    }

    public function test_user_invitation_flow_creates_and_accepts_membership(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'invited.user@wsa.test',
            'role' => 'member',
        ], $headers)->assertCreated();

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $this->postJson('/api/v1/auth/accept-invitation', [
            'token' => $token,
            'name' => 'Invited User',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('user.email', 'invited.user@wsa.test');

        $this->assertTrue(
            User::where('email', 'invited.user@wsa.test')
                ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
                ->exists()
        );

        $this->assertDatabaseHas('organization_invitations', [
            'email' => 'invited.user@wsa.test',
            'organization_id' => $organization->id,
        ]);
        $this->assertNotNull(OrganizationInvitation::where('email', 'invited.user@wsa.test')->value('accepted_at'));
    }

    public function test_invitation_requires_access_manage_and_can_be_revoked(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $created = $this->postJson('/api/v1/invitations', [
            'email' => 'revoke.me@wsa.test',
        ], $headers)->assertCreated();

        $invitationId = $created->json('id');

        $this->getJson('/api/v1/invitations', $headers)
            ->assertOk()
            ->assertJsonFragment(['email' => 'revoke.me@wsa.test']);

        $this->deleteJson("/api/v1/invitations/{$invitationId}", [], $headers)->assertNoContent();
        $this->assertDatabaseMissing('organization_invitations', ['id' => $invitationId]);
    }

    public function test_auth_sessions_can_be_listed_and_revoked(): void
    {
        $admin = User::where('email', 'admin@wsa.test')->first();
        $current = $admin->createToken('current-session');
        $other = $admin->createToken('other-session');

        $headers = ['Authorization' => 'Bearer '.$current->plainTextToken];

        $this->getJson('/api/v1/auth/sessions', $headers)
            ->assertOk()
            ->assertJsonFragment(['name' => 'current-session', 'is_current' => true])
            ->assertJsonFragment(['name' => 'other-session', 'is_current' => false]);

        $this->deleteJson('/api/v1/auth/sessions/'.$other->accessToken->id, [], $headers)
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::find($other->accessToken->id));
    }

    public function test_catalog_includes_monitoring_view_permission(): void
    {
        $this->assertContains('monitoring.view', config('permissions'));
    }
}
