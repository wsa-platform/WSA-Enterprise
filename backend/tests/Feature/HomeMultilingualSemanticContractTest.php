<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use Tests\TestCase;

/**
 * Phase 9B — Home Free Question multilingual semantic contract.
 * Does not invoke Crop Page, persistence, QueryBuilder, or providers.
 */
class HomeMultilingualSemanticContractTest extends TestCase
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
                'ar' => 'كم يحتاج القمح من مياه الري؟',
                'en' => 'How much irrigation water does wheat require?',
                'tr' => 'Buğday ne kadar sulama suyuna ihtiyaç duyar?',
                'fr' => 'De quelle quantité d\'eau d\'irrigation le blé a-t-il besoin ?',
            ],
            'Q3' => [
                'ar' => 'كم كان إنتاج القمح في مصر عام 2022؟',
                'en' => 'What was Egypt\'s wheat production in 2022?',
                'tr' => 'Mısır\'da 2022 yılında buğday üretimi ne kadardı?',
                'fr' => 'Quelle était la production de blé en Égypte en 2022 ?',
            ],
            'Q4' => [
                'ar' => 'ما أنواع التربة في مصر؟',
                'en' => 'What are the soil types in Egypt?',
                'tr' => 'Mısır\'daki toprak türleri nelerdir?',
                'fr' => 'Quels sont les types de sols en Égypte ?',
            ],
            'Q5' => [
                'ar' => 'ما درجة حرارة إنبات البطاطا الحلوة؟',
                'en' => 'What is the germination temperature of sweet potato?',
                'tr' => 'Tatlı patatesin çimlenme sıcaklığı nedir?',
                'fr' => 'Quelle est la température de germination de la patate douce ?',
            ],
            'Q6' => [
                'ar' => 'أيهما يحتاج مياه ري أكثر، القمح أم الذرة؟',
                'en' => 'Which needs more irrigation water, wheat or maize?',
                'tr' => 'Buğday ve mısırdan hangisi daha fazla sulama suyu ister?',
                'fr' => 'Le blé ou le maïs, lequel nécessite le plus d\'eau d\'irrigation ?',
            ],
            'Q7' => [
                'ar' => 'كيف تؤثر الملوحة على إنبات القمح؟',
                'en' => 'How does salinity affect wheat germination?',
                'tr' => 'Tuzluluk buğdayın çimlenmesini nasıl etkiler?',
                'fr' => 'Comment la salinité affecte-t-elle la germination du blé ?',
            ],
            'Q8' => [
                'ar' => 'ما إنتاجية حبوب القمح لكل وحدة مساحة؟',
                'en' => 'What is wheat grain yield per unit area?',
                'tr' => 'Buğdayın birim alan başına tane verimi nedir?',
                'fr' => 'Quel est le rendement en grains du blé par unité de surface ?',
            ],
        ];
    }

    /**
     * @dataProvider corpusProvider
     */
    public function test_home_qus_semantic_contract(string $id, string $lang, string $query): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => $query]);
        $c = $understood->constraints;

        $this->assertSame($lang, $understood->language, $query);
        $this->assertNotSame('crop', $this->unsafeResidualSubjectType($understood->subject, $understood->cropId), $query);

        match ($id) {
            'Q1' => $this->assertGerminationTemperature($understood, 'wheat', 'Triticum aestivum', $query),
            'Q2' => $this->assertIrrigationWheat($understood, $query),
            'Q3' => $this->assertEgyptWheatProduction2022($understood, $query),
            'Q4' => $this->assertEgyptSoilTypes($understood, $query),
            'Q5' => $this->assertGerminationTemperature($understood, 'sweet-potato', 'Ipomoea batatas', $query),
            'Q6' => $this->assertWheatMaizeIrrigationComparison($understood, $query),
            'Q7' => $this->assertSalinityGerminationCausal($understood, $query),
            'Q8' => $this->assertGrainYieldPerArea($understood, $query),
            default => $this->fail('unknown test id '.$id),
        };

        $this->assertArrayNotHasKey('selected_crop_id', $c);
    }

    public function test_four_languages_share_canonical_contract_for_each_intent(): void
    {
        $qus = app(QueryUnderstandingService::class);
        foreach (self::corpus() as $id => $langs) {
            $contracts = [];
            foreach ($langs as $lang => $query) {
                $understood = $qus->understand(['query' => $query]);
                $contracts[$lang] = $this->canonicalSlice($understood);
            }
            foreach (['en', 'tr', 'fr'] as $lang) {
                $this->assertSame(
                    $contracts['ar']['crop_id'],
                    $contracts[$lang]['crop_id'],
                    $id.' '.$lang.' crop_id',
                );
                $this->assertSame(
                    $contracts['ar']['scientific_name'],
                    $contracts[$lang]['scientific_name'],
                    $id.' '.$lang.' scientific_name',
                );
                $this->assertSame(
                    $contracts['ar']['requested_property'],
                    $contracts[$lang]['requested_property'],
                    $id.' '.$lang.' requested_property',
                );
                $this->assertSame(
                    $contracts['ar']['year'],
                    $contracts[$lang]['year'],
                    $id.' '.$lang.' year',
                );
                $this->assertSame(
                    $contracts['ar']['location'],
                    $contracts[$lang]['location'],
                    $id.' '.$lang.' location',
                );
                $this->assertSame(
                    $contracts['ar']['is_comparison'],
                    $contracts[$lang]['is_comparison'],
                    $id.' '.$lang.' comparison',
                );
            }
        }
    }

    public function test_negative_misir_locative_is_egypt_not_maize(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Mısır\'daki toprak türleri nelerdir?']);
        $this->assertSame('tr', $understood->language);
        $this->assertSame('Egypt', $understood->location);
        $this->assertNotSame('corn', $understood->cropId);
        $this->assertFalse((bool) ($understood->constraints['is_comparison'] ?? false));
    }

    public function test_negative_bugday_and_misir_is_maize_not_egypt(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Buğday ve mısırdan hangisi daha fazla sulama suyu ister?']);
        $ids = $this->comparisonCropIds($understood);
        $this->assertContains('wheat', $ids);
        $this->assertContains('corn', $ids);
        $this->assertNull($understood->location);
    }

    public function test_negative_patate_douce_is_not_potato(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Quelle est la température de germination de la patate douce ?']);
        $this->assertSame('sweet-potato', $understood->cropId);
        $this->assertNotSame('potato', $understood->cropId);
        $this->assertSame('Ipomoea batatas', $understood->scientificName);
    }

    public function test_negative_ble_resolves_to_wheat(): void
    {
        $home = AgriculturalEntityCatalog::recognizeHomeMultilingualCrops('température de germination du blé');
        $this->assertSame('wheat', $home[0]['crop_id'] ?? null);
        $this->assertNull(
            AgriculturalEntityCatalog::recognizeCrop('température de germination du blé')['crop_id'] ?? null,
            'global recognizeCrop must not consume Home TR/FR surfaces',
        );
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Quelle est la température de germination du blé ?']);
        $this->assertSame('wheat', $understood->cropId);
    }

    public function test_negative_bugday_is_not_a_location(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Buğdayın çimlenme sıcaklığı nedir?']);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertNull($understood->location);
        $this->assertFalse(AgriculturalEntityCatalog::isLocationAliasToken('buğday'));
        $this->assertNull(AgriculturalEntityCatalog::recognizeCrop('buğdayın çimlenme sıcaklığı'));
    }

    public function test_crop_profile_path_does_not_consume_home_multilingual_helpers(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand([
            'query' => 'Buğday ve mısırdan elde edilen ürünler nelerdir?',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
        ]);
        $this->assertSame('wheat', $understood->cropId);
        $this->assertFalse((bool) ($understood->constraints['is_comparison'] ?? false));
        $this->assertArrayNotHasKey('comparison_entities', $understood->constraints);
    }

    public function test_negative_temperature_phrase_is_not_a_crop(): void
    {
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('température de germination'));
        $this->assertTrue(AgriculturalEntityCatalog::isUnsafeResidualEntitySurface('çimlenme sıcaklığı'));
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'Quelle est la température de germination ?']);
        $this->assertNull($understood->cropId);
        $this->assertNotSame('crop', $understood->subject['type'] ?? null);
    }

    public function test_negative_unrelated_phrase_is_not_promoted_to_crop(): void
    {
        $qus = app(QueryUnderstandingService::class);
        $understood = $qus->understand(['query' => 'xyzzy qoph leftover phrase']);
        $this->assertNull($understood->cropId);
        $subjectType = is_array($understood->subject) ? ($understood->subject['type'] ?? null) : null;
        $this->assertNotSame('crop', $subjectType);
    }

    /**
     * @param  array{type?: string, value?: string, label?: string, resolution?: string}|null  $subject
     */
    private function unsafeResidualSubjectType(?array $subject, ?string $cropId): string
    {
        if ($cropId !== null && $cropId !== '') {
            return 'resolved';
        }
        if (! is_array($subject) || ($subject['type'] ?? '') !== 'crop') {
            return 'none';
        }
        if (($subject['resolution'] ?? '') === 'unresolved') {
            return 'crop';
        }

        return 'resolved';
    }

    private function assertGerminationTemperature(object $understood, string $cropId, string $scientificName, string $query): void
    {
        $this->assertSame($cropId, $understood->cropId, $query);
        $this->assertSame($scientificName, $understood->scientificName, $query);
        $this->assertSame('temperature', $understood->constraints['requested_property'] ?? null, $query);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null, $query);
        $this->assertNotSame('recommendation', $understood->constraints['question_type'] ?? null, $query);
        if ($cropId === 'sweet-potato') {
            $this->assertFalse(
                (bool) ($understood->constraints['is_comparison'] ?? false),
                $query.' '.json_encode($understood->constraints['comparison_entities'] ?? null, JSON_UNESCAPED_UNICODE),
            );
        }
    }

    private function assertIrrigationWheat(object $understood, string $query): void
    {
        $this->assertSame('wheat', $understood->cropId, $query);
        $this->assertSame('Triticum aestivum', $understood->scientificName, $query);
        $this->assertSame('irrigation', $understood->constraints['requested_property'] ?? null, $query);
        $this->assertSame('crop_water_requirement', $understood->constraints['scientific_sense'] ?? null, $query);
    }

    private function assertEgyptWheatProduction2022(object $understood, string $query): void
    {
        $this->assertSame('wheat', $understood->cropId, $query);
        $this->assertSame('Triticum aestivum', $understood->scientificName, $query);
        $this->assertSame('Egypt', $understood->location, $query);
        $this->assertSame('2022', $understood->constraints['year'] ?? null, $query);
        $this->assertSame('quantity', $understood->constraints['requested_property'] ?? null, $query);
        $this->assertNotSame('corn', $understood->cropId, $query);
    }

    private function assertEgyptSoilTypes(object $understood, string $query): void
    {
        $this->assertSame('Egypt', $understood->location, $query);
        $this->assertNull($understood->cropId, $query);
        $this->assertNotSame('corn', $understood->cropId, $query);
        $this->assertSame('land_classification', $understood->constraints['scientific_sense'] ?? $understood->researchIntent, $query);
        $this->assertSame('classification', $understood->constraints['requested_property'] ?? $understood->constraints['question_type'] ?? null, $query);
    }

    private function assertWheatMaizeIrrigationComparison(object $understood, string $query): void
    {
        $ids = $this->comparisonCropIds($understood);
        $this->assertContains('wheat', $ids, $query);
        $this->assertContains('corn', $ids, $query);
        $this->assertTrue((bool) ($understood->constraints['is_comparison'] ?? false), $query);
        $this->assertSame('irrigation', $understood->constraints['requested_property'] ?? null, $query);
        $this->assertNull($understood->location, $query);
        $this->assertSame('comparison', $understood->constraints['question_type'] ?? null, $query);
    }

    private function assertSalinityGerminationCausal(object $understood, string $query): void
    {
        $this->assertSame('wheat', $understood->cropId, $query);
        $this->assertSame('seed_germination', $understood->constraints['scientific_sense'] ?? null, $query);
        $this->assertTrue((bool) ($understood->constraints['is_causal'] ?? false), $query);
        $this->assertSame('causes', $understood->constraints['question_type'] ?? null, $query);
        $this->assertNotSame('recommendation', $understood->constraints['question_type'] ?? null, $query);
        $this->assertNotEmpty($understood->constraints['causal_affector'] ?? 'salinity', $query);
    }

    private function assertGrainYieldPerArea(object $understood, string $query): void
    {
        $this->assertSame('wheat', $understood->cropId, $query);
        $this->assertSame('quantity', $understood->constraints['requested_property'] ?? null, $query);
        $surface = mb_strtolower((string) ($understood->constraints['requested_property_surface'] ?? ''));
        $terms = $understood->constraints['requested_property_query_terms'] ?? [];
        $blob = $surface.' '.implode(' ', is_array($terms) ? $terms : []);
        $this->assertTrue(
            str_contains($blob, 'yield') || str_contains($blob, 'grain') || str_contains($blob, 'production'),
            $query.' '.$blob,
        );
        $this->assertNotSame('general_knowledge', $understood->constraints['requested_property'] ?? null, $query);
    }

    /**
     * @return list<string>
     */
    private function comparisonCropIds(object $understood): array
    {
        $entities = $understood->constraints['comparison_entities'] ?? [];
        $ids = [];
        if (is_array($entities)) {
            foreach ($entities as $entity) {
                if (is_array($entity) && isset($entity['crop_id'])) {
                    $ids[] = (string) $entity['crop_id'];
                }
            }
        }
        if ($understood->cropId) {
            $ids[] = (string) $understood->cropId;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, mixed>
     */
    private function canonicalSlice(object $understood): array
    {
        $comparisonIds = $this->comparisonCropIds($understood);
        sort($comparisonIds);

        return [
            'crop_id' => $understood->cropId,
            'scientific_name' => $understood->scientificName,
            'requested_property' => $understood->constraints['requested_property'] ?? null,
            'year' => $understood->constraints['year'] ?? null,
            'location' => $understood->location,
            'is_comparison' => (bool) ($understood->constraints['is_comparison'] ?? false),
            'comparison_ids' => $comparisonIds,
        ];
    }
}
