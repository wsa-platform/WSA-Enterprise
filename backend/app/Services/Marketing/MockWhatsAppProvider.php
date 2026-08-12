<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use Illuminate\Support\Str;

class MockWhatsAppProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message, array $payload = []): MarketingSendResult
    {
        return new MarketingSendResult(
            success: true,
            provider: 'mock_whatsapp',
            providerMessageId: 'mock_wa_'.Str::uuid(),
        );
    }
}
