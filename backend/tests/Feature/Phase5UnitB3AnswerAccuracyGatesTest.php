<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 5 Unit B3 — answer expression accuracy gates (Catalog-free).
 */
class Phase5UnitB3AnswerAccuracyGatesTest extends TestCase
{
    use Phase5UnitATestFixtures;

    public function test_b3_01_supported_numeric_value_appears_in_answer(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-ok', ClaimEvidenceRelationship::SUPPORTED),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertNotEmpty($report->claims, 'expected claim traces; status='.$report->status);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertTrue(
            str_contains($report->claims[0]->claimText, '9')
            || $this->answerBlob($report) !== '',
        );
        $this->assertTrue(
            str_contains($this->answerBlob($report), '9')
            || ($report->claims[0]->numericalValues !== []),
        );
        $this->assertTrue(
            str_contains(implode(' ', $report->claims[0]->numericalValues), '9')
            || str_contains($report->claims[0]->claimText, '9'),
        );
    }

    public function test_b3_02_unsupported_numeric_value_does_not_appear(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-bad', ClaimEvidenceRelationship::SUPPORTED),
            'The study was cited 154 times and discussed soil moisture at 12%.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $blob = $this->answerBlob($report);
        if ($report->claims !== []) {
            $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        }
        $this->assertStringNotContainsString('154', $blob);
        $this->assertStringNotContainsString('12%', $blob);
        $this->assertTrue(
            $report->claims === []
            || $this->hasAccuracySignal($report)
            || trim(implode(' ', $report->limitations)) !== ''
            || in_array($report->status, ['no_validated_evidence', 'insufficient_evidence'], true),
        );
    }

    public function test_b3_03_publication_year_not_promoted_to_measurement(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-year', ClaimEvidenceRelationship::SUPPORTED),
            'Published in 2022. DOI 10.1000/example. No production quantity measurement is reported.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $blob = $this->answerBlob($report);

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertFalse($this->presentsYearAsMeasurement($blob, '2022'));
    }

    public function test_b3_04_wrong_property_no_factual_substitution(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-area', ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat in Egypt covered 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $blob = $this->answerBlob($report);

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('1.1', $blob);
        $this->assertTrue($this->hasAccuracySignal($report));
    }

    public function test_b3_05_wrong_entity_no_factual_substitution(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $base = $this->phase5Evidence('e-ent', ClaimEvidenceRelationship::SUPPORTED);
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'species_relation' => 'different_species',
                'entity_matched' => false,
            ]),
            'Maize production quantity in Egypt reached 9 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('9 million', $this->answerBlob($report));
    }

    public function test_b3_06_wrong_geography_no_factual_substitution(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Italy?',
            requested: ['quantity'],
            location: 'Italy',
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $base = $this->phase5Evidence('e-geo', ClaimEvidenceRelationship::SUPPORTED);
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
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

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('7.2', $this->answerBlob($report));
    }

    public function test_b3_07_wrong_year_no_factual_substitution(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
            ],
        );
        $base = $this->phase5Evidence('e-time', ClaimEvidenceRelationship::SUPPORTED);
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
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

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('9 million', $this->answerBlob($report));
    }

    public function test_b3_08_multiple_numeric_values_not_arbitrarily_selected(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Italy?',
            requested: ['quantity'],
            location: 'Italy',
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-multi', ClaimEvidenceRelationship::SUPPORTED),
            'Wheat figures in Italy: production quantity 7.2 million tonnes and harvested area 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));
        $blob = $this->answerBlob($report);

        // Must not arbitrarily select among mixed measurement classes.
        $this->assertSame('', trim($report->claims[0]->claimText));
        $this->assertSame([], $report->claims[0]->numericalValues);
        $this->assertStringNotContainsString('1.1', $blob);
        $this->assertTrue($this->hasAccuracySignal($report) || trim(implode(' ', $report->limitations)) !== '');
    }

    public function test_b3_09_partially_supported_keeps_supported_portion_only(): void
    {
        $plan = $this->phase5Plan(
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence(
                'e-partial',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ),
            'Wheat production quantity in Egypt reached 9 million tonnes under irrigated trials only.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $report->claims[0]->claimRelationship);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertContains('partial_evidence_support', $report->claims[0]->limitations);
    }

    public function test_b3_10_conflicting_claim_no_arbitrary_value(): void
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

        foreach ($report->claims as $claim) {
            if ($claim->claimRelationship === ClaimEvidenceRelationship::CONFLICTING) {
                $this->assertSame('', trim($claim->claimText));
                $this->assertSame([], $claim->numericalValues);
            }
        }
    }

    public function test_b3_11_supporting_only_not_promoted_to_direct_fact(): void
    {
        $plan = $this->phase5Plan(requested: ['quantity']);
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence(
                'e-sup',
                ClaimEvidenceRelationship::SUPPORTED,
                ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ),
            'Supporting context notes wheat production quantity near 9 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertTrue(
            $report->status === 'insufficient_evidence'
            || str_contains(mb_strtolower($report->answer), 'insufficient')
            || in_array('supporting_not_direct_evidence', $report->claims[0]->limitations ?? [], true)
            || ((float) $report->confidence) <= 0.42,
        );
    }

    public function test_b3_12_rejected_numeric_does_not_leak_into_key_findings(): void
    {
        $plan = $this->phase5Plan(
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-leak', ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat covered 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        foreach ($report->keyFindings as $finding) {
            $this->assertStringNotContainsString('1.1', (string) $finding);
        }
    }

    public function test_b3_13_rejected_value_does_not_affect_factual_summary_prose(): void
    {
        $plan = $this->phase5Plan(
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-sum', ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat covered 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertStringNotContainsString('1.1', $report->conciseSummary);
        $this->assertStringNotContainsString('1.1', $report->answer);
    }

    public function test_b3_14_confidence_reflects_accuracy_filtering(): void
    {
        $plan = $this->phase5Plan(
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-conf', ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat covered 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame(0.0, (float) $report->confidence);
    }

    #[DataProvider('accuracyLimitationLanguageProvider')]
    public function test_b3_15_to_18_accuracy_limitation_languages(string $lang): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt?',
            requested: ['quantity'],
            language: $lang,
            constraints: [
                'answer_language' => $lang,
            ],
        );
        $evidence = [$this->withEvidenceText(
            $this->phase5Evidence('e-lim-'.$lang, ClaimEvidenceRelationship::SUPPORTED),
            'Harvested area of wheat covered 1.1 million hectares.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame($lang, $report->language);
        $joined = implode(' ', array_map('strval', $report->limitations));
        $this->assertNotSame('', trim($joined));
        $this->assertTrue(
            $this->hasAccuracySignal($report) || str_contains(mb_strtolower($joined), 'property')
            || str_contains($joined, 'خاصية')
            || str_contains($joined, 'özellik')
            || str_contains(mb_strtolower($joined), 'propri'),
        );
    }

    public static function accuracyLimitationLanguageProvider(): array
    {
        return [
            'ar' => ['ar'],
            'en' => ['en'],
            'fr' => ['fr'],
            'tr' => ['tr'],
        ];
    }

    public function test_b3_19_b1_language_plus_b2_multi_claim_plus_accuracy_gate(): void
    {
        $plan = $this->phase5Plan(
            question: 'ما هي كمية الإنتاج والمساحة المحصودة للقمح في إيطاليا؟',
            requested: ['quantity', 'area'],
            location: 'Italy',
            language: 'ar',
            constraints: [
                'answer_language' => 'ar',
                'ui_language' => 'en',
            ],
            context: ['ui_language' => 'en'],
        );

        $evidence = [
            $this->withEvidenceText(
                $this->phase5Evidence('e-q-ar', ClaimEvidenceRelationship::SUPPORTED),
                'Wheat production quantity in Italy was 7.2 million tonnes.',
            ),
            $this->withEvidenceText(
                $this->phase5Evidence('e-a-wrong', ClaimEvidenceRelationship::SUPPORTED),
                'Soil moisture was measured at 12% in a greenhouse trial.',
            ),
        ];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('ar', $report->language);
        $byQc = [];
        foreach ($report->claims as $claim) {
            $byQc[$claim->questionClaimId] = $claim;
        }
        $this->assertArrayHasKey('qc-1', $byQc);
        $this->assertArrayHasKey('qc-2', $byQc);
        $this->assertNotSame('', trim($byQc['qc-1']->claimText));
        $this->assertSame('', trim($byQc['qc-2']->claimText));
        $this->assertStringNotContainsString('12%', $this->answerBlob($report));
    }

    public function test_b3_time_01_publication_year_match_does_not_override_observation_mismatch(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
            ],
        );
        $base = $this->withPublicationYear(
            $this->phase5Evidence('e-time-01', ClaimEvidenceRelationship::SUPPORTED),
            2022,
        );
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
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

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('9 million', $this->answerBlob($report));
        $this->assertTrue($this->hasAccuracySignal($report));
    }

    public function test_b3_time_02_explicit_observation_year_controls_when_publication_differs(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
            ],
        );
        $base = $this->withPublicationYear(
            $this->phase5Evidence('e-time-02', ClaimEvidenceRelationship::SUPPORTED),
            2019,
        );
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'observation' => [
                    'item' => 'wheat',
                    'area' => 'Egypt',
                    'year' => '2022',
                    'element' => 'production',
                    'value' => '9',
                    'unit' => 'tonnes',
                ],
            ]),
            'Wheat production quantity in Egypt for 2022 reached 9 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertNotEmpty($report->claims);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertTrue(
            str_contains($report->claims[0]->claimText, '9')
            || ($report->claims[0]->numericalValues !== []),
        );
    }

    public function test_b3_time_03_publication_year_alone_is_not_observation_year(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2022?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2022',
            ],
        );
        $base = $this->withPublicationYear(
            $this->phase5Evidence('e-time-03', ClaimEvidenceRelationship::SUPPORTED),
            2022,
        );
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
            ]),
            'Wheat production quantity in Egypt reached 9 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertSame('', trim($report->claims[0]->claimText ?? ''));
        $this->assertStringNotContainsString('9 million', $this->answerBlob($report));
        $this->assertTrue(
            $this->hasAccuracySignal($report)
            || in_array($report->status, ['insufficient_evidence', 'no_validated_evidence'], true),
        );
    }

    public function test_b3_time_04_explicit_observation_year_ignored_publication_year(): void
    {
        $plan = $this->phase5Plan(
            question: 'What is wheat production quantity in Egypt in 2021?',
            requested: ['quantity'],
            constraints: [
                'answer_language' => 'en',
                'year' => '2021',
            ],
        );
        $base = $this->withPublicationYear(
            $this->phase5Evidence('e-time-04', ClaimEvidenceRelationship::SUPPORTED),
            2022,
        );
        $evidence = [$this->withEvidenceText(
            $this->withQuality($base, [
                'evidence_directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                'observation_year' => '2021',
                'observation' => [
                    'item' => 'wheat',
                    'area' => 'Egypt',
                    'year' => '2021',
                    'element' => 'production',
                    'value' => '8',
                    'unit' => 'tonnes',
                ],
            ]),
            'Wheat production quantity in Egypt for 2021 reached 8 million tonnes.',
        )];

        $report = app(AnswerComposer::class)->compose($plan, $this->phase5Validation($evidence, true));

        $this->assertNotEmpty($report->claims);
        $this->assertNotSame('', trim($report->claims[0]->claimText));
        $this->assertTrue(
            str_contains($report->claims[0]->claimText, '8')
            || ($report->claims[0]->numericalValues !== []),
        );
    }

    private function answerBlob(object $report): string
    {
        return implode("\n", array_filter([
            (string) ($report->answer ?? ''),
            (string) ($report->conciseSummary ?? ''),
            implode("\n", $report->keyFindings ?? []),
            implode("\n", array_map(
                static fn ($claim): string => (string) ($claim->claimText ?? ''),
                $report->claims ?? [],
            )),
        ]));
    }

    private function hasAccuracySignal(object $report): bool
    {
        foreach ($report->claims ?? [] as $claim) {
            foreach ($claim->limitations ?? [] as $limitation) {
                if (str_starts_with((string) $limitation, 'accuracy_')) {
                    return true;
                }
            }
        }
        foreach ($report->limitations ?? [] as $limitation) {
            $text = mb_strtolower((string) $limitation);
            if (str_contains($text, 'property') || str_contains($text, 'numeric')
                || str_contains($text, 'geography') || str_contains($text, 'period')
                || str_contains($text, 'entity') || str_contains($text, 'measurement')
                || str_contains((string) $limitation, 'خاصية') || str_contains((string) $limitation, 'رقم')) {
                return true;
            }
        }

        return false;
    }

    private function presentsYearAsMeasurement(string $blob, string $year): bool
    {
        return (bool) preg_match('/\b'.preg_quote($year, '/').'\s*(?:tonnes?|kg|ha|t\/ha)\b/i', $blob);
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
