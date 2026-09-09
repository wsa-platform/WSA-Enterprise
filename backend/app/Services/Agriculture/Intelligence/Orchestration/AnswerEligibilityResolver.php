<?php

namespace App\Services\Agriculture\Intelligence\Orchestration;

use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;
use App\Services\Agriculture\Intelligence\DTO\AnswerEligibility;
use App\Services\Agriculture\Intelligence\DTO\FusedEvidenceBundle;

/**
 * Resolves web / scientific / overall eligibility without collapsing web when scientific fails.
 */
final class AnswerEligibilityResolver
{
    public function resolve(
        FusedEvidenceBundle $fusion,
        bool $scientificAnswerEligible,
        bool $scientificPartial = false,
    ): AnswerEligibility {
        $webUsable = $this->webUsable($fusion);
        $webAnswerEligible = $webUsable;

        $details = [
            'scientific_partial' => $scientificPartial && ! $scientificAnswerEligible,
            'web_supports_scientific' => $webUsable && ($scientificAnswerEligible || $scientificPartial),
            'scientific_strong' => $scientificAnswerEligible,
            'web_consensus' => $fusion->webConsensus?->toArray(),
            'fusion_summary' => $fusion->summary,
        ];

        $reasons = [];
        if ($scientificAnswerEligible) {
            $reasons[] = 'scientific_evidence_sufficient';
        } else {
            $reasons[] = 'scientific_evidence_insufficient';
        }
        if ($webAnswerEligible) {
            $reasons[] = 'web_evidence_sufficient';
        } else {
            $reasons[] = 'web_evidence_insufficient_or_unavailable';
        }

        return AnswerEligibility::resolve(
            webAnswerEligible: $webAnswerEligible,
            scientificAnswerEligible: $scientificAnswerEligible,
            reasons: $reasons,
            details: $details,
        );
    }

    public function assertGeneralWebFallbackWorks(AnswerEligibility $eligibility): bool
    {
        return $eligibility->webAnswerEligible
            && ! $eligibility->scientificAnswerEligible
            && $eligibility->overallAnswerEligible
            && $eligibility->answerStatus === AnswerStatus::GENERAL_WEB;
    }

    private function webUsable(FusedEvidenceBundle $fusion): bool
    {
        if ($fusion->webConsensus !== null && (
            $fusion->webConsensus->hasConsensus
            || in_array($fusion->webConsensus->status, ['textual', 'consensus', 'conflicted'], true)
        ) && ($fusion->webConsensus->values !== [] || $fusion->webConsensus->representativeValue !== null)) {
            return true;
        }

        foreach ($fusion->results as $result) {
            if ($result->webEvidence !== [] && $result->hasUsableEvidence()) {
                return true;
            }
        }

        foreach ($fusion->dedupedEvidence as $item) {
            if (($item['evidence_family'] ?? '') === 'web') {
                return true;
            }
        }

        return false;
    }
}
