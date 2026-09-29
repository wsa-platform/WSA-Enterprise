<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use InvalidArgumentException;

/**
 * R8 / B6 — application-level Answer-Level provider for already-identified answers.
 *
 * This provider never inspects claims, evidence, confidence, conflict, ranking, or
 * candidates. It only re-exposes ScientificIndependentAnswer objects that were
 * supplied explicitly at construction time. An empty constructor list is valid and
 * yields an empty source (no inferred answers).
 */
final class ExplicitScientificIndependentAnswerProvider implements ScientificIndependentAnswerProvider
{
    /** @var list<ScientificIndependentAnswer> */
    private array $answers;

    /**
     * @param list<ScientificIndependentAnswer> $answers
     */
    public function __construct(array $answers = [])
    {
        foreach ($answers as $answer) {
            if (! $answer instanceof ScientificIndependentAnswer) {
                throw new InvalidArgumentException(
                    'Explicit independent answer provider accepts only ScientificIndependentAnswer objects.'
                );
            }
        }

        $this->answers = array_values($answers);
    }

    public function provide(
        CanonicalScientificQuestion $question,
    ): ScientificIndependentAnswerSource {
        // Question is accepted for the provider boundary contract only.
        // Content is never derived from the question, claims, or evidence.
        assert($question instanceof CanonicalScientificQuestion);

        return ScientificIndependentAnswerSource::fromExplicitAnswers($this->answers);
    }
}
