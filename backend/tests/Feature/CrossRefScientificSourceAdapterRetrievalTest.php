<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Crossref adapter retrieval depth (country-agnostic rows↑).
 */
class CrossRefScientificSourceAdapterRetrievalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function crossRefWork(string $title, string $doi, string $abstract = ''): array
    {
        return [
            'DOI' => $doi,
            'title' => [$title],
            'abstract' => $abstract,
            'publisher' => 'Academic Press',
            'container-title' => ['Soil Science'],
            'issued' => ['date-parts' => [[2020]]],
            'author' => [['given' => 'A', 'family' => 'Researcher']],
        ];
    }

    public function test_crossref_fetches_at_least_fifty_rows_for_default_limit(): void
    {
        $capturedRows = null;

        Http::fake(function (Request $request) use (&$capturedRows) {
            $this->assertStringContainsString('api.crossref.org/works', $request->url());
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $capturedRows = isset($query['rows']) ? (int) $query['rows'] : null;

            $items = [];
            for ($i = 1; $i <= 50; $i++) {
                $items[] = $this->crossRefWork(
                    "Generic agricultural soil study {$i}",
                    '10.1000/depth-'.$i,
                );
            }
            // Simulate a deeper national-inventory-style hit past the old 10-row window.
            $items[30] = $this->crossRefWork(
                'National soil classification survey for country-wide inventory',
                '10.1000/national-inventory-depth',
                'Country-wide soil classification and land types inventory.',
            );

            return Http::response(['message' => ['items' => $items]], 200);
        });

        $outcome = app(CrossRefScientificSourceAdapter::class)->search(
            'soil types Exampleland',
            10,
        );

        $this->assertSame(50, $capturedRows, 'Adapter must request rows>=50 (not capped at 10)');
        $this->assertSame('success', $outcome->status);
        $this->assertCount(50, $outcome->results);

        $dois = array_map(static fn ($r) => $r->doi, $outcome->results);
        $this->assertContains(
            '10.1000/national-inventory-depth',
            $dois,
            'Normalized results must retain hits beyond the former 10-row window',
        );
        $titles = array_map(static fn ($r) => mb_strtolower($r->title), $outcome->results);
        $this->assertTrue(
            (bool) array_filter(
                $titles,
                static fn (string $t): bool => str_contains($t, 'national soil classification')
                    || str_contains($t, 'country-wide inventory'),
            ),
            'Country-agnostic national inventory phrasing must survive normalization',
        );
    }

    public function test_crossref_respects_upper_fetch_cap_when_limit_is_large(): void
    {
        $capturedRows = null;

        Http::fake(function (Request $request) use (&$capturedRows) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $capturedRows = isset($query['rows']) ? (int) $query['rows'] : null;

            return Http::response(['message' => ['items' => [
                $this->crossRefWork('Cap check work', '10.1000/cap-check'),
            ]]], 200);
        });

        app(CrossRefScientificSourceAdapter::class)->search('land classification Exampleland', 500);

        $this->assertSame(100, $capturedRows, 'Adapter must cap Crossref rows at 100');
    }

    public function test_crossref_empty_query_short_circuits(): void
    {
        Http::fake();

        $outcome = app(CrossRefScientificSourceAdapter::class)->search('   ', 10);

        $this->assertSame('empty', $outcome->status);
        $this->assertSame('empty_query', $outcome->error);
        Http::assertNothingSent();
    }
}
