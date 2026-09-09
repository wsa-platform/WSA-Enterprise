<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class PlantDiseaseDetectorAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'plant_disease_detector';
    }

    protected function providerId(): string
    {
        return 'plant_disease_detector';
    }

    protected function providerName(): string
    {
        return 'Plant Disease Detector';
    }
}
