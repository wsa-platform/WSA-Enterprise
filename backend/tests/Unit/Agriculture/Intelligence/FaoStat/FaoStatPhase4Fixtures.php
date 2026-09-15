<?php

namespace Tests\Unit\Agriculture\Intelligence\FaoStat;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use Illuminate\Support\Facades\Http;

trait FaoStatPhase4Fixtures
{
    /** @return array<string, mixed> */
    protected function italyWheatRow(): array
    {
        return [
            'Domain Code' => 'QCL',
            'Domain' => 'Crops and livestock products',
            'Area Code' => '106',
            'Area' => 'Italy',
            'Element Code' => '5510',
            'Element' => 'Production',
            'Item Code' => '15',
            'Item' => 'Wheat',
            'Year Code' => '2022',
            'Year' => '2022',
            'Unit' => 't',
            'Value' => '6609520',
            'Flag' => 'A',
            'Flag Description' => 'Official value',
            'Note' => '',
        ];
    }

    /** @return array<string, mixed> */
    protected function loginPayload(): array
    {
        return [
            'AuthenticationResult' => [
                'AccessToken' => 'test-access-token',
                'IdToken' => 'test-id-token',
                'RefreshToken' => 'test-refresh-token',
                'ExpiresIn' => 3600,
                'TokenType' => 'Bearer',
            ],
            'ChallengeParameters' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $routes
     * @return array<string, mixed>
     */
    protected function authAnd(array $routes): array
    {
        return array_merge([
            'faostatservices.fao.org/api/v1/auth/login' => Http::response($this->loginPayload(), 200),
        ], $routes);
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    protected function phase4Plan(
        string $question,
        string $intent = 'agricultural_economics',
        array $constraints = [],
        ?string $crop = null,
        ?string $location = null,
    ): KnowledgeQueryPlan {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: $question,
            language: 'en',
            agriculturalDomain: 'agriculture',
            subject: ['type' => 'topic', 'value' => 'production'],
            crop: $crop,
            cropId: null,
            scientificName: null,
            topic: 'production statistics',
            subtopic: null,
            requestedInformation: ['evidence'],
            constraints: array_merge([
                'question_type' => 'statistical',
            ], $constraints),
            location: $location,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: $intent,
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: $intent,
            agriculturalDomain: 'agriculture',
            subjectEntity: ['type' => 'topic', 'value' => 'production'],
            topics: ['production statistics'],
            subtopics: [],
            requestedInformation: ['evidence'],
            evidenceRequirements: ['official_statistics'],
            sourcePriorities: ['fao_stat', 'openalex', 'crossref', 'semantic_scholar'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }

    protected function faostatResult(?array $observation = null, ?int $publicationYear = null): ScientificSearchResult
    {
        $row = $observation ?? [
            'domain_code' => 'QCL',
            'domain' => 'Crops and livestock products',
            'area_code' => '106',
            'area' => 'Italy',
            'item_code' => '15',
            'item' => 'Wheat',
            'query_element_code' => '2510',
            'response_element_code' => '5510',
            'element_code' => '5510',
            'element' => 'Production',
            'year_code' => '2022',
            'year' => '2022',
            'unit' => 't',
            'value' => '6609520',
            'flag' => 'A',
            'flag_description' => 'Official value',
            'note' => '',
            'provenance' => [
                'source' => 'FAOSTAT',
                'provider' => 'fao_stat',
                'host' => 'faostatservices.fao.org',
                'domain_code' => 'QCL',
                'query_url' => 'https://faostatservices.fao.org/api/v1/en/data/QCL?area=106&item=15&element=2510&year=2022',
            ],
        ];

        return new ScientificSearchResult(
            sourceKey: 'fao_stat',
            sourceIdentifier: 'QCL|106|15|5510|2022',
            title: 'Wheat — Production — Italy — 2022',
            authors: [],
            publicationYear: $publicationYear,
            doi: null,
            canonicalUrl: $row['provenance']['query_url'] ?? null,
            abstract: 'Value: 6609520 t',
            journal: null,
            foundBySources: ['fao_stat'],
            relevanceMetadata: [
                'evidence_type' => 'DIRECT_STATISTICAL_EVIDENCE',
                'evidence_family' => 'official_statistics',
                'not_literature' => true,
                'query_element_code' => $row['query_element_code'] ?? '2510',
                'response_element_code' => $row['response_element_code'] ?? '5510',
            ],
            rawMetadata: [
                'faostat' => $row,
                'provenance' => $row['provenance'] ?? [],
            ],
        );
    }

    /**
     * @param  list<array{code: string, label: string}>  $rows
     * @return array{metadata: array<string, mixed>, data: list<array{code: string, label: string}>}
     */
    protected function codeListPayload(array $rows): array
    {
        return ['metadata' => [], 'data' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    protected function discoveryPayload(): array
    {
        return [
            'metadata' => [],
            'data' => [
                ['domain_code' => 'QCL', 'domain_name' => 'Crops and livestock products', 'group_code' => 'Q'],
                ['domain_code' => 'RFN', 'domain_name' => 'Fertilizers by Nutrient', 'group_code' => 'RB'],
                ['domain_code' => 'RP', 'domain_name' => 'Pesticides Use', 'group_code' => 'RP'],
                ['domain_code' => 'RL', 'domain_name' => 'Land Use', 'group_code' => 'RL'],
                ['domain_code' => 'ESB', 'domain_name' => 'Cropland Nutrient Balance', 'group_code' => 'E'],
                ['domain_code' => 'EMN', 'domain_name' => 'Livestock Manure', 'group_code' => 'E'],
                ['domain_code' => 'ET', 'domain_name' => 'Temperature change on land', 'group_code' => 'ET'],
                ['domain_code' => 'FBS', 'domain_name' => 'Food Balances (2010-)', 'group_code' => 'FB'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function qclVerificationRoutes(): array
    {
        return $this->authAnd([
            'faostatservices.fao.org/api/v1/en/groupsanddomains' => Http::response($this->discoveryPayload(), 200),
            'faostatservices.fao.org/api/v1/en/metadata/QCL' => Http::response(['metadata' => ['domain' => 'QCL'], 'data' => [['item' => 'ok']]], 200),
            'faostatservices.fao.org/api/v1/en/dimensions/QCL*' => Http::response([
                'data' => [
                    ['id' => 'countries', 'label' => 'Area'],
                    ['id' => 'items', 'label' => 'Item'],
                    ['id' => 'elements', 'label' => 'Element'],
                    ['id' => 'years', 'label' => 'Year'],
                ],
            ], 200),
            'faostatservices.fao.org/api/v1/en/codes/countries/QCL*' => Http::response($this->codeListPayload([
                ['code' => '106', 'label' => 'Italy'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/items/QCL*' => Http::response($this->codeListPayload([
                ['code' => '15', 'label' => 'Wheat'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/elements/QCL*' => Http::response($this->codeListPayload([
                ['code' => '2510', 'label' => 'Production Quantity'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/codes/years/QCL*' => Http::response($this->codeListPayload([
                ['code' => '2022', 'label' => '2022'],
            ]), 200),
            'faostatservices.fao.org/api/v1/en/data/QCL*' => Http::response([
                'metadata' => ['output_type' => 'OBJECTS'],
                'data' => [$this->italyWheatRow()],
            ], 200),
        ]);
    }
}
