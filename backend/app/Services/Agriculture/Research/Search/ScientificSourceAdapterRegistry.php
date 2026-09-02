<?php

namespace App\Services\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;

/**
 * Registry of Stage 3 scientific source adapters.
 */
class ScientificSourceAdapterRegistry
{
    /** @var array<string, ScientificSourceAdapterInterface> */
    private array $adapters;

    public function __construct(
        OpenAlexScientificSourceAdapter $openAlex,
        CrossRefScientificSourceAdapter $crossRef,
    ) {
        $this->adapters = [
            $openAlex->sourceKey() => $openAlex,
            $crossRef->sourceKey() => $crossRef,
        ];
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
