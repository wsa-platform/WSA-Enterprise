<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * Single facet accounting record inside a Projection envelope.
 */
final readonly class ProjectionFacetRecord
{
    private function __construct(
        public string $facetCode,
        public ProjectionFacetDisposition $disposition,
        public bool $identityCritical,
        public ?string $sourceNativeRepresentation,
        public ?string $reason,
        public bool $exactEquivalenceEstablished,
    ) {}

    public static function of(
        string $facetCode,
        ProjectionFacetDisposition $disposition,
        ?string $sourceNativeRepresentation = null,
        ?string $reason = null,
        bool $exactEquivalenceEstablished = false,
    ): self {
        $code = ProjectionIdentityCriticalFacets::normalize($facetCode);
        $identityCritical = ProjectionIdentityCriticalFacets::isIdentityCritical($code);

        if ($disposition === ProjectionFacetDisposition::OMITTED
            || $disposition === ProjectionFacetDisposition::UNSUPPORTED
            || $disposition === ProjectionFacetDisposition::UNRESOLVED
        ) {
            $r = self::normalizeOptional($reason);
            if ($r === null) {
                throw new ProjectionInvariantViolation(
                    "Disposition {$disposition->value} for facet [{$code}] requires an explicit reason."
                );
            }
            $reason = $r;
        } else {
            $reason = self::normalizeOptional($reason);
        }

        if ($disposition === ProjectionFacetDisposition::EXPANDED && ! $exactEquivalenceEstablished) {
            // Non-exact expansion contributes APPROXIMATED at aggregation time.
        }

        return new self(
            $code,
            $disposition,
            $identityCritical,
            self::normalizeOptional($sourceNativeRepresentation),
            $reason,
            $exactEquivalenceEstablished,
        );
    }

    public function fidelityContribution(): ProjectionFidelityClass
    {
        if ($this->disposition === ProjectionFacetDisposition::EXPANDED) {
            return $this->exactEquivalenceEstablished
                ? ProjectionFidelityClass::EXACT
                : ProjectionFidelityClass::APPROXIMATED;
        }

        return $this->disposition->toFidelityContribution();
    }

    public function isExactRepresentation(): bool
    {
        return $this->fidelityContribution() === ProjectionFidelityClass::EXACT;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'facet_code' => $this->facetCode,
            'disposition' => $this->disposition->value,
            'identity_critical' => $this->identityCritical,
            'source_native_representation' => $this->sourceNativeRepresentation,
            'reason' => $this->reason,
            'exact_equivalence_established' => $this->exactEquivalenceEstablished,
            'fidelity_contribution' => $this->fidelityContribution()->value,
        ];
    }

    private static function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
