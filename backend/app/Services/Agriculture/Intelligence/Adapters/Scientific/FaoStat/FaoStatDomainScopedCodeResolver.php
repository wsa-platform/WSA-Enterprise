<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Domain-scoped label resolution. Italy→106 is QCL-only unless another domain
 * uniquely resolves the same label from its own code list.
 */
final class FaoStatDomainScopedCodeResolver
{
    public function __construct(
        private FaoStatDeveloperPortalClient $client,
    ) {}

    /**
     * @return array{status: 'resolved'|'unresolved'|'ambiguous', code: ?string, domain: string, dimension: string}
     */
    public function resolve(string $domain, string $dimension, string $label): array
    {
        $domain = $this->client->assertInspectableDomain($domain);
        $dimension = FaoStatOfficialCodeDimensions::assertOfficial($dimension);

        if ($domain === FaoStatDomainCatalog::QCL) {
            $mapped = $this->qclVerifiedMap($dimension, $label);
            if ($mapped !== null) {
                return [
                    'status' => 'resolved',
                    'code' => $mapped,
                    'domain' => $domain,
                    'dimension' => $dimension,
                ];
            }

            // Verified QCL maps only during search resolution. Do not probe the live
            // portal for unknown labels — that would invent codes via HTTP and break
            // the incomplete-filter / no-unsafe-invention contract.
            return [
                'status' => 'unresolved',
                'code' => null,
                'domain' => $domain,
                'dimension' => $dimension,
            ];
        }

        $got = $this->client->resolveUniqueCode($dimension, $domain, $label);

        return [
            'status' => $got['status'],
            'code' => $got['code'],
            'domain' => $domain,
            'dimension' => $dimension,
        ];
    }

    private function qclVerifiedMap(string $dimension, string $label): ?string
    {
        $blob = mb_strtolower(trim($label));
        if ($dimension === 'countries') {
            return FaoStatQclDimensionResolver::areaCode($label, $blob);
        }
        if ($dimension === 'items') {
            return FaoStatQclDimensionResolver::itemCode($label, $blob);
        }
        if ($dimension === 'elements') {
            if ($blob === 'production quantity' || $blob === 'production') {
                return FaoStatQclElementSemantics::QUERY_PRODUCTION_QUANTITY;
            }
            if ($blob === 'yield' || $blob === 'crop yield') {
                return FaoStatQclElementSemantics::QUERY_YIELD;
            }
            if ($blob === 'area harvested' || $blob === 'harvested area') {
                return FaoStatQclElementSemantics::QUERY_AREA_HARVESTED;
            }
        }
        if ($dimension === 'years' && preg_match('/^(?:19|20)\d{2}$/', trim($label)) === 1) {
            return trim($label);
        }

        return null;
    }
}
