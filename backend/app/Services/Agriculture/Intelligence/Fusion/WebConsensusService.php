<?php

namespace App\Services\Agriculture\Intelligence\Fusion;

use App\Services\Agriculture\Intelligence\DTO\WebConsensusResult;

/**
 * Web consensus layer — ranges, agreement, representative value (not first-hit).
 */
final class WebConsensusService
{
    /**
     * @param  list<array<string, mixed>>  $webItems
     */
    public function build(array $webItems): WebConsensusResult
    {
        if ($webItems === []) {
            return new WebConsensusResult(hasConsensus: false, status: 'empty');
        }

        $numeric = [];
        $sources = [];
        $values = [];

        foreach ($webItems as $item) {
            $sources[] = (string) ($item['provider_id'] ?? $item['source'] ?? 'web');
            $val = $item['numeric_value'] ?? $item['value'] ?? null;
            $entry = [
                'value' => $val,
                'text' => $item['snippet'] ?? $item['title'] ?? $item['text'] ?? null,
                'url' => $item['url'] ?? null,
                'quality' => (float) ($item['quality'] ?? $item['confidence'] ?? 0.5),
                'source' => $item['provider_id'] ?? $item['source'] ?? 'web',
            ];
            $values[] = $entry;
            if (is_numeric($val)) {
                $numeric[] = [
                    'value' => (float) $val,
                    'quality' => $entry['quality'],
                    'source' => $entry['source'],
                ];
            }
        }

        $sources = array_values(array_unique($sources));

        if ($numeric === []) {
            // Textual agreement: unique titles / snippets
            $texts = array_filter(array_map(
                static fn (array $v): string => strtolower(trim((string) ($v['text'] ?? ''))),
                $values,
            ));
            $unique = array_unique($texts);
            $agreement = count($texts) > 0
                ? 1.0 - ((count($unique) - 1) / max(count($texts), 1))
                : 0.0;
            $best = null;
            $bestQ = -1.0;
            foreach ($values as $v) {
                if ($v['quality'] > $bestQ) {
                    $bestQ = $v['quality'];
                    $best = $v['text'];
                }
            }

            return new WebConsensusResult(
                hasConsensus: $agreement >= 0.5 && count($values) >= 1,
                representativeValue: $best,
                agreementScore: round(max(0.0, min(1.0, $agreement)), 4),
                values: $values,
                sources: $sources,
                quality: [
                    'item_count' => count($values),
                    'numeric_count' => 0,
                    'method' => 'textual_quality_weighted',
                ],
                status: count($values) >= 1 ? 'textual' : 'empty',
            );
        }

        $nums = array_column($numeric, 'value');
        $min = min($nums);
        $max = max($nums);
        $span = max(abs($max - $min), 1e-9);
        $mid = ($min + $max) / 2.0;
        $within = 0;
        foreach ($nums as $n) {
            if (abs($n - $mid) / max(abs($mid), $span, 1e-9) <= 0.2) {
                $within++;
            }
        }
        $agreement = $within / count($nums);

        // Quality-weighted representative (not first-hit)
        $weightSum = 0.0;
        $weighted = 0.0;
        foreach ($numeric as $n) {
            $w = max(0.01, $n['quality']);
            $weightSum += $w;
            $weighted += $n['value'] * $w;
        }
        $representative = $weightSum > 0 ? $weighted / $weightSum : $mid;

        $conflicts = [];
        if (($max - $min) / max(abs($mid), 1e-9) > 0.25) {
            $conflicts[] = [
                'type' => 'numeric_range_conflict',
                'min' => $min,
                'max' => $max,
            ];
        }

        return new WebConsensusResult(
            hasConsensus: $agreement >= 0.5 && $conflicts === [],
            representativeValue: round($representative, 6),
            rangeMin: $min,
            rangeMax: $max,
            agreementScore: round($agreement, 4),
            values: $values,
            conflicts: $conflicts,
            sources: $sources,
            quality: [
                'item_count' => count($values),
                'numeric_count' => count($numeric),
                'method' => 'quality_weighted_mean',
            ],
            status: $conflicts === [] ? 'consensus' : 'conflicted',
        );
    }
}
