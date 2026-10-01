<?php

namespace App\Services\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationRecord;
use App\Services\Agriculture\Research\Disclosure\FidelityDisclosureHandoff;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;

/**
 * Per-sourceKey coexistence plan entry — result only; not an authority.
 */
final readonly class CoexistenceSourcePlanEntry
{
    /**
     * @param  list<CoexistenceEventCode>  $events
     * @param  list<string>  $limitations
     */
    public function __construct(
        public string $sourceKey,
        public Stage3SourceKeyResolution $identity,
        public bool $selectorSelected,
        public bool $retrievalPermitted,
        public bool $legacyCompatible,
        public bool $cghiaArtifactsAttached,
        public bool $verifiedAutomationClaimed,
        public array $events,
        public array $limitations,
        public ?CapabilityDecisionIdentity $capabilityDecision = null,
        public ?PathDecisionIdentity $pathDecision = null,
        public ?PathEligibilityState $eligibilityState = null,
        public ?PathStatus $pathStatus = null,
        public ?ProjectionEnvelope $projection = null,
        public ?CorrelationChain $correlation = null,
        public ?DurableCorrelationRecord $durableCorrelation = null,
        public ?FidelityDisclosureHandoff $disclosureHandoff = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'identity' => $this->identity->toArray(),
            'selector_selected' => $this->selectorSelected,
            'retrieval_permitted' => $this->retrievalPermitted,
            'legacy_compatible' => $this->legacyCompatible,
            'cghia_artifacts_attached' => $this->cghiaArtifactsAttached,
            'verified_automation_claimed' => $this->verifiedAutomationClaimed,
            'events' => array_map(
                static fn (CoexistenceEventCode $e): string => $e->value,
                $this->events,
            ),
            'limitations' => $this->limitations,
            'capability_decision_identity' => $this->capabilityDecision?->decisionId,
            'capability_and_result_class' => $this->capabilityDecision?->andResultClass->value,
            'path_decision_identity' => $this->pathDecision?->decisionId->value,
            'path_id' => $this->pathDecision?->pathId->value,
            'path_family' => $this->pathDecision?->pathFamily->value,
            'eligibility_state' => $this->eligibilityState?->value ?? $this->pathDecision?->eligibilityState->value,
            'path_status' => $this->pathStatus?->value ?? $this->pathDecision?->status->value,
            'projection_identity' => $this->projection?->projectionIdentity->value,
            'fidelity_class' => $this->projection?->fidelityClass->value,
            'correlation_tokens' => $this->correlation?->compositeCorrelationTokens(),
            'durable_correlation_id' => $this->durableCorrelation?->persistenceRecordId->toInt(),
            'disclosure' => $this->disclosureHandoff?->toArray(),
        ];
    }
}
