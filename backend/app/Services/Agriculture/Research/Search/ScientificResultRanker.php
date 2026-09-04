<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Deterministic relevance ranking — not scientific validity (Stage 4).
 *
 * Query-aware scores: entity / topic / intent / sense / directness / quality metadata.
 * Diversity prefers provider/journal/institution — never country.
 */
class ScientificResultRanker
{
    public function __construct(
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
    ) {}

    /**
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function rank(string $searchQuery, array $results, ?KnowledgeQueryPlan $plan = null): array
    {
        $queryTerms = $this->terms($searchQuery);
        $ranked = [];

        foreach ($results as $result) {
            $score = $this->scoreResult($result, $queryTerms);
            $metadata = [
                'ranking_basis' => 'query_relevance_directness_quality',
                'not_scientific_validation' => true,
            ];

            if ($plan !== null) {
                $assessment = $this->relevanceGate->assess(
                    $plan,
                    $result->title,
                    $result->abstract,
                    $result->doi,
                    $this->extraTextFromResult($result),
                );
                $directness = $this->directnessAssessor->assess(
                    $plan,
                    $result->title,
                    $result->abstract,
                    $result->doi,
                    $this->extraTextFromResult($result),
                );

                $score += $assessment['score'];
                $score += $directness['score'];
                $score += $this->qualityMetadataBonus($result);

                $metadata['entity_matched'] = $assessment['entity_matched'];
                $metadata['topic_matched'] = $assessment['topic_matched'];
                $metadata['sense_matched'] = $assessment['sense_matched'] ?? false;
                $metadata['context_matched'] = $assessment['context_matched'] ?? false;
                $metadata['context_adequate'] = $assessment['context_adequate'] ?? false;
                $metadata['relevance_gate'] = $assessment['relevant'];
                $metadata['evidence_directness'] = $directness['directness'];
                $metadata['directness_reasons'] = $directness['reasons'];
                $metadata['factor_coverage'] = $directness['factor_coverage'];

                if (! $assessment['relevant']
                    || $directness['directness'] === ScientificEvidenceDirectnessAssessor::IRRELEVANT) {
                    $metadata['rejected_by_relevance_gate'] = true;
                    $metadata['rejection_reasons'] = $assessment['rejection_reasons'] !== []
                        ? $assessment['rejection_reasons']
                        : $directness['reasons'];
                    $score *= 0.05;
                }
            }

            $ranked[] = $result->withRelevanceScore($score, $metadata);
        }

        usort($ranked, function (ScientificSearchResult $a, ScientificSearchResult $b): int {
            $scoreCompare = ($b->relevanceScore ?? 0.0) <=> ($a->relevanceScore ?? 0.0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            $directnessOrder = [
                ScientificEvidenceDirectnessAssessor::DIRECT => 0,
                ScientificEvidenceDirectnessAssessor::SUPPORTING => 1,
                ScientificEvidenceDirectnessAssessor::BACKGROUND => 2,
                ScientificEvidenceDirectnessAssessor::IRRELEVANT => 3,
            ];
            $aDir = $directnessOrder[$a->relevanceMetadata['evidence_directness'] ?? ''] ?? 9;
            $bDir = $directnessOrder[$b->relevanceMetadata['evidence_directness'] ?? ''] ?? 9;
            if ($aDir !== $bDir) {
                return $aDir <=> $bDir;
            }

            return strcmp($a->title, $b->title);
        });

        return $ranked;
    }

    /**
     * Drop results rejected by the relevance gate when a plan is available.
     *
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function filterRelevant(array $results): array
    {
        return array_values(array_filter(
            $results,
            static fn (ScientificSearchResult $result): bool => ! ($result->relevanceMetadata['rejected_by_relevance_gate'] ?? false),
        ));
    }

    /**
     * Mild diversity across provider / journal / institution when scores are similar.
     * Country is never used.
     *
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function diversifyByProviderJournalInstitution(array $results): array
    {
        if (count($results) <= 2) {
            return $results;
        }

        $selected = [];
        $seenProviders = [];
        $seenJournals = [];
        $seenInstitutions = [];
        $deferred = [];

        foreach ($results as $index => $result) {
            $topScore = $results[0]->relevanceScore ?? 0.0;
            $score = $result->relevanceScore ?? 0.0;
            $similarQuality = $topScore <= 0.0 || abs($topScore - $score) <= max(8.0, $topScore * 0.12);

            $provider = $result->sourceKey;
            $journal = mb_strtolower(trim((string) ($result->journal ?? '')));
            $institution = mb_strtolower(trim((string) ($this->institutionFromResult($result) ?? '')));

            $providerDup = isset($seenProviders[$provider]) && $seenProviders[$provider] >= 2;
            $journalDup = $journal !== '' && isset($seenJournals[$journal]);
            $institutionDup = $institution !== '' && isset($seenInstitutions[$institution]);

            if ($index === 0 || ! $similarQuality || (! $providerDup && ! $journalDup && ! $institutionDup)) {
                $selected[] = $result;
                $seenProviders[$provider] = ($seenProviders[$provider] ?? 0) + 1;
                if ($journal !== '') {
                    $seenJournals[$journal] = true;
                }
                if ($institution !== '') {
                    $seenInstitutions[$institution] = true;
                }

                continue;
            }

            $deferred[] = $result;
        }

        return array_values(array_merge($selected, $deferred));
    }

    /**
     * @param  list<string>  $queryTerms
     */
    private function scoreResult(ScientificSearchResult $result, array $queryTerms): float
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $result->journal,
            implode(' ', $result->authors),
        ])));

        if ($haystack === '') {
            return 0.0;
        }

        $score = 0.0;
        foreach ($queryTerms as $term) {
            if ($term === '') {
                continue;
            }
            if (str_contains($haystack, $term)) {
                $score += mb_strlen($term);
            }
        }

        $normalizedTitle = mb_strtolower(trim($result->title));
        $normalizedQuery = mb_strtolower(trim(implode(' ', $queryTerms)));
        if ($normalizedTitle !== '' && $normalizedQuery !== '' && str_contains($normalizedTitle, $normalizedQuery)) {
            $score += 20.0;
        }

        if ($result->publicationYear !== null && $result->publicationYear >= (int) date('Y') - 10) {
            $score += 1.0;
        }

        if (count($result->foundBySources) > 1) {
            $score += 2.0;
        }

        return $score;
    }

    private function qualityMetadataBonus(ScientificSearchResult $result): float
    {
        $bonus = 0.0;
        if ($result->doi !== null && trim($result->doi) !== '') {
            $bonus += 2.0;
        }
        if ($result->journal !== null && trim($result->journal) !== '') {
            $bonus += 1.5;
        }
        if ($result->publicationYear !== null) {
            $bonus += 0.5;
        }
        if (count($result->authors) > 0) {
            $bonus += 0.5;
        }

        return $bonus;
    }

    private function institutionFromResult(ScientificSearchResult $result): ?string
    {
        $raw = $result->rawMetadata ?? [];
        $openalex = is_array($raw['openalex'] ?? null) ? $raw['openalex'] : [];
        $authorships = is_array($openalex['authorships'] ?? null) ? $openalex['authorships'] : [];
        foreach ($authorships as $authorship) {
            if (! is_array($authorship)) {
                continue;
            }
            $institutions = is_array($authorship['institutions'] ?? null) ? $authorship['institutions'] : [];
            foreach ($institutions as $institution) {
                if (is_array($institution) && isset($institution['display_name'])) {
                    $name = trim((string) $institution['display_name']);
                    if ($name !== '') {
                        return $name;
                    }
                }
            }
        }

        return null;
    }

    /** @return list<string> */
    private function terms(string $query): array
    {
        $normalized = mb_strtolower(trim($query));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 3));
    }

    /**
     * Keywords/concepts/metadata as secondary text after title+abstract.
     */
    private function extraTextFromResult(ScientificSearchResult $result): ?string
    {
        $parts = [];
        $raw = $result->rawMetadata ?? [];

        $openalex = is_array($raw['openalex'] ?? null) ? $raw['openalex'] : [];
        foreach (is_array($openalex['concepts'] ?? null) ? $openalex['concepts'] : [] as $concept) {
            if (is_array($concept) && isset($concept['display_name'])) {
                $parts[] = (string) $concept['display_name'];
            }
        }
        foreach (is_array($openalex['keywords'] ?? null) ? $openalex['keywords'] : [] as $keyword) {
            if (is_array($keyword) && isset($keyword['display_name'])) {
                $parts[] = (string) $keyword['display_name'];
            } elseif (is_string($keyword)) {
                $parts[] = $keyword;
            }
        }

        $crossref = is_array($raw['crossref'] ?? null) ? $raw['crossref'] : [];
        foreach (is_array($crossref['subject'] ?? null) ? $crossref['subject'] : [] as $subject) {
            if (is_string($subject)) {
                $parts[] = $subject;
            }
        }

        $joined = trim(implode(' ', $parts));

        return $joined !== '' ? $joined : null;
    }
}
