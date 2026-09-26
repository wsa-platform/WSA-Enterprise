<?php

namespace Tests\Unit\Agriculture\Research;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\CsqEntity;
use App\Services\Agriculture\Research\CsqProcess;
use App\Services\Agriculture\Research\CsqProperty;
use App\Services\Agriculture\Research\CsqRelation;
use App\Services\Agriculture\Research\CsqTarget;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class CanonicalScientificQuestionPhase1Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        class_exists(CanonicalScientificQuestion::class);
    }

    public function test_csq_can_be_instantiated_unpopulated(): void
    {
        $csq = CanonicalScientificQuestion::unpopulated(
            originalQuestion: 'تأثير التغذية على إنتاج اللبن في الأبقار',
            language: 'ar',
        );

        $this->assertSame('تأثير التغذية على إنتاج اللبن في الأبقار', $csq->originalQuestion);
        $this->assertSame('ar', $csq->language);
        $this->assertSame('', $csq->normalizedForm);
        $this->assertSame('', $csq->researchContext);
    }

    public function test_csq_and_role_containers_are_readonly(): void
    {
        $this->assertTrue((new ReflectionClass(CanonicalScientificQuestion::class))->isReadOnly());
        $this->assertTrue((new ReflectionClass(CsqEntity::class))->isReadOnly());
        $this->assertTrue((new ReflectionClass(CsqTarget::class))->isReadOnly());
        $this->assertTrue((new ReflectionClass(CsqProcess::class))->isReadOnly());
        $this->assertTrue((new ReflectionClass(CsqProperty::class))->isReadOnly());
        $this->assertTrue((new ReflectionClass(CsqRelation::class))->isReadOnly());

        foreach ($this->publicMethodNames(CanonicalScientificQuestion::class) as $method) {
            $this->assertDoesNotMatchRegularExpression(
                '/^set[A-Z]/',
                $method,
                'CSQ must not expose mutation setters',
            );
        }
    }

    public function test_core_semantic_roles_remain_distinguishable(): void
    {
        $csq = CanonicalScientificQuestion::unpopulated('q', 'en');

        $this->assertInstanceOf(CsqEntity::class, $csq->entity);
        $this->assertInstanceOf(CsqTarget::class, $csq->target);
        $this->assertInstanceOf(CsqProcess::class, $csq->process);
        $this->assertInstanceOf(CsqProperty::class, $csq->property);
        $this->assertInstanceOf(CsqRelation::class, $csq->relation);
        $this->assertNotSame($csq->entity, $csq->target);
        $this->assertNotSame($csq->target, $csq->process);
        $this->assertNotSame($csq->process, $csq->property);
        $this->assertSame(CanonicalScientificQuestion::ROLE_ENTITY, $csq->entity->role);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_NONE, $csq->target->kind);
        $this->assertSame(CanonicalScientificQuestion::RELATION_NONE, $csq->relation->type);
        $this->assertNull($csq->property->ofRole);
    }

    public function test_optional_roles_and_context_containers_exist(): void
    {
        $csq = CanonicalScientificQuestion::unpopulated('q', 'en');

        $this->assertSame([], $csq->context->requestedInformation);
        $this->assertSame([], $csq->conditions->items);
        $this->assertNull($csq->time->year);
        $this->assertNull($csq->geography->label);
        $this->assertNull($csq->evidenceRequirement->requiredEvidenceType);
        $this->assertNull($csq->evidenceRequirement->requiresFactualDirect);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_NONE, $csq->resolution->ambiguityState);
        $this->assertSame([], $csq->resolution->clarificationRequirements);
        $this->assertNull($csq->cropBinding->cropId);
    }

    public function test_canonical_ids_default_to_null_and_are_not_invented(): void
    {
        $empty = CanonicalScientificQuestion::unpopulated('q', 'en');
        $this->assertNull($empty->entity->canonicalId);
        $this->assertNull($empty->entity->canonicalNamespace);
        $this->assertNull($empty->target->canonicalId);
        $this->assertNull($empty->target->canonicalNamespace);
        $this->assertNull($empty->process->canonicalId);
        $this->assertNull($empty->process->canonicalNamespace);
        $this->assertNull($empty->geography->canonicalId);

        $provided = new CanonicalScientificQuestion(
            originalQuestion: 'q',
            language: 'en',
            entity: CanonicalScientificQuestion::entity(
                surface: 'cattle',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'verified-entity',
                canonicalNamespace: 'internal',
            ),
            target: CanonicalScientificQuestion::target(
                surface: 'milk',
                kind: CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT,
                resolution: CanonicalScientificQuestion::RESOLUTION_UNRESOLVED,
            ),
        );

        $this->assertSame('verified-entity', $provided->entity->canonicalId);
        $this->assertNull($provided->target->canonicalId);
        $this->assertSame(CanonicalScientificQuestion::RESOLUTION_UNRESOLVED, $provided->target->resolution);
        $this->assertSame(CanonicalScientificQuestion::TARGET_KIND_PRODUCT_OUTPUT, $provided->target->kind);
    }

    public function test_csq_does_not_require_provider_or_evidence_fields(): void
    {
        $forbidden = [
            'openalex',
            'crossref',
            'semantic_scholar',
            'consensus',
            'faostat',
            'qcl',
            'provider',
            'variant',
            'citation',
            'primary_answer',
            'doi',
            'url',
            'score',
        ];

        foreach ($this->declaredPropertyNames(CanonicalScientificQuestion::class) as $name) {
            $folded = strtolower($name);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $folded);
            }
        }
    }

    public function test_agricultural_knowledge_query_can_carry_optional_csq(): void
    {
        $csq = CanonicalScientificQuestion::unpopulated('wheat yield', 'en', 'wheat yield', 'home');
        $query = $this->knowledgeQuery(canonicalQuestion: $csq);

        $this->assertSame($csq, $query->canonicalQuestion);
        $this->assertTrue($query->isClear());
        $this->assertFalse($query->needsClarification());
    }

    public function test_existing_agricultural_knowledge_query_construction_remains_compatible(): void
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'wheat yield',
            normalizedQuestion: 'wheat yield',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'yield',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
        );

        $this->assertNull($query->canonicalQuestion);
        $this->assertSame('wheat yield', $query->originalQuestion);
        $this->assertSame('en', $query->language);
        $this->assertTrue($query->isClear());
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
        $this->assertArrayNotHasKey('canonical_question', $query->toArray());
        $this->assertArrayNotHasKey('canonicalQuestion', $query->toArray());
    }

    public function test_akq_canonical_question_constructor_argument_is_optional(): void
    {
        $parameter = (new ReflectionClass(AgriculturalKnowledgeQuery::class))
            ->getConstructor()
            ?->getParameters();
        $this->assertNotNull($parameter);
        $last = $parameter[array_key_last($parameter)];
        $this->assertSame('canonicalQuestion', $last->getName());
        $this->assertTrue($last->isOptional());
        $this->assertTrue($last->allowsNull());
        $this->assertTrue($last->isDefaultValueAvailable());
        $this->assertNull($last->getDefaultValue());
        $type = $last->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $type);
        $this->assertSame(CanonicalScientificQuestion::class, $type->getName());
    }

    /**
     * @return list<string>
     */
    private function publicMethodNames(string $class): array
    {
        $names = [];
        foreach ((new ReflectionClass($class))->getMethods() as $method) {
            if ($method->isPublic()) {
                $names[] = $method->getName();
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function declaredPropertyNames(string $class): array
    {
        return array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($class))->getProperties(),
        );
    }

    private function knowledgeQuery(?CanonicalScientificQuestion $canonicalQuestion = null): AgriculturalKnowledgeQuery
    {
        return new AgriculturalKnowledgeQuery(
            originalQuestion: 'wheat yield',
            normalizedQuestion: 'wheat yield',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'crop', 'value' => 'wheat'],
            crop: 'wheat',
            cropId: 'wheat',
            scientificName: null,
            topic: 'yield',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: $canonicalQuestion,
        );
    }
}
