<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchVariantBudget;
use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use App\Services\Agriculture\Research\Synthesis\ScientificUserPresentation;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CsqArchitecturalClosureTest extends TestCase
{
    private QueryUnderstandingService $qus;

    private ResearchPlanner $planner;

    private RetrievalSemanticContract $rsc;

    private ScientificSearchQueryBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->qus = new QueryUnderstandingService();
        $this->planner = new ResearchPlanner($this->qus);
        $this->rsc = new RetrievalSemanticContract();
        $this->builder = new ScientificSearchQueryBuilder();
    }

    public function test_csq_is_readonly_and_has_no_setters(): void
    {
        $this->assertTrue((new \ReflectionClass(CanonicalScientificQuestion::class))->isReadOnly());
        foreach ((new \ReflectionClass(CanonicalScientificQuestion::class))->getMethods() as $method) {
            $this->assertDoesNotMatchRegularExpression('/^set[A-Z]/', $method->getName());
        }
    }

    public function test_planner_reads_csq_and_ignores_conflicting_legacy_topics(): void
    {
        $query = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $this->assertNotNull($query->canonicalQuestion);
        $contaminated = new AgriculturalKnowledgeQuery(
            originalQuestion: $query->originalQuestion,
            normalizedQuestion: $query->normalizedQuestion,
            language: $query->language,
            agriculturalDomain: $query->agriculturalDomain,
            subject: $query->subject,
            crop: $query->crop,
            cropId: $query->cropId,
            scientificName: $query->scientificName,
            topic: 'photosynthesis',
            subtopic: $query->subtopic,
            requestedInformation: $query->requestedInformation,
            constraints: array_merge($query->constraints, [
                'scientific_topics' => ['climate change', 'photosynthesis'],
            ]),
            location: $query->location,
            researchRequired: $query->researchRequired,
            ambiguityState: $query->ambiguityState,
            clarificationRequirements: $query->clarificationRequirements,
            researchIntent: 'cultivation',
            canonicalQuestion: $query->canonicalQuestion,
        );
        $plan = $this->planner->planKnowledgeQuery(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $this->assertSame($query->canonicalQuestion?->target->surface, $plan->normalizedQuery->canonicalQuestion?->target->surface);
        $this->assertSame($query->canonicalQuestion?->process->surface, $plan->normalizedQuery->canonicalQuestion?->process->surface);
        $joined = mb_strtolower(implode(' ', $plan->topics));
        $this->assertStringNotContainsString('photosynthesis', $joined);
        $this->assertStringNotContainsString('climate change', $joined);
        $this->assertSame('photosynthesis', $contaminated->topic);
        $this->assertContains($query->canonicalQuestion?->process->surface, $plan->topics);
        $this->assertContains($query->canonicalQuestion?->target->surface, $plan->topics);
    }

    public function test_builder_does_not_fall_back_to_legacy_when_csq_exists(): void
    {
        $plan = $this->planner->planKnowledgeQuery(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $applied = $this->rsc->apply($plan);
        $clean = $this->builder->buildVariantsFromPlan($applied);
        $hijacked = $applied->normalizedQuery->copyPreservingCanonical(
            array_merge($applied->normalizedQuery->constraints, [
                'scientific_topics' => ['photosynthesis'],
                'requested_property' => 'temperature',
                'requested_property_key' => 'temperature',
                'scientific_sense' => 'plant_physiology',
            ]),
            $applied->normalizedQuery->crop,
            $applied->normalizedQuery->cropId,
        );
        $hijackedPlan = new \App\Services\Agriculture\Research\KnowledgeQueryPlan(
            normalizedQuery: $hijacked,
            researchIntent: 'cultivation',
            agriculturalDomain: $applied->agriculturalDomain,
            subjectEntity: $applied->subjectEntity,
            topics: ['photosynthesis', 'climate change'],
            subtopics: $applied->subtopics,
            requestedInformation: $applied->requestedInformation,
            evidenceRequirements: $applied->evidenceRequirements,
            sourcePriorities: $applied->sourcePriorities,
            primaryResearchStrategy: $applied->primaryResearchStrategy,
            researchSequence: $applied->researchSequence,
            ambiguityState: $applied->ambiguityState,
            clarificationRequirements: $applied->clarificationRequirements,
            contextInput: $applied->contextInput,
            readyForStage3: true,
        );
        $contaminated = $this->builder->buildVariantsFromPlan($hijackedPlan);
        $this->assertSame($clean, $contaminated);
        $primary = mb_strtolower($contaminated[0] ?? '');
        $this->assertStringNotContainsString('photosynthesis', $primary);
        $this->assertStringNotContainsString('temperature', $primary);
    }

    public function test_to_array_omits_csq_and_copy_preserves_it(): void
    {
        $query = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $this->assertNotNull($query->canonicalQuestion);
        $this->assertArrayNotHasKey('canonical_question', $query->toArray());
        $copy = $query->copyPreservingCanonical($query->constraints, $query->crop, $query->cropId);
        $this->assertSame($query->canonicalQuestion, $copy->canonicalQuestion);
    }

    public function test_rsc_and_accuracy_gate_preserve_csq(): void
    {
        $plan = $this->planner->planKnowledgeQuery(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $applied = $this->rsc->apply($plan);
        $this->assertSame($plan->normalizedQuery->canonicalQuestion, $applied->normalizedQuery->canonicalQuestion);
        $gate = new AnswerExpressionAccuracyGate();
        $method = new ReflectionMethod($gate, 'planWithClaimProperty');
        $method->setAccessible(true);
        $rewritten = $method->invoke($gate, $applied, 'quantity');
        $this->assertSame($plan->normalizedQuery->canonicalQuestion, $rewritten->normalizedQuery->canonicalQuestion);
    }

    public function test_comparative_operands_are_first_class_and_compiled(): void
    {
        $query = $this->qus->understand(['query' => 'مقارنة إنتاج القمح والذرة']);
        $csq = $query->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $csq->relation->type);
        $this->assertNotEmpty($csq->relation->operands);
        $surfaces = array_map(static fn (array $op): string => mb_strtolower((string) $op['surface']), $csq->relation->operands);
        $joined = implode(' ', $surfaces);
        $this->assertTrue(str_contains($joined, 'قمح') || str_contains($joined, 'wheat'), $joined);
        $this->assertTrue(str_contains($joined, 'ذرة') || str_contains($joined, 'maize') || str_contains($joined, 'corn'), $joined);
        foreach ($csq->relation->operands as $operand) {
            $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $operand['resolution']);
            $this->assertNull($operand['normalized']);
        }
        $spec = RetrievalSpecification::fromCanonical($csq);
        $this->assertSame($csq->relation->operands, $spec->projections['relation']['operands']);
        $this->assertNotEmpty($spec->conceptsForRole('comparison_operand'));
        $plan = $this->planner->planKnowledgeQuery(['query' => 'مقارنة إنتاج القمح والذرة']);
        $variants = implode(' ', $this->builder->buildVariantsFromPlan($this->rsc->apply($plan)));
        foreach ($csq->relation->operands as $operand) {
            $this->assertStringContainsString($operand['surface'], $variants);
        }
    }

    public function test_verified_livestock_id_and_unresolved_target(): void
    {
        $csq = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار'])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame('cattle', $csq->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $csq->entity->canonicalNamespace);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_RESOLVED, $csq->entity->resolution);
        $this->assertNotNull($csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
        $this->assertNull($csq->target->canonicalId);
        $this->assertNull($csq->process->canonicalId);
    }

    public function test_unresolved_entity_has_no_invented_id(): void
    {
        $csq = $this->qus->understand(['query' => 'تأثير التسميد على إنتاج محصول غير معروف'])->canonicalQuestion;
        $this->assertNotNull($csq);
        if ($csq->entity->surface !== null) {
            $this->assertNull($csq->entity->canonicalId);
            $this->assertContains($csq->entity->resolution, [
                CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
                CanonicalScientificQuestion::RESOLUTION_NONE,
            ]);
        }
    }

    public function test_requested_property_key_is_unified(): void
    {
        $query = $this->qus->understand(['query' => 'تأثير الري على إنتاج القمح']);
        $this->assertNotSame('', trim((string) ($query->constraints['requested_property'] ?? '')));
        $this->assertSame($query->constraints['requested_property'], $query->constraints['requested_property_key'] ?? null);
    }

    public function test_r13_generic_process_is_not_frozen_as_entity(): void
    {
        $this->assertTrue(CanonicalScientificQuestion::isGenericProcessSurface('irrigation'));
        $this->assertFalse(CanonicalScientificQuestion::isGenericProcessSurface('cattle'));
    }

    public function test_r7_irrigation_property_is_not_rewritten_to_yield(): void
    {
        $csq = $this->qus->understand(['query' => 'ما معدل الري للقمح'])->canonicalQuestion;
        $this->assertNotNull($csq);
        if ($csq->property->key === 'irrigation') {
            $this->assertFalse(CanonicalScientificQuestion::isProductivityFallbackSurface($csq->property->surface));
        }
    }

    public function test_legacy_path_only_when_csq_absent(): void
    {
        $legacy = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley irrigation',
            normalizedQuestion: 'barley irrigation',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley', 'label' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: ['scientific_topics' => ['irrigation'], 'requested_property' => 'irrigation'],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );
        $this->assertNull($legacy->canonicalQuestion);
        $plan = new \App\Services\Agriculture\Research\KnowledgeQueryPlan(
            normalizedQuery: $legacy,
            researchIntent: 'irrigation',
            agriculturalDomain: 'agronomy',
            subjectEntity: $legacy->subject,
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: \App\Services\Agriculture\Research\KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: \App\Services\Agriculture\Research\KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($plan)[0] ?? '');
        $this->assertStringContainsString('barley', $primary);
    }

    public function test_variant_budget_is_respected(): void
    {
        $plan = $this->rsc->apply($this->planner->planKnowledgeQuery([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]));
        $variants = $this->builder->buildVariantsFromPlan($plan);
        $this->assertLessThanOrEqual(5, count($variants));
        $this->assertLessThanOrEqual(ScientificSearchVariantBudget::MAX_VARIANTS, count(ScientificSearchVariantBudget::apply($variants)));
    }

    public function test_protected_thresholds_and_direct_cache_remain_untouched(): void
    {
        $this->assertSame(0.50, ScientificUserPresentation::FINAL_ANSWER_CONFIDENCE_THRESHOLD);
        $this->assertSame(0.50, ScientificUserPresentation::SEARCH_RESULT_CONFIDENCE_THRESHOLD);
        $csq = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار'])->canonicalQuestion;
        $this->assertNull($csq?->evidenceRequirement->requiresFactualDirect);
        $composer = new \ReflectionClass(\App\Services\Agriculture\Research\Synthesis\AnswerComposer::class);
        $this->assertTrue($composer->hasMethod('requiresFactualDirectEvidence'));
    }

    public function test_rsc_apply_is_not_bypassed(): void
    {
        $apply = new ReflectionMethod(RetrievalSemanticContract::class, 'apply');
        $this->assertFalse($apply->isAbstract());
        $plan = $this->planner->planKnowledgeQuery(['query' => 'تأثير الري على إنتاج القمح']);
        $applied = $this->rsc->apply($plan);
        $this->assertNotSame(spl_object_id($plan), spl_object_id($applied));
        $this->assertSame($plan->normalizedQuery->canonicalQuestion, $applied->normalizedQuery->canonicalQuestion);
        $this->assertArrayHasKey('retrieval_specification', $applied->normalizedQuery->constraints);
    }

    public function test_multilingual_causal_roles_share_relation_type(): void
    {
        $graphs = [];
        foreach ([
            'ar' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'en' => 'Effect of nutrition on milk production in cattle',
            'fr' => 'Effet de la nutrition sur la production de lait chez les bovins',
            'tr' => 'Beslenmenin sığırlarda süt üretimine etkisi',
        ] as $lang => $query) {
            $csq = $this->qus->understand(['query' => $query])->canonicalQuestion;
            $this->assertNotNull($csq, $lang);
            $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type, $lang);
            $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $csq->relation->from, $lang);
            $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $csq->relation->to, $lang);
            $this->assertSame('cattle', $csq->entity->canonicalId, $lang);
            $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $csq->entity->canonicalNamespace, $lang);
            $this->assertNotNull($csq->process->surface, $lang);
            $this->assertNotNull($csq->target->surface, $lang);
            $this->assertNull($csq->target->canonicalId, $lang);
            $this->assertNull($csq->process->canonicalId, $lang);
            $this->assertSame('quantity', $csq->property->key, $lang);
            $target = mb_strtolower((string) $csq->target->surface);
            $this->assertTrue(
                str_contains($target, 'لبن')
                || str_contains($target, 'milk')
                || str_contains($target, 'lait')
                || str_contains($target, 'süt'),
                $lang.' target='.$target,
            );
            $process = mb_strtolower((string) $csq->process->surface);
            $this->assertTrue(
                str_contains($process, 'تغذ')
                || str_contains($process, 'nutrition')
                || str_contains($process, 'beslenme'),
                $lang.' process='.$process,
            );
            $graphs[$lang] = $csq;
        }
        $this->assertSame($graphs['ar']->entity->canonicalId, $graphs['fr']->entity->canonicalId);
        $this->assertSame($graphs['en']->entity->canonicalId, $graphs['tr']->entity->canonicalId);
    }

    public function test_home_crop_isolation_and_crop_binding(): void
    {
        $home = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار']);
        $crop = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'Wheat',
            'knowledge_option' => 'farming-needs',
        ]);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME, $home->canonicalQuestion?->researchContext);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE, $crop->canonicalQuestion?->researchContext);
        $this->assertSame('wheat', $crop->canonicalQuestion?->cropBinding->cropId);
        $this->assertNotSame('wheat', $crop->canonicalQuestion?->entity->normalized);
        $this->assertSame($home->canonicalQuestion?->target->surface, $crop->canonicalQuestion?->target->surface);
    }

    public function test_descriptive_and_associative_and_unresolved_process(): void
    {
        $descriptive = $this->qus->understand(['query' => 'What is wheat?'])->canonicalQuestion;
        $this->assertNotNull($descriptive);
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $descriptive->relation->type);
        $associative = $this->qus->understand(['query' => 'relationship between irrigation and wheat production'])->canonicalQuestion;
        $this->assertNotNull($associative);
        $this->assertSame(CanonicalScientificQuestion::RELATION_ASSOCIATIVE, $associative->relation->type);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $associative->relation->type);
        if ($associative->process->surface !== null) {
            $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $associative->process->resolution);
            $this->assertNull($associative->process->canonicalId);
        }
    }

    public function test_faostat_incomplete_filter_constant_and_no_qcl_on_csq(): void
    {
        $csq = $this->qus->understand(['query' => 'تأثير التغذية على إنتاج اللبن في الأبقار'])->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNull($csq->target->canonicalId);
        $encoded = json_encode($csq->entity);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('faostat', mb_strtolower($encoded));
        $this->assertTrue(class_exists(\App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory::class));
        $this->assertSame(
            'INCOMPLETE_FILTERS',
            \App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory::INCOMPLETE_FILTERS,
        );
    }
}
