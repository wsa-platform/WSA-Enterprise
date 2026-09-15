<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Intelligence\Contracts\SourceRole;
use App\Services\Agriculture\Intelligence\DTO\CanonicalAgriculturalResult;
use App\Services\Agriculture\Intelligence\DTO\FusedEvidenceBundle;
use App\Services\Agriculture\Intelligence\Fusion\EvidenceFusionService;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Claim-level provider mapping. FAOSTAT stays in stats; scholarly stays scientificEvidence.
 * Does not replace EvidenceFusionService and does not call ClaimEvidenceMatcher.
 */
final class FaoStatClaimEvidenceFusion
{
    public function __construct(
        private FaoStatClaimSupportAssessor $claimSupport,
        private EvidenceFusionService $fusion,
    ) {}

    /**
     * @param  list<array{id: string, plan: KnowledgeQueryPlan}>  $claims
     * @param  list<ScientificSearchResult>  $results
     * @return list<array<string, mixed>>
     */
    public function mapClaims(array $claims, array $results): array
    {
        $mapped = [];
        foreach ($claims as $claim) {
            $id = (string) ($claim['id'] ?? '');
            $plan = $claim['plan'] ?? null;
            if ($id === '' || ! $plan instanceof KnowledgeQueryPlan) {
                continue;
            }
            $providers = [];
            $states = [];
            foreach ($results as $result) {
                if (! $result instanceof ScientificSearchResult) {
                    continue;
                }
                if ($result->sourceKey === FaoStatDeveloperPortalAdapter::SOURCE_KEY) {
                    $support = $this->claimSupport->assess($plan, $result);
                    $state = (string) ($support['factors']['faostat_support_state'] ?? FaoStatSupportState::UNVERIFIED);
                    $states[] = $state;
                    if (in_array($state, [FaoStatSupportState::SUPPORTED, FaoStatSupportState::PARTIALLY_SUPPORTED], true)) {
                        $providers[] = 'fao_stat';
                    }

                    continue;
                }
                $providers[] = $result->sourceKey;
                $states[] = 'scholarly_candidate';
            }
            $mapped[] = [
                'claim_id' => $id,
                'providers' => array_values(array_unique($providers)),
                'faostat_support_states' => $states,
                'insufficient' => $providers === [],
            ];
        }

        return $mapped;
    }

    /**
     * @param  list<CanonicalAgriculturalResult>  $results
     */
    public function fuse(array $results): FusedEvidenceBundle
    {
        return $this->fusion->fuse($results);
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    public function faostatStatsResult(array $observation, string $url = ''): CanonicalAgriculturalResult
    {
        return new CanonicalAgriculturalResult(
            providerId: FaoStatDeveloperPortalAdapter::SOURCE_KEY,
            status: 'success',
            stats: [[
                'title' => (string) ($observation['item'] ?? 'FAOSTAT observation'),
                'provider_id' => FaoStatDeveloperPortalAdapter::SOURCE_KEY,
                'source_role' => SourceRole::OFFICIAL_AGRICULTURAL_DATA,
                'evidence_family' => 'official',
                'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                'not_literature' => true,
                'value' => $observation['value'] ?? null,
                'unit' => $observation['unit'] ?? null,
                'year' => $observation['year'] ?? null,
                'url' => $url,
                'query_element_code' => $observation['query_element_code'] ?? null,
                'response_element_code' => $observation['response_element_code'] ?? null,
            ]],
        );
    }

    /**
     * @param  array<string, mixed>  $paper
     */
    public function scholarlyResult(string $providerId, array $paper): CanonicalAgriculturalResult
    {
        return new CanonicalAgriculturalResult(
            providerId: $providerId,
            status: 'success',
            scientificEvidence: [[
                'title' => (string) ($paper['title'] ?? ''),
                'doi' => $paper['doi'] ?? null,
                'publication_year' => $paper['publication_year'] ?? null,
                'provider_id' => $providerId,
                'source_role' => SourceRole::SCIENTIFIC_EVIDENCE,
                'evidence_family' => 'scientific',
            ]],
        );
    }
}
