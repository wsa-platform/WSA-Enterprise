<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;

/**
 * Detects conflicting evidence across validated items.
 *
 * Conservative: requires meaningful positive-vs-negative contradiction with
 * sufficient polarity strength. Does not wipe an entire topic group on weak
 * polarity noise, and preserves DIRECT evidence when only secondary items conflict.
 */
class EvidenceConflictDetector
{
    private const MIN_STRENGTH = 2;

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

            foreach ($this->conflictingPairs($indices, $polarities, $items) as $index) {
                $conflictIndices[$index] = true;
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

    /**
     * @param  list<int>  $indices
     * @param  array<int, array{label: string, strength: int, pos: int, neg: int}>  $polarities
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<int>
     */
    private function conflictingPairs(array $indices, array $polarities, array $items): array
    {
        $marked = [];

        for ($i = 0; $i < count($indices); $i++) {
            for ($j = $i + 1; $j < count($indices); $j++) {
                $a = $indices[$i];
                $b = $indices[$j];
                if (! $this->isMeaningfulContradiction($polarities[$a], $polarities[$b])) {
                    continue;
                }

                $aDirect = $this->isDirect($items[$a]);
                $bDirect = $this->isDirect($items[$b]);
                $aStrong = $polarities[$a]['strength'] >= self::MIN_STRENGTH;
                $bStrong = $polarities[$b]['strength'] >= self::MIN_STRENGTH;

                // Both DIRECT with strong opposing polarity → genuine conflict pair.
                if ($aDirect && $bDirect && $aStrong && $bStrong) {
                    $marked[$a] = true;
                    $marked[$b] = true;

                    continue;
                }

                // Preserve DIRECT when only a secondary/weaker claim contradicts it.
                if ($aDirect && ! $bDirect) {
                    $marked[$b] = true;

                    continue;
                }
                if ($bDirect && ! $aDirect) {
                    $marked[$a] = true;

                    continue;
                }

                // Non-direct pair: mark only items with strong polarity on both sides.
                if ($aStrong && $bStrong) {
                    $marked[$a] = true;
                    $marked[$b] = true;
                }
            }
        }

        return array_map('intval', array_keys($marked));
    }

    /**
     * @param  array{label: string, strength: int, pos: int, neg: int}  $a
     * @param  array{label: string, strength: int, pos: int, neg: int}  $b
     */
    private function isMeaningfulContradiction(array $a, array $b): bool
    {
        // Neutral never contradicts; weak single-keyword polarity is not enough.
        if ($a['label'] === 'neutral' || $b['label'] === 'neutral') {
            return false;
        }

        if ($a['label'] === $b['label']) {
            return false;
        }

        // Require opposing positive vs negative with meaningful strength on both sides.
        $opposing = ($a['label'] === 'positive' && $b['label'] === 'negative')
            || ($a['label'] === 'negative' && $b['label'] === 'positive');

        if (! $opposing) {
            return false;
        }

        return $a['strength'] >= self::MIN_STRENGTH && $b['strength'] >= self::MIN_STRENGTH;
    }

    private function isDirect(ScientificEvidenceItem $item): bool
    {
        $directness = $item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? null;

        return $directness === ScientificEvidenceDirectnessAssessor::DIRECT;
    }

    private function topicKey(ScientificEvidenceItem $item): string
    {
        $base = mb_strtolower(trim(($item->claimTopic ?? '').' '.($item->agriculturalDomain ?? '')));

        return md5($base);
    }

    /**
     * @return array{label: string, strength: int, pos: int, neg: int}
     */
    private function polarity(string $text): array
    {
        $lower = mb_strtolower($text);
        $negative = ['not ', 'no ', 'without ', 'reduce', 'decrease', 'avoid', 'limit', 'harm', 'risk', 'inhibit', 'suppress', 'adverse'];
        $positive = ['improve', 'increase', 'enhance', 'benefit', 'effective', 'support', 'recommend', 'optimal', 'promote', 'favor'];

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

        // Ambiguous mixed cues without clear dominance → neutral (not a conflict trigger).
        if ($posScore > 0 && $negScore > 0 && abs($posScore - $negScore) <= 1) {
            return [
                'label' => 'neutral',
                'strength' => max($posScore, $negScore),
                'pos' => $posScore,
                'neg' => $negScore,
            ];
        }

        if ($negScore > $posScore) {
            return [
                'label' => 'negative',
                'strength' => $negScore,
                'pos' => $posScore,
                'neg' => $negScore,
            ];
        }
        if ($posScore > $negScore) {
            return [
                'label' => 'positive',
                'strength' => $posScore,
                'pos' => $posScore,
                'neg' => $negScore,
            ];
        }

        return [
            'label' => 'neutral',
            'strength' => 0,
            'pos' => $posScore,
            'neg' => $negScore,
        ];
    }
}
