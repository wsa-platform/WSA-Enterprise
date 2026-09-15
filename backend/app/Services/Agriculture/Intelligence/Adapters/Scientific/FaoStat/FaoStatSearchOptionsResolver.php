<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * FAOSTAT search options from the plan.
 * Copies numeric codes when present; otherwise resolves verified QCL labels only.
 * Never passes OpenAlex Consensus domain=agri as a FAOSTAT dataset code.
 */
final class FaoStatSearchOptionsResolver
{
    /**
     * @return array<string, string>
     */
    public static function fromPlan(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];

        $out = [];
        $pairs = [
            'area' => ['area_code', 'area', 'fao_area_code'],
            'item' => ['item_code', 'item', 'fao_item_code'],
            'element' => ['element_code', 'element', 'query_element_code', 'fao_element_code'],
            'year' => ['year', 'year_code', 'fao_year'],
        ];
        foreach ($pairs as $target => $keys) {
            $code = self::firstNumeric($constraints, $keys);
            if ($code !== null) {
                $out[$target] = $code;
            }
        }

        $out['domain'] = FaoStatQclDimensionResolver::sanitizeDomain(
            (string) ($constraints['domain'] ?? $constraints['domain_code'] ?? ''),
        );

        if ($out['domain'] !== FaoStatQclDimensionResolver::DOMAIN_QCL) {
            return $out;
        }

        $blob = strtolower(trim(implode(' ', array_filter([
            $query->originalQuestion,
            $query->normalizedQuestion,
            is_string($query->crop) ? $query->crop : '',
            is_string($query->cropId) ? $query->cropId : '',
            is_string($query->location) ? $query->location : '',
            is_string($constraints['location'] ?? null) ? (string) $constraints['location'] : '',
        ]))));

        if (! isset($out['area'])) {
            $area = FaoStatQclDimensionResolver::areaCode(
                is_string($query->location) && $query->location !== ''
                    ? $query->location
                    : (string) ($constraints['location'] ?? ''),
                $blob,
            );
            if ($area !== null) {
                $out['area'] = $area;
            }
        }

        if (! isset($out['item'])) {
            $crop = is_string($query->cropId) && $query->cropId !== ''
                ? $query->cropId
                : (is_string($query->crop) ? $query->crop : '');
            $item = FaoStatQclDimensionResolver::itemCode($crop, $blob);
            if ($item !== null) {
                $out['item'] = $item;
            }
        }

        if (! isset($out['element'])) {
            $element = FaoStatQclDimensionResolver::queryElementCode($plan);
            if ($element !== null) {
                $out['element'] = $element;
            }
        }

        if (! isset($out['year'])) {
            $year = FaoStatQclDimensionResolver::yearCode($constraints, $blob);
            if ($year !== null) {
                $out['year'] = $year;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $keys
     */
    private static function firstNumeric(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }
            $code = trim((string) $source[$key]);
            if ($code !== '' && preg_match('/^\d{1,8}$/', $code) === 1) {
                return $code;
            }
        }

        return null;
    }
}
