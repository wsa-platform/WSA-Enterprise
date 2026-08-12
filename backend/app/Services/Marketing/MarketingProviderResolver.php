<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\SmsProviderInterface;
use App\Contracts\Marketing\WhatsAppProviderInterface;

class MarketingProviderResolver
{
    public function __construct(
        private MockSmsProvider $sms,
        private MockEmailProvider $email,
        private MockWhatsAppProvider $whatsapp,
    ) {}

    public function sms(): SmsProviderInterface
    {
        return match (config('marketing.providers.sms', 'mock')) {
            'mock' => $this->sms,
            default => $this->sms,
        };
    }

    public function email(): EmailProviderInterface
    {
        return match (config('marketing.providers.email', 'mock')) {
            'mock' => $this->email,
            default => $this->email,
        };
    }

    public function whatsapp(): WhatsAppProviderInterface
    {
        return match (config('marketing.providers.whatsapp', 'mock')) {
            'mock' => $this->whatsapp,
            default => $this->whatsapp,
        };
    }
}
