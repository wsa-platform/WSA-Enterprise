<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiRequest;
use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Ai\AiService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase10AsyncAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_async_store_returns_202_and_pending_status(): void
    {
        Queue::fake();
        Config::set('ai.async_dispatch', true);

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Async smoke content'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertAccepted()
            ->assertJsonPath('status', 'pending');

        Queue::assertPushed(ProcessAiRequest::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ai.request.dispatched']);
    }

    public function test_sync_store_still_returns_201(): void
    {
        Config::set('ai.async_dispatch', false);

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Sync content'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'completed');
    }

    public function test_worker_completes_pending_request(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::factory()->create();

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'pending',
            'input' => ['content' => 'Worker test'],
        ]);

        (new ProcessAiRequest($record->id))->handle(app(AiService::class));

        $record->refresh();
        $this->assertSame('completed', $record->status);
        $this->assertNotNull($record->output);
        $this->assertTrue(AuditLog::where('action', 'ai.request.completed')->exists());
    }

    public function test_duplicate_processing_is_idempotent(): void
    {
        $organization = Organization::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $user = User::factory()->create();

        $record = AiRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'Already done'],
            'output' => ['summary' => 'existing'],
        ]);

        (new ProcessAiRequest($record->id))->handle(app(AiService::class));

        $record->refresh();
        $this->assertSame('completed', $record->status);
        $this->assertSame(['summary' => 'existing'], $record->output);
    }

    public function test_show_endpoint_is_tenant_scoped(): void
    {
        $orgA = Organization::create(['name' => 'A', 'slug' => 'a']);
        $orgB = Organization::create(['name' => 'B', 'slug' => 'b']);
        $admin = User::factory()->create();
        $admin->organizations()->attach($orgA, ['role' => 'admin']);
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreign = AiRequest::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'secret'],
            'output' => ['summary' => 'x'],
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson("/api/v1/ai/requests/{$foreign->id}", [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertNotFound();
    }
}
