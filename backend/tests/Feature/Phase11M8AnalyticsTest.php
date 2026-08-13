<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\ApiClient;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @group security
 */
class Phase11M8AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_analytics_overview_is_organization_scoped(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Analytics Org B', 'slug' => 'analytics-org-b']);
        EnterpriseRoleService::seedForOrganization($orgB->id);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->syncWithoutDetaching([
            $orgB->id => ['role' => 'admin', 'is_active' => true],
        ]);

        Farm::create(['organization_id' => $orgA->id, 'code' => 'A-F1', 'name' => 'Farm A']);
        Farm::create(['organization_id' => $orgB->id, 'code' => 'B-F1', 'name' => 'Farm B']);

        $farmsBeforeA = Farm::where('organization_id', $orgA->id)->count();
        $farmsBeforeB = Farm::where('organization_id', $orgB->id)->count();
        $aiBeforeA = AiRequest::where('organization_id', $orgA->id)->count();
        $aiBeforeB = AiRequest::where('organization_id', $orgB->id)->count();

        AiRequest::create([
            'organization_id' => $orgA->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'a'],
            'output' => ['summary' => 'a'],
        ]);
        AiRequest::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'b'],
            'output' => ['summary' => 'b'],
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $responseA = $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ]);

        $responseA->assertOk()
            ->assertJsonPath('organization_id', $orgA->id)
            ->assertJsonPath('scope', 'organization')
            ->assertJsonPath('farms.total', $farmsBeforeA)
            ->assertJsonPath('ai.requests_total', $aiBeforeA + 1);

        $responseB = $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgB->id,
        ]);

        $responseB->assertOk()
            ->assertJsonPath('organization_id', $orgB->id)
            ->assertJsonPath('scope', 'organization')
            ->assertJsonPath('farms.total', $farmsBeforeB)
            ->assertJsonPath('ai.requests_total', $aiBeforeB + 1);
    }

    public function test_analytics_overview_requires_platform_view_permission(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $user = User::create([
            'name' => 'Limited',
            'email' => 'limited-analytics@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $user->organizations()->attach($organization->id, ['role' => 'member']);

        $limitedRole = Role::create([
            'organization_id' => $organization->id,
            'name' => 'Limited Analytics',
            'slug' => 'limited-analytics',
        ]);
        $limitedRole->permissions()->sync(
            Permission::where('organization_id', $organization->id)
                ->where('name', 'farm.view')
                ->pluck('id')
        );
        $user->roles()->sync([$limitedRole->id => ['organization_id' => $organization->id]]);

        $token = $user->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }
}
