<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Conflict-assessment outcomes for Stage-4/5 synthesis.
 *
 * Distinct from {@see ClaimEvidenceRelationship}: that axis is claim↔evidence
 * support; this axis classifies whether opposing findings are scientifically
 * comparable contradictions or contextual differences.
 */
final class EvidenceConflictAssessmentOutcome
{
    public const GENUINE_CONFLICT = 'genuine_conflict';

    public const RESOLVED_BY_CONTEXT = 'resolved_by_context';

    public const PARTIALLY_RECONCILED = 'partially_reconciled';

    public const NOT_COMPARABLE = 'not_comparable';

    public const INSUFFICIENT_INFORMATION = 'insufficient_information';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::GENUINE_CONFLICT,
            self::RESOLVED_BY_CONTEXT,
            self::PARTIALLY_RECONCILED,
            self::NOT_COMPARABLE,
            self::INSUFFICIENT_INFORMATION,
        ];
    }
}
