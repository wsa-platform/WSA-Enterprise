<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Production activation states. Distinct from the Phase 4 verification lifecycle.
 * ACTIVATABLE does not mean ACTIVE.
 */
final class FaoStatDomainActivationState
{
    public const DISCOVERED = 'DISCOVERED';

    public const VERIFIED = 'VERIFIED';

    public const ACTIVATABLE = 'ACTIVATABLE';

    public const ACTIVE = 'ACTIVE';

    public const DISABLED = 'DISABLED';

    public const FAILED = 'FAILED';
}
