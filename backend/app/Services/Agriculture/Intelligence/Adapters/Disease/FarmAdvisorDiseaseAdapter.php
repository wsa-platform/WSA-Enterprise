<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class FarmAdvisorDiseaseAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'farm_advisor';
    }

    protected function providerId(): string
    {
        return 'farm_advisor';
    }

    protected function providerName(): string
    {
        return 'Farm Advisor';
    }
}
