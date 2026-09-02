<?php

namespace App\Services\Agriculture\Research\Persistence;

/**
 * Structured Stage 5 verified knowledge persistence report.
 */
final class KnowledgePersistenceExecutionReport
{
    /**
     * @param  array<string, mixed>|null  $provenance
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $performed,
        public readonly ?int $libraryItemId,
        public readonly ?string $slug,
        public readonly string $action,
        public readonly ?array $provenance,
        public readonly array $observability,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'library_persistence' => [
                'performed' => $this->performed,
                'stage' => 5,
                'action' => $this->action,
                'library_item_id' => $this->libraryItemId,
                'slug' => $this->slug,
            ],
            'provenance' => $this->provenance,
            'observability' => $this->observability,
        ];
    }
}
