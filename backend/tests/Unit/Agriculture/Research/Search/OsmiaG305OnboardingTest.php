<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalKnowledgeQuery;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Onboarding\CurrentMembershipNearReadySeatCatalog;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetReader;
use App\Services\Agriculture\Research\Search\Family\Bulk\BulkDatasetSourceProfile;
use App\Services\Agriculture\Research\Search\Family\Bulk\OsmiaCrossrefSnapshotBuilder;
use App\Services\Agriculture\Research\Search\Family\OaiPmh\OaiPmhHarvester;
use App\Services\Agriculture\Research\Search\GovernedScientificSourceEligibility;
use App\Services\Agriculture\Research\Search\LocalIndex\InMemoryScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexScientificSourceAdapter;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalRecordRetentionPolicy;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\OsmiaLocalIndexQuerySpecializer;
use App\Services\Agriculture\Research\Search\Registry\ScientificSourceCatalog;
use DateTimeImmutable;
use Tests\TestCase;

final class OsmiaG305OnboardingTest extends TestCase
{
    public function test_near_ready_seat_is_runtime_active_with_cc_by_licensing(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G3-05');
        $this->assertNotNull($seat);
        $this->assertSame('osmia', $seat->sourceKey);
        $this->assertTrue($seat->isRuntimeActive());
        $this->assertInstanceOf(BulkDatasetSourceProfile::class, $seat->familyProfile);
        $licensing = $seat->familyProfile->licensing();
        $this->assertTrue($licensing->permits(
            \App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage::METADATA_STORAGE
        ));
        // CC BY 4.0 work license: abstracts retained for LocalIndex evidence (not PDF archive).
        $this->assertTrue($licensing->permits(
            \App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage::FULL_TEXT_STORAGE
        ));
        $this->assertTrue($licensing->permits(
            \App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage::EXCERPT_DISPLAY
        ));
    }

    public function test_catalog_registers_osmia_governed_adapter(): void
    {
        $catalog = ScientificSourceCatalog::default();
        $this->assertTrue($catalog->has('osmia'));
        $reg = $catalog->get('osmia');
        $this->assertSame(LocalIndexScientificSourceAdapter::class, $reg?->adapterClass);
        $this->assertSame(
            \App\Services\Agriculture\Research\Search\ScientificKnowledgeType::SCHOLARLY_EVIDENCE,
            \App\Services\Agriculture\Research\Search\ScientificKnowledgeType::forSourceKey('osmia'),
        );
    }

    public function test_eligibility_requires_bee_or_pollinator_cues(): void
    {
        $gate = new GovernedScientificSourceEligibility();
        $bee = $this->plan('What is the effect of habitat on wild bee diversity?');
        $wheat = $this->plan('What is the crop water requirement of wheat?');
        $this->assertTrue($gate->isEligible('osmia', $bee));
        $this->assertFalse($gate->isEligible('osmia', $wheat));
    }

    public function test_osmia_query_specializer_avoids_generic_agriculture_variant(): void
    {
        $plan = $this->plan('What is the taxonomic classification of bees regarding Melittidae and Apis mellifera?');
        $queries = (new OsmiaLocalIndexQuerySpecializer())->retrievalQueries($plan);
        $this->assertNotEmpty($queries);
        $blob = mb_strtolower(implode(' | ', $queries));
        $this->assertStringNotContainsString('agriculture general knowledge', $blob);
        $this->assertTrue(
            str_contains($blob, 'melittidae')
            || str_contains($blob, 'apis mellifera')
            || str_contains($blob, 'classification'),
        );
    }

    public function test_osmia_query_specializer_prioritizes_latin_taxon_over_discourse_binomials(): void
    {
        foreach ([
            'What host associations are known for Nomada wild bees?',
            'Quelles associations d\'hôtes sont connues pour les abeilles Nomada?',
            'ما ارتباطات العائل المعروفة لنحل Nomada البري؟',
            'Nomada yabani arılarının bilinen konukçu ilişkileri nelerdir?',
        ] as $question) {
            $queries = (new OsmiaLocalIndexQuerySpecializer())->retrievalQueries($this->plan($question));
            $this->assertNotEmpty($queries, $question);
            $firstTwo = array_slice($queries, 0, 2);
            $this->assertTrue(
                in_array('Nomada', $firstTwo, true),
                'Nomada must occupy a first-two LocalIndex slot for: '.$question.' got '.json_encode($queries)
            );
            foreach ($firstTwo as $q) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^(What|Quelles)(\s|$)/u',
                    $q,
                    'Discourse openers must not occupy early OSMIA slots'
                );
            }
        }
    }

    public function test_jsonl_fixture_ingests_and_is_searchable(): void
    {
        $seat = CurrentMembershipNearReadySeatCatalog::get('G3-05');
        $this->assertNotNull($seat);
        $profile = $seat->familyProfile;
        $this->assertInstanceOf(BulkDatasetSourceProfile::class, $profile);

        $path = storage_path('app/e2e-tmp/osmia-fixture.jsonl');
        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode([
            'id' => '10.47446/osmia10.7',
            'title' => 'Discovery of Nomada pulchra and wild bee host associations',
            'abstract' => 'Wild bee Nomada pulchra recorded with pollinator habitat notes.',
            'doi' => '10.47446/osmia10.7',
            'url' => 'https://doi.org/10.47446/osmia10.7',
            'published' => '2022-12-26',
            'year' => 2022,
            'authors' => 'Durand, O.; Bourlet, P.',
            'journal' => 'Osmia',
        ], JSON_UNESCAPED_UNICODE)."\n");

        $store = new InMemoryScientificRecordStore();
        $index = new LocalScientificIndex();
        $report = (new LocalIndexIngestionService(
            $store,
            $index,
            new LocalRecordRetentionPolicy(),
            new OaiPmhHarvester(),
            new BulkDatasetReader(),
        ))->ingestSnapshot($profile, $path, new DateTimeImmutable('2026-11-24T18:00:00+00:00'));

        $this->assertSame('completed', $report->status);
        $this->assertGreaterThanOrEqual(1, $store->generation(OsmiaCrossrefSnapshotBuilder::SOURCE_KEY));

        $adapter = new LocalIndexScientificSourceAdapter($profile, $index, $store);
        $outcome = $adapter->search('wild bee Nomada', 5);
        $this->assertSame('success', $outcome->status);
        $this->assertNotEmpty($outcome->results);
        $this->assertSame('osmia', $outcome->results[0]->sourceKey);
    }

    private function plan(string $question): KnowledgeQueryPlan
    {
        $query = new AgriculturalKnowledgeQuery(
            originalQuestion: $question,
            normalizedQuestion: mb_strtolower($question),
            language: 'en',
            agriculturalDomain: 'general_agriculture',
            subject: null,
            crop: null,
            cropId: null,
            scientificName: null,
            topic: 'agriculture',
            subtopic: null,
            requestedInformation: ['evidence_backed_guidance'],
            constraints: [
                'question_language' => 'en',
            ],
            location: null,
            researchRequired: true,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
            researchIntent: 'scientific_research',
        );

        return new KnowledgeQueryPlan(
            normalizedQuery: $query,
            researchIntent: 'scientific_research',
            agriculturalDomain: 'general_agriculture',
            subjectEntity: null,
            topics: ['agriculture'],
            subtopics: [],
            requestedInformation: ['evidence_backed_guidance'],
            evidenceRequirements: [],
            sourcePriorities: ['osmia'],
            primaryResearchStrategy: KnowledgeQueryPlan::STRATEGY_INTERNET_FIRST,
            researchSequence: KnowledgeQueryPlan::STAGE_EXECUTION_SEQUENCE,
            ambiguityState: AgriculturalKnowledgeQuery::AMBIGUITY_CLEAR,
            clarificationRequirements: [],
        );
    }
}
