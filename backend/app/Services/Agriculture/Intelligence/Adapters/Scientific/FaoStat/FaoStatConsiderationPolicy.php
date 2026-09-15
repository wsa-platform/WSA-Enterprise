<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * FAOSTAT consideration is universal when the Developer Portal flag is on.
 * There is no agricultural-question detector.
 */
final class FaoStatConsiderationPolicy
{
    public const CONSIDERED = 'CONSIDERED';

    public const DISABLED = 'DISABLED';

    public static function isEnabled(): bool
    {
        return filter_var(config('agricultural_intelligence.faostat.enabled', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Every WSA question considers FAOSTAT when the portal is enabled.
     */
    public static function decision(): string
    {
        return self::isEnabled() ? self::CONSIDERED : self::DISABLED;
    }
}
