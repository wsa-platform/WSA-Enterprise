<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\AppNotification;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use App\Services\Authorization\PermissionService;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase11TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_filters_tenant_owned_models_when_context_set(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b']);

        Farm::create(['organization_id' => $orgA->id, 'code' => 'A1', 'name' => 'Farm A']);
        Farm::create(['organization_id' => $orgB->id, 'code' => 'B1', 'name' => 'Farm B']);

        app(TenantContext::class)->setOrganizationId($orgA->id);

        $this->assertSame(1, Farm::count());
        $this->assertSame('Farm A', Farm::first()->name);
    }

    public function test_global_scope_does_not_apply_without_tenant_context(): void
    {
        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a-2']);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b-2']);

        Farm::create(['organization_id' => $orgA->id, 'code' => 'A2', 'name' => 'Farm A2']);
        Farm::create(['organization_id' => $orgB->id, 'code' => 'B2', 'name' => 'Farm B2']);

        $this->assertSame(2, Farm::withoutGlobalScopes()->count());
    }

    public function test_api_farm_list_is_scoped_to_active_organization(): void
    {
        $this->seed(DatabaseSeeder::class);
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign', 'slug' => 'foreign-scope']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        Farm::create(['organization_id' => $orgB->id, 'code' => 'FX', 'name' => 'Foreign Farm']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/farm/farms', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ]);

        $response->assertOk();
        $names = collect($response->json())->pluck('name');
        $this->assertFalse($names->contains('Foreign Farm'));
    }

    public function test_cross_tenant_ai_request_show_returns_not_found(): void
    {
        $this->seed(DatabaseSeeder::class);
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign AI', 'slug' => 'foreign-ai']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreign = AiRequest::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'secret'],
            'output' => ['summary' => 'secret'],
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/ai/requests/'.$foreign->id, [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }

    public function test_cross_tenant_notification_read_is_not_found(): void
    {
        $this->seed(DatabaseSeeder::class);
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign Notify', 'slug' => 'foreign-notify']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $notification = AppNotification::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'type' => 'system',
            'title' => 'Foreign',
            'body' => 'Hidden',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/notifications/'.$notification->id.'/read', [], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }
}
