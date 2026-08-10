<?php

namespace Tests\Feature;

use App\Models\BillingInvoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Database\Seeders\BillingSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class Phase11M5BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BillingSeeder::class);
    }

    public function test_plan_catalog_is_available_to_billing_viewers(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/billing/plans', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonFragment(['slug' => 'free'])
            ->assertJsonFragment(['slug' => 'enterprise']);
    }

    public function test_subscription_can_be_assigned_and_cancelled(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->postJson('/api/v1/billing/subscription/plan', ['plan_slug' => 'pro'], $headers)
            ->assertOk()
            ->assertJsonPath('plan.slug', 'pro');

        $this->postJson('/api/v1/billing/subscription/cancel', ['at_period_end' => true], $headers)
            ->assertOk()
            ->assertJsonPath('cancel_at_period_end', true);

        $this->assertTrue(
            \App\Models\AuditLog::where('action', 'billing.subscription.cancelled')->exists()
        );
    }

    public function test_billing_usage_summary_integrates_ai_usage(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->getJson('/api/v1/billing/usage', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk()
            ->assertJsonStructure([
                'period_start',
                'metrics' => ['ai.requests' => ['used', 'limit', 'remaining', 'usage_percent']],
                'history',
            ]);
    }

    public function test_inactive_subscription_blocks_ai_when_billing_enabled(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        $subscription = app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);
        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_at_period_end' => false]);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'blocked by billing'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_billing_quota_enforcement_uses_plan_limit(): void
    {
        Config::set('billing.enabled', true);
        Config::set('ai.quota_enabled', false);

        $organization = Organization::first();
        $pro = Plan::where('slug', 'pro')->firstOrFail();
        app(SubscriptionService::class)->assignPlan($organization->id, $pro);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        \App\Models\UsageRecord::create([
            'organization_id' => $organization->id,
            'metric' => 'ai.requests',
            'quantity' => 500,
            'period_start' => now()->startOfMonth()->toDateString(),
        ]);

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'over plan quota'],
        ], $headers)->assertStatus(429);
    }

    public function test_foreign_organization_cannot_read_billing_subscription(): void
    {
        $orgA = Organization::first();
        $orgB = Organization::create(['name' => 'Billing Foreign Org', 'slug' => 'billing-foreign-org']);
        $admin = User::where('email', 'admin@wsa.test')->first();
        $admin->organizations()->attach($orgB, ['role' => 'admin']);

        Config::set('billing.enabled', true);
        $orgBSubscription = app(SubscriptionService::class)->ensureDefaultSubscription($orgB->id);
        $orgBSubscription->update(['status' => 'active', 'external_subscription_id' => 'sub_foreign_only']);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/billing/subscription', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $orgA->id,
        ])->assertOk();

        $this->assertSame($orgA->id, $response->json('subscription.organization_id'));
        $this->assertNotSame($orgBSubscription->id, $response->json('subscription.id'));
        $this->assertNotSame('sub_foreign_only', $response->json('subscription.external_subscription_id'));
    }

    public function test_viewer_cannot_assign_plan(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);

        $viewer = User::create([
            'name' => 'Billing Viewer',
            'email' => 'billing-viewer@wsa.test',
            'password' => bcrypt('password'),
        ]);
        $viewer->organizations()->attach($organization->id, ['role' => 'member']);
        $viewerRole = \App\Models\Role::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('slug', 'viewer')
            ->firstOrFail();
        $viewer->roles()->sync([$viewerRole->id => ['organization_id' => $organization->id]]);

        $token = $viewer->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/billing/subscription/plan', ['plan_slug' => 'pro'], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();
    }

    public function test_invoices_are_scoped_to_organization(): void
    {
        Config::set('billing.enabled', true);
        $organization = Organization::first();
        app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);
        app(SubscriptionService::class)->createInvoiceForCurrentPeriod($organization->id);

        $foreign = Organization::create(['name' => 'Invoice Foreign', 'slug' => 'invoice-foreign']);
        BillingInvoice::withoutGlobalScopes()->create([
            'organization_id' => $foreign->id,
            'number' => 'INV-FOREIGN-001',
            'status' => 'open',
            'amount_cents' => 1000,
            'currency' => 'USD',
        ]);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/billing/invoices', [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertOk();

        $payload = $response->json();
        $rows = $payload['data'] ?? $payload;
        $this->assertTrue(collect($rows)->every(fn ($row) => $row['organization_id'] === $organization->id));
    }

    public function test_operational_settings_can_be_updated_by_admin(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->putJson('/api/v1/billing/settings', [
            'settings' => [
                'operations.timezone' => 'Asia/Riyadh',
                'operations.support_email' => 'ops@wsa.test',
            ],
        ], $headers)->assertOk()
            ->assertJsonFragment(['operations.timezone' => ['value' => 'Asia/Riyadh']]);

        $this->getJson('/api/v1/billing/settings', $headers)
            ->assertOk()
            ->assertJsonFragment(['operations.support_email' => ['value' => 'ops@wsa.test']]);
    }
}
