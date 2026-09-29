<?php

namespace App\Services\Agriculture\Research\Synthesis;

use InvalidArgumentException;

/**
 * R8.30-A — explicit Answer-level source for independent scientific answers.
 *
 * This class is a boundary/container only. It accepts already-identified
 * ScientificIndependentAnswer objects and never derives answers from claims,
 * evidence, confidence, conflict, or source differences.
 */
final class ScientificIndependentAnswerSource
{
    /** @var list<ScientificIndependentAnswer> */
    private array $answers;

    /**
     * @param list<ScientificIndependentAnswer> $answers
     */
    public function __construct(array $answers)
    {
        foreach ($answers as $answer) {
            if (! $answer instanceof ScientificIndependentAnswer) {
                throw new InvalidArgumentException(
                    'Independent answer source accepts only ScientificIndependentAnswer objects.'
                );
            }
        }

        $this->answers = array_values($answers);
    }

    /**
     * @return list<ScientificIndependentAnswer>
     */
    public function answers(): array
    {
        return $this->answers;
    }

    /**
     * @param list<ScientificIndependentAnswer> $answers
     */
    public static function fromExplicitAnswers(array $answers): self
    {
        return new self($answers);
    }
}
