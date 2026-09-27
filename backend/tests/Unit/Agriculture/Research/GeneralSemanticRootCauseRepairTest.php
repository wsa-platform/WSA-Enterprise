<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ScientificQuestionSemantics;
use PHPUnit\Framework\TestCase;

/**
 * General semantic-rule probes for RC1/RC2/RC3/RC5.
 * Fixtures are unseen equivalents, not production special cases.
 */
class GeneralSemanticRootCauseRepairTest extends TestCase
{
    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function namedEntityPlusProcessProvider(): array
    {
        return [
            'en_oats_planting' => ['What are the best conditions for planting oats?', 'oats', true],
            'en_barley_growing' => ['How should barley be grown in cool seasons?', 'barley', true],
            'en_millet_cultivating' => ['Which practices help when cultivating millet?', 'millet', true],
            'fr_oats_cultiver' => ['Quelles sont les meilleures pratiques pour cultiver l\'avoine ?', 'oats', true],
            'tr_lentil_ekim' => ['Mercimek ekimi nasıl yapılır?', 'lentil', true],
            'ar_chickpea_planting' => ['ما أفضل ظروف زراعة الحمص؟', 'chickpea', true],
        ];
    }

    /**
     * @dataProvider namedEntityPlusProcessProvider
     */
    public function test_rc1_named_entity_keeps_recognized_process(
        string $query,
        string $expectedCropId,
        bool $expectsProcess,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame($expectedCropId, $understood->cropId, $query);
        $this->assertSame($expectedCropId, $csq->entity->canonicalId ?? $csq->entity->normalized, $query);
        if ($expectsProcess) {
            $this->assertNotNull($csq->process->surface, $query.' expected PROCESS');
            $this->assertNotSame(
                mb_strtolower((string) $csq->entity->surface),
                mb_strtolower((string) $csq->process->surface),
                $query.' process collapsed into entity',
            );
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function definitionDoesNotInventProcessProvider(): array
    {
        return [
            'en_oats' => ['What is oats?'],
            'en_millet' => ['What is millet?'],
            'fr_barley' => ["Qu'est-ce que l'orge ?"],
            'tr_lentil' => ['Mercimek nedir?'],
            'ar_chickpea' => ['ما هو الحمص؟'],
        ];
    }

    /**
     * @dataProvider definitionDoesNotInventProcessProvider
     */
    public function test_rc1_definition_does_not_acquire_process(string $query): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertNull($csq->process->surface, $query);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function irrigationTokenBoundaryProvider(): array
    {
        return [
            'true_ar_irrigation' => ['ما كمية الري التي يحتاجها الشوفان؟', true],
            'true_en_irrigation' => ['How much irrigation water do oats need?', true],
            'true_tr_irrigation' => ['Yulaf sulama suyu ihtiyacı nedir?', true],
            'false_history' => ['ما أهمية التاريخ الزراعي للشعير؟', false],
            'false_america' => ['ما دور أمريكا في زراعة الدخن؟', false],
            'false_village' => ['ما دور القرية في إنتاج العدس؟', false],
            'false_freedom' => ['ما علاقة الحرية بالسياسة الزراعية؟', false],
        ];
    }

    /**
     * @dataProvider irrigationTokenBoundaryProvider
     */
    public function test_rc2_irrigation_uses_token_boundaries(string $query, bool $expectsIrrigation): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $property = (string) ($understood->constraints['requested_property'] ?? '');
        if ($expectsIrrigation) {
            $this->assertSame('irrigation', $property, $query);
        } else {
            $this->assertNotSame('irrigation', $property, $query);
        }
    }

    public function test_rc2_short_root_does_not_match_inside_unrelated_token(): void
    {
        $this->assertFalse(AgriculturalEntityCatalog::containsTerm('ريزوم', 'ري'));
        $this->assertFalse(AgriculturalEntityCatalog::containsTerm('التاريخ الزراعي', 'ري'));
        $this->assertFalse(AgriculturalEntityCatalog::containsTerm('أمريكا', 'ري'));
        $this->assertFalse(AgriculturalEntityCatalog::containsTerm('قرية', 'ري'));
        $this->assertTrue(AgriculturalEntityCatalog::containsTerm('كمية الري للشوفان', 'ري'));
        $this->assertTrue(AgriculturalEntityCatalog::containsTerm('الري بالتنقيط', 'الري'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function processParticipleMustNotBecomeEntityProvider(): array
    {
        return [
            'ar_participle' => ['ما أنواع الطيور المستزرعة في المرتفعات؟'],
            'en_farmed' => ['What farmed shrimp species are common in coastal ponds?'],
            'en_cultured' => ['Which cultured algae strains are used in feed?'],
            'fr_elevees' => ['Quelles espèces élevées en bassin sont courantes ?'],
            'tr_yetistirilen' => ['Havuzlarda yetiştirilen türler nelerdir?'],
        ];
    }

    /**
     * @dataProvider processParticipleMustNotBecomeEntityProvider
     */
    public function test_rc3_process_participle_is_not_entity(string $query): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $surface = mb_strtolower((string) ($csq->entity->surface ?? ''));
        foreach (['المستزرعة', 'farmed', 'cultured', 'élevées', 'elevees', 'yetiştirilen', 'yetistirilen'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $surface, $query);
        }
        $this->assertFalse(
            AgriculturalEntityCatalog::isGenericScientificProcessToken((string) ($csq->entity->surface ?? '')),
            $query.' entity is a process token',
        );
    }

    public function test_rc3_true_named_entity_still_resolves(): void
    {
        $understood = $this->qus->understand(['query' => 'What is flax?']);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('flax', $understood->cropId);
        $this->assertNotNull($csq->entity->surface);
    }

    public function test_rc3_unknown_residual_is_not_promoted(): void
    {
        $understood = $this->qus->understand(['query' => 'What are the flibbertigibbet conditions in highland ponds?']);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq);
        $surface = mb_strtolower((string) ($csq->entity->surface ?? ''));
        $this->assertStringNotContainsString('flibbertigibbet', $surface);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function landSuitabilityVersusMeasurableRangeProvider(): array
    {
        return [
            'en_oats_land' => ['Which land is suitable for oats?', false],
            'en_millet_soil' => ['What soil is suitable for millet?', false],
            'ar_chickpea_land' => ['ما الأراضي المناسبة للحمص؟', false],
            'fr_sorghum_land' => ['Quelles terres conviennent au sorgho ?', false],
            'tr_barley_soil' => ['Arpa için uygun toprak nedir?', false],
            'en_oats_temperature' => ['What is the suitable temperature for oats?', true],
            'en_barley_ph' => ['What is the suitable pH for barley?', true],
            'en_quinoa_salinity' => ['What is the suitable salinity for millet?', true],
            'en_flax_moisture' => ['What is the suitable moisture for flax?', true],
            'ar_oats_temperature' => ['ما درجة الحرارة المناسبة للشوفان؟', true],
        ];
    }

    /**
     * @dataProvider landSuitabilityVersusMeasurableRangeProvider
     */
    public function test_rc5_suitability_frame_precedes_bare_range_cue(
        string $query,
        bool $expectsMeasurableRange,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $type = (string) ($understood->constraints['question_type'] ?? '');
        if ($expectsMeasurableRange) {
            $this->assertTrue(
                in_array($type, ['range', 'quantity'], true)
                || ScientificQuestionSemantics::prefersMeasurementValueQuestionType(
                    mb_strtolower($query),
                    'optimal_range',
                    AgriculturalEntityCatalog::extractTopicFactors($query),
                    (string) ($understood->constraints['scientific_sense'] ?? ''),
                    (string) ($understood->constraints['requested_property'] ?? ''),
                ),
                $query.' expected measurable range, got '.$type,
            );
            $this->assertFalse(
                AgriculturalEntityCatalog::asksLandOrSoilSuitabilityQuestion($query),
                $query,
            );
        } else {
            $this->assertTrue(
                AgriculturalEntityCatalog::asksLandOrSoilSuitabilityQuestion($query),
                $query,
            );
            $this->assertNotSame('range', $type, $query);
        }
    }

    public function test_rc3_morphology_reduces_arabic_participial_process(): void
    {
        $this->assertTrue(AgriculturalEntityCatalog::isGenericScientificProcessToken('المستزرعة'));
        $this->assertTrue(AgriculturalEntityCatalog::isGenericScientificProcessToken('farmed'));
        $this->assertTrue(AgriculturalEntityCatalog::isGenericScientificProcessToken('cultivées'));
        $this->assertTrue(AgriculturalEntityCatalog::isGenericScientificProcessToken('yetiştirilen'));
        $this->assertFalse(AgriculturalEntityCatalog::isGenericScientificProcessToken('الحمص'));
        $this->assertFalse(AgriculturalEntityCatalog::isGenericScientificProcessToken('oats'));
    }
}
