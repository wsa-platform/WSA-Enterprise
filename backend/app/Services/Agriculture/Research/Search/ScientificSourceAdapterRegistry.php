<?php

namespace App\Services\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatDeveloperPortalAdapter;
use App\Services\Agriculture\Research\Search\Adapters\ConsensusScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\CrossRefScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\OpenAlexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\Adapters\SemanticScholarScientificSourceAdapter;

/**
 * Registry of Stage 3 scientific source adapters.
 *
 * Internet-First path selects OpenAlex + Crossref + Semantic Scholar (+ FAOSTAT Portal when enabled).
 * Consensus remains registered for optional/legacy use but is not required.
 * fao_stat is exclusively FaoStatDeveloperPortalAdapter.
 */
class ScientificSourceAdapterRegistry
{
    /** @var array<string, ScientificSourceAdapterInterface> */
    private array $adapters;

    public function __construct(
        OpenAlexScientificSourceAdapter $openAlex,
        CrossRefScientificSourceAdapter $crossRef,
        SemanticScholarScientificSourceAdapter $semanticScholar,
        ConsensusScientificSourceAdapter $consensus,
        FaoStatDeveloperPortalAdapter $portalFaoStat,
    ) {
        $this->adapters = [
            $openAlex->sourceKey() => $openAlex,
            $crossRef->sourceKey() => $crossRef,
            $semanticScholar->sourceKey() => $semanticScholar,
            $consensus->sourceKey() => $consensus,
            $portalFaoStat->sourceKey() => $portalFaoStat,
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
