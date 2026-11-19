<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * BHL PublicationSearch query specialization (G6-20 projection).
 *
 * Prefers scientific-name / heritage-literature queries. Does not claim OCR
 * full-text comprehension or modern clinical conclusions from historical pages.
 */
final class BhlPublicationQuerySpecializer
{
    public const SOURCE_KEY = 'bhl';

    /**
     * @return list<string>
     */
    public function retrievalQueries(KnowledgeQueryPlan $plan): array
    {
        $scientific = trim((string) ($plan->normalizedQuery->scientificName ?? ''));
        $crop = trim((string) ($plan->normalizedQuery->cropId ?? $plan->normalizedQuery->crop ?? ''));
        $topic = trim((string) ($plan->normalizedQuery->topic ?? ''));
        $intent = trim((string) $plan->researchIntent);

        $queries = [];
        if ($scientific !== '') {
            $queries[] = $scientific;
            if ($topic !== '' && ! str_contains(mb_strtolower($scientific), mb_strtolower($topic))) {
                $queries[] = trim($scientific.' '.$topic);
            }
            if (in_array($intent, ['taxonomy', 'species_lookup', 'biodiversity'], true)) {
                $queries[] = trim($scientific.' botanical literature');
            }
        } elseif ($crop !== '') {
            $queries[] = $crop;
            if ($topic !== '') {
                $queries[] = trim($crop.' '.$topic);
            }
        }

        return array_values(array_unique(array_filter(
            $queries,
            static fn (string $q): bool => trim($q) !== '',
        )));
    }
}
