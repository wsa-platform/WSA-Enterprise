<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityEvidenceFreshness;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRecordId;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilityState;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\Coexistence\CoexistenceMode;
use App\Services\Agriculture\Research\Coexistence\CoexistenceRetrievalPlan;
use App\Services\Agriculture\Research\Coexistence\CoexistenceSourcePlanEntry;
use App\Services\Agriculture\Research\Coexistence\Stage3SourceKeyResolution;
use App\Services\Agriculture\Research\CsqEntity;
use App\Services\Agriculture\Research\CsqGeography;
use App\Services\Agriculture\Research\CsqProperty;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\ExternalIdentityKind;
use App\Services\Agriculture\Research\Identity\ExternalIdentityRef;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Path\CapabilityToPathEligibilityMapper;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Projection\ProjectionSearchOptionsBinder;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Proves OD-C Projection approved metadata is attached on the Stage-3 options path.
 */
final class ProjectionOrchestratorRuntimeIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_options_for_source_attaches_approved_metadata_from_capability_decision(): void
    {
        $subject = CapabilitySubject::of(
            AdrMembershipId::fromString('G3-01'),
            CanonicalSourceIdentityId::fromString('cid_apis'),
        );
        $records = [
            CapabilityRecord::create(
                CapabilityRecordId::fromString('rec-species'),
                $subject,
                CapabilityDimension::scientificFacetQueryConstrainable('species'),
                CapabilityState::VERIFIED,
                CapabilityEvidenceFreshness::CURRENT,
            ),
            CapabilityRecord::create(
                CapabilityRecordId::fromString('rec-geo'),
                $subject,
                CapabilityDimension::scientificFacetQueryConstrainable('geography'),
                CapabilityState::UNAVAILABLE,
                CapabilityEvidenceFreshness::CURRENT,
            ),
        ];
        $cap = (new CapabilityRequirementEvaluator())->evaluate(
            $subject,
            CapabilityRequirementSet::of([
                CapabilityDimension::scientificFacetQueryConstrainable('species'),
                CapabilityDimension::scientificFacetQueryConstrainable('geography'),
            ]),
            $records,
            'cap-dec-orch-001',
            '2026-11-11T00:00:00Z',
        );
        $mapper = new CapabilityToPathEligibilityMapper();
        $path = PathModelDomainContract::bindFromCapabilityDecision(
            PathDecisionId::fromString('path-dec-orch-001'),
            PathId::fromString('path-orch-001'),
            PathFamily::P01,
            $cap,
            $mapper->suggestedStatus($mapper->map($cap->andResultClass)),
            decidedAt: '2026-11-11T00:00:00Z',
        );

        $identity = Stage3SourceKeyResolution::adrBound(
            'openalex',
            ExternalIdentityRef::of(ExternalIdentityKind::EXTERNAL_AGGREGATOR, 'openalex'),
            AdrMembershipId::fromString('G3-01'),
            CanonicalSourceIdentityId::fromString('cid_apis'),
        );
        $entry = new CoexistenceSourcePlanEntry(
            sourceKey: 'openalex',
            identity: $identity,
            selectorSelected: true,
            retrievalPermitted: true,
            legacyCompatible: true,
            cghiaArtifactsAttached: true,
            verifiedAutomationClaimed: false,
            events: [],
            limitations: [],
            capabilityDecision: $cap,
            pathDecision: $path,
        );
        $coexistence = new CoexistenceRetrievalPlan(
            CoexistenceMode::CGHIA_ATTACHED,
            ['openalex'],
            [$entry],
            'wheat yield Egypt',
            'q:test',
            'csq:orch-runtime-001',
        );

        $orchestrator = $this->makeOrchestrator();
        $active = new ReflectionProperty($orchestrator, 'activeCoexistencePlan');
        $active->setAccessible(true);
        $active->setValue($orchestrator, $coexistence);

        $method = new ReflectionMethod($orchestrator, 'optionsForSource');
        $method->setAccessible(true);
        $options = $method->invoke($orchestrator, 'openalex', $this->planWithEgypt());

        $this->assertSame(
            ProjectionSearchOptionsBinder::STATUS_ATTACHED,
            $options[ProjectionSearchOptionsBinder::STATUS_KEY],
        );
        $meta = $options[ProjectionSearchOptionsBinder::OPTIONS_KEY];
        $this->assertIsArray($meta);
        $this->assertContains('geography', $meta['unsupported_facets']);
        $this->assertArrayNotHasKey('source_query', $meta);
        $this->assertArrayNotHasKey('country', $options, 'Builder must omit country when geography is unsupported');
        $this->assertTrue($options['include_semantic_score']);
        $this->assertArrayHasKey('projection_query_constraints', $options);
    }

    public function test_options_for_source_skips_without_capability_decision(): void
    {
        $orchestrator = $this->makeOrchestrator();
        $active = new ReflectionProperty($orchestrator, 'activeCoexistencePlan');
        $active->setAccessible(true);
        $active->setValue($orchestrator, null);

        $method = new ReflectionMethod($orchestrator, 'optionsForSource');
        $method->setAccessible(true);
        $options = $method->invoke($orchestrator, 'openalex', $this->planWithEgypt());

        $this->assertSame(
            ProjectionSearchOptionsBinder::STATUS_SKIPPED_MISSING_DECISION,
            $options[ProjectionSearchOptionsBinder::STATUS_KEY],
        );
        $this->assertArrayHasKey('country', $options);
        $this->assertArrayNotHasKey('source_query', $options[ProjectionSearchOptionsBinder::OPTIONS_KEY]);
    }

    private function makeOrchestrator(): MultiSourceScientificSearchOrchestrator
    {
        return new MultiSourceScientificSearchOrchestrator(
            Mockery::mock(ScientificSourceAdapterRegistry::class),
            Mockery::mock(ScientificSourceSelector::class),
            new ScientificSearchQueryBuilder(),
            Mockery::mock(ScientificResultDeduplicator::class),
            Mockery::mock(ScientificResultRanker::class),
        );
    }

    private function planWithEgypt(): KnowledgeQueryPlan
    {
        $question = new CanonicalScientificQuestion(
            originalQuestion: 'What is wheat yield in Egypt?',
            language: 'en',
            entity: new CsqEntity(
                surface: 'wheat',
                normalized: 'wheat',
                resolution: CanonicalScientificQuestion::RESOLUTION_RESOLVED,
                canonicalId: 'wheat',
                canonicalNamespace: CanonicalScientificQuestion::NAMESPACE_TAXONOMY_CROP,
            ),
            property: new CsqProperty(key: 'yield', surface: 'yield'),
            geography: new CsqGeography(country: 'Egypt', label: 'Egypt'),
        );

        $normalized = new AgriculturalKnowledgeQuery(
            originalQuestion: $question->originalQuestion,
            normalizedQuestion: $question->originalQuestion,
            language: $question->language,
            agriculturalDomain: 'crops',
            subject: null,
            crop: $question->entity->normalized,
            cropId: $question->entity->canonicalId,
            scientificName: null,
            topic: 'research',
            subtopic: null,
            requestedInformation: [],
            constraints: [],
            location: $question->geography->country,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
            canonicalQuestion: $question,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $normalized,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'crops',
            subjectEntity: null,
            topics: [],
            subtopics: [],
            requestedInformation: [],
            evidenceRequirements: [],
            sourcePriorities: [],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
