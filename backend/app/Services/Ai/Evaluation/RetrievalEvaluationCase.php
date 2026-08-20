<?php

namespace App\Services\Ai\Evaluation;

final class RetrievalEvaluationCase
{
    /**
     * @param  list<string>  $expectedIds  Composite keys `source_type:source_id`
     */
    public function __construct(
        public readonly string $id,
        public readonly int $organizationId,
        public readonly string $query,
        public readonly array $expectedIds = [],
        public readonly int $k = 5,
        public readonly string $strategy = 'keyword',
        public readonly ?string $expectedTopId = null,
    ) {}
}
