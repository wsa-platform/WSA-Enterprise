<?php

namespace App\Services\Ai\Rag;

use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\KnowledgeFreshnessService;
use App\Services\Ai\Retrieval\KnowledgeTextNormalizer;

class RagCandidateProcessor
{
    public function __construct(
        private KnowledgeTextNormalizer $normalizer,
        private KnowledgeFreshnessService $freshness,
    ) {}

    /**
     * Deduplicate, apply score threshold, and drop cross-tenant hits.
     *
     * @param  list<AiRetrievalHit>  $hits
     * @return list<AiRetrievalHit>
     */
    public function process(int $organizationId, array $hits): array
    {
        $minScore = $this->minScore();
        $dedupeContent = (bool) config('ai.rag.dedupe_content', true);
        $kept = [];
        $seenKeys = [];
        $seenContent = [];

        foreach ($hits as $hit) {
            if (! $hit instanceof AiRetrievalHit) {
                continue;
            }
            if ($hit->sourceId < 1 || trim($hit->title) === '' || trim($hit->content) === '') {
                continue;
            }
            if ($hit->organizationId !== null && $hit->organizationId !== $organizationId) {
                continue;
            }
            if ($hit->score < $minScore) {
                continue;
            }

            $key = $hit->sourceType.':'.$hit->sourceId;
            if (isset($seenKeys[$key])) {
                continue;
            }

            if ($dedupeContent) {
                $fingerprint = $this->contentFingerprint($hit);
                if (isset($seenContent[$fingerprint])) {
                    continue;
                }
                $seenContent[$fingerprint] = true;
            }

            $seenKeys[$key] = true;
            $kept[] = $hit;
        }

        return $kept;
    }

    public function freshnessLabel(AiRetrievalHit $hit): string
    {
        return $this->freshness->classify($hit->updatedAt);
    }

    private function contentFingerprint(AiRetrievalHit $hit): string
    {
        return hash('sha256', $this->normalizer->searchable($hit->title)."\n".$this->normalizer->searchable($hit->content));
    }

    private function minScore(): float
    {
        $value = (float) config('ai.rag.min_score', 0);
        if (! is_finite($value)) {
            return 0.0;
        }

        return max(0.0, min(1000.0, $value));
    }
}
