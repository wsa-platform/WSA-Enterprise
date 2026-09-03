<?php

namespace App\Services\Agriculture\Diagnosis;

/**
 * Selective follow-up question to reduce uncertainty.
 */
final class AdditionalInfoRequest
{
    /**
     * @param  list<string>  $suggestedAnswers
     */
    public function __construct(
        public readonly string $id,
        public readonly string $question,
        public readonly string $reason,
        public readonly string $priority,
        public readonly array $suggestedAnswers = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'reason' => $this->reason,
            'priority' => $this->priority,
            'suggested_answers' => $this->suggestedAnswers,
        ];
    }
}
