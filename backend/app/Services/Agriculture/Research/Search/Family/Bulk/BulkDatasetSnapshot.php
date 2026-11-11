<?php

namespace App\Services\Agriculture\Research\Search\Family\Bulk;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Everything one read of a dataset file produced.
 *
 * Only a COMPLETE snapshot describes the whole dataset, so only a complete snapshot may
 * withdraw records that are absent from it. A truncated, malformed or unreadable file is
 * INCOMPLETE or FAILED and must leave the active generation unchanged.
 */
final readonly class BulkDatasetSnapshot
{
    public const STATUS_COMPLETE = 'complete';

    public const STATUS_INCOMPLETE = 'incomplete';

    public const STATUS_FAILED = 'failed';

    /**
     * @param  list<ScientificSearchResult>  $records  usable records, first occurrence of each identifier
     * @param  list<string>  $withdrawnIdentifiers  identifiers the dataset flags as withdrawn
     */
    public function __construct(
        public string $sourceKey,
        public string $status,
        public array $records = [],
        public array $withdrawnIdentifiers = [],
        public int $linesRead = 0,
        public int $unusableRecords = 0,
        public int $malformedLines = 0,
        public int $duplicateIdentifiers = 0,
        public ?string $failure = null,
        public ?string $contentSha256 = null,
    ) {}

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'status' => $this->status,
            'records' => count($this->records),
            'withdrawn_identifiers' => count($this->withdrawnIdentifiers),
            'lines_read' => $this->linesRead,
            'unusable_records' => $this->unusableRecords,
            'malformed_lines' => $this->malformedLines,
            'duplicate_identifiers' => $this->duplicateIdentifiers,
            'failure' => $this->failure,
            'content_sha256' => $this->contentSha256,
        ];
    }
}
