<?php

namespace App\Services\Marketplace;

use App\Contracts\MarketplacePaymentProviderInterface;
use App\Models\ContactAccessOrder;
use Illuminate\Support\Str;

class MockMarketplacePaymentProvider implements MarketplacePaymentProviderInterface
{
    public function charge(ContactAccessOrder $order, string $idempotencyKey): ContactAccessOrder
    {
        if ($order->payment_status === ContactAccessOrder::PAYMENT_PAID) {
            return $order;
        }

        if (config('marketplace.contact_access.force_fail', false) || str_starts_with($idempotencyKey, 'fail-')) {
            $order->update([
                'payment_provider' => config('marketplace.contact_access.payment_provider', 'mock'),
                'payment_reference' => 'mock_fail_'.Str::uuid(),
                'payment_status' => ContactAccessOrder::PAYMENT_FAILED,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $order->fresh();
        }

        $order->update([
            'payment_provider' => config('marketplace.contact_access.payment_provider', 'mock'),
            'payment_reference' => 'mock_pay_'.Str::uuid(),
            'payment_status' => ContactAccessOrder::PAYMENT_PAID,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $order->fresh();
    }
}
