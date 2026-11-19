<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;

/**
 * Workstream B restoration harness — ginger temperature product scenario.
 *
 * Replays the documented user question from e2e-tmp/acc-q1-validate.json and
 * records stage counts. Does not invent answers when evidence is insufficient.
 */
final class ScientificSearchRestorationHarness
{
    public const ORIGINAL_USER_QUESTION = 'ما هو تأثير درجة الحرارة على نبات الزنجبيل؟';

    /**
     * @param  array{
     *   composer?: AnswerComposer,
     *   limit?: int,
     *   source_keys?: list<string>|null
     * }  $options
     * @return array<string, mixed>
     */
    public function runGingerTemperatureScenario(array $options = []): array
    {
        $started = microtime(true);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => self::ORIGINAL_USER_QUESTION,
        ]);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $budget = ScientificSearchVariantBudget::apply($variants);
        $selector = app(ScientificSourceSelector::class);
        $selection = $selector->selectionTrace($plan);

        $limit = max(1, (int) ($options['limit'] ?? 8));
        $sourceKeys = $options['source_keys'] ?? null;
        $search = app(MultiSourceScientificSearchOrchestrator::class)->execute($plan, $limit, $sourceKeys);

        $validation = app(AgriculturalScientificValidationService::class)->validate($plan, $search);
        $composer = $options['composer'] ?? app(AnswerComposer::class);
        $synthesis = $composer->compose($plan, $validation);

        $raw = count($search->results);
        $survivors = count($search->deduplicatedResults);
        $accepted = $validation->validatedCount;
        $answer = trim((string) $synthesis->answer);
        $validAnswer = $answer !== ''
            && ! in_array($synthesis->status, [
                'no_search_results',
                'all_results_rejected_by_relevance',
                'no_validated_evidence',
                'needs_clarification',
                'insufficient_evidence',
            ], true);

        return [
            'ok' => true,
            'scenario' => 'ginger_temperature_plant_growth',
            'scenario_label' => 'TEST_SCENARIO_DOCUMENTED_USER_QUESTION',
            'original_user_question' => self::ORIGINAL_USER_QUESTION,
            'detected_language' => $plan->normalizedQuery->language,
            'scientific_name' => $plan->normalizedQuery->scientificName,
            'crop_id' => $plan->normalizedQuery->cropId,
            'scientific_sense' => $plan->normalizedQuery->constraints['scientific_sense'] ?? null,
            'query_variants_raw' => $variants,
            'query_variants_budgeted' => $budget['variants'],
            'selected_sources' => $selection['selected'],
            'relevance_excluded' => $selection['relevance_excluded'],
            'capability_denied' => $selection['capability_denied'],
            'search_status' => $search->status,
            'raw_result_count' => $raw,
            'survivor_count' => $survivors,
            'validation_status' => $validation->status,
            'validated_count' => $accepted,
            'rejected_count' => $validation->rejectedCount,
            'composer_status' => $synthesis->status,
            'composer_answer_present' => $answer !== '',
            'valid_scientific_answer' => $validAnswer,
            'failure_class' => $validation->observability['failure_class'] ?? null,
            'gates' => [
                'B1_search_execution' => $search->attemptedSources !== [],
                'B2_relevant_retrieval' => $survivors > 0,
                'B3_evidence_acceptance' => $accepted > 0,
                'B4_evidence_sufficiency' => $accepted > 0,
                'B5_scientific_answer' => $validAnswer,
                'B6_provenance' => $accepted > 0,
                'B9_negative_classification' => in_array($validation->status, [
                    'no_search_results',
                    'all_results_rejected_by_relevance',
                    'no_validated_evidence',
                    'validation_completed',
                    'validation_completed_with_rejections',
                ], true) || $validAnswer,
            ],
            'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }
}
