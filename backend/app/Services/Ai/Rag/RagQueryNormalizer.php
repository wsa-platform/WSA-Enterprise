<?php

namespace App\Services\Ai\Rag;

use App\Services\Ai\Retrieval\KnowledgeTextNormalizer;

class RagQueryNormalizer
{
    /** @var list<string> */
    private const QUERY_KEYS = [
        'message', 'content', 'query', 'notes', 'question', 'title', 'lesson_title',
    ];

    public function __construct(private KnowledgeTextNormalizer $normalizer) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function fromInput(array $input): string
    {
        foreach (self::QUERY_KEYS as $key) {
            if (isset($input[$key]) && is_string($input[$key]) && trim($input[$key]) !== '') {
                return $this->normalizer->clean($input[$key]);
            }
        }

        return '';
    }

    public function searchable(string $query): string
    {
        return $this->normalizer->searchable($query);
    }
}
