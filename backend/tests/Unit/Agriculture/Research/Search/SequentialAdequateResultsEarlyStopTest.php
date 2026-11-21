<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSearchTimeBudget;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Sequential home retrieval must not keep hitting governed/local adapters after
 * scholarly providers already returned an adequate raw hit set.
 */
class SequentialAdequateResultsEarlyStopTest extends TestCase
{
    public function test_non_scholarly_adapters_skip_after_adequate_scholarly_hits(): void
    {
        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the effect of temperature on wheat growth?',
        ]);

        $openAlex = $this->fakeAdapter('openalex', 8);
        $crossref = $this->fakeAdapter('crossref', 4);
        $semantic = $this->fakeAdapter('semantic_scholar', 3);
        $agris = $this->fakeAdapter('agris', 3);
        $gbif = $this->fakeAdapter('gbif', 3);

        $budget = ScientificSearchTimeBudget::start();
        $method = new ReflectionMethod($orchestrator, 'collectAdaptersSequentially');
        $method->setAccessible(true);

        /** @var array<string, array<string, mixed>> $collections */
        $collections = $method->invoke(
            $orchestrator,
            [$openAlex, $crossref, $semantic, $agris, $gbif],
            ['wheat temperature growth'],
            8,
            $plan,
            $budget,
            0,
        );

        $this->assertSame('success', $collections['openalex']['adapterStatus']);
        $this->assertSame('success', $collections['crossref']['adapterStatus']);
        // R3.1: third independent scholarly corpus is attempted before early-stop.
        $this->assertSame('success', $collections['semantic_scholar']['adapterStatus']);
        // Wheat physiology does not require AGRICULTURAL_PRACTICE → AGRIS may early-stop.
        $this->assertSame('skipped', $collections['agris']['adapterStatus']);
        $this->assertSame('skipped', $collections['gbif']['adapterStatus']);
        $this->assertSame(
            'adequate_results_early_stop',
            $collections['agris']['outcomes'][0]->error,
        );
        $this->assertTrue($openAlex->called);
        $this->assertTrue($crossref->called);
        $this->assertTrue($semantic->called);
        $this->assertFalse($agris->called);
        $this->assertFalse($gbif->called);
    }

    public function test_agris_is_not_early_stopped_when_agricultural_practice_is_required(): void
    {
        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are recommended irrigation practices for maize cultivation according to agricultural research?',
        ]);

        $openAlex = $this->fakeAdapter('openalex', 8);
        $crossref = $this->fakeAdapter('crossref', 4);
        $semantic = $this->fakeAdapter('semantic_scholar', 3);
        $agris = $this->fakeAdapter('agris', 3);

        $budget = ScientificSearchTimeBudget::start();
        $method = new ReflectionMethod($orchestrator, 'collectAdaptersSequentially');
        $method->setAccessible(true);

        /** @var array<string, array<string, mixed>> $collections */
        $collections = $method->invoke(
            $orchestrator,
            [$openAlex, $crossref, $semantic, $agris],
            ['maize irrigation cultivation'],
            8,
            $plan,
            $budget,
            0,
        );

        $this->assertSame('success', $collections['semantic_scholar']['adapterStatus']);
        $this->assertSame('success', $collections['agris']['adapterStatus']);
        $this->assertTrue($agris->called);
    }

    public function test_dr_duke_remains_protected_for_phytochemical_questions(): void
    {
        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What chemical constituents are reported in Mentha piperita according to ethnobotanical phytochemical data?',
        ]);

        $openAlex = $this->fakeAdapter('openalex', 8);
        $crossref = $this->fakeAdapter('crossref', 4);
        $duke = $this->fakeAdapter('dr_duke', 3);

        $budget = ScientificSearchTimeBudget::start();
        $method = new ReflectionMethod($orchestrator, 'collectAdaptersSequentially');
        $method->setAccessible(true);

        /** @var array<string, array<string, mixed>> $collections */
        $collections = $method->invoke(
            $orchestrator,
            [$openAlex, $crossref, $duke],
            ['mentha piperita chemical constituents'],
            8,
            $plan,
            $budget,
            0,
        );

        $this->assertSame('success', $collections['dr_duke']['adapterStatus']);
        $this->assertTrue($duke->called);
    }

    private function fakeAdapter(string $key, int $resultCount): ScientificSourceAdapterInterface
    {
        return new class($key, $resultCount) implements ScientificSourceAdapterInterface
        {
            public bool $called = false;

            public function __construct(
                private readonly string $key,
                private readonly int $resultCount,
            ) {}

            public function sourceKey(): string
            {
                return $this->key;
            }

            public function displayName(): string
            {
                return $this->key;
            }

            public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
            {
                $this->called = true;
                $results = [];
                for ($i = 0; $i < $this->resultCount; $i++) {
                    $results[] = new ScientificSearchResult(
                        sourceKey: $this->key,
                        sourceIdentifier: $this->key.'-'.$i,
                        title: 'Temperature effects on wheat growth '.$i,
                        authors: [],
                        publicationYear: 2020,
                        doi: null,
                        canonicalUrl: 'https://example.test/'.$this->key.'/'.$i,
                        abstract: 'Validated agricultural physiology abstract about temperature and wheat.',
                        journal: null,
                        foundBySources: [$this->key],
                    );
                }

                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->key,
                    status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
                    results: $results,
                );
            }
        };
    }
}
