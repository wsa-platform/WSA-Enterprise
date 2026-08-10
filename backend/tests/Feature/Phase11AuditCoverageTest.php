<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Database\Seeders\BillingSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class Phase11AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BillingSeeder::class);
    }

    public function test_organization_settings_update_is_audited_with_request_id(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->putJson('/api/v1/billing/settings', [
            'settings' => ['operations.timezone' => 'Europe/London'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
            'X-Request-Id' => 'audit-settings-req',
        ])->assertOk();

        $log = AuditLog::where('action', 'organization.settings.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('audit-settings-req', $log->request_id);
        $this->assertSame($organization->id, $log->organization_id);
    }

    public function test_team_and_billing_changes_remain_audited(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
            'X-Request-Id' => 'audit-team-billing-req',
        ];

        $this->postJson('/api/v1/teams', [
            'name' => 'Audit Coverage Team',
            'slug' => 'audit-coverage-team',
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/billing/subscription/plan', ['plan_slug' => 'pro'], $headers)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.created',
            'organization_id' => $organization->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billing.subscription.changed',
            'organization_id' => $organization->id,
        ]);

        $teamLog = AuditLog::where('action', 'team.created')->latest('id')->first();
        $this->assertSame('audit-team-billing-req', $teamLog?->request_id);
    }

    public function test_cross_tenant_denial_records_request_id_and_security_notification(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/dashboard', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => '999999',
            'X-Request-Id' => 'audit-cross-tenant-req',
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'security.cross_tenant_denied',
            'user_id' => $admin->id,
            'request_id' => 'audit-cross-tenant-req',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'type' => 'security.cross_tenant_attempt',
        ]);
    }
}
