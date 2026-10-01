<?php

namespace App\Services\Agriculture\Research\Path;

/**
 * CGHIA fallback layers as domain relationship semantics only (T8).
 *
 * Does not execute fallback or invoke adapters/HTTP.
 */
enum PathFallbackLayer: string
{
    case L1 = 'L1';
    case L2 = 'L2';
    case L3 = 'L3';

    public function designMeaning(): string
    {
        return match ($this) {
            self::L1 => 'native / protocol / bulk (Cap-gated primary)',
            self::L2 => 'EXTERNAL aggregator',
            self::L3 => 'manual (P18)',
        };
    }

    public function orderIndex(): int
    {
        return match ($this) {
            self::L1 => 1,
            self::L2 => 2,
            self::L3 => 3,
        };
    }
}
