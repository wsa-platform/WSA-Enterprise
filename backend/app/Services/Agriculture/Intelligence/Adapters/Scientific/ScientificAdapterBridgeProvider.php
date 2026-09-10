<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\AbstractAgriculturalProvider;
use App\Services\Agriculture\Intelligence\Contracts\ProviderHealthState;
use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
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
        $key = $this->adapter->sourceKey();
        $role = $this->sourceRole($key);

        return new ProviderDescriptor(
            id: $key,
            name: $this->adapter->displayName(),
            type: ProviderType::SCIENTIFIC,
            capabilities: $this->capabilitiesFor($key),
            version: '1.0.0',
            priority: $this->priority,
            timeoutSeconds: (int) config('wsa.scientific_http_timeout', 15),
            health: $this->enabled ? ProviderHealthState::HEALTHY : ProviderHealthState::NOT_CONFIGURED,
            auth: ['mode' => 'optional_key'],
            confidenceMeta: [
                'family' => $role === SourceRole::OFFICIAL_AGRICULTURAL_DATA ? 'official' : 'scientific',
                'source_role' => $role,
            ],
            evidenceCapable: true,
            limitations: $this->limitationsFor($key),
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
        $key = $this->adapter->sourceKey();
        $role = $this->sourceRole($key);
        $family = match ($role) {
            SourceRole::OFFICIAL_AGRICULTURAL_DATA => 'official',
            SourceRole::CITATION_METADATA => 'citation_metadata',
            default => 'scientific',
        };
        $evidence = [];
        foreach ($outcome->results as $result) {
            $evidence[] = array_merge($result->toArray(), [
                'evidence_family' => $family,
                'source_role' => $role,
                'provider_id' => $key,
                'confidence' => $role === SourceRole::SCIENTIFIC_EVIDENCE ? 0.75 : 0.55,
            ]);
        }

        $isOfficial = $role === SourceRole::OFFICIAL_AGRICULTURAL_DATA;

        return new CanonicalAgriculturalResult(
            providerId: $key,
            status: 'success',
            scientificEvidence: $isOfficial ? [] : $evidence,
            stats: $isOfficial ? $evidence : [],
            confidence: $isOfficial ? 0.7 : 0.75,
            source: $key,
            timestamp: now()->toIso8601String(),
            meta: $outcome->observability ?? [],
        );
    }

    /** @return list<string> */
    private function capabilitiesFor(string $sourceKey): array
    {
        return match ($sourceKey) {
            'fao_stat' => ['official_agricultural_data', 'agricultural_statistics'],
            'crossref' => ['citation_metadata', 'scientific_search'],
            default => ['scientific_search', 'scholarly_evidence', 'peer_reviewed_index'],
        };
    }

    private function sourceRole(string $sourceKey): string
    {
        return match ($sourceKey) {
            'fao_stat' => SourceRole::OFFICIAL_AGRICULTURAL_DATA,
            'crossref' => SourceRole::CITATION_METADATA,
            default => SourceRole::SCIENTIFIC_EVIDENCE,
        };
    }

    /** @return list<string> */
    private function limitationsFor(string $sourceKey): array
    {
        return match ($sourceKey) {
            'fao_stat' => ['Official agricultural statistics — not peer-reviewed experimental evidence'],
            'crossref' => ['Citation metadata — not automatically scientific claim evidence'],
            default => ['Scholarly index — not general web search'],
        };
    }
}
