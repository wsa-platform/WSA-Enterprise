<?php

namespace App\Contracts;

use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Stage 3 scientific source adapter contract.
 * Each adapter queries one external scholarly index and returns normalized outcomes.
 */
interface ScientificSourceAdapterInterface
{
    public function sourceKey(): string;

    public function displayName(): string;

    /**
     * @param  array<string, mixed>  $options  Provider-specific filters (Consensus: domain, country, …)
     */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome;
}
