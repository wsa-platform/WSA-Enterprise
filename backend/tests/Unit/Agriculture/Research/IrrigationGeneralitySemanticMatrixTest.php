<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use Tests\TestCase;

/**
 * Mission: irrigation generality remediation (GAP A–D).
 * Positive/negative semantic matrix across EN/AR/TR/FR — contract level.
 */
class IrrigationGeneralitySemanticMatrixTest extends TestCase
{
    /**
     * @return list<array{
     *   id: string,
     *   lang: string,
     *   family: string,
     *   query: string,
     *   expect_cwr: bool,
     *   expect_helper: bool,
     *   expect_intent?: string|list<string>,
     *   expect_sense?: string|list<string>,
     *   forbid_sense?: list<string>,
     *   crop?: string|null,
     *   water_role?: string|null,
     *   retrieval_any?: list<string>,
     *   retrieval_forbid?: list<string>,
     * }>
     */
    public static function matrixCases(): array
    {
        $cases = [
            // ---- Positive: explicit irrigation acts / CWR ----
            [
                'id' => 'P01',
                'lang' => 'en',
                'family' => 'deficit_intervention',
                'query' => 'What are the effects of deficit irrigation on wheat yield?',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'wheat',
                'retrieval_any' => ['deficit irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P02',
                'lang' => 'ar',
                'family' => 'deficit_intervention',
                'query' => 'ما تأثير الري الناقص على إنتاجية القمح؟',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'wheat',
                'retrieval_any' => ['deficit irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P03',
                'lang' => 'ar',
                'family' => 'irrigation_quantity',
                'query' => 'ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'pomegranate',
                'retrieval_any' => ['irrigation quantity', 'crop water requirement', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P04',
                'lang' => 'en',
                'family' => 'irrigation_scheduling',
                'query' => 'Irrigation scheduling for maize.',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => ['maize', 'corn'],
                'retrieval_any' => ['irrigation scheduling', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P05',
                'lang' => 'tr',
                'family' => 'crop_water_requirement',
                'query' => 'Buğday sulama suyu ihtiyacı.',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'wheat',
                'retrieval_any' => ['crop water requirement', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P06',
                'lang' => 'fr',
                'family' => 'crop_water_requirement',
                'query' => 'Besoin en eau du blé.',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'wheat',
                'retrieval_any' => ['crop water requirement', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P07',
                'lang' => 'en',
                'family' => 'deficit_intervention',
                'query' => 'How does deficit irrigation affect tomato fruit quality?',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'tomato',
                'retrieval_any' => ['deficit irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P08',
                'lang' => 'ar',
                'family' => 'crop_water_requirement',
                'query' => 'ما الاحتياجات المائية لأشجار الزيتون؟',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'olive',
                'retrieval_any' => ['crop water requirement', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P09',
                'lang' => 'en',
                'family' => 'irrigation_quantity',
                'query' => 'What irrigation quantity is required for greenhouse tomatoes?',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => 'tomato',
                'retrieval_any' => ['irrigation quantity', 'irrigation', 'crop water requirement'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P10',
                'lang' => 'ar',
                'family' => 'irrigation_scheduling',
                'query' => 'كيف تؤثر جدولة الري على نمو الذرة؟',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_intent' => 'irrigation',
                'expect_sense' => 'crop_water_requirement',
                'crop' => ['maize', 'corn'],
                'retrieval_any' => ['irrigation scheduling', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P11',
                'lang' => 'tr',
                'family' => 'deficit_intervention',
                'query' => 'Eksik sulama buğday verimini nasıl etkiler?',
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_sense' => 'crop_water_requirement',
                'retrieval_any' => ['deficit irrigation', 'irrigation'],
                'retrieval_forbid' => [],
            ],
            [
                'id' => 'P12',
                'lang' => 'fr',
                'family' => 'irrigation_scheduling',
                'query' => "Programmation de l'irrigation pour le maïs.",
                'expect_cwr' => true,
                'expect_helper' => true,
                'expect_sense' => 'crop_water_requirement',
                'retrieval_any' => ['irrigation scheduling', 'irrigation'],
                'retrieval_forbid' => [],
            ],

            // ---- Negative: incidental / constraint / other domains ----
            [
                'id' => 'N01',
                'lang' => 'en',
                'family' => 'environmental_constraint',
                'query' => 'What crops can be grown where water is scarce?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_intent' => 'cultivation',
                'forbid_sense' => ['crop_water_requirement'],
                'water_role' => ['constraint', null],
            ],
            [
                'id' => 'N02',
                'lang' => 'en',
                'family' => 'fertilizer_requirement',
                'query' => 'What fertilizer quantity do tomato plants need?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N03',
                'lang' => 'en',
                'family' => 'seed_requirement',
                'query' => 'What seed quantity is required for wheat?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N04',
                'lang' => 'ar',
                'family' => 'salinity_physiology',
                'query' => 'ما تأثير الملوحة على القمح؟',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'salinity_physiology',
                'crop' => 'wheat',
            ],
            [
                'id' => 'N05',
                'lang' => 'en',
                'family' => 'disease_incidental_irrigation',
                'query' => 'Does irrigation increase wheat rust severity?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'disease',
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N06',
                'lang' => 'en',
                'family' => 'extension_adoption',
                'query' => 'Adoption of irrigation technology among farmers.',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'agricultural_extension',
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N07',
                'lang' => 'en',
                'family' => 'varieties_suitability',
                'query' => 'Which wheat varieties suit arid regions?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'varieties',
                'forbid_sense' => ['crop_water_requirement'],
                'crop' => 'wheat',
            ],
            [
                'id' => 'N08',
                'lang' => 'en',
                'family' => 'irrigation_systems',
                'query' => 'Types of irrigation systems used in horticulture.',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N09',
                'lang' => 'ar',
                'family' => 'organic_soil',
                'query' => 'ما أثر الزراعة العضوية على خصوبة التربة؟',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N10',
                'lang' => 'en',
                'family' => 'irrigation_systems',
                'query' => 'Wheat under drip irrigation systems for improved market quality.',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N11',
                'lang' => 'en',
                'family' => 'irrigation_systems',
                'query' => 'Irrigation technology for wheat: a comparison of available systems.',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N12',
                'lang' => 'en',
                'family' => 'environmental_constraint',
                'query' => 'Agricultural water scarcity and farmer adaptation.',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N13',
                'lang' => 'en',
                'family' => 'disease_incidental_irrigation',
                'query' => 'What are the main diseases affecting wheat under arid conditions?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'disease',
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N14',
                'lang' => 'en',
                'family' => 'salinity_physiology',
                'query' => 'How does soil salinity affect tomato growth?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_sense' => 'salinity_physiology',
            ],
            [
                'id' => 'N15',
                'lang' => 'en',
                'family' => 'environmental_constraint',
                'query' => 'Which crops are suitable for low-water environments?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'expect_intent' => 'cultivation',
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N16',
                'lang' => 'tr',
                'family' => 'environmental_constraint',
                'query' => 'Su kıtlığı olan bölgelerde hangi ürünler yetiştirilebilir?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N17',
                'lang' => 'fr',
                'family' => 'environmental_constraint',
                'query' => 'Quelles cultures conviennent aux régions arides?',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
            [
                'id' => 'N18',
                'lang' => 'ar',
                'family' => 'varieties_suitability',
                'query' => 'ما أصناف القمح المناسبة للمناطق الجافة؟',
                'expect_cwr' => false,
                'expect_helper' => false,
                'forbid_sense' => ['crop_water_requirement'],
            ],
        ];

        // PHPUnit unpacks associative datasets as named parameters — wrap each case.
        return array_map(static fn (array $case): array => [$case], $cases);
    }

    public function test_helper_unit_signals_across_languages(): void
    {
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'What are the effects of deficit irrigation on wheat yield?'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'ما تأثير الري الناقص على إنتاجية القمح؟'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'Buğday sulama suyu ihtiyacı.'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'Besoin en eau du blé.'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'Eksik sulama buğday verimini nasıl etkiler?'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            "Programmation de l'irrigation pour le maïs."
        ));

        $this->assertFalse(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'Does irrigation increase wheat rust severity?'
        ));
        $this->assertFalse(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'Types of irrigation systems used in horticulture.'
        ));
        $this->assertFalse(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'What crops can be grown where water is scarce?'
        ));
        $this->assertFalse(AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement(
            'What fertilizer quantity do tomato plants need?'
        ));

        $this->assertTrue(AgriculturalEntityCatalog::asksIrrigationSystemsOrTechnologyInventory(
            'Types of irrigation systems used in horticulture.'
        ));
        $this->assertTrue(AgriculturalEntityCatalog::hasPlantDiseaseOrPathogenSignal(
            'Does irrigation increase wheat rust severity?'
        ));
    }

    public function test_english_retrieval_concept_preserves_semantic_surface(): void
    {
        $this->assertSame(
            'deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('الري الناقص')
        );
        $this->assertSame(
            'regulated deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('الري المنظم')
        );
        $this->assertSame(
            'irrigation quantity',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('كمية الري')
        );
        $this->assertSame(
            'crop water requirement',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('الاحتياج المائي للمحصول')
        );
        $this->assertSame(
            'irrigation scheduling',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('جدولة الري')
        );
        $this->assertSame(
            'irrigation water-use efficiency',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('كفاءة استخدام مياه الري')
        );
        $this->assertSame(
            'effects of irrigation on yield',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('تأثير الري على الإنتاجية')
        );
        $this->assertSame(
            'effects of water availability on growth',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('تأثير المياه على النمو')
        );
        $this->assertNotSame(
            'deficit irrigation',
            AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept('كمية الري المناسبة للقمح')
        );
    }

    /**
     * @dataProvider matrixCases
     */
    public function test_semantic_matrix_case(array $case): void
    {
        $query = $case['query'];
        $helper = AgriculturalEntityCatalog::asksExplicitIrrigationOrWaterRequirement($query);
        $this->assertSame(
            $case['expect_helper'],
            $helper,
            $case['id'].' helper mismatch for: '.$query
        );

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => $query,
            'force_execute' => true,
        ]);
        $nq = $plan->normalizedQuery;
        $sense = (string) ($nq->constraints['scientific_sense'] ?? '');
        $intent = (string) $nq->researchIntent;
        $roles = is_array($nq->constraints['scientific_factor_roles'] ?? null)
            ? $nq->constraints['scientific_factor_roles']
            : [];

        if (($case['expect_cwr'] ?? false) === true) {
            $this->assertSame('crop_water_requirement', $sense, $case['id'].' sense: '.$query);
        }
        if (isset($case['forbid_sense'])) {
            foreach ($case['forbid_sense'] as $forbidden) {
                $this->assertNotSame($forbidden, $sense, $case['id'].' forbid sense '.$forbidden.': '.$query);
            }
        }
        if (isset($case['expect_sense'])) {
            $expected = $case['expect_sense'];
            if (is_array($expected)) {
                $this->assertTrue(in_array($sense, $expected, true), $case['id']." sense={$sense}: ".$query);
            } else {
                $this->assertSame($expected, $sense, $case['id'].' sense: '.$query);
            }
        }
        if (isset($case['expect_intent'])) {
            $expectedIntent = $case['expect_intent'];
            if (is_array($expectedIntent)) {
                $this->assertTrue(in_array($intent, $expectedIntent, true), $case['id']." intent={$intent}: ".$query);
            } else {
                $this->assertSame($expectedIntent, $intent, $case['id'].' intent: '.$query);
            }
        }
        if (array_key_exists('crop', $case) && $case['crop'] !== null) {
            $expectedCrop = $case['crop'];
            if (is_array($expectedCrop)) {
                $this->assertTrue(
                    in_array($nq->cropId, $expectedCrop, true),
                    $case['id'].' crop='.(string) $nq->cropId.': '.$query
                );
            } else {
                $this->assertSame($expectedCrop, $nq->cropId, $case['id'].' crop: '.$query);
            }
        }
        if (isset($case['water_role'])) {
            $actualRole = $roles['water'] ?? null;
            $expectedRole = $case['water_role'];
            if (is_array($expectedRole)) {
                $this->assertTrue(
                    in_array($actualRole, $expectedRole, true),
                    $case['id'].' water role='.(string) $actualRole.': '.$query
                );
            } else {
                $this->assertSame($expectedRole, $actualRole, $case['id'].' water role: '.$query);
            }
            // Environmental scarcity must not be treated as a requested water attribute.
            $this->assertNotSame('requested', $actualRole, $case['id'].' water must not be requested: '.$query);
        }

        $variants = app(ScientificSearchQueryBuilder::class)->buildVariantsFromPlan($plan);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertSame(0, preg_match('/\p{Arabic}/u', $joined), $case['id'].' Arabic leakage: '.$joined);

        if (! empty($case['retrieval_any'])) {
            $hit = false;
            foreach ($case['retrieval_any'] as $needle) {
                if (str_contains($joined, mb_strtolower($needle))) {
                    $hit = true;
                    break;
                }
            }
            $this->assertTrue($hit, $case['id'].' retrieval missing any of '
                .implode('|', $case['retrieval_any']).' in '.$joined);
        }
        foreach ($case['retrieval_forbid'] ?? [] as $needle) {
            $this->assertFalse(
                str_contains($joined, mb_strtolower($needle)),
                $case['id'].' retrieval forbid '.$needle.': '.$joined
            );
        }

        // Quantity / requirement positives must not collapse exclusively to deficit irrigation.
        if (($case['family'] ?? '') === 'irrigation_quantity'
            || (($case['family'] ?? '') === 'crop_water_requirement' && ($case['lang'] ?? '') === 'ar' && str_contains($query, 'كمية'))
        ) {
            if (str_contains($joined, 'deficit irrigation')
                && ! str_contains($query, 'ناقص')
                && ! str_contains(mb_strtolower($query), 'deficit')
            ) {
                $this->assertTrue(
                    str_contains($joined, 'irrigation quantity')
                    || str_contains($joined, 'crop water requirement')
                    || str_contains($joined, 'water requirement'),
                    $case['id'].' quantity must retain requirement terms: '.$joined
                );
            }
        }
    }

    public function test_matrix_covers_required_languages_and_polarity(): void
    {
        $langs = [];
        $pos = 0;
        $neg = 0;
        foreach (self::matrixCases() as $row) {
            $case = $row[0];
            $langs[$case['lang']] = true;
            if ($case['expect_cwr']) {
                $pos++;
            } else {
                $neg++;
            }
        }
        $this->assertGreaterThanOrEqual(10, $pos);
        $this->assertGreaterThanOrEqual(15, $neg);
        foreach (['en', 'ar', 'tr', 'fr'] as $lang) {
            $this->assertArrayHasKey($lang, $langs, 'missing language '.$lang);
        }
    }
}
