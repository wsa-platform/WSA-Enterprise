<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase8ComprehensiveWorkflowTest extends TestCase
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

    public function test_full_regression_workflow_from_sign_in_to_logout(): void
    {
        [$organization, $user] = $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/platform/organizations')->assertOk();
        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/platform/workflow-summary')->assertOk();

        $farm = $this->postJson('/api/v1/farm/farms', ['code' => 'F1', 'name' => 'North Farm'])
            ->assertCreated()
            ->json();

        $this->putJson("/api/v1/farm/farms/{$farm['id']}", ['code' => 'F1', 'name' => 'North Farm Updated'])
            ->assertOk()
            ->assertJsonPath('name', 'North Farm Updated');

        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/soil/analyses')->assertOk();

        $this->postJson('/api/v1/diagnosis/requests', [
            'reference' => 'DX-PH8-1',
            'notes' => 'Leaf spots on tomato plants',
        ])->assertCreated();

        $course = $this->postJson('/api/v1/training/courses', [
            'code' => 'TC-PH8',
            'title' => 'Integrated pest management',
        ])->assertCreated()->json();

        $this->postJson('/api/v1/training/enrollments', ['course_id' => $course['id']])
            ->assertCreated();

        $this->getJson('/api/v1/training/enrollments')->assertOk();

        $this->postJson('/api/v1/library/items', [
            'slug' => 'tomato-guide-ph8',
            'title' => 'Tomato guide',
            'publication_status' => 'published',
        ])->assertCreated();

        $this->getJson('/api/v1/library/search?q=tomato')->assertOk()->assertJsonPath('query', 'tomato');

        $this->getJson('/api/v1/ai/provider')->assertOk();
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_qa',
            'input' => ['query' => 'What irrigation method is best?'],
        ])->assertCreated()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonPath('status', 'completed');

        $this->assertSame(1, Farm::where('organization_id', $organization->id)->count());

        $foreign = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);
        $this->withHeader('X-Organization-Id', (string) $foreign->id)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden();
    }

    public function test_bearer_token_is_invalidated_after_logout(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        $organization->members()->attach($user->id, ['role' => 'admin']);
        $token = $user->createToken('phase8-test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->withHeaders($headers)->getJson('/api/v1/dashboard')->assertOk();
        $this->withHeaders($headers)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->withHeaders($headers)->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_farm_list_pagination_and_update_remain_backward_compatible(): void
    {
        $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/farm/farms')->assertOk()->assertJsonIsArray();
        $this->getJson('/api/v1/farm/farms?per_page=5')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
    }
}
