<?php

namespace App\Services\Agriculture\Intelligence\Normalization;

use App\Services\Agriculture\Intelligence\DTO\MeasurementValue;

/**
 * Safe unit normalization — keeps original + normalized, or refuses unsafe conversion.
 */
final class UnitNormalizationService
{
    /** @var array<string, array{to: string, factor: float}> */
    private const SAFE_FACTORS = [
        'mm' => ['to' => 'm', 'factor' => 0.001],
        'cm' => ['to' => 'm', 'factor' => 0.01],
        'm' => ['to' => 'm', 'factor' => 1.0],
        'km' => ['to' => 'm', 'factor' => 1000.0],
        'g' => ['to' => 'kg', 'factor' => 0.001],
        'kg' => ['to' => 'kg', 'factor' => 1.0],
        't' => ['to' => 'kg', 'factor' => 1000.0],
        'ton' => ['to' => 'kg', 'factor' => 1000.0],
        'ha' => ['to' => 'ha', 'factor' => 1.0],
        'c' => ['to' => 'c', 'factor' => 1.0],
        'celsius' => ['to' => 'c', 'factor' => 1.0],
    ];

    public function normalize(float|int|string|null $value, ?string $unit): MeasurementValue
    {
        $unitKey = strtolower(trim((string) $unit));
        if ($value === null || $unitKey === '' || ! is_numeric($value)) {
            return new MeasurementValue(
                value: $value,
                unit: $unit,
                conversionSafe: $unitKey === '' || ! is_numeric($value),
                conversionNote: is_numeric($value) ? null : 'non_numeric_value',
            );
        }

        if (! isset(self::SAFE_FACTORS[$unitKey])) {
            return new MeasurementValue(
                value: $value,
                unit: $unit,
                conversionSafe: false,
                conversionNote: 'unsafe_or_unknown_unit_conversion_refused',
            );
        }

        $map = self::SAFE_FACTORS[$unitKey];
        $normalized = ((float) $value) * $map['factor'];

        return new MeasurementValue(
            value: $value,
            unit: $unit,
            normalizedValue: $normalized,
            normalizedUnit: $map['to'],
            conversionSafe: true,
        );
    }
}
