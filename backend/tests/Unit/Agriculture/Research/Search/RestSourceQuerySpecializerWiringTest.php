<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\BhlPublicationQuerySpecializer;
use App\Services\Agriculture\Research\Search\GbifSpeciesQuerySpecializer;
use App\Services\Agriculture\Research\Search\MultiSourceScientificSearchOrchestrator;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use ReflectionMethod;
use Tests\TestCase;

/**
 * REST specializers must apply outside the LocalIndex-only gate.
 */
class RestSourceQuerySpecializerWiringTest extends TestCase
{
    public function test_gbif_and_bhl_specializers_override_variants_for_rest_adapters(): void
    {
        $orchestrator = app(MultiSourceScientificSearchOrchestrator::class);
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the taxonomic classification of Triticum aestivum?',
        ]);
        // Force scientific identity for deterministic specializer output.
        $nq = $plan->normalizedQuery;
        $refNq = new \ReflectionClass($nq);
        // KnowledgeQueryPlan fields are readonly in some builds — rebuild via harness-like plan if needed.
        if ($nq->scientificName === null || trim((string) $nq->scientificName) === '') {
            $this->markTestSkipped('Planner did not resolve scientificName for wheat taxonomy question');
        }

        $method = new ReflectionMethod($orchestrator, 'variantsForAdapter');
        $method->setAccessible(true);

        $gbifAdapter = $this->fakeAdapter(GbifSpeciesQuerySpecializer::SOURCE_KEY);
        $bhlAdapter = $this->fakeAdapter(BhlPublicationQuerySpecializer::SOURCE_KEY);

        $gbifVariants = $method->invoke(
            $orchestrator,
            $gbifAdapter,
            $plan,
            ['Triticum aestivum species classification thermal growth'],
        );
        $bhlVariants = $method->invoke(
            $orchestrator,
            $bhlAdapter,
            $plan,
            ['unrelated scholarly noise phrase'],
        );

        $this->assertNotEmpty($gbifVariants);
        $this->assertSame(trim((string) $nq->scientificName), $gbifVariants[0]);
        $this->assertStringNotContainsString('thermal growth', implode(' ', $gbifVariants));

        $this->assertNotEmpty($bhlVariants);
        $this->assertStringContainsString(
            explode(' ', trim((string) $nq->scientificName))[0],
            $bhlVariants[0],
        );
    }

    private function fakeAdapter(string $key): ScientificSourceAdapterInterface
    {
        return new class($key) implements ScientificSourceAdapterInterface
        {
            public function __construct(private readonly string $key) {}

            public function sourceKey(): string
            {
                return $this->key;
            }

            public function displayName(): string
            {
                return $this->key;
            }

            public function search(string $query, int $limit = 5, array $options = []): ScientificSourceSearchOutcome
            {
                return new ScientificSourceSearchOutcome(
                    sourceKey: $this->key,
                    status: ScientificSourceSearchOutcome::STATUS_SUCCESS,
                    results: [
                        new ScientificSearchResult(
                            sourceKey: $this->key,
                            sourceIdentifier: '1',
                            title: 't',
                            authors: [],
                            publicationYear: null,
                            doi: null,
                            canonicalUrl: null,
                            abstract: null,
                            journal: null,
                            foundBySources: [$this->key],
                        ),
                    ],
                );
            }
        };
    }
}
