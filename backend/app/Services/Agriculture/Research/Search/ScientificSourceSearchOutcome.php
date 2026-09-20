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
     * @param  array<string, mixed>|null  $observability  Safe provider metrics only (never secrets).
     */
    public function __construct(
        public readonly string $sourceKey,
        public readonly string $status,
        public readonly array $results = [],
        public readonly ?string $error = null,
        public readonly ?int $httpStatus = null,
        public readonly ?array $observability = null,
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
            // Top-level duration_ms for Stage-3 latency forensics (RC-L Phase-1).
            // Adapters already record latency_ms inside observability; probes historically
            // read duration_ms at the outcome root and saw null.
            'duration_ms' => $this->resolveDurationMs(),
            'observability' => $this->observability,
            'results' => array_map(static fn (ScientificSearchResult $r): array => $r->toArray(), $this->results),
        ];
    }

    /**
     * Prefer explicit observability.latency_ms; never invent a zero when absent.
     */
    private function resolveDurationMs(): ?int
    {
        if (! is_array($this->observability)) {
            return null;
        }
        if (! array_key_exists('latency_ms', $this->observability)) {
            return null;
        }
        $raw = $this->observability['latency_ms'];
        if (is_int($raw)) {
            return max(0, $raw);
        }
        if (is_float($raw) || (is_string($raw) && is_numeric($raw))) {
            return max(0, (int) round((float) $raw));
        }

        return null;
    }
}
