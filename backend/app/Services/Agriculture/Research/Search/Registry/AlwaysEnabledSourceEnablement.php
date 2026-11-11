<?php

namespace App\Services\Agriculture\Research\Search\Registry;

final readonly class AlwaysEnabledSourceEnablement implements ScientificSourceEnablement
{
    public function isEnabled(): bool
    {
        return true;
    }
}
