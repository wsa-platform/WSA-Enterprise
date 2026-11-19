<?php

namespace App\Services\Agriculture\Research\Synthesis;

/**
 * Derives ScientificIndependentAnswer[] from Stage-5 synthesis claims.
 *
 * Independence is claim-bound (distinct claim identity + evidence), not
 * paragraph splitting. Does not invent answers when evidence is insufficient.
 */
final class ClaimBoundIndependentAnswerDeriver
{
    /**
     * @return list<ScientificIndependentAnswer>
     */
    public function derive(AnswerSynthesisExecutionReport $synthesis): array
    {
        if (! $synthesis->performed || $synthesis->claims === []) {
            return $this->fallbackFromSynthesisAnswer($synthesis);
        }

        $groups = [];
        foreach ($synthesis->claims as $claim) {
            if (! $claim instanceof ResearchAnswerClaim) {
                continue;
            }
            $text = trim($claim->claimText);
            if ($text === '') {
                continue;
            }
            // Only claims that carry evidence can become independent answers.
            if ($claim->evidenceIds === []) {
                continue;
            }
            $groupKey = $claim->questionClaimId !== null && trim($claim->questionClaimId) !== ''
                ? 'qc:'.trim($claim->questionClaimId)
                : 'c:'.$claim->claimId;
            $groups[$groupKey][] = $claim;
        }

        if ($groups === []) {
            return $this->fallbackFromSynthesisAnswer($synthesis);
        }

        $answers = [];
        $seenNormalized = [];
        foreach ($groups as $groupClaims) {
            /** @var list<ResearchAnswerClaim> $groupClaims */
            $primary = $groupClaims[0];
            $evidenceIds = [];
            $sourceIds = [];
            $statementIds = [];
            foreach ($groupClaims as $claim) {
                foreach ($claim->evidenceIds as $eid) {
                    $evidenceIds[$eid] = true;
                }
                foreach ($claim->sourceIds as $sid) {
                    $sourceIds[$sid] = true;
                }
                $statementIds[$claim->claimId] = true;
            }
            $answerText = trim($primary->claimText);
            $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $answerText) ?? $answerText);
            if (isset($seenNormalized[$normalized])) {
                continue;
            }
            $seenNormalized[$normalized] = true;

            $evidenceList = array_keys($evidenceIds);
            $resultId = $evidenceList[0] ?? ('claim-'.$primary->claimId);

            $answers[] = new ScientificIndependentAnswer(
                answer: $answerText,
                statementIds: array_keys($statementIds),
                evidenceIds: $evidenceList,
                sourceIds: array_keys($sourceIds),
                resultId: $resultId,
            );
        }

        return $answers !== [] ? $answers : $this->fallbackFromSynthesisAnswer($synthesis);
    }

    /**
     * @return list<ScientificIndependentAnswer>
     */
    private function fallbackFromSynthesisAnswer(AnswerSynthesisExecutionReport $synthesis): array
    {
        $answer = trim((string) ($synthesis->answer ?? ''));
        if ($answer === '' || ! $synthesis->performed) {
            return [];
        }

        $evidenceIds = [];
        $sourceIds = [];
        foreach ($synthesis->claims as $claim) {
            if (! $claim instanceof ResearchAnswerClaim) {
                continue;
            }
            foreach ($claim->evidenceIds as $eid) {
                $evidenceIds[$eid] = true;
            }
            foreach ($claim->sourceIds as $sid) {
                $sourceIds[$sid] = true;
            }
        }
        foreach ($synthesis->citations as $citation) {
            if (! $citation instanceof ResearchAnswerCitation) {
                continue;
            }
            $cid = trim($citation->evidenceId);
            if ($cid !== '') {
                $evidenceIds[$cid] = true;
            }
            $src = trim((string) ($citation->sourceKey ?? $citation->sourceId));
            if ($src !== '') {
                $sourceIds[$src] = true;
            }
        }

        if ($evidenceIds === []) {
            // Integrity: no evidence-bound independent answer without evidence ids.
            return [];
        }

        return [
            new ScientificIndependentAnswer(
                answer: $answer,
                statementIds: ['synthesis-primary'],
                evidenceIds: array_keys($evidenceIds),
                sourceIds: array_keys($sourceIds),
                resultId: array_key_first($evidenceIds) ?: 'synthesis-primary',
            ),
        ];
    }
}
