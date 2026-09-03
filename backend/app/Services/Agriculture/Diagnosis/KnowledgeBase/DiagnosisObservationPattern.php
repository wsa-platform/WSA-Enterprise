<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Observation pattern that can match Stage 6 VisionObservation cues.
 */
final class DiagnosisObservationPattern
{
    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $plantParts
     * @param  list<string>  $negativeKeywords
     * @param  list<string>  $observationTypes
     */
    public function __construct(
        public readonly string $id,
        public readonly array $keywords = [],
        public readonly array $plantParts = [],
        public readonly array $negativeKeywords = [],
        public readonly array $observationTypes = [],
        public readonly ?string $severityHint = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'keywords' => $this->keywords,
            'plant_parts' => $this->plantParts,
            'negative_keywords' => $this->negativeKeywords,
            'observation_types' => $this->observationTypes,
            'severity_hint' => $this->severityHint,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? 'pattern'),
            keywords: self::stringList($data['keywords'] ?? []),
            plantParts: self::stringList($data['plant_parts'] ?? []),
            negativeKeywords: self::stringList($data['negative_keywords'] ?? []),
            observationTypes: self::stringList($data['observation_types'] ?? []),
            severityHint: isset($data['severity_hint']) && is_string($data['severity_hint'])
                ? $data['severity_hint']
                : null,
        );
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values($out);
    }
}
