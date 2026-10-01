<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * RequiredCapabilities (AND) vs optional enrichments (excluded from AND).
 */
final readonly class CapabilityRequirementSet
{
    /**
     * @param  list<CapabilityDimension>  $required
     * @param  list<CapabilityDimension>  $optionalEnrichments
     */
    private function __construct(
        public array $required,
        public array $optionalEnrichments,
    ) {}

    /**
     * @param  list<CapabilityDimension>  $required
     * @param  list<CapabilityDimension>  $optionalEnrichments
     */
    public static function of(array $required, array $optionalEnrichments = []): self
    {
        if ($required === []) {
            throw new CapabilityInvariantViolation(
                'RequiredCapabilities must contain at least one mandatory dimension.'
            );
        }

        foreach ($required as $dimension) {
            if (! $dimension instanceof CapabilityDimension) {
                throw new CapabilityInvariantViolation('RequiredCapabilities entries must be CapabilityDimension.');
            }
        }
        foreach ($optionalEnrichments as $dimension) {
            if (! $dimension instanceof CapabilityDimension) {
                throw new CapabilityInvariantViolation('Optional enrichments must be CapabilityDimension.');
            }
        }

        return new self(array_values($required), array_values($optionalEnrichments));
    }

    public function isOptional(CapabilityDimension $dimension): bool
    {
        foreach ($this->optionalEnrichments as $optional) {
            if ($optional->equals($dimension)) {
                return true;
            }
        }

        return false;
    }
}
