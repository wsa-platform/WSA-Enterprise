<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSupportState;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FaoStatPhase4ClaimMappingTest extends TestCase
{
    use FaoStatPhase4Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(FaoStatDeveloperPortalTokenManager::class)->reset();
        config([
            'agricultural_intelligence.faostat.enabled' => true,
            'agricultural_intelligence.faostat.allowed_domains' => ['QCL'],
        ]);
    }

    public function test_matching_quantitative_observation_is_supported(): void
    {
        $plan = $this->phase4Plan(
            'Italy produced 6,609,520 tonnes of wheat in 2022.',
            'agricultural_economics',
            ['domain' => 'QCL', 'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022'],
            'Wheat',
            'Italy',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $support['relationship']);
        $this->assertSame('2510', $support['factors']['query_element_code']);
        $this->assertSame('5510', $support['factors']['response_element_code']);
        $this->assertNotSame($support['factors']['query_element_code'], $support['factors']['response_element_code']);
    }

    public function test_comparison_with_only_one_year_is_partially_supported(): void
    {
        $plan = $this->phase4Plan('Wheat production in Italy increased from 2021 to 2022.');
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::PARTIALLY_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame(ClaimEvidenceRelationship::PARTIALLY_SUPPORTED, $support['relationship']);
        $this->assertSame('missing_comparison_year', $support['factors']['faostat_support_reason']);
    }

    public function test_causal_claim_is_not_supported_by_statistical_observation(): void
    {
        $plan = $this->phase4Plan('Wheat production increased because rainfall increased.');
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_APPLICABLE, $support['factors']['faostat_support_state']);
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $support['relationship']);
    }

    public function test_recommendation_claim_is_not_supported_by_statistical_observation(): void
    {
        $plan = $this->phase4Plan('The best fertilizer for wheat is X.');
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_APPLICABLE, $support['factors']['faostat_support_state']);
        $this->assertNotSame(ClaimEvidenceRelationship::SUPPORTED, $support['relationship']);
    }

    public function test_year_mismatch_is_not_supported(): void
    {
        $plan = $this->phase4Plan(
            'Italy produced 6,609,520 tonnes of wheat in 2021.',
            'agricultural_economics',
            ['year' => '2021'],
            'Wheat',
            'Italy',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('year_mismatch', $support['factors']['faostat_support_reason']);
    }

    public function test_publication_year_is_not_used_as_faostat_year(): void
    {
        $plan = $this->phase4Plan(
            'Italy produced 6,609,520 tonnes of wheat in 2022.',
            'agricultural_economics',
            ['year' => '2022'],
            'Wheat',
            'Italy',
        );
        $result = $this->faostatResult(publicationYear: 2018);
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $result);
        $this->assertSame(FaoStatSupportState::SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('2022', $support['factors']['observation_year']);
        $this->assertSame(2018, $support['factors']['publication_year_ignored']);
    }

    public function test_area_mismatch_is_not_supported(): void
    {
        $plan = $this->phase4Plan(
            'France produced 6,609,520 tonnes of wheat in 2022.',
            'agricultural_economics',
            ['year' => '2022'],
            'Wheat',
            'France',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('area_mismatch', $support['factors']['faostat_support_reason']);
    }

    public function test_item_mismatch_is_not_supported(): void
    {
        $plan = $this->phase4Plan(
            'Italy produced 6,609,520 tonnes of maize in 2022.',
            'agricultural_economics',
            ['year' => '2022'],
            'Maize',
            'Italy',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('item_mismatch', $support['factors']['faostat_support_reason']);
    }

    public function test_element_mismatch_is_not_supported(): void
    {
        $plan = $this->phase4Plan(
            'Italy wheat yield statistics 2022',
            'agricultural_economics',
            ['domain' => 'QCL', 'area' => '106', 'item' => '15', 'element' => '2413', 'year' => '2022'],
            'Wheat',
            'Italy',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('element_mismatch', $support['factors']['faostat_support_reason']);
    }

    public function test_unit_mismatch_is_not_supported_without_conversion(): void
    {
        $plan = $this->phase4Plan(
            'Italy produced 6,609,520 kilograms of wheat in 2022.',
            'agricultural_economics',
            ['year' => '2022'],
            'Wheat',
            'Italy',
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $this->faostatResult());
        $this->assertSame(FaoStatSupportState::NOT_SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame('unit_mismatch', $support['factors']['faostat_support_reason']);
    }

    public function test_missing_observation_is_unverified(): void
    {
        $plan = $this->phase4Plan('Italy produced 6,609,520 tonnes of wheat in 2022.');
        $empty = new ScientificSearchResult(
            sourceKey: 'fao_stat',
            sourceIdentifier: 'empty',
            title: 'empty',
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: null,
            abstract: null,
            journal: null,
            foundBySources: ['fao_stat'],
            relevanceMetadata: ['not_literature' => true],
            rawMetadata: ['faostat' => []],
        );
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $empty);
        $this->assertSame(FaoStatSupportState::UNVERIFIED, $support['factors']['faostat_support_state']);
        $this->assertSame(ClaimEvidenceRelationship::NOT_VALIDATED, $support['relationship']);
    }
}
