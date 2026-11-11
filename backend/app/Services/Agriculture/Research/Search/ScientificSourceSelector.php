<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatConsiderationPolicy;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityPolicy;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;

/**
 * Deterministic source selection from KnowledgeQueryPlan.
 *
 * Policy (Phase 3-B / ADR-021 §3), driven by ScientificSourceCatalog selection roles:
 * - Internet-First scholarly defaults: OpenAlex, Crossref, Semantic Scholar.
 * - Consensus remains optional/legacy and is never auto-selected.
 * - FAOSTAT Developer Portal is universally considered when enabled (Phase 3-A);
 *   consideration ≠ evidence acceptance (gate/pipeline outcomes still apply).
 * - Governed ADR expansion seats (GOVERNED_EXPANSION) join after statistical
 *   consideration when enabled and capability-authorized; Stage-3 defaults remain.
 * - Inactive providers are excluded; unknown keys are never invented here.
 */
class ScientificSourceSelector
{
    /**
     * @deprecated Compatibility projection of the catalog's INTERNET_FIRST_DEFAULT role; not read by selection.
     *
     * @var list<string>
     */
    public const DEFAULT_INTERNET_FIRST_SOURCES = [
        'openalex',
        'crossref',
        'semantic_scholar',
    ];

    /**
     * @deprecated Compatibility projection of the catalog's OPTIONAL_EXPLICIT_ONLY role; not read by selection.
     *
     * @var list<string>
     */
    public const OPTIONAL_SOURCES = [
        'consensus',
    ];

    /** Stage 3 retrieval needs only the search operation. */
    public const REQUIRED_OPERATION = ScientificSourceOperation::SEARCH;

    private readonly ScientificSourceCatalog $catalog;

    private readonly ScientificSourceCapabilityPolicy $capabilityPolicy;

    public function __construct(
        ?ScientificSourceCatalog $catalog = null,
        ?ScientificSourceCapabilityPolicy $capabilityPolicy = null,
    ) {
        $this->catalog = $catalog ?? ScientificSourceCatalog::default();
        $this->capabilityPolicy = $capabilityPolicy ?? new ScientificSourceCapabilityPolicy();
    }

    /** @return list<string> */
    public function selectSources(KnowledgeQueryPlan $plan): array
    {
        if ($plan->needsClarification() || ! $plan->readyForStage3) {
            return [];
        }

        if (! $plan->isInternetFirst()) {
            return [];
        }

        return [
            ...$this->authorizedKeysWithRole(ScientificSourceSelectionRole::STATISTICAL_CONSIDERATION),
            ...$this->authorizedKeysWithRole(ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT),
            ...$this->authorizedKeysWithRole(ScientificSourceSelectionRole::GOVERNED_EXPANSION),
        ];
    }

    /** @return list<string> */
    private function authorizedKeysWithRole(ScientificSourceSelectionRole $role): array
    {
        return array_values(array_filter(
            $this->catalog->enabledKeysWithRole($role),
            fn (string $key): bool => $this->capabilityPolicy
                ->decide($this->catalog->get($key), self::REQUIRED_OPERATION)->allowed,
        ));
    }

    /**
     * Internal observability for Stage 3 forensics (not a public API envelope change).
     *
     * @return array{
     *     selected: list<string>,
     *     skipped_inactive: list<string>,
     *     optional_not_selected: list<string>,
     *     faostat_consideration: string,
     *     policy: string,
     *     capability_denied: list<array<string, string|bool|null>>
     * }
     */
    public function selectionTrace(KnowledgeQueryPlan $plan): array
    {
        $selected = $this->selectSources($plan);
        $skippedInactive = [];
        $capabilityDenied = [];
        $planSelects = ! $plan->needsClarification() && $plan->readyForStage3 && $plan->isInternetFirst();

        foreach ([
            ScientificSourceSelectionRole::INTERNET_FIRST_DEFAULT,
            ScientificSourceSelectionRole::STATISTICAL_CONSIDERATION,
            ScientificSourceSelectionRole::GOVERNED_EXPANSION,
        ] as $role) {
            foreach ($this->catalog->keysWithRole($role) as $key) {
                if (in_array($key, $selected, true)) {
                    continue;
                }
                $registration = $this->catalog->get($key);
                if (! $registration?->isEnabled()) {
                    $skippedInactive[] = $key;

                    continue;
                }
                if ($planSelects) {
                    $decision = $this->capabilityPolicy->decide($registration, self::REQUIRED_OPERATION);
                    if (! $decision->allowed) {
                        $capabilityDenied[] = $decision->toArray();
                    }
                }
            }
        }

        return [
            'selected' => $selected,
            'skipped_inactive' => array_values(array_unique($skippedInactive)),
            'optional_not_selected' => $this->catalog->keysWithRole(ScientificSourceSelectionRole::OPTIONAL_EXPLICIT_ONLY),
            'faostat_consideration' => FaoStatConsiderationPolicy::decision(),
            'policy' => 'internet_first_defaults_plus_universal_faostat_when_enabled',
            'capability_denied' => $capabilityDenied,
        ];
    }
}
