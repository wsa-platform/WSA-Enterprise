<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

/**
 * Differential / alternative diagnosis entry with evidence polarity.
 */
final class DiagnosisDifferentialEntry
{
    public const RELATION_ALTERNATIVE = 'alternative';

    public const RELATION_SUPPORTING = 'supporting';

    public const RELATION_CONTRADICTING = 'contradicting';

    public function __construct(
        public readonly string $id,
        public readonly string $commonName,
        public readonly string $relation = self::RELATION_ALTERNATIVE,
        public readonly ?string $notes = null,
        public readonly ?string $category = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'common_name' => $this->commonName,
            'relation' => $this->relation,
            'notes' => $this->notes,
            'category' => $this->category,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? 'diff'),
            commonName: (string) ($data['common_name'] ?? ''),
            relation: (string) ($data['relation'] ?? self::RELATION_ALTERNATIVE),
            notes: isset($data['notes']) && is_string($data['notes']) ? $data['notes'] : null,
            category: isset($data['category']) && is_string($data['category']) ? $data['category'] : null,
        );
    }
}
