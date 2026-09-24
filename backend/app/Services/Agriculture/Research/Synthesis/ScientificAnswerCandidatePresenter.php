<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Presentation selection for genuine Stage-5 claims.
 *
 * Confidence is an internal filter/order variable only.
 * It does not change directness, sufficiency, or citation eligibility.
 */
final class ScientificAnswerCandidatePresenter
{
    public const PRESENTATION_THRESHOLD = 0.50;

    /**
     * @return array{
     *   answer_candidates: list<array{answer: string, result_id: string, evidence_ids: list<string>, source_ids: list<string>}>,
     *   answer_candidate_selection: array<string, mixed>
     * }
     */
    public static function fromSynthesis(AnswerSynthesisExecutionReport $synthesis): array
    {
        $eligible = [];
        foreach ($synthesis->claims as $claim) {
            if (! $claim instanceof ResearchAnswerClaim) {
                continue;
            }
            if ($claim->confidence < self::PRESENTATION_THRESHOLD) {
                continue;
            }
            $text = trim($claim->claimText);
            if ($text === '') {
                continue;
            }
            $eligible[] = $claim;
        }

        usort(
            $eligible,
            static fn (ResearchAnswerClaim $left, ResearchAnswerClaim $right): int => $right->confidence <=> $left->confidence,
        );

        $seen = [];
        $candidates = [];
        foreach ($eligible as $claim) {
            $normalized = mb_strtolower($claim->claimText);
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $candidates[] = [
                'answer' => $claim->claimText,
                'result_id' => $claim->claimId !== '' ? $claim->claimId : ('claim-'.(string) count($candidates)),
                'evidence_ids' => $claim->evidenceIds,
                'source_ids' => $claim->sourceIds,
            ];
        }

        return [
            'answer_candidates' => $candidates,
            'answer_candidate_selection' => [
                'threshold' => self::PRESENTATION_THRESHOLD,
                'input_claim_count' => count($synthesis->claims),
                'presented_count' => count($candidates),
                'excluded_below_threshold' => max(0, count($synthesis->claims) - count($eligible)),
                'confidence_exposed' => false,
                'directness_unchanged' => true,
            ],
        ];
    }
}
