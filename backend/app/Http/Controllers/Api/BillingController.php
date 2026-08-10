<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Services\Billing\BillingUsageService;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\OrganizationSettingsService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function __construct(
        private SubscriptionService $subscriptionService,
        private BillingUsageService $billingUsageService,
        private EntitlementService $entitlementService,
        private OrganizationSettingsService $organizationSettingsService,
    ) {}

    public function plans(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.view');

        return response()->json($this->subscriptionService->activePlans());
    }

    public function subscription(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.view');
        $organizationId = $this->organization($request);

        $subscription = $this->subscriptionService->currentSubscription($organizationId)
            ?? $this->subscriptionService->ensureDefaultSubscription($organizationId);

        return response()->json([
            'subscription' => $subscription->load('plan.features'),
            'billing_account' => $subscription->billingAccount,
            'entitlements' => $this->entitlementService->summaryForOrganization($organizationId),
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.view');

        return response()->json(
            $this->billingUsageService->summaryForOrganization($this->organization($request))
        );
    }

    public function assignPlan(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $plan = Plan::query()->where('slug', $data['plan_slug'])->where('is_active', true)->firstOrFail();

        $subscription = $this->subscriptionService->assignPlan(
            $organizationId,
            $plan,
            $request->user()->id,
        );

        return response()->json($subscription->load('plan.features'), 200);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'at_period_end' => ['sometimes', 'boolean'],
        ]);

        $subscription = $this->subscriptionService->cancel(
            $organizationId,
            $request->user()->id,
            $data['at_period_end'] ?? true,
        );

        return response()->json($subscription->load('plan.features'));
    }

    public function reactivate(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.manage');

        $subscription = $this->subscriptionService->reactivate(
            $this->organization($request),
            $request->user()->id,
        );

        return response()->json($subscription->load('plan.features'));
    }

    public function invoices(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.view');

        return $this->paginateQuery(
            $request,
            BillingInvoice::query()->with('payments')->latest()
        );
    }

    public function settings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.view');

        return response()->json(
            $this->organizationSettingsService->allForOrganization($this->organization($request))
        );
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'billing.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        return response()->json(
            $this->organizationSettingsService->updateForOrganization(
                $organizationId,
                $data['settings'],
                $request->user()->id,
                $request,
            )
        );
    }
}
