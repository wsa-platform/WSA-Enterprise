<?php

namespace Tests\Unit\Agriculture\Intelligence;

use App\Services\Agriculture\Intelligence\Contracts\AnswerStatus;
use App\Services\Agriculture\Intelligence\DTO\AnswerEligibility;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\FusedEvidenceBundle;
use App\Services\Agriculture\Intelligence\DTO\WebConsensusResult;
use App\Services\Agriculture\Intelligence\Fusion\EvidenceFusionService;
use App\Services\Agriculture\Intelligence\Fusion\WebConsensusService;
use App\Services\Agriculture\Intelligence\Normalization\UnitNormalizationService;
use App\Services\Agriculture\Intelligence\Normalization\WebResultNormalizer;
use App\Services\Agriculture\Intelligence\Orchestration\AnswerEligibilityResolver;
use Tests\TestCase;

class AnswerEligibilityAndFusionTest extends TestCase
{
    public function test_scientific_insufficient_web_sufficient_yields_general_web_overall_eligible(): void
    {
        $fusion = new FusedEvidenceBundle(
            results: [
                new CanonicalAgriculturalResult(
                    providerId: 'web_search',
                    status: 'success',
                    webEvidence: [
                        [
                            'evidence_family' => 'web',
                            'title' => 'Agronomy overview',
                            'snippet' => 'Typical ranges discussed online',
                            'url' => 'https://example.com/a',
                            'confidence' => 0.5,
                            'quality' => 0.6,
                        ],
                    ],
                    confidence: 0.45,
                ),
            ],
            dedupedEvidence: [
                ['evidence_family' => 'web', 'title' => 'Agronomy overview'],
            ],
            providersUsed: ['web_search'],
            webConsensus: new WebConsensusResult(
                hasConsensus: true,
                representativeValue: 'Typical ranges discussed online',
                agreementScore: 0.8,
                values: [['text' => 'Typical ranges discussed online', 'quality' => 0.6]],
                status: 'textual',
            ),
        );

        $eligibility = app(AnswerEligibilityResolver::class)->resolve(
            $fusion,
            scientificAnswerEligible: false,
            scientificPartial: false,
        );

        $this->assertFalse($eligibility->scientificAnswerEligible);
        $this->assertTrue($eligibility->webAnswerEligible);
        $this->assertTrue($eligibility->overallAnswerEligible);
        $this->assertSame(AnswerStatus::GENERAL_WEB, $eligibility->answerStatus);
        $this->assertTrue(app(AnswerEligibilityResolver::class)->assertGeneralWebFallbackWorks($eligibility));
    }

    public function test_both_insufficient_yields_insufficient(): void
    {
        $fusion = new FusedEvidenceBundle(results: [], webConsensus: new WebConsensusResult(false, status: 'empty'));
        $eligibility = AnswerEligibility::resolve(false, false);
        $this->assertFalse($eligibility->overallAnswerEligible);
        $this->assertSame(AnswerStatus::INSUFFICIENT, $eligibility->answerStatus);
        $this->assertFalse(app(AnswerEligibilityResolver::class)->assertGeneralWebFallbackWorks($eligibility));
    }

    public function test_scientific_eligible_is_scientific_verified(): void
    {
        $eligibility = AnswerEligibility::resolve(webAnswerEligible: false, scientificAnswerEligible: true);
        $this->assertTrue($eligibility->overallAnswerEligible);
        $this->assertSame(AnswerStatus::SCIENTIFIC_VERIFIED, $eligibility->answerStatus);
    }

    public function test_web_consensus_is_not_first_hit(): void
    {
        $consensus = app(WebConsensusService::class)->build([
            ['numeric_value' => 20, 'quality' => 0.2, 'provider_id' => 'a', 'snippet' => 'low'],
            ['numeric_value' => 21, 'quality' => 0.9, 'provider_id' => 'b', 'snippet' => 'high'],
            ['numeric_value' => 22, 'quality' => 0.8, 'provider_id' => 'c', 'snippet' => 'also'],
        ]);

        $this->assertNotSame(20.0, (float) $consensus->representativeValue);
        $this->assertGreaterThan(20.0, (float) $consensus->representativeValue);
        $this->assertSame(20.0, $consensus->rangeMin);
        $this->assertSame(22.0, $consensus->rangeMax);
        $this->assertTrue($consensus->hasConsensus);
        $this->assertSame('consensus', $consensus->status);
    }

    public function test_web_consensus_excludes_unrelated_numeric_context(): void
    {
        $consensus = app(WebConsensusService::class)->build([
            ['numeric_value' => 31, 'quality' => 0.9, 'provider_id' => 'a', 'snippet' => 'Oct 31, 2025 · overview'],
            ['numeric_value' => 3.5, 'quality' => 0.9, 'provider_id' => 'b', 'snippet' => '3.5 million records indexed'],
            ['numeric_value' => 10, 'quality' => 0.9, 'provider_id' => 'c', 'snippet' => 'wait 10 days after application'],
            ['numeric_value' => 123, 'quality' => 0.9, 'provider_id' => 'd', 'snippet' => 'see page 123 for methods'],
            ['range_min' => 2010, 'range_max' => 2024, 'quality' => 0.8, 'provider_id' => 'e', 'snippet' => 'coverage 2010-2024'],
        ]);

        $this->assertFalse($consensus->hasConsensus);
        $this->assertNotSame(7.333333, $consensus->representativeValue);
        $this->assertNull($consensus->rangeMin);
        $this->assertNull($consensus->rangeMax);
        $this->assertSame('textual', $consensus->status);
    }

    public function test_web_consensus_preserves_compatible_range_without_midpoint(): void
    {
        $consensus = app(WebConsensusService::class)->build([
            ['range_min' => 25, 'range_max' => 30, 'unit' => 'c', 'quality' => 0.8, 'provider_id' => 'a', 'snippet' => 'typical range 25-30 c'],
            ['range_min' => 24, 'range_max' => 28, 'unit' => 'c', 'quality' => 0.7, 'provider_id' => 'b', 'snippet' => 'observed 24-28 c'],
        ]);

        $this->assertTrue($consensus->hasConsensus);
        $this->assertSame(25.0, $consensus->rangeMin);
        $this->assertSame(28.0, $consensus->rangeMax);
        $this->assertSame('25–28', $consensus->representativeValue);
        $this->assertNotSame(26.5, $consensus->representativeValue);
        $this->assertNotSame(27.5, $consensus->representativeValue);
    }

    public function test_web_consensus_does_not_average_incompatible_units_or_conflicts(): void
    {
        $incompatible = app(WebConsensusService::class)->build([
            ['numeric_value' => 25, 'unit' => 'c', 'quality' => 0.9, 'provider_id' => 'a', 'snippet' => '25 c'],
            ['numeric_value' => 80, 'unit' => 'kg', 'quality' => 0.9, 'provider_id' => 'b', 'snippet' => '80 kg'],
        ]);

        $this->assertNotEquals(
            (25 * 0.9 + 80 * 0.9) / 1.8,
            (float) $incompatible->representativeValue
        );

        $conflicted = app(WebConsensusService::class)->build([
            ['range_min' => 10, 'range_max' => 12, 'unit' => 'c', 'quality' => 0.8, 'provider_id' => 'a', 'snippet' => '10-12 c'],
            ['range_min' => 40, 'range_max' => 50, 'unit' => 'c', 'quality' => 0.8, 'provider_id' => 'b', 'snippet' => '40-50 c'],
        ]);

        $this->assertFalse($conflicted->hasConsensus);
        $this->assertSame('conflicted', $conflicted->status);
        $this->assertNull($conflicted->representativeValue);
        $this->assertNotEmpty($conflicted->conflicts);
    }

    public function test_fusion_dedupes_by_doi_and_detects_conflicts(): void
    {
        $fusion = app(EvidenceFusionService::class)->fuse([
            new CanonicalAgriculturalResult(
                providerId: 'openalex',
                status: 'success',
                scientificEvidence: [
                    ['doi' => '10.1/abc', 'title' => 'A', 'numeric_value' => 10, 'confidence' => 0.8, 'evidence_family' => 'scientific'],
                ],
            ),
            new CanonicalAgriculturalResult(
                providerId: 'crossref',
                status: 'success',
                scientificEvidence: [
                    ['doi' => '10.1/abc', 'title' => 'A dup', 'numeric_value' => 40, 'confidence' => 0.8, 'evidence_family' => 'scientific'],
                ],
            ),
        ]);

        $this->assertCount(1, $fusion->dedupedEvidence);
        $this->assertNotEmpty($fusion->conflicts);
        $this->assertArrayHasKey('architectural', $fusion->confidence);
        $this->assertArrayHasKey('provider_model', $fusion->confidence);
        $this->assertArrayHasKey('scientific', $fusion->confidence);
    }

    public function test_unit_normalization_keeps_original_or_refuses_unsafe(): void
    {
        $units = app(UnitNormalizationService::class);
        $safe = $units->normalize(100, 'cm');
        $this->assertTrue($safe->conversionSafe);
        $this->assertSame(100, $safe->value);
        $this->assertSame(1.0, $safe->normalizedValue);
        $this->assertSame('m', $safe->normalizedUnit);

        $unsafe = $units->normalize(5, 'stone');
        $this->assertFalse($unsafe->conversionSafe);
        $this->assertNull($unsafe->normalizedValue);
        $this->assertSame(5, $unsafe->value);
    }

    public function test_web_result_normalizer(): void
    {
        $n = app(WebResultNormalizer::class)->normalize([
            'title' => 'Hello',
            'url' => 'https://example.com',
            'snippet' => 'Value 12.5 mm noted',
        ], 'web_search');

        $this->assertSame('web', $n['evidence_family']);
        $this->assertSame(12.5, $n['numeric_value']);
        $this->assertSame('mm', $n['unit']);
        $this->assertSame('Value 12.5 mm noted', $n['context']);
        $this->assertSame(\App\Services\Agriculture\Intelligence\Contracts\SourceRole::WEB_SOURCE, $n['source_role']);
    }

    public function test_web_result_normalizer_preserves_range_without_inventing_midpoint(): void
    {
        $n = app(WebResultNormalizer::class)->normalize([
            'title' => 'Range note',
            'url' => 'https://example.com/r',
            'snippet' => 'Recommended window 24-28 °C under controlled conditions',
        ], 'web_search');

        $this->assertSame(24.0, $n['range_min']);
        $this->assertSame(28.0, $n['range_max']);
        $this->assertSame('c', $n['unit']);
        $this->assertNull($n['numeric_value']);
        $this->assertStringContainsString('controlled conditions', (string) $n['context']);
    }

    public function test_fusion_keeps_official_and_weather_and_does_not_treat_citation_doi_as_scientific(): void
    {
        $fusion = app(EvidenceFusionService::class)->fuse([
            new CanonicalAgriculturalResult(
                providerId: 'fao_stat',
                status: 'success',
                stats: [[
                    'title' => 'Official production',
                    'source_role' => \App\Services\Agriculture\Intelligence\Contracts\SourceRole::OFFICIAL_AGRICULTURAL_DATA,
                    'evidence_family' => 'official',
                    'confidence' => 0.7,
                    'url' => 'https://www.fao.org/faostat/',
                ]],
            ),
            new CanonicalAgriculturalResult(
                providerId: 'open_meteo',
                status: 'success',
                weather: [[
                    'temperature_c' => 18.2,
                    'source_role' => \App\Services\Agriculture\Intelligence\Contracts\SourceRole::ENVIRONMENTAL_DATA,
                    'evidence_family' => 'environmental',
                    'confidence' => 0.6,
                    'location' => ['latitude' => 30.0, 'longitude' => 31.0],
                ]],
            ),
            new CanonicalAgriculturalResult(
                providerId: 'crossref',
                status: 'success',
                scientificEvidence: [[
                    'doi' => '10.1/meta-only',
                    'title' => 'Metadata record',
                    'source_role' => \App\Services\Agriculture\Intelligence\Contracts\SourceRole::CITATION_METADATA,
                    'evidence_family' => 'citation_metadata',
                    'confidence' => 0.9,
                ]],
            ),
        ]);

        $roles = array_column($fusion->dedupedEvidence, 'source_role');
        $this->assertContains(\App\Services\Agriculture\Intelligence\Contracts\SourceRole::OFFICIAL_AGRICULTURAL_DATA, $roles);
        $this->assertContains(\App\Services\Agriculture\Intelligence\Contracts\SourceRole::ENVIRONMENTAL_DATA, $roles);
        $this->assertContains(\App\Services\Agriculture\Intelligence\Contracts\SourceRole::CITATION_METADATA, $roles);
        $this->assertNull($fusion->confidence['scientific']);
    }
}
