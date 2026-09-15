<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Claim-level FAOSTAT support. Partial support is never promoted to full support.
 */
final class FaoStatSupportState
{
    public const SUPPORTED = 'SUPPORTED';

    public const PARTIALLY_SUPPORTED = 'PARTIALLY_SUPPORTED';

    public const NOT_SUPPORTED = 'NOT_SUPPORTED';

    public const NOT_APPLICABLE = 'NOT_APPLICABLE';

    public const UNVERIFIED = 'UNVERIFIED';
}
