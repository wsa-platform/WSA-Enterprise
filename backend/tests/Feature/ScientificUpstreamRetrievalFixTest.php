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

        $this->assertSame(1, $openAlexCalls, 'OpenAlex must short-circuit after first 429');
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
}
