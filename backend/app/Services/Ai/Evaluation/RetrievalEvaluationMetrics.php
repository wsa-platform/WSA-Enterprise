<?php

namespace App\Services\Ai\Evaluation;

/**
 * Deterministic IR metrics over ranked retrieval IDs.
 *
 * Definitions (K = cut size after production ranking):
 * - Precision@K = |relevant ∩ top-K| / |top-K|  (1.0 when both expected and retrieved are empty)
 * - Recall@K    = |relevant ∩ top-K| / |expected| (1.0 when expected is empty)
 * - F1@K        = harmonic mean of precision and recall (0 when both are 0)
 * - Hit@K       = 1 if at least one expected ID is in top-K; 1 when both lists are empty; else 0
 * - MRR         = 1 / rank of the first expected ID in top-K, else 0 (0 when expected is empty)
 *
 * Duplicate retrieved IDs keep first rank. Duplicate expected IDs are a set.
 */
class RetrievalEvaluationMetrics
{
    /**
     * @param  list<string>  $retrievedIds
     * @param  list<string>  $expectedIds
     * @return array{k: int, precision: float, recall: float, f1: float, hit: float, mrr: float}
     */
    public function score(array $retrievedIds, array $expectedIds, int $k): array
    {
        $k = max(1, $k);
        $retrieved = $this->uniquePreserveOrder($retrievedIds);
        $expected = array_values(array_unique(array_values($expectedIds)));
        $top = array_slice($retrieved, 0, $k);
        $expectedSet = array_fill_keys($expected, true);

        $truePositives = 0;
        foreach ($top as $id) {
            if (isset($expectedSet[$id])) {
                $truePositives++;
            }
        }

        $precision = $this->precision($truePositives, count($top), $expected === []);
        $recall = $this->recall($truePositives, count($expected));
        $f1 = $this->f1($precision, $recall);
        $hit = $this->hit($truePositives, $expected === [], $top === []);
        $mrr = $this->mrr($top, $expectedSet, $expected === []);

        return [
            'k' => $k,
            'precision' => $this->round($precision),
            'recall' => $this->round($recall),
            'f1' => $this->round($f1),
            'hit' => $this->round($hit),
            'mrr' => $this->round($mrr),
        ];
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    public function uniquePreserveOrder(array $ids): array
    {
        $unique = [];
        foreach ($ids as $id) {
            if (! is_string($id) || $id === '' || isset($unique[$id])) {
                continue;
            }
            $unique[$id] = $id;
        }

        return array_values($unique);
    }

    private function precision(int $truePositives, int $retrievedCount, bool $expectedEmpty): float
    {
        if ($retrievedCount === 0) {
            return $expectedEmpty ? 1.0 : 0.0;
        }

        return $truePositives / $retrievedCount;
    }

    private function recall(int $truePositives, int $expectedCount): float
    {
        if ($expectedCount === 0) {
            return 1.0;
        }

        return $truePositives / $expectedCount;
    }

    private function f1(float $precision, float $recall): float
    {
        $sum = $precision + $recall;
        if ($sum <= 0.0) {
            return 0.0;
        }

        return (2.0 * $precision * $recall) / $sum;
    }

    private function hit(int $truePositives, bool $expectedEmpty, bool $retrievedEmpty): float
    {
        if ($expectedEmpty) {
            return $retrievedEmpty ? 1.0 : 0.0;
        }

        return $truePositives > 0 ? 1.0 : 0.0;
    }

    /**
     * @param  list<string>  $top
     * @param  array<string, true>  $expectedSet
     */
    private function mrr(array $top, array $expectedSet, bool $expectedEmpty): float
    {
        if ($expectedEmpty) {
            return 0.0;
        }
        foreach ($top as $index => $id) {
            if (isset($expectedSet[$id])) {
                return 1.0 / ($index + 1);
            }
        }

        return 0.0;
    }

    private function round(float $value): float
    {
        if (! is_finite($value)) {
            return 0.0;
        }

        return round($value, 4);
    }
}
