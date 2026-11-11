<?php

namespace App\Services\Agriculture\Research\Search\Contract;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Presents a v1 adapter through contract v2 during migration (ADR-023 §8.36).
 *
 * Declares only what is known about any v1 adapter: one request at a time, no retries at the
 * orchestrator level, and fully unresolved licensing. Everything else stays undeclared.
 */
final class ScientificSourceAdapterV1Bridge implements ScientificSourceAdapterV2Interface
{
    private function __construct(
        private readonly ScientificSourceAdapterInterface $inner,
    ) {}

    public static function wrap(ScientificSourceAdapterInterface $adapter): ScientificSourceAdapterV2Interface
    {
        return $adapter instanceof ScientificSourceAdapterV2Interface ? $adapter : new self($adapter);
    }

    public function inner(): ScientificSourceAdapterInterface
    {
        return $this->inner;
    }

    public function sourceKey(): string
    {
        return $this->inner->sourceKey();
    }

    public function displayName(): string
    {
        return $this->inner->displayName();
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        return $this->inner->search($query, $limit, $options);
    }

    public function searchRequest(ScientificSourceSearchRequest $request): ScientificSourceSearchOutcome
    {
        return $this->inner->search($request->queryText, $request->limit, $request->options);
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->inner->sourceKey(),
            licensing: ScientificSourceLicensingDeclaration::unresolved(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }
}
