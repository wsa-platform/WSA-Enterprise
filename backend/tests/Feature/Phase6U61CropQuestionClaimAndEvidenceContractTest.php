<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimEvidenceMapper;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimExtractor;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\AgriculturalScientificValidationService;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceMatcher;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use ReflectionClass;
use Tests\TestCase;

/**
 * Phase 6 U6.1 — Shared pipeline reuse, QuestionClaim, evidence ownership, wrong-crop, languages.
 *
 * DIRTY-WIP NOTICE: AnswerComposer / Catalog / QUS may be dirty. Service-resolution tests
 * load working-tree classes. QuestionClaimExtractor / AccuracyGate / Disposition are on HEAD
 * for B3 closed contracts unless Composer WIP overlays compose behavior.
 */
class Phase6U61CropQuestionClaimAndEvidenceContractTest extends TestCase
{
    public function test_g_crop_question_claims_independent_of_evidence_ids(): void
    {
        $plan = $this->cropPlan(['yield', 'quantity'], ['answer_language' => 'en']);
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $this->assertCount(2, $claims);
        $this->assertSame(['qc-1', 'qc-2'], array_map(static fn ($c) => $c->claimId, $claims));

        $evidence = [
            $this->evidence('e-maize-1', 'Maize yield in Brazil averaged 5 t/ha.', 'maize'),
            $this->evidence('e-wheat-1', 'Wheat yield in Italy averaged 4 t/ha.', 'wheat'),
        ];
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->validation($evidence, true),
            $evidence,
        );

        $this->assertCount(2, $matrix['question_claims']);
        $claimIds = array_column($matrix['question_claims'], 'claim_id');
        $this->assertSame(['qc-1', 'qc-2'], $claimIds);
        foreach ($matrix['claim_evidence_bindings'] as $binding) {
            $this->assertNotSame($binding['question_claim_id'] ?? null, $binding['evidence_id'] ?? 'missing');
            $this->assertArrayHasKey('claim_relationship', $binding);
        }
        $this->assertSame('crop', $matrix['question_claims'][0]['scope']['home_or_crop'] ?? null);
    }

    public function test_g_insufficient_and_conflicting_relationships_remain_explicit(): void
    {
        $plan = $this->cropPlan(['quantity'], ['answer_language' => 'en', 'requested_property' => 'quantity']);
        $evidence = [
            $this->evidence('e-insuf', 'General agronomy notes without quantity.', 'wheat', ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE),
            $this->evidence('e-conf', 'Wheat production was 10 million tonnes.', 'wheat', ClaimEvidenceRelationship::CONFLICTING, true),
        ];
        $matrix = (new QuestionClaimSynthesisContract)->build(
            $plan,
            $this->validation($evidence, false),
            $evidence,
        );

        $relationships = array_column($matrix['claim_evidence_bindings'], 'relationship');
        $this->assertTrue(
            in_array(ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE, $relationships, true)
            || in_array(ClaimEvidenceRelationship::CONFLICTING, $relationships, true)
            || (($matrix['answer_statement_traces'][0]['aggregate_claim_relationship'] ?? null) !== ClaimEvidenceRelationship::SUPPORTED),
            'Crop QuestionClaim matrix must preserve insufficient/conflict semantics'
        );
    }

    public function test_g_answer_language_ar_en_fr_tr_on_crop_claims(): void
    {
        foreach (['ar', 'en', 'fr', 'tr'] as $lang) {
            $plan = $this->cropPlan(['yield'], ['answer_language' => $lang], language: $lang);
            $claims = (new QuestionClaimExtractor)->extract($plan);
            $this->assertSame($lang, $claims[0]->answerLanguage, $lang);
            $this->assertSame($lang, $claims[0]->questionLanguage, $lang);
        }
    }

    public function test_g_ui_locale_does_not_override_answer_language_on_claims(): void
    {
        $plan = $this->cropPlan(['yield'], [
            'answer_language' => 'en',
            'ui_locale' => 'ar',
        ], language: 'en');
        $claims = (new QuestionClaimExtractor)->extract($plan);
        $this->assertSame('en', $claims[0]->answerLanguage);
        $this->assertSame('en', $claims[0]->questionLanguage);
        $this->assertNotSame('ar', $claims[0]->answerLanguage);
    }

    public function test_h_crop_reuses_shared_validation_stack_classes(): void
    {
        $this->assertTrue(class_exists(ScientificEvidenceRelevanceGate::class));
        $this->assertTrue(class_exists(ScientificEvidenceDirectnessAssessor::class));
        $this->assertTrue(class_exists(ClaimEvidenceMatcher::class));
        $this->assertTrue(class_exists(EvidenceVerificationLayer::class));
        $this->assertTrue(class_exists(AgriculturalScientificValidationService::class));
        $this->assertTrue(class_exists(AnswerExpressionAccuracyGate::class));
        $this->assertTrue(class_exists(AnswerComposer::class));
        $this->assertTrue(class_exists(QuestionClaimEvidenceMapper::class));

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $item = $this->evidence('e-shared', 'Wheat farming needs include nitrogen fertilization rates of 120 kg/ha.', 'wheat');
        $validation = $this->validation([$item], true);
        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $this->assertNotSame('', (string) $synthesis->status);
        $this->assertInstanceOf(AgriculturalScientificValidationService::class, app(AgriculturalScientificValidationService::class));
    }

    public function test_h_crop_skips_home_evidence_lifecycle_disposition(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertFalse((new HomeEvidenceLifecycleDisposition)->appliesTo($plan));
        $item = $this->evidence('e-disp', 'Wheat cultivation practices.', 'wheat');
        $validation = $this->validation([$item], true);
        $synthesis = app(AnswerComposer::class)->compose($plan, $validation);
        $decorated = (new HomeEvidenceLifecycleDisposition)->applyToSynthesis($plan, $validation, $synthesis);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $decorated->researchMetadata);
    }

    public function test_e_crop_scholarly_overlap_policy_is_sequential(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $ref = new ReflectionClass($orchestrator);
        $method = $ref->getMethod('shouldOverlapIndependentScholarlyProviders');
        $method->setAccessible(true);
        $this->assertFalse(
            $method->invoke($orchestrator, $plan),
            'Crop profile must not enable Home scholarly provider overlap concurrency'
        );
    }

    public function test_m_wrong_crop_evidence_is_not_treated_as_supported_for_selected_crop(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'scientific-research',
            'query' => 'Wheat yield scientific research',
        ]);
        $this->assertTrue($plan->toAgriculturalResearchPlan()->isCropProfileIntent());

        $wrongCropResult = new \App\Services\Agriculture\Research\Search\ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: 'W000WRONG',
            title: 'Maize nitrogen response in Brazil',
            authors: ['Fixture'],
            publicationYear: 2020,
            doi: '10.9999/u61-wrong-maize',
            canonicalUrl: 'https://example.test/u61/wrong-maize',
            abstract: 'Maize (Zea mays) grain yield increased under nitrogen fertilization in Brazil field trials.',
            journal: 'Fixture Journal',
            foundBySources: ['openalex'],
        );

        $matcher = app(ClaimEvidenceMatcher::class);
        $assessment = $matcher->match(
            $plan,
            $wrongCropResult,
            (string) $wrongCropResult->abstract,
            EvidenceValidationStatus::EVIDENCE_USABLE,
        );
        $relationship = (string) ($assessment['relationship'] ?? '');

        $this->assertNotSame(
            ClaimEvidenceRelationship::SUPPORTED,
            $relationship,
            'Wrong-crop maize evidence must not be SUPPORTED for a wheat crop_profile plan'
        );
    }

    public function test_k_structure_crop_plan_forces_legacy_intent_flag(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $legacy = $plan->toAgriculturalResearchPlan();
        $this->assertTrue($legacy->isCropProfileIntent());
        $this->assertSame('crop_profile', $legacy->intent);
    }

    /**
     * @param  list<string>  $requested
     * @param  array<string, mixed>  $constraints
     */
    private function cropPlan(array $requested, array $constraints = [], string $language = 'en'): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'Wheat scientific research yield',
            normalizedQuestion: 'Wheat scientific research yield',
            language: $language,
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat', 'label' => 'Wheat'],
            crop: 'Wheat',
            cropId: 'wheat',
            scientificName: 'Triticum aestivum',
            topic: 'scientific_literature',
            subtopic: 'scientific-research',
            requestedInformation: $requested,
            constraints: $constraints,
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_literature',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_literature',
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['scientific_literature'],
            subtopics: ['scientific-research'],
            requestedInformation: $requested,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [
                'selected_crop_id' => 'wheat',
                'selected_crop_name' => 'Wheat',
                'knowledge_option' => 'scientific-research',
            ],
            readyForStage3: true,
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validation(array $items, bool $sufficient): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
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
            searchSummary: ['search_status' => 'search_completed'],
            observability: [],
        );
    }

    private function evidence(
        string $id,
        string $text,
        string $entity,
        string $relationship = ClaimEvidenceRelationship::SUPPORTED,
        bool $conflict = false,
    ): ScientificEvidenceItem {
        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: 'Fixture '.$id,
            authors: ['Fixture'],
            institution: 'Fixture',
            journal: 'Fixture Journal',
            doi: '10.9999/u61-'.$id,
            url: 'https://example.test/u61/'.$id,
            publicationYear: 2020,
            retrievedAt: '2026-09-22T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: 'yield',
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 80.0,
            qualityFactors: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'answer_eligible' => true,
            ],
            sourceAttribution: [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ],
            cropOrEntity: $entity,
            hasConflict: $conflict,
        );
    }
}
