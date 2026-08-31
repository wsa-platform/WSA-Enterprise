<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Providers\ConnectableProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ResendEmailProvider implements ConnectableProviderInterface, EmailProviderInterface
{
    public function isConfigured(): bool
    {
        return (string) config('marketing.providers.email') === 'resend'
            && filled(config('providers.email.resend_key'));
    }

    public function connectionStatus(): array
    {
        return app(ProviderStatusService::class)->email();
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];
        }

        $response = Http::withToken((string) config('providers.email.resend_key'))
            ->acceptJson()
            ->get('https://api.resend.com/domains');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Resend API reachable.'];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? $response->body() ?: 'Resend connection failed.',
        ];
    }

    public function send(string $to, string $subject, string $body, array $payload = []): MarketingSendResult
    {
        if (! $this->isConfigured()) {
            return new MarketingSendResult(
                success: false,
                provider: 'resend',
                errorCode: 'not_configured',
                errorMessage: config('providers.status_label_unconfigured'),
            );
        }

        $from = (string) ($payload['from'] ?? config('mail.from.address', 'onboarding@resend.dev'));

        $response = Http::withToken((string) config('providers.email.resend_key'))
            ->acceptJson()
            ->post('https://api.resend.com/emails', [
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $body,
            ]);

        if ($response->successful()) {
            return new MarketingSendResult(
                success: true,
                provider: 'resend',
                providerMessageId: (string) ($response->json('id') ?? Str::uuid()),
            );
        }

        return new MarketingSendResult(
            success: false,
            provider: 'resend',
            errorCode: (string) $response->status(),
            errorMessage: $response->json('message') ?? $response->body() ?: config('providers.status_label_disconnected'),
        );
    }
}
