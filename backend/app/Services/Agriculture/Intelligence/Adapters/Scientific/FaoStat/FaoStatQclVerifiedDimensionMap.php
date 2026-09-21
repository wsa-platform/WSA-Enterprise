<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Loads the versioned verified QCL area/item map from resources.
 * Search resolution never probes the live portal for unknown labels.
 */
final class FaoStatQclVerifiedDimensionMap
{
    /** @var array{version: string, provenance: string, areas: array<string, string>, items: array<string, string>}|null */
    private static ?array $payload = null;

    public static function path(): string
    {
        return base_path('resources/faostat/qcl_verified_dimension_map.php');
    }

    public static function version(): string
    {
        return (string) (self::payload()['version'] ?? '');
    }

    public static function provenance(): string
    {
        return (string) (self::payload()['provenance'] ?? '');
    }

    /**
     * @return array<string, string>
     */
    public static function areas(): array
    {
        $areas = self::payload()['areas'] ?? [];

        return is_array($areas) ? $areas : [];
    }

    /**
     * @return array<string, string>
     */
    public static function items(): array
    {
        $items = self::payload()['items'] ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * @return array{version: string, provenance: string, areas: array<string, string>, items: array<string, string>}
     */
    private static function payload(): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }

        $path = self::path();
        $loaded = is_file($path) ? require $path : [];
        if (! is_array($loaded)) {
            $loaded = [];
        }

        self::$payload = [
            'version' => (string) ($loaded['version'] ?? 'unknown'),
            'provenance' => (string) ($loaded['provenance'] ?? ''),
            'areas' => is_array($loaded['areas'] ?? null) ? $loaded['areas'] : [],
            'items' => is_array($loaded['items'] ?? null) ? $loaded['items'] : [],
        ];

        return self::$payload;
    }

    /** @internal testing */
    public static function resetCache(): void
    {
        self::$payload = null;
    }
}
