<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

final class NamedPlantDiseaseRetrievalTermsTest extends TestCase
{
    public function test_multilingual_rust_surfaces_map_to_rust_and_puccinia(): void
    {
        foreach ([
            'What causes wheat rust disease and how does it affect crop yield?',
            'Quelles sont les causes de la rouille du blé et comment affecte-t-elle le rendement?',
            'ما أسباب مرض صدأ القمح وكيف يؤثر على إنتاجية المحصول؟',
            'Buğday pas hastalığının nedenleri nelerdir ve ürün verimini nasıl etkiler?',
        ] as $question) {
            $terms = AgriculturalEntityCatalog::namedPlantDiseaseRetrievalTerms($question);
            $this->assertContains('rust', $terms, $question);
            $this->assertContains('Puccinia', $terms, $question);
        }
    }

    public function test_disease_query_variants_lead_with_named_disease_identity(): void
    {
        $builder = app(ScientificSearchQueryBuilder::class);
        $plan = $this->diseasePlan('What causes wheat rust disease and how does it affect crop yield?');
        $variants = $builder->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $blob = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue(
            str_contains($blob, 'rust') || str_contains($blob, 'puccinia'),
            'Disease variants must preserve rust identity, got: '.json_encode($variants)
        );
    }

    public function test_csq_live_planner_disease_variants_preserve_rust_identity(): void
    {
        $planner = app(\App\Services\Agriculture\Research\ResearchPlanner::class);
        $builder = app(ScientificSearchQueryBuilder::class);
        foreach ([
            'What causes wheat rust disease and how does it affect crop yield?',
            'Quelles sont les causes de la rouille du blé et comment affecte-t-elle le rendement?',
        ] as $question) {
            $plan = $planner->planKnowledgeQuery(['query' => $question, 'force_execute' => true]);
            $variants = $builder->buildVariantsFromPlan($plan);
            $blob = mb_strtolower(implode(' | ', $variants));
            $this->assertTrue(
                str_contains($blob, 'rust') || str_contains($blob, 'puccinia'),
                'CSQ/live planner must preserve rust identity for: '.$question.' got '.json_encode($variants)
            );
        }
    }

    public function test_non_rust_wheat_paper_is_not_direct_for_rust_cause_question(): void
    {
        $plan = $this->diseasePlan('What causes wheat rust disease and how does it affect crop yield?');
        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Assessment of crop yield and characteristics in different bread wheat under rainfed and irrigated conditions',
            'Notably, the first year of the study exhibited decreased values for most traits under rainfed conditions compared with irrigated conditions.',
            '10.1000/irrigation.not.rust',
            null,
            'openalex',
        );
        $this->assertNotSame(
            ScientificEvidenceDirectnessAssessor::DIRECT,
            $assessment['directness'] ?? null,
            'Irrigation-only wheat yield papers must not be DIRECT for rust causation'
        );
    }

    public function test_rust_pathogen_paper_can_be_direct_for_rust_cause_question(): void
    {
        $plan = $this->diseasePlan('What causes wheat rust disease and how does it affect crop yield?');
        $assessment = app(ScientificEvidenceDirectnessAssessor::class)->assess(
            $plan,
            'Stem rust (Puccinia graminis) epidemics reduce wheat grain yield',
            'Puccinia graminis causes stem rust disease; infection decreased grain yield by 30% in susceptible cultivars.',
            '10.1000/rust.yield',
            null,
            'openalex',
        );
        $this->assertSame(
            ScientificEvidenceDirectnessAssessor::DIRECT,
            $assessment['directness'] ?? null,
            'Puccinia/rust yield papers must remain DIRECT for rust causation'
        );
    }

    private function diseasePlan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: mb_strtolower($question),
            language: 'en',
            agriculturalDomain: 'pests_diseases',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: 'disease',
            subtopic: null,
            requestedInformation: ['evidence_backed_guidance'],
            constraints: [
                'question_language' => 'en',
                'answer_language' => 'en',
                'question_type' => 'causes',
                'scientific_sense' => 'disease',
                'required_evidence_type' => 'causal_relationship',
                'scientific_intent_qualifier' => 'effect',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'disease',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'disease',
            agriculturalDomain: 'pests_diseases',
            subjectEntity: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            topics: ['wheat', 'disease'],
            subtopics: [],
            requestedInformation: ['evidence_backed_guidance'],
            evidenceRequirements: [],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }
}
