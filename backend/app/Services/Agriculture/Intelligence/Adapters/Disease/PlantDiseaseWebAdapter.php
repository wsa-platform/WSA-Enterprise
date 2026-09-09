<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class PlantDiseaseWebAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'plant_disease_web';
    }

    protected function providerId(): string
    {
        return 'plant_disease_web';
    }

    protected function providerName(): string
    {
        return 'Plant Disease Web';
    }
}
