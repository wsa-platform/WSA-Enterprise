<?php

namespace App\Services\Jobs;

use App\Contracts\JobsPaymentProviderInterface;
use App\Models\JobContactTransaction;
use Illuminate\Support\Str;

class MockJobsPaymentProvider implements JobsPaymentProviderInterface
{
    public function charge(JobContactTransaction $transaction, string $idempotencyKey): JobContactTransaction
    {
        if ($transaction->payment_status === 'completed') {
            return $transaction;
        }

        if (config('jobs.contact_exchange.force_fail', false) || str_starts_with($idempotencyKey, 'fail-')) {
            $transaction->update([
                'payment_provider' => config('jobs.contact_exchange.payment_provider', 'mock'),
                'payment_reference' => 'mock_fail_'.Str::uuid(),
                'payment_status' => 'failed',
                'idempotency_key' => $idempotencyKey,
            ]);

            return $transaction->fresh();
        }

        $transaction->update([
            'payment_provider' => config('jobs.contact_exchange.payment_provider', 'mock'),
            'payment_reference' => 'mock_pay_'.Str::uuid(),
            'payment_status' => 'completed',
            'idempotency_key' => $idempotencyKey,
        ]);

        return $transaction->fresh();
    }
}
