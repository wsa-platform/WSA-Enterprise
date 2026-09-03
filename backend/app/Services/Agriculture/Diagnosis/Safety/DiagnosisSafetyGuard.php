<?php

namespace App\Services\Agriculture\Diagnosis\Safety;

use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\DiagnosisStatus;
use App\Services\Agriculture\Diagnosis\SafetyLimitations;
use App\Services\Agriculture\Diagnosis\UncertaintyAssessment;

/**
 * Safety layer: caps certainty, strips unsafe management claims, separates diagnosis vs management.
 */
class DiagnosisSafetyGuard
{
    /** @var list<string> */
    private const DOSAGE_PATTERNS = [
        '/\b\d+(\.\d+)?\s?(ml|mg|g|kg|l|liter|litre|ppm|%)\b/i',
        '/\b(dose|dosage|concentration|ppm|spray at)\b/i',
        '/\b(pesticide|fungicide|insecticide|herbicide)\s+\w+\s+\d+/i',
    ];

    /**
     * @param  list<CandidateDiagnosis>  $candidates
     * @param  list<string>  $managementNotes
     * @return array{
     *     candidates: list<CandidateDiagnosis>,
     *     management_notes: list<string>,
     *     safety: SafetyLimitations,
     *     status: string
     * }
     */
    public function apply(
        array $candidates,
        UncertaintyAssessment $uncertainty,
        array $managementNotes = [],
        bool $allowManagementGuidance = true,
    ): array {
        $safeCandidates = [];
        foreach ($candidates as $candidate) {
            $score = DiagnosisConfidenceBand::clampImageAloneScore($candidate->confidenceScore);
            if ($score >= 1.0) {
                $score = DiagnosisConfidenceBand::MAX_IMAGE_ALONE_SCORE;
            }

            $scientificVerified = $candidate->scientificNameVerified
                && is_string($candidate->scientificName)
                && trim($candidate->scientificName) !== '';

            $safeCandidates[] = new CandidateDiagnosis(
                id: $candidate->id,
                commonName: $candidate->commonName,
                scientificName: $scientificVerified ? $candidate->scientificName : null,
                scientificNameVerified: $scientificVerified,
                confidenceScore: $score,
                confidenceBand: DiagnosisConfidenceBand::fromScore($score),
                rank: $candidate->rank,
                rationale: $this->stripUnsafeText($candidate->rationale),
                evidence: $candidate->evidence,
                differentialNotes: array_values(array_filter(array_map(
                    fn (string $note): string => $this->stripUnsafeText($note),
                    $candidate->differentialNotes,
                ))),
                category: $candidate->category,
            );
        }

        $safeManagement = [];
        if ($allowManagementGuidance) {
            foreach ($managementNotes as $note) {
                $cleaned = $this->stripUnsafeText($note);
                if ($cleaned !== '' && ! $this->containsDosageOrPesticideClaim($cleaned)) {
                    $safeManagement[] = $cleaned;
                }
            }
        }

        if ($safeManagement === [] && $allowManagementGuidance && $safeCandidates !== []) {
            $safeManagement[] = 'Monitor affected tissue, improve cultural conditions where appropriate, and seek local expert confirmation before applying chemical controls.';
            $safeManagement[] = 'Management actions are distinct from diagnosis and require local agronomic judgment.';
        }

        return [
            'candidates' => $safeCandidates,
            'management_notes' => $safeManagement,
            'safety' => SafetyLimitations::defaults(),
            'status' => $this->resolveStatus($safeCandidates, $uncertainty),
        ];
    }

    /**
     * @param  list<CandidateDiagnosis>  $candidates
     */
    private function resolveStatus(array $candidates, UncertaintyAssessment $uncertainty): string
    {
        if ($candidates === []) {
            return DiagnosisStatus::UNCERTAIN;
        }

        $top = $candidates[0];

        if ($top->confidenceBand === DiagnosisConfidenceBand::INSUFFICIENT || $top->confidenceScore < 0.30) {
            return DiagnosisStatus::UNCERTAIN;
        }

        if ($uncertainty->contextLimited && $top->confidenceScore < 0.60) {
            return DiagnosisStatus::INSUFFICIENT_CONTEXT;
        }

        if ($top->confidenceBand === DiagnosisConfidenceBand::HIGH && ! $uncertainty->differentialAmbiguity) {
            return DiagnosisStatus::DIAGNOSED;
        }

        if (in_array($top->confidenceBand, [DiagnosisConfidenceBand::HIGH, DiagnosisConfidenceBand::MODERATE], true)) {
            return DiagnosisStatus::PROBABLE;
        }

        return DiagnosisStatus::UNCERTAIN;
    }

    private function stripUnsafeText(string $text): string
    {
        $cleaned = trim($text);
        foreach (self::DOSAGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $cleaned) === 1) {
                return 'Specific chemical dosages and concentrations are withheld for safety; consult a qualified advisor.';
            }
        }

        return $cleaned;
    }

    private function containsDosageOrPesticideClaim(string $text): bool
    {
        foreach (self::DOSAGE_PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }
}
