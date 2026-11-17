<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalDomainCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Tests\TestCase;

/**
 * Rev 11.0.10 §16 — governed seats must be relevance-selected, not all-five-every-query.
 *
 * Does not grant SCIENTIFIC_INTEGRATION_VERIFIED.
 */
class GovernedScientificSourceEligibilityTest extends TestCase
{
    public function test_irrigation_selects_agris_excludes_taxonomy_and_phytochem(): void
    {
        $plan = $this->manualPlan(
            question: 'What is barley water requirement in Brazil?',
            domain: 'agronomy',
            intent: 'irrigation',
            topic: 'irrigation',
            crop: 'barley',
            scientificName: null,
            sense: 'crop_water_requirement',
        );

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('agris', $plan));
        $this->assertFalse($eligibility->isEligible('organic_eprints', $plan));
        $this->assertFalse($eligibility->isEligible('dr_duke', $plan));
        $this->assertFalse($eligibility->isEligible('gbif', $plan));
        $this->assertFalse($eligibility->isEligible('bhl', $plan));

        $selected = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertContains('agris', $selected);
        $this->assertNotContains('dr_duke', $selected);
        $this->assertNotContains('gbif', $selected);
        $this->assertNotContains('bhl', $selected);
        $this->assertNotContains('organic_eprints', $selected);
    }

    public function test_organic_agriculture_selects_organic_eprints_and_agris(): void
    {
        $plan = $this->manualPlan(
            question: 'Organic agriculture compost effects on soil fertility',
            domain: AgriculturalDomainCatalog::SOIL,
            intent: 'scientific_research',
            topic: 'organic_agriculture',
            crop: null,
            scientificName: null,
            sense: 'soil_fertility',
        );

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('agris', $plan));
        $this->assertTrue($eligibility->isEligible('organic_eprints', $plan));
        $this->assertFalse($eligibility->isEligible('dr_duke', $plan));
        $this->assertFalse($eligibility->isEligible('gbif', $plan));
    }

    public function test_mentha_phytochemistry_selects_dr_duke_and_related_literature(): void
    {
        $plan = $this->manualPlan(
            question: 'Mentha piperita phytochemical constituents and ethnobotany',
            domain: AgriculturalDomainCatalog::MEDICINAL_AROMATIC_PLANTS,
            intent: 'phytochemistry',
            topic: 'phytochemistry',
            crop: 'mint',
            scientificName: 'Mentha piperita',
            sense: 'phytochemistry',
            subject: ['type' => 'crop', 'value' => 'mint', 'label' => 'mint'],
        );

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('dr_duke', $plan));
        $this->assertTrue($eligibility->isEligible('agris', $plan));
        $this->assertTrue($eligibility->isEligible('gbif', $plan));
        $this->assertTrue($eligibility->isEligible('bhl', $plan));
    }

    public function test_species_classification_selects_gbif_and_bhl(): void
    {
        $plan = $this->manualPlan(
            question: 'Triticum aestivum species classification and taxonomy',
            domain: AgriculturalDomainCatalog::FIELD_CROPS,
            intent: 'taxonomy',
            topic: 'taxonomy',
            crop: 'wheat',
            scientificName: 'Triticum aestivum',
            sense: 'taxonomy',
            subject: ['type' => 'scientific_name', 'value' => 'Triticum aestivum'],
        );

        $eligibility = new GovernedScientificSourceEligibility();
        $this->assertTrue($eligibility->isEligible('gbif', $plan));
        $this->assertTrue($eligibility->isEligible('bhl', $plan));
        $this->assertTrue($eligibility->isEligible('agris', $plan));
        $this->assertFalse($eligibility->isEligible('dr_duke', $plan));
        $this->assertFalse($eligibility->isEligible('organic_eprints', $plan));
    }

    public function test_planner_land_query_does_not_select_all_five_governed_sources(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);
        $this->assertTrue($plan->isInternetFirst());
        $this->assertTrue($plan->readyForStage3);

        $selected = app(ScientificSourceSelector::class)->selectSources($plan);
        $governed = array_values(array_intersect(
            $selected,
            GovernedScientificSourceEligibility::PROFILED_KEYS,
        ));
        $this->assertContains('agris', $governed);
        $this->assertLessThan(
            5,
            count($governed),
            'Land classification must not auto-select all five ACTIVE governed seats: '.implode(',', $governed),
        );
        $this->assertNotContains('dr_duke', $governed);
    }

    public function test_selection_trace_records_relevance_exclusions(): void
    {
        $plan = $this->manualPlan(
            question: 'barley water requirement',
            domain: 'agronomy',
            intent: 'irrigation',
            topic: 'irrigation',
            crop: 'barley',
            scientificName: null,
            sense: 'crop_water_requirement',
        );
        $trace = app(ScientificSourceSelector::class)->selectionTrace($plan);
        $this->assertArrayHasKey('relevance_excluded', $trace);
        $keys = array_column($trace['relevance_excluded'], 'source_key');
        $this->assertContains('dr_duke', $keys);
        $this->assertContains('gbif', $keys);
        $this->assertStringContainsString('governed_relevance', $trace['policy']);
    }

    /**
     * @param  array{type: string, value: string, label?: string}|null  $subject
     */
    private function manualPlan(
        string $question,
        string $domain,
        string $intent,
        string $topic,
        ?string $crop,
        ?string $scientificName,
        string $sense,
        ?array $subject = null,
    ): KnowledgeQueryPlan {
        $subject ??= $crop !== null
            ? ['type' => 'crop', 'value' => $crop, 'label' => $crop]
            : ['type' => 'topic', 'value' => $topic];

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: $domain,
            subject: $subject,
            crop: $crop,
            cropId: $crop,
            scientificName: $scientificName,
            topic: $topic,
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: ['question_type' => 'research', 'scientific_sense' => $sense],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: $domain,
            subjectEntity: $subject,
            topics: [$topic],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
