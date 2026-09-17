<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceModality;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Search\ScientificStatisticalClaimAligner;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;
use Mockery;
use Tests\TestCase;

class AnswerComposerEvidenceStateContractTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_01_direct_sufficient_composes_a_direct_answer(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([$this->directWheatEvidence()]),
        );

        $this->assertSame('sufficient_direct_evidence', $report->researchMetadata['sufficiency_mode']);
        $this->assertSame(1, (int) $report->researchMetadata['direct_evidence_count']);
        $this->assertTrue((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertSame('PASSED', $report->researchMetadata['direct_evidence_gate']);
        $this->assertNotSame('insufficient_evidence', $report->status);
        $this->assertNotEmpty($report->citations);
        $this->assertNotEmpty($report->claims);
        $this->assertStringNotContainsString(
            'Insufficient direct scientific evidence was found for a definitive answer.',
            $report->conciseSummary,
        );
    }

    public function test_02_direct_partial_is_not_supporting_only(): void
    {
        $item = $this->usableEvidence(
            'direct-partial',
            'Wheat classification is only partially documented for one cultivar group.',
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
        );
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([$item]),
        );

        $this->assertGreaterThanOrEqual(1, (int) $report->researchMetadata['direct_evidence_count']);
        $this->assertNotSame('supporting_only', $report->researchMetadata['sufficiency_mode']);
        $this->assertNotSame('supported_answer', $report->researchMetadata['sufficiency_mode']);
        $this->assertTrue((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertNotEmpty($report->limitations);
        $this->assertTrue(collect($report->limitations)->contains(
            fn (string $line): bool => str_contains($line, 'partial support'),
        ));
    }

    public function test_03_factual_supporting_only_is_not_a_sufficient_main_answer(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'quantity']),
            $this->validationReport([
                $this->supportingWheatEvidence('support-a', 'Wheat irrigation studies found improved water-use efficiency under drip systems.'),
                $this->supportingWheatEvidence('support-b', 'Wheat irrigation reviews reported related agronomic water savings in field trials.'),
            ]),
        );

        $this->assertSame(0, (int) $report->researchMetadata['direct_evidence_count']);
        $this->assertFalse((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertSame('supported_answer', $report->researchMetadata['sufficiency_mode']);
        $this->assertSame('INSUFFICIENT_DIRECT_EVIDENCE', $report->researchMetadata['direct_evidence_gate']);
        $this->assertSame('insufficient_evidence', $report->status);
        $this->assertSame([], $report->claims);
        $this->assertSame([], $report->citations);
        $this->assertStringContainsString(
            'Insufficient direct scientific evidence was found for a definitive answer.',
            $report->conciseSummary,
        );
        $this->assertStringContainsString('### Additional information', $report->answer);
        $this->assertStringContainsString(
            'The following is supporting/contextual only and is not a confident direct answer:',
            $report->answer,
        );
    }

    public function test_04_non_factual_supporting_only_is_not_sufficient(): void
    {
        $report = $this->composer()->compose(
            $this->plan([
                'question' => 'What are the benefits of hydroponics?',
                'topic' => 'hydroponics',
                'entity_dependent' => false,
                'subject_type' => 'topic',
                'crop' => null,
            ]),
            $this->validationReport([
                $this->usableEvidence(
                    'hydro-support',
                    'Hydroponics soilless culture can improve water-use efficiency in controlled production systems.',
                    ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    [
                        'publicationTitle' => 'Hydroponics water use efficiency review',
                        'claimTopic' => 'hydroponics',
                    ],
                ),
            ]),
        );

        $this->assertSame(0, (int) $report->researchMetadata['direct_evidence_count']);
        $this->assertFalse((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertSame('supporting_only', $report->researchMetadata['sufficiency_mode']);
        $this->assertSame('insufficient_evidence', $report->status);
        $this->assertSame([], $report->claims);
    }

    public function test_05_supported_answer_keeps_supporting_context_not_generic_no_evidence(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'quantity']),
            $this->validationReport([
                $this->supportingWheatEvidence('sa-a', 'Wheat irrigation studies found improved water-use efficiency under drip systems.'),
                $this->supportingWheatEvidence('sa-b', 'Wheat irrigation reviews reported related agronomic water savings in field trials.'),
            ]),
        );

        $this->assertNotSame('no_validated_evidence', $report->status);
        $this->assertSame('supported_answer', $report->researchMetadata['sufficiency_mode']);
        $this->assertSame('sufficient_supporting_evidence', $report->researchMetadata['failure_reason'] ?? null);
        $this->assertGreaterThanOrEqual(1, (int) $report->researchMetadata['supporting_evidence_count']);
        $this->assertStringContainsString('### Additional information', $report->answer);
        $this->assertStringNotContainsString('no_relevant_validated_evidence', (string) ($report->researchMetadata['failure_reason'] ?? ''));
    }

    public function test_06_compatible_supporting_evidence_remains_additional_context(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([
                $this->directWheatEvidence(),
                $this->supportingWheatEvidence(
                    'compat-support',
                    'Related wheat classification studies described additional cultivar grouping methods.',
                    'Wheat classification supporting review',
                ),
            ]),
        );

        $this->assertSame('sufficient_direct_evidence', $report->researchMetadata['sufficiency_mode']);
        $this->assertStringContainsString('### Additional information', $report->answer);
        $this->assertStringContainsString('Wheat classification supporting review', $report->answer);
        $this->assertStringContainsString('Supporting/contextual information', $report->answer);
    }

    public function test_07_wrong_entity_supporting_evidence_is_not_useful_additional_information(): void
    {
        $report = $this->composer()->compose(
            $this->plan([
                'question_type' => 'classification',
                'entity_dependent' => true,
            ]),
            $this->validationReport([
                $this->directWheatEvidence(),
                $this->usableEvidence(
                    'maize-support',
                    'Maize hybrid classification described dent and flint groups under temperate breeding programs.',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    [
                        'publicationTitle' => 'Maize hybrid classification review',
                        'claimTopic' => 'classification',
                        'species_relation' => 'different_species',
                    ],
                ),
            ]),
        );

        $this->assertStringNotContainsString('Maize hybrid classification review', $report->answer);
        $this->assertStringNotContainsString('dent and flint groups', $report->answer);
    }

    public function test_08_geographic_mismatch_is_not_useful_additional_information(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([
                $this->directWheatEvidence(),
                $this->usableEvidence(
                    'geo-mismatch',
                    'Wheat classification in Brazil described regional cultivar groups unrelated to the requested location.',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                    ['publicationTitle' => 'Brazilian wheat classification atlas'],
                ),
            ]),
        );

        $this->assertStringNotContainsString('Brazilian wheat classification atlas', $report->answer);
        $this->assertStringNotContainsString('Wheat classification in Brazil', $report->answer);
    }

    public function test_09_conflicting_publication_year_is_not_useful_additional_information(): void
    {
        $report = $this->composer()->compose(
            $this->plan([
                'question_type' => 'classification',
                'year' => '2020',
            ]),
            $this->validationReport([
                $this->directWheatEvidence(2020),
                $this->usableEvidence(
                    'year-mismatch',
                    'Related wheat classification notes described older cultivar groups from an unrelated season.',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    [
                        'publicationTitle' => 'Wheat classification notes 1998',
                        'publicationYear' => 1998,
                    ],
                ),
            ]),
        );

        $this->assertStringNotContainsString('Wheat classification notes 1998', $report->answer);
        $this->assertStringNotContainsString('unrelated season', $report->answer);
    }

    public function test_10_incompatible_requested_property_is_not_useful_additional_information(): void
    {
        $report = $this->composer()->compose(
            $this->plan([
                'question_type' => 'classification',
                'requested_property' => 'yield',
                'requested_property_query_terms' => ['yield'],
            ]),
            $this->validationReport([
                $this->usableEvidence(
                    'direct-yield',
                    'Wheat yield classification groups include bread wheat and durum wheat under common agronomic systems.',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::DIRECT,
                    ['publicationTitle' => 'Wheat yield classification primary study'],
                ),
                $this->usableEvidence(
                    'property-mismatch',
                    'Soil salinity physiology changed ion transport in wheat leaves without reporting yield.',
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    ['publicationTitle' => 'Wheat salinity ion transport study'],
                ),
            ]),
        );

        $this->assertStringNotContainsString('Wheat salinity ion transport study', $report->answer);
        $this->assertStringNotContainsString('ion transport', $report->answer);
    }

    public function test_11_relevant_supporting_evidence_is_not_globally_suppressed(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'quantity']),
            $this->validationReport([
                $this->supportingWheatEvidence('keep-a', 'Wheat irrigation studies found improved water-use efficiency under drip systems.'),
                $this->supportingWheatEvidence('keep-b', 'Wheat irrigation reviews reported related agronomic water savings in field trials.'),
            ]),
        );

        $this->assertSame('supported_answer', $report->researchMetadata['sufficiency_mode']);
        $this->assertStringContainsString('### Additional information', $report->answer);
        $this->assertStringContainsString('Wheat irrigation supporting review keep-a', $report->answer);
        $this->assertStringContainsString('Wheat irrigation supporting review keep-b', $report->answer);
    }

    public function test_12_duplicate_supporting_evidence_is_still_suppressed(): void
    {
        $shared = 'Wheat classification groups include bread wheat and durum wheat under common agronomic systems.';
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([
                $this->usableEvidence(
                    'direct-dup',
                    $shared,
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::DIRECT,
                    ['publicationTitle' => 'Wheat classification primary study'],
                ),
                $this->usableEvidence(
                    'support-dup',
                    $shared,
                    ClaimEvidenceRelationship::SUPPORTED,
                    ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    ['publicationTitle' => 'Wheat classification duplicate review'],
                ),
            ]),
        );

        $this->assertSame('sufficient_direct_evidence', $report->researchMetadata['sufficiency_mode']);
        $this->assertStringNotContainsString('Wheat classification duplicate review', $report->answer);
    }

    public function test_13_direct_statistical_evidence_still_composes(): void
    {
        $value = '9101785';
        $item = $this->usableEvidence(
            'faostat-wheat',
            'Wheat Production Egypt 2020 Value: '.$value.' t from official statistics.',
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
            [
                'publicationTitle' => 'Wheat — Production — Egypt — 2020',
                'sourceType' => 'official_statistics',
                'institution' => 'FAO',
                'claimTopic' => 'production',
                'qualityFactors' => [
                    'not_literature' => true,
                    'evidence_type' => ScientificEvidenceModality::DIRECT_STATISTICAL,
                    'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'observation' => [
                        'item' => 'Wheat',
                        'area' => 'Egypt',
                        'year' => '2020',
                        'element' => 'Production',
                        'unit' => 't',
                        'value' => $value,
                    ],
                ],
            ],
        );

        $report = $this->composer()->compose(
            $this->plan([
                'question' => 'wheat production quantity Egypt 2020',
                'question_type' => 'quantity',
                'requested_property' => 'quantity',
                'requested_property_surface' => 'production quantity',
                'year' => '2020',
                'location' => 'Egypt',
            ]),
            $this->validationReport([$item]),
        );

        $this->assertSame(1, (int) $report->researchMetadata['direct_evidence_count']);
        $this->assertTrue((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertSame('sufficient_direct_evidence', $report->researchMetadata['sufficiency_mode']);
        $this->assertNotEmpty($report->claims);
        $this->assertTrue(collect($report->claims)->contains(
            fn ($claim): bool => str_contains($claim->claimText, $value)
                || collect($claim->numericalValues)->contains(fn (string $entry): bool => str_contains($entry, $value)),
        ));
    }

    public function test_14_no_valid_evidence_does_not_fabricate_claims(): void
    {
        $report = $this->composer()->compose(
            $this->plan(['question_type' => 'classification']),
            $this->validationReport([]),
        );

        $this->assertSame('no_validated_evidence', $report->status);
        $this->assertFalse((bool) $report->researchMetadata['evidence_sufficient']);
        $this->assertSame([], $report->claims);
        $this->assertSame([], $report->citations);
        $this->assertSame([], $report->keyFindings);
        $this->assertStringNotContainsString('### Additional information', $report->answer);
    }

    private function composer(): AnswerComposer
    {
        $gate = Mockery::mock(ScientificEvidenceRelevanceGate::class);
        $gate->shouldReceive('isRelevant')->andReturn(true);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function plan(array $overrides = []): KnowledgeQueryPlan
    {
        $crop = array_key_exists('crop', $overrides) ? $overrides['crop'] : 'wheat';
        $subjectType = (string) ($overrides['subject_type'] ?? ($crop !== null ? 'crop' : 'topic'));
        $entityDependent = array_key_exists('entity_dependent', $overrides)
            ? (bool) $overrides['entity_dependent']
            : $crop !== null;
        $question = (string) ($overrides['question'] ?? 'What are wheat classification groups?');
        $topic = (string) ($overrides['topic'] ?? 'classification');

        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $crop !== null
                ? ['type' => $subjectType, 'value' => (string) $crop, 'label' => (string) $crop]
                : ['type' => $subjectType, 'value' => $topic],
            crop: $crop,
            cropId: $crop,
            scientificName: null,
            topic: $topic,
            subtopic: null,
            requestedInformation: [(string) ($overrides['question_type'] ?? 'classification')],
            constraints: array_filter([
                'answer_language' => 'en',
                'question_type' => $overrides['question_type'] ?? null,
                'requested_property' => $overrides['requested_property'] ?? null,
                'requested_property_surface' => $overrides['requested_property_surface'] ?? null,
                'requested_property_query_terms' => $overrides['requested_property_query_terms'] ?? null,
                'year' => $overrides['year'] ?? null,
                'entity_dependent' => $entityDependent,
            ], static fn (mixed $value): bool => $value !== null),
            location: $overrides['location'] ?? null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: (string) ($overrides['research_intent'] ?? 'scientific_explanation'),
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $query->subject,
            topics: [$topic],
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
     */
    private function validationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: $items === [] ? 'no_relevant_validated_evidence' : 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $items !== [],
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    private function directWheatEvidence(?int $year = 2023): ScientificEvidenceItem
    {
        return $this->usableEvidence(
            'direct-wheat',
            'Wheat classification groups include bread wheat and durum wheat under common agronomic systems.',
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::DIRECT,
            [
                'publicationTitle' => 'Wheat classification primary study',
                'publicationYear' => $year,
            ],
        );
    }

    private function supportingWheatEvidence(string $id, string $text, ?string $title = null): ScientificEvidenceItem
    {
        return $this->usableEvidence(
            $id,
            $text,
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            [
                'publicationTitle' => $title ?? 'Wheat irrigation supporting review '.$id,
                'answer_eligible' => true,
                'species_relation' => 'same_species',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function usableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        string $directness,
        array $overrides = [],
    ): ScientificEvidenceItem {
        $qualityFactors = array_merge([
            'not_scientific_certainty' => true,
            'evidence_directness' => $directness,
            'answer_eligible' => (bool) ($overrides['answer_eligible'] ?? ($directness !== ScientificEvidenceDirectnessAssessor::DIRECT)),
            'species_relation' => $overrides['species_relation'] ?? 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
        ], is_array($overrides['qualityFactors'] ?? null) ? $overrides['qualityFactors'] : []);

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: 'source-'.$evidenceId,
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: '10.1000/'.$evidenceId,
            url: 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: isset($overrides['publicationYear']) ? (int) $overrides['publicationYear'] : 2023,
            retrievedAt: '2026-09-17T00:00:00+00:00',
            agriculturalDomain: 'field_crops',
            claimTopic: (string) ($overrides['claimTopic'] ?? 'wheat'),
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: $qualityFactors,
            sourceAttribution: [
                'organization' => (string) ($overrides['institution'] ?? 'University of Agriculture'),
                'source_type' => (string) ($overrides['sourceType'] ?? 'university_research'),
                'evidence_directness' => $directness,
            ],
        );
    }
}
