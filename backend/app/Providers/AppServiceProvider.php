<?php

namespace App\Providers;

use App\Contracts\AiMonitoringAnalyzerInterface;
use App\Contracts\AiProviderInterface;
use App\Contracts\BillingProviderInterface;
use App\Contracts\JobsPaymentProviderInterface;
use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\SmsProviderInterface;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use App\Contracts\MarketplacePaymentProviderInterface;
use App\Services\Agriculture\Diagnosis\Image\DiagnosisImageValidator;
use App\Services\Agriculture\Diagnosis\Knowledge\DiagnosisKnowledgeSupportInterface;
use App\Services\Agriculture\Diagnosis\Knowledge\HeuristicDiagnosisKnowledgeSupport;
use App\Services\Agriculture\Diagnosis\Vision\AiProviderVisionAdapter;
use App\Services\Agriculture\Diagnosis\Vision\VisionAnalysisProviderInterface;
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
use App\Services\Jobs\MockJobsPaymentProvider;
use App\Services\Marketing\MarketingProviderResolver;
use App\Services\Marketplace\MockMarketplacePaymentProvider;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\MonitoringEventService;
use App\Services\Monitoring\RemediationService;
use App\Services\Monitoring\SafeRemediationExecutor;
use App\Services\Monitoring\StubAiMonitoringAnalyzer;
use App\Services\Notifications\NotificationService;
use App\Services\Providers\ProviderStatusService;
use App\Services\Tenancy\TenantContext;
use App\Services\Welcome\WelcomeWorkflowService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);

        $this->app->singleton(AiProviderResolver::class);
        $this->app->singleton(AiUsageRecorder::class);
        $this->app->singleton(AiQuotaService::class);

        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(EntitlementService::class);
        $this->app->singleton(BillingUsageService::class);
        $this->app->singleton(OrganizationSettingsService::class);
        $this->app->singleton(NotificationService::class);

        $this->app->singleton(HealthCheckService::class);
        $this->app->singleton(MonitoringEventService::class);
        $this->app->singleton(RemediationService::class);

        $this->app->bind(SafeRemediationExecutor::class, fn (): never => throw new \RuntimeException(
            'Resolve RemediationService for remediation actions.',
        ));

        $this->app->when(RemediationService::class)
            ->needs(SafeRemediationExecutor::class)
            ->give(fn ($app) => new SafeRemediationExecutor($app->make(HealthCheckService::class)));
        $this->app->bind(AiMonitoringAnalyzerInterface::class, StubAiMonitoringAnalyzer::class);

        $this->app->singleton(ProviderStatusService::class);
        $this->app->singleton(WelcomeWorkflowService::class);

        $this->app->bind(BillingProviderInterface::class, MockBillingProvider::class);
        $this->app->bind(JobsPaymentProviderInterface::class, MockJobsPaymentProvider::class);
        $this->app->bind(MarketplacePaymentProviderInterface::class, MockMarketplacePaymentProvider::class);
        $this->app->bind(SmsProviderInterface::class, fn ($app) => $app->make(MarketingProviderResolver::class)->sms());
        $this->app->bind(EmailProviderInterface::class, fn ($app) => $app->make(MarketingProviderResolver::class)->email());
        $this->app->bind(WhatsAppProviderInterface::class, fn ($app) => $app->make(MarketingProviderResolver::class)->whatsapp());

        $this->app->bind(AiProviderInterface::class, fn () => app(AiProviderResolver::class)->forOrganization(null));

        $this->app->bind(MockAiProvider::class);

        $this->app->bind(VisionAnalysisProviderInterface::class, function ($app) {
            return new AiProviderVisionAdapter($app->make(AiProviderInterface::class));
        });
        $this->app->bind(DiagnosisKnowledgeSupportInterface::class, HeuristicDiagnosisKnowledgeSupport::class);
        $this->app->singleton(DiagnosisImageValidator::class, function () {
            return new DiagnosisImageValidator(
                maxBytes: max(1, (int) config('wsa.plant_diagnosis.max_image_bytes', 5_242_880)),
            );
        });

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
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
