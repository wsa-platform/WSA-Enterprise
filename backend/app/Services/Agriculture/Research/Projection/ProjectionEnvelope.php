<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathStatus;

/**
 * Immutable Projection Envelope (Projection + C9 Design §6).
 *
 * Represents source-native query + C9 facet accounting. Does not execute retrieval,
 * mutate CSQ, recompute Cap/Path, or wire Stage-3.
 */
final readonly class ProjectionEnvelope
{
    public const UNKNOWN_METADATA = 'UNKNOWN';

    /**
     * @param  list<ProjectionWarning>  $projectionWarnings
     * @param  list<ProjectionSourceNativeIdentifier>  $sourceNativeIdentifiers
     */
    private function __construct(
        public ProjectionIdentity $projectionIdentity,
        public CanonicalQueryId $canonicalQueryId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public string $capabilityDecisionId,
        public string $pathDecisionId,
        public PathId $pathId,
        public PathFamily $pathFamily,
        public PathEligibilityState $pathEligibilityState,
        public PathStatus $pathStatus,
        public string $sourceQuery,
        public ProjectionFacetAccounting $facetAccounting,
        public ProjectionFidelityClass $fidelityClass,
        public array $projectionWarnings,
        public array $sourceNativeIdentifiers,
        public ProjectionRetrievalMethod $retrievalMethod,
        public string $retrievalTimestamp,
        public string $sourceVersion,
        public string $projectionVersion,
    ) {}

    /**
     * @param  list<ProjectionWarning>  $projectionWarnings
     * @param  list<ProjectionSourceNativeIdentifier>  $sourceNativeIdentifiers
     */
    public static function create(
        ProjectionIdentity $projectionIdentity,
        CanonicalQueryId $canonicalQueryId,
        CapabilityDecisionIdentity $capabilityDecision,
        PathDecisionIdentity $pathDecision,
        string $sourceQuery,
        ProjectionFacetAccounting $facetAccounting,
        ?ProjectionFidelityClass $fidelityClass = null,
        array $projectionWarnings = [],
        array $sourceNativeIdentifiers = [],
        ?ProjectionRetrievalMethod $retrievalMethod = null,
        ?string $retrievalTimestamp = null,
        ?string $sourceVersion = null,
        ?string $projectionVersion = null,
    ): self {
        ProjectionDomainContract::assertSubjectAlignment($capabilityDecision, $pathDecision);
        ProjectionDomainContract::assertProjectionIdentityDistinct(
            $projectionIdentity,
            $canonicalQueryId,
            $pathDecision->adrId,
            $pathDecision->canonicalIdentityId,
            $capabilityDecision->decisionId,
            $pathDecision->decisionId->value,
            $pathDecision->pathId,
        );

        foreach ($projectionWarnings as $warning) {
            if (! $warning instanceof ProjectionWarning) {
                throw new ProjectionInvariantViolation('projection_warnings must be ProjectionWarning instances.');
            }
        }
        foreach ($sourceNativeIdentifiers as $identifier) {
            if (! $identifier instanceof ProjectionSourceNativeIdentifier) {
                throw new ProjectionInvariantViolation(
                    'source_native_identifiers must be ProjectionSourceNativeIdentifier instances.'
                );
            }
            if ($identifier->value === $pathDecision->adrId->value
                || $identifier->value === $projectionIdentity->value
                || $identifier->value === $pathDecision->pathId->value
            ) {
                throw new ProjectionInvariantViolation(
                    'source_native_identifiers must remain distinct from adr_id / projection_identity / path_id.'
                );
            }
        }

        $computed = ProjectionFidelityAggregator::aggregateIdentityCritical($facetAccounting->records);
        $claimed = $fidelityClass ?? $computed;
        ProjectionFidelityAggregator::assertExactClaimAllowed($claimed, $facetAccounting->records);

        $warnings = array_values($projectionWarnings);
        if ($claimed !== ProjectionFidelityClass::EXACT) {
            $warnings[] = ProjectionWarning::of(
                'C9_NON_EXACT',
                "Aggregate fidelity_class is {$claimed->value}; identity-critical non-EXACT cannot yield an unqualified exact scientific claim (B8).",
            );
        }

        return new self(
            $projectionIdentity,
            $canonicalQueryId,
            $pathDecision->adrId,
            $pathDecision->canonicalIdentityId,
            $capabilityDecision->decisionId,
            $pathDecision->decisionId->value,
            $pathDecision->pathId,
            $pathDecision->pathFamily,
            $pathDecision->eligibilityState,
            $pathDecision->status,
            $sourceQuery,
            $facetAccounting,
            $claimed,
            $warnings,
            array_values($sourceNativeIdentifiers),
            $retrievalMethod ?? ProjectionRetrievalMethod::unknown(),
            self::normalizeUnknownable($retrievalTimestamp),
            self::normalizeUnknownable($sourceVersion),
            self::normalizeUnknownable($projectionVersion),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $buckets = $this->facetAccounting->toBucketArrays();

        return [
            'projection_identity' => $this->projectionIdentity->value,
            'canonical_query_id' => $this->canonicalQueryId->value,
            'source_identity' => [
                'adr_id' => $this->adrId->value,
                'canonical_identity_id' => $this->canonicalIdentityId?->value,
            ],
            'capability_decision_identity' => $this->capabilityDecisionId,
            'path_decision_identity' => $this->pathDecisionId,
            'path_id' => $this->pathId->value,
            'path_family' => $this->pathFamily->value,
            'path_eligibility_state' => $this->pathEligibilityState->value,
            'path_status' => $this->pathStatus->value,
            'source_query' => $this->sourceQuery,
            'mapped_facets' => $buckets['mapped_facets'],
            'expanded_terms' => $buckets['expanded_terms'],
            'unsupported_facets' => $buckets['unsupported_facets'],
            'omitted_facets' => $buckets['omitted_facets'],
            'unresolved_facets' => $buckets['unresolved_facets'],
            'compressed_facets' => $buckets['compressed_facets'],
            'approximated_facets' => $buckets['approximated_facets'],
            'source_native_identifiers' => array_map(
                static fn (ProjectionSourceNativeIdentifier $i) => $i->toArray(),
                $this->sourceNativeIdentifiers,
            ),
            'retrieval_method' => $this->retrievalMethod->value,
            'retrieval_timestamp' => $this->retrievalTimestamp,
            'source_version' => $this->sourceVersion,
            'projection_version' => $this->projectionVersion,
            'fidelity_class' => $this->fidelityClass->value,
            'projection_warnings' => array_map(
                static fn (ProjectionWarning $w) => $w->toArray(),
                $this->projectionWarnings,
            ),
        ];
    }

    private static function normalizeUnknownable(?string $value): string
    {
        if ($value === null) {
            return self::UNKNOWN_METADATA;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? self::UNKNOWN_METADATA : $trimmed;
    }
}
