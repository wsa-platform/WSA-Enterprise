<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Canonical Stage-3/4/Composer evidence pipeline statuses (ISS-EVD-002).
 *
 * Producers must emit these strings. Legacy aliases are accepted by consumers
 * via {@see self::canonicalize()}.
 */
final class EvidencePipelineStatus
{
    /** Search completed with zero raw hits. */
    public const NO_SEARCH_RESULTS = 'no_search_results';

    /** Raw hits existed but relevance removed all survivors. */
    public const ALL_RESULTS_REJECTED_BY_RELEVANCE = 'all_results_rejected_by_relevance';

    /** Validation finished with no usable validated evidence items. */
    public const NO_VALIDATED_EVIDENCE = 'no_validated_evidence';

    /** Validated evidence exists but is insufficient to answer. */
    public const INSUFFICIENT_EVIDENCE = 'insufficient_evidence';

    /** Clarification required before Stage 3. */
    public const NEEDS_CLARIFICATION = 'needs_clarification';

    /**
     * Map legacy / divergent producer strings onto the canonical contract.
     */
    public static function canonicalize(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return $status;
        }

        return match ($status) {
            // Historical validator spelling (ISS-EVD-002).
            'no_valid_evidence' => self::NO_VALIDATED_EVIDENCE,
            // Singular typo / docblock-only form.
            'no_search_result' => self::NO_SEARCH_RESULTS,
            default => $status,
        };
    }

    /** @return list<string> */
    public static function emptyEvidenceFamily(): array
    {
        return [
            self::NO_SEARCH_RESULTS,
            self::ALL_RESULTS_REJECTED_BY_RELEVANCE,
            self::NO_VALIDATED_EVIDENCE,
            self::INSUFFICIENT_EVIDENCE,
            self::NEEDS_CLARIFICATION,
        ];
    }
}
