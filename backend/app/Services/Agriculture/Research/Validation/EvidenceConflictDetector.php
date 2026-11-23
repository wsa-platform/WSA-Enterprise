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

    public function __construct(
        private EvidenceConflictContextAssessor $contextAssessor = new EvidenceConflictContextAssessor,
    ) {}

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

        /** @var array<int, array{outcome: string, differing_keys: list<string>, reason: string}> $pairAssessments */
        $pairAssessments = [];
        $genuineConflictIndices = [];
        $contextResolvedIndices = [];

        foreach ($topicGroups as $indices) {
            if (count($indices) < 2) {
                continue;
            }

            $polarities = [];
            foreach ($indices as $index) {
                $polarities[$index] = $this->polarity($items[$index]->evidenceText ?? '');
            }

            foreach ($this->opposingPairs($indices, $polarities, $items) as $pair) {
                $a = $pair['a'];
                $b = $pair['b'];
                $assessment = $this->contextAssessor->assessPair($items[$a], $items[$b]);
                $pairAssessments[$a] = $assessment;
                $pairAssessments[$b] = $assessment;

                if ($assessment['outcome'] === EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT
                    || $assessment['outcome'] === EvidenceConflictAssessmentOutcome::NOT_COMPARABLE) {
                    // Contextual / non-comparable differences are not genuine contradictions.
                    $contextResolvedIndices[$a] = true;
                    $contextResolvedIndices[$b] = true;

                    continue;
                }

                // GENUINE_CONFLICT or INSUFFICIENT_INFORMATION (conservative: do not merge).
                foreach ($pair['mark'] as $index) {
                    $genuineConflictIndices[$index] = true;
                }
            }
        }

        if ($genuineConflictIndices === [] && $contextResolvedIndices === []) {
            return $items;
        }

        $updated = [];
        foreach ($items as $index => $item) {
            $assessment = $pairAssessments[$index] ?? null;

            if (isset($genuineConflictIndices[$index])) {
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
                    qualityFactors: array_merge($item->qualityFactors, [
                        'conflict_detected' => true,
                        'conflict_assessment' => $assessment['outcome']
                            ?? EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT,
                        'conflict_assessment_reason' => $assessment['reason'] ?? 'opposing_polarity',
                        'conflict_differing_keys' => $assessment['differing_keys'] ?? [],
                    ]),
                    sourceAttribution: $item->sourceAttribution,
                    hasConflict: true,
                    conditions: $item->conditions,
                    cropOrEntity: $item->cropOrEntity,
                );

                continue;
            }

            if (isset($contextResolvedIndices[$index]) && $assessment !== null) {
                // Preserve original support relationship; annotate contextual difference.
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
                    validationFailures: $item->validationFailures,
                    claimRelationship: $item->claimRelationship,
                    confidence: $item->confidence,
                    qualityScore: $item->qualityScore,
                    qualityFactors: array_merge($item->qualityFactors, [
                        'conflict_detected' => false,
                        'conflict_assessment' => $assessment['outcome'],
                        'conflict_assessment_reason' => $assessment['reason'],
                        'conflict_differing_keys' => $assessment['differing_keys'],
                        'contextual_difference' => true,
                    ]),
                    sourceAttribution: $item->sourceAttribution,
                    hasConflict: false,
                    conditions: $item->conditions,
                    cropOrEntity: $item->cropOrEntity,
                );

                continue;
            }

            $updated[] = $item;
        }

        return $updated;
    }

    /**
     * @param  list<int>  $indices
     * @param  array<int, array{label: string, strength: int, pos: int, neg: int}>  $polarities
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<array{a: int, b: int, mark: list<int>}>
     */
    private function opposingPairs(array $indices, array $polarities, array $items): array
    {
        $pairs = [];

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

                $mark = [];
                // Both DIRECT with strong opposing polarity → candidate conflict pair.
                if ($aDirect && $bDirect && $aStrong && $bStrong) {
                    $mark = [$a, $b];
                } elseif ($aDirect && ! $bDirect) {
                    // Preserve DIRECT when only a secondary/weaker claim contradicts it.
                    $mark = [$b];
                } elseif ($bDirect && ! $aDirect) {
                    $mark = [$a];
                } elseif ($aStrong && $bStrong) {
                    $mark = [$a, $b];
                }

                if ($mark === []) {
                    continue;
                }

                $pairs[] = ['a' => $a, 'b' => $b, 'mark' => $mark];
            }
        }

        return $pairs;
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
