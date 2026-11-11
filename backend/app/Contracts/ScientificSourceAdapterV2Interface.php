<?php

namespace App\Contracts;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceSearchRequest;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Stage 3 scientific source adapter contract v2 (ADR-023 §8.36).
 *
 * Extends v1 so that every v2 adapter still runs wherever a v1 adapter is expected.
 * searchRequest() may throw only expected source failures (ScientificSourceFailureClassifier);
 * anything else is a defect and propagates.
 */
interface ScientificSourceAdapterV2Interface extends ScientificSourceAdapterInterface
{
    public const V2_CONTRACT_VERSION = 'scientific_source_adapter.v2';

    public function descriptor(): ScientificSourceAdapterDescriptor;

    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome;
}
