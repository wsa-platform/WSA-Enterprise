<?php

namespace App\Services\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceRegistryInvariantViolation;
use Illuminate\Contracts\Container\Container;

/**
 * Registry of Stage 3 scientific source adapters.
 *
 * Adapters are resolved from ScientificSourceCatalog registrations, in catalog order.
 * A source-family adapter is constructed with its registration's profile (ADR-023 §8.39).
 * Internet-First path selects OpenAlex + Crossref + Semantic Scholar (+ FAOSTAT Portal when enabled).
 * Consensus remains registered for optional/legacy use but is not required.
 * fao_stat is exclusively FaoStatDeveloperPortalAdapter.
 */
class ScientificSourceAdapterRegistry
{
    /** @var array<string, ScientificSourceAdapterInterface> */
    private array $adapters = [];

    public function __construct(Container $container, ?ScientificSourceCatalog $catalog = null)
    {
        foreach (($catalog ?? ScientificSourceCatalog::default())->all() as $sourceKey => $registration) {
            $adapter = $container->make(
                $registration->adapterClass,
                $registration->familyProfile !== null ? ['profile' => $registration->familyProfile] : [],
            );
            if (! $adapter instanceof ScientificSourceAdapterInterface) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Resolved adapter for '{$sourceKey}' does not implement ScientificSourceAdapterInterface."
                );
            }
            if ($adapter->sourceKey() !== $sourceKey) {
                throw new ScientificSourceRegistryInvariantViolation(
                    "Adapter {$registration->adapterClass} reports sourceKey '{$adapter->sourceKey()}' but is registered as '{$sourceKey}'."
                );
            }
            $this->adapters[$sourceKey] = $adapter;
        }
    }

    /** @return list<string> */
    public function registeredSourceKeys(): array
    {
        return array_keys($this->adapters);
    }

    public function get(string $sourceKey): ?ScientificSourceAdapterInterface
    {
        return $this->adapters[$sourceKey] ?? null;
    }

    /**
     * @param  list<string>  $sourceKeys
     * @return list<ScientificSourceAdapterInterface>
     */
    public function resolveMany(array $sourceKeys): array
    {
        $resolved = [];
        foreach ($sourceKeys as $sourceKey) {
            $adapter = $this->get($sourceKey);
            if ($adapter !== null) {
                $resolved[] = $adapter;
            }
        }

        return $resolved;
    }
}
