<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Synthesis\ResearchAnswerCitation;

/**
 * Distinguishes discovery-provider multiplicity from independent document identity.
 *
 * Same DOI discovered by OpenAlex + Crossref is one independent document with two
 * contributing providers. Provider count must not be treated as scientific evidence count.
 */
final class EvidenceIndependenceAssessor
{
    /**
     * Canonical document key for independence. Null means identity is too weak to merge.
     */
    public function documentIdentityKey(?string $doi, ?string $canonicalUrl, ?string $sourceKey, ?string $sourceIdentifier): ?string
    {
        if ($doi !== null && trim($doi) !== '') {
            $normalized = strtolower(trim($doi));
            if (preg_match('/^10\.\d{4,9}\/\S+$/', $normalized) === 1) {
                return 'doi:'.$normalized;
            }
            // Invalid DOI must not invent document identity.
            return null;
        }

        if ($canonicalUrl !== null && trim($canonicalUrl) !== '') {
            return 'url:'.strtolower(rtrim(trim($canonicalUrl), '/'));
        }

        if ($sourceKey !== null && $sourceKey !== '' && $sourceIdentifier !== null && trim($sourceIdentifier) !== '') {
            return 'id:'.$sourceKey.':'.strtolower(trim($sourceIdentifier));
        }

        return null;
    }

    public function documentIdentityKeyFromSearchResult(ScientificSearchResult $result): ?string
    {
        return $this->documentIdentityKey(
            $result->doi,
            $result->canonicalUrl,
            $result->sourceKey,
            $result->sourceIdentifier,
        );
    }

    public function documentIdentityKeyFromEvidence(ScientificEvidenceItem $item): ?string
    {
        $url = $item->url;
        $sourceIdentifier = is_string($item->sourceAttribution['source_identifier'] ?? null)
            ? (string) $item->sourceAttribution['source_identifier']
            : $item->sourceId;

        return $this->documentIdentityKey($item->doi, $url, $item->sourceKey, $sourceIdentifier);
    }

    /**
     * @param  list<ResearchAnswerCitation>  $citations
     * @return array{
     *   independent_provider_count: int,
     *   providers: list<string>,
     *   by_provider: array<string, int>,
     *   independent_document_count: int,
     *   document_keys: list<string>,
     *   unresolved_document_identity_count: int,
     *   same_document_multi_provider_count: int
     * }
     */
    public function assessCitations(array $citations): array
    {
        $byProvider = [];
        $documents = [];
        $unresolved = 0;
        $multiProviderDocs = 0;

        foreach ($citations as $citation) {
            if (! $citation instanceof ResearchAnswerCitation) {
                continue;
            }
            $providers = $citation->contributingProviders !== []
                ? $citation->contributingProviders
                : array_values(array_filter([(string) ($citation->sourceKey ?? $citation->sourceType ?? '')]));
            foreach ($providers as $provider) {
                if ($provider === '') {
                    continue;
                }
                $byProvider[$provider] = ($byProvider[$provider] ?? 0) + 1;
            }

            $docKey = $this->documentIdentityKey(
                $citation->doi,
                $citation->url,
                $citation->sourceKey,
                $citation->sourceId,
            );
            if ($docKey === null) {
                // Unresolved identity: each citation is counted separately without merge.
                $unresolved++;
                $documents['unresolved:'.$citation->evidenceId] = true;

                continue;
            }
            if (! isset($documents[$docKey]) && count(array_unique(array_filter($providers))) > 1) {
                $multiProviderDocs++;
            }
            $documents[$docKey] = true;
        }

        ksort($byProvider);

        return [
            'independent_provider_count' => count($byProvider),
            'providers' => array_keys($byProvider),
            'by_provider' => $byProvider,
            'independent_document_count' => count($documents),
            'document_keys' => array_keys($documents),
            'unresolved_document_identity_count' => $unresolved,
            'same_document_multi_provider_count' => $multiProviderDocs,
        ];
    }
}
