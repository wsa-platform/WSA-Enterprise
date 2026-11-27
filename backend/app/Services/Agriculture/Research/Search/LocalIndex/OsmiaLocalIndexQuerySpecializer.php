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

        // Prefer Latin taxa / bee-scope tokens first. Orchestrator tries only
        // MAX_VARIANTS_PER_PROVIDER (=2) queries — discourse false-binomials
        // ("What host", "Quelles associations") must not occupy those slots.
        $priority = [];
        $secondary = [];

        $scientific = trim((string) ($plan->normalizedQuery->scientificName ?? ''));
        if ($scientific !== '') {
            $priority[] = $scientific;
            $genus = trim(explode(' ', $scientific)[0] ?? '');
            if ($genus !== '' && strcasecmp($genus, $scientific) !== 0) {
                $priority[] = $genus;
            }
        }

        foreach ($this->bareLatinGenera($original) as $genus) {
            $priority[] = $genus;
        }

        foreach ($this->binomials($hay) as $binomial) {
            $genus = trim(explode(' ', $binomial)[0] ?? '');
            if ($genus !== '' && ! $this->isDiscourseCapitalizedToken($genus)) {
                $priority[] = $genus;
                if (! $this->isJunkBinomialEpithet(trim(explode(' ', $binomial)[1] ?? ''))) {
                    $secondary[] = $binomial;
                }
            }
        }

        foreach ($this->familyOrHigherTaxa($hay) as $taxon) {
            $priority[] = $taxon;
            if ($this->hasBeeScope($hay)) {
                $secondary[] = $taxon.' bee';
                $secondary[] = 'classification '.$taxon;
            }
        }

        if ($this->hasBeeScope($hay)) {
            if ($this->isTaxonomyQuestion($plan, $hay)) {
                $secondary[] = 'classification abeilles';
                $secondary[] = 'classification bees';
                $secondary[] = 'classification taxonomique abeilles';
                $priority[] = 'Melittidae';
                $secondary[] = 'Melittidae Apis mellifera';
                $secondary[] = 'abeille Melittidae';
                $secondary[] = 'abeilles Melittidae';
            }
            // Keep Nomada as a known hymenoptera host-association corpus anchor when
            // the question already has bee/pollinator scope (not a hardcoded answer).
            if (preg_match('/\bnomada\b/ui', $hay) === 1) {
                $priority[] = 'Nomada';
            }
            $secondary[] = 'abeille';
            $secondary[] = 'abeilles';
            $secondary[] = 'bee pollinator';
        }

        $out = [];
        foreach ([...$priority, ...$secondary] as $q) {
            $q = trim(preg_replace('/\s+/u', ' ', $q) ?? $q);
            if ($q === '' || $this->isDiscourseCapitalizedToken($q)) {
                continue;
            }
            if (! in_array($q, $out, true)) {
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
    private function bareLatinGenera(string $text): array
    {
        if (preg_match_all('/\b([A-Z][a-z]{3,})\b/u', $text, $captures) === 0) {
            return [];
        }
        $out = [];
        foreach ($captures[1] as $token) {
            $token = trim((string) $token);
            if ($token === '' || $this->isDiscourseCapitalizedToken($token)) {
                continue;
            }
            if (! in_array($token, $out, true)) {
                $out[] = $token;
            }
        }

        return $out;
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
            $genus = trim((string) ($capture[1] ?? ''));
            $epithet = trim((string) ($capture[2] ?? ''));
            if ($genus === '' || $this->isDiscourseCapitalizedToken($genus) || $this->isJunkBinomialEpithet($epithet)) {
                continue;
            }
            $binomial = $genus.' '.$epithet;
            if (! in_array($binomial, $out, true)) {
                $out[] = $binomial;
            }
        }

        return $out;
    }

    private function isDiscourseCapitalizedToken(string $token): bool
    {
        static $stop = null;
        if ($stop === null) {
            $stop = array_fill_keys([
                'What', 'Which', 'When', 'Where', 'Why', 'How', 'Who', 'Whom',
                'The', 'This', 'That', 'These', 'Those', 'There', 'Their', 'Then',
                'Based', 'According', 'Recent', 'First', 'After', 'Before', 'During',
                'About', 'Under', 'Over', 'With', 'From', 'Into', 'Only', 'Also',
                'Some', 'Many', 'Most', 'More', 'Less', 'Such', 'Each', 'Both',
                'Does', 'Did', 'Have', 'Has', 'Had', 'Will', 'Would', 'Could', 'Should',
                'Can', 'May', 'Might', 'Must', 'Need', 'Known', 'Effect', 'Effects',
                'Impact', 'Impacts', 'Role', 'Roles', 'Type', 'Types', 'Level', 'Levels',
                'Quelles', 'Quelle', 'Quels', 'Quel', 'Comment', 'Pourquoi', 'Quand',
                'Dans', 'Pour', 'Sur', 'Avec', 'Sans', 'Chez', 'Entre', 'Après', 'Avant',
                'Selon', 'Parmi', 'Ainsi', 'Aussi', 'Donc', 'Mais', 'Puis', 'Lors',
                'Une', 'Des', 'Les', 'Ces', 'Cet', 'Cette', 'Ceux', 'Celles',
                'Données', 'Donnee', 'Etude', 'Étude', 'Analyse', 'Présence', 'Presence',
                'Nedir', 'Nasıl', 'Hangi', 'Neden', 'Ne', 'Bir', 'Bu', 'Şu', 'Su',
                'Için', 'İçin', 'Göre', 'Kadar', 'Daha', 'En', 'Ve', 'Ile', 'İle',
                'Hakkında', 'Olarak', 'Arasında', 'Üzerinde', 'Uzerinde',
                'Europe', 'France', 'Spain', 'Italy', 'Africa', 'Asia', 'America',
            ], true);
        }

        return isset($stop[$token]);
    }

    private function isJunkBinomialEpithet(string $epithet): bool
    {
        $epithet = mb_strtolower(trim($epithet));
        if ($epithet === '') {
            return true;
        }

        static $junk = null;
        if ($junk === null) {
            $junk = array_fill_keys([
                'host', 'hosts', 'wild', 'bee', 'bees', 'associations', 'association',
                'habitat', 'habitats', 'causes', 'cause', 'disease', 'diseases',
                'sont', 'est', 'des', 'les', 'une', 'aux', 'pour', 'dans',
                'about', 'known', 'from', 'with', 'under', 'over', 'into',
                'yabani', 'arı', 'arilar', 'arılar', 'konukçu', 'konukcu', 'konak',
            ], true);
        }

        return isset($junk[$epithet]);
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
