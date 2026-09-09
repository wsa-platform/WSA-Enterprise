<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Bridges existing ScientificSourceAdapterInterface into AgriculturalProviderInterface.
 */
final class ScientificAdapterBridgeProvider extends AbstractAgriculturalProvider
{
    public function __construct(
        private ScientificSourceAdapterInterface $adapter,
        private int $priority = 30,
        private bool $enabled = true,
    ) {}

    protected function buildDescriptor(): ProviderDescriptor
    {
        return new ProviderDescriptor(
            id: $this->adapter->sourceKey(),
            name: $this->adapter->displayName(),
            type: ProviderType::SCIENTIFIC,
            capabilities: ['scientific_search', 'scholarly_evidence', 'peer_reviewed_index'],
            version: '1.0.0',
            priority: $this->priority,
            timeoutSeconds: (int) config('wsa.scientific_http_timeout', 15),
            health: $this->enabled ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: ['mode' => 'optional_key'],
            confidenceMeta: ['family' => 'scientific'],
            evidenceCapable: true,
            limitations: ['Scholarly index — not general web search'],
            enabled: $this->enabled,
        );
    }

    protected function isConfigured(): bool
    {
        return $this->enabled;
    }

    protected function doRetrieve(ProviderQueryInput $input): CanonicalAgriculturalResult
    {
        $outcome = $this->adapter->search(
            $input->query,
            $input->limit,
            array_merge($input->constraints, $input->context),
        );

        return match ($outcome->status) {
            ScientificSourceSearchOutcome::STATUS_SUCCESS => $this->fromSuccess($outcome),
            ScientificSourceSearchOutcome::STATUS_EMPTY => CanonicalAgriculturalResult::empty(
                $this->adapter->sourceKey(),
                $outcome->error ?? 'empty',
            ),
            ScientificSourceSearchOutcome::STATUS_UNAVAILABLE => CanonicalAgriculturalResult::notConfigured(
                $this->adapter->sourceKey(),
                $outcome->error ?? 'unavailable',
            ),
            default => CanonicalAgriculturalResult::failed(
                $this->adapter->sourceKey(),
                $outcome->error ?? 'failed',
            ),
        };
    }

    private function fromSuccess(ScientificSourceSearchOutcome $outcome): CanonicalAgriculturalResult
    {
        $evidence = [];
        foreach ($outcome->results as $result) {
            $evidence[] = array_merge($result->toArray(), [
                'evidence_family' => 'scientific',
                'provider_id' => $this->adapter->sourceKey(),
                'confidence' => 0.75,
            ]);
        }

        return new CanonicalAgriculturalResult(
            providerId: $this->adapter->sourceKey(),
            status: 'success',
            scientificEvidence: $evidence,
            confidence: 0.75,
            source: $this->adapter->sourceKey(),
            timestamp: now()->toIso8601String(),
            meta: $outcome->observability ?? [],
        );
    }
}
