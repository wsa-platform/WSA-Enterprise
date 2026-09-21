<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\ScientificResultRanker;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Tests\TestCase;

/**
 * Deterministic geographic-scope ranking for inventory/classification queries.
 * NATIONAL > REGIONAL for country-level inventory; reverse for regional queries.
 */
class ScientificResultRankerGeoScopeTest extends TestCase
{
    public function test_egypt_ar_national_inventory_ranks_national_above_regional(): void
    {
        $this->assertNationalBeatsRegional(
            'ما هي أنواع الأراضي الزراعية في مصر؟',
            'Egypt',
        );
    }

    public function test_egypt_en_national_inventory_ranks_national_above_regional(): void
    {
        $this->assertNationalBeatsRegional(
            'What are the types of agricultural land in Egypt?',
            'Egypt',
        );
    }

    public function test_regional_query_prefers_regional_over_national(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the soil types in South Sinai?',
        ]);
        $this->assertSame('South Sinai', $plan->normalizedQuery->location);

        $regional = $this->fixture(
            'W-reg',
            'Soil Classification and Land Capability Evaluation in South Sinai',
            'Soil types and land classes inventory for the South Sinai governorate study area.',
            2023,
        );
        $national = $this->fixture(
            'W-nat',
            'Soil Map of Egypt: national soil types and land classification',
            'Nationwide inventory of soil types and agricultural land classes across the country.',
            2015,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'soil types South Sinai',
            [$national, $regional],
            $plan,
        );

        $this->assertSame('W-reg', $ranked[0]->sourceIdentifier);
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_REGIONAL, $ranked[0]->relevanceMetadata['document_geo_scope'] ?? null);
        $this->assertSame('regional', $ranked[0]->relevanceMetadata['query_geo_level'] ?? null);
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL, $this->byId($ranked, 'W-nat')->relevanceMetadata['document_geo_scope'] ?? null);
    }

    public function test_irrelevant_national_does_not_beat_relevant_regional(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);

        $irrelevantNational = $this->fixture(
            'W-irr',
            'National banking reforms and tourism GDP growth in Egypt',
            'Nationwide macroeconomic inventory of banking and tourism indicators across the country.',
            2024,
        );
        $relevantRegional = $this->fixture(
            'W-rel',
            'Soil Classification and Land Capability Evaluation for Sustainable Agricultural Use in South Sinai, Egypt',
            'Lists soil types and land capability classes for agricultural land use planning in the South Sinai governorate.',
            2020,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types Egypt',
            [$irrelevantNational, $relevantRegional],
            $plan,
        );

        $this->assertSame('W-rel', $ranked[0]->sourceIdentifier);
        $this->assertTrue((bool) ($this->byId($ranked, 'W-irr')->relevanceMetadata['rejected_by_relevance_gate'] ?? false)
            || ($this->byId($ranked, 'W-irr')->relevanceScore ?? 0.0) < ($ranked[0]->relevanceScore ?? 0.0));
    }

    public function test_non_inventory_tomato_ranking_unchanged_by_geo_scope(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What is the optimal temperature for tomato seed germination?',
        ]);
        $this->assertNotSame(
            'classification_or_types_inventory',
            $plan->normalizedQuery->constraints['required_evidence_type'] ?? null,
        );

        $a = $this->fixture(
            'W-a',
            'Effect of temperature on tomato seed germination',
            'Solanum lycopersicum seed germination optimum near 25 C under controlled temperature regimes.',
            2022,
        );
        $b = $this->fixture(
            'W-b',
            'National soil map of Egypt unrelated filler',
            'Nationwide soil classification inventory across the country.',
            2024,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'Solanum lycopersicum temperature germination',
            [$b, $a],
            $plan,
        );

        $this->assertSame('W-a', $ranked[0]->sourceIdentifier);
        $this->assertSame(0.0, (float) ($ranked[0]->relevanceMetadata['geo_scope_delta'] ?? 0.0));
        $this->assertNull($ranked[0]->relevanceMetadata['query_geo_level'] ?? null);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('genericCountryInventoryProvider')]
    public function test_generic_country_inventory_national_beats_regional(string $query, string $country, string $regionPhrase): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
        $this->assertSame($country, $plan->normalizedQuery->location, $query);

        $national = $this->fixture(
            'W-nat-'.$country,
            'Soil Map of '.$country.': national soil types and land classification',
            'Nationwide inventory of soil types and agricultural land classes across '.$country.'.',
            2016,
        );
        $regional = $this->fixture(
            'W-reg-'.$country,
            'Soil Classification and Land Capability Evaluation in '.$regionPhrase.', '.$country,
            'Soil types and land classes for the '.$regionPhrase.' province study area in '.$country.'.',
            2024,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'soil types '.$country,
            [$regional, $national],
            $plan,
        );

        $this->assertSame('W-nat-'.$country, $ranked[0]->sourceIdentifier, $query);
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL, $ranked[0]->relevanceMetadata['document_geo_scope'] ?? null);
        $this->assertSame('country', $ranked[0]->relevanceMetadata['query_geo_level'] ?? null);
        $this->assertGreaterThan(0.0, (float) ($ranked[0]->relevanceMetadata['geo_scope_delta'] ?? 0.0));
        $this->assertLessThan(0.0, (float) ($this->byId($ranked, 'W-reg-'.$country)->relevanceMetadata['geo_scope_delta'] ?? 0.0));
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    public static function genericCountryInventoryProvider(): array
    {
        return [
            ['What are the soil types in Saudi Arabia?', 'Saudi Arabia', 'Eastern Province'],
            ['What are the types of agricultural land in Turkey?', 'Turkey', 'Central Anatolia'],
            ['What are the soil types in Morocco?', 'Morocco', 'Souss Massa'],
            ['What are the soil types in France?', 'France', 'Provence'],
            ['What are the agricultural land types in Brazil?', 'Brazil', 'Mato Grosso'],
            ['What are the soil types in India?', 'India', 'Punjab'],
            ['What are the soil types in Canada?', 'Canada', 'Saskatchewan'],
        ];
    }

    public function test_ranker_source_has_no_egypt_specific_branches(): void
    {
        $source = (string) file_get_contents(base_path('app/Services/Agriculture/Research/Search/ScientificResultRanker.php'));
        $this->assertDoesNotMatchRegularExpression('/if\s*\(\s*.*egypt/i', $source);
        $this->assertDoesNotMatchRegularExpression('/===?\s*[\'"]egypt[\'"]/i', $source);
        $this->assertDoesNotMatchRegularExpression('/south\s*sinai/i', $source);
        $this->assertStringContainsString('NATIONAL_SCOPE_BONUS_RATIO', $source);
        $this->assertStringContainsString('REGIONAL_SCOPE_PENALTY_RATIO', $source);
        $this->assertStringContainsString('0.15', $source);
        $this->assertStringContainsString('0.10', $source);
    }

    public function test_single_country_mention_without_national_framing_is_unknown_scope(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);

        $bareMention = $this->fixture(
            'W-bare',
            'Micronutrient uptake in irrigated plots',
            'Multi-country micronutrient trials included a site in Egypt among others; abstract focuses on fertilizer uptake rates only.',
            2021,
        );
        $national = $this->fixture(
            'W-nat',
            'Soil types of Egypt: national land classification inventory',
            'Nationwide classification of agricultural land types across Egypt.',
            2018,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types Egypt',
            [$bareMention, $national],
            $plan,
        );

        $this->assertSame(
            ScientificResultRanker::DOCUMENT_GEO_SCOPE_UNKNOWN,
            $this->byId($ranked, 'W-bare')->relevanceMetadata['document_geo_scope'] ?? null,
        );
        $this->assertSame(0.0, (float) ($this->byId($ranked, 'W-bare')->relevanceMetadata['geo_scope_delta'] ?? -1));
        $this->assertSame(
            ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL,
            $this->byId($ranked, 'W-nat')->relevanceMetadata['document_geo_scope'] ?? null,
        );
    }

    /**
     * Prove RAW/post-topical → geo → FINAL ordering when national post-topical is only slightly below regional.
     * Effective geo: NATIONAL +15% of post-topical; REGIONAL −10% of post-topical (topically gated).
     */
    public function test_national_wins_final_when_post_topical_slightly_below_regional(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);

        // Regional gets a mild term/source edge so post-topical is slightly higher; national still wins FINAL.
        $national = $this->fixture(
            'W-nat',
            'Soil types of Egypt: national land classification inventory',
            'Nationwide inventory of soil types and agricultural land classes across Egypt.',
            2015,
        );
        $regional = new ScientificSearchResult(
            'openalex',
            'W-reg',
            'Soil Classification South Sinai Egypt agricultural',
            ['Author'],
            2024,
            '10.1000/W-reg',
            'https://doi.org/10.1000/W-reg',
            'Soil types land classes South Sinai governorate Egypt.',
            'Journal of Soil Science',
            ['openalex', 'crossref'],
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types Egypt',
            [$regional, $national],
            $plan,
        );

        $nat = $this->byId($ranked, 'W-nat');
        $reg = $this->byId($ranked, 'W-reg');

        $natDelta = (float) ($nat->relevanceMetadata['geo_scope_delta'] ?? 0.0);
        $regDelta = (float) ($reg->relevanceMetadata['geo_scope_delta'] ?? 0.0);
        $natPost = ((float) $nat->relevanceScore) - $natDelta;
        $regPost = ((float) $reg->relevanceScore) - $regDelta;
        $natFinal = (float) $nat->relevanceScore;
        $regFinal = (float) $reg->relevanceScore;

        $this->assertFalse((bool) ($nat->relevanceMetadata['rejected_by_relevance_gate'] ?? false));
        $this->assertFalse((bool) ($reg->relevanceMetadata['rejected_by_relevance_gate'] ?? false));
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL, $nat->relevanceMetadata['document_geo_scope'] ?? null);
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_REGIONAL, $reg->relevanceMetadata['document_geo_scope'] ?? null);

        // Post-topical (after topical gate, before geo): national slightly below regional.
        $this->assertLessThan($regPost, $natPost, 'precondition: national post-topical slightly below regional');
        $this->assertGreaterThan(0.90, $natPost / $regPost, 'precondition: gap is slight (national still ≥90% of regional post)');

        // Geo ratios applied to post-topical score.
        $this->assertEqualsWithDelta($natPost * 0.15, $natDelta, 0.001);
        $this->assertEqualsWithDelta(-($regPost * 0.10), $regDelta, 0.001);

        // FINAL after geo: national wins rank.
        $this->assertGreaterThan($regFinal, $natFinal);
        $this->assertSame('W-nat', $ranked[0]->sourceIdentifier);
    }

    public function test_irrelevant_national_geo_bonus_not_applied_and_loses_to_relevant_regional(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'What are the types of agricultural land in Egypt?',
        ]);

        $irrelevantNational = $this->fixture(
            'W-irr',
            'National banking reforms and tourism GDP growth in Egypt',
            'Nationwide macroeconomic inventory of banking and tourism indicators across the country.',
            2024,
        );
        $relevantRegional = $this->fixture(
            'W-rel',
            'Soil Classification and Land Capability Evaluation for Sustainable Agricultural Use in South Sinai, Egypt',
            'Lists soil types and land capability classes for agricultural land use planning in the South Sinai governorate.',
            2020,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types Egypt',
            [$irrelevantNational, $relevantRegional],
            $plan,
        );

        $irr = $this->byId($ranked, 'W-irr');
        $rel = $this->byId($ranked, 'W-rel');

        $this->assertTrue((bool) ($irr->relevanceMetadata['rejected_by_relevance_gate'] ?? false));
        $this->assertSame(0.0, (float) ($irr->relevanceMetadata['geo_scope_delta'] ?? -1.0));
        $this->assertSame('W-rel', $ranked[0]->sourceIdentifier);
        $this->assertGreaterThan((float) $irr->relevanceScore, (float) $rel->relevanceScore);
    }

    private function assertNationalBeatsRegional(string $query, string $country): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery(['query' => $query]);
        $this->assertSame($country, $plan->normalizedQuery->location);
        $this->assertSame(
            'classification_or_types_inventory',
            $plan->normalizedQuery->constraints['required_evidence_type'] ?? null,
        );

        $national = $this->fixture(
            'W-nat',
            'Soil types of '.$country.': national land classification inventory',
            'Nationwide inventory of soil types and agricultural land classes across '.$country.'.',
            2018,
        );
        $regional = $this->fixture(
            'W-reg',
            'Soil Classification and Land Capability Evaluation for Sustainable Agricultural Use in South Sinai, '.$country,
            'Soil types and land capability classes for agricultural use in the South Sinai governorate, '.$country.'.',
            2023,
        );

        $ranked = app(ScientificResultRanker::class)->rank(
            'agricultural land types '.$country,
            [$regional, $national],
            $plan,
        );

        $this->assertSame('W-nat', $ranked[0]->sourceIdentifier, $query);
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_NATIONAL, $ranked[0]->relevanceMetadata['document_geo_scope'] ?? null);
        $this->assertSame('country', $ranked[0]->relevanceMetadata['query_geo_level'] ?? null);
        $this->assertGreaterThan(0.0, (float) ($ranked[0]->relevanceMetadata['geo_scope_delta'] ?? 0.0));

        $reg = $this->byId($ranked, 'W-reg');
        $this->assertSame(ScientificResultRanker::DOCUMENT_GEO_SCOPE_REGIONAL, $reg->relevanceMetadata['document_geo_scope'] ?? null);
        $this->assertLessThan(0.0, (float) ($reg->relevanceMetadata['geo_scope_delta'] ?? 0.0));
        $this->assertGreaterThan($reg->relevanceScore ?? 0.0, $ranked[0]->relevanceScore ?? 0.0);
    }

    private function fixture(string $id, string $title, string $abstract, int $year): ScientificSearchResult
    {
        return new ScientificSearchResult(
            'openalex',
            $id,
            $title,
            ['Author'],
            $year,
            '10.1000/'.$id,
            'https://doi.org/10.1000/'.$id,
            $abstract,
            'Journal of Soil Science',
            ['openalex'],
        );
    }

    /** @param list<ScientificSearchResult> $ranked */
    private function byId(array $ranked, string $id): ScientificSearchResult
    {
        foreach ($ranked as $item) {
            if ($item->sourceIdentifier === $id) {
                return $item;
            }
        }

        $this->fail('Missing ranked result: '.$id);
    }
}
