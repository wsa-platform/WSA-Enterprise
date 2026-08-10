<?php

namespace App\Contracts;

use App\Models\BillingAccount;
use App\Models\BillingInvoice;
use App\Models\Subscription;

interface BillingProviderInterface
{
    public function ensureCustomer(BillingAccount $account): BillingAccount;

    public function activateSubscription(Subscription $subscription): Subscription;

    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = false): Subscription;

    public function reactivateSubscription(Subscription $subscription): Subscription;

    public function recordInvoice(BillingInvoice $invoice): BillingInvoice;

    public function syncSubscription(Subscription $subscription): Subscription;
}
