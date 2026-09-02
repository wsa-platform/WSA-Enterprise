<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Per-source Stage 3 search outcome — never silently hides failures.
 */
final class ScientificSourceSearchOutcome
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_EMPTY = 'empty';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @param  list<ScientificSearchResult>  $results
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $status,
        public readonly array $results = [],
        public readonly ?string $error = null,
        public readonly ?int $httpStatus = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->sourceKey,
            'status' => $this->status,
            'result_count' => count($this->results),
            'error' => $this->error,
            'http_status' => $this->httpStatus,
            'results' => array_map(static fn (ScientificSearchResult $r): array => $r->toArray(), $this->results),
        ];
    }
}
