<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Web search outcome — never invents results.
 */
final class WebSearchOutcome
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_EMPTY = 'empty';

    public const STATUS_FAILED = 'failed';

    public const STATUS_NOT_CONFIGURED = 'not_configured';

    /**
     * @param  list<array<string, mixed>>  $results
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $providerId,
        public readonly string $status,
        public readonly array $results = [],
        public readonly ?string $error = null,
        public readonly ?int $httpStatus = null,
        public readonly array $observability = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider_id' => $this->providerId,
            'status' => $this->status,
            'result_count' => count($this->results),
            'results' => $this->results,
            'error' => $this->error,
            'http_status' => $this->httpStatus,
            'observability' => $this->observability,
        ];
    }
}
