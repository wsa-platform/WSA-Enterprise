<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Services\Ai\AiProviderResolver;
use App\Services\Ai\AiQuotaService;
use App\Services\Ai\AiRequestValidator;
use App\Services\Ai\AiResponseNormalizer;
use App\Services\Ai\AiService;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\MockAiProvider;
use App\Services\Audit\AuditService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Tenancy\TenantContext::class);

        $this->app->singleton(AiProviderResolver::class);
        $this->app->singleton(AiUsageRecorder::class);
        $this->app->singleton(AiQuotaService::class);

        $this->app->bind(AiProviderInterface::class, fn () => app(AiProviderResolver::class)->forOrganization(null));

        $this->app->bind(MockAiProvider::class);

        $this->app->singleton(AiService::class, fn ($app) => new AiService(
            $app->make(AiProviderResolver::class),
            $app->make(AiRequestValidator::class),
            $app->make(AiResponseNormalizer::class),
            $app->make(AuditService::class),
            $app->make(AiQuotaService::class),
            $app->make(AiUsageRecorder::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}
