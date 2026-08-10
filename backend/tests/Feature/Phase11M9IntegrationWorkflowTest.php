<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Database\Seeders\BillingSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * @group security
 */
class Phase11M9IntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BillingSeeder::class);
        Config::set('billing.enabled', true);
    }

    public function test_phase11_modules_integrate_across_auth_billing_ai_notifications_audit_and_analytics(): void
    {
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('integration')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $baseline = $this->getJson('/api/v1/analytics/overview', $headers)->assertOk()->json();
        $aiBefore = $baseline['ai']['requests_total'];
        $unreadBefore = $baseline['notifications']['unread'];
        $auditBefore = $baseline['audit']['events_24h'];

        $this->postJson('/api/v1/billing/subscription/plan', ['plan_slug' => 'pro'], $headers)
            ->assertOk()
            ->assertJsonPath('plan.slug', 'pro');

        $this->assertTrue(
            AuditLog::where('organization_id', $organization->id)
                ->where('action', 'billing.subscription.changed')
                ->exists()
        );

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'M9 integration workflow'],
        ], $headers)->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'organization_id' => $organization->id,
            'type' => 'ai.request.completed',
        ]);

        $this->postJson('/api/v1/api-clients', [
            'name' => 'M9 Integration Client',
        ], $headers)->assertCreated();

        $overview = $this->getJson('/api/v1/analytics/overview', $headers)
            ->assertOk()
            ->assertJsonPath('organization_id', $organization->id)
            ->json();

        $this->assertSame($aiBefore + 1, $overview['ai']['requests_total']);
        $this->assertGreaterThan($unreadBefore, $overview['notifications']['unread']);
        $this->assertGreaterThan($auditBefore, $overview['audit']['events_24h']);
        $this->assertArrayHasKey('metrics', $overview['billing_usage']);
        $this->assertArrayHasKey('quota', $overview['ai']);

        $this->getJson('/api/v1/billing/subscription', $headers)->assertOk();
        $this->getJson('/api/v1/notifications', $headers)->assertOk();
        $this->getJson('/api/v1/audit-logs', $headers)->assertOk();
        $this->getJson('/api/v1/ai/usage', $headers)->assertOk();
    }

    public function test_analytics_does_not_expose_foreign_organization_metrics(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'M9 Foreign Org', 'slug' => 'm9-foreign-org']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->syncWithoutDetaching([$orgB->id]);

        AppNotification::withoutGlobalScopes()->create([
            'organization_id' => $orgB->id,
            'user_id' => $admin->id,
            'type' => 'system.maintenance',
            'title' => 'Foreign only',
            'body' => 'Should not appear in org A analytics',
        ]);

        $token = $admin->createToken('integration')->plainTextToken;

        $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk()
            ->assertJsonPath('organization_id', $orgA->id);

        $orgAUnread = AppNotification::where('organization_id', $orgA->id)
            ->whereNull('read_at')
            ->count();

        $this->getJson('/api/v1/analytics/overview', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertJsonPath('notifications.unread', $orgAUnread);
    }
}
