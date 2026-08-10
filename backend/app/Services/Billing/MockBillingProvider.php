<?php

namespace App\Services\Billing;

use App\Contracts\BillingProviderInterface;
use App\Models\BillingAccount;
use App\Models\BillingInvoice;
use App\Models\Subscription;
use Illuminate\Support\Str;

class MockBillingProvider implements BillingProviderInterface
{
    public function ensureCustomer(BillingAccount $account): BillingAccount
    {
        if ($account->external_customer_id === null) {
            $account->update([
                'external_customer_id' => 'mock_cus_'.Str::uuid(),
            ]);
        }

        return $account->fresh();
    }

    public function activateSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'external_subscription_id' => $subscription->external_subscription_id ?? 'mock_sub_'.Str::uuid(),
            'cancelled_at' => null,
            'cancel_at_period_end' => false,
        ]);

        return $subscription->fresh();
    }

    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = false): Subscription
    {
        if ($atPeriodEnd) {
            $subscription->update([
                'cancel_at_period_end' => true,
            ]);

            return $subscription->fresh();
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_at_period_end' => false,
        ]);

        return $subscription->fresh();
    }

    public function reactivateSubscription(Subscription $subscription): Subscription
    {
        return $this->activateSubscription($subscription);
    }

    public function recordInvoice(BillingInvoice $invoice): BillingInvoice
    {
        $invoice->update([
            'external_invoice_id' => $invoice->external_invoice_id ?? 'mock_inv_'.Str::uuid(),
            'status' => $invoice->status === 'draft' ? 'open' : $invoice->status,
        ]);

        return $invoice->fresh();
    }

    public function syncSubscription(Subscription $subscription): Subscription
    {
        return $subscription->fresh(['plan.features']);
    }
}
