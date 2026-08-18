<?php

namespace App\Contracts;

use App\Models\ContactAccessOrder;

interface MarketplacePaymentProviderInterface
{
    public function charge(ContactAccessOrder $order, string $idempotencyKey): ContactAccessOrder;
}
