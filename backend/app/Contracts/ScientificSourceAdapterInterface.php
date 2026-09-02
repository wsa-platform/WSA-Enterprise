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

    public function search(string $query, int $limit = 10): ScientificSourceSearchOutcome;
}
