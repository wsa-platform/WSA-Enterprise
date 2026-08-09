<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase8SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organization, 1: User} */
    private function actingAsTenantAdmin(): array
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_cross_org_task_status_update_is_forbidden(): void
    {
        [$organizationA] = $this->actingAsTenantAdmin();
        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $project = Project::create(['organization_id' => $organizationB->id, 'code' => 'P1', 'name' => 'Foreign project', 'status' => 'active']);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Foreign task', 'status' => 'todo', 'priority' => 'medium']);

        $this->patchJson("/api/v1/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertNotFound();
    }

    public function test_registration_is_disabled_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_farm_list_supports_pagination_metadata(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/farm/farms?per_page=5')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_user_endpoint_returns_sanitized_profile(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'email'])
            ->assertJsonMissing(['password']);
    }

    public function test_cross_org_role_assignment_is_rejected(): void
    {
        [$organizationA] = $this->actingAsTenantAdmin();
        $organizationB = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $foreignRole = Role::create(['organization_id' => $organizationB->id, 'name' => 'Foreign role']);
        $member = User::create(['name' => 'Member', 'email' => 'member@wsa.test', 'password' => Hash::make('password')]);
        $member->organizations()->attach($organizationA->id, ['role' => 'member']);

        $this->postJson("/api/v1/users/{$member->id}/roles", ['role_id' => $foreignRole->id])
            ->assertUnprocessable();
    }

    public function test_api_request_logging_middleware_does_not_break_responses(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/farm/farms')->assertOk();
    }
}
