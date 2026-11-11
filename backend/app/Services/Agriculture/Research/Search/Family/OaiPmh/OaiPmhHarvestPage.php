<?php

namespace App\Services\Agriculture\Research\Search\Family\OaiPmh;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * One ListRecords response of an OAI-PMH harvest (ADR-023 §8.39).
 *
 * Deleted and unusable records are counted, never dropped silently. A failed page keeps its
 * typed category and the repository's OAI-PMH error codes.
 */
final readonly class OaiPmhHarvestPage
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_EMPTY = 'empty';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  list<ScientificSearchResult>  $records
     * @param  list<string>  $deletedIdentifiers
     * @param  list<string>  $errorCodes  OAI-PMH error codes as the repository returned them
     */
    public function __construct(
        public string $sourceKey,
        public string $status,
        public array $records = [],
        public array $deletedIdentifiers = [],
        public int $skippedRecords = 0,
        public ?string $resumptionToken = null,
        public ?int $completeListSize = null,
        public ?string $responseDate = null,
        public ?ScientificSourceFailureCategory $failureCategory = null,
        public array $errorCodes = [],
        public ?string $error = null,
        public ?int $httpStatus = null,
    ) {}

    public static function failed(
        string $sourceKey,
        ScientificSourceFailureCategory $category,
        string $error,
        ?int $httpStatus = null,
        array $errorCodes = [],
    ): self {
        return new self(
            sourceKey: $sourceKey,
            status: self::STATUS_FAILED,
            failureCategory: $category,
            errorCodes: $errorCodes,
            error: $error,
            httpStatus: $httpStatus,
        );
    }

    /** True when the list ended without failure: there is no further page to request. */
    public function isComplete(): bool
    {
        return $this->status !== self::STATUS_FAILED && $this->resumptionToken === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->sourceKey,
            'status' => $this->status,
            'record_count' => count($this->records),
            'deleted_identifiers' => $this->deletedIdentifiers,
            'skipped_records' => $this->skippedRecords,
            'resumption_token' => $this->resumptionToken,
            'complete_list_size' => $this->completeListSize,
            'response_date' => $this->responseDate,
            'failure_category' => $this->failureCategory?->value,
            'retryable' => $this->failureCategory?->isRetryable(),
            'error_codes' => $this->errorCodes,
            'error' => $this->error,
            'http_status' => $this->httpStatus,
            'complete' => $this->isComplete(),
            'records' => array_map(static fn (ScientificSearchResult $r): array => $r->toArray(), $this->records),
        ];
    }
}
