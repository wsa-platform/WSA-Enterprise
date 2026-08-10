<?php

namespace Tests\Feature;

use App\Models\DiagnosisCategory;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\Authorization\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase9AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_audited_without_password(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@wsa.test',
            'password' => 'password',
            'device_name' => 'test-suite',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
        ]);

        $log = \App\Models\AuditLog::first();
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    public function test_failed_login_is_audited_without_password(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@wsa.test',
            'password' => 'wrong-password',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login_failed',
        ]);

        $log = \App\Models\AuditLog::where('action', 'auth.login_failed')->first();
        $this->assertSame('admin@wsa.test', $log->new_values['email'] ?? null);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    public function test_logout_is_audited(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@wsa.test', 'password' => Hash::make('password')]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }
}
