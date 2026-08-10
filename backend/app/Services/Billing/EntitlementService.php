<?php

namespace App\Services\Billing;

use App\Exceptions\PlanRestrictionException;
use App\Exceptions\SubscriptionInactiveException;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Audit\AuditService;

class EntitlementService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private AuditService $auditService,
    ) {}

    public function billingEnabled(): bool
    {
        return (bool) config('billing.enabled', false);
    }

    public function assertSubscriptionActive(int $organizationId, ?int $userId = null): void
    {
        if (! $this->billingEnabled()) {
            return;
        }

        $subscription = $this->subscriptionService->currentSubscription($organizationId);

        if ($subscription === null || ! $subscription->isActive()) {
            $this->auditService->record(
                action: 'billing.subscription.inactive',
                organizationId: $organizationId,
                userId: $userId,
                newValues: ['status' => $subscription?->status ?? 'missing'],
            );

            throw new SubscriptionInactiveException($organizationId, $subscription?->status ?? 'missing');
        }
    }

    public function canUseFeature(int $organizationId, string $featureKey): bool
    {
        if (! $this->billingEnabled()) {
            return true;
        }

        $subscription = $this->subscriptionService->currentSubscription($organizationId);
        if ($subscription === null || ! $subscription->isActive()) {
            return false;
        }

        $feature = $this->featureForSubscription($subscription, $featureKey);
        if ($feature === null) {
            return false;
        }

        if ($feature->limit_value === null) {
            return true;
        }

        return $feature->limit_value > 0;
    }

    public function getLimit(int $organizationId, string $featureKey): ?int
    {
        if (! $this->billingEnabled()) {
            return null;
        }

        $subscription = $this->subscriptionService->currentSubscription($organizationId);
        if ($subscription === null) {
            return null;
        }

        $feature = $this->featureForSubscription($subscription, $featureKey);

        return $feature?->limit_value;
    }

    public function assertFeatureAllowed(int $organizationId, string $featureKey, ?int $userId = null): void
    {
        $this->assertSubscriptionActive($organizationId, $userId);

        if (! $this->billingEnabled()) {
            return;
        }

        if (! $this->canUseFeature($organizationId, $featureKey)) {
            $this->auditService->record(
                action: 'billing.feature.denied',
                organizationId: $organizationId,
                userId: $userId,
                newValues: ['feature_key' => $featureKey],
            );

            throw new PlanRestrictionException($organizationId, $featureKey);
        }
    }

    public function assertUserCapacity(int $organizationId, ?int $userId = null): void
    {
        if (! $this->billingEnabled()) {
            return;
        }

        $limit = $this->getLimit($organizationId, 'users.max');
        if ($limit === null) {
            return;
        }

        $count = User::query()
            ->whereHas('organizations', fn ($query) => $query->whereKey($organizationId))
            ->count();

        if ($count >= $limit) {
            throw new PlanRestrictionException(
                $organizationId,
                'users.max',
                'Organization user limit reached for the current plan.',
            );
        }
    }

    /** @return array<string, mixed> */
    public function summaryForOrganization(int $organizationId): array
    {
        $subscription = $this->subscriptionService->currentSubscription($organizationId);
        $plan = $subscription?->plan;

        return [
            'enabled' => $this->billingEnabled(),
            'subscription_active' => $subscription?->isActive() ?? ! $this->billingEnabled(),
            'plan' => $plan ? [
                'slug' => $plan->slug,
                'name' => $plan->name,
            ] : null,
            'features' => $plan
                ? $plan->features->mapWithKeys(fn (PlanFeature $feature) => [
                    $feature->feature_key => [
                        'limit' => $feature->limit_value,
                        'period' => $feature->limit_period,
                    ],
                ])->all()
                : [],
        ];
    }

    private function featureForSubscription(Subscription $subscription, string $featureKey): ?PlanFeature
    {
        $subscription->loadMissing('plan.features');

        return $subscription->plan?->features->firstWhere('feature_key', $featureKey);
    }
}
