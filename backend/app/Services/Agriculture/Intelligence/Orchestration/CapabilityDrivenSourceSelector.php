<?php

namespace App\Services\Agriculture\Intelligence\Orchestration;

use App\Services\Agriculture\Intelligence\Contracts\ProviderType;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;
use App\Services\Agriculture\Intelligence\DTO\ProviderSelectionResult;
use App\Services\Agriculture\Intelligence\Registry\AgriculturalProviderRegistry;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Capability-driven source selection — no crop/question-specific branches.
 */
final class CapabilityDrivenSourceSelector
{
    public function __construct(
        private AgriculturalProviderRegistry $registry,
        private RequiredCapabilityResolver $capabilityResolver,
    ) {}

    /**
     * @return list<\App\Contracts\Agriculture\AgriculturalProviderInterface>
     */
    public function select(KnowledgeQueryPlan $plan, ProviderQueryInput $input): array
    {
        return $this->selectWithTrace($plan, $input)->selected;
    }

    public function selectWithTrace(KnowledgeQueryPlan $plan, ProviderQueryInput $input): ProviderSelectionResult
    {
        $capabilities = $input->requiredCapabilities !== []
            ? $input->requiredCapabilities
            : $this->capabilityResolver->resolve($plan, [
                'required_capabilities' => $input->requiredCapabilities,
            ]);

        $types = $this->typesForCapabilities($capabilities, $plan);

        $selected = $this->registry->selectMatchingAnyCapability($types, $capabilities, true);
        $kept = [];
        foreach ($selected as $provider) {
            $d = $provider->descriptor();
            if ($d->type === ProviderType::ENVIRONMENTAL && ! $this->hasCoordinates($input)) {
                continue;
            }
            $kept[] = $provider;
        }
        $selected = $kept;

        $selectedIds = [];
        foreach ($selected as $provider) {
            $selectedIds[$provider->descriptor()->id] = true;
        }

        $skipped = [];
        foreach ($this->registry->all() as $provider) {
            $d = $provider->descriptor();
            if (isset($selectedIds[$d->id])) {
                continue;
            }
            $reason = $this->skipReason($d->enabled, $d->type, $d->capabilities, $types, $capabilities, $input);
            $skipped[] = [
                'id' => $d->id,
                'type' => $d->type,
                'reason' => $reason,
            ];
        }

        return new ProviderSelectionResult(
            selected: $selected,
            skipped: $skipped,
            requiredCapabilities: $capabilities,
        );
    }

    /**
     * @param  list<string>  $capabilities
     * @return list<string>
     */
    private function typesForCapabilities(array $capabilities, KnowledgeQueryPlan $plan): array
    {
        $types = [];
        $capSet = array_fill_keys($capabilities, true);

        if (isset($capSet['web_search']) || isset($capSet['general_knowledge'])) {
            $types[] = ProviderType::WEB;
        }
        if (isset($capSet['scientific_search'])
            || isset($capSet['scholarly_evidence'])
            || isset($capSet['citation_metadata'])
            || isset($capSet['official_agricultural_data'])
            || isset($capSet['agricultural_statistics'])) {
            $types[] = ProviderType::SCIENTIFIC;
        }
        if (isset($capSet['plant_disease_analysis'])) {
            $types[] = ProviderType::DISEASE;
        }
        if (isset($capSet['weather'])) {
            $types[] = ProviderType::ENVIRONMENTAL;
        }
        if (isset($capSet['mcp']) || isset($capSet['market_signals'])) {
            $types[] = ProviderType::MCP;
        }
        if (isset($capSet['field_sensors'])) {
            $types[] = ProviderType::FIELD;
        }
        if (isset($capSet['tool_execution'])) {
            $types[] = ProviderType::EXECUTION;
        }

        if ($types === [] && ($plan->isInternetFirst() || $plan->readyForStage3)) {
            $types = [ProviderType::SCIENTIFIC, ProviderType::WEB];
        }

        return array_values(array_unique($types));
    }

    /**
     * @param  list<string>  $providerCapabilities
     * @param  list<string>  $types
     * @param  list<string>  $requiredCapabilities
     */
    private function skipReason(
        bool $enabled,
        string $type,
        array $providerCapabilities,
        array $types,
        array $requiredCapabilities,
        ProviderQueryInput $input,
    ): string {
        if (! $enabled) {
            return 'disabled';
        }
        if ($types !== [] && ! in_array($type, $types, true)) {
            return 'type_not_required';
        }
        if ($requiredCapabilities !== [] && array_intersect($requiredCapabilities, $providerCapabilities) === []) {
            return 'capability_not_required';
        }
        if ($type === ProviderType::ENVIRONMENTAL && ! $this->hasCoordinates($input)) {
            return 'missing_coordinates';
        }

        return 'not_selected';
    }

    private function hasCoordinates(ProviderQueryInput $input): bool
    {
        $lat = $input->constraints['latitude'] ?? $input->context['latitude'] ?? null;
        $lon = $input->constraints['longitude'] ?? $input->context['longitude'] ?? null;

        return is_numeric($lat) && is_numeric($lon);
    }
}
