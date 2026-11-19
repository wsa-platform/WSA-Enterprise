<?php

namespace App\Services\Agriculture\Research\Synthesis;

use InvalidArgumentException;

/**
 * Server-side stable answer result set for pagination without re-search.
 *
 * Stores ordered independent answers after validation + composition complete.
 * Page reads are pure slice operations — no Orchestrator, Composer, or Validation.
 */
final class StableAnswerResultSet
{
    public const PAGE_SIZE = 10;

    public const DEFAULT_TTL_SECONDS = 3600;

    /**
     * @param  list<array<string, mixed>>  $answers
     * @param  list<array<string, mixed>>  $references
     * @param  array<string, mixed>  $observability
     */
    public function __construct(
        public readonly string $resultSetId,
        public readonly int $organizationId,
        public readonly string $originalQuestion,
        public readonly ?string $csqFingerprint,
        public readonly array $answers,
        public readonly array $references,
        public readonly string $createdAt,
        public readonly string $expiresAt,
        public readonly array $observability = [],
        public readonly string $status = 'ready',
    ) {
        if (trim($this->resultSetId) === '') {
            throw new InvalidArgumentException('StableAnswerResultSet requires resultSetId.');
        }
        if ($this->organizationId <= 0) {
            throw new InvalidArgumentException('StableAnswerResultSet requires a positive organizationId.');
        }
    }

    public function totalAnswers(): int
    {
        return count($this->answers);
    }

    public function totalPages(): int
    {
        $total = $this->totalAnswers();
        if ($total === 0) {
            return 0;
        }

        return (int) ceil($total / self::PAGE_SIZE);
    }

    public function isExpired(?int $now = null): bool
    {
        $now = $now ?? time();

        return $now >= strtotime($this->expiresAt);
    }

    /**
     * @return array{
     *   result_set_id: string,
     *   page: int,
     *   page_size: int,
     *   total_answers: int,
     *   total_pages: int,
     *   answers: list<array<string, mixed>>,
     *   references: list<array<string, mixed>>,
     *   status: string,
     *   expires_at: string,
     *   rerun_performed: false
     * }
     */
    public function page(int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $slice = array_slice($this->answers, $offset, self::PAGE_SIZE);

        return [
            'result_set_id' => $this->resultSetId,
            'page' => $page,
            'page_size' => self::PAGE_SIZE,
            'total_answers' => $this->totalAnswers(),
            'total_pages' => $this->totalPages(),
            'answers' => array_values($slice),
            'references' => $this->references,
            'status' => $this->status,
            'expires_at' => $this->expiresAt,
            'original_question' => $this->originalQuestion,
            'rerun_performed' => false,
            'observability' => array_merge($this->observability, [
                'pagination_mode' => 'stable_result_set_slice',
                'composer_rerun' => false,
                'search_rerun' => false,
                'validation_rerun' => false,
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'result_set_id' => $this->resultSetId,
            'organization_id' => $this->organizationId,
            'original_question' => $this->originalQuestion,
            'csq_fingerprint' => $this->csqFingerprint,
            'answers' => $this->answers,
            'references' => $this->references,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'status' => $this->status,
            'observability' => $this->observability,
            'total_answers' => $this->totalAnswers(),
            'page_size' => self::PAGE_SIZE,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            resultSetId: (string) ($data['result_set_id'] ?? ''),
            organizationId: (int) ($data['organization_id'] ?? 0),
            originalQuestion: (string) ($data['original_question'] ?? ''),
            csqFingerprint: isset($data['csq_fingerprint']) ? (string) $data['csq_fingerprint'] : null,
            answers: is_array($data['answers'] ?? null) ? array_values($data['answers']) : [],
            references: is_array($data['references'] ?? null) ? array_values($data['references']) : [],
            createdAt: (string) ($data['created_at'] ?? gmdate('c')),
            expiresAt: (string) ($data['expires_at'] ?? gmdate('c', time() + self::DEFAULT_TTL_SECONDS)),
            observability: is_array($data['observability'] ?? null) ? $data['observability'] : [],
            status: (string) ($data['status'] ?? 'ready'),
        );
    }
}
