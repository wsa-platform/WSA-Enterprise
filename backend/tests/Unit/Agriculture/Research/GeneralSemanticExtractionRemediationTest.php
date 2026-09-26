<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use PHPUnit\Framework\TestCase;

class GeneralSemanticExtractionRemediationTest extends TestCase
{
    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
    }

    public function test_process_trim_does_not_use_entity_stop_list(): void
    {
        $kept = AgriculturalEntityCatalog::trimProcessPhrase('الري');
        $this->assertSame('الري', $kept);
        $this->assertSame('irrigation', AgriculturalEntityCatalog::trimProcessPhrase('irrigation'));
        $this->assertTrue(AgriculturalEntityCatalog::isNamedEntityStopToken('الري'));
    }

    public function test_irrigation_causal_keeps_process_role(): void
    {
        $csq = $this->csq('تأثير الري على إنتاج القمح');
        $this->assertNotNull($csq->process->surface);
        $this->assertSame('الري', $csq->process->surface);
        $this->assertNotNull($csq->entity->surface);
        $this->assertSame('إنتاج القمح', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $csq->target->kind);
        $this->assertSame('quantity', $csq->property->key);
        $this->assertSame('yield', $csq->property->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
        $this->assertSame('causes', $this->qus->understand(['query' => 'تأثير الري على إنتاج القمح'])->constraints['question_type'] ?? null);
        $this->assertNull($csq->process->canonicalId);
        $this->assertNotSame($csq->process->surface, $csq->entity->surface);
        $this->assertNotSame($csq->process->surface, $csq->property->surface);
    }

    public function test_english_irrigation_causal_keeps_process(): void
    {
        $csq = $this->csq('effect of irrigation on wheat production');
        $this->assertNotNull($csq->process->surface);
        $this->assertSame('irrigation', mb_strtolower((string) $csq->process->surface));
        $this->assertNotNull($csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
    }

    public function test_production_matrix_distinguishes_product_from_entity_scope(): void
    {
        $wheat = $this->csq('إنتاج القمح');
        $this->assertSame('إنتاج القمح', $wheat->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $wheat->target->kind);
        $this->assertNotNull($wheat->entity->surface);
        $this->assertNotSame($wheat->entity->surface, $wheat->target->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $wheat->property->ofRole);
        $this->assertNull($wheat->target->canonicalId);

        $maize = $this->csq('إنتاج الذرة');
        $this->assertSame('إنتاج الذرة', $maize->target->surface);
        $this->assertNotNull($maize->entity->surface);

        $milk = $this->csq('إنتاج الحليب');
        $this->assertSame('إنتاج الحليب', $milk->target->surface);
        $this->assertNull($milk->entity->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $milk->property->ofRole);

        $laban = $this->csq('إنتاج اللبن');
        $this->assertSame('إنتاج اللبن', $laban->target->surface);
        $this->assertNull($laban->entity->canonicalId);

        $cattle = $this->csq('إنتاج الأبقار');
        $this->assertSame('إنتاج الأبقار', $cattle->target->surface);
        $this->assertNull($cattle->target->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $cattle->target->resolution);
        $this->assertNotSame('milk production', mb_strtolower((string) $cattle->target->surface));

        $scoped = $this->csq('إنتاج الحليب في الأبقار');
        $this->assertSame('إنتاج الحليب', $scoped->target->surface);
        $this->assertSame('cattle', $scoped->entity->normalized);
        $this->assertNotSame($scoped->entity->surface, $scoped->target->surface);
    }

    public function test_quantity_of_object_is_target_not_property_surface(): void
    {
        $csq = $this->csq('كمية اللبن');
        $this->assertSame('اللبن', $csq->target->surface);
        $this->assertSame('quantity', $csq->property->key);
        $this->assertNotSame('اللبن', $csq->property->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
        $this->assertNull($csq->entity->surface);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNotSame($csq->target->surface, $csq->property->surface);
    }

    public function test_english_how_much_object_is_target(): void
    {
        $graph = AgriculturalEntityCatalog::extractMeasureObjectFrame('how much milk');
        $this->assertNotNull($graph);
        $this->assertSame('quantity', $graph['property_key']);
        $this->assertSame('milk', $graph['target_surface']);
        $this->assertNotSame('milk', $graph['property_surface']);
    }

    public function test_weight_affected_object_is_not_given_invented_property(): void
    {
        $csq = $this->csq('تأثير التغذية على وزن الأبقار');
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertNotNull($csq->target->surface);
        $this->assertStringContainsString('وزن', (string) $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_AFFECTED_OBJECT, $csq->target->kind);
        $this->assertNull($csq->property->key);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
    }

    public function test_associative_frame_is_not_causal(): void
    {
        $csq = $this->csq('العلاقة بين التغذية وإنتاج اللبن');
        $this->assertSame(CanonicalScientificQuestion::RELATION_ASSOCIATIVE, $csq->relation->type);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertSame('إنتاج اللبن', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
        $this->assertNull($csq->entity->surface);
        $this->assertNull($csq->process->canonicalId);
    }

    public function test_english_relation_between_is_associative(): void
    {
        $csq = $this->csq('relationship between irrigation and wheat production');
        $this->assertSame(CanonicalScientificQuestion::RELATION_ASSOCIATIVE, $csq->relation->type);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertNotNull($csq->process->surface);
        $this->assertNotNull($csq->target->surface);
    }

    public function test_descriptive_is_not_causal_and_does_not_invent_target(): void
    {
        $csq = $this->csq('What is wheat?');
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $csq->relation->type);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertNull($csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_NONE, $csq->target->kind);
        $this->assertNull($csq->relation->to);
    }

    public function test_comparative_preserves_second_operand(): void
    {
        $query = $this->qus->understand(['query' => 'مقارنة إنتاج القمح والذرة']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $csq->relation->type);
        $this->assertNotNull($csq->relation->from);
        $this->assertNotNull($csq->relation->to);
        $this->assertSame('إنتاج القمح', $csq->target->surface);
        $this->assertStringNotContainsString('والذرة', (string) $csq->target->surface);
        $operands = $query->constraints['comparison_operands'] ?? [];
        $this->assertTrue(
            count((array) $operands) >= 2
            || count((array) ($query->constraints['comparison_entities'] ?? [])) >= 2,
            json_encode([$operands, $query->constraints['comparison_entities'] ?? null], JSON_UNESCAPED_UNICODE) ?: '',
        );
    }

    public function test_comparative_milk_and_meat_productions_keep_both_operands(): void
    {
        $query = $this->qus->understand(['query' => 'مقارنة إنتاج الحليب وإنتاج اللحوم']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $csq->relation->type);
        $operands = $query->constraints['comparison_operands'] ?? [];
        $this->assertGreaterThanOrEqual(2, count((array) $operands));
    }

    public function test_freeze_does_not_invent_entity_from_requested_surface(): void
    {
        $csq = $this->csq('العلاقة بين التغذية وإنتاج اللبن');
        $this->assertNull($csq->entity->surface);
        $this->assertNotSame('livestock', $csq->entity->normalized);
        $this->assertNotSame('agriculture', $csq->entity->normalized);
        $this->assertNotNull($csq->target->surface);
        $this->assertNotNull($csq->process->surface);
    }

    public function test_home_and_crop_share_roles_for_identical_question(): void
    {
        $home = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $crop = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]);
        $this->assertNotNull($home->canonicalQuestion);
        $this->assertNotNull($crop->canonicalQuestion);
        $this->assertSame($home->canonicalQuestion->target->surface, $crop->canonicalQuestion->target->surface);
        $this->assertSame($home->canonicalQuestion->process->surface, $crop->canonicalQuestion->process->surface);
        $this->assertSame($home->canonicalQuestion->property->key, $crop->canonicalQuestion->property->key);
        $this->assertSame($home->canonicalQuestion->relation->type, $crop->canonicalQuestion->relation->type);
        $this->assertSame('cattle', $home->canonicalQuestion->entity->normalized);
        $this->assertSame('cattle', $crop->canonicalQuestion->entity->normalized);
        $this->assertSame('wheat', $crop->canonicalQuestion->cropBinding->cropId);
        $this->assertNotSame('wheat', $crop->canonicalQuestion->entity->normalized);
    }

    public function test_original_arabic_causal_graph_survives(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('cattle', $csq->entity->normalized);
        $this->assertSame('إنتاج اللبن', $csq->target->surface);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertSame('quantity', $csq->property->key);
        $this->assertSame('yield', $csq->property->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
        $this->assertSame('causes', $query->constraints['question_type'] ?? null);
        $this->assertNull($csq->target->canonicalId);
    }

    public function test_fertilization_causal_keeps_process(): void
    {
        $csq = $this->csq('تأثير التسميد على إنتاج الذرة');
        $this->assertSame('التسميد', $csq->process->surface);
        $this->assertSame('إنتاج الذرة', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
    }

    public function test_role_invariants_on_full_causal_graph(): void
    {
        $csq = $this->csq('تأثير التغذية على إنتاج اللبن في الأبقار');
        $this->assertNotSame($csq->entity->surface, $csq->target->surface);
        $this->assertNotSame($csq->target->surface, $csq->property->surface);
        $this->assertNotSame($csq->process->surface, $csq->entity->surface);
        $this->assertNotSame($csq->process->surface, $csq->property->surface);
        $this->assertSame('cattle', $csq->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $csq->entity->canonicalNamespace);
        $this->assertNull($csq->target->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
    }

    public function test_crop_binding_is_not_question_entity(): void
    {
        $csq = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
        $this->assertNotSame('wheat', $csq->entity->normalized);
        $this->assertNotSame($csq->cropBinding->cropId, $csq->entity->surface);
    }

    private function csq(string $query): CanonicalScientificQuestion
    {
        $csq = $this->qus->understand(['query' => $query])->canonicalQuestion;
        $this->assertNotNull($csq);

        return $csq;
    }
}
