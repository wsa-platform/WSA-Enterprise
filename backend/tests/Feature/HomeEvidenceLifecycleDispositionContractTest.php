<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Home\HomeEvidenceLifecycleDisposition;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerSynthesisExecutionReport;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use Tests\TestCase;

/**
 * Phase 10C — Home evidence lifecycle disposition (Home-only layer).
 *
 * Does not modify AnswerComposer. Composer is exercised only as a black-box
 * eligibility/preparation boundary for multi-entity and supporting cases.
 */
class HomeEvidenceLifecycleDispositionContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_no_search_results_disposition_is_no_results_retrieved(): void
    {
        $plan = $this->homePlan();
        $validation = $this->validationReport(
            items: [],
            status: 'no_search_results',
            sourcesReceived: 0,
            rejected: 0,
            searchStatus: 'no_results',
            evidenceSufficient: false,
        );

        $meta = $this->disposition()->classify($plan, $validation, composerEligibleCount: 0);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            $meta['lifecycle_status'] ?? null,
        );
        $this->assertSame(0, (int) ($meta['sources_received'] ?? -1));
        $this->assertSame(0, (int) ($meta['composer_eligible_count'] ?? -1));
        $this->assertSame(0, (int) ($meta['validated_evidence_count'] ?? -1));
        $this->assertSame(0, (int) ($meta['rejected_evidence_count'] ?? -1));
    }

    public function test_rejected_only_disposition_is_retrieved_but_rejected(): void
    {
        $rejected = $this->item(
            id: 'test-fixture-rej-1',
            text: 'TEST FIXTURE ONLY — unrelated psychology paper about anxiety therapy.',
            relationship: ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
            directness: ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            usable: false,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — anxiety therapy (not agricultural evidence)',
                'doi' => '10.9999/test-fixture-rej-1',
                'url' => 'https://example.test/fixtures/rej-1',
                'species_relation' => 'unrelated',
                'entity_matched' => false,
                'topic_matched' => false,
            ],
        );

        $plan = $this->homePlan();
        $validation = $this->validationReport(
            items: [],
            status: 'validation_completed_with_rejections',
            sourcesReceived: 3,
            rejected: 3,
            rejectedEvidence: [$rejected],
            searchStatus: 'search_completed',
            evidenceSufficient: false,
        );

        $meta = $this->disposition()->classify($plan, $validation, composerEligibleCount: 0);
        $refs = $this->disposition()->unusedEvidenceReferences($plan, $validation);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::RETRIEVED_BUT_REJECTED,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertNotSame(
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(3, (int) ($meta['sources_received'] ?? -1));
        $this->assertSame(3, (int) ($meta['rejected_evidence_count'] ?? -1));
        $this->assertNotEmpty($refs);
        $this->assertSame('test-fixture-rej-1', $refs[0]['evidence_id'] ?? null);
        $this->assertSame('https://example.test/fixtures/rej-1', $refs[0]['url'] ?? null);
        $this->assertSame('10.9999/test-fixture-rej-1', $refs[0]['doi'] ?? null);
    }

    public function test_validated_but_not_composer_eligible_preserves_identity_and_disposition(): void
    {
        $validatedButWeak = $this->item(
            id: 'test-fixture-weak-1',
            text: 'TEST FIXTURE ONLY — Psychological effects of ginger aroma on anxiety disorder patients.',
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::RELATED,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Ginger psychology and consumer anxiety',
                'doi' => '10.9999/test-fixture-ginger-psych',
                'url' => 'https://example.test/fixtures/ginger-psych',
                'species_relation' => 'related_species',
                'entity_matched' => true,
                'topic_matched' => false,
            ],
        );

        $plan = $this->homePlan([
            'question' => 'What is the germination temperature of ginger?',
            'crop' => 'ginger',
            'question_type' => 'range',
        ]);
        $validation = $this->validationReport(
            items: [$validatedButWeak],
            sourcesReceived: 5,
            searchStatus: 'search_completed',
            evidenceSufficient: false,
        );

        $meta = $this->disposition()->classify($plan, $validation, composerEligibleCount: 0);
        $refs = $this->disposition()->unusedEvidenceReferences($plan, $validation);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::VALIDATED_NOT_COMPOSER_ELIGIBLE,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::VALIDATED_NOT_COMPOSER_ELIGIBLE,
            $meta['lifecycle_status'] ?? null,
        );
        $this->assertNotSame(
            HomeEvidenceLifecycleDisposition::NO_RESULTS_RETRIEVED,
            $meta['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(1, (int) ($meta['validated_evidence_count'] ?? -1));
        $this->assertSame(0, (int) ($meta['composer_eligible_count'] ?? -1));
        $this->assertNotEmpty($refs);
        $this->assertSame('test-fixture-weak-1', $refs[0]['evidence_id'] ?? null);
        $this->assertSame('10.9999/test-fixture-ginger-psych', $refs[0]['doi'] ?? null);
        $this->assertSame('https://example.test/fixtures/ginger-psych', $refs[0]['url'] ?? null);
    }

    public function test_usable_evidence_disposition_is_composer_used(): void
    {
        $usable = $this->item(
            id: 'test-fixture-wheat-direct',
            text: 'TEST FIXTURE ONLY — Triticum aestivum irrigation water requirement averaged 450 mm per season.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::DIRECT,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Wheat irrigation water requirement',
                'doi' => '10.9999/test-fixture-wheat-irr',
                'url' => 'https://example.test/fixtures/wheat-irr',
                'claimTopic' => 'irrigation',
                'cropOrEntity' => 'wheat',
                'species_relation' => 'same_species',
                'entity_matched' => true,
                'topic_matched' => true,
                'answer_eligible' => true,
            ],
        );

        $plan = $this->homePlan(['question_type' => 'quantity', 'requested_property' => 'irrigation']);
        $validation = $this->validationReport(
            items: [$usable],
            sourcesReceived: 1,
            searchStatus: 'search_completed',
            evidenceSufficient: true,
        );

        $synthesis = $this->composer()->compose($plan, $validation);
        $decorated = $this->disposition()->applyToSynthesis($plan, $validation, $synthesis);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::COMPOSER_USED,
            $decorated->researchMetadata['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertGreaterThanOrEqual(1, (int) ($decorated->researchMetadata['composer_eligible_count'] ?? 0));
        $this->assertNotEmpty($decorated->citations);
    }

    public function test_multi_entity_direct_evidence_both_survive_composer(): void
    {
        $wheat = $this->item(
            id: 'test-fixture-wheat-direct',
            text: 'TEST FIXTURE ONLY — Triticum aestivum irrigation water requirement under field conditions averaged 450 mm per season.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::DIRECT,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Wheat irrigation water requirement field study',
                'doi' => '10.9999/test-fixture-wheat-irr',
                'url' => 'https://example.test/fixtures/wheat-irr',
                'claimTopic' => 'irrigation',
                'cropOrEntity' => 'wheat',
                'species_relation' => 'same_species',
                'entity_matched' => true,
                'topic_matched' => true,
                'answer_eligible' => true,
            ],
        );
        $maize = $this->item(
            id: 'test-fixture-maize-direct',
            text: 'TEST FIXTURE ONLY — Zea mays irrigation water requirement under field conditions averaged 550 mm per season.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::DIRECT,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Maize irrigation water requirement field study',
                'doi' => '10.9999/test-fixture-maize-irr',
                'url' => 'https://example.test/fixtures/maize-irr',
                'claimTopic' => 'irrigation',
                'cropOrEntity' => 'maize',
                'species_relation' => 'same_species',
                'entity_matched' => true,
                'topic_matched' => true,
                'answer_eligible' => true,
            ],
        );

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'Which needs more irrigation water, wheat or maize?',
        ]);
        $this->assertFalse($plan->toAgriculturalResearchPlan()->isCropProfileIntent());
        $this->assertTrue((bool) ($plan->normalizedQuery->constraints['is_comparison'] ?? false));

        $validation = $this->validationReport(
            items: [$wheat, $maize],
            sourcesReceived: 2,
            searchStatus: 'search_completed',
            evidenceSufficient: true,
        );

        $synthesis = $this->composer()->compose($plan, $validation);
        $decorated = $this->disposition()->applyToSynthesis($plan, $validation, $synthesis);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::COMPOSER_USED,
            $decorated->researchMetadata['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(2, (int) ($decorated->researchMetadata['validated_evidence_count'] ?? -1));
        $this->assertGreaterThanOrEqual(2, (int) ($decorated->researchMetadata['composer_eligible_count'] ?? 0));
        $this->assertNotEmpty($decorated->claims);
        $this->assertNotEmpty($decorated->citations);

        $blob = mb_strtolower(implode(' ', array_merge(
            array_map(static fn ($c) => $c->claimText, $decorated->claims),
            array_map(static fn ($c) => (string) ($c->title ?? ''), $decorated->citations),
            [$decorated->answer ?? ''],
        )));
        $this->assertTrue(
            str_contains($blob, 'wheat') || str_contains($blob, 'triticum'),
            'wheat evidence missing from composer output',
        );
        $this->assertTrue(
            str_contains($blob, 'maize') || str_contains($blob, 'zea') || str_contains($blob, 'corn'),
            'maize evidence missing from composer output',
        );

        $entityIds = array_values(array_filter(array_map(
            static fn (ScientificEvidenceItem $item): ?string => $item->cropOrEntity,
            [$wheat, $maize],
        )));
        $this->assertSame(['wheat', 'maize'], $entityIds);
    }

    public function test_supporting_only_keeps_insufficient_direct_disposition(): void
    {
        $a = $this->item(
            id: 'test-fixture-sup-a',
            text: 'TEST FIXTURE ONLY — Wheat irrigation studies found improved water-use efficiency under drip systems.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::SUPPORTING,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Wheat irrigation drip review A',
                'doi' => '10.9999/test-fixture-sup-a',
                'url' => 'https://example.test/fixtures/sup-a',
                'answer_eligible' => true,
                'cropOrEntity' => 'wheat',
            ],
        );
        $b = $this->item(
            id: 'test-fixture-sup-b',
            text: 'TEST FIXTURE ONLY — Wheat irrigation reviews reported related agronomic water savings in field trials.',
            relationship: ClaimEvidenceRelationship::SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::SUPPORTING,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Wheat irrigation drip review B',
                'doi' => '10.9999/test-fixture-sup-b',
                'url' => 'https://example.test/fixtures/sup-b',
                'answer_eligible' => true,
                'cropOrEntity' => 'wheat',
            ],
        );

        $plan = $this->homePlan(['question_type' => 'quantity', 'requested_property' => 'irrigation']);
        $validation = $this->validationReport(
            items: [$a, $b],
            sourcesReceived: 2,
            searchStatus: 'search_completed',
            evidenceSufficient: false,
        );

        $synthesis = $this->composer()->compose($plan, $validation);
        $decorated = $this->disposition()->applyToSynthesis($plan, $validation, $synthesis);

        $this->assertSame(
            HomeEvidenceLifecycleDisposition::INSUFFICIENT_DIRECT_SUPPORTING_RETAINED,
            $decorated->researchMetadata['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame('INSUFFICIENT_DIRECT_EVIDENCE', $decorated->researchMetadata['direct_evidence_gate'] ?? null);
        $this->assertGreaterThanOrEqual(1, count($decorated->evidenceReferences));
    }

    public function test_crop_profile_plan_does_not_receive_home_disposition_fields(): void
    {
        $base = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'farming needs',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertTrue($base->toAgriculturalResearchPlan()->isCropProfileIntent());

        $validation = $this->validationReport(
            items: [],
            status: 'no_search_results',
            sourcesReceived: 0,
            searchStatus: 'no_results',
            evidenceSufficient: false,
        );

        $meta = $this->disposition()->classify($base, $validation, composerEligibleCount: 0);
        $this->assertSame([], $meta);

        $bare = new AnswerSynthesisExecutionReport(
            status: 'no_search_results',
            performed: true,
            answer: 'insufficient',
            conciseSummary: 'insufficient',
            detailedExplanation: 'insufficient',
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: 'insufficient',
            conflicts: [],
            language: 'en',
            researchMetadata: ['query' => 'farming needs'],
            observability: [],
        );
        $decorated = $this->disposition()->applyToSynthesis($base, $validation, $bare);
        $this->assertSame($bare, $decorated);
        $this->assertArrayNotHasKey('evidence_lifecycle_disposition', $decorated->researchMetadata);
        $this->assertArrayNotHasKey('lifecycle_status', $decorated->researchMetadata);
    }

    /**
     * Universal U1 parity: same post-compose apply used by UniversalAnswerOrchestrator.
     * S1: Composer status may remain no_validated_evidence; disposition/lifecycle_status carry truth.
     */
    public function test_universal_u1_post_compose_apply_preserves_composer_status_and_sets_lifecycle_alias(): void
    {
        $validatedButWeak = $this->item(
            id: 'test-fixture-weak-u1',
            text: 'TEST FIXTURE ONLY — Psychological effects of ginger aroma on anxiety disorder patients.',
            relationship: ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            directness: ScientificEvidenceDirectnessAssessor::RELATED,
            extra: [
                'publicationTitle' => 'TEST FIXTURE — Ginger psychology',
                'doi' => '10.9999/test-fixture-ginger-u1',
                'url' => 'https://example.test/fixtures/ginger-u1',
                'species_relation' => 'related_species',
                'entity_matched' => true,
                'topic_matched' => false,
            ],
        );

        $plan = $this->homePlan([
            'question' => 'What is the germination temperature of ginger?',
            'crop' => 'ginger',
            'question_type' => 'range',
        ]);
        $validation = $this->validationReport(
            items: [$validatedButWeak],
            sourcesReceived: 5,
            searchStatus: 'search_completed',
            evidenceSufficient: false,
        );

        // Simulate Composer CASE C output (status frozen under S1).
        $composerLike = new AnswerSynthesisExecutionReport(
            status: 'no_validated_evidence',
            performed: true,
            answer: 'insufficient',
            conciseSummary: 'insufficient',
            detailedExplanation: 'insufficient',
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: [],
            uncertainty: 'insufficient',
            conflicts: [],
            language: 'en',
            researchMetadata: ['query' => 'ginger', 'failure_reason' => 'no_relevant_validated_evidence'],
            observability: ['usable_evidence_count' => 0],
        );

        $decorated = $this->disposition()->applyToSynthesis($plan, $validation, $composerLike);

        $this->assertSame('no_validated_evidence', $decorated->status);
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::VALIDATED_NOT_COMPOSER_ELIGIBLE,
            $decorated->researchMetadata['evidence_lifecycle_disposition'] ?? null,
        );
        $this->assertSame(
            HomeEvidenceLifecycleDisposition::VALIDATED_NOT_COMPOSER_ELIGIBLE,
            $decorated->researchMetadata['lifecycle_status'] ?? null,
        );
        $this->assertNotEmpty($decorated->evidenceReferences);
        $this->assertSame('test-fixture-weak-u1', $decorated->evidenceReferences[0]['evidence_id'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function homePlan(array $overrides = []): KnowledgeQueryPlan
    {
        $crop = array_key_exists('crop', $overrides) ? $overrides['crop'] : 'wheat';
        $question = (string) ($overrides['question'] ?? 'What is the irrigation requirement of wheat?');
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $crop !== null
                ? ['type' => 'crop', 'value' => (string) $crop, 'label' => (string) $crop]
                : ['type' => 'topic', 'value' => 'irrigation'],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: [(string) ($overrides['question_type'] ?? 'quantity')],
            constraints: array_filter([
                'answer_language' => 'en',
                'question_type' => $overrides['question_type'] ?? 'quantity',
                'requested_property' => $overrides['requested_property'] ?? 'irrigation',
                'entity_dependent' => true,
            ], static fn (mixed $v): bool => $v !== null),
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_explanation',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @param  list<ScientificEvidenceItem>  $rejectedEvidence
     */
    private function validationReport(
        array $items,
        string $status = 'validation_completed',
        int $sourcesReceived = 0,
        int $rejected = 0,
        array $rejectedEvidence = [],
        string $searchStatus = '',
        bool $evidenceSufficient = false,
    ): EvidenceValidationExecutionReport {
        return new EvidenceValidationExecutionReport(
            status: $status,
            validatedEvidence: $items,
            rejectedEvidence: $rejectedEvidence,
            sourcesReceived: $sourcesReceived,
            validatedCount: count($items),
            rejectedCount: $rejected,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $evidenceSufficient,
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: array_filter([
                'search_status' => $searchStatus !== '' ? $searchStatus : null,
                'successful_sources' => $searchStatus === 'search_completed' ? ['openalex'] : [],
                'failed_sources' => [],
            ], static fn (mixed $v): bool => $v !== null),
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function item(
        string $id,
        string $text,
        string $relationship,
        string $directness,
        array $extra = [],
        bool $usable = true,
    ): ScientificEvidenceItem {
        $quality = array_merge([
            'evidence_directness' => $directness,
            'answer_eligible' => (bool) ($extra['answer_eligible'] ?? ($directness === ScientificEvidenceDirectnessAssessor::DIRECT)),
            'species_relation' => (string) ($extra['species_relation'] ?? 'same_species'),
            'entity_matched' => (bool) ($extra['entity_matched'] ?? true),
            'topic_matched' => (bool) ($extra['topic_matched'] ?? true),
            'sense_coverage' => (bool) ($extra['sense_coverage'] ?? true),
            'factor_coverage' => (float) ($extra['factor_coverage'] ?? 1.0),
        ], is_array($extra['qualityFactors'] ?? null) ? $extra['qualityFactors'] : []);

        return new ScientificEvidenceItem(
            evidenceId: $id,
            sourceId: 'src-'.$id,
            sourceKey: 'openalex',
            sourceType: 'peer_reviewed_journal',
            publicationTitle: (string) ($extra['publicationTitle'] ?? 'TEST FIXTURE — Evidence '.$id),
            authors: ['Test Fixture Author'],
            institution: 'TEST FIXTURE Institution',
            journal: 'TEST FIXTURE Journal',
            doi: $extra['doi'] ?? ('10.9999/test-fixture-'.$id),
            url: $extra['url'] ?? ('https://example.test/fixtures/'.$id),
            publicationYear: 2022,
            retrievedAt: '2026-09-19T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: (string) ($extra['claimTopic'] ?? 'irrigation'),
            evidenceText: $text,
            validationStatus: $usable
                ? EvidenceValidationStatus::EVIDENCE_USABLE
                : EvidenceValidationStatus::REJECTED,
            validationFailures: $usable ? [] : ['rejected'],
            claimRelationship: $relationship,
            confidence: 0.7,
            qualityScore: 70.0,
            qualityFactors: $quality,
            sourceAttribution: ['evidence_directness' => $directness],
            cropOrEntity: isset($extra['cropOrEntity']) ? (string) $extra['cropOrEntity'] : null,
        );
    }

    private function disposition(): HomeEvidenceLifecycleDisposition
    {
        return new HomeEvidenceLifecycleDisposition;
    }

    private function composer(): AnswerComposer
    {
        $gate = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $gate->shouldReceive('isRelevant')->andReturnUsing(function ($plan, $title, $text) {
            $hay = mb_strtolower(trim(($title ?? '').' '.($text ?? '')));

            return ! str_contains($hay, 'anxiety') && ! str_contains($hay, 'psychology');
        });
        $gate->shouldReceive('assess')->andReturn([
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
            'sense_coverage' => true,
            'factor_coverage' => 1.0,
        ]);

        return new AnswerComposer(
            app(ScientificSourceValidator::class),
            $gate,
            app(ScientificEvidenceDirectnessAssessor::class),
            app(EvidenceVerificationLayer::class),
            new ScientificStatisticalClaimAligner(),
        );
    }
}
