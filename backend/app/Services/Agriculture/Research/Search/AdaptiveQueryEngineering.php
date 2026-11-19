<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Adaptive query engineering helpers used by Orchestrator + Builder coordination.
 *
 * Does not replace ScientificSearchQueryBuilder or ScientificQueryCompiler.
 * Produces optional extra variants and stopping decisions bound to CSQ identity.
 */
final class AdaptiveQueryEngineering
{
    public const MAX_EXTRA_VARIANTS = 2;

    /**
     * @return list<array{variant: string, strategy: string, reason: string}>
     */
    public function expansionCandidates(KnowledgeQueryPlan $plan, array $existingVariants = []): array
    {
        $existing = array_map(
            static fn ($v): string => mb_strtolower(trim((string) $v)),
            $existingVariants,
        );
        $candidates = [];
        $entity = trim((string) ($plan->normalizedQuery->scientificName
            ?? $plan->normalizedQuery->cropId
            ?? ''));
        if ($entity === '' && is_array($plan->subjectEntity)) {
            $entity = trim((string) ($plan->subjectEntity['value'] ?? $plan->subjectEntity['label'] ?? ''));
        }
        $property = trim((string) ($plan->normalizedQuery->constraints['requested_property'] ?? ''));
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        $topic = is_array($topics) && $topics !== [] ? trim((string) $topics[0]) : '';

        if ($entity !== '') {
            $exact = $entity.($property !== '' ? ' '.$property : ($topic !== '' ? ' '.$topic : ''));
            $candidates[] = [
                'variant' => $exact,
                'strategy' => 'exact_core',
                'reason' => 'identity_derived_core_concepts',
            ];
            if ($property !== '' || $topic !== '') {
                $candidates[] = [
                    'variant' => $entity,
                    'strategy' => 'broader_recall',
                    'reason' => 'entity_only_broader_recall_when_coverage_gap',
                ];
            }
        }

        $out = [];
        foreach ($candidates as $row) {
            $norm = mb_strtolower(trim($row['variant']));
            if ($norm === '' || in_array($norm, $existing, true)) {
                continue;
            }
            $existing[] = $norm;
            $out[] = $row;
            if (count($out) >= self::MAX_EXTRA_VARIANTS) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  list<ScientificSearchResult>  $survivors
     * @return array{should_expand: bool, reason: string, stop: bool}
     */
    public function evaluateCoverage(
        KnowledgeQueryPlan $plan,
        array $survivors,
        int $rawCount,
        int $expansionRound,
    ): array {
        if ($expansionRound >= 1) {
            return ['should_expand' => false, 'reason' => 'max_expansion_rounds', 'stop' => true];
        }
        if ($survivors !== []) {
            return ['should_expand' => false, 'reason' => 'survivors_present', 'stop' => true];
        }
        if ($rawCount === 0) {
            return ['should_expand' => true, 'reason' => 'zero_raw_hits', 'stop' => false];
        }

        // Raw hits existed but relevance wiped them — try broader recall once if identity entity exists.
        $policy = new ScientificKnowledgeTypeRelevancePolicy;
        $needsSpecialized = $policy->protectedSourceKeys($plan) !== [];

        return [
            'should_expand' => true,
            'reason' => $needsSpecialized
                ? 'relevance_wipe_try_broader_or_specialized'
                : 'relevance_wipe_try_broader_recall',
            'stop' => false,
        ];
    }

    /**
     * Merge expansion variants into the primary variant list without mutating CSQ.
     *
     * @param  list<string>  $variants
     * @return list<string>
     */
    public function mergeVariants(array $variants, KnowledgeQueryPlan $plan): array
    {
        $extra = $this->expansionCandidates($plan, $variants);
        foreach ($extra as $row) {
            $variants[] = $row['variant'];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($v): string => trim((string) $v),
            $variants,
        ), static fn (string $v): bool => $v !== '')));
    }
}
