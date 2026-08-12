<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\MarketingSendResult;
use Illuminate\Support\Str;

class MockEmailProvider implements EmailProviderInterface
{
    public function send(string $to, string $subject, string $body, array $payload = []): MarketingSendResult
    {
        return new MarketingSendResult(
            success: true,
            provider: 'mock_email',
            providerMessageId: 'mock_email_'.Str::uuid(),
        );
    }
}
