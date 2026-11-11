<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * Outcome of one ingestion run for one source.
 */
final readonly class LocalIngestionReport
{
    public const COMPLETED = 'completed';

    /** Page budget spent; the checkpoint resumes the harvest. */
    public const PAUSED = 'paused';

    /** A page failed; committed pages stay and the checkpoint resumes the harvest. */
    public const FAILED = 'failed';

    /** The licence does not permit retrieval or storage; nothing was requested or read. */
    public const LICENCE_REFUSED = 'licence_refused';

    /** The dataset read was incomplete; the active generation is unchanged. */
    public const SNAPSHOT_REJECTED = 'snapshot_rejected';

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, int>  $refusals  retention refusal reason => records
     */
    public function __construct(
        public string $sourceKey,
        public string $status,
        public int $pages = 0,
        public int $generation = 0,
        public array $counts = [],
        public array $refusals = [],
        public ?string $failure = null,
        public bool $restartedWindow = false,
        public ?HarvestCheckpoint $checkpoint = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'status' => $this->status,
            'pages' => $this->pages,
            'generation' => $this->generation,
            'counts' => $this->counts,
            'refusals' => $this->refusals,
            'failure' => $this->failure,
            'restarted_window' => $this->restartedWindow,
            'checkpoint' => $this->checkpoint?->toArray(),
        ];
    }
}
