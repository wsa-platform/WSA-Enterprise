<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleFromHeader;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Tests\TestCase;

/**
 * R2: answer_language follows the question language, never platform UI locale.
 * Retrieval stays English-first scholarly (ADR-022 §15).
 */
class ScientificResearchLanguageContractTest extends TestCase
{
    /** @return list<string> */
    private function platformLocales(): array
    {
        return SetLocaleFromHeader::SUPPORTED_LOCALES;
    }

    public function test_accept_language_sets_ui_locale_not_answer_language(): void
    {
        $response = $this->postJson(
            '/api/v1/public/research-agent/plan',
            ['query' => 'What are the types of agricultural land in Egypt?'],
            ['Accept-Language' => 'ar'],
        );

        $response->assertOk();
        $query = $response->json('query_understanding');
        $this->assertSame('en', $query['language'] ?? null);
        $this->assertSame('en', $query['constraints']['question_language'] ?? null);
        // R2: English question → English answer language regardless of Accept-Language.
        $this->assertSame('en', $query['constraints']['answer_language'] ?? null);
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_answer_language_follows_question_not_platform_locale(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $questions = [
            'ar' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
            'en' => 'What are the types of agricultural land in Egypt?',
            'tr' => "Mısır'daki tarım arazilerinin türleri nelerdir?",
            'fr' => 'Quels sont les types de terres agricoles en Égypte ?',
        ];

        foreach ($this->platformLocales() as $platform) {
            app()->setLocale($platform);
            foreach ($questions as $questionLang => $question) {
                $understood = $qus->understand(['query' => $question]);
                $this->assertSame(
                    $questionLang,
                    $understood->language,
                    "question language for {$platform}/{$questionLang}",
                );
                $this->assertSame(
                    $questionLang,
                    $understood->constraints['question_language'] ?? null,
                    "question_language for {$platform}/{$questionLang}",
                );
                $this->assertSame(
                    $questionLang,
                    $understood->constraints['answer_language'] ?? null,
                    "R2 answer_language follows question, not platform, for {$platform}/{$questionLang}",
                );
            }
        }
    }

    public function test_retrieval_variants_stay_english_for_arabic_question(): void
    {
        app()->setLocale('en');
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $this->assertSame('ar', $plan->normalizedQuery->constraints['answer_language'] ?? null);
        $this->assertSame('ar', $plan->normalizedQuery->language);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $joined = mb_strtolower(implode(' ', $variants));
        $this->assertStringContainsString('egypt', $joined);
        $this->assertTrue(
            str_contains($joined, 'land') || str_contains($joined, 'soil'),
            $joined,
        );
        foreach ($variants as $variant) {
            $this->assertSame(
                0,
                preg_match('/\p{Arabic}/u', $variant),
                'retrieval query must stay English-first scholarly: '.$variant,
            );
        }
    }

    public function test_arabic_question_labels_english_evidence_excerpts(): void
    {
        app()->setLocale('en');
        $englishSnippet = 'Agricultural land types in Egypt include alluvial soils, sandy soils, calcareous soils, and saline soils.';
        $originalUrl = 'https://doi.org/10.1000/lang-contract-land';
        $originalTitle = 'Classification of agricultural land types in Egypt';
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $this->assertSame('ar', $plan->normalizedQuery->language);
        $this->assertSame('ar', $plan->normalizedQuery->constraints['answer_language'] ?? null);

        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'lang-ar-en',
                $englishSnippet,
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => $originalTitle,
                    'doi' => '10.1000/lang-contract-land',
                    'url' => $originalUrl,
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                ],
            ),
        ]));

        $this->assertSame('ar', $report->language);
        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $this->mainAnswerSection($report->answer));
        // Foreign excerpts must be attributed, not unmarked English prose as the answer voice.
        $this->assertStringContainsString('مقتطف من المصدر', $report->answer);
        $this->assertNotEmpty($report->citations);
        $this->assertSame($originalTitle, $report->citations[0]->title ?? null);
        $this->assertSame($originalUrl, $report->citations[0]->url ?? null);
    }

    public function test_arabic_causal_irrigation_answer_includes_attributed_excerpts_not_chrome_only(): void
    {
        $prose = 'Deficit irrigation at 70% of crop evapotranspiration reduced wheat grain yield by approximately 12% compared with full irrigation while improving water productivity under arid conditions.';
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما تأثير الري الناقص على إنتاجية القمح؟',
            'force_execute' => true,
        ]);
        $this->assertSame('crop_water_requirement', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);
        $this->assertSame('ar', $plan->normalizedQuery->constraints['answer_language'] ?? null);

        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'irrig-ar-1',
                $prose,
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Deficit irrigation effects on wheat yield',
                    'doi' => '10.1000/deficit-wheat-1',
                    'url' => 'https://doi.org/10.1000/deficit-wheat-1',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'claimTopic' => 'crop water requirement',
                ],
            ),
        ]));

        $this->assertSame('ar', $report->language);
        $this->assertGreaterThan(200, mb_strlen($report->answer), $report->answer);
        $this->assertStringContainsString('مقتطف من المصدر', $report->answer);
        $this->assertStringContainsString('Deficit irrigation', $report->answer);
        $this->assertStringContainsString('تشير الأدلة العلمية', $this->mainAnswerSection($report->answer));
    }

    public function test_english_question_yields_english_prose_regardless_of_arabic_ui(): void
    {
        app()->setLocale('ar');
        $englishSnippet = 'Agricultural land types in Egypt include alluvial soils, sandy soils, calcareous soils, and saline soils.';
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);
        $this->assertSame('en', $plan->normalizedQuery->language);
        $this->assertSame('en', $plan->normalizedQuery->constraints['answer_language'] ?? null);

        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'lang-en-ui-ar',
                $englishSnippet,
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Classification of agricultural land types in Egypt',
                    'doi' => '10.1000/lang-contract-en-ui',
                    'url' => 'https://doi.org/10.1000/lang-contract-en-ui',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                ],
            ),
        ]));

        $this->assertSame('en', $report->language);
        $main = $this->mainAnswerSection($report->answer);
        $this->assertStringContainsString('Direct scientific evidence', $main);
        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', $main);
    }

    public function test_question_language_matrix_localizes_composer_prose(): void
    {
        $markers = [
            'en' => ['query' => 'What is the best temperature for tomato seed germination?', 'marker' => 'Direct scientific evidence'],
            'ar' => ['query' => 'ما هي أفضل درجة حرارة لإنبات بذور الطماطم؟', 'marker' => 'تشير الأدلة العلمية المباشرة'],
            'tr' => ['query' => 'Domates tohumu çimlenmesi için en uygun sıcaklık nedir?', 'marker' => 'Doğrudan bilimsel kanıtlar'],
            'fr' => ['query' => 'Quelle est la meilleure température pour la germination des graines de tomate ?', 'marker' => 'Les preuves scientifiques directes'],
        ];
        $snippet = 'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes.';

        foreach ($markers as $lang => $row) {
            app()->setLocale('en');
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
                'query' => $row['query'],
            ]);
            $this->assertSame($lang, $plan->normalizedQuery->language, $lang);
            $this->assertSame($lang, $plan->normalizedQuery->constraints['answer_language'] ?? null, $lang);

            $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
                $this->usableEvidence(
                    'lang-range-'.$lang,
                    $snippet,
                    ClaimEvidenceRelationship::SUPPORTED,
                    [
                        'publicationTitle' => 'Effect of temperature on tomato seed germination',
                        'doi' => '10.1000/lang-range-'.$lang,
                        'url' => 'https://doi.org/10.1000/lang-range-'.$lang,
                        'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    ],
                ),
            ]));

            $this->assertSame($lang, $report->language, $lang);
            $this->assertStringContainsString($row['marker'], $this->mainAnswerSection($report->answer), $lang);
        }
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function validationReport(array $items): EvidenceValidationExecutionReport
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
            evidenceSufficient: true,
            validatorsUsed: ['test'],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function usableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        array $overrides = [],
    ): ScientificEvidenceItem {
        $directness = $overrides['directness'] ?? ScientificEvidenceDirectnessAssessor::DIRECT;

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: (string) ($overrides['sourceId'] ?? 'source-'.$evidenceId),
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: array_key_exists('doi', $overrides) ? $overrides['doi'] : '10.1000/'.$evidenceId,
            url: array_key_exists('url', $overrides) ? $overrides['url'] : 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'soil',
            claimTopic: (string) ($overrides['claimTopic'] ?? 'land classification'),
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: 0.8,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => $directness,
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => $directness,
            ],
        );
    }

    private function mainAnswerSection(string $answer): string
    {
        foreach ([
            '### المصادر الأساسية',
            '### المصدر الأساسي',
            '### المصادر',
            '### Primary sources',
            '### Primary source',
            '### Sources',
            '### Birincil kaynaklar',
            '### Birincil kaynak',
            '### Kaynaklar',
            '### Sources principales',
            '### Source principale',
        ] as $marker) {
            $pos = mb_strpos($answer, $marker);
            if ($pos !== false) {
                return trim(mb_substr($answer, 0, $pos));
            }
        }

        return $answer;
    }
}
