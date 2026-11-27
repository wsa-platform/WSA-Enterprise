<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use Tests\TestCase;

/**
 * Contract: Latin taxonomic tokens in multilingual questions must align to evidence
 * without depending on English/Turkish accidental "false binomials".
 */
final class ScientificEvidenceRelevanceLatinTaxonContractTest extends TestCase
{
    public function test_bare_latin_genus_matches_across_question_languages(): void
    {
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $title = 'First record of Mentha aquatica in coastal wetlands';
        $abstract = 'Mentha aquatica L. (Lamiaceae) habitat notes and distribution.';

        foreach ([
            'en' => 'What is known about Mentha wetland habitat?',
            'fr' => 'Quelles données sur Mentha et son habitat humide?',
            'ar' => 'ما المعروف عن موئل نبات Mentha في الأراضي الرطبة؟',
            'tr' => 'Mentha sulak alan habitatı hakkında ne bilinmektedir?',
        ] as $lang => $question) {
            $plan = $this->plan($question, $lang);
            $assessment = $gate->assess($plan, $title, $abstract, '10.1000/mentha.test', null, 'openalex');
            $this->assertTrue(
                (bool) ($assessment['entity_matched'] ?? false),
                "entity_matched must be true for bare Latin genus in {$lang} question"
            );
            $this->assertTrue(
                (bool) ($assessment['relevant'] ?? false),
                "evidence must remain relevant for {$lang} question"
            );
        }
    }

    public function test_unrelated_capitalized_discourse_word_is_not_an_entity_match(): void
    {
        $gate = app(ScientificEvidenceRelevanceGate::class);
        $plan = $this->plan('What is known about irrigation scheduling?', 'en');
        $assessment = $gate->assess(
            $plan,
            'Soil moisture sensors for deficit irrigation',
            'Field trial of wheat water productivity under deficit irrigation.',
            null,
            null,
            'openalex',
        );
        $this->assertFalse(
            (bool) ($assessment['entity_matched'] ?? true),
            'Discourse openers like What must not invent entity matches'
        );
    }

    public function test_faunistic_directness_is_language_agnostic_for_latin_genus_plus_bee_cues(): void
    {
        $directness = app(ScientificEvidenceDirectnessAssessor::class);
        $title = 'Rediscovery of Andrena binominata in Mediterranean habitats';
        $abstract = 'Andrena host associations and nesting habitat notes for solitary bees.';

        foreach ([
            'en' => 'What is known about Andrena wild bee host associations and habitat?',
            'fr' => 'Quelles données sur les abeilles sauvages Andrena et leur habitat?',
            'ar' => 'ما المعروف عن ارتباطات العائل والموئل لنحل Andrena البري؟',
            'tr' => 'Andrena yabani arılarının konukçu ilişkileri ve habitatı hakkında ne bilinmektedir?',
        ] as $lang => $question) {
            $plan = $this->plan($question, $lang);
            $assessment = $directness->assess($plan, $title, $abstract, '10.1000/andrena.test', null, 'openalex');
            $this->assertSame(
                ScientificEvidenceDirectnessAssessor::DIRECT,
                $assessment['directness'] ?? null,
                "faunistic directness must hold for {$lang}"
            );
        }
    }

    private function plan(string $question, string $language): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: mb_strtolower($question),
            language: $language,
            agriculturalDomain: 'general_agriculture',
            subject: null,
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'agriculture',
            subtopic: null,
            requestedInformation: ['evidence_backed_guidance'],
            constraints: [
                'question_language' => $language,
                'answer_language' => $language,
                'question_type' => 'general',
                'scientific_sense' => 'general_knowledge',
                'required_evidence_type' => 'topic_aligned_scientific_claim',
                'scientific_intent_qualifier' => 'general',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'general_agriculture',
            subjectEntity: null,
            topics: ['general_knowledge'],
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
