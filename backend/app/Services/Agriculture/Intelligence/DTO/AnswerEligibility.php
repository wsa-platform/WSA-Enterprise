<?php

namespace App\Services\Agriculture\Intelligence\DTO;

use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;

/**
 * Separated web / scientific / overall eligibility (ADR-001 non-negotiable).
 *
 * scientific_answer_eligible=false MUST NOT force overall_answer_eligible=false
 * when web_answer_eligible=true → answer_status=GENERAL_WEB.
 */
final class AnswerEligibility
{
    /**
     * @param  list<string>  $reasons
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly bool $webAnswerEligible,
        public readonly bool $scientificAnswerEligible,
        public readonly bool $overallAnswerEligible,
        public readonly string $answerStatus,
        public readonly array $reasons = [],
        public readonly array $details = [],
    ) {}

    public static function resolve(
        bool $webAnswerEligible,
        bool $scientificAnswerEligible,
        array $reasons = [],
        array $details = [],
    ): self {
        $status = match (true) {
            $scientificAnswerEligible && $webAnswerEligible => AnswerStatus::SCIENTIFIC_VERIFIED,
            $scientificAnswerEligible && ! $webAnswerEligible => AnswerStatus::SCIENTIFIC_VERIFIED,
            ! $scientificAnswerEligible && $webAnswerEligible => AnswerStatus::GENERAL_WEB,
            default => AnswerStatus::INSUFFICIENT,
        };

        // Web + limited scientific support (scientific partial but not fully eligible)
        if (! $scientificAnswerEligible && $webAnswerEligible && ($details['scientific_partial'] ?? false)) {
            $status = AnswerStatus::WEB_SUPPORTED_SCIENTIFIC_LIMITED;
        }

        // When both scientific and web are strong, prefer SCIENTIFIC_VERIFIED (already set).
        // When scientific eligible and web also present with consensus support flag:
        if ($scientificAnswerEligible && ($details['web_supports_scientific'] ?? false) && ! ($details['scientific_strong'] ?? true)) {
            $status = AnswerStatus::WEB_SUPPORTED_SCIENTIFIC_LIMITED;
        }

        $overall = $scientificAnswerEligible || $webAnswerEligible;

        return new self(
            webAnswerEligible: $webAnswerEligible,
            scientificAnswerEligible: $scientificAnswerEligible,
            overallAnswerEligible: $overall,
            answerStatus: $overall ? $status : AnswerStatus::INSUFFICIENT,
            reasons: $reasons,
            details: $details,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'web_answer_eligible' => $this->webAnswerEligible,
            'scientific_answer_eligible' => $this->scientificAnswerEligible,
            'overall_answer_eligible' => $this->overallAnswerEligible,
            'answer_status' => $this->answerStatus,
            'reasons' => $this->reasons,
            'details' => $this->details,
        ];
    }
}
