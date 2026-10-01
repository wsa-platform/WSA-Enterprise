<?php

namespace App\Services\Agriculture\Research\Coexistence;

/**
 * IU-09 runtime mode — does not own Cap/Path/Projection semantics.
 */
enum CoexistenceMode: string
{
    case LEGACY_ONLY = 'legacy_only';
    case CGHIA_ATTACHED = 'cghia_attached';

    public static function fromConfig(): self
    {
        $raw = strtolower(trim((string) config(
            'agricultural_intelligence.cghia_coexistence.mode',
            self::LEGACY_ONLY->value,
        )));

        return match ($raw) {
            self::CGHIA_ATTACHED->value, 'cghia', 'attached' => self::CGHIA_ATTACHED,
            default => self::LEGACY_ONLY,
        };
    }
}
