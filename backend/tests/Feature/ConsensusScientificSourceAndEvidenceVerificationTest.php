<?php

namespace Tests\Feature;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\QueryUnderstandingService;
use App\Services\Agriculture\Research\ResearchPlanner;
use App\Services\Agriculture\Research\Search\Adapters\ConsensusScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificResultDeduplicator;
use App\Services\Agriculture\Research\Search\ScientificResultNormalizer;
use App\Services\Agriculture\Research\Search\ScientificSearchQueryBuilder;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;
use App\Services\Agriculture\Research\Search\ScientificSourceAdapterRegistry;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use App\Services\Agriculture\Research\Synthesis\AnswerComposer;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\EvidenceValidationStatus;
use App\Services\Agriculture\Research\Validation\EvidenceVerificationLayer;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsensusScientificSourceAndEvidenceVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function consensusWork(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Tomato heat stress physiology in open field systems',
            'abstract' => 'Tomato plants grown in open-field conditions showed reduced growth under heat stress above 35 C.',
            'authors' => ['A Researcher', 'B Scientist'],
            'doi' => '10.1000/consensus-tomato-heat',
            'url' => 'https://doi.org/10.1000/consensus-tomato-heat',
            'journal_name' => 'Journal of Agronomy',
            'publish_year' => 2022,
            'semantic_score' => 0.91,
            'citation_count' => 12,
            'countries_of_study' => ['eg'],
            'publisher_name' => 'Elsevier India',
        ], $overrides);
    }

    public function test_missing_api_key_returns_unavailable_without_http(): void
    {
        Config::set('wsa.consensus_api_key', null);
        Http::fake();

        $outcome = app(ConsensusScientificSourceAdapter::class)->search('tomato irrigation', 5);

        $this->assertSame('consensus', $outcome->sourceKey);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $outcome->status);
        $this->assertSame('missing_api_key', $outcome->error);
        Http::assertNothingSent();
    }

    public function test_request_sends_x_api_key_domain_country_and_semantic_score(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake([
            'api.consensus.app/v1/search*' => Http::response(['results' => [$this->consensusWork()]], 200),
        ]);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato cultivation in Egypt',
        ]);
        $options = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);

        $this->assertSame('agri', $options['domain'] ?? null);
        $this->assertSame('eg', $options['country'] ?? null);
        $this->assertTrue($options['include_semantic_score']);

        $outcome = app(ConsensusScientificSourceAdapter::class)->search(
            'Solanum lycopersicum cultivation Egypt',
            5,
            $options,
        );

        $this->assertSame(ScientificSourceSearchOutcome::STATUS_SUCCESS, $outcome->status);
        Http::assertSent(function ($request) {
            $this->assertSame('test-consensus-key-not-real', $request->header('x-api-key')[0] ?? null);
            $this->assertStringContainsString('api.consensus.app/v1/search', $request->url());
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return ($query['domain'] ?? null) === 'agri'
                && ($query['country'] ?? null) === 'eg'
                && (($query['include_semantic_score'] ?? null) === '1'
                    || ($query['include_semantic_score'] ?? null) === 'true'
                    || ($query['include_semantic_score'] ?? null) === true);
        });
    }

    public function test_country_filter_not_applied_without_asked_location(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato drip irrigation scheduling',
        ]);
        $options = app(ScientificSearchQueryBuilder::class)->buildConsensusRequestOptions($plan);

        $this->assertSame('agri', $options['domain'] ?? null);
        $this->assertArrayNotHasKey('country', $options);
    }

    public function test_normalizer_maps_semantic_score_and_allows_missing_doi_abstract(): void
    {
        $normalizer = app(ScientificResultNormalizer::class);

        $full = $normalizer->fromConsensusWork($this->consensusWork());
        $this->assertNotNull($full);
        $this->assertSame('consensus', $full->sourceKey);
        $this->assertSame('10.1000/consensus-tomato-heat', $full->doi);
        $this->assertSame(0.91, $full->relevanceMetadata['semantic_score'] ?? null);
        $this->assertSame(['eg'], $full->relevanceMetadata['countries_of_study'] ?? null);

        $sparse = $normalizer->fromConsensusWork([
            'title' => 'Sparse consensus paper without doi',
            'authors' => ['Only Author'],
            'publish_year' => 2020,
        ]);
        $this->assertNotNull($sparse);
        $this->assertNull($sparse->doi);
        $this->assertNull($sparse->abstract);
        $this->assertStringStartsWith('consensus:', (string) $sparse->sourceIdentifier);
    }

    public function test_deduplicator_merges_consensus_doi_with_openalex(): void
    {
        $doi = '10.1000/shared-doi';
        $results = app(ScientificResultDeduplicator::class)->deduplicate([
            new ScientificSearchResult(
                'openalex',
                'W1',
                'Shared title',
                ['A'],
                2021,
                $doi,
                'https://doi.org/'.$doi,
                'Abstract A',
                'J',
                ['openalex'],
            ),
            new ScientificSearchResult(
                'consensus',
                $doi,
                'Shared title',
                ['B'],
                2021,
                $doi,
                'https://doi.org/'.$doi,
                'Abstract B',
                'J',
                ['consensus'],
                ['semantic_score' => 0.8],
            ),
        ]);

        $this->assertCount(1, $results);
        $this->assertEqualsCanonicalizing(['openalex', 'consensus'], $results[0]->foundBySources);
    }

    public function test_registry_includes_consensus(): void
    {
        $keys = app(ScientificSourceAdapterRegistry::class)->registeredSourceKeys();
        $this->assertContains('consensus', $keys);
        $this->assertContains('openalex', $keys);
        $this->assertContains('crossref', $keys);
    }

    public function test_http_401_is_provider_failure(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake(['api.consensus.app/v1/search*' => Http::response(['error' => 'unauthorized'], 401)]);

        $unauthorized = app(ConsensusScientificSourceAdapter::class)->search('query', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $unauthorized->status);
        $this->assertSame('provider_auth_or_billing', $unauthorized->error);
        $this->assertSame(401, $unauthorized->httpStatus);
    }

    public function test_http_429_is_unavailable_rate_limited(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake(['api.consensus.app/v1/search*' => Http::response(['error' => 'rate'], 429)]);

        $rateLimited = app(ConsensusScientificSourceAdapter::class)->search('query', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_UNAVAILABLE, $rateLimited->status);
        $this->assertSame('rate_limited', $rateLimited->error);
        $this->assertSame(429, $rateLimited->httpStatus);
    }

    public function test_http_5xx_is_provider_failure(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake(['api.consensus.app/v1/search*' => Http::response(['error' => 'boom'], 503)]);

        $serverError = app(ConsensusScientificSourceAdapter::class)->search('query', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $serverError->status);
        $this->assertSame('http_5xx', $serverError->error);
        $this->assertSame(503, $serverError->httpStatus);
    }

    public function test_http_timeout_is_provider_failure(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $timeout = app(ConsensusScientificSourceAdapter::class)->search('query', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $timeout->status);
        $this->assertSame('timeout', $timeout->error);
    }

    public function test_http_402_is_provider_failure(): void
    {
        Config::set('wsa.consensus_api_key', 'test-consensus-key-not-real');
        Http::fake(['api.consensus.app/v1/search*' => Http::response(['error' => 'payment'], 402)]);

        $billing = app(ConsensusScientificSourceAdapter::class)->search('query', 3);
        $this->assertSame(ScientificSourceSearchOutcome::STATUS_FAILED, $billing->status);
        $this->assertSame('provider_auth_or_billing', $billing->error);
        $this->assertSame(402, $billing->httpStatus);
    }

    public function test_live_consensus_skipped_without_env_key(): void
    {
        $envKey = trim((string) (getenv('CONSENSUS_API_KEY') ?: env('CONSENSUS_API_KEY', '')));
        if ($envKey === '') {
            $this->markTestSkipped('CONSENSUS_API_KEY not present — live Consensus call skipped');
        }

        Config::set('wsa.consensus_api_key', $envKey);
        Http::preventStrayRequests(false);

        $outcome = app(ConsensusScientificSourceAdapter::class)->search(
            'tomato irrigation agriculture',
            3,
            ['domain' => 'agri', 'include_semantic_score' => true],
        );

        $this->assertContains($outcome->status, [
            ScientificSourceSearchOutcome::STATUS_SUCCESS,
            ScientificSourceSearchOutcome::STATUS_EMPTY,
            ScientificSourceSearchOutcome::STATUS_FAILED,
            ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
        ]);
        // Never assert or print the key itself.
        $this->assertNotSame('', $outcome->sourceKey);
    }

    public function test_verification_geographic_mismatch_vs_publisher_india_egypt_study_ok(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'land types in Egypt',
        ]);
        $layer = app(EvidenceVerificationLayer::class);

        $mismatch = new ScientificSearchResult(
            'consensus',
            'c1',
            'Soil classification study in India',
            ['A'],
            2020,
            '10.1000/india-soil',
            null,
            'A soil classification survey conducted in India across arid zones.',
            'J',
            ['consensus'],
            ['countries_of_study' => ['in'], 'publisher_name' => 'Springer'],
            ['consensus' => [
                'countries_of_study' => ['in'],
                'publisher_name' => 'Springer',
                'title' => 'Soil classification study in India',
                'abstract' => 'A soil classification survey conducted in India across arid zones.',
            ]],
        );
        $mismatchAssessment = $layer->assess($plan, $mismatch);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH, $mismatchAssessment['directness']);
        $this->assertSame(EvidenceVerificationLayer::LABEL_GEOGRAPHIC_MISMATCH, $mismatchAssessment['verification_label']);

        $publisherIndiaEgyptStudy = new ScientificSearchResult(
            'consensus',
            'c2',
            'Egyptian soil land types survey',
            ['A'],
            2021,
            '10.1000/egypt-soil',
            null,
            'Land classification of Egyptian soils under arid climates.',
            'J',
            ['consensus'],
            [
                'countries_of_study' => ['eg'],
                'publisher_name' => 'Elsevier India',
            ],
            ['consensus' => [
                'countries_of_study' => ['eg'],
                'publisher_name' => 'Elsevier India',
                'title' => 'Egyptian soil land types survey',
                'abstract' => 'Land classification of Egyptian soils under arid climates.',
            ]],
        );
        $ok = $layer->assess($plan, $publisherIndiaEgyptStudy);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH, $ok['directness']);
    }

    public function test_land_types_egypt_not_direct_for_gerbera_greenhouse(): void
    {
        $understanding = app(QueryUnderstandingService::class)->understand([
            'query' => 'أنواع الأراضي في مصر',
        ]);
        $this->assertSame('land_classification', $understanding->constraints['scientific_sense'] ?? null);

        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'أنواع الأراضي في مصر',
        ]);
        $layer = app(EvidenceVerificationLayer::class);

        $offtopic = new ScientificSearchResult(
            'consensus',
            'c3',
            'Gerbera cultivation in polyhouse greenhouse Egypt',
            ['A'],
            2019,
            '10.1000/gerbera',
            null,
            'Gerbera rose cucumber production under polyhouse greenhouse hydroponics in Egypt.',
            'J',
            ['consensus'],
            ['countries_of_study' => ['eg']],
            ['consensus' => [
                'countries_of_study' => ['eg'],
                'title' => 'Gerbera cultivation in polyhouse greenhouse Egypt',
                'abstract' => 'Gerbera rose cucumber production under polyhouse greenhouse hydroponics in Egypt.',
            ]],
        );

        $assessment = $layer->assess($plan, $offtopic);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertContains(
            $assessment['directness'],
            [
                ScientificEvidenceDirectnessAssessor::IRRELEVANT,
                ScientificEvidenceDirectnessAssessor::RELATED,
                ScientificEvidenceDirectnessAssessor::BACKGROUND,
            ],
        );
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $assessment['directness']);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
    }

    public function test_environment_open_field_vs_greenhouse_demotes_direct(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato heat stress in open field cultivation',
        ]);
        $this->assertSame('open_field', $plan->normalizedQuery->constraints['production_system'] ?? null);

        $layer = app(EvidenceVerificationLayer::class);
        $result = new ScientificSearchResult(
            'openalex',
            'W9',
            'Tomato heat stress under greenhouse protected cultivation',
            ['A'],
            2022,
            '10.1000/gh-tomato',
            null,
            'Tomato heat stress physiology was evaluated in greenhouse polyhouse protected cultivation systems.',
            'J',
            ['openalex'],
        );

        // Force a base DIRECT-like path by using verification refine after assess.
        $assessment = $layer->assess($plan, $result);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        // When base assessor would have returned DIRECT, verification demotes with this reason;
        // otherwise relevance already blocked DIRECT — either path is acceptable.
        if (in_array('production_environment_mismatch', $assessment['reasons'], true)) {
            // Env mismatch → RELATED (not SUPPORTING); never primary-citation eligible.
            $this->assertSame(ScientificEvidenceDirectnessAssessor::RELATED, $assessment['directness']);
            $this->assertFalse($layer->isPrimaryCitationEligible($assessment['directness']));
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::SUPPORTING, $assessment['directness']);
            $this->assertNotSame(ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH, $assessment['directness']);
        }
    }

    public function test_semantic_score_cannot_force_direct_label(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'wheat irrigation requirement',
        ]);
        $layer = app(EvidenceVerificationLayer::class);

        $result = new ScientificSearchResult(
            'consensus',
            'c4',
            'Unrelated marine biology survey',
            ['A'],
            2020,
            null,
            null,
            'Ocean salinity plankton dynamics unrelated to crops.',
            'J',
            ['consensus'],
            ['semantic_score' => 0.99, 'citation_count' => 500],
            ['consensus' => [
                'title' => 'Unrelated marine biology survey',
                'abstract' => 'Ocean salinity plankton dynamics unrelated to crops.',
                'semantic_score' => 0.99,
            ]],
            0.99,
        );

        $assessment = $layer->assess($plan, $result);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $assessment['directness']);
        $this->assertNotSame(EvidenceVerificationLayer::LABEL_DIRECT, $assessment['verification_label']);
    }

    public function test_location_iso_mapping_for_catalog_countries(): void
    {
        $this->assertSame('eg', AgriculturalEntityCatalog::locationToIsoCountryCode('Egypt'));
        $this->assertSame('sa', AgriculturalEntityCatalog::locationToIsoCountryCode('Saudi Arabia'));
        $this->assertSame('tr', AgriculturalEntityCatalog::locationToIsoCountryCode('Turkey'));
    }

    public function test_composer_rejects_geographic_mismatch_as_primary_citation(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'soil classification in Egypt',
        ]);

        $layer = app(EvidenceVerificationLayer::class);
        // Primary-citation eligibility matrix: DIRECT only.
        $this->assertTrue($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::DIRECT,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::RELATED,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible(
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
        ));
        $this->assertFalse($layer->isPrimaryCitationEligible('unknown_label'));

        $composer = app(AnswerComposer::class);
        $geoOnly = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'geo-india',
                'Soil classification survey conducted in India across arid zones.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Soil classification study in India',
                    'directness' => ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                    'claimTopic' => 'soil classification',
                ],
            ),
        ]));
        $this->assertContains($geoOnly->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertSame([], $geoOnly->citations);

        $mixed = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'geo-india',
                'Soil classification survey conducted in India across arid zones.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Soil classification study in India',
                    'directness' => ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                    'claimTopic' => 'soil classification',
                ],
            ),
            $this->composerUsableEvidence(
                'egypt-direct',
                'Land classification of Egyptian soils under arid climates shows distinct soil types.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Egyptian soil land types survey',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'claimTopic' => 'soil classification Egypt',
                ],
            ),
        ]));
        $this->assertNotContains($mixed->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertCount(1, $mixed->citations);
        $this->assertSame('egypt-direct', $mixed->citations[0]->evidenceId);
        $this->assertSame(1, $mixed->researchMetadata['direct_evidence_count'] ?? 0);
    }

    public function test_composer_direct_survives_with_irrelevant_and_geo_excluded(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato heat stress open field',
        ]);
        $composer = app(AnswerComposer::class);

        $report = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'irrelevant',
                'Marine plankton salinity dynamics unrelated to crops.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Ocean salinity plankton survey',
                    'directness' => ScientificEvidenceDirectnessAssessor::IRRELEVANT,
                ],
            ),
            $this->composerUsableEvidence(
                'geo',
                'Tomato heat stress trials conducted exclusively in Brazil field sites.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato heat stress Brazil',
                    'directness' => ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                    'claimTopic' => 'tomato heat',
                ],
            ),
            $this->composerUsableEvidence(
                'support',
                'Tomato physiology notes under heat without open-field trial specifics.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Tomato heat physiology supporting notes',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'claimTopic' => 'tomato heat stress',
                ],
            ),
            $this->composerUsableEvidence(
                'direct',
                'Tomato plants in open-field conditions showed reduced growth under heat stress above 35 C.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato heat stress physiology in open field systems',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'claimTopic' => 'tomato heat stress',
                ],
            ),
        ]));

        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertCount(1, $report->citations);
        $this->assertSame('direct', $report->citations[0]->evidenceId);
        $this->assertSame(1, $report->researchMetadata['direct_evidence_count'] ?? 0);
        $citedIds = array_map(static fn ($c) => $c->evidenceId, $report->citations);
        $this->assertNotContains('irrelevant', $citedIds);
        $this->assertNotContains('geo', $citedIds);
        $this->assertNotContains('support', $citedIds);
        foreach ($report->citations as $citation) {
            $this->assertSame('direct', $citation->evidenceId);
        }
    }

    public function test_composer_supporting_cannot_force_direct_or_invent_answer(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'optimal tomato germination temperature',
        ]);
        $composer = app(AnswerComposer::class);

        $supportingOnly = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'support',
                'Tomato seed treatments with rhizobacteria improved germination percentage under laboratory trays.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Rhizobacteria tomato germination without thermal optima',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'claimTopic' => 'tomato germination',
                    'confidence' => 0.4,
                ],
            ),
        ]));
        // Weak supporting alone must not invent a DIRECT answer for a strict crop+factor query.
        $this->assertContains($supportingOnly->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertSame(0, (int) ($supportingOnly->researchMetadata['direct_evidence_count'] ?? 0));

        $strongSupporting = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'support-strong',
                'Tomato germination responds to thermal regimes near 25 C under controlled seed testing.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato seed germination thermal regimes',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                    'claimTopic' => 'tomato germination temperature',
                    'confidence' => 0.7,
                ],
            ),
        ]));
        // Strong SUPPORTING may synthesize partially, but must not be counted as DIRECT
        // and must not appear in primary citations[] (DIRECT-only eligibility).
        if (! in_array($strongSupporting->status, ['no_validated_evidence', 'insufficient_evidence'], true)) {
            $this->assertSame(0, (int) ($strongSupporting->researchMetadata['direct_evidence_count'] ?? 0));
            $this->assertGreaterThanOrEqual(1, (int) ($strongSupporting->researchMetadata['supporting_evidence_count'] ?? 0));
            foreach ($strongSupporting->evidenceReferences as $ref) {
                $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $ref['evidence_directness'] ?? null);
            }
            $this->assertSame([], $strongSupporting->citations);
            $citedIds = array_map(static fn ($c) => $c->evidenceId, $strongSupporting->citations);
            $this->assertNotContains('support-strong', $citedIds);
        } else {
            $this->assertSame([], $strongSupporting->citations);
        }

        // SUPPORTED directness may feed synthesis, but never primary citations[].
        $supportedDirectness = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'supported-label',
                'Tomato germination thermal optima near 25 C are documented in seed physiology reviews.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato germination temperature supported review',
                    'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTED,
                    'claimTopic' => 'tomato germination temperature',
                    'confidence' => 0.75,
                ],
            ),
        ]));
        $this->assertSame([], $supportedDirectness->citations);
        $this->assertSame(0, (int) ($supportedDirectness->researchMetadata['direct_evidence_count'] ?? 0));
        if (! in_array($supportedDirectness->status, ['no_validated_evidence', 'insufficient_evidence'], true)) {
            $this->assertGreaterThanOrEqual(1, (int) ($supportedDirectness->researchMetadata['supporting_evidence_count'] ?? 0));
        }
    }

    public function test_composer_insufficient_when_only_background_or_related(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'optimal tomato germination temperature',
        ]);
        $composer = app(AnswerComposer::class);

        $report = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'bg',
                'This overview will briefly discuss general horticulture history.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'General horticulture overview',
                    'directness' => ScientificEvidenceDirectnessAssessor::BACKGROUND,
                ],
            ),
            $this->composerUsableEvidence(
                'related',
                'Related crop physiology notes without tomato germination optima.',
                ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
                [
                    'publicationTitle' => 'Related physiology notes',
                    'directness' => ScientificEvidenceDirectnessAssessor::RELATED,
                ],
            ),
        ]));

        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertSame([], $report->citations);
        $this->assertSame([], $report->keyFindings);
        $this->assertTrue(
            str_contains(mb_strtolower($report->answer), 'insufficient')
            || str_contains(mb_strtolower($report->answer), 'not sufficiently relevant'),
        );
    }

    public function test_composer_egypt_geo_mismatch_excluded_from_primary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'land types in Egypt',
        ]);
        $layer = app(EvidenceVerificationLayer::class);
        $composer = app(AnswerComposer::class);

        $indiaResult = new ScientificSearchResult(
            'consensus',
            'c-india',
            'Soil classification study in India',
            ['A'],
            2020,
            '10.1000/india-soil-composer',
            null,
            'A soil classification survey conducted in India across arid zones.',
            'J',
            ['consensus'],
            ['countries_of_study' => ['in']],
            ['consensus' => [
                'countries_of_study' => ['in'],
                'title' => 'Soil classification study in India',
                'abstract' => 'A soil classification survey conducted in India across arid zones.',
            ]],
        );
        $assessment = $layer->assess($plan, $indiaResult);
        $this->assertSame(ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH, $assessment['directness']);

        $report = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'india-geo',
                'A soil classification survey conducted in India across arid zones.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Soil classification study in India',
                    'directness' => $assessment['directness'],
                    'claimTopic' => 'land types',
                ],
            ),
        ]));
        $this->assertContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertSame([], $report->citations);
    }

    public function test_composer_open_field_vs_greenhouse_and_hydroponics_not_direct_primary(): void
    {
        $openFieldPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato heat stress in open field cultivation',
        ]);
        $this->assertSame('open_field', $openFieldPlan->normalizedQuery->constraints['production_system'] ?? null);

        $layer = app(EvidenceVerificationLayer::class);
        $composer = app(AnswerComposer::class);

        $greenhouseResult = new ScientificSearchResult(
            'openalex',
            'W-gh',
            'Tomato heat stress under greenhouse protected cultivation',
            ['A'],
            2022,
            '10.1000/gh-tomato-composer',
            null,
            'Tomato heat stress physiology was evaluated in greenhouse polyhouse protected cultivation systems.',
            'J',
            ['openalex'],
        );
        $ghAssessment = $layer->assess($openFieldPlan, $greenhouseResult);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $ghAssessment['directness']);
        if (in_array('production_environment_mismatch', $ghAssessment['reasons'], true)) {
            $this->assertSame(ScientificEvidenceDirectnessAssessor::RELATED, $ghAssessment['directness']);
        }
        $this->assertFalse($layer->isPrimaryCitationEligible($ghAssessment['directness']));

        $ghReport = $composer->compose($openFieldPlan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'gh-only',
                'Tomato heat stress physiology was evaluated in greenhouse polyhouse protected cultivation systems.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato heat stress under greenhouse protected cultivation',
                    'directness' => $ghAssessment['directness'],
                    'claimTopic' => 'tomato heat stress',
                ],
            ),
        ]));
        $this->assertSame([], $ghReport->citations);
        if (! in_array($ghReport->status, ['no_validated_evidence', 'insufficient_evidence'], true)) {
            $this->assertSame(0, (int) ($ghReport->researchMetadata['direct_evidence_count'] ?? 0));
            foreach ($ghReport->evidenceReferences as $ref) {
                $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $ref['evidence_directness'] ?? null);
            }
        }

        $hydroPlan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'tomato growth in hydroponics systems',
        ]);
        $openFieldEvidence = new ScientificSearchResult(
            'openalex',
            'W-of',
            'Tomato growth under open field rainfed cultivation',
            ['A'],
            2021,
            '10.1000/of-tomato-composer',
            null,
            'Tomato vegetative growth was measured in open-field outdoor cultivation without hydroponics.',
            'J',
            ['openalex'],
        );
        $hydroAssessment = $layer->assess($hydroPlan, $openFieldEvidence);
        $this->assertNotSame(ScientificEvidenceDirectnessAssessor::DIRECT, $hydroAssessment['directness']);
        if (in_array('production_environment_mismatch', $hydroAssessment['reasons'], true)) {
            $this->assertSame(ScientificEvidenceDirectnessAssessor::RELATED, $hydroAssessment['directness']);
        }
        $this->assertFalse($layer->isPrimaryCitationEligible($hydroAssessment['directness']));

        $hydroReport = $composer->compose($hydroPlan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'of-for-hydro',
                'Tomato vegetative growth was measured in open-field outdoor cultivation without hydroponics.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Tomato growth under open field rainfed cultivation',
                    'directness' => $hydroAssessment['directness'],
                    'claimTopic' => 'tomato hydroponics',
                ],
            ),
        ]));
        $this->assertSame([], $hydroReport->citations);
        if (! in_array($hydroReport->status, ['no_validated_evidence', 'insufficient_evidence'], true)) {
            $this->assertSame(0, (int) ($hydroReport->researchMetadata['direct_evidence_count'] ?? 0));
        }
    }

    public function test_composer_ginger_growth_excludes_essential_oil_irrelevant_from_primary(): void
    {
        $plan = app(ResearchPlanner::class)->planKnowledgeQuery([
            'query' => 'ginger growth temperature requirements',
        ]);
        $composer = app(AnswerComposer::class);

        $report = $composer->compose($plan, $this->composerValidationReport([
            $this->composerUsableEvidence(
                'oil',
                'Ginger essential oil chemical composition and antimicrobial activity of Zingiber officinale extracts.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Essential oil composition of Zingiber officinale',
                    'directness' => ScientificEvidenceDirectnessAssessor::IRRELEVANT,
                    'claimTopic' => 'ginger essential oil',
                ],
            ),
            $this->composerUsableEvidence(
                'growth',
                'Zingiber officinale rhizome growth responds optimally near 25-30 C under field temperature regimes.',
                ClaimEvidenceRelationship::SUPPORTED,
                [
                    'publicationTitle' => 'Ginger rhizome growth temperature optima',
                    'directness' => ScientificEvidenceDirectnessAssessor::DIRECT,
                    'claimTopic' => 'ginger growth temperature',
                ],
            ),
        ]));

        $this->assertNotContains($report->status, ['no_validated_evidence', 'insufficient_evidence']);
        $this->assertCount(1, $report->citations);
        $this->assertSame('growth', $report->citations[0]->evidenceId);
        $citedIds = array_map(static fn ($c) => $c->evidenceId, $report->citations);
        $this->assertNotContains('oil', $citedIds);
        $this->assertSame(1, $report->researchMetadata['direct_evidence_count'] ?? 0);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     */
    private function composerValidationReport(array $items): EvidenceValidationExecutionReport
    {
        return new EvidenceValidationExecutionReport(
            status: 'validation_completed',
            validatedEvidence: $items,
            rejectedEvidence: [],
            sourcesReceived: count($items),
            validatedCount: count($items),
            rejectedCount: 0,
            duplicateCount: 0,
            conflictingCount: 0,
            evidenceSufficient: $items !== [],
            validatorsUsed: [],
            qualityDistribution: [],
            searchSummary: [],
            observability: [],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function composerUsableEvidence(
        string $evidenceId,
        string $text,
        string $relationship,
        array $overrides = [],
    ): ScientificEvidenceItem {
        $directness = $overrides['directness'] ?? ScientificEvidenceDirectnessAssessor::DIRECT;
        $confidence = (float) ($overrides['confidence'] ?? 0.8);

        return new ScientificEvidenceItem(
            evidenceId: $evidenceId,
            sourceId: (string) ($overrides['sourceId'] ?? 'source-'.$evidenceId),
            sourceKey: 'openalex',
            sourceType: (string) ($overrides['sourceType'] ?? 'university_research'),
            publicationTitle: (string) ($overrides['publicationTitle'] ?? 'Scientific publication title'),
            authors: ['Dr Researcher'],
            institution: (string) ($overrides['institution'] ?? 'University of Agriculture'),
            journal: 'Journal of Agronomy',
            doi: array_key_exists('doi', $overrides) ? $overrides['doi'] : '10.1000/'.$evidenceId,
            url: array_key_exists('url', $overrides) ? $overrides['url'] : 'https://doi.org/10.1000/'.$evidenceId,
            publicationYear: 2023,
            retrievedAt: now()->toIso8601String(),
            agriculturalDomain: 'field_crops',
            claimTopic: (string) ($overrides['claimTopic'] ?? 'topic'),
            evidenceText: $text,
            validationStatus: EvidenceValidationStatus::EVIDENCE_USABLE,
            validationFailures: [],
            claimRelationship: $relationship,
            confidence: $confidence,
            qualityScore: 75.0,
            qualityFactors: [
                'not_scientific_certainty' => true,
                'evidence_directness' => $directness,
                'verification_label' => app(EvidenceVerificationLayer::class)->toVerificationLabel((string) $directness),
            ],
            sourceAttribution: [
                'organization' => 'University of Agriculture',
                'source_type' => 'university_research',
                'evidence_directness' => $directness,
            ],
        );
    }
}
