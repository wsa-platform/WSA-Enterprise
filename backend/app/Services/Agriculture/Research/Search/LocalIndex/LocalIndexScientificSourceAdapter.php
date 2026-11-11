<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

use App\Contracts\ScientificSourceAdapterV2Interface;
use App\Services\Agriculture\Research\Search\Contract\DelegatesSearchRequestToSearch;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceAdapterDescriptor;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceRetryPolicy;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyAdapter;
use App\Services\Agriculture\Research\Search\Family\ScientificSourceFamilyProfile;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceAccessFamily;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Searches the local index of a harvested or bulk-loaded source (ADR-023 §8.40, §8.41).
 *
 * Serves OAI-PMH, bulk and static dataset sources, which have no query endpoint of their own.
 * It receives the compiled query like any remote adapter and returns stored records only.
 * Before each search the index is brought to the generation committed in the record store,
 * rebuilding it from the stored ACTIVE records when another process committed since. A source
 * with no committed generation is UNAVAILABLE and a store fault is FAILED, never EMPTY, so
 * absence of data never reads as absence of evidence. Not registered for any production source.
 */
final class LocalIndexScientificSourceAdapter implements ScientificSourceAdapterV2Interface, ScientificSourceFamilyAdapter
{
    use DelegatesSearchRequestToSearch;

    public function __construct(
        private readonly ScientificSourceFamilyProfile $profile,
        private readonly LocalIndexSearchEngine $index,
        private readonly ScientificRecordStore $store,
    ) {
        if (! self::servesAccessFamily($profile->accessFamily())) {
            throw new LocalIndexInvariantViolation("The local index cannot serve '{$profile->sourceKey()}' of family '{$profile->accessFamily()->value}'.");
        }
    }

    public static function servesAccessFamily(ScientificSourceAccessFamily $family): bool
    {
        return in_array($family, [
            ScientificSourceAccessFamily::OAI_PMH,
            ScientificSourceAccessFamily::BULK_DATASET,
            ScientificSourceAccessFamily::STATIC_DATASET,
        ], true);
    }

    public function profile(): ScientificSourceFamilyProfile
    {
        return $this->profile;
    }

    public function sourceKey(): string
    {
        return $this->profile->sourceKey();
    }

    public function displayName(): string
    {
        return $this->profile->displayName();
    }

    public function descriptor(): ScientificSourceAdapterDescriptor
    {
        return new ScientificSourceAdapterDescriptor(
            sourceKey: $this->profile->sourceKey(),
            licensing: $this->profile->licensing(),
            retryPolicy: ScientificSourceRetryPolicy::none(),
        );
    }

    /** @param  array<string, mixed>  $options  unused: the index has no provider filters */
    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        if (trim($query) === '') {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_EMPTY, error: 'empty_query');
        }
        if (! $this->profile->licensing()->permits(ScientificSourceUsage::RETRIEVAL)) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, error: 'licence_retrieval_not_permitted');
        }
        $startedNs = hrtime(true);
        $key = $this->profile->sourceKey();
        try {
            $committed = $this->store->generation($key);
            if ($committed !== 0 && (! $this->index->hasSource($key) || $this->index->generation($key) !== $committed || $this->index->documentCount($key) === 0)) {
                // Stream ACTIVE rows — never materialize the full corpus for rebuild.
                $this->index->rebuild($key, $this->store->iterateActive($key), $committed);
            }
        } catch (LocalStoreFailure $failure) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_FAILED, error: $failure->reason, startedNs: $startedNs);
        }
        if ($committed === 0) {
            return $this->outcome(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, error: 'local_index_not_built');
        }

        $hits = $this->index->search($key, $query, max(1, $limit));
        $results = [];
        foreach ($hits as $hit) {
            $results[] = $hit['record']->result;
        }

        return $this->outcome(
            $results === [] ? ScientificSourceSearchOutcome::STATUS_EMPTY : ScientificSourceSearchOutcome::STATUS_SUCCESS,
            $results,
            startedNs: $startedNs,
        );
    }

    /** @param  list<ScientificSearchResult>  $results */
    private function outcome(string $status, array $results = [], ?string $error = null, ?int $startedNs = null): ScientificSourceSearchOutcome
    {
        $observability = [
            'provider' => $this->profile->sourceKey(),
            'access_family' => $this->profile->accessFamily()->value,
            'index_version' => $this->index->version(),
            'index_generation' => $this->index->generation($this->profile->sourceKey()),
        ];
        if ($startedNs !== null) {
            $observability['latency_ms'] = max(0, (int) ((hrtime(true) - $startedNs) / 1_000_000));
        }

        return new ScientificSourceSearchOutcome(
            sourceKey: $this->profile->sourceKey(),
            status: $status,
            results: $results,
            error: $error,
            observability: $observability,
        );
    }
}
