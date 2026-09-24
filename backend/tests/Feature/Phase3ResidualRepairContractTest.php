<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Synthesis\AnswerComposerPhrases;
use App\Services\Agriculture\Research\Synthesis\QuestionClaimSynthesisContract;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase 3 residual repair: additional-info time semantics, B3 accuracy_outcomes trace,
 * and localized user-facing limitation messages.
 */
class Phase3ResidualRepairContractTest extends TestCase
{
    use Phase5UnitATestFixtures;

    /** @var list<string> */
    private const LEAKED_MACHINE_KEYS = [
        'insufficient_validated_evidence_for_question_claim',
        'partial_evidence_support',
        'validation_evidence_insufficient',
        'conflicting_evidence_for_question_claim',
        'supporting_not_direct_evidence',
        'comparison_decomposition_unsupported',
        'accuracy_entity_incompatible',
        'accuracy_property_unsupported',
        'accuracy_geography_unsupported',
        'accuracy_period_unsupported',
        'accuracy_numeric_unsupported',
        'accuracy_numeric_ambiguous',
        'accuracy_geography_mismatch',
        'accuracy_period_mismatch',
    ];

    public function test_r1_a_matching_observation_year_keeps_additional_evidence_eligible(): void
    {
        $report = $this->composeSupportingTimeCase(
            requestedYear: '2020',
            observationYear: '2020',
            publicationYear: 2018,
            title: 'Wheat irrigation supporting review obs-match-2020',
        );

        $this->assertStringContainsString(
            'Wheat irrigation supporting review obs-match-2020',
            (string) $report->additionalInformation,
        );
    }

    public function test_r1_b_non_matching_observation_year_is_not_additional_eligible(): void
    {
        $report = $this->composeSupportingTimeCase(
            requestedYear: '2020',
            observationYear: '2018',
            publicationYear: 2018,
            title: 'Wheat irrigation supporting review obs-mismatch-2018',
        );

        $this->assertStringNotContainsString(
            'Wheat irrigation supporting review obs-mismatch-2018',
            (string) $report->additionalInformation,
        );
        $this->assertStringNotContainsString(
            'Wheat irrigation supporting review obs-mismatch-2018',
            (string) $report->answer,
        );
    }

    public function test_r1_c_publication_year_must_not_satisfy_observation_year_requirement(): void
    {
        $report = $this->composeSupportingTimeCase(
            requestedYear: '2020',
            observationYear: '2018',
            publicationYear: 2020,
            title: 'Wheat irrigation supporting review pub-2020-obs-2018',
        );

        $this->assertStringNotContainsString(
            'Wheat irrigation supporting review pub-2020-obs-2018',
            (string) $report->additionalInformation,
        );
        $this->assertStringNotContainsString(
            'Wheat irrigation supporting review pub-2020-obs-2018',
            (string) $report->answer,
        );
    }

    public function test_r1_c2_publication_year_without_observation_year_does_not_satisfy(): void
    {
        $report = $this->composeSupportingTimeCase(
            requestedYear: '2020',
            observationYear: null,
            publicationYear: 2020,
            title: 'Wheat irrigation supporting review pub-only-2020',
        );

        $this->assertStringNotContainsString(
            'Wheat irrigation supporting review pub-only-2020',
            (string) $report->additionalInformation,
        );
    }

    public function test_r1_d_no_temporal_requirement_leaves_additional_information_unchanged(): void
    {
        $item = $this->supportingTimeEvidence(
            'keep-no-year',
            'Wheat irrigation supporting review no-year',
            null,
            2015,
        );
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: ['answer_language' => 'en'],
        );
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation([$item], true));

        $this->assertStringContainsString(
            'Wheat irrigation supporting review no-year',
            (string) $report->additionalInformation,
        );
    }

    public function test_r2_a_b3_approved_claim_preserves_supported_trace(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: ['answer_language' => 'en'],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-ok', ClaimEvidenceRelationship::SUPPORTED),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        )];
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $trace = $this->firstTrace($report);

        $this->assertSame('supported', $trace['status']);
        $this->assertSame([], $trace['accuracy_outcomes'] ?? []);
        $this->assertArrayHasKey('accuracy_outcomes', $trace);
    }

    public function test_r2_b_entity_accuracy_rejection_is_reported_on_trace(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: ['answer_language' => 'en'],
        );
        $base = $this->phase5Evidence('e-ent', ClaimEvidenceRelationship::SUPPORTED);
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'species_relation' => 'same_species',
                'entity_matched' => false,
            ]),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        )];
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $trace = $this->firstTrace($report);

        $this->assertContains('accuracy_entity_incompatible', $trace['accuracy_outcomes'] ?? []);
        $this->assertSame('supported', $trace['status']);
    }

    public function test_r2_c_property_accuracy_rejection_is_reported_on_trace(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: ['answer_language' => 'en'],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-area', ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat in Egypt covered 1.1 million hectares.',
        )];
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $trace = $this->firstTrace($report);

        $this->assertContains('accuracy_property_unsupported', $trace['accuracy_outcomes'] ?? []);
    }

    public function test_r2_d_numeric_accuracy_rejection_is_reported_on_trace(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: ['answer_language' => 'en'],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-bad', ClaimEvidenceRelationship::SUPPORTED),
            'Wheat production quantity in Egypt was discussed without a reported measurement, and the paper was cited 154 times.',
        )];
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $trace = $this->firstTrace($report);

        $this->assertTrue(
            in_array('accuracy_numeric_unsupported', $trace['accuracy_outcomes'] ?? [], true)
            || in_array('accuracy_numeric_ambiguous', $trace['accuracy_outcomes'] ?? [], true),
            'expected a numeric accuracy outcome, got: '.json_encode($trace['accuracy_outcomes'] ?? []),
        );
    }

    public function test_r2_e_geography_and_period_accuracy_rejections_are_reported(): void
    {
        $geoPlan = $this->phase5Plan(
            question: 'What is wheat production quantity in Italy?',
            requested: ['quantity'],
            location: 'Italy',
            constraints: ['answer_language' => 'en'],
        );
        $geoBase = $this->phase5Evidence('e-geo', ClaimEvidenceRelationship::SUPPORTED);
        $geoEvidence = [$this->withEvidenceText(
            $this->withQuality($geoBase, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'observation' => [
                    'item' => 'wheat',
                    'area' => 'France',
                    'year' => '2020',
                    'element' => 'production',
                    'value' => '7.2',
                    'unit' => 'tonnes',
                ],
            ]),
            'Wheat production quantity in France was 7.2 million tonnes.',
        )];
        $geoReport = app(AnswerComposer::class)->compose($geoPlan, $this->phase5Validation($geoEvidence, true));
        $this->assertContains(
            'accuracy_geography_unsupported',
            $this->firstTrace($geoReport)['accuracy_outcomes'] ?? [],
        );

        $timePlan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
            ],
        );
        $timeBase = $this->phase5Evidence('e-time', ClaimEvidenceRelationship::SUPPORTED);
        $timeEvidence = [$this->withEvidenceText(
            $this->withQuality($timeBase, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'observation' => [
                    'item' => 'wheat',
                    'area' => 'Egypt',
                    'year' => '2021',
                    'element' => 'production',
                    'value' => '9',
                    'unit' => 'tonnes',
                ],
            ]),
            'Wheat production quantity in Egypt for 2021 reached 9 million tonnes.',
        )];
        $timeReport = app(AnswerComposer::class)->compose($timePlan, $this->phase5Validation($timeEvidence, true));
        $this->assertContains(
            'accuracy_period_unsupported',
            $this->firstTrace($timeReport)['accuracy_outcomes'] ?? [],
        );
    }

    public function test_r2_f_conflict_remains_conflict_and_is_not_converted(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-c1', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
                'Wheat production quantity in Egypt reached 9 million tonnes.',
            ),
            $this->withEvidenceText(
                $this->phase5Evidence('e-c2', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
                'Wheat production quantity in Egypt reached 4 million tonnes.',
            ),
        ];
        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $trace = $this->firstTrace($report);

        $this->assertSame('conflict', $trace['status']);
        foreach ($trace['accuracy_outcomes'] ?? [] as $outcome) {
            $this->assertStringStartsWith('accuracy_', (string) $outcome);
            $this->assertNotSame('supported', $trace['status']);
            $this->assertNotSame('partially_supported', $trace['status']);
        }
        $this->assertSame('conflict', $trace['status']);
    }

    public function test_r2_g_existing_trace_statuses_remain_backward_compatible(): void
    {
        $supported = (new QuestionClaimSynthesisContract)->build(
            $this->phase5Plan(),
            $this->phase5Validation([$this->phase5Evidence('e-1', ClaimEvidenceRelationship::SUPPORTED)], true),
            [$this->phase5Evidence('e-1', ClaimEvidenceRelationship::SUPPORTED)],
        );
        $this->assertSame('supported', $supported['answer_statement_traces'][0]['status']);
        $this->assertSame([], $supported['answer_statement_traces'][0]['accuracy_outcomes']);

        $partial = (new QuestionClaimSynthesisContract)->build(
            $this->phase5Plan(),
            $this->phase5Validation(
                [$this->phase5Evidence('e-p', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED)],
                true,
            ),
            [$this->phase5Evidence('e-p', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED)],
        );
        $this->assertSame('partially_supported', $partial['answer_statement_traces'][0]['status']);
        $this->assertSame([], $partial['answer_statement_traces'][0]['accuracy_outcomes']);

        $insufficient = (new QuestionClaimSynthesisContract)->build(
            $this->phase5Plan(),
            $this->phase5Validation([], false),
            [],
        );
        $this->assertSame('insufficient_evidence', $insufficient['answer_statement_traces'][0]['status']);
        $this->assertSame([], $insufficient['answer_statement_traces'][0]['accuracy_outcomes']);
        $this->assertContains(
            'insufficient_validated_evidence_for_question_claim',
            $insufficient['answer_statement_traces'][0]['limitations'],
        );
    }

    #[DataProvider('limitationKeyLanguageProvider')]
    public function test_r3_limitation_keys_are_localized_and_do_not_leak(
        string $code,
        string $language,
        string $phraseKey,
    ): void {
        $phrased = $this->phraseUserFacingLimitation($language, $code);
        $expected = AnswerComposerPhrases::get($language, $phraseKey);

        $this->assertSame($expected, $phrased);
        $this->assertNotSame($code, $phrased);
        $this->assertStringNotContainsString($code, $phrased);
        $this->assertNotSame('', trim($phrased));
        foreach (self::LEAKED_MACHINE_KEYS as $machineKey) {
            $this->assertStringNotContainsString($machineKey, $phrased);
        }
    }

    public function test_r3_unknown_key_uses_localized_generic_fallback(): void
    {
        foreach (['ar', 'en', 'fr', 'tr'] as $language) {
            $phrased = $this->phraseUserFacingLimitation($language, 'not_a_real_internal_limitation_key');
            $this->assertSame(AnswerComposerPhrases::get($language, 'limitation_generic'), $phrased);
            $this->assertStringNotContainsString('not_a_real_internal_limitation_key', $phrased);
            $this->assertStringNotContainsString('limitation_', $phrased);
        }
    }

    public function test_r3_compose_user_facing_limitations_do_not_leak_machine_keys(): void
    {
        $cases = [
            $this->composeQuantity(
                'What is the production quantity and harvested area of wheat in Italy?',
                ['quantity', 'area'],
                'Italy',
                [$this->withEvidenceText(
                    $this->phase5Evidence('e-q-only', ClaimEvidenceRelationship::SUPPORTED),
                    'Wheat production quantity in Italy was 7.2 million tonnes.',
                )],
            ),
            $this->composeQuantity(
                'What is wheat production quantity in Egypt?',
                ['quantity'],
                'Egypt',
                [$this->withEvidenceText(
                    $this->phase5Evidence('e-partial', ClaimEvidenceRelationship::PARTIALLY_SUPPORTED),
                    'Wheat production quantity estimates are incomplete for Egypt.',
                )],
            ),
            $this->composeQuantity(
                'What is wheat production quantity in Egypt?',
                ['quantity'],
                'Egypt',
                [$this->withEvidenceText(
                    $this->phase5Evidence('e-conflict', ClaimEvidenceRelationship::CONFLICTING, conflict: true),
                    'Wheat production quantity was 9 million tonnes according to source A.',
                )],
            ),
        ];

        foreach ($cases as $report) {
            $blob = implode("\n", array_map('strval', $report->limitations));
            foreach (self::LEAKED_MACHINE_KEYS as $machineKey) {
                $this->assertStringNotContainsString($machineKey, $blob);
            }
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function limitationKeyLanguageProvider(): array
    {
        $keys = [
            'insufficient_validated_evidence_for_question_claim' => 'limitation_insufficient_claim_evidence',
            'partial_evidence_support' => 'limitation_partial_claim_support',
            'validation_evidence_insufficient' => 'limitation_validation_insufficient',
            'conflicting_evidence_for_question_claim' => 'limitation_claim_conflict',
            'supporting_not_direct_evidence' => 'limitation_supporting_not_direct',
            'comparison_decomposition_unsupported' => 'limitation_comparison_incomplete',
            'accuracy_entity_incompatible' => 'limitation_accuracy_entity',
            'accuracy_property_unsupported' => 'limitation_accuracy_property',
            'accuracy_geography_unsupported' => 'limitation_accuracy_geography',
            'accuracy_period_unsupported' => 'limitation_accuracy_period',
            'accuracy_numeric_unsupported' => 'limitation_accuracy_numeric',
        ];
        $rows = [];
        foreach ($keys as $code => $phraseKey) {
            foreach (['ar', 'en', 'fr', 'tr'] as $language) {
                $rows[] = [$code, $language, $phraseKey];
            }
        }

        return $rows;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $evidence
     */
    private function composeQuantity(
        string $question,
        array $requested,
        string $location,
        array $evidence,
    ): object {
        $plan = $this->phase5Plan(
            question: $question,
            requested: $requested,
            location: $location,
            constraints: ['answer_language' => 'en'],
        );

        return app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
    }

    private function composeSupportingTimeCase(
        string $requestedYear,
        ?string $observationYear,
        int $publicationYear,
        string $title,
    ): object {
        $item = $this->supportingTimeEvidence('time-item', $title, $observationYear, $publicationYear);
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in '.$requestedYear.'?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => $requestedYear,
            ],
        );

        return app(AnswerComposer::class)->compose($plan, $this->phase5Validation([$item], true));
    }

    private function supportingTimeEvidence(
        string $id,
        string $title,
        ?string $observationYear,
        int $publicationYear,
    ): ScientificEvidenceItem {
        $base = $this->phase5Evidence(
            $id,
            ClaimEvidenceRelationship::SUPPORTED,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
        );
        $factors = [
            'evidence_directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
            'answer_eligible' => true,
            'species_relation' => 'same_species',
            'entity_matched' => true,
            'topic_matched' => true,
        ];
        if ($observationYear !== null) {
            $factors['observation'] = ['year' => $observationYear];
            $factors['observation_year'] = $observationYear;
        }

        return $this->withPublicationYear(
            $this->withTitle(
                $this->withEvidenceText(
                    $this->withQuality($base, $factors),
                    'Wheat irrigation studies found improved water-use efficiency under drip systems.',
                ),
                $title,
            ),
            $publicationYear,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function firstTrace(object $report): array
    {
        $traces = $report->observability['answer_statement_traces'] ?? [];
        $this->assertNotEmpty($traces);
        $this->assertIsArray($traces[0]);

        return $traces[0];
    }

    private function phraseUserFacingLimitation(string $language, string $code): string
    {
        $method = new ReflectionMethod(AnswerComposer::class, 'phraseUserFacingLimitation');

        return (string) $method->invoke(app(AnswerComposer::class), $language, $code);
    }

    private function withEvidenceText(ScientificEvidenceItem $item, string $text): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $item->evidenceId,
            sourceId: $item->sourceId,
            sourceKey: $item->sourceKey,
            sourceType: $item->sourceType,
            publicationTitle: $item->publicationTitle,
            authors: $item->authors,
            institution: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            retrievedAt: $item->retrievedAt,
            agriculturalDomain: $item->agriculturalDomain,
            claimTopic: $item->claimTopic,
            evidenceText: $text,
            validationStatus: $item->validationStatus,
            validationFailures: $item->validationFailures,
            claimRelationship: $item->claimRelationship,
            confidence: $item->confidence,
            qualityScore: $item->qualityScore,
            qualityFactors: $item->qualityFactors,
            sourceAttribution: $item->sourceAttribution,
            hasConflict: $item->hasConflict,
            conditions: $item->conditions,
            cropOrEntity: $item->cropOrEntity ?? null,
        );
    }

    private function withTitle(ScientificEvidenceItem $item, string $title): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $item->evidenceId,
            sourceId: $item->sourceId,
            sourceKey: $item->sourceKey,
            sourceType: $item->sourceType,
            publicationTitle: $title,
            authors: $item->authors,
            institution: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            retrievedAt: $item->retrievedAt,
            agriculturalDomain: $item->agriculturalDomain,
            claimTopic: $item->claimTopic,
            evidenceText: $item->evidenceText,
            validationStatus: $item->validationStatus,
            validationFailures: $item->validationFailures,
            claimRelationship: $item->claimRelationship,
            confidence: $item->confidence,
            qualityScore: $item->qualityScore,
            qualityFactors: $item->qualityFactors,
            sourceAttribution: $item->sourceAttribution,
            hasConflict: $item->hasConflict,
            conditions: $item->conditions,
            cropOrEntity: $item->cropOrEntity ?? null,
        );
    }

    private function withPublicationYear(ScientificEvidenceItem $item, int $year): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $item->evidenceId,
            sourceId: $item->sourceId,
            sourceKey: $item->sourceKey,
            sourceType: $item->sourceType,
            publicationTitle: $item->publicationTitle,
            authors: $item->authors,
            institution: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $year,
            retrievedAt: $item->retrievedAt,
            agriculturalDomain: $item->agriculturalDomain,
            claimTopic: $item->claimTopic,
            evidenceText: $item->evidenceText,
            validationStatus: $item->validationStatus,
            validationFailures: $item->validationFailures,
            claimRelationship: $item->claimRelationship,
            confidence: $item->confidence,
            qualityScore: $item->qualityScore,
            qualityFactors: $item->qualityFactors,
            sourceAttribution: $item->sourceAttribution,
            hasConflict: $item->hasConflict,
            conditions: $item->conditions,
            cropOrEntity: $item->cropOrEntity ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $factors
     */
    private function withQuality(ScientificEvidenceItem $item, array $factors): ScientificEvidenceItem
    {
        return new ScientificEvidenceItem(
            evidenceId: $item->evidenceId,
            sourceId: $item->sourceId,
            sourceKey: $item->sourceKey,
            sourceType: $item->sourceType,
            publicationTitle: $item->publicationTitle,
            authors: $item->authors,
            institution: $item->institution,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            retrievedAt: $item->retrievedAt,
            agriculturalDomain: $item->agriculturalDomain,
            claimTopic: $item->claimTopic,
            evidenceText: $item->evidenceText,
            validationStatus: $item->validationStatus ?: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: $item->validationFailures,
            claimRelationship: $item->claimRelationship,
            confidence: $item->confidence,
            qualityScore: $item->qualityScore,
            qualityFactors: array_merge($item->qualityFactors, $factors),
            sourceAttribution: array_merge($item->sourceAttribution, $factors),
            hasConflict: $item->hasConflict,
            conditions: $item->conditions,
            cropOrEntity: $item->cropOrEntity ?? null,
        );
    }
}
