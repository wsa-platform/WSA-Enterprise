<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Phase-5 Unit A — question-derived claim (independent of retrieved evidence).
 *
 * Evidence must not define the question. This object is extracted from the
 * KnowledgeQueryPlan / AgriculturalKnowledgeQuery surface only.
 */
final class QuestionClaim
{
    /**
     * @param  list<string>  $limitations
     * @param  array<string, mixed>  $scope
     */
    public function __construct(
        public readonly string $claimId,
        public readonly string $questionText,
        public readonly string $claimText,
        public readonly string $claimIntent,
        public readonly ?string $entity,
        public readonly ?string $property,
        public readonly ?string $location,
        public readonly ?string $time,
        public readonly string $answerLanguage,
        public readonly string $questionLanguage,
        public readonly array $limitations = [],
        public readonly array $scope = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'claim_id' => $this->claimId,
            'question_text' => $this->questionText,
            'claim_text' => $this->claimText,
            'claim_intent' => $this->claimIntent,
            'entity' => $this->entity,
            'property' => $this->property,
            'location' => $this->location,
            'time' => $this->time,
            'answer_language' => $this->answerLanguage,
            'question_language' => $this->questionLanguage,
            'limitations' => $this->limitations,
            'scope' => $this->scope,
        ];
    }
}
