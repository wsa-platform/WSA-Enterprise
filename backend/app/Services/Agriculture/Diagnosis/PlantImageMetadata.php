<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Validated image metadata. Never exposes absolute filesystem paths.
 */
final class PlantImageMetadata
{
    /**
     * @param  string  $contentHash  SHA-256 of raw bytes
     * @param  string  $detectedMime  Server-detected MIME (not client-claimed)
     */
    public function __construct(
        public readonly string $contentHash,
        public readonly string $detectedMime,
        public readonly int $sizeBytes,
        public readonly int $width,
        public readonly int $height,
        public readonly string $originalClientName = '',
        public readonly string $clientClaimedMime = '',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'content_hash' => $this->contentHash,
            'detected_mime' => $this->detectedMime,
            'size_bytes' => $this->sizeBytes,
            'width' => $this->width,
            'height' => $this->height,
            'original_client_name' => $this->originalClientName !== '' ? basename($this->originalClientName) : null,
            'client_claimed_mime' => $this->clientClaimedMime !== '' ? $this->clientClaimedMime : null,
        ];
    }
}
