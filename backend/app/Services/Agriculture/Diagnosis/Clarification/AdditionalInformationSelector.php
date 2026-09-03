<?php

namespace App\Services\Agriculture\Diagnosis\Clarification;

use App\Services\Agriculture\Diagnosis\AdditionalInfoRequest;
use App\Services\Agriculture\Diagnosis\CandidateDiagnosis;
use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\UncertaintyAssessment;

/**
 * Selective additional-information questions based on uncertainty factors.
 */
class AdditionalInformationSelector
{
    /**
     * @param  list<CandidateDiagnosis>  $candidates
     * @return list<AdditionalInfoRequest>
     */
    public function select(UncertaintyAssessment $uncertainty, PlantContext $context, array $candidates): array
    {
        $questions = [];

        if ($uncertainty->contextLimited || $context->plantName === null) {
            $questions[] = new AdditionalInfoRequest(
                id: 'plant_identity',
                question: 'What plant or crop species is shown in the image?',
                reason: 'Plant identity reduces differential ambiguity.',
                priority: 'high',
                suggestedAnswers: [],
            );
        }

        if ($context->growthStage === null) {
            $questions[] = new AdditionalInfoRequest(
                id: 'growth_stage',
                question: 'What is the current growth stage of the plant?',
                reason: 'Growth stage changes likely causes and urgency.',
                priority: 'medium',
                suggestedAnswers: ['seedling', 'vegetative', 'flowering', 'fruiting', 'senescence'],
            );
        }

        if ($uncertainty->imageQualityLimited) {
            $questions[] = new AdditionalInfoRequest(
                id: 'clearer_images',
                question: 'Can you provide a clearer close-up of the affected tissue and a whole-plant photo?',
                reason: 'Image quality currently limits observational confidence.',
                priority: 'high',
                suggestedAnswers: [],
            );
        }

        if ($uncertainty->differentialAmbiguity || count($candidates) > 1) {
            $questions[] = new AdditionalInfoRequest(
                id: 'symptom_onset',
                question: 'When did symptoms first appear, and have they spread?',
                reason: 'Onset and spread help separate biotic from abiotic causes.',
                priority: 'medium',
                suggestedAnswers: ['sudden (1-2 days)', 'gradual (1-2 weeks)', 'spreading to nearby plants'],
            );
        }

        if (in_array('recent_weather_or_irrigation_notes', $uncertainty->missingSignals, true) || $context->notes === null) {
            $questions[] = new AdditionalInfoRequest(
                id: 'environment',
                question: 'Were there recent irrigation changes, fertilizer applications, or unusual weather?',
                reason: 'Environmental context is needed for abiotic differentials.',
                priority: 'medium',
                suggestedAnswers: ['overwatering', 'drought', 'heat wave', 'recent fertilizer', 'none noted'],
            );
        }

        // Keep selective — max 4 questions, prioritize high.
        usort(
            $questions,
            static function (AdditionalInfoRequest $a, AdditionalInfoRequest $b): int {
                $order = ['high' => 0, 'medium' => 1, 'low' => 2];

                return ($order[$a->priority] ?? 9) <=> ($order[$b->priority] ?? 9);
            }
        );

        return array_slice($questions, 0, 4);
    }
}
