<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class GreenSenseDiseaseAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'greensense';
    }

    protected function providerId(): string
    {
        return 'greensense';
    }

    protected function providerName(): string
    {
        return 'GreenSense';
    }
}
