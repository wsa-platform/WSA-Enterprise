<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use PHPUnit\Framework\TestCase;

/**
 * Process-family authority and definition-subject materialization.
 * Fixtures are unseen equivalents, not production special cases.
 */
class ProcessAuthorityAndDefinitionSubjectContractTest extends TestCase
{
    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function namedEntityKeepsProcessWithoutDualOccupancyProvider(): array
    {
        return [
            'en_sunflower_planting' => ['When is the right window for planting sunflower?', 'sunflower'],
            'en_oats_cultivate' => ['How should oats be cultivated after frost?', 'oats'],
            'fr_oats_planter' => ['Quelle est la période pour planter l\'avoine ?', 'oats'],
            'fr_potato_cultiver' => ['Comment cultiver la pomme de terre en sol léger ?', 'potato'],
            'tr_chickpea_ekim' => ['Nohut ekimi hangi ayda yapılır?', 'chickpea'],
            'tr_corn_yetistirme' => ['Mısır yetiştirme zamanı ne zaman?', 'corn'],
            'ar_tomato_planting' => ['ما مواعيد زراعة الطماطم؟', 'tomato'],
            'ar_barley_grow' => ['كيف تتم زراعة الشعير بعد الأمطار؟', 'barley'],
            'en_lentil_growing' => ['What growing conditions suit lentil?', 'lentil'],
            'en_rice_sowing' => ['When should rice sowing begin after flooding?', 'rice'],
        ];
    }

    /**
     * @dataProvider namedEntityKeepsProcessWithoutDualOccupancyProvider
     */
    public function test_rc_new1_process_family_does_not_occupy_entity_without_identity(
        string $query,
        string $expectedCropId,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame($expectedCropId, $understood->cropId, $query);
        $this->assertNotNull($csq->process->surface, $query);
        $entity = mb_strtolower((string) $csq->entity->surface);
        $process = mb_strtolower((string) $csq->process->surface);
        $this->assertNotSame($entity, $process, $query.' dual occupancy');
        $this->assertFalse(
            AgriculturalEntityCatalog::tokenMatchesCultivationProcessFamily((string) $csq->entity->surface),
            $query.' entity is a cultivation-family process',
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function definitionDoesNotInventProcessProvider(): array
    {
        return [
            'en_wheat' => ['What is wheat?'],
            'en_rice' => ['What is rice?'],
            'ar_wheat' => ['ما هو القمح؟'],
            'tr_wheat' => ['Buğday nedir?'],
            'fr_wheat' => ["Qu'est-ce que le blé ?"],
        ];
    }

    /**
     * @dataProvider definitionDoesNotInventProcessProvider
     */
    public function test_rc_new1_definition_does_not_acquire_process(string $query): void
    {
        $csq = $this->qus->understand(['query' => $query])->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertNull($csq->process->surface, $query);
    }

    public function test_rc_new1_unrelated_noun_is_not_process(): void
    {
        $this->assertFalse(AgriculturalEntityCatalog::tokenMatchesCultivationProcessFamily('sunflower'));
        $this->assertFalse(AgriculturalEntityCatalog::tokenMatchesCultivationProcessFamily('القمح'));
        $this->assertFalse(AgriculturalEntityCatalog::isGenericScientificProcessToken('oats'));
        $this->assertTrue(AgriculturalEntityCatalog::tokenMatchesCultivationProcessFamily('planting'));
        $this->assertTrue(AgriculturalEntityCatalog::surfaceOccupiesProcessRole('cultiver'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function catalogDefinitionSubjectProvider(): array
    {
        return [
            'en_rye' => ['What is rye?', 'rye'],
            'en_oats' => ['What is oats?', 'oats'],
            'ar_barley' => ['ما هو الشعير؟', 'barley'],
            'tr_lentil' => ['Mercimek nedir?', 'lentil'],
            'fr_oats' => ["Qu'est-ce que l'avoine ?", 'oats'],
        ];
    }

    /**
     * @dataProvider catalogDefinitionSubjectProvider
     */
    public function test_rc_new2_catalog_definition_subject_resolves(string $query, string $expectedCropId): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame($expectedCropId, $understood->cropId, $query);
        $this->assertNotNull($csq->entity->surface, $query);
        $this->assertNull($csq->process->surface, $query);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unresolvedDefinitionSubjectProvider(): array
    {
        return [
            'en_unknown' => ['What is kaniwa?'],
            'ar_unknown' => ['ما هو الكتان؟'],
            'fr_unknown' => ["Qu'est-ce que le sarrasin ?"],
            'tr_unknown' => ['Karabuğday nedir?'],
            'en_concept' => ['What is allelopathy?'],
        ];
    }

    /**
     * @dataProvider unresolvedDefinitionSubjectProvider
     */
    public function test_rc_new2_explicit_definition_subject_materializes_without_catalog(
        string $query,
    ): void {
        $subject = AgriculturalEntityCatalog::extractExplicitDefinitionSubject($query);
        $this->assertNotNull($subject, $query);
        $this->assertTrue(
            AgriculturalEntityCatalog::residualSurfaceHasEntityEvidence($subject, $query),
            $query,
        );
        $roles = AgriculturalEntityCatalog::extractSemanticTarget($query);
        $this->assertNotNull($roles['entity_surface'] ?? null, $query);
        $this->assertTrue(
            AgriculturalEntityCatalog::surfacesCollapse((string) $roles['entity_surface'], $subject)
            || mb_strtolower((string) $roles['entity_surface']) === mb_strtolower($subject),
            $query.' subject not materialized',
        );
        $this->assertSame(
            CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            $roles['entity_resolution'] ?? null,
            $query,
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function classificationDoesNotInventEntityProvider(): array
    {
        return [
            'en_types_molluscs' => ['What types of cultivated molluscs are listed in estuary surveys?'],
            'en_which_soils' => ['Which quonkle soils appear after flooding?'],
            'ar_types_unknown' => ['ما أنواع الطيور في المرتفعات؟'],
            'tr_types' => ['Tarla koşullarında ekilen türler nelerdir?'],
            'fr_plantes' => ['Quelles plantes plantées en serre sont décrites ?'],
        ];
    }

    /**
     * @dataProvider classificationDoesNotInventEntityProvider
     */
    public function test_rc_new2_classification_frame_is_not_definition_evidence(string $query): void
    {
        $this->assertNull(AgriculturalEntityCatalog::extractExplicitDefinitionSubject($query), $query);
        $roles = AgriculturalEntityCatalog::extractSemanticTarget($query);
        $surface = mb_strtolower((string) ($roles['entity_surface'] ?? ''));
        $this->assertStringNotContainsString('quonkle', $surface, $query);
        $this->assertStringNotContainsString('molluscs', $surface, $query);
    }

    public function test_rc_new2_process_definition_does_not_become_entity(): void
    {
        $query = 'What is cultivation?';
        $csq = $this->qus->understand(['query' => $query])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNull($csq->entity->surface);
        $this->assertNotNull($csq->process->surface);
    }
}
