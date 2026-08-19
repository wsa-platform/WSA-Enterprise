<?php

namespace App\Services\Ai;

class AiGroundedAnswerDisclosurePolicy
{
    public const STATE_GROUNDED = 'grounded';

    public const STATE_EMPTY_RETRIEVAL = 'empty_retrieval';

    public const STATE_RETRIEVAL_FAILED = 'retrieval_failed';

    public const STATE_GENERAL_REQUEST = 'general_request';

    public const EMPTY_RETRIEVAL_DISCLOSURE = 'لم يتم العثور على مصدر معرفي داخلي مطابق لهذا السؤال، لذلك فهذه إجابة عامة وليست مبنية على قاعدة المعرفة الداخلية.';

    public const RETRIEVAL_FAILED_DISCLOSURE = 'تعذر الوصول إلى قاعدة المعرفة الداخلية لهذا الطلب، لذلك فهذه إجابة عامة وليست مبنية على قاعدة المعرفة الداخلية.';

    /** @var list<string> */
    private const KNOWLEDGE_REQUEST_TYPES = [
        'library_summary',
        'library_qa',
        'assistant',
    ];

    /** @var list<string> */
    private const ANSWER_FIELDS = [
        'summary',
        'answer',
        'reply',
        'guidance',
        'content',
    ];

    /**
     * Apply user-visible grounding disclosure after AI-06 citation integrity.
     * Provider text and retrieved knowledge cannot change server-derived state.
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    public function apply(array $normalized, AiGroundedAnswerDecision $decision, string $requestType): array
    {
        $state = $this->state($decision, $requestType);
        $disclosure = $this->disclosureFor($state);

        $normalized['sources'] = $decision->citations;
        $normalized['grounded'] = $decision->grounded;
        $normalized['grounding_state'] = $state;
        $normalized['disclosure_applied'] = $disclosure !== '';
        $normalized['disclosure_code'] = $disclosure === '' ? null : $state;

        if ($disclosure !== '') {
            $normalized = $this->attachDisclosure($normalized, $disclosure);
        }

        return $normalized;
    }

    public function state(AiGroundedAnswerDecision $decision, string $requestType): string
    {
        if ($decision->grounded && $decision->citations !== []) {
            return self::STATE_GROUNDED;
        }

        if (! $this->isKnowledgeRequest($requestType) || ! config('ai.retrieval.enabled', true)) {
            return self::STATE_GENERAL_REQUEST;
        }

        if ($decision->retrievalFailed) {
            return self::STATE_RETRIEVAL_FAILED;
        }

        return self::STATE_EMPTY_RETRIEVAL;
    }

    public function isKnowledgeRequest(string $requestType): bool
    {
        return in_array($requestType, self::KNOWLEDGE_REQUEST_TYPES, true);
    }

    public function disclosureFor(string $state): string
    {
        return match ($state) {
            self::STATE_EMPTY_RETRIEVAL => self::EMPTY_RETRIEVAL_DISCLOSURE,
            self::STATE_RETRIEVAL_FAILED => self::RETRIEVAL_FAILED_DISCLOSURE,
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function attachDisclosure(array $normalized, string $disclosure): array
    {
        foreach (self::ANSWER_FIELDS as $field) {
            if (! isset($normalized[$field]) || ! is_string($normalized[$field])) {
                continue;
            }

            $normalized[$field] = $this->withDisclosure($normalized[$field], $disclosure);
        }

        return $normalized;
    }

    private function withDisclosure(string $answer, string $disclosure): string
    {
        if ($this->alreadyContainsDisclosure($answer)) {
            return $answer;
        }

        return $disclosure."\n\n".$answer;
    }

    private function alreadyContainsDisclosure(string $answer): bool
    {
        return str_contains($answer, self::EMPTY_RETRIEVAL_DISCLOSURE)
            || str_contains($answer, self::RETRIEVAL_FAILED_DISCLOSURE);
    }
}
