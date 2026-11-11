<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;

/**
 * Derives ProjectionQueryConstraints from caller facet codes (ADR-023 §8.33, B-1).
 *
 * Facet codes are explicit caller input: no approved contract yet defines their extraction
 * from CSQ. Constraints come only from the cells the capability decision already resolved
 * (ProjectionFacetConstraint::fromCapabilityDecision); never re-resolves Cap records, never
 * writes Cap, CapVer, or any repository.
 */
final class ProjectionQueryConstraintsFactory
{
    /**
     * @param  list<string>  $facetCodes
     * @param  list<ProjectionWarning>  $warnings
     */
    public function derive(
        CanonicalQueryId $canonicalQueryId,
        CapabilityDecisionIdentity $capabilityDecision,
        PathDecisionIdentity $pathDecision,
        array $facetCodes,
        ?ProjectionRetrievalMethod $retrievalMethod = null,
        array $warnings = [],
    ): ProjectionQueryConstraints {
        ProjectionDomainContract::assertSubjectAlignment($capabilityDecision, $pathDecision);

        $codes = [];
        $seen = [];
        foreach ($facetCodes as $facetCode) {
            if (! is_string($facetCode)) {
                throw new ProjectionInvariantViolation('Facet codes must be strings.');
            }
            $code = ProjectionIdentityCriticalFacets::normalize($facetCode);
            if (isset($seen[$code])) {
                throw new ProjectionInvariantViolation("Duplicate facet_code [{$code}] in facet codes.");
            }
            $seen[$code] = true;
            $codes[] = $code;
        }

        return ProjectionQueryConstraints::create(
            $canonicalQueryId,
            $capabilityDecision,
            $pathDecision,
            ProjectionFacetConstraint::fromCapabilityDecision($capabilityDecision, $codes),
            $retrievalMethod,
            $warnings,
        );
    }
}
