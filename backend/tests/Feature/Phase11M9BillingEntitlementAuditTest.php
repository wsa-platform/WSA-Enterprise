<?php

namespace Tests\Feature;

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
class Phase11M9BillingEntitlementAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BillingSeeder::class);
        Config::set('billing.enabled', true);
    }

    public function test_inactive_subscription_ai_denial_is_audited(): void
    {
        $organization = Organization::first();
        $subscription = app(SubscriptionService::class)->ensureDefaultSubscription($organization->id);
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_at_period_end' => false,
        ]);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'blocked'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertForbidden();

        $this->assertTrue(
            AuditLog::where('organization_id', $organization->id)
                ->where('action', 'billing.subscription.inactive')
                ->exists()
        );
    }
}
