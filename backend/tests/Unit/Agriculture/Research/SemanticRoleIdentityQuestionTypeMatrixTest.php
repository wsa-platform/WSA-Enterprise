<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use PHPUnit\Framework\TestCase;

/**
 * Data-driven matrices for process-vs-entity roles, multilingual crop identity,
 * and measurement-vs-definition question types. Surfaces are fixtures, not
 * production special cases.
 */
class SemanticRoleIdentityQuestionTypeMatrixTest extends TestCase
{
    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: ?string}>
     */
    public static function processVersusEntityProvider(): array
    {
        return [
            'en_growth' => ['What is the optimal temperature for the growth of wheat?', 'wheat', 'growth'],
            'en_cultivation' => ['What are the cultivation requirements of wheat?', 'wheat', 'cultivation'],
            'en_irrigation' => ['What is the irrigation requirement of wheat?', 'wheat', null],
            'en_diseases' => ['What are the diseases of wheat?', 'wheat', null],
            'en_yield' => ['What is the yield of wheat?', 'wheat', null],
            'en_temperature' => ['What is the temperature of wheat?', 'wheat', null],
            'en_soil' => ['What are the soil requirements of wheat?', 'wheat', null],
            'fr_growth' => ['Quelle est la température optimale pour la croissance du blé ?', 'wheat', 'croissance'],
            'fr_cultivation' => ['Quelles sont les exigences de culture du blé ?', 'wheat', null],
            'fr_irrigation' => ["Quel est le besoin d'irrigation du blé ?", 'wheat', null],
            'ar_growth' => ['ما هي درجة الحرارة المناسبة لنمو القمح؟', 'wheat', 'نمو'],
            'tr_growth' => ['Buğdayın büyümesi için optimum sıcaklık nedir?', 'wheat', 'büyüme'],
        ];
    }

    /**
     * @dataProvider processVersusEntityProvider
     */
    public function test_process_or_property_surface_is_not_the_crop_entity(
        string $query,
        string $expectedCropId,
        ?string $expectedProcessNeedle,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame($expectedCropId, $understood->cropId, $query);
        $this->assertSame($expectedCropId, $csq->cropBinding->cropId, $query);
        $this->assertSame($expectedCropId, $csq->entity->canonicalId ?? $csq->entity->normalized, $query);
        $this->assertFalse(
            CanonicalScientificQuestion::isGenericProcessSurface((string) $csq->entity->canonicalId),
            $query.' entity canonical is a process',
        );
        $surface = mb_strtolower((string) $csq->entity->surface);
        foreach (['growth', 'croissance', 'cultivation', 'irrigation', 'diseases', 'yield', 'temperature', 'soil', 'büyüme'] as $forbidden) {
            $this->assertNotSame($forbidden, $surface, $query.' process occupied entity');
        }
        if ($expectedProcessNeedle !== null && $csq->process->surface !== null) {
            $this->assertStringContainsString(
                $expectedProcessNeedle,
                mb_strtolower($csq->process->surface),
                $query,
            );
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function cropIdentityProvider(): array
    {
        $rows = [];
        $crops = [
            'wheat' => [
                'en' => 'wheat',
                'ar' => 'القمح',
                'fr' => 'blé',
                'tr_base' => 'buğday',
                'tr_poss' => 'buğdayın',
            ],
            'barley' => [
                'en' => 'barley',
                'ar' => 'الشعير',
                'fr' => 'orge',
                'tr_base' => 'arpa',
                'tr_poss' => 'arpanın',
            ],
            'rice' => [
                'en' => 'rice',
                'ar' => 'الأرز',
                'fr' => 'riz',
                'tr_base' => 'pirinç',
                'tr_poss' => 'pirincin',
            ],
            'corn' => [
                'en' => 'maize',
                'ar' => 'الذرة',
                'fr' => 'maïs',
                'tr_base' => 'mısır',
                'tr_poss' => 'mısırın',
            ],
            'chickpea' => [
                'en' => 'chickpea',
                'ar' => 'الحمص',
                'fr' => 'pois chiche',
                'tr_base' => 'nohut',
                'tr_poss' => 'nohutun',
            ],
            'lentil' => [
                'en' => 'lentil',
                'ar' => 'العدس',
                'fr' => 'lentille',
                'tr_base' => 'mercimek',
                'tr_poss' => 'mercimeğin',
            ],
            'sesame' => [
                'en' => 'sesame',
                'ar' => 'السمسم',
                'fr' => 'sésame',
                'tr_base' => 'susam',
                'tr_poss' => 'susamın',
            ],
            'potato' => [
                'en' => 'potato',
                'ar' => 'البطاطا',
                'fr' => 'pomme de terre',
                'tr_base' => 'patates',
                'tr_poss' => 'patatesin',
            ],
            'tomato' => [
                'en' => 'tomato',
                'ar' => 'الطماطم',
                'fr' => 'tomate',
                'tr_base' => 'domates',
                'tr_poss' => 'domatesin',
            ],
            'alfalfa' => [
                'en' => 'alfalfa',
                'ar' => 'الفصة',
                'fr' => 'luzerne',
                'tr_base' => 'yonca',
                'tr_poss' => 'yoncanın',
            ],
        ];
        foreach ($crops as $cropId => $forms) {
            foreach ($forms as $formId => $label) {
                $rows[$cropId.'_'.$formId] = [$label, $cropId];
            }
        }

        return $rows;
    }

    /**
     * @dataProvider cropIdentityProvider
     */
    public function test_crop_surface_resolves_without_special_case_strings(string $label, string $cropId): void
    {
        $this->assertSame(
            $cropId,
            AgriculturalEntityCatalog::resolveCanonicalCropIdFromLabel($label),
            $label,
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function turkishSentenceIdentityProvider(): array
    {
        return [
            'barley_sentence' => ['Arpanın büyümesi için optimum sıcaklık nedir?', 'barley'],
            'wheat_sentence' => ['Buğdayın büyümesi için optimum sıcaklık nedir?', 'wheat'],
            'rice_sentence' => ['Pirincin büyümesi için optimum sıcaklık nedir?', 'rice'],
            'maize_sentence' => ['Mısırın büyümesi için optimum sıcaklık nedir?', 'corn'],
            'chickpea_sentence' => ['Nohutun büyümesi için optimum sıcaklık nedir?', 'chickpea'],
            'lentil_sentence' => ['Mercimeğin büyümesi için optimum sıcaklık nedir?', 'lentil'],
            'sesame_sentence' => ['Susamın büyümesi için optimum sıcaklık nedir?', 'sesame'],
            'potato_sentence' => ['Patatesin büyümesi için optimum sıcaklık nedir?', 'potato'],
            'tomato_sentence' => ['Domatesin büyümesi için optimum sıcaklık nedir?', 'tomato'],
            'alfalfa_sentence' => ['Yoncanın büyümesi için optimum sıcaklık nedir?', 'alfalfa'],
        ];
    }

    /**
     * @dataProvider turkishSentenceIdentityProvider
     */
    public function test_turkish_inflected_sentence_resolves_crop_identity(string $query, string $cropId): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $this->assertSame($cropId, $understood->cropId, $query);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame($cropId, $csq->cropBinding->cropId, $query);
        $this->assertSame($cropId, $csq->entity->canonicalId ?? $csq->entity->normalized, $query);
        $this->assertNotSame('arpa', $csq->entity->canonicalId);
    }

    public function test_global_recognize_crop_still_ignores_home_turkish_surfaces(): void
    {
        $this->assertNull(AgriculturalEntityCatalog::recognizeCrop('buğdayın çimlenme sıcaklığı'));
        $this->assertNull(AgriculturalEntityCatalog::recognizeCrop('arpanın büyümesi'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function questionTypeProvider(): array
    {
        return [
            'ar_temp_range' => ['ما هي درجة الحرارة المناسبة لنمو الأرز؟', 'range'],
            'en_temp_range' => ['What is the optimal temperature for rice growth?', 'range'],
            'fr_temp_range' => ['Quelle est la température optimale pour la croissance du riz ?', 'range'],
            'tr_temp_range' => ['Pirincin büyümesi için optimum sıcaklık nedir?', 'range'],
            'ar_water_value' => ['ما هي كمية المياه المناسبة لنمو القمح؟', 'range'],
            'en_water_qty' => ['How much irrigation water does wheat require?', 'quantity'],
            'ar_definition' => ['ما هو القمح؟', 'definition'],
            'en_definition' => ['What is wheat?', 'definition'],
            'fr_definition' => ["Qu'est-ce que le blé ?", 'definition'],
            'tr_definition' => ['Buğday nedir?', 'definition'],
            'en_recommendation' => ['What is the best method to cultivate wheat?', 'recommendation'],
            'ar_recommendation' => ['ما هي أفضل طريقة لزراعة القمح؟', 'recommendation'],
            'en_temp_suitable_range' => ['What temperature is suitable for wheat growth?', 'range'],
            'en_ph_range' => ['What is the pH range for wheat?', 'range'],
            'en_water_req_range' => ['What is the suitable water requirement for wheat?', 'range'],
            'fr_temp_convient_range' => ['Quelle température convient à la croissance du blé ?', 'range'],
            'tr_suitable_range' => ['Buğday için uygun sıcaklık aralığı nedir?', 'range'],
            'en_crops_high_temp' => ['What crops are suitable under high temperature?', 'recommendation'],
            'en_crops_desert' => ['Which crops are suitable for desert conditions?', 'recommendation'],
            'en_crops_saline' => ['What crops can grow under saline conditions?', 'recommendation'],
            'en_crops_little_water' => ['Which crops need little water?', 'recommendation'],
            'ar_crops_high_temp' => ['ما المحاصيل المناسبة للحرارة المرتفعة؟', 'recommendation'],
            'ar_crops_desert' => ['ما المحاصيل المناسبة للزراعة في الصحراء؟', 'recommendation'],
            'ar_crops_saline' => ['ما المحاصيل التي تتحمل الملوحة؟', 'recommendation'],
            'ar_crops_little_water' => ['ما المحاصيل التي تحتاج إلى مياه قليلة؟', 'recommendation'],
            'fr_crops_high_temp' => ['Quelles cultures conviennent aux fortes températures ?', 'recommendation'],
            'fr_crops_desert' => ['Quelles cultures conviennent aux conditions désertiques ?', 'recommendation'],
            'tr_crops_high_temp' => ['Yüksek sıcaklıklara hangi ürünler uygundur?', 'recommendation'],
            'tr_crops_desert' => ['Çöl koşullarına hangi ürünler uygundur?', 'recommendation'],
            'ar_rice_definition' => ['ما هو الأرز؟', 'definition'],
            'en_rice_definition' => ['What is rice?', 'definition'],
            'en_comparison' => ['Which needs more irrigation water, wheat or maize?', 'comparison'],
            'en_procedure_how' => ['How to irrigate wheat?', 'recommendation'],
            'en_explanation' => ['Why do wheat leaves turn yellow?', 'causes'],
            'ar_explanation' => ['لماذا تصفر أوراق القمح؟', 'causes'],
            'ar_soil_types' => ['ما أنواع التربة في مصر؟', 'classification'],
            'en_saline_soil_crops' => ['What crops can be grown in saline soil?', 'recommendation'],
            'en_crop_cultivation_definition' => ['What is crop cultivation?', 'definition'],
            'fr_crop_cultivation_definition' => ["Qu'est-ce que la culture agricole ?", 'definition'],
            'ar_crop_cultivation_definition' => ['ما هي زراعة المحاصيل؟', 'definition'],
            'tr_crop_cultivation_definition' => ['Bitki yetiştiriciliği nedir?', 'definition'],
            'en_saline_soil_definition' => ['What is saline soil?', 'definition'],
            'fr_saline_soil_crops' => ['Quelles cultures peuvent pousser dans des sols salins ?', 'recommendation'],
            'fr_saline_soil_definition' => ["Qu'est-ce qu'un sol salin ?", 'definition'],
            'ar_saline_soil_crops' => ['ما المحاصيل التي يمكن زراعتها في التربة المالحة؟', 'recommendation'],
            'ar_saline_soil_definition' => ['ما هي التربة المالحة؟', 'definition'],
            'tr_saline_soil_crops' => ['Tuzlu topraklarda hangi ürünler yetişebilir?', 'recommendation'],
            'tr_saline_soil_definition' => ['Tuzlu toprak nedir?', 'definition'],
            'en_arid_crops' => ['What crops are suitable for arid regions?', 'recommendation'],
            'en_arid_definition' => ['What is an arid environment?', 'definition'],
            'ar_arid_crops' => ['ما المحاصيل المناسبة للظروف الصحراوية؟', 'recommendation'],
            'ar_arid_definition' => ['ما هي البيئة الصحراوية؟', 'definition'],
            'fr_arid_crops' => ['Quelles cultures conviennent aux conditions arides ?', 'recommendation'],
            'fr_arid_definition' => ["Qu'est-ce qu'un environnement aride ?", 'definition'],
            'tr_arid_crops' => ['Kurak koşullara hangi ürünler uygundur?', 'recommendation'],
            'tr_arid_definition' => ['Kurak ortam nedir?', 'definition'],
            'en_combined_arid_saline' => ['What crops can be grown in arid regions with saline water?', 'recommendation'],
            'ar_combined_arid_saline' => ['ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟', 'recommendation'],
            'fr_combined_arid_saline' => ["Quelles cultures peuvent être cultivées dans des régions arides avec de l'eau salée ?", 'recommendation'],
            'tr_combined_arid_saline' => ['Tuzlu su bulunan kurak bölgelerde hangi ürünler yetiştirilebilir?', 'recommendation'],
        ];
    }

    /**
     * @dataProvider questionTypeProvider
     */
    public function test_question_type_follows_requested_information_not_copula(string $query, string $expectedType): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $this->assertSame($expectedType, $understood->constraints['question_type'] ?? null, $query);
    }

    public function test_cross_language_measurement_roles_are_canonically_equivalent(): void
    {
        $queries = [
            'ar' => 'ما هي درجة الحرارة المناسبة لنمو الأرز؟',
            'en' => 'What is the optimal temperature for rice growth?',
            'fr' => 'Quelle est la température optimale pour la croissance du riz ?',
            'tr' => 'Pirincin büyümesi için optimum sıcaklık nedir?',
        ];

        $slices = [];
        foreach ($queries as $lang => $query) {
            $understood = $this->qus->understand(['query' => $query]);
            $csq = $understood->canonicalQuestion;
            $this->assertNotNull($csq, $query);
            $slices[$lang] = [
                'crop_id' => $understood->cropId,
                'scientific' => $understood->scientificName,
                'entity' => $csq->entity->canonicalId ?? $csq->entity->normalized,
                'property' => $csq->property->key ?? ($understood->constraints['requested_property'] ?? null),
                'question_type' => $understood->constraints['question_type'] ?? null,
            ];
        }
        foreach (['en', 'fr', 'tr'] as $lang) {
            $this->assertSame($slices['ar']['crop_id'], $slices[$lang]['crop_id'], $lang.' crop');
            $this->assertSame($slices['ar']['scientific'], $slices[$lang]['scientific'], $lang.' scientific');
            $this->assertSame($slices['ar']['entity'], $slices[$lang]['entity'], $lang.' entity');
            $this->assertSame($slices['ar']['question_type'], $slices[$lang]['question_type'], $lang.' qtype');
        }
    }

    /**
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}>
     */
    public static function constraintMustNotOccupyEntityProvider(): array
    {
        return [
            'en_saline_soil' => ['What crops can be grown in saline soil?', ['saline'], ['saline_soil']],
            'fr_saline_soil' => ['Quelles cultures peuvent pousser dans des sols salins ?', ['saline', 'salins', 'salin'], ['saline_soil']],
            'ar_saline_soil' => ['ما المحاصيل التي يمكن زراعتها في التربة المالحة؟', ['مالحة', 'ملوحة', 'saline'], ['saline_soil']],
            'tr_saline_soil' => ['Tuzlu topraklarda hangi ürünler yetişebilir?', ['tuzlu', 'saline'], ['saline_soil']],
            'en_arid' => ['What crops are suitable for arid regions?', ['arid', 'desert'], ['arid_environment']],
            'ar_arid' => ['ما المحاصيل المناسبة للظروف الصحراوية؟', ['صحراوية', 'arid', 'desert'], ['arid_environment']],
            'fr_arid' => ['Quelles cultures conviennent aux conditions arides ?', ['aride', 'arides', 'arid', 'desert'], ['arid_environment']],
            'tr_arid' => ['Kurak koşullara hangi ürünler uygundur?', ['kurak', 'arid', 'desert'], ['arid_environment']],
            'en_little_water' => ['What crops need little water?', ['water'], ['water_scarcity']],
            'en_limited_water' => ['Which crops require limited water?', ['water'], ['water_scarcity']],
            'en_water_scarce' => ['Which crops are suitable where water is scarce?', ['water'], ['water_scarcity']],
            'ar_little_water' => ['ما المحاصيل التي تحتاج إلى مياه قليلة؟', ['مياه', 'ماء', 'water'], ['water_scarcity']],
            'ar_scarcity' => ['ما المحاصيل المناسبة في ظل شح المياه؟', ['مياه', 'ماء', 'water'], ['water_scarcity']],
            'fr_little_water' => ["Quelles cultures nécessitent peu d'eau ?", ['eau', 'water'], ['water_scarcity']],
            'fr_rare_water' => ["Quelles cultures conviennent lorsque l'eau est rare ?", ['eau', 'water'], ['water_scarcity']],
            'tr_little_water' => ['Hangi ürünler az suya ihtiyaç duyar?', ['su', 'water'], ['water_scarcity']],
            'tr_scarcity' => ['Su kıtlığında hangi ürünler uygundur?', ['su', 'water'], ['water_scarcity']],
            'en_saline_water' => ['What crops can be grown with saline water?', ['saline', 'water'], ['saline_water']],
            'en_salt_water' => ['What crops can grow with salt water?', ['salt', 'water'], ['saline_water']],
            'fr_eau_salee' => ["Quelles cultures peuvent être cultivées avec de l'eau salée ?", ['salée', 'salee', 'eau'], ['saline_water']],
            'fr_eau_saline' => ["Quelles cultures peuvent pousser avec de l'eau saline ?", ['saline', 'eau'], ['saline_water']],
            'tr_tuzlu_su' => ['Tuzlu su ile hangi ürünler yetiştirilebilir?', ['tuzlu', 'su'], ['saline_water']],
            'tr_tuzlu_sularda' => ['Tuzlu sularda hangi ürünler yetişebilir?', ['tuzlu', 'su'], ['saline_water']],
            'ar_saline_water' => ['ما المحاصيل التي يمكن زراعتها بالمياه المالحة؟', ['مالحة', 'مياه'], ['saline_water']],
            'en_high_temp' => ['What crops are suitable under high temperature?', ['temperature'], ['high_temperature']],
            'ar_high_temp' => ['ما المحاصيل المناسبة للحرارة المرتفعة؟', ['حرارة', 'temperature'], ['high_temperature']],
            'en_combined' => [
                'What crops can be grown in arid regions with saline water?',
                ['saline', 'arid', 'desert', 'water'],
                ['arid_environment', 'saline_water'],
            ],
            'ar_combined' => [
                'ما أفضل المحاصيل التي يمكن زراعتها في الأراضي الصحراوية ذات المياه المالحة؟',
                ['مالحة', 'صحراوية', 'مياه', 'saline', 'arid', 'desert'],
                ['arid_environment', 'saline_water'],
            ],
            'fr_combined' => [
                "Quelles cultures peuvent être cultivées dans des régions arides avec de l'eau salée ?",
                ['salée', 'salee', 'saline', 'arides', 'arid', 'desert'],
                ['arid_environment', 'saline_water'],
            ],
            'tr_combined' => [
                'Tuzlu su bulunan kurak bölgelerde hangi ürünler yetiştirilebilir?',
                ['tuzlu', 'kurak', 'saline', 'arid', 'desert'],
                ['arid_environment', 'saline_water'],
            ],
        ];
    }

    /**
     * @dataProvider constraintMustNotOccupyEntityProvider
     * @param  list<string>  $forbiddenEntitySurfaces
     * @param  list<string>  $expectedConstraintTypes
     */
    public function test_environmental_constraint_does_not_occupy_entity_on_entity_set_questions(
        string $query,
        array $forbiddenEntitySurfaces,
        array $expectedConstraintTypes,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame('recommendation', $understood->constraints['question_type'] ?? null, $query);
        $this->assertTrue(
            AgriculturalEntityCatalog::asksAgriculturalEntitySetQuestion(mb_strtolower($query)),
            $query.' should ask for an agricultural entity set',
        );
        $surface = mb_strtolower(trim((string) $csq->entity->surface));
        $normalized = mb_strtolower(trim((string) ($csq->entity->normalized ?? '')));
        foreach ($forbiddenEntitySurfaces as $forbidden) {
            $this->assertNotSame($forbidden, $surface, $query.' constraint occupied entity surface');
            $this->assertNotSame($forbidden, $normalized, $query.' constraint occupied entity normalized');
        }
        $types = [];
        foreach ($understood->constraints['environmental_constraints'] ?? [] as $constraint) {
            if (is_array($constraint) && isset($constraint['type'])) {
                $types[] = (string) $constraint['type'];
            }
        }
        foreach ($expectedConstraintTypes as $expectedType) {
            $this->assertContains($expectedType, $types, $query.' missing constraint '.$expectedType);
        }
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function environmentalConceptDefinitionProvider(): array
    {
        return [
            'en_saline_soil' => ['What is saline soil?', ['saline', 'soil']],
            'fr_saline_soil' => ["Qu'est-ce qu'un sol salin ?", ['salin', 'sol']],
            'ar_saline_soil' => ['ما هي التربة المالحة؟', ['مالحة', 'تربة']],
            'tr_saline_soil' => ['Tuzlu toprak nedir?', ['tuzlu', 'toprak']],
            'en_arid' => ['What is an arid environment?', ['arid']],
            'ar_arid' => ['ما هي البيئة الصحراوية؟', ['صحراوية']],
            'fr_arid' => ["Qu'est-ce qu'un environnement aride ?", ['aride']],
            'tr_arid' => ['Kurak ortam nedir?', ['kurak']],
            'en_saline_water' => ['What is saline water?', ['saline']],
            'fr_eau_salee' => ["Qu'est-ce que l'eau salée ?", ['salée', 'salee', 'eau']],
            'tr_tuzlu_su' => ['Tuzlu su nedir?', ['tuzlu']],
            'ar_saline_water' => ['ما هي المياه المالحة؟', ['مالحة']],
            'en_water_scarcity' => ['What is water scarcity?', ['scarcity', 'water']],
            'fr_penurie' => ["Qu'est-ce que la pénurie d'eau ?", ['pénurie', 'penurie', 'eau']],
            'tr_kitlik' => ['Su kıtlığı nedir?', ['kıtlık', 'kitlik', 'su']],
            'ar_nudrah' => ['ما هي ندرة المياه؟', ['ندرة', 'مياه']],
        ];
    }

    /**
     * @dataProvider environmentalConceptDefinitionProvider
     * @param  list<string>  $conceptNeedles
     */
    public function test_environmental_concept_may_be_definition_subject(
        string $query,
        array $conceptNeedles,
    ): void {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame('definition', $understood->constraints['question_type'] ?? null, $query);
        $this->assertFalse(
            AgriculturalEntityCatalog::asksAgriculturalEntitySetQuestion(mb_strtolower($query)),
            $query.' definition must not be treated as an entity-set request',
        );
        $surface = mb_strtolower(trim((string) $csq->entity->surface));
        foreach (['crops', 'crop', 'wheat', 'rice', 'barley'] as $forbiddenSet) {
            $this->assertNotSame($forbiddenSet, $surface, $query.' definition collapsed to a crop set');
        }
        $hay = mb_strtolower(implode(' ', array_filter([
            $csq->entity->surface,
            $csq->entity->normalized,
            $understood->subject['label'] ?? null,
            $understood->subject['value'] ?? null,
            $query,
        ])));
        $hit = false;
        foreach ($conceptNeedles as $needle) {
            if (str_contains($hay, mb_strtolower($needle))) {
                $hit = true;
                break;
            }
        }
        $this->assertTrue($hit, $query.' definition lost environmental concept subject='.$hay);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function residualProcessMustNotOccupyEntityProvider(): array
    {
        return [
            'en_grown' => ['What crops can be grown in saline soil?'],
            'en_grow' => ['What crops can grow in saline soil?'],
            'en_cultivated' => ['What crops can be cultivated in saline soil?'],
            'en_suitable_growing' => ['What crops are suitable for growing in saline soil?'],
            'fr_pousser' => ['Quelles cultures peuvent pousser dans des sols salins ?'],
            'fr_cultivees' => ['Quelles cultures peuvent être cultivées dans des sols salins ?'],
            'fr_conviennent' => ['Quelles cultures conviennent aux sols salins ?'],
            'ar_grown' => ['ما المحاصيل التي يمكن زراعتها في التربة المالحة؟'],
            'ar_grow' => ['ما المحاصيل التي يمكن أن تنمو في التربة المالحة؟'],
            'tr_yetisebilir' => ['Tuzlu topraklarda hangi ürünler yetişebilir?'],
            'tr_yetistirilebilir' => ['Tuzlu topraklarda hangi ürünler yetiştirilebilir?'],
            'unknown_residual' => ['What crops can foobar in saline soil?'],
        ];
    }

    /**
     * @dataProvider residualProcessMustNotOccupyEntityProvider
     */
    public function test_residual_process_or_unknown_phrase_does_not_occupy_entity(string $query): void
    {
        $understood = $this->qus->understand(['query' => $query]);
        $csq = $understood->canonicalQuestion;
        $this->assertNotNull($csq, $query);
        $this->assertSame('recommendation', $understood->constraints['question_type'] ?? null, $query);
        $this->assertSame('crops', $understood->subject['value'] ?? $understood->subject['label'] ?? null, $query);
        $surface = mb_strtolower(trim((string) $csq->entity->surface));
        foreach ([
            'peuvent pousser dans', 'peuvent', 'pousser', 'dans',
            'can grow', 'grown', 'cultivated', 'foobar',
            'yetişebilir', 'yetiştirilebilir', 'يمكن',
        ] as $forbidden) {
            $this->assertNotSame($forbidden, $surface, $query);
        }
        $this->assertSame('', $surface, $query.' residual occupied entity');
        $this->assertSame('crop_category', $understood->subject['type'] ?? null, $query);
    }

    public function test_named_crop_survives_process_framing(): void
    {
        foreach ([
            'What can wheat grow in?',
            'Where can wheat grow?',
            'How can wheat be cultivated?',
        ] as $query) {
            $understood = $this->qus->understand(['query' => $query]);
            $this->assertSame('wheat', $understood->cropId, $query);
            $this->assertSame(
                'wheat',
                $understood->canonicalQuestion?->entity->canonicalId
                    ?? $understood->canonicalQuestion?->entity->normalized,
                $query,
            );
        }
    }

    public function test_named_crop_measurement_does_not_promote_contextual_water_or_temperature(): void
    {
        $water = $this->qus->understand(['query' => 'What is the water requirement of wheat?']);
        $this->assertSame('wheat', $water->cropId);
        $this->assertSame('wheat', $water->canonicalQuestion?->entity->canonicalId ?? $water->canonicalQuestion?->entity->normalized);
        $this->assertNotSame('water', mb_strtolower((string) $water->canonicalQuestion?->entity->surface));
        $this->assertContains($water->constraints['question_type'] ?? null, ['range', 'quantity', 'requirements']);

        $temperature = $this->qus->understand(['query' => 'What temperature is suitable for wheat?']);
        $this->assertSame('wheat', $temperature->cropId);
        $this->assertSame('range', $temperature->constraints['question_type'] ?? null);
        $this->assertNotSame('temperature', mb_strtolower((string) $temperature->canonicalQuestion?->entity->surface));
    }

    public function test_production_code_has_no_surface_special_cases(): void
    {
        $files = [
            dirname(__DIR__, 4).'/app/Services/Agriculture/Research/QueryUnderstandingService.php',
            dirname(__DIR__, 4).'/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php',
            dirname(__DIR__, 4).'/app/Services/Agriculture/Research/CanonicalScientificQuestion.php',
            dirname(__DIR__, 4).'/app/Services/Agriculture/Research/ScientificQuestionSemantics.php',
        ];
        foreach ($files as $file) {
            $src = file_get_contents($file);
            $this->assertIsString($src, $file);
            $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$language\s*===\s*\'fr\'/', $src, $file);
            $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$language\s*===\s*\'tr\'/', $src, $file);
            $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$surface\s*===\s*\'arpa\'/', $src, $file);
            $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$surface\s*===\s*\'croissance\'/', $src, $file);
            $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*\$cropId\s*===\s*\'wheat\'/', $src, $file);
        }
    }
}
