<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11M4DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_platform_me_returns_permissions_for_organization(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/platform/me', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonStructure(['user', 'organization_id', 'membership_role', 'roles', 'permissions']);
    }

    public function test_access_summary_returns_org_and_ai_metrics(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/platform/access-summary', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('organization_id', $organization->id)
            ->assertJsonStructure([
                'users_count',
                'teams_count',
                'roles_count',
                'audit_events_24h',
                'ai_requests' => ['today', 'pending', 'processing', 'completed', 'failed', 'cancelled'],
                'quota',
                'system' => ['api', 'queue'],
            ]);
    }

    public function test_team_show_returns_members(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $team = Team::create([
            'organization_id' => $organization->id,
            'name' => 'Field Ops',
            'slug' => 'field-ops',
        ]);
        $team->members()->attach($admin->id, ['role' => 'lead']);

        $this->getJson("/api/v1/teams/{$team->id}", [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('name', 'Field Ops')
            ->assertJsonCount(1, 'members');
    }

    public function test_foreign_team_show_returns_not_found(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Other Org', 'slug' => 'other-org']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreignTeam = Team::create([
            'organization_id' => $orgB->id,
            'name' => 'Secret Team',
            'slug' => 'secret-team',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson("/api/v1/teams/{$foreignTeam->id}", [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }
}
