<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Web;

use App\Contracts\Agriculture\AgriculturalProviderInterface;
use App\Contracts\Agriculture\WebSearchProviderInterface;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;

/**
 * Bridges WebSearchProviderInterface into the universal provider registry.
 */
final class WebSearchAgriculturalProvider implements AgriculturalProviderInterface
{
    public function __construct(
        private WebSearchProviderInterface $webSearch,
    ) {}

    public function descriptor(): ProviderDescriptor
    {
        return new ProviderDescriptor(
            id: $this->webSearch->providerId(),
            name: $this->webSearch->displayName(),
            type: ProviderType::WEB,
            capabilities: ['web_search', 'general_knowledge'],
            version: '1.0.0',
            priority: 50,
            timeoutSeconds: $this->timeoutSeconds(),
            health: $this->webSearch->isConfigured() ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: [
                'mode' => $this->authMode(),
                'configured' => $this->webSearch->isConfigured(),
            ],
            confidenceMeta: [
                'family' => 'web',
                'not_scientific' => true,
                'adapter' => $this->webSearch->providerId(),
            ],
            evidenceCapable: true,
            limitations: [
                'Not a scholarly source',
                'Distinct from Semantic Scholar / OpenAlex / Crossref / FAO',
            ],
            config: [
                'enabled' => $this->webFamilyEnabled(),
            ],
            enabled: $this->webFamilyEnabled(),
        );
    }

    public function health(): ProviderHealthStatus
    {
        if (! $this->webSearch->isConfigured()) {
            return new ProviderHealthStatus(
                $this->webSearch->providerId(),
                ProviderHealthState::NOT_CONFIGURED,
                'NOT_CONFIGURED',
            );
        }

        return new ProviderHealthStatus($this->webSearch->providerId(), ProviderHealthState::HEALTHY, 'ok');
    }

    public function retrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $outcome = $this->webSearch->search($input->query, $input->limit, $input->constraints);

        if ($outcome->status === WebSearchOutcome::STATUS_NOT_CONFIGURED) {
            return CanonicalAgriculturalResult::notConfigured($this->webSearch->providerId());
        }
        if ($outcome->status === WebSearchOutcome::STATUS_FAILED) {
            return CanonicalAgriculturalResult::failed($this->webSearch->providerId(), $outcome->error ?? 'failed');
        }
        if ($outcome->status === WebSearchOutcome::STATUS_EMPTY || $outcome->results === []) {
            return CanonicalAgriculturalResult::empty($this->webSearch->providerId(), $outcome->error ?? 'empty');
        }

        return new CanonicalAgriculturalResult(
            providerId: $this->webSearch->providerId(),
            status: 'success',
            webEvidence: $outcome->results,
            confidence: 0.45,
            source: $this->webSearch->providerId(),
            timestamp: now()->toIso8601String(),
            limitations: ['general_web_evidence_not_scientifically_verified'],
            meta: $outcome->observability,
        );
    }

    private function webFamilyEnabled(): bool
    {
        return (bool) config('agricultural_intelligence.web_search.enabled', false)
            || (bool) config('agricultural_intelligence.mcp.free_search.enabled', false);
    }

    private function timeoutSeconds(): int
    {
        if ((bool) config('agricultural_intelligence.mcp.free_search.enabled', false)) {
            $raw = (int) config('agricultural_intelligence.mcp.free_search.timeout', 30000);

            return $raw >= 1000 ? max(1, min(120, (int) round($raw / 1000))) : max(1, min(120, $raw));
        }

        return (int) config('agricultural_intelligence.web_search.timeout', 15);
    }

    private function authMode(): string
    {
        return (bool) config('agricultural_intelligence.mcp.free_search.enabled', false)
            ? 'none'
            : 'required_key';
    }
}
