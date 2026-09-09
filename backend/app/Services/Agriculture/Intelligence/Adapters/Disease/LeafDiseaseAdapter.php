<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Disease;

final class LeafDiseaseAdapter extends AbstractRestDiseaseAdapter
{
    protected function configKey(): string
    {
        return 'leaf_disease';
    }

    protected function providerId(): string
    {
        return 'leaf_disease';
    }

    protected function providerName(): string
    {
        return 'Leaf Disease';
    }
}
