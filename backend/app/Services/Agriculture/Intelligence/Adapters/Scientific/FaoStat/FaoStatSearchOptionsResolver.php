<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * FAOSTAT search options from the plan.
 * Copies numeric FAO codes when present; otherwise resolves verified QCL labels only.
 * Never passes OpenAlex Consensus domain=agri as a FAOSTAT dataset code.
 * Never treats WSA crop taxonomy IDs as FAOSTAT item codes.
 *
 * Multi-measure questions with complete area/item/year decompose into one canonical
 * query per measure. Incomplete multi-measure plans stay AMBIGUOUS_MEASURES (no guess).
 */
final class FaoStatSearchOptionsResolver
{
    /**
     * Primary options bag for gates/diagnostics (single-element view).
     *
     * @return array<string, string>
     */
    public static function fromPlan(KnowledgeQueryPlan $plan): array
    {
        $queries = self::canonicalQueriesFromPlan($plan);
        if ($queries === []) {
            return self::buildBase($plan, null, FaoStatPipelineOutcome::INCOMPLETE_FILTERS, []);
        }
        if (count($queries) === 1) {
            return $queries[0];
        }

        // Multi-query decomposition: diagnostic bag keeps shared dims and all measures,
        // without a single element (gate must not prefer one measure).
        $diagnostic = $queries[0];
        unset($diagnostic['element'], $diagnostic['element_code'], $diagnostic['query_element_code']);
        $diagnostic['element_resolution_status'] = FaoStatPipelineOutcome::DECOMPOSED_MEASURES;
        $measures = [];
        foreach ($queries as $queryOptions) {
            foreach (explode(',', (string) ($queryOptions['requested_measures'] ?? '')) as $measure) {
                $measure = trim($measure);
                if ($measure !== '') {
                    $measures[$measure] = true;
                }
            }
        }
        if ($measures !== []) {
            $diagnostic['requested_measures'] = implode(',', array_keys($measures));
        }
        $diagnostic['canonical_query_count'] = (string) count($queries);

        return $diagnostic;
    }

    /**
     * Canonical FAOSTAT provider queries for this plan.
     * One entry per distinct measure when multi-measure dims are complete.
     *
     * @return list<array<string, string>>
     */
    public static function canonicalQueriesFromPlan(KnowledgeQueryPlan $plan): array
    {
        $base = self::buildBase($plan, null, 'pending', []);
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];

        $explicitElement = self::firstNumeric($constraints, [
            'element_code', 'query_element_code', 'fao_element_code', 'element',
        ]);
        $structuredMeasures = FaoStatQclElementSemantics::detectMeasuresInSurface(trim(implode(' ', array_filter([
            (string) ($constraints['requested_property_surface'] ?? ''),
            (string) ($constraints['element_label'] ?? ''),
            (string) ($constraints['measure'] ?? ''),
            (string) ($constraints['fao_element_label'] ?? ''),
        ]))));

        if ($explicitElement !== null) {
            $codeMeasure = FaoStatQclElementSemantics::measureForAnyCode($explicitElement);
            if ($codeMeasure !== null
                && count($structuredMeasures) === 1
                && $structuredMeasures[0] !== $codeMeasure
            ) {
                return [self::buildBase(
                    $plan,
                    null,
                    FaoStatPipelineOutcome::MEASURE_CONFLICT,
                    array_values(array_unique(array_filter([$codeMeasure, $structuredMeasures[0]]))),
                )];
            }

            return [self::buildBase($plan, $explicitElement, 'resolved', $codeMeasure !== null ? [$codeMeasure] : [])];
        }

        $resolution = FaoStatQclDimensionResolver::resolveQueryElement($plan);
        $measures = $resolution['measures'];

        if ($resolution['status'] === 'resolved' && $resolution['element'] !== null) {
            return [self::buildBase($plan, $resolution['element'], 'resolved', $measures)];
        }

        if ($resolution['status'] === 'ambiguous' && count($measures) > 1) {
            $complete = isset($base['area'], $base['item'], $base['year']);
            if ($complete) {
                $queries = [];
                foreach ($measures as $measure) {
                    $element = FaoStatQclElementSemantics::queryCodeForMeasure($measure);
                    if ($element === null) {
                        continue;
                    }
                    $queries[] = self::buildBase(
                        $plan,
                        $element,
                        FaoStatPipelineOutcome::DECOMPOSED_MEASURES,
                        $measures,
                    );
                }

                return $queries !== [] ? $queries : [self::buildBase(
                    $plan,
                    null,
                    FaoStatPipelineOutcome::AMBIGUOUS_MEASURES,
                    $measures,
                )];
            }

            return [self::buildBase(
                $plan,
                null,
                FaoStatPipelineOutcome::AMBIGUOUS_MEASURES,
                $measures,
            )];
        }

        return [self::buildBase($plan, null, $resolution['status'], $measures)];
    }

    /**
     * @param  list<string>  $measures
     * @return array<string, string>
     */
    private static function buildBase(
        KnowledgeQueryPlan $plan,
        ?string $element,
        string $elementStatus,
        array $measures,
    ): array {
        $query = $plan->normalizedQuery;
        $constraints = is_array($query->constraints) ? $query->constraints : [];

        $out = [];
        $pairs = [
            'area' => ['area_code', 'fao_area_code', 'area'],
            'item' => ['item_code', 'fao_item_code', 'item'],
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
            // Non-QCL: preserve explicit codes only; do not apply QCL measure resolution.
            if ($element !== null) {
                $out['element'] = $element;
                $out['element_code'] = $element;
                $out['query_element_code'] = $element;
            } else {
                $fromConstraints = self::firstNumeric($constraints, [
                    'element_code', 'query_element_code', 'fao_element_code', 'element',
                ]);
                if ($fromConstraints !== null) {
                    $out['element'] = $fromConstraints;
                    $out['element_code'] = $fromConstraints;
                    $out['query_element_code'] = $fromConstraints;
                }
            }
            $out['element_resolution_status'] = $elementStatus;
            if ($measures !== []) {
                $out['requested_measures'] = implode(',', $measures);
            }

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
            $item = FaoStatQclDimensionResolver::itemCodeFromCropIdentity(
                is_string($query->cropId) ? $query->cropId : null,
                is_string($query->crop) ? $query->crop : null,
                is_string($query->scientificName) ? $query->scientificName : null,
                $blob,
            );
            if ($item !== null) {
                $out['item'] = $item;
            }
        }

        if (! isset($out['year'])) {
            $year = FaoStatQclDimensionResolver::yearCode($constraints, $blob);
            if ($year !== null) {
                $out['year'] = $year;
            }
        }

        $out['element_resolution_status'] = $elementStatus;
        if ($measures !== []) {
            $out['requested_measures'] = implode(',', $measures);
        }

        if ($element !== null) {
            $out['element'] = $element;
            $out['element_code'] = $element;
            $out['query_element_code'] = $element;
        }

        $cropLabel = is_string($query->crop) && $query->crop !== ''
            ? $query->crop
            : (is_string($query->cropId) ? $query->cropId : '');
        if ($cropLabel !== '' && preg_match('/^\d{1,8}$/', $cropLabel) !== 1) {
            $out['item_label'] = $cropLabel;
        }
        $areaLabel = is_string($query->location) && $query->location !== ''
            ? $query->location
            : (string) ($constraints['location'] ?? '');
        if ($areaLabel !== '') {
            $out['area_label'] = $areaLabel;
        }
        $elementLabel = trim((string) ($constraints['requested_property_surface'] ?? $constraints['element_label'] ?? ''));
        if ($elementLabel !== '') {
            $out['element_label'] = $elementLabel;
        } elseif ($element !== null) {
            $measure = FaoStatQclElementSemantics::measureForQueryCode($element);
            if ($measure === FaoStatQclElementSemantics::MEASURE_YIELD) {
                $out['element_label'] = 'Yield';
            } elseif ($measure === FaoStatQclElementSemantics::MEASURE_AREA_HARVESTED) {
                $out['element_label'] = 'Area harvested';
            } elseif ($measure === FaoStatQclElementSemantics::MEASURE_PRODUCTION_QUANTITY) {
                $out['element_label'] = 'Production Quantity';
            }
        }
        if (isset($out['year'])) {
            $out['year_label'] = $out['year'];
        }
        if (isset($out['item'])) {
            $out['item_code'] = $out['item'];
        }
        if (isset($out['area'])) {
            $out['area_code'] = $out['area'];
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
