<?php

namespace App\Services\Agriculture\Research\Projection;

/**
 * C9 worst-loss aggregation over material identity-critical facet contributions.
 *
 * No average / majority / best-facet scoring. No invented numeric thresholds.
 */
final class ProjectionFidelityAggregator
{
    /**
     * @param  list<ProjectionFacetRecord>  $facets
     */
    public static function aggregateIdentityCritical(array $facets): ProjectionFidelityClass
    {
        $worst = ProjectionFidelityClass::EXACT;
        $sawMaterial = false;

        foreach ($facets as $facet) {
            if (! $facet instanceof ProjectionFacetRecord) {
                throw new ProjectionInvariantViolation('Facet accounting must contain ProjectionFacetRecord only.');
            }
            if (! $facet->identityCritical) {
                continue;
            }
            $sawMaterial = true;
            $worst = ProjectionFidelityClass::worst($worst, $facet->fidelityContribution());
        }

        // Vacuous EXACT when no identity-critical facets were projected is allowed.
        unset($sawMaterial);

        return $worst;
    }

    /**
     * INV-07: identity-critical material loss cannot be claimed as EXACT.
     *
     * @param  list<ProjectionFacetRecord>  $facets
     */
    public static function assertExactClaimAllowed(
        ProjectionFidelityClass $claimed,
        array $facets,
    ): void {
        $computed = self::aggregateIdentityCritical($facets);
        if ($claimed === ProjectionFidelityClass::EXACT
            && $computed !== ProjectionFidelityClass::EXACT
        ) {
            throw new ProjectionInvariantViolation(
                "Cannot claim fidelity_class EXACT when identity-critical worst loss is {$computed->value}."
            );
        }

        if ($claimed->lossRank() < $computed->lossRank()) {
            throw new ProjectionInvariantViolation(
                "Claimed fidelity_class {$claimed->value} is better than identity-critical worst loss {$computed->value}."
            );
        }
    }
}
