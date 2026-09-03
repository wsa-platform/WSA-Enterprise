<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Normalized Stage 6 diagnosis request (runtime domain model).
 */
final class PlantDiagnosisRequest
{
    /**
     * @param  string  $imageBinary  Validated image bytes (kept in-memory; never logged)
     */
    public function __construct(
        public readonly string $imageBinary,
        public readonly PlantImageMetadata $imageMetadata,
        public readonly PlantContext $plantContext,
        public readonly ?int $organizationId = null,
        public readonly string $locale = 'en',
        public readonly bool $allowManagementGuidance = true,
    ) {}

    /** @return array<string, mixed> */
    public function toPublicArray(): array
    {
        return [
            'organization_id' => $this->organizationId,
            'locale' => $this->locale,
            'allow_management_guidance' => $this->allowManagementGuidance,
            'image' => $this->imageMetadata->toArray(),
            'plant_context' => $this->plantContext->toArray(),
        ];
    }
}
