<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Runtime on/off switch of a registered source, evaluated on every call (never cached).
 */
interface ScientificSourceEnablement
{
    public function isEnabled(): bool;
}
