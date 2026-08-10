<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10TenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_foreign_organization_header_cannot_read_audit_logs(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign Org', 'slug' => 'foreign-org']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        AuditLog::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'action' => 'test.foreign',
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/audit-logs', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk()
            ->assertJsonMissing(['action' => 'test.foreign']);
    }

    public function test_foreign_organization_cannot_read_ai_request(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Foreign Org', 'slug' => 'foreign-org-2']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        $foreign = AiRequest::create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'request_type' => 'library_summary',
            'provider' => 'mock',
            'status' => 'completed',
            'input' => ['content' => 'hidden'],
            'output' => ['summary' => 'hidden'],
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/ai/requests', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk()
            ->assertJsonMissing(['id' => $foreign->id]);
    }
}
