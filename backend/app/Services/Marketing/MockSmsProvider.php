<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Marketing\SmsProviderInterface;
use Illuminate\Support\Str;

class MockSmsProvider implements SmsProviderInterface
{
    public function send(string $to, string $message, array $payload = []): MarketingSendResult
    {
        return new MarketingSendResult(
            success: true,
            provider: 'mock_sms',
            providerMessageId: 'mock_sms_'.Str::uuid(),
        );
    }
}
