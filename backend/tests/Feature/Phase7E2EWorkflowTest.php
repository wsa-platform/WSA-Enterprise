<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase7E2EWorkflowTest extends TestCase
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

    public function test_complete_agricultural_workflow_endpoints_are_accessible(): void
    {
        [$organization] = $this->actingAsTenantAdmin();

        $this->getJson('/api/v1/platform/organizations')->assertOk();
        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/platform/workflow-summary')->assertOk();

        $this->postJson('/api/v1/farm/farms', ['code' => 'F1', 'name' => 'North Farm'])
            ->assertCreated();

        $this->getJson('/api/v1/farm/fields')->assertOk();
        $this->getJson('/api/v1/crop/types')->assertOk();
        $this->getJson('/api/v1/soil/analyses')->assertOk();

        $this->postJson('/api/v1/diagnosis/requests', [
            'reference' => 'DX-E2E-1',
            'notes' => 'Leaf spots on tomato plants',
        ])->assertCreated();

        $this->postJson('/api/v1/training/courses', [
            'code' => 'TC-1',
            'title' => 'Integrated pest management',
        ])->assertCreated();

        $this->postJson('/api/v1/library/items', [
            'slug' => 'tomato-guide',
            'title' => 'Tomato guide',
            'publication_status' => 'published',
        ])->assertCreated();

        $this->getJson('/api/v1/ai/provider')->assertOk();
        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_qa',
            'input' => ['query' => 'What irrigation method is best?'],
        ])->assertCreated();

        $this->assertSame(1, Farm::where('organization_id', $organization->id)->count());
    }

    public function test_cross_tenant_read_with_foreign_header_is_forbidden(): void
    {
        $this->actingAsTenantAdmin();
        $foreign = Organization::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $this->withHeader('X-Organization-Id', (string) $foreign->id)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden();
    }

    public function test_unauthenticated_dashboard_is_rejected(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
