<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;

/**
 * Walk DISCOVERED → ACTIVATABLE using official FAOSTAT endpoints only.
 * Does not activate domains. Live /data uses verification fetch, not production allowlist.
 */
final class FaoStatDomainVerifier
{
    public function __construct(
        private FaoStatDeveloperPortalClient $client,
        private FaoStatDeveloperPortalResultNormalizer $normalizer,
        private FaoStatClaimSupportAssessor $claimSupport,
        private FaoStatDomainScopedCodeResolver $codes,
    ) {}

    /**
     * @param  array<string, string>|null  $liveProbe  Numeric FAOSTAT filters only (area/item/element/year).
     */
    public function verify(string $domain, ?array $liveProbe = null): FaoStatDomainVerificationReport
    {
        try {
            $domain = $this->client->assertInspectableDomain($domain);
        } catch (FaoStatPortalException $e) {
            return $this->failed($domain, FaoStatDomainStatus::NOT_VERIFIED, $e->category);
        }

        try {
            $discovered = FaoStatDomainCatalog::discoveredFromPayload($this->client->getGroupsAndDomains());
        } catch (FaoStatPortalException $e) {
            return $this->failed($domain, FaoStatDomainStatus::NOT_VERIFIED, $e->category);
        }

        if (! FaoStatDomainCatalog::isDiscovered($domain, $discovered)) {
            return $this->failed($domain, FaoStatDomainStatus::NOT_VERIFIED, 'domain_not_in_discovery');
        }

        try {
            $metadata = $this->client->getMetadata($domain);
        } catch (FaoStatPortalException $e) {
            return $this->report($domain, FaoStatDomainStatus::DISCOVERED, $e->category, true);
        }
        if (! is_array($metadata) || $metadata === []) {
            return $this->report($domain, FaoStatDomainStatus::DISCOVERED, 'metadata_empty', true);
        }

        try {
            $dimensionsPayload = $this->client->getDimensions($domain);
        } catch (FaoStatPortalException $e) {
            return $this->report($domain, FaoStatDomainStatus::METADATA_VERIFIED, $e->category, true, true);
        }
        $dimensionIds = $this->dimensionIds($dimensionsPayload);
        if ($dimensionIds === []) {
            return $this->report($domain, FaoStatDomainStatus::METADATA_VERIFIED, 'dimensions_empty', true, true);
        }

        $codeCounts = [];
        $official = [];
        foreach ($dimensionIds as $dimensionId) {
            if (strtolower($dimensionId) === 'areas') {
                $official['countries'] = true;

                continue;
            }
            $mapped = FaoStatOfficialCodeDimensions::normalize($dimensionId);
            if ($mapped === null) {
                continue;
            }
            $official[$mapped] = true;
        }

        if ($official === []) {
            return $this->report(
                $domain,
                FaoStatDomainStatus::DIMENSIONS_VERIFIED,
                'no_official_code_dimensions',
                true,
                true,
                true,
                $dimensionIds,
            );
        }

        foreach (array_keys($official) as $codeDimension) {
            try {
                $rows = $this->client->listCodes($codeDimension, $domain);
            } catch (FaoStatPortalException $e) {
                return $this->report(
                    $domain,
                    FaoStatDomainStatus::DIMENSIONS_VERIFIED,
                    $e->category,
                    true,
                    true,
                    true,
                    $dimensionIds,
                );
            }
            $codeCounts[$codeDimension] = count($rows);
        }

        $probe = $this->resolveProbe($domain, $liveProbe);
        if ($probe === null) {
            return $this->report(
                $domain,
                FaoStatDomainStatus::CODES_VERIFIED,
                'no_safe_live_probe',
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
            );
        }

        try {
            $result = $this->client->getVerificationData($domain, $probe);
        } catch (FaoStatPortalException $e) {
            return $this->report(
                $domain,
                FaoStatDomainStatus::CODES_VERIFIED,
                $e->category,
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
            );
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
        $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        if ($rows === []) {
            return $this->report(
                $domain,
                FaoStatDomainStatus::CODES_VERIFIED,
                FaoStatErrorCategory::EMPTY_RESULT,
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
                ['observation_count' => 0, 'http_status' => $result['status'] ?? null],
            );
        }

        $queryUrl = $this->queryUrl($domain, $probe);
        $queryElement = (string) ($probe['element'] ?? '');
        $observation = null;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $observation = $this->normalizer->normalize($row, $queryElement, $queryUrl);
            if ($observation !== null) {
                break;
            }
        }
        if ($observation === null || $observation->value === '') {
            return $this->report(
                $domain,
                FaoStatDomainStatus::CODES_VERIFIED,
                'observation_not_normalizable',
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
                ['observation_count' => count($rows)],
            );
        }

        $searchResult = $this->toSearchResult($observation);
        $plan = $this->quantitativePlan($observation);
        $support = $this->claimSupport->assess($plan, $searchResult);
        $supportState = (string) ($support['factors']['faostat_support_state'] ?? '');
        if ($supportState !== FaoStatSupportState::SUPPORTED
            || ($support['relationship'] ?? '') !== ClaimEvidenceRelationship::SUPPORTED) {
            return $this->report(
                $domain,
                FaoStatDomainStatus::LIVE_QUERY_VERIFIED,
                'claim_mapping_not_supported',
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
                [
                    'observation_count' => 1,
                    'value' => $observation->value,
                    'unit' => $observation->unit,
                    'year' => $observation->year,
                ],
                ['support_state' => $supportState, 'relationship' => $support['relationship'] ?? null],
                $observation->provenance,
            );
        }

        if ($observation->queryElementCode !== '' && $observation->responseElementCode !== ''
            && $observation->queryElementCode === $observation->responseElementCode
            && $queryElement !== '') {
            return $this->report(
                $domain,
                FaoStatDomainStatus::CLAIM_MAPPING_VERIFIED,
                'query_and_response_element_not_kept_separate',
                true,
                true,
                true,
                $dimensionIds,
                $codeCounts,
                ['observation_count' => 1],
                ['support_state' => $supportState],
                $observation->provenance,
            );
        }

        return new FaoStatDomainVerificationReport(
            domain: $domain,
            status: FaoStatDomainStatus::ACTIVATABLE,
            reason: 'verified',
            discovered: true,
            metadataVerified: true,
            dimensionsVerified: true,
            codesVerified: true,
            liveQueryVerified: true,
            claimMappingVerified: true,
            activatable: true,
            dimensionIds: $dimensionIds,
            codeCounts: $codeCounts,
            live: [
                'observation_count' => 1,
                'area' => $observation->area,
                'item' => $observation->item,
                'element' => $observation->element,
                'year' => $observation->year,
                'unit' => $observation->unit,
                'value' => $observation->value,
                'query_element_code' => $observation->queryElementCode,
                'response_element_code' => $observation->responseElementCode,
            ],
            claimMapping: [
                'support_state' => $supportState,
                'relationship' => $support['relationship'],
            ],
            provenance: $observation->provenance,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function dimensionIds(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        if (! is_array($data)) {
            return [];
        }
        $ids = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? $row['code'] ?? $row['dimension_id'] ?? $row['DimensionId'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, string>|null  $liveProbe
     * @return array<string, string>|null
     */
    private function resolveProbe(string $domain, ?array $liveProbe): ?array
    {
        if (is_array($liveProbe) && $liveProbe !== []) {
            $out = [];
            foreach (['area', 'item', 'element', 'year'] as $key) {
                $code = trim((string) ($liveProbe[$key] ?? ''));
                if ($code !== '' && preg_match('/^\d{1,8}$/', $code) === 1) {
                    $out[$key] = $code;
                }
            }

            return $out === [] ? null : $out;
        }

        if ($domain !== FaoStatDomainCatalog::QCL) {
            return null;
        }

        return [
            'area' => '106',
            'item' => '15',
            'element' => FaoStatQclDimensionResolver::QUERY_ELEMENT_PRODUCTION_QUANTITY,
            'year' => '2022',
        ];
    }

    /**
     * @param  array<string, string>  $probe
     */
    private function queryUrl(string $domain, array $probe): string
    {
        $base = FaoStatDeveloperPortalClient::normalizedBaseUrl();
        $lang = FaoStatDeveloperPortalClient::lang();
        $query = http_build_query($probe, '', '&', PHP_QUERY_RFC3986);

        return $base.'/'.$lang.'/data/'.$domain.'?'.$query;
    }

    private function toSearchResult(FaoStatObservation $observation): ScientificSearchResult
    {
        return new ScientificSearchResult(
            sourceKey: FaoStatDeveloperPortalAdapter::SOURCE_KEY,
            sourceIdentifier: implode('|', array_filter([
                $observation->domainCode,
                $observation->areaCode,
                $observation->itemCode,
                $observation->responseElementCode,
                $observation->year,
            ])),
            title: trim(implode(' — ', array_filter([
                $observation->item,
                $observation->element,
                $observation->area,
                $observation->year,
            ]))),
            authors: [],
            publicationYear: null,
            doi: null,
            canonicalUrl: $observation->provenance['query_url'] ?? null,
            abstract: $observation->value !== ''
                ? 'Value: '.$observation->value.($observation->unit !== '' ? ' '.$observation->unit : '')
                : null,
            journal: null,
            foundBySources: [FaoStatDeveloperPortalAdapter::SOURCE_KEY],
            relevanceMetadata: [
                'evidence_type' => FaoStatEvidenceType::DIRECT_STATISTICAL_EVIDENCE,
                'evidence_family' => 'official_statistics',
                'not_literature' => true,
                'query_element_code' => $observation->queryElementCode,
                'response_element_code' => $observation->responseElementCode,
            ],
            rawMetadata: [
                'faostat' => $observation->toArray(),
                'raw_observation' => $observation->raw,
                'provenance' => $observation->provenance,
            ],
        );
    }

    private function quantitativePlan(FaoStatObservation $observation): KnowledgeQueryPlan
    {
        $question = trim(sprintf(
            'What was %s production quantity in %s in %s?',
            $observation->item !== '' ? $observation->item : 'the item',
            $observation->area !== '' ? $observation->area : 'the area',
            $observation->year !== '' ? $observation->year : 'the year',
        ));
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agronomy',
            subject: ['type' => 'topic', 'value' => 'production statistics'],
            crop: $observation->item !== '' ? $observation->item : null,
            cropId: null,
            scientificName: null,
            topic: 'production statistics',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: [
                'question_type' => 'statistical',
                'domain' => $observation->domainCode !== '' ? $observation->domainCode : FaoStatDomainCatalog::QCL,
                'area' => $observation->areaCode,
                'item' => $observation->itemCode,
                'element' => $observation->queryElementCode,
                'year' => $observation->year,
            ],
            location: $observation->area !== '' ? $observation->area : null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'agricultural_economics',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'agricultural_economics',
            agriculturalDomain: 'agronomy',
            subjectEntity: ['type' => 'topic', 'value' => 'production statistics'],
            topics: ['production statistics'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }

    /**
     * @param  list<string>  $dimensionIds
     * @param  array<string, int>  $codeCounts
     * @param  array<string, mixed>  $live
     * @param  array<string, mixed>  $claimMapping
     * @param  array<string, mixed>  $provenance
     */
    private function report(
        string $domain,
        string $status,
        string $reason,
        bool $discovered = false,
        bool $metadata = false,
        bool $dimensions = false,
        array $dimensionIds = [],
        array $codeCounts = [],
        array $live = [],
        array $claimMapping = [],
        array $provenance = [],
    ): FaoStatDomainVerificationReport {
        $codes = $status === FaoStatDomainStatus::CODES_VERIFIED
            || in_array($status, [
                FaoStatDomainStatus::LIVE_QUERY_VERIFIED,
                FaoStatDomainStatus::CLAIM_MAPPING_VERIFIED,
                FaoStatDomainStatus::ACTIVATABLE,
            ], true);

        return new FaoStatDomainVerificationReport(
            domain: strtoupper(trim($domain)),
            status: $status,
            reason: $reason,
            discovered: $discovered,
            metadataVerified: $metadata,
            dimensionsVerified: $dimensions,
            codesVerified: $codes && $codeCounts !== [],
            liveQueryVerified: $status === FaoStatDomainStatus::LIVE_QUERY_VERIFIED
                || $status === FaoStatDomainStatus::CLAIM_MAPPING_VERIFIED
                || $status === FaoStatDomainStatus::ACTIVATABLE,
            claimMappingVerified: $status === FaoStatDomainStatus::CLAIM_MAPPING_VERIFIED
                || $status === FaoStatDomainStatus::ACTIVATABLE,
            activatable: false,
            dimensionIds: $dimensionIds,
            codeCounts: $codeCounts,
            live: $live,
            claimMapping: $claimMapping,
            provenance: $provenance,
        );
    }

    private function failed(string $domain, string $status, string $reason): FaoStatDomainVerificationReport
    {
        return $this->report($domain, $status, $reason);
    }
}
