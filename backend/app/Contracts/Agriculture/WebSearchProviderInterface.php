<?php

namespace App\Contracts\Agriculture;

use App\Services\Agriculture\Intelligence\DTO\WebSearchOutcome;

/**
 * General web search provider — distinct from scholarly (SS/OpenAlex/Crossref/FAO) sources.
 */
interface WebSearchProviderInterface
{
    public function providerId(): string;

    public function displayName(): string;

    public function isConfigured(): bool;

    /**
     * @param  array<string, mixed>  $options
     */
    public function search(string $query, int $limit = 10, array $options = []): WebSearchOutcome;
}
