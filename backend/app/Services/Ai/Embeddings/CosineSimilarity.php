<?php

namespace App\Services\Ai\Embeddings;

final class CosineSimilarity
{
    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     */
    public static function score(array $left, array $right): float
    {
        $count = count($left);
        if ($count === 0 || $count !== count($right)) {
            return 0.0;
        }

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $a = (float) $left[$i];
            $b = (float) $right[$i];
            if (! is_finite($a) || ! is_finite($b)) {
                return 0.0;
            }
            $dot += $a * $b;
            $leftNorm += $a * $a;
            $rightNorm += $b * $b;
        }

        if ($leftNorm <= 0.0 || $rightNorm <= 0.0) {
            return 0.0;
        }

        return round($dot / (sqrt($leftNorm) * sqrt($rightNorm)), 6);
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    public static function l2Normalize(array $vector): array
    {
        $norm = 0.0;
        foreach ($vector as $value) {
            $value = (float) $value;
            $norm += $value * $value;
        }
        if ($norm <= 0.0) {
            return $vector;
        }
        $scale = 1 / sqrt($norm);
        $normalized = [];
        foreach ($vector as $value) {
            $normalized[] = round(((float) $value) * $scale, 8);
        }

        return $normalized;
    }
}
