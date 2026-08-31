<?php

namespace App\Services\Providers;

use App\Contracts\Marketing\MarketingSendResult;
use App\Contracts\Marketing\SmsProviderInterface;
use App\Contracts\Providers\ConnectableProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TwilioSmsProvider implements ConnectableProviderInterface, SmsProviderInterface
{
    public function isConfigured(): bool
    {
        return (string) config('marketing.providers.sms') === 'twilio'
            && filled(config('providers.sms.twilio_sid'))
            && filled(config('providers.sms.twilio_token'))
            && filled(config('providers.sms.twilio_from'));
    }

    public function connectionStatus(): array
    {
        return app(ProviderStatusService::class)->sms();
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];
        }

        $sid = (string) config('providers.sms.twilio_sid');
        $response = Http::withBasicAuth($sid, (string) config('providers.sms.twilio_token'))
            ->acceptJson()
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

        if ($response->successful()) {
            return ['success' => true, 'message' => 'Twilio account reachable.'];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? $response->body() ?: 'Twilio connection failed.',
        ];
    }

    public function send(string $to, string $message, array $payload = []): MarketingSendResult
    {
        if (! $this->isConfigured()) {
            return new MarketingSendResult(
                success: false,
                provider: 'twilio',
                errorCode: 'not_configured',
                errorMessage: config('providers.status_label_unconfigured'),
            );
        }

        $sid = (string) config('providers.sms.twilio_sid');
        $from = (string) ($payload['from'] ?? config('providers.sms.twilio_from'));

        $response = Http::asForm()
            ->withBasicAuth($sid, (string) config('providers.sms.twilio_token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return new MarketingSendResult(
                success: true,
                provider: 'twilio',
                providerMessageId: (string) ($response->json('sid') ?? Str::uuid()),
            );
        }

        return new MarketingSendResult(
            success: false,
            provider: 'twilio',
            errorCode: (string) $response->status(),
            errorMessage: $response->json('message') ?? $response->body() ?: config('providers.status_label_disconnected'),
        );
    }
}
