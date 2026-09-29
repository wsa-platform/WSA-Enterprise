<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use InvalidArgumentException;

/**
 * R8 / B6 — Multi-Answer producer.
 *
 * Responsibilities only:
 * 1. deterministic identity
 * 2. provenance assembly
 * 3. position (input order)
 * 4. output contract
 * 5. duplicate answer_id handling (first wins)
 *
 * Does not infer independence, rank, translate, rewrite, calculate confidence,
 * or interpret conflict. Input must already be ScientificIndependentAnswer[].
 */
final class ScientificMultiAnswerProducer
{
    public function __construct(
        private readonly MultiAnswerIdentitySerializer $identitySerializer = new MultiAnswerIdentitySerializer(),
    ) {
    }

    /**
     * @param list<ScientificIndependentAnswer> $independentAnswers
     * @return list<ScientificMultiAnswer>
     */
    public function produce(
        CanonicalScientificQuestion $question,
        array $independentAnswers,
    ): array {
        $produced = [];
        $seenAnswerIds = [];

        foreach (array_values($independentAnswers) as $independentAnswer) {
            if (! $independentAnswer instanceof ScientificIndependentAnswer) {
                throw new InvalidArgumentException(
                    'Multi-answer producer accepts only ScientificIndependentAnswer objects.'
                );
            }

            $answerId = $this->identitySerializer->answerId(
                $question,
                $independentAnswer->answer,
                $independentAnswer->evidenceIds,
            );

            if (isset($seenAnswerIds[$answerId])) {
                continue;
            }
            $seenAnswerIds[$answerId] = true;

            $produced[] = new ScientificMultiAnswer(
                answerId: $answerId,
                answer: $independentAnswer->answer,
                resultId: $independentAnswer->resultId,
                evidenceIds: $independentAnswer->evidenceIds,
                sourceIds: $independentAnswer->sourceIds,
                position: count($produced),
                provenance: [
                    'statement_ids' => $independentAnswer->statementIds,
                    'evidence_ids' => $independentAnswer->evidenceIds,
                    'source_ids' => $independentAnswer->sourceIds,
                    'result_id' => $independentAnswer->resultId,
                ],
            );
        }

        return $produced;
    }
}
