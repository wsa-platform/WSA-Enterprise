<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Immutable capability profile for one scientific source.
 *
 * Describes what a source can represent — not what a question means.
 * CSQ remains semantic authority; this profile never mutates CSQ.
 */
final readonly class ScientificSourceProfile
{
    /**
     * @param  list<string>  $supportedIdentityFields  e.g. entity, target, process, property
     * @param  list<string>  $supportedConstraintFields  e.g. geography, time, relation
     * @param  list<string>  $identifierFields  e.g. doi, openalex_id, item_code
     */
    public function __construct(
        public string $sourceKey,
        public string $modality,
        public array $supportedIdentityFields,
        public array $supportedConstraintFields,
        public string $queryStyle,
        public array $identifierFields = [],
        public bool $active = true,
        public int $maxQueriesPerRequest = 1,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'modality' => $this->modality,
            'supported_identity_fields' => $this->supportedIdentityFields,
            'supported_constraint_fields' => $this->supportedConstraintFields,
            'query_style' => $this->queryStyle,
            'identifier_fields' => $this->identifierFields,
            'active' => $this->active,
            'max_queries_per_request' => $this->maxQueriesPerRequest,
        ];
    }

    public function supportsField(string $field): bool
    {
        return in_array($field, $this->supportedIdentityFields, true)
            || in_array($field, $this->supportedConstraintFields, true);
    }
}
