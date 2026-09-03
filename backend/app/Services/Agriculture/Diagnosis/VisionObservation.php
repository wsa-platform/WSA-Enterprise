<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Visual observation — not a disease assertion.
 */
final class VisionObservation
{
    /**
     * @param  list<string>  $supportingCues
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $description,
        public readonly ?string $locationOnPlant = null,
        public readonly ?string $severityHint = null,
        public readonly float $observationConfidence = 0.5,
        public readonly array $supportingCues = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'location_on_plant' => $this->locationOnPlant,
            'severity_hint' => $this->severityHint,
            'observation_confidence' => round($this->observationConfidence, 4),
            'supporting_cues' => $this->supportingCues,
        ];
    }
}
