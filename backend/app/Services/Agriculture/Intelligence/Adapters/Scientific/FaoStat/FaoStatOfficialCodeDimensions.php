<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Official FAOSTAT code-list dimension ids. Never /codes/areas/.
 */
final class FaoStatOfficialCodeDimensions
{
    /** @var list<string> */
    public const IDS = ['items', 'elements', 'countries', 'regions', 'specialgroups', 'years'];

    public static function normalize(string $dimensionId): ?string
    {
        $id = strtolower(trim($dimensionId));
        $id = str_replace([' ', '_'], '', $id);

        return match ($id) {
            'items', 'item' => 'items',
            'elements', 'element' => 'elements',
            'countries', 'country', 'area', 'areas' => 'countries',
            'regions', 'region' => 'regions',
            'specialgroups', 'specialgroup' => 'specialgroups',
            'years', 'year' => 'years',
            default => in_array($id, self::IDS, true) ? $id : null,
        };
    }

    public static function assertOfficial(string $dimensionId): string
    {
        $normalized = self::normalize($dimensionId);
        if ($normalized === null || $dimensionId === 'areas') {
            throw new FaoStatPortalException(
                FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR,
                'unsupported_code_dimension',
            );
        }

        return $normalized;
    }
}
