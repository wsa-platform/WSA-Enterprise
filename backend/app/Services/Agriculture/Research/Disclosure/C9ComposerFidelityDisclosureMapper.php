<?php

namespace App\Services\Agriculture\Research\Disclosure;

use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;
use App\Services\Agriculture\Research\Projection\ProjectionFacetRecord;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;

/**
 * IU-08 OD-01 mapper: ProjectionEnvelope → FidelityDisclosureHandoff.
 *
 * Consumes IU-04 C9 outputs; does not recalculate aggregation, Cap, Path, CSQ, R6, or confidence.
 */
final class C9ComposerFidelityDisclosureMapper
{
    public function map(ProjectionEnvelope $envelope): FidelityDisclosureHandoff
    {
        $projectionIdentity = $envelope->projectionIdentity;
        $aggregate = $envelope->fidelityClass;
        $disclosures = [];

        if ($aggregate !== ProjectionFidelityClass::EXACT) {
            $disclosures[] = FidelityDisclosureRecord::aggregate($aggregate, $projectionIdentity);
        }

        $facets = $envelope->facetAccounting->records;
        usort(
            $facets,
            static fn (ProjectionFacetRecord $a, ProjectionFacetRecord $b): int => strcmp($a->facetCode, $b->facetCode)
        );

        $identityCriticalNonExact = false;
        foreach ($facets as $facet) {
            $contribution = $facet->fidelityContribution();
            if ($contribution === ProjectionFidelityClass::EXACT) {
                continue;
            }

            if ($facet->identityCritical) {
                $identityCriticalNonExact = true;
                $disclosures[] = FidelityDisclosureRecord::identityCriticalLoss(
                    $contribution,
                    $facet->facetCode,
                    $projectionIdentity,
                    $facet->disposition,
                    $facet->reason,
                );
            } else {
                $disclosures[] = FidelityDisclosureRecord::nonIdentityFacetLoss(
                    $contribution,
                    $facet->facetCode,
                    $projectionIdentity,
                    $facet->disposition,
                    $facet->reason,
                );
            }
        }

        $qualificationRequired = $aggregate !== ProjectionFidelityClass::EXACT || $identityCriticalNonExact;

        return FidelityDisclosureHandoff::create(
            $projectionIdentity,
            $aggregate,
            $disclosures,
            $qualificationRequired,
            $qualificationRequired,
            FidelityDisclosureHandoff::SCHEMA_VERSION,
        );
    }
}
