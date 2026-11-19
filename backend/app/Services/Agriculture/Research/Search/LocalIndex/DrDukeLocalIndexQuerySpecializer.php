<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Dr. Duke (G1-17) LocalIndex query specializer for limited phytochem/ethnobotany projection.
 * Does not mutate CSQ. Does not invent clinical efficacy terms.
 */
final class DrDukeLocalIndexQuerySpecializer
{
    public const SOURCE_KEY = 'dr_duke';

    public const ADR_ID = 'G1-17';

    /**
     * @return array{
     *   source_key: string,
     *   adr_id: string,
     *   original_question: string,
     *   versions: list<array<string, mixed>>,
     *   primary_query: string
     * }
     */
    public function specialize(KnowledgeQueryPlan $plan): array
    {
        $q = $plan->normalizedQuery;
        $original = (string) $q->originalQuestion;
        $entity = trim((string) ($q->scientificName ?? $q->crop ?? ''));
        if ($entity === '' && is_array($q->subject)) {
            $entity = trim((string) ($q->subject['label'] ?? $q->subject['value'] ?? ''));
        }

        $sense = mb_strtolower((string) ($q->constraints['scientific_sense'] ?? $plan->researchIntent ?? ''));
        $factor = 'phytochemical';
        if (str_contains($sense, 'ethno') || str_contains(mb_strtolower($original), 'traditional')) {
            $factor = 'ethnobotany';
        } elseif (str_contains($sense, 'chem') || str_contains(mb_strtolower($original), 'phytochem')) {
            $factor = 'phytochemical';
        }

        $versions = [];
        $core = array_values(array_filter([$entity !== '' ? $entity : null, $factor]));
        $versions[] = [
            'query_version' => 'v1_entity_factor',
            'query' => implode(' ', $core !== [] ? $core : ['phytochemical']),
            'role' => 'primary',
            'csq_mutated' => false,
            'reason' => 'Primary LocalIndex AND query preserving plant identity and phytochem/ethnobotany factor.',
        ];
        if ($entity !== '') {
            $versions[] = [
                'query_version' => 'v2_entity_only',
                'query' => $entity,
                'role' => 'refinement',
                'csq_mutated' => false,
                'reason' => 'Entity-only refinement if factor over-constrains FTS AND.',
            ];
        }
        $versions[] = [
            'query_version' => 'v_neg',
            'query' => 'zzzxdukenonmatchtokenqqq999',
            'role' => 'negative_probe',
            'csq_mutated' => false,
            'reason' => 'Negative empty-result probe.',
        ];

        return [
            'source_key' => self::SOURCE_KEY,
            'adr_id' => self::ADR_ID,
            'original_question' => $original,
            'versions' => $versions,
            'primary_query' => (string) ($versions[0]['query'] ?? 'phytochemical'),
        ];
    }

    /** @return list<string> */
    public function retrievalQueries(KnowledgeQueryPlan $plan): array
    {
        $out = [];
        foreach ($this->specialize($plan)['versions'] as $v) {
            if (($v['role'] ?? '') === 'negative_probe') {
                continue;
            }
            $q = trim((string) ($v['query'] ?? ''));
            if ($q !== '') {
                $out[] = $q;
            }
        }

        return array_values(array_unique($out));
    }
}
