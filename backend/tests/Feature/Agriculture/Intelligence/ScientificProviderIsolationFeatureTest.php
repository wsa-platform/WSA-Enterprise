<?php

namespace Tests\Feature\Agriculture\Intelligence;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStatScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScientificProviderIsolationFeatureTest extends TestCase
{
    public function test_registry_includes_fao_and_selector_respects_flag(): void
    {
        $registry = app(ScientificSourceAdapterRegistry::class);
        $this->assertContains('fao_stat', $registry->registeredSourceKeys());
        $this->assertInstanceOf(FaoStatScientificSourceAdapter::class, $registry->get('fao_stat'));

        config(['agricultural_intelligence.fao.enabled' => false]);
        $plan = $this->internetFirstPlan();
        $sources = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertNotContains('fao_stat', $sources);

        config(['agricultural_intelligence.fao.enabled' => true]);
        $sourcesOn = app(ScientificSourceSelector::class)->selectSources($plan);
        $this->assertContains('fao_stat', $sourcesOn);
    }

    public function test_semantic_scholar_unauth_429_and_5xx_isolation(): void
    {
        Http::fake([
            'api.semanticscholar.org/*' => Http::sequence()
                ->push(['data' => []], 401)
                ->push(['message' => 'rate'], 429, ['Retry-After' => '0'])
                ->push(['message' => 'rate'], 429, ['Retry-After' => '0'])
                ->push(['message' => 'rate'], 429, ['Retry-After' => '0'])
                ->push(['error' => 'down'], 503),
        ]);

        config(['wsa.semantic_scholar_api_key' => '']);
        $adapter = app(SemanticScholarScientificSourceAdapter::class);

        $unauth = $adapter->search('nitrogen fixation legumes', 5);
        $this->assertContains($unauth->status, [
            ScientificSourceSearchOutcome::STATUS_FAILED,
            ScientificSourceSearchOutcome::STATUS_EMPTY,
            ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
        ]);

        $rate = $adapter->search('nitrogen fixation legumes', 5);
        $this->assertContains($rate->status, [
            ScientificSourceSearchOutcome::STATUS_FAILED,
            ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
        ]);

        $server = $adapter->search('nitrogen fixation legumes', 5);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $server->status);
    }

    private function internetFirstPlan(): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: 'irrigation efficiency research',
            normalizedQuestion: 'irrigation efficiency research',
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => 'irrigation'],
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'irrigation',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'topic', 'value' => 'irrigation'],
            topics: ['irrigation'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['peer_reviewed'],
            sourcePriorities: ['openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            readyForStage3: true,
        );
    }
}
