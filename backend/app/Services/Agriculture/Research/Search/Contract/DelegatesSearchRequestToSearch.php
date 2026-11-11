<?php

namespace App\Services\Agriculture\Research\Search\Contract;

use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Contract v2 entry point for adapters whose v1 search() already honours every request field
 * (ADR-023 §8.36). The query text reaches search() unchanged (D-04).
 */
trait DelegatesSearchRequestToSearch
{
    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome
    {
        return $this->search($request->queryText, $request->limit, $request->options);
    }
}
