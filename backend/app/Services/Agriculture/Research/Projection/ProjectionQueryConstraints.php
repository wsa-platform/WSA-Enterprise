<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;
use App\Services\Agriculture\Research\Path\PathId;

/**
 * Per-facet query constraint metadata for one seat and one CSQ (ADR-023 §8.33, B-1).
 *
 * Consumed by ScientificSearchQueryBuilder, which remains the sole lexical construction and
 * expansion authority (D-04, D-10.9.2). Carries no query text, terms, expansions or fidelity:
 * source_query, expanded_terms and fidelity_class stay in ProjectionEnvelope.
 * Runtime attachment is via ProjectionSearchOptionsBinder on the Stage-3 orchestrator path (OD-C).
 */
final readonly class ProjectionQueryConstraints
{
    public const SCHEMA_VERSION = 'projection_query_constraints.v1';

    public const WARNING_QUERY_CONSTRAINT_UNKNOWN = 'QUERY_CONSTRAINT_UNKNOWN';

    /**
     * @param  list<ProjectionFacetConstraint>  $facetConstraints
     * @param  list<ProjectionWarning>  $warnings
     */
    private function __construct(
        public CanonicalQueryId $canonicalQueryId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public string $capabilityDecisionId,
        public string $pathDecisionId,
        public PathId $pathId,
        public array $facetConstraints,
        public ProjectionRetrievalMethod $retrievalMethod,
        public array $warnings,
    ) {}

    /**
     * Every facet constraint must equal the one the capability decision resolves for its facet
     * code, freshness included (§8.33.8, M-1 and R-1): a caller cannot assert a Cap state or
     * CURRENT freshness the decision does not carry.
     *
     * Caller warnings come first (identical code and message kept once), then one
     * QUERY_CONSTRAINT_UNKNOWN warning per UNKNOWN facet in facet order (D-10.9.6).
     *
     * @param  list<ProjectionFacetConstraint>  $facetConstraints
     * @param  list<ProjectionWarning>  $warnings
     */
    public static function create(
        CanonicalQueryId $canonicalQueryId,
        CapabilityDecisionIdentity $capabilityDecision,
        PathDecisionIdentity $pathDecision,
        array $facetConstraints,
        ?ProjectionRetrievalMethod $retrievalMethod = null,
        array $warnings = [],
    ): self {
        ProjectionDomainContract::assertSubjectAlignment($capabilityDecision, $pathDecision);

        $constraints = [];
        $seenCodes = [];
        foreach ($facetConstraints as $constraint) {
            if (! $constraint instanceof ProjectionFacetConstraint) {
                throw new ProjectionInvariantViolation(
                    'facet_constraints must be ProjectionFacetConstraint instances.'
                );
            }
            if (isset($seenCodes[$constraint->facetCode])) {
                throw new ProjectionInvariantViolation(
                    "Duplicate facet_code [{$constraint->facetCode}] in facet_constraints."
                );
            }
            $seenCodes[$constraint->facetCode] = true;
            $constraints[] = $constraint;
        }
        $resolved = ProjectionFacetConstraint::fromCapabilityDecision(
            $capabilityDecision,
            array_map(static fn (ProjectionFacetConstraint $c): string => $c->facetCode, $constraints),
        );
        foreach ($constraints as $i => $constraint) {
            if ($constraint->toArray() !== $resolved[$i]->toArray()
                || $constraint->evidenceFreshness !== $resolved[$i]->evidenceFreshness
            ) {
                throw new ProjectionInvariantViolation(
                    "Facet constraint [{$constraint->facetCode}] does not match the capability decision."
                );
            }
        }
        usort(
            $constraints,
            static fn (ProjectionFacetConstraint $a, ProjectionFacetConstraint $b): int => strcmp($a->facetCode, $b->facetCode),
        );

        foreach ($warnings as $warning) {
            if (! $warning instanceof ProjectionWarning) {
                throw new ProjectionInvariantViolation('warnings must be ProjectionWarning instances.');
            }
        }
        $generated = [];
        foreach ($constraints as $constraint) {
            $reason = $constraint->unknownReason();
            if ($reason !== null) {
                $generated[] = ProjectionWarning::of(
                    self::WARNING_QUERY_CONSTRAINT_UNKNOWN,
                    "Facet [{$constraint->facetCode}] query constraint is UNKNOWN ({$reason}).",
                );
            }
        }
        $disclosed = [];
        $seenWarnings = [];
        foreach ([...array_values($warnings), ...$generated] as $warning) {
            $key = $warning->code."\0".$warning->message;
            if (! isset($seenWarnings[$key])) {
                $seenWarnings[$key] = true;
                $disclosed[] = $warning;
            }
        }

        return new self(
            $canonicalQueryId,
            $pathDecision->adrId,
            $pathDecision->canonicalIdentityId,
            $capabilityDecision->decisionId,
            $pathDecision->decisionId->value,
            $pathDecision->pathId,
            $constraints,
            $retrievalMethod ?? ProjectionRetrievalMethod::unknown(),
            $disclosed,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'canonical_query_id' => $this->canonicalQueryId->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'capability_decision_id' => $this->capabilityDecisionId,
            'path_decision_id' => $this->pathDecisionId,
            'path_id' => $this->pathId->value,
            'facet_constraints' => array_map(
                static fn (ProjectionFacetConstraint $c) => $c->toArray(),
                $this->facetConstraints,
            ),
            'retrieval_method' => $this->retrievalMethod->value,
            'warnings' => array_map(
                static fn (ProjectionWarning $w) => $w->toArray(),
                $this->warnings,
            ),
        ];
    }

    /**
     * Owner-approved Projection metadata (OD-C). No source_query. No HTTP query text.
     *
     * @return array{
     *     unsupported_facets: list<string>,
     *     omitted_facets: list<string>,
     *     unresolved_facets: list<string>,
     *     projection_warnings: list<array{code: string, message: string}>
     * }
     */
    public function approvedMetadata(): array
    {
        $unsupported = [];
        $omitted = [];
        $unresolved = [];
        foreach ($this->facetConstraints as $constraint) {
            if ($constraint->constraint === ProjectionFacetConstraintState::NOT_CONSTRAINABLE) {
                $unsupported[] = $constraint->facetCode;
            } elseif ($constraint->capabilityState === ProjectionFacetConstraint::CAPABILITY_STATE_MISSING) {
                $omitted[] = $constraint->facetCode;
            } elseif ($constraint->constraint === ProjectionFacetConstraintState::UNKNOWN) {
                $unresolved[] = $constraint->facetCode;
            }
        }

        return [
            'unsupported_facets' => $unsupported,
            'omitted_facets' => $omitted,
            'unresolved_facets' => $unresolved,
            'projection_warnings' => array_map(
                static fn (ProjectionWarning $w) => $w->toArray(),
                $this->warnings,
            ),
        ];
    }
}
