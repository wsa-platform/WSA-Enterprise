<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * Where an incremental harvest of one source stands.
 *
 * IN_PROGRESS keeps the resumption token of the next page and the window being harvested;
 * the high-water mark advances only when a whole list completes, so an interrupted harvest
 * never skips records.
 */
final readonly class HarvestCheckpoint
{
    public const IN_PROGRESS = 'in_progress';

    public const COMPLETE = 'complete';

    public function __construct(
        public string $sourceKey,
        public string $state,
        public ?string $resumptionToken,
        public ?string $windowFrom,
        public ?string $harvestStartedAt,
        public ?string $highWaterMark,
        public int $pagesCommitted,
        public string $updatedAt,
    ) {
        if (! in_array($state, [self::IN_PROGRESS, self::COMPLETE], true)) {
            throw new LocalIndexInvariantViolation("Unknown checkpoint state '{$state}'.");
        }
        if ($state === self::COMPLETE && $resumptionToken !== null) {
            throw new LocalIndexInvariantViolation('A complete harvest has no resumption token.');
        }
        if ($pagesCommitted < 0) {
            throw new LocalIndexInvariantViolation('Committed pages cannot be negative.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'state' => $this->state,
            'resumption_token_present' => $this->resumptionToken !== null,
            'window_from' => $this->windowFrom,
            'harvest_started_at' => $this->harvestStartedAt,
            'high_water_mark' => $this->highWaterMark,
            'pages_committed' => $this->pagesCommitted,
            'updated_at' => $this->updatedAt,
        ];
    }
}
