<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Providers\ConnectableProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PostmarkEmailProvider implements ConnectableProviderInterface, EmailProviderInterface
{
    public function isConfigured(): bool
    {
        return (string) config('marketing.providers.email') === 'postmark'
            && filled(config('providers.email.postmark_key'));
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

        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => (string) config('providers.email.postmark_key'),
            'Accept' => 'application/json',
        ])->get('https://api.postmarkapp.com/server');

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Postmark server reachable.'];
        }

        return [
            'success' => false,
            'error' => $response->json('Message') ?? $response->body() ?: 'Postmark connection failed.',
        ];
    }

    public function send(string $to, string $subject, string $body, array $payload = []): MarketingSendResult
    {
        if (! $this->isConfigured()) {
            return new MarketingSendResult(
                success: false,
                provider: 'postmark',
                errorCode: 'not_configured',
                errorMessage: config('providers.status_label_unconfigured'),
            );
        }

        $from = (string) ($payload['from'] ?? config('mail.from.address', 'hello@example.com'));

        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => (string) config('providers.email.postmark_key'),
            'Accept' => 'application/json',
        ])->post('https://api.postmarkapp.com/email', [
            'From' => $from,
            'To' => $to,
            'Subject' => $subject,
            'HtmlBody' => $body,
        ]);

        if ($response->successful()) {
            return new MarketingSendResult(
                success: true,
                provider: 'postmark',
                providerMessageId: (string) ($response->json('MessageID') ?? Str::uuid()),
            );
        }

        return new MarketingSendResult(
            success: false,
            provider: 'postmark',
            errorCode: (string) $response->status(),
            errorMessage: $response->json('Message') ?? $response->body() ?: config('providers.status_label_disconnected'),
        );
    }
}
