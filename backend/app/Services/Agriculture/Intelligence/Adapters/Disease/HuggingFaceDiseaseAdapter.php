<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class HuggingFaceDiseaseAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'huggingface';
    }

    protected function providerId(): string
    {
        return 'huggingface_disease';
    }

    protected function providerName(): string
    {
        return 'HuggingFace Disease';
    }
}
