<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use App\Services\Agriculture\Diagnosis\PlantContext;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Retrieval / matching query against the diagnosis knowledge base.
 */
final class DiagnosisKnowledgeQuery
{
    /**
     * @param  list<VisionObservation>  $observations
     * @param  list<string>  $symptoms
     * @param  list<string>  $aliases
     */
    public function __construct(
        public readonly ?PlantContext $context = null,
        public readonly array $observations = [],
        public readonly array $symptoms = [],
        public readonly ?string $cropKey = null,
        public readonly ?string $scientificName = null,
        public readonly ?string $commonName = null,
        public readonly ?string $plantPart = null,
        public readonly ?string $disease = null,
        public readonly ?string $pathogen = null,
        public readonly ?string $pest = null,
        public readonly ?string $nutrient = null,
        public readonly ?string $abioticStress = null,
        public readonly ?string $category = null,
        public readonly ?string $causalClass = null,
        public readonly array $aliases = [],
        public readonly bool $verifiedOnly = true,
    ) {}

    /** @param  array<string, mixed>  $input */
    public static function fromInput(array $input): self
    {
        $context = isset($input['context']) && $input['context'] instanceof PlantContext
            ? $input['context']
            : PlantContext::fromInput($input);

        $observations = [];
        if (isset($input['observations']) && is_array($input['observations'])) {
            foreach ($input['observations'] as $obs) {
                if ($obs instanceof VisionObservation) {
                    $observations[] = $obs;
                }
            }
        }

        $symptoms = [];
        if (isset($input['symptoms']) && is_array($input['symptoms'])) {
            foreach ($input['symptoms'] as $symptom) {
                if (is_string($symptom) && trim($symptom) !== '') {
                    $symptoms[] = trim($symptom);
                }
            }
        }

        $aliases = [];
        if (isset($input['aliases']) && is_array($input['aliases'])) {
            foreach ($input['aliases'] as $alias) {
                if (is_string($alias) && trim($alias) !== '') {
                    $aliases[] = trim($alias);
                }
            }
        }

        return new self(
            context: $context,
            observations: $observations,
            symptoms: $symptoms !== [] ? $symptoms : $context->symptomsDescribed,
            cropKey: self::nullableString($input['crop'] ?? $input['crop_type'] ?? $input['crop_key'] ?? $context->cropType),
            scientificName: self::nullableString($input['scientific_name'] ?? null),
            commonName: self::nullableString($input['common_name'] ?? $input['plant_name'] ?? $context->plantName),
            plantPart: self::nullableString($input['plant_part'] ?? null),
            disease: self::nullableString($input['disease'] ?? null),
            pathogen: self::nullableString($input['pathogen'] ?? null),
            pest: self::nullableString($input['pest'] ?? null),
            nutrient: self::nullableString($input['nutrient'] ?? null),
            abioticStress: self::nullableString($input['abiotic_stress'] ?? null),
            category: self::nullableString($input['category'] ?? null),
            causalClass: self::nullableString($input['causal_class'] ?? null),
            aliases: $aliases,
            verifiedOnly: filter_var($input['verified_only'] ?? true, FILTER_VALIDATE_BOOL),
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
