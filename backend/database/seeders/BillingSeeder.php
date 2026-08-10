<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Services\Billing\SubscriptionService;
use Illuminate\Database\Seeder;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            'free' => [
                'name' => 'Free',
                'description' => 'Core agricultural modules with limited AI usage.',
                'sort_order' => 1,
                'features' => [
                    ['feature_key' => 'ai.requests', 'limit_value' => 50, 'limit_period' => 'monthly'],
                    ['feature_key' => 'users.max', 'limit_value' => 5, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'modules.business', 'limit_value' => 0, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'ai.use', 'limit_value' => 1, 'limit_period' => 'lifetime'],
                ],
            ],
            'pro' => [
                'name' => 'Pro',
                'description' => 'All modules with expanded AI and team capacity.',
                'sort_order' => 2,
                'features' => [
                    ['feature_key' => 'ai.requests', 'limit_value' => 500, 'limit_period' => 'monthly'],
                    ['feature_key' => 'users.max', 'limit_value' => 25, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'modules.business', 'limit_value' => 1, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'ai.use', 'limit_value' => 1, 'limit_period' => 'lifetime'],
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'Unlimited usage and full enterprise operations.',
                'sort_order' => 3,
                'features' => [
                    ['feature_key' => 'ai.requests', 'limit_value' => null, 'limit_period' => 'monthly'],
                    ['feature_key' => 'users.max', 'limit_value' => null, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'modules.business', 'limit_value' => 1, 'limit_period' => 'lifetime'],
                    ['feature_key' => 'ai.use', 'limit_value' => 1, 'limit_period' => 'lifetime'],
                ],
            ],
        ];

        foreach ($plans as $slug => $definition) {
            $plan = Plan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );

            foreach ($definition['features'] as $feature) {
                PlanFeature::updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_key' => $feature['feature_key']],
                    [
                        'limit_value' => $feature['limit_value'],
                        'limit_period' => $feature['limit_period'],
                    ],
                );
            }
        }

        if (! config('billing.enabled', false)) {
            return;
        }

        $subscriptionService = app(SubscriptionService::class);
        Organization::query()->each(function (Organization $organization) use ($subscriptionService): void {
            $subscriptionService->ensureDefaultSubscription($organization->id);
        });
    }
}
