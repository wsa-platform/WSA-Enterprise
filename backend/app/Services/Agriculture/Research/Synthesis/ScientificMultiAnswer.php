<?php

namespace App\Services\Agriculture\Research\Synthesis;

use InvalidArgumentException;

/**
 * R8 — producer output for one independent Multi-Answer.
 *
 * Carries deterministic identity, producer-owned position, and Answer → Evidence →
 * Sources → Result provenance assembled from an explicit ScientificIndependentAnswer.
 */
final class ScientificMultiAnswer
{
    /**
     * @param list<string> $evidenceIds
     * @param list<string> $sourceIds
     * @param array{
     *   statement_ids: list<string>,
     *   evidence_ids: list<string>,
     *   source_ids: list<string>,
     *   result_id: string
     * } $provenance
     */
    public function __construct(
        public readonly string $answerId,
        public readonly string $answer,
        public readonly string $resultId,
        public readonly array $evidenceIds,
        public readonly array $sourceIds,
        public readonly int $position,
        public readonly array $provenance,
    ) {
        if (! preg_match('/^sha256:[0-9a-f]{64}$/', $this->answerId)) {
            throw new InvalidArgumentException('Multi-answer answer_id must match sha256:<64 lowercase hex>.');
        }
        if (trim($this->answer) === '') {
            throw new InvalidArgumentException('Multi-answer answer must not be empty.');
        }
        if (trim($this->resultId) === '') {
            throw new InvalidArgumentException('Multi-answer result_id must not be empty.');
        }
        if ($this->position < 0) {
            throw new InvalidArgumentException('Multi-answer position must be a nonnegative integer.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'answer_id' => $this->answerId,
            'answer' => $this->answer,
            'result_id' => $this->resultId,
            'evidence_ids' => $this->evidenceIds,
            'source_ids' => $this->sourceIds,
            'position' => $this->position,
            'provenance' => $this->provenance,
        ];
    }
}
