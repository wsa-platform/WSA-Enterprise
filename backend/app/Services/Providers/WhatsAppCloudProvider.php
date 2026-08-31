<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use App\Contracts\Providers\ConnectableProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsAppCloudProvider implements ConnectableProviderInterface, WhatsAppProviderInterface
{
    public function isConfigured(): bool
    {
        return (string) config('marketing.providers.whatsapp') === 'whatsapp'
            && filled(config('providers.whatsapp.token'))
            && filled(config('providers.whatsapp.phone_id'));
    }

    public function connectionStatus(): array
    {
        return app(ProviderStatusService::class)->whatsapp();
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];
        }

        $phoneId = (string) config('providers.whatsapp.phone_id');
        $response = Http::withToken((string) config('providers.whatsapp.token'))
            ->acceptJson()
            ->get("https://graph.facebook.com/v18.0/{$phoneId}");

        if ($response->successful()) {
            return ['success' => true, 'message' => 'WhatsApp Cloud API reachable.'];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? $response->body() ?: 'WhatsApp connection failed.',
        ];
    }

    public function send(string $to, string $message, array $payload = []): MarketingSendResult
    {
        if (! $this->isConfigured()) {
            return new MarketingSendResult(
                success: false,
                provider: 'whatsapp',
                errorCode: 'not_configured',
                errorMessage: config('providers.status_label_unconfigured'),
            );
        }

        $phoneId = (string) config('providers.whatsapp.phone_id');
        $response = Http::withToken((string) config('providers.whatsapp.token'))
            ->acceptJson()
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->successful()) {
            $messageId = $response->json('messages.0.id');

            return new MarketingSendResult(
                success: true,
                provider: 'whatsapp',
                providerMessageId: is_string($messageId) ? $messageId : (string) Str::uuid(),
            );
        }

        return new MarketingSendResult(
            success: false,
            provider: 'whatsapp',
            errorCode: (string) $response->status(),
            errorMessage: $response->json('error.message') ?? $response->body() ?: config('providers.status_label_disconnected'),
        );
    }
}
