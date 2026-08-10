<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11TeamAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_team_and_add_member(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $member = User::create([
            'name' => 'Team Member',
            'email' => 'team-member@wsa.test',
            'password' => bcrypt('password'),
        ]);
        $member->organizations()->attach($organization->id, ['role' => 'member']);

        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $create = $this->postJson('/api/v1/teams', [
            'name' => 'Field Operations',
            'slug' => 'field-ops',
        ], $headers)->assertCreated();

        $teamId = $create->json('id');

        $this->postJson("/api/v1/teams/{$teamId}/members", [
            'user_id' => $member->id,
        ], $headers)->assertOk();

        $this->assertDatabaseHas('team_user', [
            'team_id' => $teamId,
            'user_id' => $member->id,
        ]);

        $this->assertTrue(AuditLog::where('action', 'team.created')->exists());
        $this->assertTrue(AuditLog::where('action', 'team.member_added')->exists());
    }

    public function test_viewer_cannot_create_teams(): void
    {
        $organization = Organization::first();
        $viewer = User::create([
            'name' => 'Viewer',
            'email' => 'viewer-team@wsa.test',
            'password' => bcrypt('password'),
        ]);
        $viewer->organizations()->attach($organization->id, ['role' => 'member']);

        $token = $viewer->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/teams', [
            'name' => 'Blocked Team',
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }
}
