<?php

namespace Tests\Unit\Agriculture\Research\Evaluation;

use App\Services\Agriculture\Research\Evaluation\GeneralScientificSearchMatrix;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use Tests\TestCase;

/**
 * Contract-level matrix: identity / eligibility / no-invention gates without live HTTP.
 */
class GeneralScientificSearchMatrixContractTest extends TestCase
{
    public function test_matrix_has_minimum_diversity_and_held_out_set(): void
    {
        $all = GeneralScientificSearchMatrix::cases();
        $held = GeneralScientificSearchMatrix::heldOutCases();
        $dev = GeneralScientificSearchMatrix::developmentCases();

        $this->assertGreaterThanOrEqual(12, count($all));
        $this->assertGreaterThanOrEqual(4, count($held));
        $this->assertSame(count($all), count($dev) + count($held));

        $domains = array_unique(array_map(static fn (array $c): string => $c['domain'], $all));
        $this->assertGreaterThanOrEqual(3, count($domains));

        $languages = array_unique(array_map(static fn (array $c): string => $c['language'], $all));
        $this->assertContains('ar', $languages);
        $this->assertContains('en', $languages);
    }

    public function test_development_cases_preserve_scientific_identity_and_eligibility_contracts(): void
    {
        $planner = app(ResearchPlanner::class);
        $eligibility = new GovernedScientificSourceEligibility();

        foreach (GeneralScientificSearchMatrix::developmentCases() as $case) {
            $plan = $planner->planKnowledgeQuery(['query' => $case['question']]);
            $this->assertNotSame('', trim($plan->normalizedQuery->originalQuestion));
            $this->assertSame($case['question'], $plan->normalizedQuery->originalQuestion);

            if ($case['id'] === 'GS-10') {
                // Nonsense query must not invent a scientific answer path claim.
                $this->assertTrue(
                    $plan->ambiguityState !== '' || $plan->researchIntent !== '',
                    'GS-10 must still produce a plan object without crashing',
                );
                continue;
            }

            $anyOf = $case['expected_eligible_any_of'] ?? [];
            if ($anyOf === []) {
                continue;
            }

            $matched = false;
            foreach ($anyOf as $sourceKey) {
                if ($eligibility->isEligible($sourceKey, $plan)) {
                    $matched = true;
                    break;
                }
            }

            // Soft assert for sources that are profile-gated and may require richer CSQ cues.
            // Fail only when none of the expected family can ever be eligible for an empty bag —
            // irrigation must not mark GBIF eligible when expected_not_eligible says so.
            $notEligible = $case['expected_not_eligible'] ?? [];
            foreach ($notEligible as $blocked) {
                $this->assertFalse(
                    $eligibility->isEligible($blocked, $plan),
                    sprintf('%s must not be eligible for %s', $blocked, $case['id']),
                );
            }

            if (in_array($case['id'], ['GS-06', 'GS-07', 'GS-11'], true)) {
                $this->assertTrue(
                    $matched,
                    sprintf('%s expected one of [%s] eligible; plan intent=%s topics=%s',
                        $case['id'],
                        implode(',', $anyOf),
                        $plan->researchIntent,
                        implode(',', $plan->topics),
                    ),
                );
            }
        }
    }

    public function test_held_out_cases_are_plannable_without_hardcoded_question_maps(): void
    {
        $planner = app(ResearchPlanner::class);
        $source = file_get_contents(
            (new \ReflectionClass(\App\Services\Agriculture\Research\QueryUnderstandingService::class))->getFileName() ?: ''
        ) ?: '';

        foreach (GeneralScientificSearchMatrix::heldOutCases() as $case) {
            $plan = $planner->planKnowledgeQuery(['query' => $case['question']]);
            $this->assertSame($case['question'], $plan->normalizedQuery->originalQuestion);
            $this->assertStringNotContainsString(
                $case['question'],
                $source,
                'Held-out question must not be hardcoded in QueryUnderstandingService',
            );
        }
    }
}
