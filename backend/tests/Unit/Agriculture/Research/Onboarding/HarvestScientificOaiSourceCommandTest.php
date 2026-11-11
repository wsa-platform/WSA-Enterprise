<?php

namespace Tests\Unit\Agriculture\Research\Onboarding;

use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Artisan harvest wiring for OAI seats — fixture HTTP only; does not activate.
 * Detailed harvest/probe assertions live in OrganicEprintsOaiLocalIndexPathTest.
 */
class HarvestScientificOaiSourceCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
    }

    protected function tearDown(): void
    {
        CurrentMembershipNearReadySeatCatalog::swap(null);
        ScientificSourceCatalog::forgetDefault();
        parent::tearDown();
    }

    public function test_memory_harvest_command_exits_successfully_without_activating(): void
    {
        Http::fake([
            'https://orgprints.org/cgi/oai2*' => Http::response(
                (string) file_get_contents(base_path('tests/Fixtures/ScientificSourceFamilies/organic_eprints_list_records_page1.xml')),
                200,
                ['Content-Type' => 'text/xml'],
            ),
        ]);

        $this->artisan('scientific:harvest-oai', [
            'source_key' => 'organic_eprints',
            '--max-pages' => 1,
            '--memory' => true,
            '--probe-query' => 'nutritional value plant foods',
        ])->assertSuccessful();

        $this->assertTrue(ScientificSourceCatalog::default()->has('organic_eprints'));
        $this->assertTrue(CurrentMembershipNearReadySeatCatalog::get('G1-02')?->isRuntimeActive());
    }

    public function test_unknown_source_key_fails(): void
    {
        $this->artisan('scientific:harvest-oai', [
            'source_key' => 'not_a_real_source',
            '--memory' => true,
        ])->assertFailed();
    }
}
