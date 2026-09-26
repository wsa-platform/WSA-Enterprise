<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use PHPUnit\Framework\TestCase;

class PostCsqSemanticPreservationTest extends TestCase
{
    private RetrievalSemanticContract $rsc;

    private ScientificSearchQueryBuilder $builder;

    private QueryUnderstandingService $qus;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        class_exists(RetrievalSpecification::class);
        $this->rsc = new RetrievalSemanticContract();
        $this->builder = new ScientificSearchQueryBuilder();
        $this->qus = new QueryUnderstandingService();
    }

    public function test_clone_preserves_canonical_question_identity(): void
    {
        $source = $this->qus->understand(['query' => 'إنتاج القمح']);
        $this->assertNotNull($source->canonicalQuestion);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $this->assertSame($source->canonicalQuestion, $applied->normalizedQuery->canonicalQuestion);
    }

    public function test_roles_survive_retrieval_boundary(): void
    {
        $source = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $csq = $source->canonicalQuestion;
        $this->assertNotNull($csq);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $downstream = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($downstream);
        $this->assertSame($csq->entity->surface, $downstream->entity->surface);
        $this->assertSame($csq->target->surface, $downstream->target->surface);
        $this->assertSame($csq->process->surface, $downstream->process->surface);
        $this->assertSame($csq->property->ofRole, $downstream->property->ofRole);
        $this->assertSame($csq->relation->type, $downstream->relation->type);
        $this->assertSame($csq->relation->from, $downstream->relation->from);
        $this->assertSame($csq->relation->to, $downstream->relation->to);
        $this->assertSame('cattle', $downstream->entity->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::NAMESPACE_CATALOG_LIVESTOCK, $downstream->entity->canonicalNamespace);
        $this->assertNull($downstream->target->canonicalId);
    }

    public function test_unresolved_target_survives_compilation(): void
    {
        $source = $this->qus->understand(['query' => 'إنتاج الحليب']);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->target->surface);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $csq->target->resolution);
        $this->assertNull($csq->target->canonicalId);
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(RetrievalSpecification::SOURCE_CSQ, $spec->source);
        $this->assertNotEmpty($spec->conceptsForRole(CanonicalScientificQuestion::ROLE_TARGET));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($applied)[0] ?? '');
        $this->assertNotSame('', $primary);
        $this->assertTrue(
            str_contains($primary, mb_strtolower((string) $csq->target->surface))
            || str_contains($primary, 'production')
            || str_contains($primary, 'حليب'),
            $primary,
        );
    }

    public function test_property_of_role_is_not_rewritten_to_entity(): void
    {
        $source = $this->qus->understand(['query' => 'إنتاج القمح']);
        $this->assertSame(
            CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            $source->canonicalQuestion?->property->ofRole,
        );
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $this->assertSame(
            CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            $applied->normalizedQuery->canonicalQuestion?->property->ofRole,
        );
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $spec->propertyOfRole);
    }

    public function test_causal_endpoints_survive_to_retrieval_planning(): void
    {
        $source = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
        ]);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $spec->relationFrom);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $spec->relationTo);
        $this->assertNotEmpty($spec->conceptsForRole(CanonicalScientificQuestion::ROLE_PROCESS));
        $this->assertNotEmpty($spec->conceptsForRole(CanonicalScientificQuestion::ROLE_TARGET));
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($applied)[0] ?? '');
        $process = mb_strtolower((string) $applied->normalizedQuery->canonicalQuestion?->process->surface);
        $target = mb_strtolower((string) $applied->normalizedQuery->canonicalQuestion?->target->surface);
        $this->assertStringContainsString($process, $primary);
        $this->assertStringContainsString($target, $primary);
    }

    public function test_comparison_relation_survives(): void
    {
        $source = $this->qus->understand([
            'query' => 'قارن بين القمح والذرة من حيث الإنتاجية.',
        ]);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $this->assertSame(
            CanonicalScientificQuestion::RELATION_COMPARATIVE,
            $applied->normalizedQuery->canonicalQuestion?->relation->type,
        );
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(CanonicalScientificQuestion::RELATION_COMPARATIVE, $spec->relationType);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
    }

    public function test_descriptive_question_is_not_compiled_as_causal(): void
    {
        $source = $this->qus->understand(['query' => 'What is wheat?']);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $csq->relation->type);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $csq->relation->type);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_NONE, $csq->target->kind);
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(CanonicalScientificQuestion::RELATION_DESCRIPTIVE, $spec->relationType);
        $this->assertNotSame(CanonicalScientificQuestion::RELATION_CAUSAL, $spec->relationType);
    }

    public function test_crop_binding_does_not_overwrite_question_roles(): void
    {
        $source = $this->qus->understand([
            'query' => 'تأثير التغذية على إنتاج اللبن في الأبقار',
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]);
        $applied = $this->rsc->apply($this->planFromQuery($source, [
            'selected_crop_id' => 'wheat',
            'selected_crop_name' => 'wheat',
        ]));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertSame(CanonicalScientificQuestion::RESEARCH_CONTEXT_CROP_PROFILE, $csq->researchContext);
        $this->assertSame('wheat', $csq->cropBinding->cropId);
        $this->assertSame('إنتاج اللبن', $csq->target->surface);
        $this->assertSame('التغذية', $csq->process->surface);
        $this->assertNotSame('wheat', $csq->entity->normalized);
    }

    public function test_legacy_fields_remain_but_csq_wins_for_compilation(): void
    {
        $source = $this->qus->understand(['query' => 'إنتاج القمح']);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $this->assertArrayHasKey('scientific_topics', $applied->normalizedQuery->constraints);
        $this->assertArrayHasKey('semantic_target', $applied->normalizedQuery->constraints);
        $this->assertSame(
            RetrievalSpecification::SOURCE_CSQ,
            $applied->normalizedQuery->constraints['retrieval_specification']['source'] ?? null,
        );
        $this->assertSame(
            $source->canonicalQuestion?->target->surface,
            $applied->normalizedQuery->canonicalQuestion?->target->surface,
        );
    }

    public function test_primary_variant_keeps_required_roles_from_csq(): void
    {
        $source = $this->qus->understand([
            'query' => 'effect of feeding on milk production in cattle',
        ]);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($applied)[0] ?? '');
        $this->assertNotNull($csq->process->surface);
        $this->assertNotNull($csq->target->surface);
        $this->assertStringContainsString(mb_strtolower($csq->process->surface), $primary);
        $this->assertStringContainsString(mb_strtolower($csq->target->surface), $primary);
        $this->assertLessThanOrEqual(5, count($this->builder->buildVariantsFromPlan($applied)));
    }

    public function test_legacy_required_factor_is_not_dropped_from_primary_without_csq(): void
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'barley irrigation',
            normalizedQuestion: 'barley irrigation',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'barley'],
            crop: 'barley',
            cropId: 'barley',
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'requested_property_surface' => 'water requirement',
                'requested_property_key' => 'quantity',
                'scientific_sense' => 'crop_water_requirement',
                'scientific_factors' => ['drought'],
                'requested_property_query_terms' => ['yield', 'production'],
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'irrigation',
        );
        $applied = $this->rsc->apply($this->planFromQuery($query));
        $this->assertNull($applied->normalizedQuery->canonicalQuestion);
        $this->assertSame(
            RetrievalSpecification::SOURCE_LEGACY,
            $applied->normalizedQuery->constraints['retrieval_specification']['source'] ?? null,
        );
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($applied)[0] ?? '');
        $this->assertStringContainsString('barley', $primary);
        $this->assertTrue(str_contains($primary, 'irrigation') || str_contains($primary, 'water'));
        $this->assertStringContainsString('drought', $primary);
    }

    public function test_supporting_sense_survives_property_first_ordering(): void
    {
        $source = $this->qus->understand([
            'query' => 'What is the suitable temperature for zirqon seed germination?',
        ]);
        $applied = $this->rsc->apply($this->planFromQuery($source));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertNotNull($csq->entity->surface);
        $primary = mb_strtolower($this->builder->buildVariantsFromPlan($applied)[0] ?? '');
        $this->assertStringContainsString(mb_strtolower((string) $csq->entity->surface), $primary);
        $this->assertTrue(
            str_contains($primary, 'temperature') || str_contains($primary, 'thermal'),
            $primary,
        );
        $sense = mb_strtolower((string) ($csq->context->scientificSense ?? ''));
        if ($sense !== '') {
            $this->assertTrue(
                str_contains($primary, 'germinat') || str_contains($primary, str_replace('_', ' ', $sense)),
                $primary,
            );
        }
    }

    public function test_crop_knowledge_option_without_independent_roles_keeps_existing_variant_family(): void
    {
        $planner = new ResearchPlanner($this->qus);
        $plan = $planner->planKnowledgeQuery([
            'query' => 'sweet potato farming needs',
            'selected_crop_id' => 'sweet-potato',
            'selected_crop_name' => 'Sweet potato',
            'knowledge_option' => 'farming-needs',
        ]);
        $applied = $this->rsc->apply($plan);
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($csq);
        $this->assertFalse(
            RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification'])
                ->hasIndependentQuestionRoles,
        );
        $variants = $this->builder->buildVariantsFromPlan($applied);
        $joined = mb_strtolower(implode(' | ', $variants));
        $this->assertTrue(
            str_contains($joined, 'ipomoea batatas') || str_contains($joined, 'sweet potato'),
            $joined,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function planFromQuery(AgriculturalKnowledgeQuery $query, array $context = []): KnowledgeQueryPlan
    {
        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $query->researchIntent,
            agriculturalDomain: $query->agriculturalDomain,
            subjectEntity: $query->subject,
            topics: [$query->topic],
            subtopics: $query->subtopic !== null ? [$query->subtopic] : [],
            requestedInformation: $query->requestedInformation,
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: $query->ambiguityState,
            clarificationRequirements: $query->clarificationRequirements,
            contextInput: $context,
            readyForStage3: true,
        );
    }
}
