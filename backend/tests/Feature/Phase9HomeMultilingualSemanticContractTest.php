<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use Tests\TestCase;

/**
 * Phase 9 — Clean rebuild Home multilingual semantic contract.
 * Crop Page early-return path must remain untouched.
 */
class Phase9HomeMultilingualSemanticContractTest extends TestCase
{
    /**
     * @return array<string, array{id: string, lang: string, query: string}>
     */
    public static function corpusProvider(): array
    {
        $rows = [];
        foreach (self::corpus() as $id => $langs) {
            foreach ($langs as $lang => $query) {
                $rows[$id.'-'.$lang] = ['id' => $id, 'lang' => $lang, 'query' => $query];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function corpus(): array
    {
        return [
            'Q1' => [
                'ar' => 'ما درجة حرارة إنبات القمح؟',
                'en' => 'What is the germination temperature of wheat?',
                'tr' => 'Buğdayın çimlenme sıcaklığı nedir?',
                'fr' => 'Quelle est la température de germination du blé ?',
            ],
            'Q2' => [
                'ar' => 'ما احتياجات القمح من الري؟',
                'en' => 'What are the irrigation requirements of wheat?',
                'tr' => 'Buğdayın sulama gereksinimleri nelerdir?',
                'fr' => 'Quels sont les besoins en irrigation du blé ?',
            ],
            'Q3' => [
                'ar' => 'ما إنتاجية القمح؟',
                'en' => 'What is the yield of wheat?',
                'tr' => 'Buğdayın verimi nedir?',
                'fr' => 'Quel est le rendement du blé ?',
            ],
            'Q4' => [
                'ar' => 'ما نوع التربة المناسبة للقمح؟',
                'en' => 'What type of soil is suitable for wheat?',
                'tr' => 'Buğday için hangi toprak uygundur?',
                'fr' => 'Quel type de sol convient au blé ?',
            ],
            'Q5' => [
                'ar' => 'لماذا تؤثر درجة الحرارة على إنبات القمح؟',
                'en' => 'Why does temperature affect wheat germination?',
                'tr' => 'Sıcaklık buğdayın çimlenmesini neden etkiler?',
                'fr' => 'Pourquoi la température affecte-t-elle la germination du blé ?',
            ],
            'Q6' => [
                'ar' => 'ما إنتاج القمح في مصر عام 2022؟',
                'en' => 'What was wheat production in Egypt in 2022?',
                'tr' => 'Mısır\'da 2022 yılında buğday üretimi ne kadardı?',
                'fr' => 'Quelle était la production de blé en Égypte en 2022 ?',
            ],
            // Standalone maize yield (historical Q7a-AR/EN/FR). Not comparison.
            'Q7a' => [
                'ar' => 'ما إنتاجية الذرة؟',
                'en' => 'What is the yield of maize?',
                'fr' => 'Quel est le rendement du maïs ?',
            ],
            'Q8' => [
                'ar' => 'قارن بين القمح والذرة من حيث الإنتاجية.',
                'en' => 'Compare wheat and maize in terms of yield.',
                'tr' => 'Buğday ve mısırın verimini karşılaştır.',
                'fr' => 'Comparez le rendement du blé et du maïs.',
            ],
        ];
    }

    /**
     * @dataProvider corpusProvider
     */
    public function test_phase9_home_semantic_matrix(string $id, string $lang, string $query): void
    {
        $qus = app(QueryUnderstandingService::class);
        $u = $qus->understand(['query' => $query]);
        $c = $u->constraints;

        $this->assertSame($lang, $u->language, $query);
        $this->assertArrayNotHasKey('selected_crop_id', $c);

        match ($id) {
            'Q1' => $this->assertGermination($u, $query),
            'Q2' => $this->assertIrrigation($u, $query),
            'Q3' => $this->assertYield($u, $query),
            'Q4' => $this->assertSoil($u, $query),
            'Q5' => $this->assertCausal($u, $query),
            'Q6' => $this->assertEgyptYear($u, $query),
            'Q7a' => $this->assertStandaloneMaizeYield($u, $query),
            'Q8' => $this->assertComparison($u, $query),
            default => $this->fail('unknown '.$id),
        };
    }

    public function test_misir_maize_vs_egypt_context(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $maize = $qus->understand(['query' => 'Mısırın verimi nedir?']);
        $this->assertSame('corn', $maize->cropId);
        $this->assertSame('quantity', $maize->constraints['requested_property'] ?? null);
        $this->assertNotSame('Egypt', $maize->location);

        $egypt = $qus->understand(['query' => 'Mısır\'da buğday üretimi ne kadar?']);
        $this->assertSame('wheat', $egypt->cropId);
        $this->assertSame('Egypt', $egypt->location);
        $this->assertSame('quantity', $egypt->constraints['requested_property'] ?? null);
        $this->assertNotSame('corn', $egypt->cropId);
    }

    public function test_residual_summer_not_location_or_entity(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $cases = [
            'ما أفضل طريقة لري القمح في الصيف؟',
            'What is the best way to irrigate wheat in summer?',
            'Yaz aylarında buğday nasıl sulanmalıdır?',
            'Comment irriguer le blé en été ?',
        ];
        foreach ($cases as $query) {
            $u = $qus->understand(['query' => $query]);
            $this->assertSame('wheat', $u->cropId, $query);
            $this->assertNotSame('summer', mb_strtolower((string) $u->location), $query);
            $this->assertFalse(
                in_array(mb_strtolower((string) $u->location), ['summer', 'été', 'ete', 'yaz', 'الصيف'], true),
                $query.' location='.$u->location,
            );
            $this->assertSame('irrigation', $u->constraints['requested_property'] ?? null, $query);
        }
    }

    public function test_residual_inbat_not_entity_and_wheat_preserved(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $u = $qus->understand(['query' => 'ما درجة حرارة الإنبات المناسبة للقمح؟']);
        $this->assertSame('wheat', $u->cropId);
        $this->assertSame('temperature', $u->constraints['requested_property'] ?? null);
        $this->assertSame('seed_germination', $u->constraints['scientific_sense'] ?? null);
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('الإنبات'));
    }

    public function test_additional_residual_safety_cases(): void
    {
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('température de germination'));
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('çimlenme sıcaklığı'));
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('summer'));
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('soil'));

        $qus = app(QueryUnderstandingService::class);
        $u = $qus->understand(['query' => 'Quelle est la température de germination ?']);
        $this->assertNull($u->cropId);
        $this->assertNotSame('crop', $u->subject['type'] ?? null);
    }

    public function test_crop_path_does_not_use_home_comparison(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $u = $qus->understand([
            'query' => 'Buğday ve mısırdan elde edilen ürünler nelerdir?',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
        ]);
        $this->assertSame('wheat', $u->cropId);
        $this->assertFalse((bool) ($u->constraints['is_comparison'] ?? false));
        $this->assertArrayNotHasKey('comparison_entities', $u->constraints);
    }

    public function test_downstream_plan_preserves_q1_and_q6(): void
    {
        $planner = app(ResearchPlanner::class);
        $plan = $planner->plan(['query' => 'What is the germination temperature of wheat?']);
        $nq = $plan->knowledgeQueryPlan->normalizedQuery;
        $this->assertSame('wheat', $nq->cropId);
        $this->assertSame('temperature', $nq->constraints['requested_property'] ?? null);

        $plan2 = $planner->plan(['query' => 'What was wheat production in Egypt in 2022?']);
        $nq2 = $plan2->knowledgeQueryPlan->normalizedQuery;
        $this->assertSame('wheat', $nq2->cropId);
        $this->assertSame('Egypt', $nq2->location);
        $this->assertSame('2022', $nq2->constraints['year'] ?? null);
        $this->assertSame('quantity', $nq2->constraints['requested_property'] ?? null);
    }

    public function test_four_languages_share_entity_and_property_for_q1(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $props = [];
        foreach (self::corpus()['Q1'] as $lang => $query) {
            $u = $qus->understand(['query' => $query]);
            $this->assertSame('wheat', $u->cropId, $lang);
            $props[$lang] = $u->constraints['requested_property'] ?? null;
            $this->assertSame('temperature', $props[$lang], $lang);
        }
    }

    private function assertGermination(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('Triticum aestivum', $u->scientificName, $query);
        $this->assertSame('temperature', $u->constraints['requested_property'] ?? null, $query);
        $this->assertSame('seed_germination', $u->constraints['scientific_sense'] ?? null, $query);
    }

    private function assertIrrigation(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('irrigation', $u->constraints['requested_property'] ?? null, $query);
        $this->assertSame('crop_water_requirement', $u->constraints['scientific_sense'] ?? null, $query);
    }

    private function assertYield(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('quantity', $u->constraints['requested_property'] ?? null, $query);
    }

    private function assertStandaloneMaizeYield(object $u, string $query): void
    {
        $this->assertSame('corn', $u->cropId, $query);
        $this->assertSame('quantity', $u->constraints['requested_property'] ?? null, $query);
        $this->assertFalse((bool) ($u->constraints['is_comparison'] ?? false), $query);
        $this->assertArrayNotHasKey('comparison_entities', $u->constraints, $query);
        $this->assertNotSame('Egypt', $u->location, $query);
        $this->assertNotSame('wheat', $u->cropId, $query);
    }

    private function assertSoil(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('soil', $u->constraints['requested_property'] ?? null, $query);
        $this->assertNotSame('land_classification', $u->constraints['scientific_sense'] ?? null, $query);
        $this->assertNotSame('soil', $u->subject['type'] ?? null, $query);
        $loc = mb_strtolower((string) ($u->location ?? ''));
        $this->assertFalse(str_contains($loc, 'toprak') || str_contains($loc, 'soil') || str_contains($loc, 'hangi'), $query.' loc='.$u->location);
    }

    private function assertCausal(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('causes', $u->constraints['question_type'] ?? null, $query);
        $this->assertSame('seed_germination', $u->constraints['scientific_sense'] ?? null, $query);
        $factors = $u->constraints['scientific_factors'] ?? [];
        $this->assertContains('temperature', $factors, $query);
        $this->assertContains('germination', $factors, $query);
    }

    private function assertEgyptYear(object $u, string $query): void
    {
        $this->assertSame('wheat', $u->cropId, $query);
        $this->assertSame('Egypt', $u->location, $query);
        $this->assertSame('2022', $u->constraints['year'] ?? null, $query);
        $this->assertSame('quantity', $u->constraints['requested_property'] ?? null, $query);
        $this->assertNotSame('corn', $u->cropId, $query);
    }

    private function assertComparison(object $u, string $query): void
    {
        $ids = [];
        foreach ($u->constraints['comparison_entities'] ?? [] as $e) {
            if (is_array($e) && isset($e['crop_id'])) {
                $ids[] = (string) $e['crop_id'];
            }
        }
        if ($u->cropId) {
            $ids[] = (string) $u->cropId;
        }
        $ids = array_values(array_unique($ids));
        $this->assertTrue((bool) ($u->constraints['is_comparison'] ?? false), $query);
        $this->assertContains('wheat', $ids, $query.' '.json_encode($ids));
        $this->assertContains('corn', $ids, $query.' '.json_encode($ids));
        $this->assertNotContains('fodder-corn', $ids, $query);
        $this->assertSame('quantity', $u->constraints['requested_property'] ?? null, $query);
        $this->assertSame('comparison', $u->constraints['question_type'] ?? null, $query);
        $loc = mb_strtolower((string) ($u->location ?? ''));
        $this->assertFalse(str_contains($loc, 'yield') || str_contains($loc, 'terms'), $query.' loc='.$u->location);
    }
}
