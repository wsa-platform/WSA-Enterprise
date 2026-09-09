<?php

namespace App\Contracts\Agriculture;

use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\ProviderDescriptor;
use App\Services\Agriculture\Intelligence\DTO\ProviderHealthStatus;
use App\Services\Agriculture\Intelligence\DTO\ProviderQueryInput;

/**
 * Universal agricultural intelligence provider contract (ADR-001).
 *
 * Implementations isolate failures and never leak credentials.
 */
interface AgriculturalProviderInterface
{
    public function descriptor(): ProviderDescriptor;

    public function health(): ProviderHealthStatus;

    /**
     * Retrieve provider-agnostic agricultural evidence for a query.
     * Must never throw across the orchestrator boundary — return failed/empty results instead.
     */
    public function retrieve(ProviderQueryInput $input): CanonicalAgriculturalResult;
}
