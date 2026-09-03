<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Safety / limitation disclosures for Plant AI Diagnosis outputs.
 */
final class SafetyLimitations
{
    /**
     * @param  list<string>  $statements
     * @param  list<string>  $prohibitedClaims
     */
    public function __construct(
        public readonly array $statements,
        public readonly array $prohibitedClaims = [],
        public readonly bool $isDecisionSupportOnly = true,
        public readonly bool $imageAloneNotDefinitive = true,
        public readonly bool $managementDistinctFromDiagnosis = true,
        public readonly bool $pesticideDosageForbidden = true,
    ) {}

    public static function defaults(): self
    {
        return new self(
            statements: [
                'This output is agricultural decision support only and is not a definitive laboratory diagnosis.',
                'Image analysis alone cannot provide 100% certainty.',
                'Diagnosis suggestions are separate from management recommendations.',
                'Unsupported pesticide names, dosages, or concentrations are not provided.',
                'Consult a qualified agronomist or plant pathologist for high-stakes decisions.',
            ],
            prohibitedClaims: [
                'absolute_certainty_from_image',
                'unsupported_pesticide_dosage',
                'unsupported_concentration',
                'authoritative_lab_diagnosis',
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'is_decision_support_only' => $this->isDecisionSupportOnly,
            'image_alone_not_definitive' => $this->imageAloneNotDefinitive,
            'management_distinct_from_diagnosis' => $this->managementDistinctFromDiagnosis,
            'pesticide_dosage_forbidden' => $this->pesticideDosageForbidden,
            'statements' => $this->statements,
            'prohibited_claims' => $this->prohibitedClaims,
        ];
    }
}
