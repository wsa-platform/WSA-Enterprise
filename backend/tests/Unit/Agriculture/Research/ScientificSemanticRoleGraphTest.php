<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use PHPUnit\Framework\TestCase;

class ScientificSemanticRoleGraphTest extends TestCase
{
    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
    }

    public function test_category_a_entity_target_property_are_independent(): void
    {
        $query = $this->qus->understand(['query' => 'إنتاج القمح']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->target->surface);
        $this->assertStringContainsString('إنتاج', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $csq->target->kind);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNotSame($csq->entity->surface, $csq->target->surface);
        $this->assertNotNull($csq->entity->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
    }

    public function test_category_b_process_target_causal_relation(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('أبقار', $csq->entity->surface);
        $this->assertSame('cattle', $csq->entity->normalized);
        $this->assertSame('إنتاج اللبن', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $csq->target->kind);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertNotSame('feed', $csq->process->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
        $this->assertNull($csq->target->canonicalId);
        $this->assertSame(
            'إنتاج اللبن في الأبقار',
            $query->constraints['causal_target'] ?? null,
        );
    }

    public function test_category_c_entity_property_without_production_target(): void
    {
        $query = $this->qus->understand([
            'query' => 'What is the irrigation requirement for wheat in arid regions?',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->entity->surface);
        $this->assertContains($csq->target->kind, [
            CanonicalScientificQuestion::TARGET_KIND_NONE,
            CanonicalScientificQuestion::TARGET_KIND_AFFECTED_OBJECT,
        ]);
        $this->assertNotSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $csq->target->kind);
    }

    public function test_category_d_unresolved_target_without_entity(): void
    {
        $query = $this->qus->understand(['query' => 'إنتاج الحليب']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('إنتاج الحليب', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNotSame('crop', $query->subject['type'] ?? null);
        $this->assertNotSame('حليب', $query->subject['value'] ?? null);
    }

    public function test_category_e_process_affects_entity_outcome_not_product(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على وزن الأبقار',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertNotNull($csq->target->surface);
        $this->assertStringContainsString('وزن', $csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_AFFECTED_OBJECT, $csq->target->kind);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame('cattle', $csq->entity->normalized);
    }

    public function test_category_f_comparison_sets_relation_without_collapsing_entities(): void
    {
        $query = $this->qus->understand([
            'query' => 'قارن بين القمح والذرة من حيث الإنتاجية.',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertTrue((bool) ($query->constraints['is_comparison'] ?? false));
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $csq->relation->type);
    }

    public function test_category_g_english_causal_production_structure(): void
    {
        $query = $this->qus->understand([
            'query' => 'effect of feeding on milk production in cattle',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->process->surface);
        $this->assertNotNull($csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $csq->target->kind);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
        $this->assertStringNotContainsString('cattle', mb_strtolower((string) $csq->target->surface));
    }

    public function test_category_h_descriptive_question_does_not_invent_target(): void
    {
        $query = $this->qus->understand(['query' => 'What is wheat?']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_NONE, $csq->target->kind);
        $this->assertNull($csq->target->surface);
    }

    public function test_category_i_time_is_copied_when_present(): void
    {
        $query = $this->qus->understand(['query' => 'ما إنتاج القمح في مصر عام 2022؟']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(2022, $csq->time->year);
        $this->assertNotNull($csq->geography->label);
    }

    public function test_category_j_crop_context_does_not_overwrite_question_roles(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE, $csq->researchContext);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
        $this->assertSame('إنتاج اللبن', $csq->target->surface);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertSame('wheat', $query->cropId);
    }

    public function test_category_k_unresolved_product_keeps_surface_without_id(): void
    {
        $query = $this->qus->understand(['query' => 'إنتاج الأبقار']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('إنتاج الأبقار', $csq->target->surface);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNull($csq->target->canonicalNamespace);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
        $this->assertSame('cattle', $csq->entity->normalized);
    }

    public function test_category_l_french_production_structure(): void
    {
        $graph = AgriculturalEntityCatalog::extractProductionOutputStructure('rendement de ble');
        $this->assertNotNull($graph);
        $this->assertSame('quantity', $graph['property_key']);
        $this->assertNotSame('', $graph['output_complement']);
    }

    public function test_invariant_recognized_entity_does_not_erase_target(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير الري على إنتاج القمح',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->target->surface);
        $this->assertStringContainsString('إنتاج', $csq->target->surface);
        $this->assertNotNull($csq->entity->surface);
        $this->assertNotNull($csq->process->surface);
        $this->assertSame('الري', $csq->process->surface);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to);
    }

    public function test_invariant_no_invented_canonical_ids_or_provider_fields(): void
    {
        $query = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('cattle', $csq->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $csq->entity->canonicalNamespace);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNull($csq->process->canonicalId);
        $this->assertNull($csq->evidenceRequirement->requiresFactualDirect);
        $this->assertArrayNotHasKey('canonical_question', $query->toArray());
        $this->assertArrayNotHasKey('openalex', get_object_vars($csq));
        $this->assertArrayNotHasKey('faostat', get_object_vars($csq));
    }

    public function test_invariant_extraction_does_not_depend_on_retrieval_or_evidence(): void
    {
        $first = $this->qus->understand(['query' => 'إنتاج الذرة']);
        $second = $this->qus->understand(['query' => 'إنتاج الذرة']);
        $this->assertNotNull($first->canonicalQuestion);
        $this->assertSame(
            $first->canonicalQuestion->target->surface,
            $second->canonicalQuestion?->target->surface,
        );
        $this->assertSame(
            $first->canonicalQuestion->entity->normalized,
            $second->canonicalQuestion?->entity->normalized,
        );
    }

    public function test_maize_production_keeps_crop_entity_separate_from_target(): void
    {
        $query = $this->qus->understand(['query' => 'إنتاج الذرة']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('إنتاج الذرة', $csq->target->surface);
        $this->assertNotNull($csq->entity->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $csq->property->ofRole);
    }

    public function test_existing_akq_to_array_shape_is_unchanged(): void
    {
        $query = $this->qus->understand(['query' => 'إنتاج القمح']);
        $this->assertSame([
            'original_question',
            'normalized_question',
            'language',
            'agricultural_domain',
            'subject',
            'crop',
            'crop_id',
            'scientific_name',
            'topic',
            'subtopic',
            'requested_information',
            'constraints',
            'location',
            'research_required',
            'ambiguity_state',
            'clarification_requirements',
            'research_intent',
        ], array_keys($query->toArray()));
    }
}
