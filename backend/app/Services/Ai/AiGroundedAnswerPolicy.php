<?php

namespace App\Services\Ai;

use App\Services\Ai\Rag\RagOrchestrator;
use App\Services\Ai\Rag\RagResult;
use App\Services\Ai\Retrieval\AiRetrievalHit;
use Illuminate\Support\Facades\Log;

class AiGroundedAnswerPolicy
{
    public function __construct(private RagOrchestrator $orchestrator) {}

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
            $result = $this->orchestrator->assemble($organizationId, $input);
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
        if ($citations === [] || $result->failed) {
            return AiGroundedAnswerDecision::ungrounded(
                $input,
                retrievalFailed: $result->failed,
                telemetry: $result->telemetry,
            );
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

    /**
     * @return list<array<string, mixed>>
     */
    private function usableCitations(RagResult $result): array
    {
        $citations = [];
        $seen = [];

        foreach ($result->citations as $citation) {
            if (! is_array($citation)) {
                continue;
            }
            $trusted = $this->trustedCitation($citation);
            if ($trusted === null) {
                continue;
            }
            $key = $trusted['source_type'].':'.$trusted['source_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $citations[] = $trusted;
        }

        foreach ($result->hits as $hit) {
            if (! $hit instanceof AiRetrievalHit) {
                continue;
            }
            $trusted = $this->trustedCitation([
                'title' => $hit->title,
                'reference' => $hit->sourceType.':'.$hit->sourceId,
                'source_type' => $hit->sourceType,
                'source_id' => $hit->sourceId,
                'score' => $hit->score,
            ]);
            if ($trusted === null) {
                continue;
            }
            $key = $trusted['source_type'].':'.$trusted['source_id'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $citations[] = $trusted;
        }

        return $citations;
    }

    /** @param  array<string, mixed>  $citation */
    private function trustedCitation(array $citation): ?array
    {
        unset($citation['url'], $citation['uri'], $citation['href'], $citation['link']);
        $sourceType = trim((string) ($citation['source_type'] ?? ''));
        $title = trim((string) ($citation['title'] ?? ''));
        $sourceId = (int) ($citation['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1 || $title === '') {
            return null;
        }

        $trusted = [
            'title' => $title,
            'reference' => (string) ($citation['reference'] ?? $sourceType.':'.$sourceId),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ];
        if (isset($citation['score']) && is_numeric($citation['score'])) {
            $trusted['score'] = round((float) $citation['score'], 4);
        }
        $freshness = strtolower((string) ($citation['freshness'] ?? ''));
        if (in_array($freshness, ['fresh', 'stale', 'unknown'], true)) {
            $trusted['freshness'] = $freshness;
        }

        return $trusted;
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
