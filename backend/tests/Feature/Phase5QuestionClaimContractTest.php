<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimEvidenceMapper;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * Phase 5 Unit A — Question → Claims → Evidence → AnswerStatement contracts.
 */
class Phase5QuestionClaimContractTest extends TestCase
{
    public function test_single_claim_extracted_independent_of_evidence(): void
    {
        $plan = $this->plan(
            question: 'What is the production quantity of wheat in Italy in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
                'requested_property' => 'quantity',
            ],
            location: 'Italy',
        );

        $claims = (new QuestionClaimExtractor)->extract($plan);

        $this->assertCount(1, $claims);
        $this->assertSame('qc-1', $claims[0]->claimId);
        $this->assertSame('wheat', $claims[0]->entity);
        $this->assertSame('quantity', $claims[0]->property);
        $this->assertSame('Italy', $claims[0]->location);
        $this->assertSame('2022', $claims[0]->time);
        $this->assertSame('en', $claims[0]->answerLanguage);
    }

    public function test_multi_claim_from_requested_information(): void
    {
        $plan = $this->plan(
            question: 'What is wheat production and yield in Italy?',
            requested: ['quantity', 'yield'],
            constraints: ['answer_language' => 'en'],
            location: 'Italy',
        );

        $claims = (new QuestionClaimExtractor)->extract($plan);

        $this->assertCount(2, $claims);
        $this->assertSame(['qc-1', 'qc-2'], array_map(static fn ($c) => $c->claimId, $claims));
        $this->assertSame(['quantity', 'yield'], array_map(static fn ($c) => $c->property, $claims));
    }

    public function test_evidence_presence_does_not_create_question_claims(): void
    {
        $plan = $this->plan(
            question: 'Wheat irrigation requirements',
            requested: ['irrigation'],
            constraints: ['answer_language' => 'en'],
        );
        $before = (new QuestionClaimExtractor)->extract($plan);
        $evidence = [
            $this->evidence('e-extra-1', ClaimEvidenceRelationship::SUPPORTED),
            $this->evidence('e-extra-2', ClaimEvidenceRelationship::SUPPORTED),
        ];
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->validation($evidence, sufficient: true),
            $evidence,
        );

        $this->assertCount(count($before), $matrix['question_claims']);
        $this->assertSame('qc-1', $matrix['question_claims'][0]['claim_id']);
        $this->assertCount(2, $matrix['claim_evidence_bindings']);
    }

    public function test_answer_language_locales_survive(): void
    {
        foreach (['ar', 'en', 'tr', 'fr'] as $lang) {
            $plan = $this->plan(
                question: 'Wheat yield question',
                language: $lang,
                requested: ['yield'],
                constraints: ['answer_language' => $lang],
            );
            $claims = (new QuestionClaimExtractor)->extract($plan);
            $this->assertSame($lang, $claims[0]->answerLanguage, $lang);
            $this->assertSame($lang, $claims[0]->questionLanguage, $lang);
        }
    }

    public function test_comparison_without_second_entity_is_explicit_limitation(): void
    {
        $plan = $this->plan(
            question: 'Compare wheat and maize yield',
            requested: ['yield'],
            constraints: [
                'answer_language' => 'en',
                'question_type' => 'comparison',
            ],
            intent: 'comparison',
        );

        $claims = (new QuestionClaimExtractor)->extract($plan);

        $this->assertContains('comparison_decomposition_unsupported', $claims[0]->limitations);
    }

    public function test_home_and_crop_scope_flag(): void
    {
        $home = (new QuestionClaimExtractor)->extract($this->plan(
            question: 'Home research question',
            requested: ['classification'],
            constraints: ['answer_language' => 'en'],
        ));
        $crop = (new QuestionClaimExtractor)->extract($this->plan(
            question: 'Crop research question',
            requested: ['classification'],
            constraints: ['answer_language' => 'en'],
            context: ['selected_crop_id' => 'wheat', 'selected_crop_name' => 'Wheat'],
        ));

        $this->assertSame('home', $home[0]->scope['home_or_crop']);
        $this->assertSame('crop', $crop[0]->scope['home_or_crop']);
    }

    /**
     * @param  list<string>  $requested
     * @param  array<string, mixed>  $constraints
     * @param  array<string, mixed>  $context
     */
    private function plan(
        string $question,
        array $requested = [],
        array $constraints = [],
        ?string $location = null,
        string $language = 'en',
        string $intent = 'scientific_explanation',
        array $context = [],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: $language,
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'production',
            subtopic: null,
            requestedInformation: $requested,
            constraints: $constraints,
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['production'],
            subtopics: [],
            requestedInformation: $requested,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: $context,
            readyForStage3: true,
        );
    }

    private function evidence(
        string $id,
        string $relationship,
        string $directness = ScientificEvidenceDirectnessAssessor::DIRECT,
        bool $conflict = false,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'journal_article',
            publicationTitle: 'Title '.$id,
            authors: ['A'],
            institution: null,
            journal: 'J',
            doi: '10.1000/'.$id,
            url: 'https://example.test/'.$id,
            publicationYear: 2020,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'agronomy',
            claimTopic: 'production',
            evidenceText: 'Wheat production evidence '.$id,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 0.8,
            qualityFactors: ['evidence_directness' => $directness],
            sourceAttribution: ['evidence_directness' => $directness],
            hasConflict: $conflict,
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validation(array $items, bool $sufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $sufficient ? 'validation_completed' : 'no_valid_evidence',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $sufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }
}
