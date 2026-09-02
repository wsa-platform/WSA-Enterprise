<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Stage 4 evidence extraction — uses only available abstract/title text.
 */
class ScientificEvidenceExtractor
{
    /**
     * @return array{text: string|null, claim_topic: string, completeness: string}
     */
    public function extract(ScientificSearchResult $result, KnowledgeQueryPlan $plan): array
    {
        $abstract = $result->abstract !== null ? trim((string) $result->abstract) : '';
        $title = trim($result->title);
        $text = $abstract !== '' ? $abstract : ($title !== '' ? $title : null);

        $claimTopic = $plan->normalizedQuery->normalizedQuestion !== ''
            ? $plan->normalizedQuery->normalizedQuestion
            : $plan->normalizedQuery->originalQuestion;

        $completeness = match (true) {
            $abstract !== '' => 'abstract_available',
            $title !== '' => 'title_only',
            default => 'insufficient',
        };

        return [
            'text' => $text,
            'claim_topic' => $claimTopic,
            'completeness' => $completeness,
        ];
    }
}
