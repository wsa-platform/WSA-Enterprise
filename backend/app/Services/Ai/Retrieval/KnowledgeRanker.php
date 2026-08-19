<?php

namespace App\Services\Ai\Retrieval;

use DateTimeInterface;

/**
 * Deterministic keyword ranking.
 *
 * Primary relevance (high → low):
 *   exact title (100) > title phrase (40) > title token (12 each)
 *   > summary phrase (20) > summary token (6 each)
 *   > body phrase (8) > body token (2 each)
 *   + multi-term title coverage bonus
 * Freshness is a secondary signal in [0, 2] and cannot outrank a title token.
 * Missing timestamps score 0 freshness. Ties break by source_type, then source_id.
 */
class KnowledgeRanker
{
    private const EXACT_TITLE = 100.0;

    private const TITLE_PHRASE = 40.0;

    private const TITLE_TOKEN = 12.0;

    private const SUMMARY_PHRASE = 20.0;

    private const SUMMARY_TOKEN = 6.0;

    private const BODY_PHRASE = 8.0;

    private const BODY_TOKEN = 2.0;

    private const MULTI_TERM_TITLE = 15.0;

    private const LIBRARY_PRIORITY = 0.25;

    private const FRESHNESS_MAX = 2.0;

    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    /**
     * @param  list<string>  $keywords
     */
    public function score(string $query, array $keywords, AiKnowledgeDocument $document): float
    {
        if ($keywords === [] || ! $document->visible) {
            return 0.0;
        }

        $title = $this->normalizer->searchable($document->title);
        $summary = $this->normalizer->searchable($document->summary);
        $body = $this->normalizer->searchable($document->body);
        $normalizedQuery = $this->normalizer->searchable($query);

        $score = 0.0;
        if ($normalizedQuery !== '' && $title === $normalizedQuery) {
            $score += self::EXACT_TITLE;
        }
        if ($this->hasPhrase($title, $normalizedQuery)) {
            $score += self::TITLE_PHRASE;
        }
        if ($this->hasPhrase($summary, $normalizedQuery)) {
            $score += self::SUMMARY_PHRASE;
        }
        if ($this->hasPhrase($body, $normalizedQuery)) {
            $score += self::BODY_PHRASE;
        }

        $titleHits = 0;
        foreach ($keywords as $keyword) {
            $token = $this->normalizer->searchable($keyword);
            if ($token === '') {
                continue;
            }
            if (mb_strpos($title, $token) !== false) {
                $score += self::TITLE_TOKEN;
                $titleHits++;
            }
            if (mb_strpos($summary, $token) !== false) {
                $score += self::SUMMARY_TOKEN;
            }
            if (mb_strpos($body, $token) !== false) {
                $score += self::BODY_TOKEN;
            }
        }

        if ($titleHits >= 2 && count($keywords) >= 2) {
            $score += self::MULTI_TERM_TITLE * ($titleHits / count($keywords));
        }

        if ($document->sourceType === 'library_items') {
            $score += self::LIBRARY_PRIORITY;
        }

        $score += $this->freshness($document->updatedAt);

        return round($score, 4);
    }

    /**
     * @param  list<AiRetrievalHit>  $hits
     * @return list<AiRetrievalHit>
     */
    public function sort(array $hits): array
    {
        usort($hits, static function (AiRetrievalHit $left, AiRetrievalHit $right): int {
            $score = $right->score <=> $left->score;
            if ($score !== 0) {
                return $score;
            }
            $leftTime = $left->updatedAt?->getTimestamp() ?? 0;
            $rightTime = $right->updatedAt?->getTimestamp() ?? 0;
            $freshness = $rightTime <=> $leftTime;
            if ($freshness !== 0) {
                return $freshness;
            }
            $type = $left->sourceType <=> $right->sourceType;
            if ($type !== 0) {
                return $type;
            }

            return $left->sourceId <=> $right->sourceId;
        });

        return $hits;
    }

    public function freshness(?DateTimeInterface $updatedAt): float
    {
        if ($updatedAt === null) {
            return 0.0;
        }

        $days = (int) max(0, now()->diffInDays($updatedAt));
        $ratio = 1 - (min($days, 365) / 365);

        return round(self::FRESHNESS_MAX * max(0, $ratio), 4);
    }

    private function hasPhrase(string $haystack, string $phrase): bool
    {
        return $phrase !== '' && mb_strlen($phrase) >= 8 && mb_strpos($haystack, $phrase) !== false;
    }
}
