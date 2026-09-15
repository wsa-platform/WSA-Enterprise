<?php

namespace App\Providers;

use App\Contracts\Agriculture\McpToolClientInterface;
use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Adapters\Disease\FarmAdvisorDiseaseAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\FarmGuardDiseaseAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\GreenSenseDiseaseAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\HuggingFaceDiseaseAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\LeafDiseaseAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\PlantDiseaseDetectorAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Disease\PlantDiseaseWebAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Environmental\AgricultureMcpAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Environmental\AgriSignalMcpAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Environmental\OpenMeteoEnvironmentalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Execution\OctoPusExecutionAdapter;
use App\Services\Agriculture\Intelligence\Adapters\FieldSense\FieldSenseProvider;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimEvidenceFusion;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalClient;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalResultNormalizer;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainCatalog;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainScopedCodeResolver;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDomainVerifier;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStatResultNormalizer;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStatScientificSourceAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\ScientificAdapterBridgeProvider;
use App\Services\Agriculture\Intelligence\Adapters\Web\ConfigurableWebSearchProvider;
use App\Services\Agriculture\Intelligence\Adapters\Web\FreeSearchMcpAdapter;
use App\Services\Agriculture\Intelligence\Adapters\Web\WebSearchAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Fusion\EvidenceFusionService;
use App\Services\Agriculture\Intelligence\Fusion\WebConsensusService;
use App\Services\Agriculture\Intelligence\Health\ProviderHealthChecker;
use App\Services\Agriculture\Intelligence\Mcp\LaravelStdioMcpToolClient;
use App\Services\Agriculture\Intelligence\Normalization\AgriculturalResultNormalizer;
use App\Services\Agriculture\Intelligence\Normalization\DiseaseResultNormalizer;
use App\Services\Agriculture\Intelligence\Normalization\EnvironmentalResultNormalizer;
use App\Services\Agriculture\Intelligence\Normalization\FreeSearchMcpResultNormalizer;
use App\Services\Agriculture\Intelligence\Normalization\UnitNormalizationService;
use App\Services\Agriculture\Intelligence\Normalization\WebResultNormalizer;
use App\Services\Agriculture\Intelligence\Orchestration\AnswerEligibilityResolver;
use App\Services\Agriculture\Intelligence\Orchestration\CapabilityDrivenSourceSelector;
use App\Services\Agriculture\Intelligence\Orchestration\UniversalAnswerOrchestrator;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * ADR-001 / ADR-002 universal agricultural intelligence wiring.
 */
class AgriculturalIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UnitNormalizationService::class);
        $this->app->singleton(WebResultNormalizer::class);
        $this->app->singleton(FreeSearchMcpResultNormalizer::class);
        $this->app->singleton(DiseaseResultNormalizer::class);
        $this->app->singleton(EnvironmentalResultNormalizer::class);
        $this->app->singleton(AgriculturalResultNormalizer::class);
        $this->app->singleton(FaoStatResultNormalizer::class);
        $this->app->singleton(FaoStatDeveloperPortalTokenManager::class);
        $this->app->singleton(FaoStatDeveloperPortalClient::class);
        $this->app->singleton(FaoStatDeveloperPortalResultNormalizer::class);
        $this->app->singleton(FaoStatDeveloperPortalAdapter::class);
        $this->app->singleton(FaoStatDomainCatalog::class);
        $this->app->singleton(FaoStatDomainScopedCodeResolver::class);
        $this->app->singleton(FaoStatClaimSupportAssessor::class);
        $this->app->singleton(FaoStatDomainVerifier::class);
        $this->app->singleton(FaoStatClaimEvidenceFusion::class);
        $this->app->singleton(WebConsensusService::class);
        $this->app->singleton(EvidenceFusionService::class);
        $this->app->singleton(AnswerEligibilityResolver::class);
        $this->app->singleton(CapabilityDrivenSourceSelector::class);
        $this->app->singleton(ProviderHealthChecker::class);

        $this->app->singleton(LaravelStdioMcpToolClient::class);
        $this->app->singleton(McpToolClientInterface::class, LaravelStdioMcpToolClient::class);
        $this->app->singleton(FreeSearchMcpAdapter::class);
        $this->app->singleton(ConfigurableWebSearchProvider::class);
        $this->app->singleton(WebSearchProviderInterface::class, function ($app) {
            if (filter_var(config('agricultural_intelligence.mcp.free_search.enabled', false), FILTER_VALIDATE_BOOL)) {
                return $app->make(FreeSearchMcpAdapter::class);
            }

            return $app->make(ConfigurableWebSearchProvider::class);
        });
        $this->app->singleton(WebSearchAgriculturalProvider::class);
        $this->app->singleton(FaoStatScientificSourceAdapter::class);

        $this->app->singleton(AgriculturalProviderRegistry::class, function ($app) {
            $registry = new AgriculturalProviderRegistry;

            $registry->register($app->make(WebSearchAgriculturalProvider::class));
            $registry->register($app->make(OpenMeteoEnvironmentalAdapter::class));
            $registry->register($app->make(AgriSignalMcpAdapter::class));
            $registry->register($app->make(AgricultureMcpAdapter::class));
            $registry->register($app->make(FieldSenseProvider::class));
            $registry->register($app->make(OctoPusExecutionAdapter::class));

            foreach ([
                GreenSenseDiseaseAdapter::class,
                PlantDiseaseDetectorAdapter::class,
                LeafDiseaseAdapter::class,
                FarmAdvisorDiseaseAdapter::class,
                FarmGuardDiseaseAdapter::class,
                HuggingFaceDiseaseAdapter::class,
                PlantDiseaseWebAdapter::class,
            ] as $diseaseClass) {
                $registry->register($app->make($diseaseClass));
            }

            // Bridge existing scholarly adapters (reuse — do not duplicate SS/OA/CR)
            $registry->register(new ScientificAdapterBridgeProvider(
                $app->make(OpenAlexScientificSourceAdapter::class),
                priority: 10,
                enabled: (bool) config('agricultural_intelligence.openalex.enabled', true),
            ));
            $registry->register(new ScientificAdapterBridgeProvider(
                $app->make(CrossRefScientificSourceAdapter::class),
                priority: 11,
                enabled: (bool) config('agricultural_intelligence.crossref.enabled', true),
            ));
            $registry->register(new ScientificAdapterBridgeProvider(
                $app->make(SemanticScholarScientificSourceAdapter::class),
                priority: 12,
                enabled: (bool) config('agricultural_intelligence.semantic_scholar.enabled', true),
            ));
            $portalEnabled = filter_var(config('agricultural_intelligence.faostat.enabled', false), FILTER_VALIDATE_BOOL);
            $faoAdapter = $portalEnabled
                ? $app->make(FaoStatDeveloperPortalAdapter::class)
                : $app->make(FaoStatScientificSourceAdapter::class);
            $registry->register(new ScientificAdapterBridgeProvider(
                $faoAdapter,
                priority: 15,
                enabled: $portalEnabled || (bool) config('agricultural_intelligence.fao.enabled', true),
            ));

            return $registry;
        });

        $this->app->singleton(UniversalAnswerOrchestrator::class);
    }
}
