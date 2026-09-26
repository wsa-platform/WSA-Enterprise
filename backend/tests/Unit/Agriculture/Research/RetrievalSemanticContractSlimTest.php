<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqCropBinding;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\RetrievalSemanticContract;
use App\Services\Agriculture\Research\RetrievalSpecification;
use PHPUnit\Framework\TestCase;

class RetrievalSemanticContractSlimTest extends TestCase
{
    private RetrievalSemanticContract $rsc;

    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
        $this->rsc = new RetrievalSemanticContract();
    }

    public function test_clone_preserves_csq_object_identity(): void
    {
        $csq = $this->causalCsq();
        $applied = $this->rsc->apply($this->plan($csq));
        $this->assertSame($csq, $applied->normalizedQuery->canonicalQuestion);
    }

    public function test_csq_roles_survive_apply_unchanged(): void
    {
        $csq = $this->causalCsq();
        $applied = $this->rsc->apply($this->plan($csq));
        $out = $applied->normalizedQuery->canonicalQuestion;
        $this->assertNotNull($out);
        $this->assertSame($csq->entity->surface, $out->entity->surface);
        $this->assertSame($csq->target->surface, $out->target->surface);
        $this->assertSame($csq->process->surface, $out->process->surface);
        $this->assertSame($csq->property->key, $out->property->key);
        $this->assertSame($csq->property->surface, $out->property->surface);
        $this->assertSame($csq->property->ofRole, $out->property->ofRole);
        $this->assertSame($csq->relation->type, $out->relation->type);
        $this->assertSame($csq->relation->from, $out->relation->from);
        $this->assertSame($csq->relation->to, $out->relation->to);
    }

    public function test_entity_and_target_stay_separate(): void
    {
        $applied = $this->rsc->apply($this->plan($this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
        )));
        $csq = $applied->normalizedQuery->canonicalQuestion;
        $this->assertSame('cattle', $csq?->entity->surface);
        $this->assertSame('milk production', $csq?->target->surface);
        $this->assertNotSame($csq?->entity->surface, $csq?->target->surface);
    }

    public function test_process_is_not_replaced_by_intent(): void
    {
        $applied = $this->rsc->apply($this->plan($this->csq(
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
        ), intent: 'cultivation'));
        $this->assertSame('feeding', $applied->normalizedQuery->canonicalQuestion?->process->surface);
        $this->assertSame('cultivation', $applied->researchIntent);
    }

    public function test_property_key_surface_and_of_role_survive(): void
    {
        $applied = $this->rsc->apply($this->plan($this->csq(
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            ),
        )));
        $property = $applied->normalizedQuery->canonicalQuestion?->property;
        $this->assertSame('quantity', $property?->key);
        $this->assertSame('yield', $property?->surface);
        $this->assertSame(CanonicalScientificQuestion::PROPERTY_OF_TARGET, $property?->ofRole);
    }

    public function test_causal_endpoints_survive(): void
    {
        $applied = $this->rsc->apply($this->plan($this->causalCsq()));
        $relation = $applied->normalizedQuery->canonicalQuestion?->relation;
        $this->assertSame(CanonicalScientificQuestion::RELATION_CAUSAL, $relation?->type);
        $this->assertSame(CanonicalScientificQuestion::ROLE_PROCESS, $relation?->from);
        $this->assertSame(CanonicalScientificQuestion::ROLE_TARGET, $relation?->to);
    }

    public function test_non_causal_relations_are_not_converted(): void
    {
        foreach ([
            CanonicalScientificQuestion::RELATION_COMPARATIVE,
            CanonicalScientificQuestion::RELATION_ASSOCIATIVE,
            CanonicalScientificQuestion::RELATION_DESCRIPTIVE,
        ] as $type) {
            $applied = $this->rsc->apply($this->plan($this->csq(
                relation: CanonicalScientificQuestion::relation(type: $type),
            )));
            $this->assertSame($type, $applied->normalizedQuery->canonicalQuestion?->relation->type);
        }
    }

    public function test_unresolved_target_keeps_surface_and_null_id(): void
    {
        $applied = $this->rsc->apply($this->plan($this->csq(
            target: CanonicalScientificQuestion::target(
                surface: 'إنتاج اللبن',
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        )));
        $target = $applied->normalizedQuery->canonicalQuestion?->target;
        $this->assertSame('إنتاج اللبن', $target?->surface);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $target?->resolution);
        $this->assertNull($target?->canonicalId);
    }

    public function test_crop_binding_does_not_replace_question_entity(): void
    {
        $csq = $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            cropBinding: new CsqCropBinding(cropId: 'wheat', cropLabel: 'wheat'),
        );
        $applied = $this->rsc->apply($this->plan($csq, [], cropId: 'wheat'));
        $out = $applied->normalizedQuery->canonicalQuestion;
        $this->assertSame('cattle', $out?->entity->surface);
        $this->assertSame('milk production', $out?->target->surface);
        $this->assertSame('wheat', $out?->cropBinding->cropId);
        $this->assertSame('wheat', $applied->normalizedQuery->cropId);
    }

    public function test_conflicting_legacy_fields_do_not_materialize_into_csq_or_topics(): void
    {
        $csq = $this->causalCsq();
        $incomingTerms = ['photosynthesis'];
        $incomingTopics = ['agriculture'];
        $applied = $this->rsc->apply($this->plan($csq, [
            'requested_property' => 'temperature',
            'requested_property_query_terms' => $incomingTerms,
            'scientific_topics' => ['climate change'],
            'scientific_sense' => 'seed_germination',
            'semantic_target' => ['entity_surface' => 'wheat'],
        ], cropId: 'wheat', intent: 'irrigation', topics: $incomingTopics));

        $this->assertSame($csq, $applied->normalizedQuery->canonicalQuestion);
        $this->assertSame($incomingTerms, $applied->normalizedQuery->constraints['requested_property_query_terms'] ?? null);
        $this->assertSame($incomingTopics, $applied->topics);
        $this->assertArrayNotHasKey('retrieval_property_role', $applied->normalizedQuery->constraints);
        $this->assertArrayNotHasKey('mandatory_retrieval_terms', $applied->normalizedQuery->constraints);
        $this->assertSame(
            RetrievalSpecification::SOURCE_CSQ,
            $applied->normalizedQuery->constraints['retrieval_specification']['source'] ?? null,
        );
        $this->assertSame('cattle', $applied->normalizedQuery->constraints['retrieval_specification']['projections']['entity']['surface'] ?? null);
        $this->assertSame('yield', $applied->normalizedQuery->constraints['retrieval_specification']['projections']['property']['surface'] ?? null);
        $this->assertSame('quantity', $applied->normalizedQuery->constraints['retrieval_specification']['projections']['property']['key'] ?? null);
    }

    public function test_spec_is_derived_from_csq_not_legacy(): void
    {
        $applied = $this->rsc->apply($this->plan($this->causalCsq(), [
            'target_surface' => 'wheat production',
            'process_surface' => 'irrigation',
        ]));
        $spec = RetrievalSpecification::fromArray($applied->normalizedQuery->constraints['retrieval_specification']);
        $this->assertSame(RetrievalSpecification::SOURCE_CSQ, $spec->source);
        $this->assertSame('milk production', $spec->projections['target']['surface']);
        $this->assertSame('feeding', $spec->projections['process']['surface']);
        $this->assertNotContains('effect', $spec->requiredTerms());
    }

    public function test_null_csq_still_materializes_legacy_water_role(): void
    {
        $applied = $this->rsc->apply($this->plan(null, [
            'requested_property_surface' => 'water requirement',
            'requested_property_key' => 'quantity',
            'scientific_sense' => 'crop_water_requirement',
            'requested_property_query_terms' => ['yield', 'production'],
        ], cropId: 'barley', intent: 'irrigation'));
        $this->assertNull($applied->normalizedQuery->canonicalQuestion);
        $this->assertSame(
            RetrievalSemanticContract::ROLE_IRRIGATION_WATER,
            $applied->normalizedQuery->constraints['retrieval_property_role'] ?? null,
        );
        $this->assertNotContains('yield', $applied->normalizedQuery->constraints['requested_property_query_terms'] ?? []);
        $this->assertContains('irrigation', $applied->normalizedQuery->constraints['requested_property_query_terms'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $constraints
     * @param  list<string>  $topics
     */
    private function plan(
        ?CanonicalScientificQuestion $csq,
        array $constraints = [],
        ?string $cropId = null,
        string $intent = 'scientific_research',
        array $topics = ['scientific_research'],
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'probe',
            normalizedQuestion: 'probe',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            crop: $cropId,
            cropId: $cropId,
            scientificName: null,
            topic: $intent,
            subtopic: null,
            requestedInformation: [],
            constraints: $constraints,
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
            canonicalQuestion: $csq,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agronomy',
            subjectEntity: $cropId !== null ? ['type' => 'crop', 'value' => $cropId] : null,
            topics: $topics,
            subtopics: [],
            requestedInformation: [],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            contextInput: [],
            readyForStage3: true,
        );
    }

    private function causalCsq(): CanonicalScientificQuestion
    {
        return $this->csq(
            entity: CanonicalScientificQuestion::entity(surface: 'cattle'),
            process: CanonicalScientificQuestion::process(surface: 'feeding'),
            target: CanonicalScientificQuestion::target(surface: 'milk production'),
            property: CanonicalScientificQuestion::property(
                key: 'quantity',
                surface: 'yield',
                ofRole: CanonicalScientificQuestion::PROPERTY_OF_TARGET,
            ),
            relation: CanonicalScientificQuestion::relation(
                type: CanonicalScientificQuestion::RELATION_CAUSAL,
                from: CanonicalScientificQuestion::ROLE_PROCESS,
                to: CanonicalScientificQuestion::ROLE_TARGET,
            ),
        );
    }

    private function csq(
        mixed $entity = null,
        mixed $target = null,
        mixed $process = null,
        mixed $property = null,
        mixed $relation = null,
        ?CsqCropBinding $cropBinding = null,
    ): CanonicalScientificQuestion {
        return new CanonicalScientificQuestion(
            originalQuestion: 'probe',
            language: 'en',
            normalizedForm: 'probe',
            researchContext: CanonicalScientificQuestion::RESEARCH_CONTEXT_HOME,
            entity: $entity ?? CanonicalScientificQuestion::entity(),
            target: $target ?? CanonicalScientificQuestion::target(),
            process: $process ?? CanonicalScientificQuestion::process(),
            property: $property ?? CanonicalScientificQuestion::property(),
            relation: $relation ?? CanonicalScientificQuestion::relation(),
            cropBinding: $cropBinding ?? new CsqCropBinding(),
        );
    }
}
