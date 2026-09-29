<?php

namespace App\Services\Agriculture\Research\Synthesis;

use InvalidArgumentException;

/**
 * R8 — explicit Answer-level scientific answer.
 *
 * This object represents answer content that has already been explicitly
 * identified upstream. It does not infer independence, rank answers, calculate
 * confidence, deduplicate, or derive content from claims/evidence.
 */
final class ScientificIndependentAnswer
{
    /**
     * @param list<string> $statementIds
     * @param list<string> $evidenceIds
     * @param list<string> $sourceIds
     */
    public function __construct(
        public readonly string $answer,
        public readonly array $statementIds,
        public readonly array $evidenceIds,
        public readonly array $sourceIds,
        public readonly string $resultId,
    ) {
        if (trim($this->answer) === '' || trim($this->resultId) === '') {
            throw new InvalidArgumentException(
                'Scientific independent answer requires answer and resultId.'
            );
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'statement_ids' => $this->statementIds,
            'evidence_ids' => $this->evidenceIds,
            'source_ids' => $this->sourceIds,
            'result_id' => $this->resultId,
        ];
    }
}
