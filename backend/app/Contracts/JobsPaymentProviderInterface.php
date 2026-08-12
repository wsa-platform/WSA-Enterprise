<?php

namespace App\Contracts;

use App\Models\JobContactTransaction;

interface JobsPaymentProviderInterface
{
    public function charge(JobContactTransaction $transaction, string $idempotencyKey): JobContactTransaction;
}
