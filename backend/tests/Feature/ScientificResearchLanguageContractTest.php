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
 * Platform locale → answer_language → composer prose.
 * Question language is independent; retrieval stays English-first scholarly.
 */
class ScientificResearchLanguageContractTest extends TestCase
{
    /** @return list<string> */
    private function platformLocales(): array
    {
        return SetLocaleFromHeader::SUPPORTED_LOCALES;
    }

    public function test_accept_language_sets_answer_language_not_question_language(): void
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
        $this->assertSame('ar', $query['constraints']['answer_language'] ?? null);
        $this->assertSame('ar', app()->getLocale());

        $regional = $this->postJson(
            '/api/v1/public/research-agent/plan',
            ['query' => 'What are the types of agricultural land in Egypt?'],
            ['Accept-Language' => 'ar-SA,ar;q=0.9'],
        );
        $regional->assertOk();
        $this->assertSame('en', $regional->json('query_understanding.language'));
        $this->assertSame('ar', $regional->json('query_understanding.constraints.answer_language'));
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_platform_versus_question_language_matrix(): void
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
                    $platform,
                    $understood->constraints['answer_language'] ?? null,
                    "answer_language follows platform, not question, for {$platform}/{$questionLang}",
                );
            }
        }
    }

    public function test_retrieval_variants_stay_english_when_platform_is_arabic(): void
    {
        $variantsByPlatform = [];
        foreach (['ar', 'tr', 'fr'] as $platform) {
            app()->setLocale($platform);
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
                'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
            ]);
            $this->assertSame($platform, $plan->normalizedQuery->constraints['answer_language'] ?? null, $platform);
            $this->assertSame('ar', $plan->normalizedQuery->language, $platform);

            $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
            $this->assertNotEmpty($variants, $platform);
            $variantsByPlatform[$platform] = $variants;
            $joined = mb_strtolower(implode(' ', $variants));
            $this->assertStringContainsString('egypt', $joined, $platform);
            $this->assertTrue(
                str_contains($joined, 'land') || str_contains($joined, 'soil'),
                $platform.': '.$joined,
            );
            foreach ($variants as $variant) {
                $this->assertSame(
                    0,
                    preg_match('/\p{Arabic}/u', $variant),
                    $platform.' retrieval query must stay English-first scholarly: '.$variant,
                );
            }
        }

        $this->assertSame($variantsByPlatform['ar'], $variantsByPlatform['tr']);
        $this->assertSame($variantsByPlatform['ar'], $variantsByPlatform['fr']);
    }

    public function test_arabic_platform_english_evidence_does_not_yield_english_final_prose(): void
    {
        app()->setLocale('ar');
        $englishSnippet = 'Agricultural land types in Egypt include alluvial soils, sandy soils, calcareous soils, and saline soils.';
        $originalUrl = 'https://doi.org/10.1000/lang-contract-land';
        $originalTitle = 'Classification of agricultural land types in Egypt';
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);
        $this->assertSame('en', $plan->normalizedQuery->language);
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
        $this->assertStringNotContainsString(
            'Agricultural land types in Egypt include',
            $this->mainAnswerSection($report->answer),
        );
        $this->assertStringContainsString($originalTitle, $report->answer);
        $this->assertSame($originalTitle, $report->citations[0]->title ?? null);
        $this->assertSame(['Dr Researcher'], $report->citations[0]->authors ?? null);
        $this->assertSame('Journal of Agronomy', $report->citations[0]->journal ?? null);
        $this->assertSame('10.1000/lang-contract-land', $report->citations[0]->doi ?? null);
        $this->assertSame($originalUrl, $report->citations[0]->url ?? null);
        $this->assertStringContainsString($originalUrl, $report->answer);
    }

    public function test_english_platform_arabic_question_yields_english_prose(): void
    {
        app()->setLocale('en');
        $englishSnippet = 'Agricultural land types in Egypt include alluvial soils, sandy soils, calcareous soils, and saline soils.';
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $this->assertSame('ar', $plan->normalizedQuery->language);
        $this->assertSame('en', $plan->normalizedQuery->constraints['answer_language'] ?? null);

        $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
            $this->usableEvidence(
                'lang-en-ar',
                $englishSnippet,
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Classification of agricultural land types in Egypt',
                    'doi' => '10.1000/lang-contract-en-ar',
                    'url' => 'https://doi.org/10.1000/lang-contract-en-ar',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                ],
            ),
        ]));

        $this->assertSame('en', $report->language);
        $main = $this->mainAnswerSection($report->answer);
        $this->assertStringContainsString('Direct scientific evidence', $main);
        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', $main);
        $this->assertStringNotContainsString('Agricultural land types in Egypt include', $main);
        $this->assertSame('Classification of agricultural land types in Egypt', $report->citations[0]->title ?? null);
        $this->assertSame('https://doi.org/10.1000/lang-contract-en-ar', $report->citations[0]->url ?? null);
    }

    public function test_platform_locale_matrix_localizes_composer_prose(): void
    {
        $markers = [
            'en' => 'Direct scientific evidence',
            'ar' => 'تشير الأدلة العلمية المباشرة',
            'tr' => 'Doğrudan bilimsel kanıtlar',
            'fr' => 'Les preuves scientifiques directes',
        ];
        $snippet = 'Solanum lycopersicum seed germination is optimal near 25 °C under controlled temperature regimes.';

        foreach ($markers as $platform => $marker) {
            app()->setLocale($platform);
            $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
                'query' => 'What is the best temperature for tomato seed germination?',
            ]);
            $this->assertSame('en', $plan->normalizedQuery->language, $platform);
            $this->assertSame($platform, $plan->normalizedQuery->constraints['answer_language'] ?? null, $platform);

            $report = app(AnswerComposer::class)->compose($plan, $this->validationReport([
                $this->usableEvidence(
                    'lang-range-'.$platform,
                    $snippet,
                    ClaimEvidenceRelationship::SUPPORTED,
                    [
                        'publicationTitle' => 'Effect of temperature on tomato seed germination',
                        'doi' => '10.1000/lang-range-'.$platform,
                        'url' => 'https://doi.org/10.1000/lang-range-'.$platform,
                        'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    ],
                ),
            ]));

            $this->assertSame($platform, $report->language, $platform);
            $main = $this->mainAnswerSection($report->answer);
            $this->assertStringContainsString($marker, $main, $platform.' main prose');
            $this->assertStringContainsString('25', $main, $platform);
            $this->assertStringNotContainsString(
                'Solanum lycopersicum seed germination is optimal near',
                $main,
                $platform.' must not paste English abstract as final prose',
            );
            $this->assertSame(
                'https://doi.org/10.1000/lang-range-'.$platform,
                $report->citations[0]->url ?? null,
                $platform,
            );
        }
    }

    public function test_missing_locale_falls_back_to_english_like_set_locale_from_header(): void
    {
        app()->setLocale('de');
        $understood = app(QueryUnderstandingService::class)->understand([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $this->assertSame('ar', $understood->language);
        $this->assertSame('en', $understood->constraints['answer_language'] ?? null);
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
            evidenceSufficient: $items !== [],
            validatorsUsed: [],
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
}
