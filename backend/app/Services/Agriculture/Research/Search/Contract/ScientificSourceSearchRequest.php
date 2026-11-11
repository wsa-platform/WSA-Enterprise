<?php

namespace App\Services\Agriculture\Research\Search\Contract;

/**
 * Structured search input of adapter contract v2 (ADR-023 §8.36).
 *
 * queryText is built by ScientificSearchQueryBuilder, the query authority (D-04); adapters
 * must send it as given and never rewrite, expand or translate it.
 */
final readonly class ScientificSourceSearchRequest
{
    /** Option key through which v1 adapters receive the remaining search budget. */
    public const BUDGET_OPTION = 'search_budget_remaining_seconds';

    /**
     * @param  array<string, mixed>  $options  provider-specific filters, as in contract v1
     */
    public function __construct(
        public string $queryText,
        public int $limit,
        public array $options = [],
    ) {
        if ($limit < 1) {
            throw new ScientificSourceContractViolation("Search request limit must be at least 1; got {$limit}.");
        }
        if (array_is_list($options) && $options !== []) {
            throw new ScientificSourceContractViolation('Search request options must be keyed by option name.');
        }
    }

    /** Remaining search budget in seconds, or null when the caller set no deadline. */
    public function remainingBudgetSeconds(): ?float
    {
        $value = $this->options[self::BUDGET_OPTION] ?? null;

        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))
            ? (float) $value
            : null;
    }
}
