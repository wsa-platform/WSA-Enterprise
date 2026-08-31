<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Providers\ConnectableProviderInterface;

class UnconfiguredEmailProvider implements ConnectableProviderInterface, EmailProviderInterface
{
    public function __construct(private ProviderStatusService $status) {}

    public function isConfigured(): bool
    {
        return $this->status->emailConfigured();
    }

    public function connectionStatus(): array
    {
        return $this->status->email();
    }

    public function testConnection(): array
    {
        return ['success' => false, 'error' => config('providers.status_label_unconfigured')];
    }

    public function send(string $to, string $subject, string $body, array $payload = []): MarketingSendResult
    {
        return new MarketingSendResult(
            success: false,
            provider: 'none',
            errorCode: 'not_configured',
            errorMessage: config('providers.status_label_disconnected'),
        );
    }
}
