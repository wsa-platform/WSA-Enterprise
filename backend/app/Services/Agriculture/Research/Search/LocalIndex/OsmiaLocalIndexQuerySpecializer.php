<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * OSMIA (G3-05) LocalIndex retrieval-query specializer.
 *
 * LocalIndex FTS ANDs tokenized terms. Generic Stage-3 variants such as
 * "agriculture general knowledge" never hit the hymenoptera journal corpus.
 * Prefer short taxon / bee-scope tokens derived from the question without mutating CSQ.
 */
final class OsmiaLocalIndexQuerySpecializer
{
    public const SOURCE_KEY = 'osmia';

    public const ADR_ID = 'G3-05';

    /**
     * @return list<string>
     */
    public function retrievalQueries(KnowledgeQueryPlan $plan): array
    {
        $original = (string) $plan->normalizedQuery->originalQuestion;
        $normalized = (string) $plan->normalizedQuery->normalizedQuestion;
        $hay = trim($original.' '.$normalized);

        $queries = [];
        foreach ($this->binomials($hay) as $binomial) {
            $queries[] = $binomial;
            $genus = trim(explode(' ', $binomial)[0] ?? '');
            if ($genus !== '') {
                $queries[] = $genus;
            }
        }

        foreach ($this->familyOrHigherTaxa($hay) as $taxon) {
            $queries[] = $taxon;
            if ($this->hasBeeScope($hay)) {
                $queries[] = $taxon.' bee';
                $queries[] = 'classification '.$taxon;
            }
        }

        if ($this->hasBeeScope($hay)) {
            if ($this->isTaxonomyQuestion($plan, $hay)) {
                $queries[] = 'classification abeilles';
                $queries[] = 'classification bees';
                $queries[] = 'classification taxonomique abeilles';
                $queries[] = 'Melittidae Apis mellifera';
                $queries[] = 'abeille Melittidae';
                $queries[] = 'abeilles Melittidae';
            }
            $queries[] = 'Nomada';
            $queries[] = 'abeille';
            $queries[] = 'abeilles';
            $queries[] = 'bee pollinator';
        }

        $scientific = trim((string) ($plan->normalizedQuery->scientificName ?? ''));
        if ($scientific !== '') {
            array_unshift($queries, $scientific);
        }

        $out = [];
        foreach ($queries as $q) {
            $q = trim(preg_replace('/\s+/u', ' ', $q) ?? $q);
            if ($q !== '' && ! in_array($q, $out, true)) {
                $out[] = $q;
            }
        }

        return array_slice($out, 0, 6);
    }

    private function isTaxonomyQuestion(KnowledgeQueryPlan $plan, string $hay): bool
    {
        $questionType = trim((string) ($plan->normalizedQuery->constraints['question_type'] ?? ''));
        $required = trim((string) ($plan->normalizedQuery->constraints['required_evidence_type'] ?? ''));
        if ($questionType === 'species' || $required === 'species_list_or_taxonomy') {
            return true;
        }

        return preg_match('/\b(taxonomy|taxonomic|classification|phylogeny|تصنيف)\b/ui', $hay) === 1;
    }

    private function hasBeeScope(string $hay): bool
    {
        return preg_match(
            '/\b(bee|bees|abeille|abeilles|pollinator|pollinators|hymenoptera|apoidea|apidae|nomada|osmia|melittidae|apis|andrena|megachile|bombus|نحل|arı|arıcılık)\b/ui',
            $hay
        ) === 1;
    }

    /**
     * @return list<string>
     */
    private function binomials(string $text): array
    {
        if (preg_match_all('/\b([A-Z][a-z]{2,})\s+([a-z]{3,})\b/u', $text, $captures, PREG_SET_ORDER) === 0) {
            return [];
        }
        $out = [];
        foreach ($captures as $capture) {
            $binomial = trim(($capture[1] ?? '').' '.($capture[2] ?? ''));
            if ($binomial !== '' && ! in_array($binomial, $out, true)) {
                $out[] = $binomial;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function familyOrHigherTaxa(string $text): array
    {
        $out = [];
        if (preg_match_all('/\b([A-Z][a-z]{3,}(?:idae|inae|oidea|iformes))\b/u', $text, $matches) > 0) {
            foreach ($matches[1] as $taxon) {
                $taxon = trim((string) $taxon);
                if ($taxon !== '' && ! in_array($taxon, $out, true)) {
                    $out[] = $taxon;
                }
            }
        }
        if (preg_match_all('/\b([a-z]{3,}(?:idae|inae|oidea))\b/u', mb_strtolower($text), $low) > 0) {
            foreach ($low[1] as $taxon) {
                $taxon = ucfirst((string) $taxon);
                if ($taxon !== '' && ! in_array($taxon, $out, true)) {
                    $out[] = $taxon;
                }
            }
        }

        return $out;
    }
}
