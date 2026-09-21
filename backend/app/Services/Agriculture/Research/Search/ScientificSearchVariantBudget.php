<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Phase 3-B: bounded, deterministic scholarly variant budget at the orchestrator boundary.
 *
 * Applies after {@see ScientificSearchQueryBuilder::buildVariantsFromPlan()} so builder WIP
 * remains untouched while equivalent variants and global fan-out stay controlled.
 */
final class ScientificSearchVariantBudget
{
    /** Global scholarly NL variant ceiling (must stay aligned with builder MAX_VARIANTS). */
    public const MAX_VARIANTS = 5;

    /**
     * @param  list<string>  $variants
     * @return array{
     *     variants: list<string>,
     *     input_count: int,
     *     equivalent_suppressed: int,
     *     truncated: int,
     *     output_count: int
     * }
     */
    public static function apply(array $variants): array
    {
        $inputCount = count($variants);
        $unique = [];
        $seen = [];
        $equivalentSuppressed = 0;

        foreach ($variants as $variant) {
            if (! is_string($variant)) {
                continue;
            }
            $trimmed = trim($variant);
            if ($trimmed === '') {
                continue;
            }
            $identity = self::identityKey($trimmed);
            if (isset($seen[$identity])) {
                $equivalentSuppressed++;

                continue;
            }
            $seen[$identity] = true;
            $unique[] = $trimmed;
        }

        $truncated = 0;
        if (count($unique) > self::MAX_VARIANTS) {
            $truncated = count($unique) - self::MAX_VARIANTS;
            $unique = array_slice($unique, 0, self::MAX_VARIANTS);
        }

        return [
            'variants' => $unique,
            'input_count' => $inputCount,
            'equivalent_suppressed' => $equivalentSuppressed,
            'truncated' => $truncated,
            'output_count' => count($unique),
        ];
    }

    public static function identityKey(string $variant): string
    {
        $normalized = mb_strtolower(trim($variant));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return $normalized;
    }
}