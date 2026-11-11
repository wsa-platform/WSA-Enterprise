<?php

namespace App\Services\Agriculture\Research\Search\Registry;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatRuntimePolicy;

/**
 * Delegates to the canonical FAOSTAT flag so the retired Fenix flag can never enable it.
 */
final readonly class FaoStatRuntimeSourceEnablement implements ScientificSourceEnablement
{
    public function isEnabled(): bool
    {
        return FaoStatRuntimePolicy::isEnabled();
    }
}
