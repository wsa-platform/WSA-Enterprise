<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Optional plant / crop context supplied with a diagnosis request.
 */
final class PlantContext
{
    /**
     * @param  list<string>  $symptomsDescribed
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly ?string $plantName = null,
        public readonly ?string $cropType = null,
        public readonly ?string $growthStage = null,
        public readonly ?string $location = null,
        public readonly ?string $notes = null,
        public readonly array $symptomsDescribed = [],
        public readonly array $extra = [],
    ) {}

    public function hasUsefulContext(): bool
    {
        return $this->plantName !== null
            || $this->cropType !== null
            || $this->growthStage !== null
            || $this->location !== null
            || ($this->notes !== null && trim($this->notes) !== '')
            || $this->symptomsDescribed !== [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'plant_name' => $this->plantName,
            'crop_type' => $this->cropType,
            'growth_stage' => $this->growthStage,
            'location' => $this->location,
            'notes' => $this->notes,
            'symptoms_described' => $this->symptomsDescribed,
            'extra' => $this->extra,
            'has_useful_context' => $this->hasUsefulContext(),
        ];
    }

    /** @param  array<string, mixed>  $input */
    public static function fromInput(array $input): self
    {
        $symptoms = [];
        if (isset($input['symptoms']) && is_array($input['symptoms'])) {
            foreach ($input['symptoms'] as $symptom) {
                if (is_string($symptom) && trim($symptom) !== '') {
                    $symptoms[] = trim($symptom);
                }
            }
        }

        return new self(
            plantName: self::nullableString($input['plant_name'] ?? $input['plant'] ?? null),
            cropType: self::nullableString($input['crop_type'] ?? $input['crop'] ?? null),
            growthStage: self::nullableString($input['growth_stage'] ?? null),
            location: self::nullableString($input['location'] ?? null),
            notes: self::nullableString($input['notes'] ?? $input['observations'] ?? null),
            symptomsDescribed: $symptoms,
            extra: is_array($input['context'] ?? null) ? $input['context'] : [],
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
