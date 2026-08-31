<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\SmsProviderInterface;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use App\Services\Providers\PostmarkEmailProvider;
use App\Services\Providers\ProviderStatusService;
use App\Services\Providers\ResendEmailProvider;
use App\Services\Providers\TwilioSmsProvider;
use App\Services\Providers\UnconfiguredEmailProvider;
use App\Services\Providers\UnconfiguredSmsProvider;
use App\Services\Providers\UnconfiguredWhatsAppProvider;
use App\Services\Providers\WhatsAppCloudProvider;

class MarketingProviderResolver
{
    public function __construct(
        private MockSmsProvider $sms,
        private MockEmailProvider $email,
        private MockWhatsAppProvider $whatsapp,
        private UnconfiguredSmsProvider $unconfiguredSms,
        private UnconfiguredEmailProvider $unconfiguredEmail,
        private UnconfiguredWhatsAppProvider $unconfiguredWhatsapp,
        private ResendEmailProvider $resendEmail,
        private PostmarkEmailProvider $postmarkEmail,
        private TwilioSmsProvider $twilioSms,
        private WhatsAppCloudProvider $whatsappCloud,
        private ProviderStatusService $status,
    ) {}

    public function sms(): SmsProviderInterface
    {
        if (! $this->status->smsConfigured()) {
            return $this->unconfiguredSms;
        }

        return match ((string) config('marketing.providers.sms', 'none')) {
            'mock' => $this->sms,
            'twilio' => $this->twilioSms,
            default => $this->unconfiguredSms,
        };
    }

    public function email(): EmailProviderInterface
    {
        if (! $this->status->emailConfigured()) {
            return $this->unconfiguredEmail;
        }

        return match ((string) config('marketing.providers.email', 'none')) {
            'mock' => $this->email,
            'resend' => $this->resendEmail,
            'postmark' => $this->postmarkEmail,
            default => $this->unconfiguredEmail,
        };
    }

    public function whatsapp(): WhatsAppProviderInterface
    {
        if (! $this->status->whatsappConfigured()) {
            return $this->unconfiguredWhatsapp;
        }

        return match ((string) config('marketing.providers.whatsapp', 'none')) {
            'mock' => $this->whatsapp,
            'whatsapp' => $this->whatsappCloud,
            default => $this->unconfiguredWhatsapp,
        };
    }
}
