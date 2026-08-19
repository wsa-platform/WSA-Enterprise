<?php

namespace App\Services\Ai\Retrieval;

class KnowledgeTextNormalizer
{
    public function clean(string $value): string
    {
        $value = preg_replace('/[^\P{C}\n\t]+/u', ' ', $value) ?? $value;
        $value = str_replace("\0", '', $value);

        return trim($value);
    }

    public function searchable(string $value): string
    {
        $value = $this->clean($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($value));
    }

    /** @return list<string> */
    public function tokens(string $value): array
    {
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $this->searchable($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            if ($part === '' || mb_strlen($part) < 3) {
                continue;
            }
            $tokens[$part] = $part;
        }

        return array_values($tokens);
    }

    public function excerpt(string $value, int $maxCharacters): string
    {
        $value = $this->clean($value);
        if ($value === '' || $maxCharacters < 1) {
            return '';
        }

        if (mb_strlen($value) <= $maxCharacters) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxCharacters));
    }
}
