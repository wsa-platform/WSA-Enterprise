<?php

namespace App\Contracts\Agriculture;

use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;

/**
 * Plant disease analysis family — REST adapters only (no Python in Laravel).
 */
interface PlantDiseaseAnalysisProviderInterface
{
    public function descriptor(): ProviderDescriptor;

    public function health(): ProviderHealthStatus;

    public function analyze(ProviderQueryInput $input): CanonicalAgriculturalResult;
}
