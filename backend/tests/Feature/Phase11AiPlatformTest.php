<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiRequest;
use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Role;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase11AiPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_usage_endpoint_returns_quota_summary(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        Config::set('ai.quota_enabled', true);
        Config::set('ai.quota_requests_per_period', 10);

        // Phase5 demo diagnosis seeds one AI usage record via DiagnosisWorkflowService.
        UsageRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->delete();

        $this->getJson('/api/v1/ai/usage', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('used', 0);
    }

    public function test_quota_enforcement_returns_429_and_audits(): void
    {
        Config::set('ai.async_dispatch', false);
        Config::set('ai.quota_enabled', true);
        Config::set('ai.quota_requests_per_period', 2);

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        UsageRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->delete();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/v1/ai/requests', [
                'request_type' => 'library_summary',
                'input' => ['content' => "Quota test {$i}"],
            ], $headers)->assertCreated();
        }

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Over quota'],
        ], $headers)->assertStatus(429)
            ->assertJsonPath('quota.limit', 2);

        $this->assertTrue(AuditLog::where('action', 'ai.quota.exceeded')->exists());
        $this->assertSame(2, UsageRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->count());
    }

    public function test_pending_request_can_be_cancelled(): void
    {
        Queue::fake();
        Config::set('ai.async_dispatch', true);

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $create = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Cancel me'],
        ], $headers)->assertAccepted();

        $id = $create->json('id');

        $this->postJson("/api/v1/ai/requests/{$id}/cancel", [], $headers)
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertTrue(AuditLog::where('action', 'ai.request.cancelled')->exists());

        (new ProcessAiRequest($id))->handle(app(AiService::class));
        $this->assertSame('cancelled', AiRequest::withoutGlobalScopes()->find($id)->status);
    }

    public function test_cannot_cancel_foreign_organization_request(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign AI Org', 'slug' => 'foreign-ai-org']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreign = AiRequest::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'pending',
            'input' => ['content' => 'secret'],
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson("/api/v1/ai/requests/{$foreign->id}/cancel", [], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }

    public function test_viewer_role_cannot_create_ai_requests(): void
    {
        $organization = Organization::first();
        EnterpriseRoleService::seedForOrganization($organization->id);

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => 'viewer-ai@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $viewer->organizations()->attach($organization->id, ['role' => 'member']);

        $viewerRole = Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'viewer')
            ->firstOrFail();
        $viewer->roles()->sync([$viewerRole->id => ['organization_id' => $organization->id]]);

        $token = $viewer->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Denied'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_provider_resolver_uses_organization_override(): void
    {
        $organization = Organization::first();
        OrganizationSetting::create([
            'organization_id' => $organization->id,
            'key' => 'ai.provider',
            'value' => ['provider' => 'mock'],
        ]);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/ai/provider', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonStructure(['quota']);
    }

    public function test_async_lifecycle_records_usage_and_audit_events(): void
    {
        Queue::fake();
        Config::set('ai.async_dispatch', true);

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        UsageRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->delete();

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Lifecycle'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertAccepted();

        $this->assertDatabaseHas('audit_logs', ['action' => 'ai.request.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ai.request.dispatched']);
        $this->assertSame(1, UsageRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->count());
    }

    public function test_completed_request_cannot_be_cancelled(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'done'],
            'output' => ['summary' => 'done'],
        ]);

        $this->postJson("/api/v1/ai/requests/{$record->id}/cancel", [], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertStatus(422);
    }
}
