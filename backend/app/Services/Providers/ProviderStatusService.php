<?php



namespace App\Services\Providers;



use App\Models\ProviderConnection;

use Illuminate\Support\Facades\Http;



class ProviderStatusService

{

    public function __construct(

        private ResendEmailProvider $resendEmail,

        private PostmarkEmailProvider $postmarkEmail,

        private TwilioSmsProvider $twilioSms,

        private WhatsAppCloudProvider $whatsappCloud,

    ) {}



    /** @return array<string, array<string, mixed>> */

    public function all(): array

    {

        $providers = [];

        foreach (config('providers.keys', []) as $key) {

            $providers[$key] = $this->forKey($key);

        }



        return $providers;

    }



    /** @return array<string, mixed> */

    public function forKey(string $key): array

    {

        return match ($key) {

            'email' => $this->buildStatus('email', fn () => $this->emailDriver(), fn () => $this->emailConfigured()),

            'sms' => $this->buildStatus('sms', fn () => $this->smsDriver(), fn () => $this->smsConfigured()),

            'whatsapp' => $this->buildStatus('whatsapp', fn () => $this->whatsappDriver(), fn () => $this->whatsappConfigured()),

            'meta' => $this->buildStatus('meta', fn () => 'meta', fn () => $this->metaConfigured()),

            'google_oauth' => $this->buildStatus('google_oauth', fn () => 'google', fn () => $this->googleOAuthConfigured()),

            'facebook_oauth' => $this->buildStatus('facebook_oauth', fn () => 'facebook', fn () => $this->facebookOAuthConfigured()),

            'ai' => $this->buildStatus('ai', fn () => $this->aiDriver(), fn () => $this->aiConfigured()),

            default => $this->unknownStatus($key),

        };

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function email(): array

    {

        $status = $this->forKey('email');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function sms(): array

    {

        $status = $this->forKey('sms');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function whatsapp(): array

    {

        $status = $this->forKey('whatsapp');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function meta(): array

    {

        $status = $this->forKey('meta');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function googleOAuth(): array

    {

        $status = $this->forKey('google_oauth');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function facebookOAuth(): array

    {

        $status = $this->forKey('facebook_oauth');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array{connected: bool, label: string, provider: string} */

    public function ai(): array

    {

        $status = $this->forKey('ai');



        return [

            'connected' => (bool) $status['connected'],

            'label' => (string) $status['label'],

            'provider' => (string) $status['provider'],

        ];

    }



    /** @return array<string, mixed> */

    public function test(string $key): array

    {

        if (! in_array($key, config('providers.keys', []), true)) {

            return [

                'provider' => $key,

                'success' => false,

                'error' => 'Unknown provider.',

            ];

        }



        $configured = match ($key) {

            'email' => $this->emailConfigured(),

            'sms' => $this->smsConfigured(),

            'whatsapp' => $this->whatsappConfigured(),

            'meta' => $this->metaConfigured(),

            'google_oauth' => $this->googleOAuthConfigured(),

            'facebook_oauth' => $this->facebookOAuthConfigured(),

            'ai' => $this->aiConfigured(),

            default => false,

        };



        if (! $configured) {

            $this->recordTest($key, false, config('providers.status_label_unconfigured'));



            return [

                'provider' => $key,

                'success' => false,

                'configured' => false,

                'connected' => false,

                'label' => config('providers.status_label_unconfigured'),

                'error' => config('providers.status_label_unconfigured'),

            ];

        }



        $result = match ($key) {

            'email' => $this->testEmail(),

            'sms' => $this->testSms(),

            'whatsapp' => $this->testWhatsapp(),

            'meta' => $this->testMeta(),

            'google_oauth' => $this->testGoogleOAuth(),

            'facebook_oauth' => $this->testFacebookOAuth(),

            'ai' => ['success' => true, 'message' => 'AI provider available.'],

            default => ['success' => false, 'error' => 'Unsupported provider.'],

        };



        $this->recordTest(

            $key,

            (bool) ($result['success'] ?? false),

            $result['error'] ?? null,

        );



        $status = $this->forKey($key);



        return [

            'provider' => $key,

            'success' => (bool) ($result['success'] ?? false),

            'message' => $result['message'] ?? null,

            'error' => $result['error'] ?? null,

            ...$status,

        ];

    }



    public function emailConfigured(): bool

    {

        $driver = $this->emailDriver();



        if ($driver === 'mock') {

            return app()->environment(['local', 'testing']);

        }



        if ($driver === 'none' || $driver === '') {

            return false;

        }



        return match ($driver) {

            'resend' => filled(config('providers.email.resend_key')),

            'postmark' => filled(config('providers.email.postmark_key')),

            default => false,

        };

    }



    public function smsConfigured(): bool

    {

        $driver = $this->smsDriver();



        if ($driver === 'mock') {

            return app()->environment(['local', 'testing']);

        }



        if ($driver === 'none' || $driver === '') {

            return false;

        }



        return match ($driver) {

            'twilio' => filled(config('providers.sms.twilio_sid'))

                && filled(config('providers.sms.twilio_token'))

                && filled(config('providers.sms.twilio_from')),

            default => false,

        };

    }



    public function whatsappConfigured(): bool

    {

        $driver = $this->whatsappDriver();



        if ($driver === 'mock') {

            return app()->environment(['local', 'testing']);

        }



        if ($driver === 'none' || $driver === '') {

            return false;

        }



        return match ($driver) {

            'whatsapp' => filled(config('providers.whatsapp.token')) && filled(config('providers.whatsapp.phone_id')),

            default => false,

        };

    }



    public function metaConfigured(): bool

    {

        return filled(config('providers.meta.app_id')) && filled(config('providers.meta.app_secret'));

    }



    public function googleOAuthConfigured(): bool

    {

        return filled(config('providers.google.client_id')) && filled(config('providers.google.client_secret'));

    }



    public function facebookOAuthConfigured(): bool

    {

        return filled(config('providers.facebook.client_id')) && filled(config('providers.facebook.client_secret'));

    }



    public function aiConfigured(): bool

    {

        $provider = $this->aiDriver();



        return $provider !== 'none' && ($provider === 'mock' ? app()->environment(['local', 'testing']) : true);

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testEmail(): array

    {

        return match ($this->emailDriver()) {

            'resend' => $this->resendEmail->testConnection(),

            'postmark' => $this->postmarkEmail->testConnection(),

            'mock' => ['success' => true, 'message' => 'Mock email provider.'],

            default => ['success' => false, 'error' => config('providers.status_label_unconfigured')],

        };

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testSms(): array

    {

        return match ($this->smsDriver()) {

            'twilio' => $this->twilioSms->testConnection(),

            'mock' => ['success' => true, 'message' => 'Mock SMS provider.'],

            default => ['success' => false, 'error' => config('providers.status_label_unconfigured')],

        };

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testWhatsapp(): array

    {

        return match ($this->whatsappDriver()) {

            'whatsapp' => $this->whatsappCloud->testConnection(),

            'mock' => ['success' => true, 'message' => 'Mock WhatsApp provider.'],

            default => ['success' => false, 'error' => config('providers.status_label_unconfigured')],

        };

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testMeta(): array

    {

        if (! $this->metaConfigured()) {

            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];

        }



        $appId = (string) config('providers.meta.app_id');

        $appSecret = (string) config('providers.meta.app_secret');

        $response = Http::acceptJson()->get("https://graph.facebook.com/v18.0/{$appId}", [

            'access_token' => "{$appId}|{$appSecret}",

            'fields' => 'id,name',

        ]);



        if ($response->successful()) {

            return ['success' => true, 'message' => 'Meta app credentials valid.'];

        }



        return [

            'success' => false,

            'error' => $response->json('error.message') ?? $response->body() ?: 'Meta connection failed.',

        ];

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testGoogleOAuth(): array

    {

        if (! $this->googleOAuthConfigured()) {

            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];

        }



        return ['success' => true, 'message' => 'Google OAuth credentials present.'];

    }



    /** @return array{success: bool, message?: string, error?: string} */

    private function testFacebookOAuth(): array

    {

        if (! $this->facebookOAuthConfigured()) {

            return ['success' => false, 'error' => config('providers.status_label_unconfigured')];

        }



        return ['success' => true, 'message' => 'Facebook OAuth credentials present.'];

    }



    /** @param  callable(): string  $driverResolver */

    /** @param  callable(): bool  $configuredResolver */

    /** @return array<string, mixed> */

    private function buildStatus(string $key, callable $driverResolver, callable $configuredResolver): array

    {

        $connection = ProviderConnection::forKey($key);

        $driver = $driverResolver();

        $configured = $configuredResolver();

        $enabled = (bool) $connection->enabled;

        $testPassed = $connection->last_test_status === ProviderConnection::STATUS_SUCCESS;

        $connected = $configured && $enabled && ($driver === 'mock' ? app()->environment(['local', 'testing']) : $testPassed);



        if (! $configured) {

            $label = config('providers.status_label_unconfigured');

            $configStatus = 'missing';

        } elseif ($connected) {

            $label = config('providers.status_label_connected');

            $configStatus = 'connected';

        } else {

            $label = config('providers.status_label_disconnected');

            $configStatus = $connection->last_test_status === ProviderConnection::STATUS_FAILED ? 'test_failed' : 'not_tested';

        }



        return [

            'name' => $key,

            'enabled' => $enabled,

            'configured' => $configured,

            'connected' => $connected,

            'config_status' => $configStatus,

            'label' => $label,

            'provider' => $configured ? $driver : 'none',

            'last_test_at' => $connection->last_test_at?->toIso8601String(),

            'last_test_status' => $connection->last_test_status,

            'last_test_error' => $connection->last_test_error,

        ];

    }



    /** @return array<string, mixed> */

    private function unknownStatus(string $key): array

    {

        return [

            'name' => $key,

            'enabled' => false,

            'configured' => false,

            'connected' => false,

            'config_status' => 'missing',

            'label' => config('providers.status_label_unconfigured'),

            'provider' => 'none',

            'last_test_at' => null,

            'last_test_status' => null,

            'last_test_error' => null,

        ];

    }



    private function recordTest(string $key, bool $success, ?string $error = null): void

    {

        $connection = ProviderConnection::forKey($key);

        $connection->update([

            'last_test_at' => now(),

            'last_test_status' => $success ? ProviderConnection::STATUS_SUCCESS : ProviderConnection::STATUS_FAILED,

            'last_test_error' => $success ? null : $error,

        ]);

    }



    private function emailDriver(): string

    {

        return (string) config('marketing.providers.email', config('providers.email.driver', 'none'));

    }



    private function smsDriver(): string

    {

        return (string) config('marketing.providers.sms', config('providers.sms.driver', 'none'));

    }



    private function whatsappDriver(): string

    {

        return (string) config('marketing.providers.whatsapp', config('providers.whatsapp.driver', 'none'));

    }



    private function aiDriver(): string

    {

        return (string) config('ai.provider', 'mock');

    }

}

