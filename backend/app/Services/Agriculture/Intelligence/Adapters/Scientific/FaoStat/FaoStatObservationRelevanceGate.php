<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Claim/need relevance for FAOSTAT observations.
 * Does not ask whether the question is agricultural.
 */
final class FaoStatObservationRelevanceGate
{
    public const RELEVANT = 'RELEVANT';

    public const NOT_RELEVANT = 'NOT_RELEVANT';

    /**
     * @return array{decision: string, relevant: bool, reason: string}
     */
    public function assess(KnowledgeQueryPlan $plan, ScientificSearchResult $result): array
    {
        if ($result->sourceKey !== 'fao_stat') {
            return ['decision' => self::RELEVANT, 'relevant' => true, 'reason' => 'not_faostat'];
        }

        $observation = is_array($result->rawMetadata['faostat'] ?? null) ? $result->rawMetadata['faostat'] : [];
        $need = FaoStatSearchOptionsResolver::fromPlan($plan);

        if (! $this->hasStatisticalInformationNeed($plan)) {
            return [
                'decision' => self::NOT_RELEVANT,
                'relevant' => false,
                'reason' => 'claim_not_statistical',
            ];
        }

        $mismatches = $this->dimensionMismatches($need, $observation);
        if ($mismatches !== []) {
            return [
                'decision' => self::NOT_RELEVANT,
                'relevant' => false,
                'reason' => 'dimension_mismatch:'.implode(',', $mismatches),
            ];
        }

        return [
            'decision' => self::RELEVANT,
            'relevant' => true,
            'reason' => 'statistical_tuple_matches',
        ];
    }

    public function hasStatisticalInformationNeed(KnowledgeQueryPlan $plan): bool
    {
        return FaoStatQclDimensionResolver::hasQuantitativeStatisticalNeed($plan);
    }

    /**
     * @param  array<string, string>  $need
     * @param  array<string, mixed>  $observation
     * @return list<string>
     */
    private function dimensionMismatches(array $need, array $observation): array
    {
        $map = [
            'area' => ['area_code', 'area'],
            'item' => ['item_code', 'item'],
            'element' => ['query_element_code', 'element_code', 'response_element_code'],
            'year' => ['year', 'year_code'],
        ];
        $mismatches = [];
        foreach ($map as $filter => $obsKeys) {
            if (! isset($need[$filter])) {
                continue;
            }
            $wanted = (string) $need[$filter];
            $matched = false;
            foreach ($obsKeys as $key) {
                if (isset($observation[$key]) && (string) $observation[$key] === $wanted) {
                    $matched = true;
                    break;
                }
            }
            if (! $matched) {
                $mismatches[] = $filter;
            }
        }

        return $mismatches;
    }
}
