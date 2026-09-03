<?php

namespace App\Services\Agriculture\Diagnosis\Ranking;

use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\UncertaintyAssessment;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Ranks candidate diagnoses and computes uncertainty.
 */
class CandidateDiagnosisRanker
{
    /**
     * @param  list<CandidateDiagnosis>  $candidates
     * @param  list<VisionObservation>  $observations
     * @return array{candidates: list<CandidateDiagnosis>, uncertainty: UncertaintyAssessment}
     */
    public function rank(array $candidates, array $observations, PlantContext $context, string $imageQuality): array
    {
        $normalized = [];
        foreach ($candidates as $candidate) {
            $score = DiagnosisConfidenceBand::clampImageAloneScore($candidate->confidenceScore);
            $normalized[] = new CandidateDiagnosis(
                id: $candidate->id,
                commonName: $candidate->commonName,
                scientificName: $candidate->scientificNameVerified ? $candidate->scientificName : null,
                scientificNameVerified: $candidate->scientificNameVerified && $candidate->scientificName !== null,
                confidenceScore: $score,
                confidenceBand: DiagnosisConfidenceBand::fromScore($score),
                rank: 0,
                rationale: $candidate->rationale,
                evidence: $candidate->evidence,
                differentialNotes: $candidate->differentialNotes,
                category: $candidate->category,
            );
        }

        usort(
            $normalized,
            static fn (CandidateDiagnosis $a, CandidateDiagnosis $b): int => $b->confidenceScore <=> $a->confidenceScore
        );

        $ranked = [];
        foreach (array_values($normalized) as $index => $candidate) {
            $ranked[] = new CandidateDiagnosis(
                id: $candidate->id,
                commonName: $candidate->commonName,
                scientificName: $candidate->scientificName,
                scientificNameVerified: $candidate->scientificNameVerified,
                confidenceScore: $candidate->confidenceScore,
                confidenceBand: $candidate->confidenceBand,
                rank: $index + 1,
                rationale: $candidate->rationale,
                evidence: $candidate->evidence,
                differentialNotes: $candidate->differentialNotes,
                category: $candidate->category,
            );
        }

        return [
            'candidates' => $ranked,
            'uncertainty' => $this->assessUncertainty($ranked, $observations, $context, $imageQuality),
        ];
    }

    /**
     * @param  list<CandidateDiagnosis>  $ranked
     * @param  list<VisionObservation>  $observations
     */
    private function assessUncertainty(
        array $ranked,
        array $observations,
        PlantContext $context,
        string $imageQuality,
    ): UncertaintyAssessment {
        $factors = [];
        $missing = [];
        $imageLimited = ! in_array(strtolower($imageQuality), ['good', 'adequate', 'high'], true);
        $contextLimited = ! $context->hasUsefulContext();
        $differential = count($ranked) > 1
            && isset($ranked[0], $ranked[1])
            && abs($ranked[0]->confidenceScore - $ranked[1]->confidenceScore) < 0.12;

        if ($imageLimited) {
            $factors[] = 'image_quality_limits_confidence';
        }
        if ($contextLimited) {
            $factors[] = 'limited_plant_context';
            $missing[] = 'plant_identity_or_crop_type';
            $missing[] = 'recent_weather_or_irrigation_notes';
        }
        if ($observations === []) {
            $factors[] = 'no_clear_observations';
        }
        if ($differential) {
            $factors[] = 'close_differential_candidates';
        }
        if ($ranked === []) {
            $factors[] = 'no_candidates';
            $missing[] = 'clearer_symptom_photos';
        }

        $top = $ranked[0]->confidenceScore ?? 0.0;
        $uncertainty = DiagnosisConfidenceBand::clampImageAloneScore(1.0 - $top);
        if ($imageLimited) {
            $uncertainty = min(1.0, $uncertainty + 0.1);
        }
        if ($contextLimited) {
            $uncertainty = min(1.0, $uncertainty + 0.08);
        }
        if ($differential) {
            $uncertainty = min(1.0, $uncertainty + 0.05);
        }

        return new UncertaintyAssessment(
            overallUncertainty: $uncertainty,
            factors: $factors,
            missingSignals: array_values(array_unique($missing)),
            imageQualityLimited: $imageLimited,
            contextLimited: $contextLimited,
            differentialAmbiguity: $differential,
        );
    }
}
