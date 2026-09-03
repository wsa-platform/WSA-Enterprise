<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Final Stage 6 Plant AI Diagnosis result.
 */
final class PlantDiagnosisResult
{
    /**
     * @param  list<VisionObservation>  $observations
     * @param  list<CandidateDiagnosis>  $candidates
     * @param  list<AdditionalInfoRequest>  $additionalInfoRequests
     * @param  list<string>  $managementNotes
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly array $observations = [],
        public readonly array $candidates = [],
        public readonly ?UncertaintyAssessment $uncertainty = null,
        public readonly array $additionalInfoRequests = [],
        public readonly SafetyLimitations $safety = new SafetyLimitations(statements: []),
        public readonly array $managementNotes = [],
        public readonly array $observability = [],
        public readonly int $stage = 6,
        public readonly ?PlantImageMetadata $imageMetadata = null,
        public readonly ?PlantContext $plantContext = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'stage' => $this->stage,
            'message' => $this->message,
            'image' => $this->imageMetadata?->toArray(),
            'plant_context' => $this->plantContext?->toArray(),
            'observations' => array_map(
                static fn (VisionObservation $item): array => $item->toArray(),
                $this->observations,
            ),
            'candidates' => array_map(
                static fn (CandidateDiagnosis $item): array => $item->toArray(),
                $this->candidates,
            ),
            'uncertainty' => $this->uncertainty?->toArray(),
            'additional_info_requests' => array_map(
                static fn (AdditionalInfoRequest $item): array => $item->toArray(),
                $this->additionalInfoRequests,
            ),
            'management_notes' => $this->managementNotes,
            'safety' => $this->safety->toArray(),
            'observability' => $this->observability,
            'engine' => 'plant_ai_diagnosis',
            'independent_of_research_agent' => true,
        ];
    }
}
