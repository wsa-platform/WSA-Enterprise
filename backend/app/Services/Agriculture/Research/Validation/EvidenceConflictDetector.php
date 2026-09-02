<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Detects conflicting evidence across validated items.
 */
class EvidenceConflictDetector
{
    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ScientificEvidenceItem>
     */
    public function detect(array $items): array
    {
        if (count($items) < 2) {
            return $items;
        }

        $topicGroups = [];
        foreach ($items as $index => $item) {
            if ($item->isRejected() || $item->evidenceText === null) {
                continue;
            }
            $topicKey = $this->topicKey($item);
            $topicGroups[$topicKey][] = $index;
        }

        $conflictIndices = [];
        foreach ($topicGroups as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            $polarities = [];
            foreach ($indices as $index) {
                $polarities[$index] = $this->polarity($items[$index]->evidenceText ?? '');
            }

            $uniquePolarities = array_unique(array_values($polarities));
            if (count($uniquePolarities) > 1) {
                foreach ($indices as $index) {
                    $conflictIndices[$index] = true;
                }
            }
        }

        if ($conflictIndices === []) {
            return $items;
        }

        $updated = [];
        foreach ($items as $index => $item) {
            if (! isset($conflictIndices[$index])) {
                $updated[] = $item;

                continue;
            }

            $updated[] = new ScientificEvidenceItem(
                evidenceId: $item->evidenceId,
                sourceId: $item->sourceId,
                sourceKey: $item->sourceKey,
                sourceType: $item->sourceType,
                publicationTitle: $item->publicationTitle,
                authors: $item->authors,
                institution: $item->institution,
                journal: $item->journal,
                doi: $item->doi,
                url: $item->url,
                publicationYear: $item->publicationYear,
                retrievedAt: $item->retrievedAt,
                agriculturalDomain: $item->agriculturalDomain,
                claimTopic: $item->claimTopic,
                evidenceText: $item->evidenceText,
                validationStatus: $item->validationStatus,
                validationFailures: array_values(array_unique(array_merge(
                    $item->validationFailures,
                    ['conflicting_scientific_evidence'],
                ))),
                claimRelationship: ClaimEvidenceRelationship::CONFLICTING,
                confidence: max(0.1, $item->confidence * 0.5),
                qualityScore: $item->qualityScore,
                qualityFactors: array_merge($item->qualityFactors, ['conflict_detected' => true]),
                sourceAttribution: $item->sourceAttribution,
                hasConflict: true,
                conditions: $item->conditions,
                cropOrEntity: $item->cropOrEntity,
            );
        }

        return $updated;
    }

    private function topicKey(ScientificEvidenceItem $item): string
    {
        $base = mb_strtolower(trim(($item->claimTopic ?? '').' '.($item->agriculturalDomain ?? '')));

        return md5($base);
    }

    private function polarity(string $text): string
    {
        $lower = mb_strtolower($text);
        $negative = ['not ', 'no ', 'without ', 'reduce', 'decrease', 'avoid', 'limit', 'harm', 'risk'];
        $positive = ['improve', 'increase', 'enhance', 'benefit', 'effective', 'support', 'recommend'];

        $negScore = 0;
        $posScore = 0;
        foreach ($negative as $needle) {
            if (str_contains($lower, $needle)) {
                $negScore++;
            }
        }
        foreach ($positive as $needle) {
            if (str_contains($lower, $needle)) {
                $posScore++;
            }
        }

        if ($negScore > $posScore) {
            return 'negative';
        }
        if ($posScore > $negScore) {
            return 'positive';
        }

        return 'neutral';
    }
}
