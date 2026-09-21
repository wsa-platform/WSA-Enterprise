<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;

/**
 * Phase-4 Catalog-safe land/classification validation signals.
 *
 * Intentionally does NOT call Catalog WIP land helpers
 * (hasLandTypeInventoryContent, isClassificationMethodologyOnlyWithoutInventory, …).
 * Those belong to Phase-6 Catalog work and must not change Phase-4 Gate/Matcher/
 * Directness/EVL semantics when Catalog WIP is present in the working tree.
 *
 * Lexical signals here are Phase-4-safe extraction only — NOT scientific truth,
 * sufficiency, disposition, or Library-save eligibility.
 */
final class CatalogSafeLandSignals
{
    public static function hasInventoryContent(string $haystack): bool
    {
        foreach ([
            'soil type', 'soil types', 'land type', 'land types', 'land class', 'land classes',
            'soil classification', 'land classification', 'soil map', 'land capability',
            'inventory of', 'nationwide inventory', 'soil taxonomy',
        ] as $signal) {
            if (self::mentions($haystack, $signal)) {
                return true;
            }
        }

        return false;
    }

    public static function isMethodologyOnlyWithoutInventory(string $haystack): bool
    {
        $methodMarkers = [
            'machine learning', 'deep learning', 'neural network', 'random forest',
            'cnn', 'gis', 'remote sensing',
        ];
        $hasMethod = false;
        foreach ($methodMarkers as $marker) {
            if (self::mentions($haystack, $marker)) {
                $hasMethod = true;
                break;
            }
        }

        return $hasMethod && ! self::hasInventoryContent($haystack);
    }

    /** @return list<string> */
    public static function offtopicMarkers(): array
    {
        return [
            'greenhouse', 'hydroponic', 'hydroponics', 'gerbera', 'ornamental',
            'machine learning', 'remote sensing', 'neural network',
            'protected cultivation', 'soilless',
        ];
    }

    public static function haystackMentionsLocation(string $haystack, string $location): bool
    {
        $location = mb_strtolower(trim($location));
        if ($location === '' || $haystack === '') {
            return false;
        }

        return self::mentions($haystack, $location);
    }

    public static function isLandOrSoilClassificationMethodQuestion(string $haystack): bool
    {
        return preg_match(
            '/\b(machine learning|deep learning|neural network|random forest|classification model|gis)\b/u',
            mb_strtolower($haystack),
        ) === 1;
    }

    private static function mentions(string $haystack, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') {
            return false;
        }

        $hay = mb_strtolower($haystack);

        return (method_exists(AgriculturalEntityCatalog::class, 'containsTerm')
                && AgriculturalEntityCatalog::containsTerm($haystack, $needle))
            || mb_strpos($hay, $needle) !== false;
    }
}
