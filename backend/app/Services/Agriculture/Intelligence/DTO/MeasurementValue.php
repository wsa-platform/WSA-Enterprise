<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Measurement with original + optional safely-normalized unit pair.
 */
final class MeasurementValue
{
    public function __construct(
        public readonly float|int|string|null $value,
        public readonly ?string $unit = null,
        public readonly float|int|string|null $normalizedValue = null,
        public readonly ?string $normalizedUnit = null,
        public readonly ?float $rangeMin = null,
        public readonly ?float $rangeMax = null,
        public readonly bool $conversionSafe = true,
        public readonly ?string $conversionNote = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
            'normalized_value' => $this->normalizedValue,
            'normalized_unit' => $this->normalizedUnit,
            'range_min' => $this->rangeMin,
            'range_max' => $this->rangeMax,
            'conversion_safe' => $this->conversionSafe,
            'conversion_note' => $this->conversionNote,
        ];
    }
}
