<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11IdorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_foreign_team_member_add_returns_not_found(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Org B IDOR', 'slug' => 'org-b-idor']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreignTeam = Team::create([
            'organization_id' => $orgB->id,
            'name' => 'Foreign Team',
            'slug' => 'foreign-team',
        ]);

        $member = User::create([
            'name' => 'Member',
            'email' => 'member-idor@wsa.test',
            'password' => bcrypt('password'),
        ]);
        $member->organizations()->attach($orgB->id, ['role' => 'member']);

        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/teams/'.$foreignTeam->id.'/members', [
            'user_id' => $member->id,
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }
}
