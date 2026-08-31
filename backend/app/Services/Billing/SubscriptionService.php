<?php

namespace App\Services\Billing;

use App\Contracts\BillingProviderInterface;
use App\Models\BillingAccount;
use App\Models\BillingInvoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private BillingProviderInterface $provider,
        private AuditService $auditService,
    ) {}

    public function currentSubscription(int $organizationId): ?Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->with('plan.features')
            ->where('organization_id', $organizationId)
            ->latest('id')
            ->first();
    }

    public function ensureDefaultSubscription(int $organizationId): Subscription
    {
        $existing = $this->currentSubscription($organizationId);
        if ($existing !== null) {
            return $existing;
        }

        $plan = Plan::query()->where('slug', config('billing.default_plan_slug', 'free'))->firstOrFail();

        return $this->assignPlan($organizationId, $plan, null, 'active');
    }

    public function assignPlan(
        int $organizationId,
        Plan $plan,
        ?int $userId = null,
        string $status = 'active',
    ): Subscription {
        return DB::transaction(function () use ($organizationId, $plan, $userId, $status): Subscription {
            $account = BillingAccount::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organizationId],
                ['provider' => config('billing.provider', 'mock')],
            );

            $this->provider->ensureCustomer($account);

            $periodStart = now()->startOfMonth()->toDateString();
            $periodEnd = now()->endOfMonth()->toDateString();

            $subscription = Subscription::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->latest('id')
                ->first();

            if ($subscription === null) {
                $subscription = Subscription::withoutGlobalScopes()->create([
                    'organization_id' => $organizationId,
                    'plan_id' => $plan->id,
                    'billing_account_id' => $account->id,
                    'status' => $status,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                ]);
            } else {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'billing_account_id' => $account->id,
                    'status' => $status,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'cancelled_at' => null,
                    'cancel_at_period_end' => false,
                ]);
                $subscription->refresh();
            }

            $subscription = $this->provider->activateSubscription($subscription);

            $this->auditService->record(
                action: 'billing.subscription.changed',
                organizationId: $organizationId,
                userId: $userId,
                auditable: $subscription,
                newValues: [
                    'plan_slug' => $plan->slug,
                    'status' => $subscription->status,
                ],
            );

            if ($userId !== null) {
                $user = \App\Models\User::find($userId);
                if ($user !== null) {
                    app(\App\Services\Welcome\WelcomeWorkflowService::class)
                        ->dispatchSubscriptionWelcome($user, $organizationId, $plan->name);
                }
            }

            return $subscription->fresh(['plan.features']);
        });
    }

    public function cancel(int $organizationId, ?int $userId = null, bool $atPeriodEnd = true): Subscription
    {
        $subscription = $this->currentSubscription($organizationId);
        abort_if($subscription === null, 404, 'Subscription not found.');

        $updated = $this->provider->cancelSubscription($subscription, $atPeriodEnd);

        $this->auditService->record(
            action: 'billing.subscription.cancelled',
            organizationId: $organizationId,
            userId: $userId,
            auditable: $updated,
            newValues: [
                'cancel_at_period_end' => $atPeriodEnd,
                'status' => $updated->status,
            ],
        );

        return $updated->fresh(['plan.features']);
    }

    public function reactivate(int $organizationId, ?int $userId = null): Subscription
    {
        $subscription = $this->currentSubscription($organizationId);
        abort_if($subscription === null, 404, 'Subscription not found.');

        $updated = $this->provider->reactivateSubscription($subscription);

        $this->auditService->record(
            action: 'billing.subscription.reactivated',
            organizationId: $organizationId,
            userId: $userId,
            auditable: $updated,
            newValues: ['status' => $updated->status],
        );

        return $updated->fresh(['plan.features']);
    }

    public function createInvoiceForCurrentPeriod(int $organizationId): BillingInvoice
    {
        $subscription = $this->currentSubscription($organizationId);
        abort_if($subscription === null, 404, 'Subscription not found.');

        $number = sprintf('INV-%d-%s', $organizationId, now()->format('Ym'));

        $invoice = BillingInvoice::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'subscription_id' => $subscription->id,
            'number' => $number,
            'status' => 'draft',
            'amount_cents' => 0,
            'currency' => config('billing.currency', 'USD'),
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'due_at' => now()->addDays(14),
        ]);

        return $this->provider->recordInvoice($invoice);
    }

    /** @return \Illuminate\Support\Collection<int, Plan> */
    public function activePlans()
    {
        return Plan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get();
    }

    public function provisionOrganization(Organization $organization): void
    {
        $this->ensureDefaultSubscription($organization->id);
    }
}
