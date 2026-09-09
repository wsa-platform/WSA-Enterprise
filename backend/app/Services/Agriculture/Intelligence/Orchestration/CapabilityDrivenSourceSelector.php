<?php

namespace App\Services\Agriculture\Intelligence\Orchestration;

use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Capability-driven source selection — no crop/question-specific branches.
 */
final class CapabilityDrivenSourceSelector
{
    public function __construct(
        private AgriculturalProviderRegistry $registry,
    ) {}

    /**
     * @return list<\App\Contracts\Agriculture\AgriculturalProviderInterface>
     */
    public function select(KnowledgeQueryPlan $plan, ProviderQueryInput $input): array
    {
        $capabilities = $input->requiredCapabilities;
        $types = [];

        // Always consider scientific + web for answer orchestration when internet-first.
        if ($plan->isInternetFirst() || $plan->readyForStage3) {
            $types[] = ProviderType::SCIENTIFIC;
            $types[] = ProviderType::WEB;
        }

        $intent = strtolower((string) ($plan->researchIntent ?? $input->intent ?? ''));
        if (str_contains($intent, 'disease') || str_contains($intent, 'pest') || in_array('plant_disease_analysis', $capabilities, true)) {
            $types[] = ProviderType::DISEASE;
        }
        if (str_contains($intent, 'weather') || str_contains($intent, 'climate') || in_array('weather', $capabilities, true)) {
            $types[] = ProviderType::ENVIRONMENTAL;
        }
        if (in_array('mcp', $capabilities, true) || in_array('market_signals', $capabilities, true)) {
            $types[] = ProviderType::MCP;
        }
        if (in_array('field_sensors', $capabilities, true)) {
            $types[] = ProviderType::FIELD;
        }

        $types = array_values(array_unique($types));
        if ($types === []) {
            $types = [ProviderType::SCIENTIFIC, ProviderType::WEB];
        }

        return $this->registry->select($types, $capabilities, true);
    }
}
