<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimEvidenceFusion;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatClaimSupportAssessor;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalTokenManager;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatSupportState;
use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FaoStatPhase4EvidenceFusionTest extends TestCase
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

    public function test_faostat_stays_in_stats_and_scholarly_stays_in_scientific_evidence(): void
    {
        $fusion = app(FaoStatClaimEvidenceFusion::class);
        $bundle = $fusion->fuse([
            $fusion->faostatStatsResult([
                'item' => 'Wheat',
                'value' => '6609520',
                'unit' => 't',
                'year' => '2022',
                'query_element_code' => '2510',
                'response_element_code' => '5510',
            ], 'https://faostatservices.fao.org/api/v1/en/data/QCL'),
            $fusion->scholarlyResult('openalex', [
                'title' => 'Wheat physiology paper',
                'doi' => '10.1/openalex',
                'publication_year' => 2018,
            ]),
            $fusion->scholarlyResult('crossref', [
                'title' => 'Crossref wheat paper',
                'doi' => '10.1/crossref',
                'publication_year' => 2019,
            ]),
            $fusion->scholarlyResult('semantic_scholar', [
                'title' => 'Semantic Scholar wheat paper',
                'doi' => '10.1/ss',
                'publication_year' => 2020,
            ]),
        ]);

        $this->assertNotEmpty($bundle->results[0]->stats);
        $this->assertSame('fao_stat', $bundle->results[0]->providerId);
        $this->assertSame(SourceRole::OFFICIAL_AGRICULTURAL_DATA, $bundle->results[0]->stats[0]['source_role']);
        $this->assertEmpty($bundle->results[0]->scientificEvidence);
        $this->assertNotEmpty($bundle->results[1]->scientificEvidence);
        $this->assertSame('openalex', $bundle->results[1]->providerId);
        $this->assertEmpty($bundle->results[1]->stats);
        $this->assertSame(SourceRole::SCIENTIFIC_EVIDENCE, $bundle->results[1]->scientificEvidence[0]['source_role']);
        $this->assertSame(2018, $bundle->results[1]->scientificEvidence[0]['publication_year']);
        $this->assertNotSame(2018, $bundle->results[0]->stats[0]['year']);
        $this->assertSame('2510', $bundle->results[0]->stats[0]['query_element_code']);
        $this->assertSame('5510', $bundle->results[0]->stats[0]['response_element_code']);
        $this->assertContains('fao_stat', $bundle->providersUsed);
        $this->assertContains('openalex', $bundle->providersUsed);
        $this->assertContains('crossref', $bundle->providersUsed);
        $this->assertContains('semantic_scholar', $bundle->providersUsed);
    }

    public function test_claim_level_fusion_assigns_providers_independently(): void
    {
        $quantitative = $this->phase4Plan(
            'Italy produced 6,609,520 tonnes of wheat in 2022.',
            'agricultural_economics',
            ['year' => '2022'],
            'Wheat',
            'Italy',
        );
        $causal = $this->phase4Plan('Wheat production increased because rainfall increased.');
        $openAlex = new ScientificSearchResult(
            sourceKey: 'openalex',
            sourceIdentifier: '10.1/openalex',
            title: 'Rainfall and wheat physiology',
            authors: ['A. Author'],
            publicationYear: 2018,
            doi: '10.1/openalex',
            canonicalUrl: 'https://openalex.org/W1',
            abstract: 'Mechanism paper',
            journal: 'Journal',
            foundBySources: ['openalex'],
        );

        $mapped = app(FaoStatClaimEvidenceFusion::class)->mapClaims(
            [
                ['id' => 'A', 'plan' => $quantitative],
                ['id' => 'B', 'plan' => $causal],
            ],
            [$this->faostatResult(), $openAlex],
        );

        $this->assertSame('A', $mapped[0]['claim_id']);
        $this->assertContains('fao_stat', $mapped[0]['providers']);
        $this->assertContains('openalex', $mapped[0]['providers']);
        $this->assertNotContains('fao_stat', $mapped[1]['providers']);
        $this->assertContains('openalex', $mapped[1]['providers']);
        $this->assertFalse($mapped[0]['insufficient']);
        $this->assertFalse($mapped[1]['insufficient']);
    }

    public function test_qcl_regression_support_and_provenance_remain_intact(): void
    {
        $plan = $this->phase4Plan(
            'What was wheat production in Italy in 2022?',
            'agricultural_economics',
            ['domain' => 'QCL', 'area' => '106', 'item' => '15', 'element' => '2510', 'year' => '2022'],
            'Wheat',
            'Italy',
        );
        $result = $this->faostatResult();
        $support = app(FaoStatClaimSupportAssessor::class)->assess($plan, $result);
        $this->assertSame(ClaimEvidenceRelationship::SUPPORTED, $support['relationship']);
        $this->assertSame(FaoStatSupportState::SUPPORTED, $support['factors']['faostat_support_state']);
        $this->assertSame([], $result->authors);
        $this->assertNull($result->doi);
        $this->assertNull($result->journal);
        $this->assertStringContainsString('faostatservices.fao.org', (string) $result->canonicalUrl);
        $this->assertSame('faostatservices.fao.org', $result->rawMetadata['faostat']['provenance']['host'] ?? null);
        $this->assertTrue($result->relevanceMetadata['not_literature'] ?? false);
    }

    public function test_insufficient_claim_when_no_provider_supports_it(): void
    {
        $mapped = app(FaoStatClaimEvidenceFusion::class)->mapClaims(
            [['id' => 'E', 'plan' => $this->phase4Plan('The best fertilizer for wheat is X.')]],
            [$this->faostatResult()],
        );
        $this->assertTrue($mapped[0]['insufficient']);
        $this->assertSame([], $mapped[0]['providers']);
    }
}
