<?php

namespace App\Services\Agriculture\Diagnosis\Knowledge;

use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\DiagnosisEvidence;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Heuristic in-process knowledge support (no DB, no Research Agent).
 * Scientific names are never invented — always null/unverified.
 */
class HeuristicDiagnosisKnowledgeSupport implements DiagnosisKnowledgeSupportInterface
{
    /**
     * @param  list<VisionObservation>  $observations
     * @return list<CandidateDiagnosis>
     */
    public function suggestCandidates(PlantContext $context, array $observations): array
    {
        if ($observations === []) {
            return [];
        }

        $joined = strtolower(implode(' ', array_map(
            static fn (VisionObservation $o): string => $o->type.' '.$o->description.' '.implode(' ', $o->supportingCues),
            $observations,
        )));

        $observationIds = array_map(static fn (VisionObservation $o): string => $o->id, $observations);
        $candidates = [];

        foreach ($this->patterns() as $pattern) {
            $hits = 0;
            foreach ($pattern['keywords'] as $keyword) {
                if (str_contains($joined, $keyword)) {
                    $hits++;
                }
            }

            if ($hits === 0) {
                continue;
            }

            $score = DiagnosisConfidenceBand::clampImageAloneScore(
                0.35 + (0.12 * min(3, $hits)) + ($context->hasUsefulContext() ? 0.08 : 0.0)
            );

            $candidates[] = new CandidateDiagnosis(
                id: $pattern['id'],
                commonName: $pattern['common_name'],
                scientificName: null,
                scientificNameVerified: false,
                confidenceScore: $score,
                confidenceBand: DiagnosisConfidenceBand::fromScore($score),
                rank: 0,
                rationale: $pattern['rationale'],
                evidence: [
                    new DiagnosisEvidence(
                        kind: 'visual_pattern_match',
                        summary: $pattern['evidence_summary'],
                        observationIds: $observationIds,
                        sourceLabel: 'heuristic_pattern_catalog',
                        fromKnowledgeSupport: true,
                    ),
                ],
                differentialNotes: $pattern['differentials'],
                category: $pattern['category'],
            );
        }

        if ($candidates === []) {
            $score = DiagnosisConfidenceBand::clampImageAloneScore(0.32 + ($context->hasUsefulContext() ? 0.05 : 0.0));
            $candidates[] = new CandidateDiagnosis(
                id: 'unspecified_abiotic_or_biotic_stress',
                commonName: 'Unspecified plant stress (visual symptoms present)',
                scientificName: null,
                scientificNameVerified: false,
                confidenceScore: $score,
                confidenceBand: DiagnosisConfidenceBand::fromScore($score),
                rank: 0,
                rationale: 'Visible symptoms were detected but did not strongly match a specific catalog pattern.',
                evidence: [
                    new DiagnosisEvidence(
                        kind: 'observation_aggregate',
                        summary: 'General visual symptom evidence without a specific named match.',
                        observationIds: $observationIds,
                        sourceLabel: 'heuristic_fallback',
                        fromKnowledgeSupport: true,
                    ),
                ],
                differentialNotes: [
                    'Could be biotic (pathogen/pest) or abiotic (nutrient, water, environment).',
                    'Additional context or laboratory confirmation may be required.',
                ],
                category: 'unspecified',
            );
        }

        return $candidates;
    }

    /**
     * @return list<array{
     *     id: string,
     *     common_name: string,
     *     category: string,
     *     keywords: list<string>,
     *     rationale: string,
     *     evidence_summary: string,
     *     differentials: list<string>
     * }>
     */
    private function patterns(): array
    {
        return [
            [
                'id' => 'leaf_spot_syndrome',
                'common_name' => 'Leaf spot syndrome',
                'category' => 'foliar',
                'keywords' => ['spot', 'lesion', 'blotch', 'necrosis', 'leaf_spot'],
                'rationale' => 'Observed foliar spotting/lesion patterns commonly associated with leaf spot syndromes.',
                'evidence_summary' => 'Matched leaf spot / lesion visual cues.',
                'differentials' => ['Early blight-like patterns', 'Bacterial leaf spot', 'Abiotic leaf scorch'],
            ],
            [
                'id' => 'powdery_mildew_like',
                'common_name' => 'Powdery mildew-like symptoms',
                'category' => 'fungal_like',
                'keywords' => ['powder', 'white coating', 'mildew', 'dusty'],
                'rationale' => 'White powdery coating cues are consistent with powdery mildew-like presentations.',
                'evidence_summary' => 'Matched powdery / white coating visual cues.',
                'differentials' => ['Downy mildew-like symptoms', 'Dust / residue on leaves'],
            ],
            [
                'id' => 'chlorosis_syndrome',
                'common_name' => 'Chlorosis / yellowing syndrome',
                'category' => 'abiotic_or_nutrient',
                'keywords' => ['yellow', 'chlorosis', 'pale', 'yellowing'],
                'rationale' => 'Yellowing/chlorosis cues may indicate nutrient imbalance, water stress, or disease.',
                'evidence_summary' => 'Matched chlorosis / yellowing visual cues.',
                'differentials' => ['Nitrogen deficiency-like', 'Iron chlorosis-like', 'Viral yellowing'],
            ],
            [
                'id' => 'wilting_syndrome',
                'common_name' => 'Wilting / collapse syndrome',
                'category' => 'vascular_or_water',
                'keywords' => ['wilt', 'wilting', 'collapse', 'drooping', 'flaccid'],
                'rationale' => 'Wilting cues can indicate water stress, root issues, or vascular disease.',
                'evidence_summary' => 'Matched wilting / collapse visual cues.',
                'differentials' => ['Drought stress', 'Root rot-like', 'Vascular wilt-like'],
            ],
            [
                'id' => 'insect_damage_like',
                'common_name' => 'Insect feeding damage-like symptoms',
                'category' => 'pest',
                'keywords' => ['chew', 'hole', 'insect', 'frass', 'bite', 'mines'],
                'rationale' => 'Feeding damage cues suggest possible insect involvement.',
                'evidence_summary' => 'Matched insect feeding / hole visual cues.',
                'differentials' => ['Mechanical damage', 'Slug/snail damage', 'Hail injury'],
            ],
        ];
    }
}
