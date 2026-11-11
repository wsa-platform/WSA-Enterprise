<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityState;

/**
 * One facet entry of ProjectionQueryConstraints (ADR-023 §8.33).
 *
 * identity_critical and constraint are derived, never supplied by the caller.
 * evidenceFreshness is an input of the mapping only; it is not serialized.
 */
final readonly class ProjectionFacetConstraint
{
    public const CAPABILITY_STATE_MISSING = 'MISSING';

    private function __construct(
        public string $facetCode,
        public bool $identityCritical,
        public string $capabilityState,
        public ?CapabilityEvidenceFreshness $evidenceFreshness,
        public ProjectionFacetConstraintState $constraint,
    ) {}

    /**
     * @param  CapabilityState|null  $capabilityState  null when the capability decision resolves no Cap fact (MISSING)
     * @param  CapabilityEvidenceFreshness|null  $evidenceFreshness  null when freshness is not established
     */
    public static function of(
        string $facetCode,
        ?CapabilityState $capabilityState,
        ?CapabilityEvidenceFreshness $evidenceFreshness,
    ): self {
        $code = ProjectionIdentityCriticalFacets::normalize($facetCode);
        $freshness = $capabilityState === null ? null : $evidenceFreshness;

        return new self(
            $code,
            ProjectionIdentityCriticalFacets::isIdentityCritical($code),
            $capabilityState?->value ?? self::CAPABILITY_STATE_MISSING,
            $freshness,
            ProjectionFacetConstraintState::fromCapability($capabilityState, $freshness),
        );
    }

    /**
     * The constraints the capability decision resolves for the given facet codes, in input order.
     *
     * Reads only the cells CapabilityRequirementEvaluator already resolved; never re-resolves Cap
     * records. A facet matches a cell only by the exact Cap dimension key: Cap codes are not
     * case-folded. A facet without a cell, or whose cell has missing_record, is MISSING.
     *
     * @param  list<string>  $facetCodes
     * @return list<self>
     */
    public static function fromCapabilityDecision(CapabilityDecisionIdentity $capabilityDecision, array $facetCodes): array
    {
        $codes = array_map(static fn (string $facetCode): string => ProjectionIdentityCriticalFacets::normalize($facetCode), $facetCodes);
        $codeByKey = [];
        foreach ($codes as $code) {
            $codeByKey[CapabilityDimension::scientificFacetQueryConstrainable($code)->key()] = $code;
        }

        $cells = [];
        foreach ($capabilityDecision->perCellOutcomes as $outcome) {
            if (! is_array($outcome) || ! is_string($outcome['dimension_key'] ?? null)) {
                throw new ProjectionInvariantViolation('Capability decision outcomes must carry a string dimension_key.');
            }
            $code = $codeByKey[$outcome['dimension_key']] ?? null;
            if ($code === null) {
                continue;
            }

            $state = is_string($outcome['state'] ?? null) ? CapabilityState::tryFrom($outcome['state']) : null;
            if ($state === null) {
                throw new ProjectionInvariantViolation(
                    "Capability decision outcome for facet_code [{$code}] has no valid Capability state."
                );
            }
            $missingRecord = $outcome['missing_record'] ?? null;
            if (! is_bool($missingRecord)) {
                throw new ProjectionInvariantViolation(
                    "Capability decision outcome for facet_code [{$code}] has no boolean missing_record."
                );
            }
            $freshness = is_string($outcome['evidence_freshness_class'] ?? null)
                ? CapabilityEvidenceFreshness::tryFrom($outcome['evidence_freshness_class'])
                : null;

            $cell = $missingRecord ? [null, null] : [$state, $freshness];
            if (isset($cells[$code]) && $cells[$code] !== $cell) {
                throw new ProjectionInvariantViolation(
                    "Conflicting capability decision outcomes for facet_code [{$code}]."
                );
            }
            $cells[$code] = $cell;
        }

        $constraints = [];
        foreach ($codes as $code) {
            [$state, $freshness] = $cells[$code] ?? [null, null];
            $constraints[] = self::of($code, $state, $freshness);
        }

        return $constraints;
    }

    /**
     * Disclosure text for an UNKNOWN constraint; null otherwise.
     */
    public function unknownReason(): ?string
    {
        if ($this->constraint !== ProjectionFacetConstraintState::UNKNOWN) {
            return null;
        }
        if ($this->capabilityState === self::CAPABILITY_STATE_MISSING) {
            return 'capability_state MISSING: no query_constrainable Capability fact in the capability decision';
        }
        if ($this->capabilityState === CapabilityState::VERIFIED->value) {
            $freshness = $this->evidenceFreshness?->value ?? 'not established';

            return "capability_state VERIFIED; evidence freshness {$freshness}, not CURRENT";
        }

        return "capability_state {$this->capabilityState}";
    }

    /**
     * @return array{facet_code: string, identity_critical: bool, capability_state: string, constraint: string}
     */
    public function toArray(): array
    {
        return [
            'facet_code' => $this->facetCode,
            'identity_critical' => $this->identityCritical,
            'capability_state' => $this->capabilityState,
            'constraint' => $this->constraint->value,
        ];
    }
}
