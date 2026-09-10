<?php

namespace App\Services\Agriculture\Intelligence\Fusion;

use App\Services\Agriculture\Intelligence\DTO\MeasurementValue;
use App\Services\Agriculture\Intelligence\DTO\WebConsensusResult;
use App\Services\Agriculture\Intelligence\Normalization\UnitNormalizationService;

/**
 * Quality-weighted numeric consensus for web evidence.
 *
 * Only measurement-compatible, context-compatible candidates are clustered.
 * Ranges are preserved (no fabricated midpoint). Unrelated numbers (dates,
 * years, counts, IDs, durations, money) are excluded via extraction context,
 * not a hard-coded number blacklist.
 */
final class WebConsensusService
{
    public function __construct(
        private UnitNormalizationService $units,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $webItems
     */
    public function build(array $webItems): WebConsensusResult
    {
        $candidates = [];
        foreach ($webItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $classified = $this->classifyCandidate($item);
            if ($classified === null) {
                continue;
            }
            $candidates[] = $classified;
        }

        if ($candidates === []) {
            return $this->textual($webItems);
        }

        $clusters = [];
        foreach ($candidates as $candidate) {
            $clusters[$candidate['cluster_key']][] = $candidate;
        }

        usort($clusters, static fn (array $a, array $b): int => count($b) <=> count($a));
        $cluster = $clusters[0] ?? [];

        if ($cluster === []) {
            return $this->textual($webItems);
        }

        $conflicts = [];
        $sources = [];
        $pointValues = [];
        $rangeBounds = [];

        foreach ($cluster as $row) {
            $sources[] = $row['source'];
            if ($row['is_range']) {
                $rangeBounds[] = [$row['range_min'], $row['range_max']];
            } elseif ($row['value'] !== null) {
                $pointValues[] = $row;
            }
        }

        $rangeConflict = $this->rangesConflict($rangeBounds);
        $pointConflict = $this->pointsConflict($pointValues);

        if ($rangeConflict || $pointConflict) {
            $conflicts[] = [
                'type' => $rangeConflict ? 'incompatible_ranges' : 'numeric_spread',
                'note' => 'compatible cluster contains unresolved conflict; no fabricated average',
            ];
        }

        $rangeMin = null;
        $rangeMax = null;
        $representative = null;
        $hasConsensus = false;
        $status = 'textual';

        if ($rangeBounds !== [] && ! $rangeConflict) {
            $overlap = $this->overlappingRange($rangeBounds);
            if ($overlap !== null) {
                $rangeMin = $overlap[0];
                $rangeMax = $overlap[1];
                $representative = $this->formatRange($rangeMin, $rangeMax);
                $hasConsensus = true;
                $status = 'consensus';
            }
        }

        if ($pointValues !== [] && ! $pointConflict && $rangeBounds === []) {
            $weighted = 0.0;
            $weightSum = 0.0;
            $mins = [];
            $maxs = [];
            foreach ($pointValues as $row) {
                $q = $row['quality'];
                $weighted += $row['value'] * $q;
                $weightSum += $q;
                $mins[] = $row['value'];
                $maxs[] = $row['value'];
            }
            $representative = $weightSum > 0 ? $weighted / $weightSum : $pointValues[0]['value'];
            $rangeMin = min($mins);
            $rangeMax = max($maxs);
            $hasConsensus = true;
            $status = 'consensus';
        }

        if ($pointValues !== [] && $rangeBounds !== [] && ! $rangeConflict && ! $pointConflict) {
            $overlap = $this->overlappingRange($rangeBounds);
            if ($overlap !== null) {
                $allMins = array_merge([$overlap[0]], array_column($pointValues, 'value'));
                $allMaxs = array_merge([$overlap[1]], array_column($pointValues, 'value'));
                $rangeMin = min($allMins);
                $rangeMax = max($allMaxs);
                $representative = $this->formatRange($rangeMin, $rangeMax);
                $hasConsensus = true;
                $status = 'consensus';
            }
        }

        if ($rangeConflict || $pointConflict) {
            $status = 'conflicted';
            $hasConsensus = false;
            $representative = null;
            if ($rangeBounds !== []) {
                $rangeMin = min(array_column($rangeBounds, 0));
                $rangeMax = max(array_column($rangeBounds, 1));
            } elseif ($pointValues !== []) {
                $vals = array_column($pointValues, 'value');
                $rangeMin = min($vals);
                $rangeMax = max($vals);
            }
        }

        if (! $hasConsensus && $status !== 'conflicted') {
            return $this->textual($webItems);
        }

        return new WebConsensusResult(
            hasConsensus: $hasConsensus,
            representativeValue: $representative,
            rangeMin: $rangeMin,
            rangeMax: $rangeMax,
            sources: array_values(array_unique($sources)),
            conflicts: $conflicts,
            status: $status,
            values: $cluster,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function classifyCandidate(array $item): ?array
    {
        $context = $this->extractionContext($item);
        $unitRaw = strtolower(trim((string) ($item['unit'] ?? $item['measurement']['unit'] ?? '')));
        $rangeMin = $this->floatOrNull($item['range_min'] ?? $item['measurement']['range_min'] ?? null);
        $rangeMax = $this->floatOrNull($item['range_max'] ?? $item['measurement']['range_max'] ?? null);
        $value = $this->floatOrNull($item['numeric_value'] ?? $item['value'] ?? null);

        if ($value === null && ($rangeMin === null || $rangeMax === null)) {
            return null;
        }

        if ($this->isContaminatedContext($context, $unitRaw, $value, $rangeMin, $rangeMax)) {
            return null;
        }

        $normalized = $this->units->normalize($value ?? $rangeMin ?? 0.0, $unitRaw !== '' ? $unitRaw : null);
        $clusterKey = $this->clusterKey($unitRaw, $normalized);

        $isRange = $rangeMin !== null && $rangeMax !== null;
        if ($isRange && $value === null) {
            // Preserve the source range. Never invent a midpoint as a fact.
            $value = null;
        }

        return [
            'value' => $isRange ? null : $value,
            'range_min' => $rangeMin,
            'range_max' => $rangeMax,
            'is_range' => $isRange,
            'unit' => $unitRaw !== '' ? $unitRaw : null,
            'normalized_unit' => $normalized->normalizedUnit,
            'cluster_key' => $clusterKey,
            'quality' => (float) ($item['quality'] ?? $item['confidence'] ?? 0.5),
            'source' => (string) ($item['provider_id'] ?? $item['source'] ?? 'web'),
            'context' => $context,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractionContext(array $item): string
    {
        return strtolower(trim(implode(' ', array_filter([
            (string) ($item['snippet'] ?? ''),
            (string) ($item['title'] ?? ''),
            (string) ($item['context'] ?? ''),
            (string) ($item['text'] ?? ''),
        ]))));
    }

    private function clusterKey(string $unitRaw, MeasurementValue $normalized): string
    {
        if ($normalized->conversionSafe && $normalized->normalizedUnit !== null) {
            return 'dim:'.$normalized->normalizedUnit;
        }
        if ($unitRaw !== '') {
            return 'unit:'.$unitRaw;
        }

        return 'untyped';
    }

    private function isContaminatedContext(
        string $context,
        string $unit,
        ?float $value,
        ?float $rangeMin,
        ?float $rangeMax,
    ): bool {
        if ($this->looksLikeCalendarYearRange($unit, $rangeMin, $rangeMax)) {
            return true;
        }

        if ($this->looksLikeCalendarYearPoint($unit, $value, $context)) {
            return true;
        }

        if ($this->extractedNumberHasUnrelatedContext($context, $value, $rangeMin, $rangeMax)) {
            return true;
        }

        if ($unit === '' && $this->looksLikeCalendarDate($context, $value, $rangeMin, $rangeMax)) {
            return true;
        }

        return false;
    }

    private function extractedNumberHasUnrelatedContext(
        string $context,
        ?float $value,
        ?float $rangeMin,
        ?float $rangeMax,
    ): bool {
        if ($context === '') {
            return false;
        }

        $numbers = [];
        foreach ([$value, $rangeMin, $rangeMax] as $number) {
            if ($number !== null) {
                $numbers[] = $this->trimNumber($number);
            }
        }

        foreach (array_unique($numbers) as $number) {
            $quoted = preg_quote($number, '/');
            $bound = '/(?<![\d.])'.$quoted.'\s*(days?|hours?|hrs?|weeks?|months?|years?|yrs?|million|billion|trillion)\b/';
            if (preg_match($bound, $context) === 1) {
                return true;
            }
            if (preg_match('/[$€£]\s*'.$quoted.'\b/', $context) === 1) {
                return true;
            }
            if (preg_match('/\bpage\s+'.$quoted.'\b/', $context) === 1) {
                return true;
            }
            if (preg_match('/\b(?:id|uuid)\s*[:#]\s*'.$quoted.'\b/', $context) === 1) {
                return true;
            }
            if (preg_match('/\bversion\s*'.$quoted.'\b/', $context) === 1) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeCalendarDate(
        string $context,
        ?float $value,
        ?float $rangeMin,
        ?float $rangeMax,
    ): bool {
        $hasMonth = preg_match(
            '/\b(january|february|march|april|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|sept|oct|nov|dec)\.?\b/',
            $context,
        ) === 1 || preg_match('/\bmay\s+\d{1,2}\b/', $context) === 1;

        if (! $hasMonth && preg_match('/\b\d{1,2}\/\d{1,2}\/(?:19|20)\d{2}\b/', $context) !== 1) {
            return false;
        }

        foreach ([$value, $rangeMin, $rangeMax] as $number) {
            if ($number !== null && $number === (float) (int) $number && $number >= 1.0 && $number <= 31.0) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeCalendarYearRange(string $unit, ?float $rangeMin, ?float $rangeMax): bool
    {
        if ($unit !== '' || $rangeMin === null || $rangeMax === null) {
            return false;
        }

        if (! $this->isCalendarYear($rangeMin) || ! $this->isCalendarYear($rangeMax)) {
            return false;
        }

        return true;
    }

    private function looksLikeCalendarYearPoint(string $unit, ?float $value, string $context): bool
    {
        if ($unit !== '' || $value === null || ! $this->isCalendarYear($value)) {
            return false;
        }

        return preg_match('/\b(19|20)\d{2}\b/', $context) === 1
            || preg_match('/\b(year|years|dated|published|copyright)\b/', $context) === 1;
    }

    private function isCalendarYear(float $value): bool
    {
        return $value === (float) (int) $value && $value >= 1800.0 && $value <= 2100.0;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $ranges
     */
    private function rangesConflict(array $ranges): bool
    {
        if (count($ranges) < 2) {
            return false;
        }

        return $this->overlappingRange($ranges) === null;
    }

    /**
     * @param  list<array<string, mixed>>  $points
     */
    private function pointsConflict(array $points): bool
    {
        if (count($points) < 2) {
            return false;
        }
        $values = array_column($points, 'value');
        $min = min($values);
        $max = max($values);
        if ($min <= 0.0) {
            return ($max - $min) > 25.0;
        }

        return (($max - $min) / abs($min)) > 0.25;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $ranges
     * @return array{0: float, 1: float}|null
     */
    private function overlappingRange(array $ranges): ?array
    {
        $low = $ranges[0][0];
        $high = $ranges[0][1];
        foreach ($ranges as $range) {
            $low = max($low, $range[0]);
            $high = min($high, $range[1]);
        }

        if ($low > $high) {
            return null;
        }

        return [$low, $high];
    }

    private function formatRange(float $min, float $max): string
    {
        $left = $this->trimNumber($min);
        $right = $this->trimNumber($max);
        if ($left === $right) {
            return $left;
        }

        return $left.'–'.$right;
    }

    private function trimNumber(float $value): string
    {
        $formatted = rtrim(rtrim(sprintf('%.6f', $value), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  list<array<string, mixed>>  $webItems
     */
    private function textual(array $webItems): WebConsensusResult
    {
        $titles = [];
        foreach (array_slice($webItems, 0, 3) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $titles[] = (string) ($item['title'] ?? $item['snippet'] ?? '');
        }

        return new WebConsensusResult(
            hasConsensus: false,
            representativeValue: $titles[0] ?? null,
            rangeMin: null,
            rangeMax: null,
            sources: [],
            conflicts: [],
            status: 'textual',
            values: [],
        );
    }
}
