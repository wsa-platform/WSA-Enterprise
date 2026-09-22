<?php

namespace App\Services\Agriculture\Research;

use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;

/**
 * Phase 7 P7-U1 / STRUCT-03 — Crop dual-emit response packaging.
 *
 * Stage 5 AnswerSynthesisExecutionReport is the sole scientific answer source.
 * Legacy Crop profile fields remain siblings for compatibility (not a second answer).
 */
final class CropCanonicalStage5Response
{
    /**
     * Promote Home-compatible Stage 5 client fields onto the Crop legacy profile root.
     *
     * @param  array<string, mixed>  $legacyProfile  Legacy sections/load_state/crop/library/… (+ research_agent nest)
     * @return array<string, mixed>
     */
    public static function dualEmit(array $legacyProfile, AnswerSynthesisExecutionReport $synthesisReport): array
    {
        $canonical = self::canonicalClientFields($synthesisReport);

        // Legacy keys first; Stage 5 client fields win on name collisions (e.g. status).
        // Legacy-only keys (sections, load_state, crop, library, references, title, …) are preserved.
        return array_merge($legacyProfile, $canonical);
    }

    /**
     * Same Stage 5 client fields Home receives via synthesisReport->toArray() merge,
     * plus explicit root `language` for the Master Plan canonical contract.
     *
     * @return array<string, mixed>
     */
    public static function canonicalClientFields(AnswerSynthesisExecutionReport $synthesisReport): array
    {
        $canonical = $synthesisReport->toArray();
        // Authoritative answer language from the same report instance (never UI locale).
        $canonical['language'] = $synthesisReport->language;

        return $canonical;
    }
}
