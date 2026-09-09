<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Capability-driven provider query input (no crop-specific branches).
 */
final class ProviderQueryInput
{
    /**
     * @param  list<string>  $entities
     * @param  list<string>  $requiredCapabilities
     * @param  array<string, mixed>  $constraints
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $query,
        public readonly string $language = 'en',
        public readonly array $entities = [],
        public readonly array $requiredCapabilities = [],
        public readonly array $constraints = [],
        public readonly array $context = [],
        public readonly int $limit = 10,
        public readonly ?string $intent = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'language' => $this->language,
            'entities' => $this->entities,
            'required_capabilities' => $this->requiredCapabilities,
            'constraints' => $this->constraints,
            'context' => $this->context,
            'limit' => $this->limit,
            'intent' => $this->intent,
        ];
    }
}
