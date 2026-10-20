<?php

namespace App\Services\Architecture\MissionArtifactArchive;

/**
 * Metadata record for one archived mission/engineering artifact (not a scientific source record).
 */
final readonly class MissionArtifactRecord
{
    /**
     * @param  array<string, mixed>  $extractedMetadata
     * @param  list<array{type: string, target_archive_id: string}>  $relationships
     */
    public function __construct(
        public string $archiveId,
        public string $ingestionIdentity,
        public string $artifactType,
        public string $format,
        public string $title,
        public ?string $originalPath,
        public ?string $missionId,
        public ?string $relatedSeat,
        public ?string $relatedSourceKey,
        public string $contentSha256,
        public int $byteLength,
        public string $captureMethod,
        public string $processingStatus,
        public ?string $supersedesArchiveId,
        public bool $isSuperseded,
        public array $extractedMetadata,
        public array $relationships,
        public string $createdAt,
        public string $blobRelativePath,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'archive_id' => $this->archiveId,
            'ingestion_identity' => $this->ingestionIdentity,
            'artifact_type' => $this->artifactType,
            'format' => $this->format,
            'title' => $this->title,
            'original_path' => $this->originalPath,
            'mission_id' => $this->missionId,
            'related_seat' => $this->relatedSeat,
            'related_source_key' => $this->relatedSourceKey,
            'content_sha256' => $this->contentSha256,
            'byte_length' => $this->byteLength,
            'capture_method' => $this->captureMethod,
            'processing_status' => $this->processingStatus,
            'supersedes_archive_id' => $this->supersedesArchiveId,
            'is_superseded' => $this->isSuperseded,
            'extracted_metadata' => $this->extractedMetadata,
            'relationships' => $this->relationships,
            'created_at' => $this->createdAt,
            'blob_relative_path' => $this->blobRelativePath,
        ];
    }
}
