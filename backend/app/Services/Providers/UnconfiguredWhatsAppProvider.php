<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use App\Contracts\Providers\ConnectableProviderInterface;

class UnconfiguredWhatsAppProvider implements ConnectableProviderInterface, WhatsAppProviderInterface
{
    public function __construct(private ProviderStatusService $status) {}

    public function isConfigured(): bool
    {
        return $this->status->whatsappConfigured();
    }

    public function connectionStatus(): array
    {
        return $this->status->whatsapp();
    }

    public function testConnection(): array
    {
        return ['success' => false, 'error' => config('providers.status_label_unconfigured')];
    }

    public function send(string $to, string $message, array $payload = []): MarketingSendResult
    {
        return new MarketingSendResult(
            success: false,
            provider: 'none',
            errorCode: 'not_configured',
            errorMessage: config('providers.status_label_disconnected'),
        );
    }
}
