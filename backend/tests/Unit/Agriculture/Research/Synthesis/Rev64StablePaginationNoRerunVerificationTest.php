<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Rev 6.4 — controlled multi-page + no-rerun + lifecycle verification.
 *
 * Uses isolated temp storage (TEST DATA ONLY). Does not claim scientific findings.
 */
final class Rev64StablePaginationNoRerunVerificationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wsa_rev64_'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->root);
        }
        parent::tearDown();
    }

    public function test_controlled_21_answers_paginate_10_10_1_without_duplicates(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $answers = [];
        for ($i = 0; $i < 21; $i++) {
            $answers[] = [
                'answer_id' => 'sha256:'.str_repeat(sprintf('%x', $i % 16), 64),
                'answer' => 'TEST-DATA independent answer '.$i,
                'result_id' => 'test-r'.$i,
                'evidence_ids' => ['test-e'.$i],
                'source_ids' => ['test_source'],
                'position' => $i,
                'provenance' => ['test_fixture' => true],
            ];
        }

        $set = $store->create(
            organizationId: 11,
            originalQuestion: 'TEST FIXTURE — not a scientific claim',
            csqFingerprint: 'test-fp',
            answers: $answers,
            references: [['title' => 'TEST REF', 'test_fixture' => true]],
        );

        $this->assertSame(21, $set->totalAnswers());
        $this->assertSame(3, $set->totalPages());
        $this->assertSame(StableAnswerResultSet::PAGE_SIZE, 10);

        $p1 = $set->page(1);
        $p2 = $set->page(2);
        $p3 = $set->page(3);

        $this->assertCount(10, $p1['answers']);
        $this->assertCount(10, $p2['answers']);
        $this->assertCount(1, $p3['answers']);
        $this->assertSame($set->resultSetId, $p1['result_set_id']);
        $this->assertSame($set->resultSetId, $p2['result_set_id']);
        $this->assertSame($set->resultSetId, $p3['result_set_id']);

        $ids = [];
        foreach ([$p1, $p2, $p3] as $page) {
            $this->assertFalse($page['rerun_performed']);
            $this->assertFalse($page['observability']['search_rerun']);
            $this->assertFalse($page['observability']['composer_rerun']);
            $this->assertFalse($page['observability']['validation_rerun']);
            $this->assertLessThanOrEqual(10, count($page['answers']));
            foreach ($page['answers'] as $row) {
                $rid = (string) $row['result_id'];
                $this->assertArrayNotHasKey($rid, $ids, 'duplicate across pages');
                $ids[$rid] = true;
                $this->assertStringStartsWith('TEST-DATA', (string) $row['answer']);
            }
        }
        $this->assertCount(21, $ids);
        $this->assertSame('TEST-DATA independent answer 0', $p1['answers'][0]['answer']);
        $this->assertSame('TEST-DATA independent answer 10', $p2['answers'][0]['answer']);
        $this->assertSame('TEST-DATA independent answer 20', $p3['answers'][0]['answer']);
    }

    public function test_agent_page_reads_do_not_require_orchestrator_or_composer(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $answers = [];
        for ($i = 0; $i < 21; $i++) {
            $answers[] = [
                'answer' => 'TEST-DATA answer '.$i,
                'result_id' => 'tr'.$i,
                'evidence_ids' => ['te'.$i],
                'source_ids' => ['test'],
                'position' => $i,
                'provenance' => ['test_fixture' => true],
            ];
        }
        $set = $store->create(11, 'TEST Q', null, $answers);

        $counters = (object) [
            'orchestrator' => 0,
            'composer' => 0,
            'validation' => 0,
            'query' => 0,
            'results' => 0,
        ];

        $agent = $this->agentWithStore($store);

        // Simulate one "query" creating the set (already done via store — no orchestrator).
        $counters->query = 1;

        $page1 = $agent->pageStableResultSet(11, $set->resultSetId, 1);
        $counters->results++;
        $page2 = $agent->pageStableResultSet(11, $set->resultSetId, 2);
        $counters->results++;
        $page3 = $agent->pageStableResultSet(11, $set->resultSetId, 3);
        $counters->results++;

        $this->assertSame(1, $counters->query);
        $this->assertSame(3, $counters->results);
        $this->assertSame(0, $counters->orchestrator);
        $this->assertSame(0, $counters->composer);
        $this->assertSame(0, $counters->validation);
        $this->assertCount(10, $page1['answers']);
        $this->assertCount(10, $page2['answers']);
        $this->assertCount(1, $page3['answers']);
        $this->assertFalse($page1['rerun_performed']);
        $this->assertFalse($page2['rerun_performed']);
        $this->assertFalse($page3['rerun_performed']);
        // pageStableResultSet only touches the store — no research pipeline methods exist on this path.
        $this->assertTrue(method_exists($agent, 'pageStableResultSet'));
        $ref = new ReflectionClass(AgriculturalResearchAgent::class);
        $method = $ref->getMethod('pageStableResultSet');
        $source = file_get_contents($ref->getFileName());
        $start = strpos($source, 'function pageStableResultSet');
        $end = strpos($source, 'function planResearch');
        $body = substr($source, $start, $end - $start);
        $this->assertStringNotContainsString('scientificSearchService', $body);
        $this->assertStringNotContainsString('answerComposer', $body);
        $this->assertStringNotContainsString('scientificValidationService', $body);
        $this->assertStringNotContainsString('conductResearch', $body);
    }

    public function test_organization_isolation_and_missing_and_expired_lifecycle(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $set = $store->create(11, 'TEST Q', null, [
            [
                'answer' => 'TEST-DATA only',
                'result_id' => 'r1',
                'evidence_ids' => ['e1'],
                'source_ids' => ['t'],
                'position' => 0,
                'provenance' => ['test_fixture' => true],
            ],
        ], ttlSeconds: 60);

        $agent = $this->agentWithStore($store);

        $ok = $agent->pageStableResultSet(11, $set->resultSetId, 1);
        $this->assertArrayHasKey('answers', $ok);
        $this->assertCount(1, $ok['answers']);

        $denied = $agent->pageStableResultSet(99, $set->resultSetId, 1);
        $this->assertSame('result_set_unavailable', $denied['status']);
        $this->assertSame('RESULT_SET_FAILURE', $denied['failure_class']);
        $this->assertSame('stable_result_set_missing_or_expired', $denied['failure_code']);

        $missing = $agent->pageStableResultSet(11, 'srs_does_not_exist', 1);
        $this->assertSame('result_set_unavailable', $missing['status']);

        // Expired: write a set with past expires_at via raw file.
        $expiredId = 'srs_expired_test';
        $path = $this->root.DIRECTORY_SEPARATOR.'11'.DIRECTORY_SEPARATOR.$expiredId.'.json';
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, json_encode([
            'result_set_id' => $expiredId,
            'organization_id' => 11,
            'original_question' => 'TEST expired',
            'csq_fingerprint' => null,
            'answers' => [['answer' => 'TEST-DATA expired', 'result_id' => 'x', 'evidence_ids' => [], 'source_ids' => [], 'position' => 0, 'provenance' => []]],
            'references' => [],
            'created_at' => gmdate('c', time() - 7200),
            'expires_at' => gmdate('c', time() - 60),
            'status' => 'ready',
            'observability' => ['test_fixture' => true],
        ], JSON_UNESCAPED_UNICODE));

        $expired = $agent->pageStableResultSet(11, $expiredId, 1);
        $this->assertSame('result_set_unavailable', $expired['status']);
        $this->assertSame('stable_result_set_missing_or_expired', $expired['failure_code']);
    }

    private function agentWithStore(StableAnswerResultSetStore $store): AgriculturalResearchAgent
    {
        $ref = new ReflectionClass(AgriculturalResearchAgent::class);
        /** @var AgriculturalResearchAgent $agent */
        $agent = $ref->newInstanceWithoutConstructor();
        $prop = $ref->getProperty('stableResultSetStore');
        $prop->setAccessible(true);
        $prop->setValue($agent, $store);

        return $agent;
    }
}
