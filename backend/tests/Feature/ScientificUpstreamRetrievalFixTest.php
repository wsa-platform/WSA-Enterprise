<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\AgriculturalScientificSearchService;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Upstream retrieval: OpenAlex 429 short-circuit + sense-driven query diversification.
 */
class ScientificUpstreamRetrievalFixTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function crossRefWork(string $title, string $abstract, string $doi): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => 'University of Agriculture',
            'container-title' => ['Journal of Agricultural Science'],
            'issued' => ['date-parts' => [[2022]]],
            'author' => [['given' => 'A', 'family' => 'Researcher']],
        ];
    }

    /** A — OpenAlex 429 + Crossref success must not zero the research. */
    public function test_a_openalex_429_crossref_success_not_zero_result(): void
    {
        $openAlexCalls = 0;
        $crossrefCalls = 0;

        Http::fake([
            'api.openalex.org/works*' => function () use (&$openAlexCalls) {
                $openAlexCalls++;

                return Http::response(['results' => []], 429);
            },
            'api.semanticscholar.org/graph/v1/paper/search*' => Http::response(['data' => []], 200),
            'api.crossref.org/works*' => function () use (&$crossrefCalls) {
                $crossrefCalls++;

                return Http::response(['message' => ['items' => [
                    $this->crossRefWork(
                        'Optimal cultivation conditions for Zingiber officinale ginger production',
                        'Field cultivation and rhizome production of ginger under temperature and irrigation regimes in agriculture.',
                        '10.1000/ginger-cult-'.$crossrefCalls,
                    ),
                ]]], 200);
            },
        ]);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertGreaterThanOrEqual(2, count($variants));

        $report = app(AgriculturalScientificSearchService::class)->search($plan);

        $this->assertSame(3, $openAlexCalls, 'OpenAlex must retry up to 3 attempts on 429 then stop');
        $this->assertSame(count($variants), $crossrefCalls, 'Crossref must still run for all variants');
        $this->assertContains('openalex', $report->failedSources);
        $this->assertContains('crossref', $report->successfulSources);
        $this->assertNotSame('all_sources_failed', $report->status);
        $this->assertGreaterThan(0, count($report->results));
        $this->assertGreaterThan(
            0,
            count($report->deduplicatedResults),
            'Relevant Crossref hits must reach ranking after OpenAlex 429',
        );
    }

    /** B — ginger common-name cultivation variant. */
    public function test_b_ginger_common_name_cultivation_variant(): void
    {
        $joined = $this->variantsJoined('ما أفضل الظروف لزراعة الزنجبيل؟');
        $this->assertStringContainsString('ginger', mb_strtolower($joined));
        $this->assertTrue(
            str_contains($joined, 'cultivation') || str_contains($joined, 'production'),
        );
    }

    /** C — Zingiber scientific / genus cultivation coverage. */
    public function test_c_zingiber_scientific_and_genus_variants(): void
    {
        $joined = $this->variantsJoined('ما أفضل الظروف لزراعة الزنجبيل؟');
        $this->assertStringContainsString('Zingiber officinale', $joined);
        $this->assertTrue(
            str_contains($joined, 'Zingiber cultivation')
            || str_contains($joined, 'Zingiber officinale cultivation'),
        );
    }

    /** D — cultivation term present for plant_growth / cultivation intent. */
    public function test_d_cultivation_variant_generated(): void
    {
        $joined = $this->variantsJoined('ما أفضل الظروف لزراعة الزنجبيل؟');
        $this->assertStringContainsString('cultivation', $joined);
    }

    /** E — production term present for cultivation / plant_growth. */
    public function test_e_production_variant_generated(): void
    {
        $joined = $this->variantsJoined('ما أفضل الظروف لزراعة الزنجبيل؟');
        $this->assertStringContainsString('production', $joined);
    }

    /** F — rhizome variants for rhizome questions. */
    public function test_f_rhizome_variants_generated(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أهمية الريزوم في نبات الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = implode(' | ', $variants);

        $this->assertStringContainsString('rhizome', mb_strtolower($joined));
        $this->assertTrue(
            str_contains(mb_strtolower($joined), 'ginger rhizome')
            || str_contains(mb_strtolower($joined), 'zingiber officinale rhizome'),
        );
        $this->assertMatchesRegularExpression('/rhizome/i', $variants[0] ?? '');
        $this->assertStringNotContainsString('irrigation', mb_strtolower($joined));
    }

    /** G — Arabic ginger cultivation query diversifies without Arabic tokens / duplicates. */
    public function test_g_arabic_ginger_cultivation_query_diversified(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أفضل الظروف لزراعة الزنجبيل؟',
        ]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);

        $this->assertLessThanOrEqual(5, count($variants));
        $this->assertSame(count($variants), count(array_unique($variants)));
        foreach ($variants as $variant) {
            $this->assertStringNotContainsString('الزنجبيل', $variant);
            $this->assertMatchesRegularExpression('/[A-Za-z]/', $variant);
        }

        $joined = implode(' | ', $variants);
        $this->assertStringContainsString('Zingiber', $joined);
        $this->assertStringContainsString('ginger', mb_strtolower($joined));
        $this->assertTrue(
            str_contains($joined, 'cultivation') || str_contains($joined, 'production'),
        );
    }

    private function variantsJoined(string $query): string
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $this->assertNotEmpty($variants);
        $this->assertSame(count($variants), count(array_unique($variants)));

        return implode(' | ', $variants);
    }

    /** TEST1 — Egypt land types retain land classification + Egypt (not bare cultivation). */
    public function test_stage3_egypt_land_types_retain_classification_and_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في مصر؟',
        ]);
        $this->assertSame('land_classification', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue(
            str_contains($joined, 'land classification')
            || str_contains($joined, 'land types')
            || str_contains($joined, 'soil classification')
            || str_contains($joined, 'agricultural land'),
            'Variants must retain land classification terms: '.$joined,
        );
        $this->assertStringContainsString('egypt', $joined);
        foreach ($variants as $variant) {
            $this->assertStringContainsString('Egypt', $variant);
            $this->assertNotSame('cultivation field_crops agriculture', $variant);
            $this->assertFalse(
                (bool) preg_match('/^cultivation\s+field_crops\s+agriculture(\s+Egypt)?$/i', $variant),
                'Must not emit bare cultivation/domain agriculture variant: '.$variant,
            );
        }
    }

    /** TEST1b — Unhamza Egypt land types (انواع الاراضي) same sense + geo as hamza form. */
    public function test_stage3_egypt_land_types_unhamza_retain_classification_and_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي انواع الاراضي الزراعية في مصر',
        ]);
        $this->assertSame('land_classification', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertSame('Egypt', $plan->normalizedQuery->constraints['location'] ?? null);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue(
            str_contains($joined, 'land classification')
            || str_contains($joined, 'land types')
            || str_contains($joined, 'soil classification')
            || str_contains($joined, 'agricultural land'),
            'Variants must retain land classification terms: '.$joined,
        );
        $this->assertStringContainsString('egypt', $joined);
        $this->assertFalse(
            str_contains($joined, 'growth physiology'),
            'Must not primary plant_growth variants: '.$joined,
        );
        foreach ($variants as $variant) {
            $this->assertStringContainsString('Egypt', $variant);
            $this->assertFalse(
                (bool) preg_match('/^cultivation\s+field_crops\s+agriculture(\s+Egypt)?$/i', $variant),
                'Must not emit bare cultivation/domain agriculture variant: '.$variant,
            );
            $this->assertFalse(
                (bool) preg_match('/\b(greenhouse|polyhouse)\b/i', $variant),
                'Must not leak greenhouse/polyhouse: '.$variant,
            );
        }
    }

    /** TEST2 — Saudi Arabia land types retain geography. */
    public function test_stage3_saudi_land_types_retain_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في السعودية؟',
        ]);
        $this->assertSame('Saudi Arabia', $plan->normalizedQuery->location);
        $this->assertSame('land_classification', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('saudi arabia', $joined);
        $this->assertTrue(
            str_contains($joined, 'land') || str_contains($joined, 'soil'),
        );
    }

    /** TEST3 — Turkey land types retain geography. */
    public function test_stage3_turkey_land_types_retain_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي أنواع الأراضي الزراعية في تركيا؟',
        ]);
        $this->assertSame('Turkey', $plan->normalizedQuery->location);
        $this->assertSame('land_classification', $plan->normalizedQuery->constraints['scientific_sense'] ?? null);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertStringContainsString('turkey', $joined);
        $this->assertTrue(
            str_contains($joined, 'land') || str_contains($joined, 'soil'),
        );
    }

    /** TEST4 — Tomato suitable land Egypt retains entity + land suitability + Egypt. */
    public function test_stage3_tomato_land_suitability_egypt_retains_entity_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما هي الأراضي المناسبة لزراعة الطماطم في مصر؟',
        ]);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertSame('tomato', $plan->normalizedQuery->cropId);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue(
            str_contains($joined, 'tomato') || str_contains($joined, 'solanum lycopersicum'),
            'Variants must retain tomato entity: '.$joined,
        );
        $this->assertTrue(
            str_contains($joined, 'land suitability') || str_contains($joined, 'soil suitability'),
            'Variants must include land/soil suitability: '.$joined,
        );
        $this->assertStringContainsString('egypt', $joined);
    }

    /** TEST5 — Fish/aquaculture Egypt retains aquaculture + Egypt. */
    public function test_stage3_fish_egypt_retains_aquaculture_and_geo(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ما أنواع الأسماك المستزرعة في مصر؟',
        ]);
        $this->assertSame('Egypt', $plan->normalizedQuery->location);
        $this->assertSame('aquaculture', $plan->researchIntent);

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));

        $this->assertTrue(
            str_contains($joined, 'aquaculture') || str_contains($joined, 'fish'),
            'Variants must retain fish/aquaculture: '.$joined,
        );
        $this->assertStringContainsString('egypt', $joined);
    }
}
