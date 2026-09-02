<?php

namespace App\Services\Agriculture\Research;

/**
 * Normalized understanding of a user's agricultural question.
 */
final class AgriculturalKnowledgeQuery
{
    public const AMBIGUITY_CLEAR = 'clear';

    public const AMBIGUITY_PARTIALLY_AMBIGUOUS = 'partially_ambiguous';

    public const AMBIGUITY_NEEDS_CLARIFICATION = 'needs_clarification';

    /**
     * @param  array{type: string, value: string, label?: string}|null  $subject
     * @param  list<string>  $requestedInformation
     * @param  array<string, mixed>  $constraints
     * @param  list<string>  $clarificationRequirements
     */
    public function __construct(
        public readonly string $originalQuestion,
        public readonly string $normalizedQuestion,
        public readonly string $language,
        public readonly string $agriculturalDomain,
        public readonly ?array $subject,
        public readonly ?string $crop,
        public readonly ?string $cropId,
        public readonly ?string $scientificName,
        public readonly string $topic,
        public readonly ?string $subtopic,
        public readonly array $requestedInformation,
        public readonly array $constraints,
        public readonly ?string $location,
        public readonly bool $researchRequired,
        public readonly string $ambiguityState,
        public readonly array $clarificationRequirements,
        public readonly string $researchIntent,
    ) {}

    public function isClear(): bool
    {
        return $this->ambiguityState === self::AMBIGUITY_CLEAR;
    }

    public function needsClarification(): bool
    {
        return $this->ambiguityState === self::AMBIGUITY_NEEDS_CLARIFICATION;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'original_question' => $this->originalQuestion,
            'normalized_question' => $this->normalizedQuestion,
            'language' => $this->language,
            'agricultural_domain' => $this->agriculturalDomain,
            'subject' => $this->subject,
            'crop' => $this->crop,
            'crop_id' => $this->cropId,
            'scientific_name' => $this->scientificName,
            'topic' => $this->topic,
            'subtopic' => $this->subtopic,
            'requested_information' => $this->requestedInformation,
            'constraints' => $this->constraints,
            'location' => $this->location,
            'research_required' => $this->researchRequired,
            'ambiguity_state' => $this->ambiguityState,
            'clarification_requirements' => $this->clarificationRequirements,
            'research_intent' => $this->researchIntent,
        ];
    }
}
