<?php

namespace App\Services\Agriculture\Research\Path;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;

/**
 * path_decision_identity snapshot (Path Model Design §19).
 *
 * Consumes IU-01 subject bind + IU-02 capability_decision_identity.
 * Does not execute paths, Projection, or Stage-3 selection.
 */
final readonly class PathDecisionIdentity
{
    /**
     * @param  list<PathLimitation>  $limitations
     * @param  list<string>  $decisionReasons
     */
    private function __construct(
        public PathDecisionId $decisionId,
        public PathId $pathId,
        public PathFamily $pathFamily,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public string $capabilityDecisionId,
        public PathEligibilityState $eligibilityState,
        public PathStatus $status,
        public array $limitations,
        public array $decisionReasons,
        public string $decidedAt,
        public string $decisionVersion,
        public bool $capabilityStaleFlagsPresent,
        public PathFallbackLayer $fallbackLayer,
        public bool $forbidsFidelityIncreaseOnFallback,
    ) {}

    /**
     * @param  list<PathLimitation>  $limitations
     * @param  list<string>  $decisionReasons
     */
    public static function create(
        PathDecisionId $decisionId,
        PathId $pathId,
        PathFamily $pathFamily,
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId,
        CapabilityDecisionIdentity $capabilityDecision,
        PathEligibilityState $eligibilityState,
        PathStatus $status,
        array $limitations = [],
        array $decisionReasons = [],
        string $decidedAt = '',
        string $decisionVersion = '1',
        PathFallbackLayer $fallbackLayer = PathFallbackLayer::L1,
        bool $forbidsFidelityIncreaseOnFallback = true,
    ): self {
        PathModelDomainContract::assertPathIdNotEqualAdrId($pathId, $adrId);
        PathModelDomainContract::assertDecisionIdDistinct(
            $decisionId,
            $pathId,
            $adrId,
            $capabilityDecision->decisionId,
            $canonicalIdentityId,
        );
        PathModelDomainContract::assertEligibilityOrthogonalToStatus($eligibilityState, $status);

        foreach ($limitations as $limitation) {
            if (! $limitation instanceof PathLimitation) {
                throw new PathInvariantViolation('Path limitations must be PathLimitation instances.');
            }
        }

        $at = trim($decidedAt);
        if ($at === '') {
            throw new PathInvariantViolation('path_decision_identity decided_at must be non-empty.');
        }

        $version = trim($decisionVersion);
        if ($version === '') {
            throw new PathInvariantViolation('path_decision_identity decision_version must be non-empty.');
        }

        // Cap subject adr must match Path subject adr (consume Cap; do not invent).
        if (! $capabilityDecision->subject->adrId->equals($adrId)) {
            throw new PathInvariantViolation(
                'Path subject adr_id must match capability_decision_identity subject adr_id.'
            );
        }

        $capCanonical = $capabilityDecision->subject->canonicalIdentityId?->value;
        $pathCanonical = $canonicalIdentityId?->value;
        if ($capCanonical !== $pathCanonical) {
            throw new PathInvariantViolation(
                'Path canonical_identity_id must match capability_decision_identity subject canonical.'
            );
        }

        return new self(
            $decisionId,
            $pathId,
            $pathFamily,
            $adrId,
            $canonicalIdentityId,
            $capabilityDecision->decisionId,
            $eligibilityState,
            $status,
            array_values($limitations),
            array_values($decisionReasons),
            $at,
            $version,
            $capabilityDecision->hasStaleFlags,
            $fallbackLayer,
            $forbidsFidelityIncreaseOnFallback,
        );
    }

    /**
     * Verified automation pair from Path Design §6 — eligibility+status only; not Cap rewrite.
     */
    public function isVerifiedAutomationPair(): bool
    {
        return ($this->eligibilityState === PathEligibilityState::ELIGIBLE
                && $this->status === PathStatus::SELECTED)
            || ($this->eligibilityState === PathEligibilityState::CONDITIONAL
                && $this->status === PathStatus::CONDITIONALLY_SELECTED);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'path_decision_identity' => $this->decisionId->value,
            'path_id' => $this->pathId->value,
            'path_family' => $this->pathFamily->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'capability_decision_identity' => $this->capabilityDecisionId,
            'eligibility_state' => $this->eligibilityState->value,
            'status' => $this->status->value,
            'limitations' => array_map(
                static fn (PathLimitation $l) => $l->toArray(),
                $this->limitations,
            ),
            'decision_reasons' => $this->decisionReasons,
            'decided_at' => $this->decidedAt,
            'decision_version' => $this->decisionVersion,
            'capability_stale_flags_present' => $this->capabilityStaleFlagsPresent,
            'fallback_layer' => $this->fallbackLayer->value,
            'forbids_fidelity_increase_on_fallback' => $this->forbidsFidelityIncreaseOnFallback,
        ];
    }
}
