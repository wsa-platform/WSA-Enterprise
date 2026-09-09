<?php

namespace App\Services\Agriculture\Intelligence\DTO;

/**
 * Provider registry metadata (ADR-001).
 *
 * @phpstan-type CapabilityMap array<string, bool|string|list<string>>
 */
final class ProviderDescriptor
{
    /**
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $auth
     * @param  array<string, mixed>  $confidenceMeta
     * @param  list<string>  $limitations
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly array $capabilities = [],
        public readonly string $version = '1.0.0',
        public readonly int $priority = 100,
        public readonly int $timeoutSeconds = 15,
        public readonly string $health = 'unknown',
        public readonly array $auth = [],
        public readonly array $confidenceMeta = [],
        public readonly bool $evidenceCapable = true,
        public readonly array $limitations = [],
        public readonly array $config = [],
        public readonly bool $enabled = true,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'capabilities' => $this->capabilities,
            'version' => $this->version,
            'priority' => $this->priority,
            'timeout_seconds' => $this->timeoutSeconds,
            'health' => $this->health,
            'auth' => $this->auth,
            'confidence_meta' => $this->confidenceMeta,
            'evidence_capable' => $this->evidenceCapable,
            'limitations' => $this->limitations,
            'config' => $this->config,
            'enabled' => $this->enabled,
        ];
    }
}
