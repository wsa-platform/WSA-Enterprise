<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\Registry\AlwaysEnabledSourceEnablement;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityDeclaration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCapabilityStatus;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceExecutionGroup;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceLifecycle;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceOperation;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistration;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceSelectionRole;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Search\ScientificSourceSelector;
use Tests\TestCase;

/**
 * Rev 11.0.13 §4 — production orchestrator path (null sourceKeys) uses governed eligibility.
 *
 * Mirrors AgriculturalResearchAgent → AgriculturalScientificSearchService → Orchestrator
 * when sourceKeys is null. Isolated stub adapters — no LocalIndex / FTS / Persist / BY0 writes.
 */
class GovernedEligibilityProductionSearchPathTest extends TestCase
{
    public function test_null_source_keys_orchestrator_path_applies_governed_relevance(): void
    {
        $catalog = $this->stubCatalog();
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is barley water requirement in Brazil?',
        ]);
        $this->assertTrue($plan->isInternetFirst());
        $this->assertTrue($plan->readyForStage3);

        $selector = new ScientificSourceSelector($catalog);
        $selected = $selector->selectSources($plan);
        $this->assertContains('agris', $selected);
        $this->assertNotContains('dr_duke', $selected);
        $this->assertNotContains('organic_eprints', $selected);
        // Planner may resolve barley → Hordeum vulgare; GBIF taxonomic grounding is allowed.
        // Phytochem/heritage-only seats must still stay out of irrigation quantity scope.
        $this->assertNotContains('bhl', $selected);
        $this->assertLessThan(
            5,
            count(array_intersect($selected, GovernedScientificSourceEligibility::PROFILED_KEYS)),
            'Irrigation must not select all five ACTIVE governed seats',
        );

        $report = (new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            $selector,
            app(ScientificSearchQueryBuilder::class),
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        ))->execute($plan, 3, null);

        $this->assertSame($selected, $report->selectedSources);
        $this->assertContains('agris', $report->attemptedSources);
        $this->assertNotContains('dr_duke', $report->attemptedSources);
        $this->assertNotContains('organic_eprints', $report->attemptedSources);
        $this->assertNotContains('bhl', $report->attemptedSources);

        $trace = $selector->selectionTrace($plan);
        $this->assertStringContainsString('governed_relevance', $trace['policy']);
        $this->assertNotEmpty($trace['relevance_excluded']);
    }

    public function test_organic_query_selects_organic_eprints_on_null_source_keys_path(): void
    {
        $catalog = $this->stubCatalog();
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'Organic agriculture compost effects on soil fertility',
        ]);

        $selector = new ScientificSourceSelector($catalog);
        $report = (new MultiSourceScientificSearchOrchestrator(
            new ScientificSourceAdapterRegistry(app(), $catalog),
            $selector,
            app(ScientificSearchQueryBuilder::class),
            app(ScientificResultDeduplicator::class),
            app(ScientificResultRanker::class),
            catalog: $catalog,
        ))->execute($plan, 2, null);

        $this->assertContains('organic_eprints', $report->selectedSources);
        $this->assertContains('agris', $report->selectedSources);
        $this->assertNotContains('dr_duke', $report->selectedSources);
    }

    private function stubCatalog(): ScientificSourceCatalog
    {
        return new ScientificSourceCatalog([
            $this->stub('agris', Rev1113StubAgrisAdapter::class),
            $this->stub('organic_eprints', Rev1113StubOrganicEprintsAdapter::class),
            $this->stub('dr_duke', Rev1113StubDrDukeAdapter::class),
            $this->stub('gbif', Rev1113StubGbifAdapter::class),
            $this->stub('bhl', Rev1113StubBhlAdapter::class),
        ]);
    }

    /** @param  class-string<ScientificSourceAdapterInterface>  $adapterClass */
    private function stub(string $sourceKey, string $adapterClass): ScientificSourceRegistration
    {
        return new ScientificSourceRegistration(
            sourceKey: $sourceKey,
            adapterClass: $adapterClass,
            adapterContractVersion: ScientificSourceAdapterInterface::CONTRACT_VERSION,
            lifecycle: ScientificSourceLifecycle::LEGACY_STAGE3,
            accessFamily: ScientificSourceAccessFamily::REST_API,
            selectionRole: ScientificSourceSelectionRole::GOVERNED_EXPANSION,
            executionGroup: ScientificSourceExecutionGroup::INDEPENDENT_SCHOLARLY,
            enablement: new AlwaysEnabledSourceEnablement(),
            pathFamily: PathFamily::P12,
            capabilities: [
                new ScientificSourceCapabilityDeclaration(
                    ScientificSourceOperation::SEARCH,
                    ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED,
                    'rev11.0.13 stub governed eligibility path',
                    verifiedAt: '2026-11-18T00:00:00+00:00',
                    expiresAt: '2099-01-01T00:00:00+00:00',
                ),
            ],
        );
    }
}

abstract class Rev1113StubGovernedAdapterBase implements ScientificSourceAdapterInterface
{
    abstract public function sourceKey(): string;

    public function displayName(): string
    {
        return 'Rev1113 stub '.$this->sourceKey();
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        $key = $this->sourceKey();

        return new ScientificSourceSearchOutcome(
            sourceKey: $key,
            status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
            results: [
                new ScientificSearchResult(
                    sourceKey: $key,
                    sourceIdentifier: 'stub-'.$key.'-1',
                    title: 'Stub result for '.$key.': '.$query,
                    authors: ['Stub'],
                    publicationYear: 2024,
                    doi: null,
                    canonicalUrl: 'https://example.invalid/'.$key,
                    abstract: 'Isolated stub abstract.',
                    journal: null,
                    foundBySources: [$key],
                ),
            ],
        );
    }
}

final class Rev1113StubAgrisAdapter extends Rev1113StubGovernedAdapterBase
{
    public function sourceKey(): string
    {
        return 'agris';
    }
}

final class Rev1113StubOrganicEprintsAdapter extends Rev1113StubGovernedAdapterBase
{
    public function sourceKey(): string
    {
        return 'organic_eprints';
    }
}

final class Rev1113StubDrDukeAdapter extends Rev1113StubGovernedAdapterBase
{
    public function sourceKey(): string
    {
        return 'dr_duke';
    }
}

final class Rev1113StubGbifAdapter extends Rev1113StubGovernedAdapterBase
{
    public function sourceKey(): string
    {
        return 'gbif';
    }
}

final class Rev1113StubBhlAdapter extends Rev1113StubGovernedAdapterBase
{
    public function sourceKey(): string
    {
        return 'bhl';
    }
}
