<?php

namespace App\Services\Ai;

use App\Services\Ai\Retrieval\AiRetrievalHit;
use App\Services\Ai\Retrieval\AiRetrievalResult;
use App\Services\Ai\Retrieval\KeywordKnowledgeRetriever;
use Illuminate\Support\Facades\Log;

class AiGroundedAnswerPolicy
{
    public function __construct(private KeywordKnowledgeRetriever $retriever) {}

    /**
     * Retrieve existing knowledge and prepare provider input.
     * Retrieval failure degrades to an ungrounded request and never fails the AI call.
     *
     * @param  array<string, mixed>  $input
     */
    public function prepare(int $organizationId, array $input): AiGroundedAnswerDecision
    {
        if (! config('ai.retrieval.enabled', true)) {
            return AiGroundedAnswerDecision::ungrounded($input, telemetry: [
                'candidate_count' => 0,
                'returned_count' => 0,
                'retrieval_duration_ms' => 0,
                'source_types' => [],
                'retrieval_status' => 'disabled',
            ]);
        }

        try {
            $result = $this->retriever->retrieve($organizationId, $this->query($input));
        } catch (\Throwable $exception) {
            Log::warning('AI retrieval failed', [
                'organization_id' => $organizationId,
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);

            return AiGroundedAnswerDecision::ungrounded($input, retrievalFailed: true, telemetry: [
                'candidate_count' => 0,
                'returned_count' => 0,
                'retrieval_duration_ms' => 0,
                'source_types' => [],
                'retrieval_status' => 'failed',
            ]);
        }

        $citations = $this->usableCitations($result);
        if ($citations === []) {
            return AiGroundedAnswerDecision::ungrounded($input, telemetry: $result->telemetry);
        }

        $context = $this->boundedContext($result->context);
        $input['retrieved_context'] = $context;
        $input['sources'] = $citations;

        return new AiGroundedAnswerDecision(
            grounded: true,
            retrievalFailed: false,
            citations: $citations,
            retrievedContext: $context,
            providerInput: $input,
            retrievalTelemetry: $result->telemetry,
        );
    }

    /**
     * Keep structured citations server-controlled. Provider/model text may mention sources,
     * but only trusted retrieval metadata is exposed on the response.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    public function applyToOutput(array $normalized, AiGroundedAnswerDecision $decision): array
    {
        $normalized['sources'] = $decision->citations;
        $normalized['grounded'] = $decision->grounded;

        return $normalized;
    }

    /** @param  array<string, mixed>  $input */
    private function query(array $input): string
    {
        foreach (['message', 'content', 'query', 'notes', 'question', 'title', 'lesson_title'] as $key) {
            if (isset($input[$key]) && is_string($input[$key]) && trim($input[$key]) !== '') {
                return $input[$key];
            }
        }

        return '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function usableCitations(AiRetrievalResult $result): array
    {
        $citations = [];
        $seen = [];

        foreach ($result->hits as $hit) {
            if (! $hit instanceof AiRetrievalHit) {
                continue;
            }

            $citation = $this->trustedCitation($hit);
            if ($citation === null) {
                continue;
            }

            $key = $citation['source_type'].':'.$citation['source_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $citations[] = $citation;
        }

        return $citations;
    }

    /** @return array<string, mixed>|null */
    private function trustedCitation(AiRetrievalHit $hit): ?array
    {
        $sourceType = trim($hit->sourceType);
        $title = trim($hit->title);
        if ($sourceType === '' || $hit->sourceId < 1 || $title === '') {
            return null;
        }

        $citation = $hit->toCitation();
        unset($citation['url'], $citation['uri'], $citation['href'], $citation['link']);

        if (($citation['source_type'] ?? '') === '' || ! isset($citation['source_id']) || ($citation['title'] ?? '') === '') {
            return null;
        }

        return [
            'title' => (string) $citation['title'],
            'reference' => (string) ($citation['reference'] ?? $sourceType.':'.$hit->sourceId),
            'source_type' => (string) $citation['source_type'],
            'source_id' => (int) $citation['source_id'],
        ];
    }

    private function boundedContext(string $context): string
    {
        $maxChars = max(1, (int) config('ai.retrieval.max_context_characters', 4000));
        if (mb_strlen($context) <= $maxChars) {
            return $context;
        }

        return mb_substr($context, 0, $maxChars);
    }
}
