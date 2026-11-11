<?php

namespace App\Services\Agriculture\Research\Projection;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Path\PathDecisionIdentity;

/**
 * Attaches OD-C Projection approved metadata to Stage-3 search options.
 *
 * Never constructs HTTP query text. Never invents CONSTRAINABLE without a resolved
 * Capability decision. ScientificSearchQueryBuilder remains D-04 query authority.
 */
final class ProjectionSearchOptionsBinder
{
    public const OPTIONS_KEY = 'projection_approved_metadata';

    public const STATUS_KEY = 'projection_constraints_status';

    public const STATUS_ATTACHED = 'attached';

    public const STATUS_SKIPPED_MISSING_DECISION = 'skipped_missing_capability_or_path_decision';

    public function __construct(
        private readonly ProjectionQueryConstraintsFactory $factory = new ProjectionQueryConstraintsFactory(),
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @param  list<string>|null  $facetCodes
     * @return array<string, mixed>
     */
    public function attach(
        array $options,
        string $canonicalQueryId,
        ?CanonicalScientificQuestion $question,
        ?CapabilityDecisionIdentity $capabilityDecision,
        ?PathDecisionIdentity $pathDecision,
        ?array $facetCodes = null,
    ): array {
        if ($capabilityDecision === null || $pathDecision === null) {
            $options[self::STATUS_KEY] = self::STATUS_SKIPPED_MISSING_DECISION;
            $options[self::OPTIONS_KEY] = [
                'unsupported_facets' => [],
                'omitted_facets' => [],
                'unresolved_facets' => [],
                'projection_warnings' => [[
                    'code' => 'PROJECTION_SKIPPED_NO_CAPABILITY_DECISION',
                    'message' => 'Projection metadata not derived: capability or path decision is absent. Absence is not CONSTRAINABLE.',
                ]],
            ];

            return $options;
        }

        $codes = $facetCodes ?? CsqPresentFacetCodes::fromQuestion($question);
        $constraints = $this->factory->derive(
            CanonicalQueryId::fromString($canonicalQueryId),
            $capabilityDecision,
            $pathDecision,
            $codes,
        );

        $options[self::STATUS_KEY] = self::STATUS_ATTACHED;
        $options[self::OPTIONS_KEY] = $constraints->approvedMetadata();
        $options['projection_query_constraints'] = $constraints->toArray();

        return $options;
    }
}
