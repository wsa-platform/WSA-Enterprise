<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Contracts\BillingProviderInterface;
use App\Services\Ai\AiProviderResolver;
use App\Services\Ai\AiQuotaService;
use App\Services\Ai\AiRequestValidator;
use App\Services\Ai\AiResponseNormalizer;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\MockAiProvider;
use App\Services\Audit\AuditService;
use App\Services\Billing\BillingUsageService;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\MockBillingProvider;
use App\Services\Billing\OrganizationSettingsService;
use App\Services\Billing\SubscriptionService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Tenancy\TenantContext::class);

        $this->app->singleton(AiProviderResolver::class);
        $this->app->singleton(AiUsageRecorder::class);
        $this->app->singleton(AiQuotaService::class);

        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(EntitlementService::class);
        $this->app->singleton(BillingUsageService::class);
        $this->app->singleton(OrganizationSettingsService::class);
        $this->app->singleton(NotificationService::class);

        $this->app->bind(BillingProviderInterface::class, MockBillingProvider::class);

        $this->app->bind(AiProviderInterface::class, fn () => app(AiProviderResolver::class)->forOrganization(null));

        $this->app->bind(MockAiProvider::class);

        $this->app->singleton(AiService::class, fn ($app) => new AiService(
            $app->make(AiProviderResolver::class),
            $app->make(AiRequestValidator::class),
            $app->make(AiResponseNormalizer::class),
            $app->make(AuditService::class),
            $app->make(AiQuotaService::class),
            $app->make(AiUsageRecorder::class),
            $app->make(NotificationService::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}
