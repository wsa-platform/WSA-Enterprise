<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class FarmGuardDiseaseAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'farm_guard';
    }

    protected function providerId(): string
    {
        return 'farm_guard';
    }

    protected function providerName(): string
    {
        return 'Farm Guard';
    }
}
